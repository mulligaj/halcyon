<?php

namespace App\Modules\ContactReports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use App\Halcyon\Utility\PorterStemmer;
use App\Halcyon\Access\Map;
use App\Modules\Tags\Traits\Taggable;
use App\Modules\History\Traits\Historable;
use App\Modules\ContactReports\Events\ReportPrepareContent;
use App\Modules\ContactReports\Events\ReportCreated;
use App\Modules\ContactReports\Events\ReportUpdated;
use App\Modules\ContactReports\Events\ReportDeleted;
use App\Modules\Users\Models\User as SystemUser;
use Carbon\Carbon;

/**
 * Contact report
 *
 * @property int    $id
 * @property int    $groupid
 * @property int    $userid
 * @property string $report
 * @property string $stemmedreport
 * @property Carbon|null $datetimecontact
 * @property Carbon|null $datetimecreated
 * @property int    $notice
 * @property Carbon|null $datetimegroupid
 * @property int    $contactreporttypeid
 */
class Report extends Model
{
	use Historable, Taggable;

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
	 * The table to which the class pertains
	 *
	 * @var  string
	 **/
	protected $table = 'contactreports';

	/**
	 * Default order by for model
	 *
	 * @var string
	 */
	public static $orderBy = 'datetimecontact';

	/**
	 * Default order direction for select queries
	 *
	 * @var  string
	 */
	public static $orderDir = 'desc';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int,string>
	 */
	protected $guarded = [
		'id',
		'datetimecreated',
		'datetimegroupid'
	];

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var  array<string,string>
	 */
	protected $casts = [
		'datetimecontact' => 'datetime:Y-m-d H:i:s',
		'datetimegroupid' => 'datetime:Y-m-d H:i:s',
	];

	/**
	 * Fields and their validation criteria
	 *
	 * @var array<string,string>
	 */
	protected $rules = array(
		'headline' => 'required',
		'body'     => 'required'
	);

	/**
	 * The event map for the model.
	 *
	 * @var array<string,string>
	 */
	protected $dispatchesEvents = [
		'created' => ReportCreated::class,
		'updated' => ReportUpdated::class,
		'deleted' => ReportDeleted::class,
	];

	/**
	 * Code block replacements
	 *
	 * @var  array<string,array>
	 */
	private $replacements = array(
		'preblocks'  => array(),
		'codeblocks' => array()
	);

	/**
	 * @var string
	 */
	protected $markdown = null;

	/**
	 * @var string
	 */
	protected $html = null;

	/**
	 * Runs extra setup code when creating/updating a new model
	 *
	 * @return  void
	 */
	protected static function boot(): void
	{
		parent::boot();

		// Parse out hashtags and tag the record
		static::created(function ($model)
		{
			$tags = $model->hashtags;
		});

		static::updated(function ($model)
		{
			$tags = $model->hashtags;
		});
	}

	/**
	 * Defines a relationship to comments
	 *
	 * @return  HasMany
	 */
	public function comments(): HasMany
	{
		return $this->hasMany(Comment::class, 'contactreportid');
	}

	/**
	 * Defines a relationship to resources map
	 *
	 * @return  HasMany
	 */
	public function resources(): HasMany
	{
		return $this->hasMany(Reportresource::class, 'contactreportid');
	}

	/**
	 * Defines a relationship to group
	 *
	 * @return  BelongsTo
	 */
	public function group(): BelongsTo
	{
		return $this->belongsTo('App\Modules\Groups\Models\Group', 'groupid');
	}

	/**
	 * Defines a relationship to creator
	 *
	 * @return  BelongsTo
	 */
	public function creator(): BelongsTo
	{
		return $this->belongsTo('App\Modules\Users\Models\User', 'userid');
	}

	/**
	 * Defines a relationship to tagged users
	 *
	 * @return  HasMany
	 */
	public function users(): HasMany
	{
		return $this->hasMany(__NAMESPACE__ . '\\User', 'contactreportid');
	}

	/**
	 * Defines a relationship to type
	 *
	 * @return  BelongsTo
	 */
	public function type(): BelongsTo
	{
		return $this->belongsTo(Type::class, 'contactreporttypeid');
	}

	/**
	 * Generate a link to item
	 *
	 * @return  string
	 */
	public function link(): string
	{
		if (app('isAdmin'))
		{
			return route('admin.news.edit', ['id' => $this->id]);
		}

		return route('site.news.show', ['id' => $this->id]);
	}

	/**
	 * Get the list of users as a string
	 *
	 * @return  string
	 */
	public function usersAsString(): string
	{
		$tags = array();
		foreach ($this->users as $u)
		{
			$tags[] = $u->user ? $u->user->name : '#' . $u->userid;
		}
		$tags = array_unique($tags);

		return implode(', ', $tags);
	}

	/**
	 * Return content as MarkDown
	 *
	 * @return string
	 */
	public function toMarkdown(): string
	{
		if (is_null($this->markdown))
		{
			$this->hashTags;
			$text = $this->report;

			// Separate code blocks as we don't want to do any processing on their content
			$text = preg_replace_callback("/```(.*?)```/uis", [$this, 'stripPre'], $text);
			$text = preg_replace_callback("/`(.*?)`/i", [$this, 'stripCode'], $text);

			$uvars = array(
				'updatedatetime' => $this->datetimecreated,
				'updatedate'     => date('l, F jS, Y', strtotime($this->datetimecreated)),
				'updatetime'     => date("g:ia", strtotime($this->datetimecreated))
			);

			$news = array_merge($this->getContentVars(), $this->getAttributes()); //$this->toArray();
			$news['resources'] = $this->resources->toArray();

			$resources = array();
			foreach ($news['resources'] as $resource)
			{
				$resource['resourcename'] = $resource['resourceid'];
				array_push($resources, $resource['resourcename']);
			}

			if (count($resources) > 1)
			{
				$resources[count($resources)-1] = 'and ' . $resources[count($resources)-1];
			}

			if (count($resources) > 2)
			{
				$news['resources'] = implode(', ', $resources);
			}
			else if (count($resources) == 2)
			{
				$news['resources'] = $resources[0] . ' ' . $resources[1];
			}
			else if (count($resources) == 1)
			{
				$news['resources'] = $resources[0];
			}
			else
			{
				$news['resources'] = implode('', $resources);
			}

			foreach ($news as $var => $value)
			{
				if (is_array($value))
				{
					continue;
				}
				$text = preg_replace("/%" . $var . "%/", $value, $text);
			}

			$text = preg_replace_callback("/\{\{PRE\}\}/", [$this, 'replacePre'], $text);
			$text = preg_replace_callback("/\{\{CODE\}\}/", [$this, 'replaceCode'], $text);

			$this->markdown = $text;
		}

		return $this->markdown;
	}

	/**
	 * Return content as HTML
	 *
	 * @return string
	 */
	public function toHtml(): string
	{
		if (is_null($this->html))
		{
			$text = $this->toMarkdown();

			$converter = new CommonMarkConverter([
				'html_input' => 'allow',
			]);
			$converter->getEnvironment()->addExtension(new TableExtension());
			$converter->getEnvironment()->addExtension(new StrikethroughExtension());
			$converter->getEnvironment()->addExtension(new AutolinkExtension());

			$text = (string) $converter->convertToHtml($text);

			// Separate code blocks as we don't want to do any processing on their content
			$text = preg_replace_callback("/\<pre\>(.*?)\<\/pre\>/uis", [$this, 'stripPre'], $text);
			$text = preg_replace_callback("/\<code\>(.*?)\<\/code\>/i", [$this, 'stripCode'], $text);

			// Convert emails
			$text = preg_replace('/([\w\.\-]+@((\w+\.)*\w{2,}\.\w{2,}))/', "<a target=\"_blank\" href=\"mailto:$1\">$1</a>", $text);

			// Convert template variables
			if (auth()->user() && auth()->user()->can('manage contactreports'))
			{
				$text = preg_replace("/%%([\w\s]+)%%/", '<span style="color:red">$0</span>', $text);
			}

			// Highlight unused variables for admins
			if (auth()->user() && auth()->user()->can('manage contactreports'))
			{
				$text = preg_replace("/%([\w\s]+)%/", '<span style="color:red">$0</span>', $text);
			}

			if (count($this->tags))
			{
				preg_match_all('/(^|[^a-z0-9_])#([a-z0-9\-_\.]+)/i', $text, $matches);

				if (!empty($matches))
				{
					foreach ($matches[0] as $match)
					{
						$slug = preg_replace("/[^a-z0-9\-_]+/i", '', $match);
						if ($tag = $this->isTag($slug))
						{
							$text = str_replace($match, ' <a class="tag badge badge-sm badge-secondary" href="' . route((app('isAdmin') ? 'admin' : 'site') . '.contactreports.index', ['tag' => $tag->slug]) . '">' . $tag->name . '</a> ', $text);
						}
					}
				}
			}

			$text = str_replace('<th>', '<th scope="col">', $text);
			$text = str_replace('align="right"', 'class="text-right"', $text);

			// Put code blocks back
			$text = preg_replace_callback("/\{\{PRE\}\}/", [$this, 'replacePre'], $text);
			$text = preg_replace_callback("/\{\{CODE\}\}/", [$this, 'replaceCode'], $text);

			$text = preg_replace('/<p>([^\n]+)<\/p>\n(<table.*?>)(.*?<\/table>)/usm', '$2 <caption>$1</caption>$3', $text);
			$text = preg_replace('/src="\/include\/images\/(.*?)"/i', 'src="' . asset("files/$1") . '"', $text);

			event($event = new ReportPrepareContent($text));
			$text = $event->getBody();

			$this->html = $text;
		}

		return $this->html;
	}

	/**
	 * Return content as HTML
	 *
	 * @deprecated
	 * @return string
	 */
	public function getFormattedReportAttribute(): string
	{
		return $this->toHtml();

		$text = $this->report;

		$converter = new CommonMarkConverter([
			'html_input' => 'allow',
		]);
		$converter->getEnvironment()->addExtension(new TableExtension());
		$converter->getEnvironment()->addExtension(new StrikethroughExtension());

		$text = (string) $converter->convertToHtml($text);

		// separate code blocks
		$text = preg_replace_callback("/\<pre\>(.*?)\<\/pre\>/uis", [$this, 'stripPre'], $text);
		$text = preg_replace_callback("/\<code\>(.*?)\<\/code\>/i", [$this, 'stripCode'], $text);

		// convert emails
		$text = preg_replace('/([\w\.\-]+@((\w+\.)*\w{2,}\.\w{2,}))/', "<a target=\"_blank\" href=\"mailto:$1\">$1</a>", $text);

		// convert template variables
		if (auth()->user() && auth()->user()->can('manage contactreports'))
		{
			$text = preg_replace("/%%([\w\s]+)%%/", '<span style="color:red">$0</span>', $text);
		}

		$uvars = array(
			'updatedatetime' => $this->datetimecreated,
			'updatedate'     => date('l, F jS, Y', strtotime($this->datetimecreated)),
			'updatetime'     => date("g:ia", strtotime($this->datetimecreated))
		);

		$news = array_merge($this->getContentVars(), $this->getAttributes()); //$this->toArray();
		$news['resources'] = $this->resources->toArray();

		$resources = array();
		foreach ($news['resources'] as $resource)
		{
			$resource['resourcename'] = $resource['resourceid'];
			array_push($resources, $resource['resourcename']);
		}

		if (count($resources) > 1)
		{
			$resources[count($resources)-1] = 'and ' . $resources[count($resources)-1];
		}

		if (count($resources) > 2)
		{
			$news['resources'] = implode(', ', $resources);
		}
		else if (count($resources) == 2)
		{
			$news['resources'] = $resources[0] . ' ' . $resources[1];
		}
		else if (count($resources) == 1)
		{
			$news['resources'] = $resources[0];
		}
		else
		{
			$news['resources'] = implode('', $resources);
		}

		foreach ($news as $var => $value)
		{
			if (is_array($value))
			{
				continue;
			}
			$text = preg_replace("/%" . $var . "%/", $value, $text);
		}

		if (auth()->user() && auth()->user()->can('manage contactreports'))
		{
			$text = preg_replace("/%([\w\s]+)%/", '<span style="color:red">$0</span>', $text);
		}

		$text = preg_replace_callback("/\{\{PRE\}\}/", [$this, 'replacePre'], $text);
		$text = preg_replace_callback("/\{\{CODE\}\}/", [$this, 'replaceCode'], $text);
		$text = str_replace('<th>', '<th scope="col">', $text);

		//$text = preg_replace_callback("/\{\{CODE\}\}/", 'replaceCode', $text);

		//$text = '<p>' . $text . '</p>';
		$text = preg_replace("/<p>(.*)(<table.*?>)(.*?<\/table>)/m", "<p>$2 <caption>$1</caption>$3", $text);

		event($event = new ReportPrepareContent($text));
		$text = $event->getBody();

		$this->hashTags;
		if (count($this->tags))
		{
			preg_match_all('/(^|[^a-z0-9_])#([a-z0-9\-_]+)/i', $text, $matches);

			if (!empty($matches))
			{
				foreach ($matches[0] as $match)
				{
					$slug = preg_replace("/[^a-z0-9\-_]+/i", '', $match);
					if ($tag = $this->isTag($slug))
					{
						$text = str_replace($match, ' <a class="tag badge badge-sm badge-secondary" href="' . route((app('isAdmin') ? 'admin' : 'site') . '.contactreports.index', ['tag' => $tag->slug]) . '">' . $tag->name . '</a> ', $text);
					}
				}
			}
		}

		return $text;
	}

	/**
	 * Strip code blocks
	 *
	 * @param   array<int,string>  $match
	 * @return  string
	 */
	protected function stripCode($match): string
	{
		array_push($this->replacements['codeblocks'], $match[0]);

		return '{{CODE}}';
	}

	/**
	 * Strip pre blocks
	 *
	 * @param   array<int,string>  $match
	 * @return  string
	 */
	protected function stripPre($match): string
	{
		array_push($this->replacements['preblocks'], $match[0]);

		return '{{PRE}}';
	}

	/**
	 * Replace code block
	 *
	 * @param   array  $match
	 * @return  string
	 */
	protected function replaceCode($match): string
	{
		return array_shift($this->replacements['codeblocks']);
	}

	/**
	 * Replace pre block
	 *
	 * @param   array  $match
	 * @return  string
	 */
	protected function replacePre($match): string
	{
		return array_shift($this->replacements['preblocks']);
	}

	/**
	 * Delete the record and all associated data
	 *
	 * @return bool False if error, True on success
	 */
	public function delete(): bool
	{
		foreach ($this->comments as $comment)
		{
			if (!$comment->delete())
			{
				return false;
			}
		}

		foreach ($this->resources as $resource)
		{
			if (!$resource->delete())
			{
				return false;
			}
		}

		foreach ($this->users as $user)
		{
			if (!$user->delete())
			{
				return false;
			}
		}

		// Attempt to delete the record
		return parent::delete();
	}

	/**
	 * Format date
	 *
	 * @param   string  $startdate
	 * @return  string
	 */
	public function formatDate($startdate): string
	{
		$datestring = '';

		if (!$startdate)
		{
			return $datestring;
		}

		if ($startdate && !is_string($startdate))
		{
			$startdate = $startdate->toDateTimeString();
		}
		$starttime = explode(' ', $startdate);
		$starttime = $starttime[1];

		$datestring = date("F j, Y", strtotime($startdate));
		if ($starttime != '00:00:00')
		{
			$datestring .= ' ' . date("g:ia", strtotime($startdate));
		}

		return $datestring;
	}

	/**
	 * Get news vars
	 *
	 * @return  array<string,string>
	 */
	protected function getContentVars(): array
	{
		$vars = array(
			'date'           => "%date%",
			'datetime'       => "%datetime%",
			'time'           => "%time%",
			'updatedatetime' => "%updatedatetime%",
			'startdatetime'  => "%startdatetime%",
			'startdate'      => "%startdate%",
			'starttime'      => "%starttime%",
			'enddatetime'    => "%enddatetime%",
			'enddate'        => "%enddate%",
			'endtime'        => "%endtime%",
		);

		foreach ($vars as $var => $value)
		{
			if ($this->datetimecreated)
			{
				if ($var == 'datetime' || $var == 'date')
				{
					if ($this->datetimecreated)
					{
						$vars[$var] = preg_replace("/&nbsp;/", ' at ', $this->formatDate($this->datetimecreated->format('Y-m-d') . ' 00:00:00'));
					}
				}

				if ($var == 'time')
				{
					if ($this->datetimecreated)
					{
						$vars[$var] = $this->datetimecreated->format('g:ia');
					}
				}
			}
		}

		if (isset($this->location) && $this->location != '')
		{
			$vars['location'] = $this->location;
		}

		if (isset($this->resources))
		{
			$resources = array();
			foreach ($this->resources as $resource)
			{
				array_push($resources, $resource['resourcename']);
			}

			if (count($resources) > 1)
			{
				$resources[count($resources)-1] = 'and ' . $resources[count($resources)-1];
			}

			if (count($resources) > 2)
			{
				$vars['resources'] = implode(', ', $resources);
			}
			else if (count($resources) == 2)
			{
				$vars['resources'] = $resources[0] . ' ' . $resources[1];
			}
			else if (count($resources) == 1)
			{
				$vars['resources'] = $resources[0];
			}
		}

		return $vars;
	}

	/**
	 * Generate stemmed report
	 *
	 * @param   string  $value
	 * @return  void
	 */
	public function setReportAttribute($value): void
	{
		$this->attributes['report'] = $value;

		$report_words = preg_replace('/[^A-Za-z0-9]/', ' ', $value);
		$report_words = preg_replace('/ +/', ' ', $report_words);
		$report_words = preg_replace_callback(
			'/(^|[^\w^@^\/^\.])(((http)(s)?(:\/\/))?(([\w\-\.]+)\.(com|edu|org|mil|gov|net|info|[a-zA-Z]{2})(\/([\w\/\?=\-\&~\.\#\$\+~%;\\,]*[A-Za-z0-9\/])?)?))(\{.+?\})?(?=[^\w^}]|$)/',
			[$this, 'stripURL'],
			$report_words
		);

		// Calculate stem for each word
		$stems = array();
		foreach (explode(' ', $report_words) as $word)
		{
			$stem = PorterStemmer::stem($word);
			$stem = substr($stem, 0, 1) . $stem;

			array_push($stems, $stem);

			// If word ends in a number, also store it without the number
			if (preg_match('/[A-Za-z]+[0-9]+/', $word))
			{
				$word = preg_replace('/[^A-Za-z]/', '', $word);

				$stem = PorterStemmer::stem($word);
				$stem = substr($stem, 0, 1) . $stem;

				array_push($stems, $stem);
			}
		}

		$stemmedreport = '';
		foreach ($stems as $stem)
		{
			$stemmedreport .= $stem . ' ';
		}

		$this->attributes['stemmedreport'] = $stemmedreport;
	}

	/**
	 * Strip URL
	 *
	 * @param   array<int,string>  $match
	 * @return  string
	 */
	private function stripURL($match): string
	{
		if (isset($match[12]))
		{
			return $match[1] . ' ' . preg_replace("/\{|\}/", '', $match[12]);
		}

		return $match[1] . ' ' . $match[2];
	}

	/**
	 * Fetch list of people "subscribed" to a report's comments
	 * This includes anybody with a comment and the report author
	 *
	 * @return  array<int,int>
	 */
	public function commentSubscribers(): array
	{
		$subscribers = array($this->userid);

		foreach ($this->comments as $comment)
		{
			$subscribers[] = $comment->userid;
		}

		// Also select all users tagged in the report who have CRM privs.
		$role_id = config('module.contactreports.staff', 0);

		if ($role_id)
		{
			$a = (new SystemUser)->getTable();
			$b = (new Map)->getTable();

			$query = SystemUser::query()
				->select($a . '.*')
				->with('roles')
				->leftJoin($b, $b . '.user_id', $a . '.id')
				->where($b . '.role_id', '=', (int)$role_id);

			foreach ($query->get() as $user)
			{
				if ($user->can('manage contactreports'))
				{
					$subscribers[] = $user->id;
				}
			}
		}

		$subscribers = array_unique($subscribers);

		return $subscribers;
	}

	/**
	 * Fetch list of people "subscribed" to a report
	 * This includes report author, anyone tagged, and watchers
	 *
	 * @return  array<int,int>
	 */
	public function subscribers(): array
	{
		$subscribers = array($this->userid);

		foreach ($this->users as $user)
		{
			//$subscribers[] = $user->userid;

			foreach ($user->followers as $follower)
			{
				$subscribers[] = $follower->userid;
			}
		}

		// Also select all users tagged in the report who have CRM privs.
		$role_id = config('module.contactreports.staff', 0);

		if ($role_id)
		{
			$a = (new SystemUser)->getTable();
			$b = (new Map)->getTable();

			$query = SystemUser::query()
				->select($a . '.*')
				->with('roles')
				->leftJoin($b, $b . '.user_id', $a . '.id')
				->where($b . '.role_id', '=', (int)$role_id);

			foreach ($query->get() as $user)
			{
				if ($user->can('manage contactreports'))
				{
					$subscribers[] = $user->id;
				}
			}
		}

		if ($this->groupid)
		{
			$gusers = $this->group->members()->where('membertype', '=', 10)->get();

			foreach ($gusers as $guser)
			{
				$subscribers[] = $guser->userid;
			}
		}

		$subscribers = array_unique($subscribers);

		return $subscribers;
	}

	/*public function getKeywordsAttribute(): string
	{
		$str = $this->report;

		$min_word_length = 3;
		$avoid = [
			'we','the','to','i','am','is','are','he','she','a','an','and','here','there','can',
			'they', 'them',
			'could','were','has','have','had','been','welcome','of','home','&nbsp;','&ldquo;',
			'words','into','this','there'
		];
		$strip_arr = ["," ,"." ,";" ,":", "\"", "'", "“","”","(",")", "!","?"];
		$str_clean = str_replace($strip_arr, '', $str);
		$str_arr = explode(' ', $str_clean);
		$clean_arr = [];

		foreach($str_arr as $word)
		{
			if (strlen($word) > $min_word_length)
			{
				$word = strtolower($word);
				if (!in_array($word, $avoid))
				{
					$clean_arr[] = $word;
				}
			}
		}

		return implode(',', $clean_arr);
	}*/

	/**
	 * Taggable namespace
	 */
	static $entityNamespace = 'crm';

	/**
	 * Find all hashtags in the report
	 *
	 * @return  string
	 */
	public function getHashtagsAttribute(): string
	{
		$str = $this->report;

		$str = preg_replace_callback("/```(.*?)```/uis", [$this, 'stripPre'], $str);
		$str = preg_replace_callback("/`(.*?)`/i", [$this, 'stripCode'], $str);

		preg_match_all('/(^|[^a-z0-9_])#([a-z0-9\-_\.]+)/i', $str, $matches);

		$hashtag = [];
		if (!empty($matches[0]))
		{
			foreach ($matches[0] as $match)
			{
				// Trim trailing periods as this is most likely a hash
				// at the end of a sentence
				$match = trim($match, '.');

				$match = preg_replace("/[^a-z0-9\-_]+/i", '', $match);

				// Ignore purely numeric items as this is most likely
				// a reference to some ID. e.g., ticket #1234
				if (is_numeric($match))
				{
					continue;
				}

				$hashtag[] = $match;
			}

			$this->setTags($hashtag);
		}

		$str = preg_replace_callback("/\{\{PRE\}\}/", [$this, 'replacePre'], $str);
		$str = preg_replace_callback("/\{\{CODE\}\}/", [$this, 'replaceCode'], $str);

		return implode(', ', $hashtag);
	}

	/**
	 * Generate basic stats for a given number of days
	 *
	 * @param   string  $start
	 * @param   string  $stop
	 * @return  array<string,mixed>
	 */
	public static function stats($start, $stop): array
	{
		$start = Carbon::parse($start);
		$stop  = Carbon::parse($stop);
		$timeframe = round(($stop->timestamp - $start->timestamp) / (60 * 60 * 24));

		$now = Carbon::now();
		$placed = array();
		for ($d = $timeframe; $d >= 0; $d--)
		{
			$yesterday = Carbon::now()->modify('- ' . $d . ' days');
			$tomorrow  = Carbon::now()->modify(($d ? '- ' . ($d - 1) : '+ 1') . ' days');

			$placed[$yesterday->format('Y-m-d')] = self::query()
				->where('datetimecontact', '>=', $yesterday->format('Y-m-d') . ' 00:00:00')
				->where('datetimecontact', '<', $tomorrow->format('Y-m-d') . ' 00:00:00')
				->count();
		}

		$stats = array(
			'timeframe' => $timeframe,
			'daily'     => $placed,
		);

		return $stats;
	}
}
