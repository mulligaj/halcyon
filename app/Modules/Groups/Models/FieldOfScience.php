<?php
namespace App\Modules\Groups\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Modules\History\Traits\Historable;

/**
 * Field of Science
 *
 * @property int    $id
 * @property int    $parentid
 * @property string $name
 */
class FieldOfScience extends Model
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
	protected $table = 'fieldofscience';

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
		'name' => 'required|string|max:255',
		'parentid' => 'nullable|integer'
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
			// The table is not setup for auto-increment
			$result = self::query()
				->select(DB::raw('MAX(id) + 1 AS seq'))
				->get()
				->first()
				->seq;

			$model->setAttribute('id', (int)$result);
		});

		static::deleted(function ($model)
		{
			foreach ($model->children as $row)
			{
				$row->delete();
			}

			foreach ($model->groups as $row)
			{
				$row->delete();
			}
		});
	}

	/**
	 * Field of science
	 *
	 * @return  BelongsTo
	 */
	public function parent(): BelongsTo
	{
		return $this->belongsTo(self::class, 'parentid');
	}

	/**
	 * Defines a relationship to parent
	 *
	 * @param  string $order
	 * @param  string $dir
	 * @return array
	 */
	public static function tree($order = 'name', $dir = 'asc'): array
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
			$list = self::treeRecurse(0, array(), $children, max(0, $levellimit-1));
		}

		return $list;
	}

	/**
	 * Recursive function to build tree
	 *
	 * @param   int  $id        Parent ID
	 * @param   array    $list      List of records
	 * @param   array    $children  Container for parent/children mapping
	 * @param   int  $maxlevel  Maximum levels to descend
	 * @param   int  $level     Indention level
	 * @return  array
	 */
	protected static function treeRecurse($id, $list, $children, $maxlevel=9999, $level=0, string $prfx = ''): array
	{
		if (@$children[$id] && $level <= $maxlevel)
		{
			foreach ($children[$id] as $z => $v)
			{
				$vid = $v->id;

				$v->level = $level;
				$v->prefix = ($prfx ? $prfx . ' › ' : '');
				$v->children_count = isset($children[$vid]) ? count($children[$vid]) : 0;

				$list[$vid] = $v;

				$p = '';
				if ($v->parentid)
				{
					$p = $list[$vid]->prefix . $list[$vid]->name;
				}

				unset($children[$id][$z]);

				$list = self::treeRecurse($vid, $list, $children, $maxlevel, $level+1, $p);
			}
			unset($children[$id]);
		}
		return $list;
	}

	/**
	 * Get all parents
	 *
	 * @param   array<int,FieldOfScience>  $ancestors
	 * @return  array<int,FieldOfScience>
	 */
	public function ancestors($ancestors = array()): array
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
	 * Groups
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
		return $this->hasMany(GroupFieldOfScience::class, 'fieldofscienceid');
		//return $this->hasOneThrough(GroupFieldOfScience::class, GroupDepartment::class, 'groupid', 'id', 'groupid', 'collegedeptid');
	}
}
