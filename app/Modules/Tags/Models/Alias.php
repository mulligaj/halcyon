<?php

namespace App\Modules\Tags\Models;

use App\Halcyon\Traits\ErrorBag;
use App\Halcyon\Traits\Validatable;
use App\Modules\History\Traits\Historable;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Tag alias
 */
class Alias extends Model
{
	use ErrorBag, Validatable, Historable;

	/**
	 * The table to which the class pertains
	 *
	 * @var  string
	 */
	protected $table = 'tags_aliases';

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
	 * Fields and their validation criteria
	 *
	 * @var  array
	 */
	protected $rules = array(
		'name'   => 'required',
		'tag_id' => 'required',
	);

	/**
	 * Generates automatic tag field
	 *
	 * @param   array   $data  the data being saved
	 * @return  string
	 */
	public function automaticTag($data)
	{
		$tag = (isset($data['raw_tag']) && $data['raw_tag'] ? $data['raw_tag'] : $data['tag']);
		return Tag::blank()->normalize($tag);
	}

	/**
	 * Creator profile
	 *
	 * @return  object
	 */
	public function creator()
	{
		return $this->belongsTo('App\Modules\Users\Models\User', 'created_by');
	}

	/**
	 * Get parent tag
	 *
	 * @return  object
	 */
	public function tag()
	{
		return $this->belongsTo(Tag::class, 'tag_id');
	}

	/**
	 * Move all references to one tag to another tag
	 *
	 * @param   integer  $oldtagid  ID of tag to be moved
	 * @param   integer  $newtagid  ID of tag to move to
	 * @return  boolean  True if records changed
	 */
	public static function moveTo($oldtagid=null, $newtagid=null)
	{
		if (!$oldtagid || !$newtagid)
		{
			return false;
		}

		$items = self::query()
			->where('tag_id', '=', $oldtagid)
			->get();

		//$entries = array();

		foreach ($items as $item)
		{
			$item->tag_id = $newtagid;
			$item->save();

			//$entries[] = $item->toArray();
		}

		/*$data = new stdClass;
		$data->old_id  = $oldtagid;
		$data->new_id  = $newtagid;
		$data->entries = $entries;

		$log = Log::blank();
		$log->set([
			'tag_id'   => $newtagid,
			'action'   => 'substitutes_moved',
			'comments' => json_encode($data)
		]);
		$log->save();*/

		return self::cleanUp($newtagid);
	}

	/**
	 * Clean up duplicate references
	 *
	 * @param   integer  $tag_id  ID of tag to clean up
	 * @return  boolean  True on success, false if errors
	 */
	public static function cleanUp($tag_id)
	{
		$subs = self::query()
			->where('tag_id', '=', (int)$tag_id)
			->get();

		$tags = array();

		foreach ($subs as $sub)
		{
			if (!isset($tags[$sub->tag]))
			{
				// Item isn't in collection yet, so add it
				$tags[$sub->tag] = $sub->id;
			}
			else
			{
				// Item tag *is* in collection.
				if ($tags[$sub->tag] == $sub->id)
				{
					// Really this shouldn't happen
					continue;
				}
				else
				{
					// Duplcate tag with a different ID!
					// We don't need duplicates.
					$sub->delete();
				}
			}
		}

		return true;
	}

	/**
	 * Copy all substitutions for one tag to another
	 *
	 * @param   integer  $oldtagid  ID of tag to be copied
	 * @param   integer  $newtagid  ID of tag to copy to
	 * @return  boolean  True if records copied
	 */
	public static function copyTo($oldtagid=null, $newtagid=null)
	{
		if (!$oldtagid || !$newtagid)
		{
			return false;
		}

		$rows = self::query()
			->where('tag_id', '=', $oldtagid)
			->get();

		if ($rows)
		{
			//$entries = array();

			foreach ($rows as $row)
			{
				$row->id = null;
				$row->tag_id = $newtagid;
				$row->save();

				//$entries[] = $row->id;
			}

			/*$data = new stdClass;
			$data->old_id  = $oldtagid;
			$data->new_id  = $newtagid;
			$data->entries = $entries;

			$log = Log::blank();
			$log->set([
				'tag_id'   => $newtagid,
				'action'   => 'substitutions_copied',
				'comments' => json_encode($data)
			]);
			$log->save();*/
		}

		return true;
	}
}
