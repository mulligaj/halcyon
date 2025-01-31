<?php

namespace App\Modules\Storage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\History\Traits\Historable;
use App\Halcyon\Utility\Number;

/**
 * Storage resource model
 */
class StorageResource extends Model
{
	use Historable, SoftDeletes;

	/**
	 * The name of the "created at" column.
	 *
	 * @var string|null
	 */
	const CREATED_AT = 'datetimecreated';

	/**
	 * The name of the "updated at" column.
	 *
	 * @var  string|null
	 */
	const UPDATED_AT = null;

	/**
	 * The name of the "deleted at" column.
	 *
	 * @var  string|null
	 */
	const DELETED_AT = 'datetimeremoved';

	/**
	 * The table to which the class pertains
	 *
	 * @var  string
	 **/
	protected $table = 'storageresources';

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
	 * The "booted" method of the model.
	 *
	 * @return void
	 */
	protected static function booted()
	{
		static::deleted(function ($model)
		{
			// Clean up any associated directories
			foreach ($model->directories as $directory)
			{
				$directory->delete();
			}
		});
	}

	/**
	 * Defines a relationship to directories
	 *
	 * @return  object
	 */
	public function directories()
	{
		return $this->hasMany(Directory::class, 'storageresourceid');
	}

	/**
	 * Defines a relationship to a parent resource
	 *
	 * @return  object
	 */
	public function resource()
	{
		return $this->belongsTo('App\Modules\Resources\Models\Asset', 'parentresourceid')->withTrashed();
	}

	/**
	 * Defines a relationship to a message queue type for retrieving quota info
	 *
	 * @return  object
	 */
	public function quotaType()
	{
		return $this->belongsTo('App\Modules\Messages\Models\Type', 'getquotatypeid');
	}

	/**
	 * Defines a relationship to a message queue type for creating a directory
	 *
	 * @return  object
	 */
	public function createType()
	{
		return $this->belongsTo('App\Modules\Messages\Models\Type', 'createtypeid');
	}

	/**
	 * Find a record by name
	 *
	 * @param   string  $name
	 * @return  StorageResource|null
	 */
	public static function findByName($name)
	{
		return self::query()
			->where('name', '=', $name)
			->orWhere('name', 'like', $name . '%')
			->orWhere('name', 'like', '%' . $name)
			->orderBy('name', 'asc')
			->limit(1)
			->get()
			->first();
	}

	/**
	 * Set value in bytes
	 *
	 * @param   string|int  $value
	 * @return  void
	 */
	public function setDefaultquotaspaceAttribute($value)
	{
		$this->attributes['defaultquotaspace'] = Number::toBytes($value);
	}

	/**
	 * Get defaultquotaspace in human readable format
	 *
	 * @return  string
	 */
	public function getFormattedDefaultquotaspaceAttribute()
	{
		return Number::formatBytes($this->defaultquotaspace);
	}

	/**
	 * Set file quota
	 *
	 * @param   mixed  $value
	 * @return  void
	 */
	public function setDefaultquotafileAttribute($value)
	{
		// Convert 9,000 -> 9000
		$value = str_replace(',', '', $value);

		$this->attributes['defaultquotafile'] = (int)$value;
	}

	/**
	 * Can this be self-serve managed?
	 *
	 * @return  bool
	 */
	public function isGroupManaged()
	{
		return ($this->groupmanaged > 0);
	}
}
