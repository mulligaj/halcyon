<?php
namespace App\Modules\Groups\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Modules\History\Traits\Historable;

/**
 * Group department
 *
 * @property int    $id
 * @property int    $parentid
 * @property string $name
 */
class Department extends Model
{
	use Historable;

	/**
	 * Timestamps
	 *
	 * @var  bool
	 **/
	public $timestamps = false;

	/**
	 * The table to which the class pertains
	 *
	 * @var  string
	 **/
	protected $table = 'collegedept';

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
		'id'
	];

	/**
	 * Fields and their validation criteria
	 *
	 * @var  array<string,string>
	 */
	protected $rules = array(
		'name' => 'required'
	);

	/**
	 * The "booted" method of the model.
	 *
	 * @return void
	 */
	protected static function booted(): void
	{
		static::creating(function ($model)
		{
			$result = self::query()
				->select(DB::raw('MAX(id) + 1 AS seq'))
				->get()
				->first()
				->seq;

			$model->setAttribute('id', (int)$result);
		});
	}

	/**
	 * Get records as nested tree
	 *
	 * @param   string  $order
	 * @param   string  $dir
	 * @return  array
	 */
	public static function tree(string $order = 'name', string $dir = 'asc'): array
	{
		$rows = self::query()
			->withCount('groups')
			->orderBy($order, $dir)
			->get();

		$list = array();

		if (count($rows) > 0)
		{
			$levellimit = 9999;
			$list       = array();
			$children   = array();

			// First pass - collect children
			foreach ($rows as $k)
			{
				$pt = $k->parentid;

				if (!isset($children[$pt]))
				{
					$children[$pt] = array();
				}
				$children[$pt][] = $k;
			}

			// Second pass - get an indent list of the items
			$list = self::treeRecurse(0, $list, $children, max(0, $levellimit-1));
		}

		return $list;
	}

	/**
	 * Recursive function to build tree
	 *
	 * @param   int      $id        Parent ID
	 * @param   array    $list      List of records
	 * @param   array    $children  Container for parent/children mapping
	 * @param   int      $maxlevel  Maximum levels to descend
	 * @param   int      $level     Indention level
	 * @param   int      $type      Indention type
	 * @param   string   $prfx
	 * @return  array
	 */
	protected static function treeRecurse(int $id, array $list, array $children, int $maxlevel=9999, int $level=0, int $type=1, string $prfx = ''): array
	{
		if (@$children[$id] && $level <= $maxlevel)
		{
			foreach ($children[$id] as $z => $v)
			{
				$vid = $v->id;
				$pt = $v->parentid;

				$list[$vid] = $v;
				$list[$vid]->prefix = ($prfx ? $prfx . ' › ' : '');
				$list[$vid]->name = $list[$vid]->name;
				$list[$vid]->level = $level;
				$list[$vid]->children_count = isset($children[$vid]) ? count(@$children[$vid]) : 0;

				$p = '';
				if ($v->parentid)
				{
					$p = $list[$vid]->prefix . $list[$vid]->name;
				}

				unset($children[$id][$z]);

				$list = self::treeRecurse($vid, $list, $children, $maxlevel, $level+1, $type, $p);
			}
			unset($children[$id]);
		}
		return $list;
	}

	/**
	 * Parent
	 *
	 * @return  BelongsTo
	 */
	public function parent(): BelongsTo
	{
		return $this->belongsTo(self::class, 'parentid');
	}

	/**
	 * Get all parents
	 *
	 * @param   array<int,Department>  $ancestors
	 * @return  array<int,Department>
	 */
	public function ancestors(array $ancestors = []): array
	{
		$parent = $this->parent;

		if ($parent)
		{
			if ($parent->parentid)
			{
				$ancestors = $parent->ancestors($ancestors);
			}

			$ancestors[] = $parent;
		}

		return $ancestors;
	}

	/**
	 * Relationship to child records
	 *
	 * @return  HasMany
	 */
	public function children(): HasMany
	{
		return $this->hasMany(self::class, 'parentid');
	}

	/**
	 * Groups
	 *
	 * @return  HasMany
	 */
	public function groups(): HasMany
	{
		return $this->hasMany(GroupDepartment::class, 'collegedeptid');
		//return $this->hasOneThrough(GroupFieldOfScience::class, GroupDepartment::class, 'groupid', 'id', 'groupid', 'collegedeptid');
	}

	/**
	 * Delete entry and associated data
	 *
	 * @return  bool
	 */
	public function delete(): bool
	{
		foreach ($this->children as $row)
		{
			$row->delete();
		}

		foreach ($this->groups as $row)
		{
			$row->delete();
		}

		return parent::delete();
	}
}
