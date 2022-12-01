<?php
namespace App\Modules\Queues\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Queues\Events\QosCreated;
use App\Modules\Queues\Events\QosUpdated;
use App\Modules\Queues\Events\QosDeleted;
use App\Modules\History\Traits\Historable;

/**
 * Model for a queue Quality of Service
 */
class Qos extends Model
{
	use Historable, SoftDeletes;

	/**
	 * The table to which the class pertains
	 *
	 * @var  string
	 **/
	protected $table = 'queueqos';

	/**
	 * The name of the "created at" column.
	 *
	 * @var string
	 */
	const CREATED_AT = 'datetimecreated';

	/**
	 * The name of the "updated at" column.
	 *
	 * @var  string
	 */
	const UPDATED_AT = 'datetimeedited';

	/**
	 * The name of the "deleted at" column.
	 *
	 * @var string
	 */
	const DELETED_AT = 'datetimeremoved';

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
	 * @var array
	 */
	protected $guarded = [
		'id'
	];

	/**
	 * The event map for the model.
	 *
	 * @var array
	 */
	protected $dispatchesEvents = [
		'created'  => QosCreated::class,
		'updated'  => QosUpdated::class,
		'deleted'  => QosDeleted::class,
	];

	/**
	 * Boot
	 *
	 * @return  void
	 */
	public static function boot()
	{
		parent::boot();

		self::deleted(function($model)
		{
			foreach ($model->queueqoses as $queueqos)
			{
				$queueqos->delete();
			}
		});
	}

	/**
	 * Defines a relationship to schedulers
	 *
	 * @return  object
	 */
	public function scheduler()
	{
		return $this->belongsTo(Scheduler::class, 'scheduler_id');
	}

	/**
	 * Defines a direct relationship to queues
	 *
	 * @return object
	 */
	public function queues()
	{
		return $this->hasManyThrough(Queue::class, QueueQos::class, 'qosid', 'id', 'id', 'queueid');
	}

	/**
	 * Defines a relationship to queue qos map
	 *
	 * @return  object
	 */
	public function queueqoses()
	{
		return $this->hasMany(QueueQos::class, 'qosid');
	}

	/**
	 * Get the list of preempts
	 *
	 * @return  array
	 */
	public function getPreemptListAttribute()
	{
		return $this->preempt ? explode("\n", $this->preempt) : [];
	}

	/**
	 * Get the list of preempt modes
	 *
	 * @return  array
	 */
	public function getPreemptModeListAttribute()
	{
		return $this->preempt_mode ? explode(',', $this->preempt_mode) : [];
	}

	/**
	 * Get the list of flags
	 *
	 * @return  array
	 */
	public function getFlagsListAttribute()
	{
		return $this->flags ? explode(',', $this->flags) : [];
	}
}
