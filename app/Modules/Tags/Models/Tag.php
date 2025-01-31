<?php

namespace App\Modules\Tags\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\History\Traits\Historable;
use App\Modules\Tags\Events\TagCreated;
use App\Modules\Tags\Events\TagUpdated;
use App\Modules\Tags\Events\TagDeleted;
use Carbon\Carbon;

/**
 * Tag model
 *
 * @property int    $id
 * @property int    $parent_id
 * @property string $slug
 * @property string $name
 * @property string $domain
 * @property int    $created_by
 * @property Carbon|null $created_at
 * @property int    $updated_by
 * @property Carbon|null $updated_at
 * @property int    $deleted_by
 * @property Carbon|null $deleted_at
 * @property int    $tagged_count
 * @property int    $alias_count
 */
class Tag extends Model
{
	use Historable, SoftDeletes;

	/**
	 * The table to which the class pertains
	 *
	 * @var string
	 **/
	protected $table = 'tags';

	/**
	 * Default order by for model
	 *
	 * @var string
	 */
	public static $orderBy = 'name';

	/**
	 * Default order direction for select queries
	 *
	 * @var string
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
	 * The event map for the model.
	 *
	 * @var array<string,string>
	 */
	protected $dispatchesEvents = [
		'created' => TagCreated::class,
		'updated' => TagUpdated::class,
		'deleted' => TagDeleted::class,
	];

	/**
	 * Runs extra setup code when creating/deleting a new model
	 *
	 * @return  void
	 */
	protected static function booted(): void
	{
		static::created(function ($model)
		{
			if ($model->parent_id)
			{
				$total = self::query()
					->where('parent_id', '=', $model->parent_id)
					->count();

				$model->parent->update(['alias_count' => $total]);
			}
		});

		static::deleting(function ($model)
		{
			if (!$model->deleted_by && auth()->user())
			{
				$model->deleted_by = auth()->user()->id;
			}
		});

		static::deleted(function ($model)
		{
			foreach ($model->aliases as $row)
			{
				$row->delete();
			}

			foreach ($model->tagged as $row)
			{
				$row->delete();
			}

			if ($model->parent_id)
			{
				$total = self::query()
					->where('parent_id', '=', $model->parent_id)
					->count();

				$model->parent->update(['alias_count' => $total]);
			}
		});
	}

	/**
	 * Set tag name and slug
	 *
	 * @param   string  $value
	 * @return  void
	 */
	public function setNameAttribute($value): void
	{
		$this->attributes['name'] = $value;
		$this->attributes['slug'] = $this->normalize($value);
	}

	/**
	 * Normalize tag input
	 *
	 * @param   string  $tag
	 * @return  string
	 */
	public function normalize($name): string
	{
		$transliterationTable = array(
			'á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE',
			'ḃ' => 'b', 'Ḃ' => 'B',
			'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C',
			'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh',
			'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E',
			'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F',
			'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G',
			'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H',
			'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I',
			'ĵ' => 'j', 'Ĵ' => 'J',
			'ķ' => 'k', 'Ķ' => 'K',
			'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L',
			'ṁ' => 'm', 'Ṁ' => 'M',
			'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N',
			'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE',
			'ṗ' => 'p', 'Ṗ' => 'P',
			'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R',
			'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS',
			'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T',
			'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE',
			'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W',
			'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y',
			'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z',
			'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u',
			'а' => 'a', 'А' => 'a', 'б' => 'b',
			'Б' => 'b', 'в' => 'v', 'В' => 'v',
			'г' => 'g', 'Г' => 'g', 'д' => 'd',
			'Д' => 'd', 'е' => 'e', 'Е' => 'e',
			'ё' => 'e', 'Ё' => 'e', 'ж' => 'zh',
			'Ж' => 'zh', 'з' => 'z', 'З' => 'z',
			'и' => 'i', 'И' => 'i', 'й' => 'j',
			'Й' => 'j', 'к' => 'k', 'К' => 'k',
			'л' => 'l', 'Л' => 'l', 'м' => 'm',
			'М' => 'm', 'н' => 'n', 'Н' => 'n',
			'о' => 'o', 'О' => 'o', 'п' => 'p',
			'П' => 'p', 'р' => 'r', 'Р' => 'r',
			'с' => 's', 'С' => 's', 'т' => 't',
			'Т' => 't', 'у' => 'u', 'У' => 'u',
			'ф' => 'f', 'Ф' => 'f', 'х' => 'h',
			'Х' => 'h', 'ц' => 'c', 'Ц' => 'c',
			'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh',
			'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch',
			'ъ' => '', 'Ъ' => '', 'ы' => 'y',
			'Ы' => 'y', 'ь' => '', 'Ь' => '',
			'э' => 'e', 'Э' => 'e', 'ю' => 'ju',
			'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja'
		);

		$name = str_replace(array_keys($transliterationTable), array_values($transliterationTable), $name);

		$separator = '-';
		// Convert all dashes/underscores into separator
		$flip = '_';

		$name = preg_replace('!['.preg_quote($flip).']+!u', $separator, $name);

		// Replace @ with the word 'at'
		$name = str_replace('@', $separator.'at'.$separator, $name);

		// Remove all characters that are not the separator, letters, numbers, or whitespace.
		$name = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($name));

		// Replace all separator characters and whitespace by a single separator
		$name = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $name);

		return trim($name, $separator);
		//return strtolower(preg_replace("/[^a-zA-Z0-9_]/", '', $tag));
	}

	/**
	 * Define relationship to creator user
	 *
	 * @return  BelongsTo
	 */
	public function creator(): BelongsTo
	{
		return $this->belongsTo('App\Modules\Users\Models\User', 'created_by');
	}

	/**
	 * Determine if record was modified
	 *
	 * @return  bool  True if modified, false if not
	 */
	public function isUpdated(): bool
	{
		if ($this->updated_at
		 && $this->updated_at != $this->created_at)
		{
			return true;
		}
		return false;
	}

	/**
	 * Editor user record
	 *
	 * @return  BelongsTo
	 */
	public function updater(): BelongsTo
	{
		return $this->belongsTo('App\Modules\Users\Models\User', 'updated_by');
	}

	/**
	 * Deleter user record
	 *
	 * @return  BelongsTo
	 */
	public function trasher(): BelongsTo
	{
		return $this->belongsTo('App\Modules\Users\Models\User', 'deleted_by');
	}

	/**
	 * Parent tag
	 *
	 * @return  BelongsTo
	 */
	public function parent(): BelongsTo
	{
		return $this->belongsTo(self::class, 'parent_id');
	}

	/**
	 * Get a list of aliases
	 *
	 * @return  HasMany
	 */
	public function aliases(): HasMany
	{
		return $this->hasMany(self::class, 'parent_id');
	}

	/**
	 * Get a comma-separated list of aliases
	 *
	 * @return  string
	 */
	public function getAliasStringAttribute(): string
	{
		$subs = $this->aliases->pluck('name')->toArray();

		return implode(', ', $subs);
	}

	/**
	 * Get a list of tagged objects
	 *
	 * @return  HasMany
	 */
	public function tagged(): HasMany
	{
		return $this->hasMany(Tagged::class, 'tag_id');
	}

	/**
	 * Retrieves one row loaded by a tag field
	 *
	 * @param   string  $tag  The tag to load by
	 * @return  Tag|null
	 **/
	public static function findByTag($tag)
	{
		$instance = new self;

		return self::query()
			->where('slug', '=', $instance->normalize($tag))
			->limit(1)
			->first();
	}

	/**
	 * Remove this tag from an object
	 *
	 * If $taggerid is provided, it will only remove the tags added to an object by
	 * that specific user
	 *
	 * @param   string   $scope     Object type (ex: resource, ticket)
	 * @param   int  $scope_id  Object ID (e.g., resource ID, ticket ID)
	 * @param   int  $tagger    User ID of person to filter tag by
	 * @return  bool
	 */
	public function removeFrom($scope, $scope_id, $tagger=0): bool
	{
		// Check if the relationship exists
		$to = Tagged::findByScoped($scope, $scope_id, $this->id, $tagger);

		if (!$to->id)
		{
			return true;
		}

		// Attempt to delete the record
		if (!$to->delete())
		{
			return false;
		}

		$this->tagged_count = $this->tagged()->count();

		return $this->save();
	}

	/**
	 * Add this tag to an object
	 *
	 * @param   string   $scope     Object type (ex: resource, ticket)
	 * @param   int  $scope_id  Object ID (e.g., resource ID, ticket ID)
	 * @param   int  $tagger    User ID of person adding tag
	 * @param   int  $strength  Tag strength
	 * @return  bool
	 */
	public function addTo($scope, $scope_id, $tagger = 0, $strength = 1): bool
	{
		// Check if the relationship already exists
		$to = Tagged::findByScoped($scope, $scope_id, $this->id, $tagger);

		if ($to->id)
		{
			return true;
		}

		// Set some data
		$to->taggable_type = (string) $scope;
		$to->taggable_id   = (int) $scope_id;
		$to->tag_id        = (int) $this->id;
		$to->strength      = (int) $strength;
		$to->created_by    = $tagger ? $tagger : auth()->user()->id;

		// Attempt to store the new record
		if (!$to->save())
		{
			return false;
		}

		$this->tagged_count = $this->tagged()->count();

		return $this->save();
	}

	/**
	 * Move all data from this tag to another, including the tag itself
	 *
	 * @param   int  $tag_id  ID of tag to merge with
	 * @return  bool
	 */
	public function mergeWith($tag_id): bool
	{
		if (!$tag_id)
		{
			return false;
		}

		// Get all the associations to this tag
		// Loop through the associations and link them to a different tag
		if (!Tagged::moveTo($this->id, $tag_id))
		{
			return false;
		}

		// Get all the substitutions to this tag
		// Loop through the records and link them to a different tag
		if (!self::moveTo($this->id, $tag_id))
		{
			return false;
		}

		// Make the current tag an alias for the new tag
		$sub = new self;
		$sub->update([
			'name'      => $this->name,
			'parent_id' => $tag_id
		]);

		// Update new tag's counts
		$tag = self::find($tag_id);
		$tag->update([
			'tagged_count' => $tag->tagged()->count(),
			'alias_count'  => $tag->aliases()->count()
		]);

		// Destroy the old tag
		if (!$this->delete())
		{
			return false;
		}

		return true;
	}

	/**
	 * Copy associations from this tag to another
	 *
	 * @param   int  $tag_id  ID of tag to copy associations to
	 * @return  bool
	 */
	public function copyTo($tag_id): bool
	{
		if (!$tag_id)
		{
			return false;
		}

		// Get all the associations to this tag
		// Loop through the associations and link them to a different tag
		if (!Tagged::copyTo($this->id, $tag_id))
		{
			return false;
		}

		// Update new tag's counts
		$tag = self::find($tag_id);
		$tag->update([
			'tagged_count' => $tag->tagged()->count()
		]);

		return true;
	}

	/**
	 * Save tag substitutions
	 *
	 * @param   string   $tag_string
	 * @return  bool
	 */
	public function saveAliases($tag_string=''): bool
	{
		// Get the old list of substitutions
		$subs = array();
		foreach ($this->aliases as $sub)
		{
			$subs[$sub->slug] = $sub;
		}

		// Add the specified tags as aliases if not
		// already a substitute
		$names = trim($tag_string);
		$names = preg_split("/(,|;)/", $names);

		$tags = array();
		foreach ($names as $name)
		{
			$nrm = $this->normalize($name);

			$tags[] = $nrm;

			if (isset($subs[$nrm]))
			{
				continue; // Substitution already exists
			}

			$sub = new self;
			$sub->name      = trim($name);
			$sub->parent_id = $this->id;
			$sub->save();
		}

		// Run through the old list of aliases, finding any
		// not in the new list and delete them
		foreach ($subs as $key => $sub)
		{
			if (!in_array($key, $tags))
			{
				$sub->delete();
			}
		}

		// Get all possibly existing tags that are now aliases
		$ids = self::query()
			->whereIn('slug', $tags)
			->get();

		// Move associations on tag and delete tag
		foreach ($ids as $tag)
		{
			if ($tag->id != $this->id)
			{
				// Get all the associations to this tag
				// Loop through the associations and link them to a different tag
				Tagged::moveTo($tag->id, $this->id);

				// Get all the aliases to this tag
				// Loop through the records and link them to a different tag
				self::moveTo($tag->id, $this->id);

				// Delete the tag
				$tag->delete();
			}
		}

		$this->tagged_count = $this->tagged()->count();
		$this->alias_count  = $this->aliases()->count();

		return $this->save();
	}
}
