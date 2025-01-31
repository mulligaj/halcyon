<?php
namespace App\Modules\Groups\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\History\Traits\Historable;
use App\Modules\Groups\Events\MemberCreating;
use App\Modules\Groups\Events\MemberCreated;
use App\Modules\Groups\Events\MemberUpdating;
use App\Modules\Groups\Events\MemberUpdated;
use App\Modules\Groups\Events\MemberDeleted;

/**
 * Group member model
 *
 * @property int    $id
 * @property int    $groupid
 * @property int    $userid
 * @property int    $userrequestid
 * @property int    $membertype
 * @property int    $owner
 * @property Carbon|null $datetimecreated
 * @property Carbon|null $datetimeremoved
 * @property Carbon|null $datelastseen
 * @property int    $notice
 */
class Member extends Model
{
	use Historable, SoftDeletes;

	/**
	 * The name of the "created at" column.
	 *
	 * @var string|null
	 */
	const CREATED_AT = 'datecreated';

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
	const DELETED_AT = 'dateremoved';

	/**
	 * The table to which the class pertains
	 *
	 * @var  string
	 **/
	protected $table = 'groupusers';

	/**
	 * Default order by for model
	 *
	 * @var string
	 */
	public static $orderBy = 'id';

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
		'groupid' => 'required|integer',
		'userid' => 'required|integer|min:1'
	);

	/**
	 * The event map for the model.
	 *
	 * @var array<string,string>
	 */
	protected $dispatchesEvents = [
		'creating' => MemberCreating::class,
		'created'  => MemberCreated::class,
		'updating' => MemberUpdating::class,
		'updated'  => MemberUpdated::class,
		'deleted'  => MemberDeleted::class,
	];

	/**
	 * Determine if datelastseen time is set
	 *
	 * @return  bool
	 */
	public function hasVisited()
	{
		return !is_null($this->datelastseen);
	}

	/**
	 * Get parent group
	 *
	 * @return  BelongsTo
	 */
	public function group(): BelongsTo
	{
		return $this->belongsTo(Group::class, 'groupid');
	}

	/**
	 * Get parent user
	 *
	 * @return  BelongsTo
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo('App\Modules\Users\Models\User', 'userid');
			/*->withDefault([
				'id' => 0,
				'name' => trans('global.unknown')
			]);*/
	}

	/**
	 * Get user request
	 *
	 * @return  BelongsTo
	 */
	public function request(): BelongsTo
	{
		return $this->belongsTo('App\Modules\Groups\Models\UserRequest', 'userrequestid');
	}

	/**
	 * Get member type
	 *
	 * @return  BelongsTo
	 */
	public function type(): BelongsTo
	{
		return $this->belongsTo(Type::class, 'membertype');
	}

	/**
	 * Is regular member?
	 *
	 * @return  bool
	 */
	public function isMember(): bool
	{
		return ($this->membertype == Type::MEMBER);
	}

	/**
	 * Is manager?
	 *
	 * @return  bool
	 */
	public function isManager(): bool
	{
		return ($this->membertype == Type::MANAGER);
	}

	/**
	 * Is viewer?
	 *
	 * @return  bool
	 */
	public function isViewer(): bool
	{
		return ($this->membertype == Type::VIEWER);
	}

	/**
	 * Is memebership pending?
	 *
	 * @return  bool
	 */
	public function isPending(): bool
	{
		return ($this->membertype == Type::PENDING);
	}

	/**
	 * Set as a member
	 *
	 * @return  void
	 */
	public function setAsMember(): void
	{
		$this->membertype = Type::MEMBER;
	}

	/**
	 * Set as a manager
	 *
	 * @return  void
	 */
	public function setAsManager(): void
	{
		$this->membertype = Type::MANAGER;
	}

	/**
	 * Set as a viewer
	 *
	 * @return  void
	 */
	public function setAsViewer(): void
	{
		$this->membertype = Type::VIEWER;
	}

	/**
	 * Set as a pending member
	 *
	 * @return  void
	 */
	public function setAsPending(): void
	{
		$this->membertype = Type::PENDING;
	}

	/**
	 * Query scope where is member
	 *
	 * @param   object  $query
	 * @return  object
	 */
	public function scopeWhereIsMember($query)
	{
		return $query->where('membertype', '=', Type::MEMBER);
	}

	/**
	 * Query scope where membership is manager
	 *
	 * @param   object  $query
	 * @return  object
	 */
	public function scopeWhereIsManager($query)
	{
		return $query->where('membertype', '=', Type::MANAGER);
	}

	/**
	 * Query scope where membership is viewer
	 *
	 * @param   object  $query
	 * @return  object
	 */
	public function scopeWhereIsViewer($query)
	{
		return $query->where('membertype', '=', Type::VIEWER);
	}

	/**
	 * Query scope where membership is pending
	 *
	 * @param   object  $query
	 * @return  object
	 */
	public function scopeWhereIsPending($query)
	{
		return $query->where('membertype', '=', Type::PENDING);
	}

	/**
	 * Get a record by group/user
	 *
	 * @param   int  $groupid
	 * @param   int  $userid
	 * @return  object
	 */
	public static function findByGroupAndUser(int $groupid, int $userid)
	{
		return self::query()
			->where('groupid', '=', $groupid)
			->where('userid', '=', $userid)
			->first();
	}
}
