<?php

namespace App\Modules\Resources\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Resources\Events\SubresourceCreating;
use App\Modules\Resources\Events\SubresourceCreated;
use App\Modules\Resources\Events\SubresourceUpdating;
use App\Modules\Resources\Events\SubresourceUpdated;
use App\Modules\Resources\Events\SubresourceDeleted;
use App\Modules\History\Traits\Historable;
use App\Modules\Queues\Models\Queue;
use Carbon\Carbon;

/**
 * Model for a subresource mapping
 *
 * @property int    $id
 * @property string $name
 * @property string $cluster
 * @property int    $nodecores
 * @property string $nodemem
 * @property int    $nodegpus
 * @property string $nodeattributes
 * @property string $description
 * @property Carbon|null $datetimeremoved
 * @property Carbon|null $datetimecreated
 * @property int    $notice
 */
class Subresource extends Model
{
	use SoftDeletes, Historable;

	/**
	 * The name of the "created at" column.
	 *
	 * @var string|null
	 */
	const CREATED_AT = 'datetimecreated';

	/**
	 * The name of the "updated at" column.
	 *
	 * @var string|null
	 */
	const UPDATED_AT = null;

	/**
	 * The name of the "deleted at" column.
	 *
	 * @var string|null
	 */
	const DELETED_AT = 'datetimeremoved';

	/**
	 * The table to which the class pertains
	 *
	 * @var  string
	 **/
	protected $table = 'subresources';

	/**
	 * Default order by for model
	 *
	 * @var string
	 */
	public static $orderBy = 'name';

	/**
	 * Default order direction for select queries
	 *
	 * @var  string
	 */
	public static $orderDir = 'asc';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int,string>
	 */
	protected $guarded = [
		'id',
		'datetimecreated',
		'datetimeremoved',
	];

	/**
	 * The event map for the model.
	 *
	 * @var array<string,string>
	 */
	protected $dispatchesEvents = [
		'creating' => SubresourceCreating::class,
		'created'  => SubresourceCreated::class,
		'updating' => SubresourceUpdating::class,
		'updated'  => SubresourceUpdated::class,
		'deleted'  => SubresourceDeleted::class,
	];

	/**
	 * Fields and their validation criteria
	 *
	 * @var array<string,string>
	 */
	protected $rules = array(
		'name'           => 'required|unique:subresource|max:32',
		'cluster'        => 'required|string|max:32',
		'nodecores'      => 'nullable|integer',
		'nodemem'        => 'nullable|string|max:5',
		'nodegpus'       => 'nullable|integer',
		'nodeattributes' => 'nullable|string|max:16',
		'description'    => 'nullable|string|max:255',
		'notice'         => 'nullable|integer',
	);

	/**
	 * Get the resource
	 *
	 * @return object
	 */
	public function resource()
	{
		return $this->hasOneThrough(Asset::class, Child::class, 'subresourceid', 'id', 'subresourceid', 'resourceid')->withTrashed();
	}

	/**
	 * Get queues
	 *
	 * @return HasMany
	 */
	public function queues(): HasMany
	{
		return $this->hasMany(Queue::class, 'subresourceid');
	}

	/**
	 * Get resource/subresource association record
	 *
	 * @return BelongsTo
	 */
	public function association(): BelongsTo
	{
		return $this->belongsTo(Child::class, 'id', 'subresourceid');
	}

	/**
	 * Stop queues
	 *
	 * @return  void
	 */
	public function stopQueues(): void
	{
		$tbl = $this->getTable();
		$name = $this->name;

		$queues = Queue::query()
			->where('subresourceid', '=', $this->id)
			->orWhereIn('subresourceid', function($in) use ($tbl, $name)
			{
				$name = substr($name, 0, strrpos($name, '-'));

				$in->select('id')
					->from($tbl)
					->where('name', '=', $name . '-Nonspecific');
			})
			->get();

		foreach ($queues as $queue)
		{
			$queue->stop();
		}

		// If marked as just started, set back to still stopped
		if ($this->notice == 1)
		{
			$this->notice = 3;
		}
		else
		{
			// If 0, we are just now stopping
			$this->notice = 2;
		}

		$this->update(['notice' => $this->notice]);
	}

	/**
	 * Start queues
	 *
	 * @return  void
	 */
	public function startQueues(): void
	{
		$tbl = $this->getTable();
		$name = $this->name;

		$queues = Queue::query()
			->where('subresourceid', '=', $this->id)
			->orWhereIn('subresourceid', function($in) use ($tbl, $name)
			{
				$name = substr($name, 0, strrpos($name, '-'));

				$in->select('id')
					->from($tbl)
					->where('name', '=', $name . '-Nonspecific');
			})
			->get();

		foreach ($queues as $queue)
		{
			$queue->start();
		}

		// If marked as just started, set back to still stopped
		if ($this->notice == 3)
		{
			$this->notice = 1;
		}
		else
		{
			$this->notice = 0;
		}

		$this->update(['notice' => $this->notice]);
	}

	/**
	 * Calculate total cores and nodes
	 *
	 * @return  void
	 */
	private function sumCoresAndNodes(): void
	{
		$totalcores  = 0;
		$totalnodes  = 0;
		$soldcores   = 0;
		$soldnodes   = 0;
		$loanedcores = 0;
		$loanednodes = 0;

		$now = Carbon::now();

		$query = $this->queues();

		if (!$this->nodecores)
		{
			$query->where('cluster', 'like', 'standby%');
		}

		/*
		"SELECT if (SUM(queueloans.corecount) IS NULL, 0, SUM(queueloans.corecount)) AS loanedcores
		FROM queues, queueloans, subresources
		WHERE queues.subresourceid = subresources.id
		AND queueloans.queueid = queues.id
		AND (queueloans.datetimestop IS NULL OR queueloans.datetimestop > NOW())
		AND (queueloans.datetimestart IS NULL OR queueloans.datetimestart <= NOW())
		AND queues.datetimeremoved IS NULL AND queues.subresourceid = '" . $id . "'
		AND (subresources.nodecores <> 0 OR queues.cluster LIKE 'standby%')
		AND queues.groupid > '0'";
		*/

		$queues = $query->get();

		foreach ($queues as $queue)
		{
			$sizes = $queue->sizes()
				->where(function($where) use ($now)
				{
					$where->whereNull('datetimestop')
						->orWhere('datetimestop', '>', $now->toDateTimeString());
				})
				->where(function($where) use ($now)
				{
					$where->whereNull('datetimestart')
						->orWhere('datetimestop', '<=', $now->toDateTimeString());
				})
				->get();

			foreach ($sizes as $size)
			{
				$totalcores += (int) $size->corecount;
				$totalnodes += (int) $size->nodecount;

				if ($queue->groupid > 0)
				{
					$soldcores += (int) $size->corecount;
					$soldnodes += (int) $size->nodecount;
				}
			}

			if ($queue->groupid > 0)
			{
				$loans = $queue->loans()
					->where(function($where) use ($now)
					{
						$where->whereNull('datetimestop')
							->orWhere('datetimestop', '>', $now->toDateTimeString());
					})
					->where(function($where) use ($now)
					{
						$where->whereNull('datetimestart')
							->orWhere('datetimestart', '<=', $now->toDateTimeString());
					})
					//->where('groupid', '>', 0)
					->get();

				foreach ($loans as $loan)
				{
					$loanedcores += (int) $loan->corecount;
					$loanednodes += (int) $loan->nodecount;
				}
			}
		}

		if ($this->nodecores != 0)
		{
			$totalnodes = round($totalcores/$this->nodecores, 1);
			$soldnodes  = round($soldcores/$this->nodecores, 1);
			$loanednodes = round($loanedcores/$this->nodecores, 1);
		}

		$this->setAttribute('totalcores', $totalcores);
		$this->setAttribute('totalnodes', $totalnodes);
		$this->setAttribute('soldcores', $soldcores);
		$this->setAttribute('soldnodes', $soldnodes);
		$this->setAttribute('loanedcores', $loanedcores);
		$this->setAttribute('loanednodes', $loanednodes);
	}

	/**
	 * Get total cores
	 *
	 * @return  int
	 */
	public function getTotalcoresAttribute(): int
	{
		if (!array_key_exists('totalcores', $this->attributes))
		{
			$this->sumCoresAndNodes();
		}

		return $this->attributes['totalcores'];
	}

	/**
	 * Get total nodes
	 *
	 * @return  int
	 */
	public function getTotalnodesAttribute(): int
	{
		if (!array_key_exists('totalnodes', $this->attributes))
		{
			$this->sumCoresAndNodes();
		}

		return $this->attributes['totalnodes'];
	}

	/**
	 * Get queue status
	 *
	 * @return  int
	 */
	public function getQueuestatusAttribute(): int
	{
		$queuestatus = 1;

		foreach ($this->queues as $queue)
		{
			if ($queue->started)
			{
				$queuestatus = 1;
			}
			elseif ($queuestatus == 1)
			{
				$queuestatus = 2;
				break;
			}
		}

		return $queuestatus;
	}

	/**
	 * Set cluster
	 *
	 * @param   string  $val
	 * @return  void
	 */
	public function setClusterAttribute($val): void
	{
		$this->attributes['cluster'] = strtolower($val);
	}

	/**
	 * Delete the record and all associated data
	 *
	 * @return  bool  False if error, True on success
	 */
	public function delete(): bool
	{
		foreach ($this->queues as $row)
		{
			$row->delete();
		}

		return parent::delete();
	}
}
