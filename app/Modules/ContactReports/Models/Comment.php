<?php

namespace App\Modules\ContactReports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use App\Halcyon\Utility\PorterStemmer;
use App\Modules\History\Traits\Historable;
use App\Modules\ContactReports\Events\CommentCreated;
use App\Modules\ContactReports\Events\CommentUpdated;
use App\Modules\ContactReports\Events\CommentDeleted;
use Carbon\Carbon;

/**
 * Model for a contact report comment
 *
 * @property int    $id
 * @property int    $contactreportid
 * @property int    $userid
 * @property string $comment
 * @property string $stemmedcomment
 * @property Carbon|null $datetimecreated
 * @property int    $notice
 */
class Comment extends Model
{
	use Historable;

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
	 * The table to which the class pertains
	 *
	 * @var  string
	 */
	protected $table = 'contactreportcomments';

	/**
	 * Default order by for model
	 *
	 * @var string
	 */
	public static $orderBy = 'datetimecreated';

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
		'id'
	];

	/**
	 * Fields and their validation criteria
	 *
	 * @var array<string,string>
	 */
	protected $rules = array(
		'body' => 'required|string'
	);

	/**
	 * The event map for the model.
	 *
	 * @var array<string,string>
	 */
	protected $dispatchesEvents = [
		'created' => CommentCreated::class,
		'updated' => CommentUpdated::class,
		'deleted' => CommentDeleted::class,
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
	 * Defines a relationship to creator
	 *
	 * @return  object
	 */
	public function creator(): BelongsTo
	{
		return $this->belongsTo('App\Modules\Users\Models\User', 'userid');
	}

	/**
	 * Defines a relationship to contact report
	 *
	 * @return  object
	 */
	public function report(): BelongsTo
	{
		return $this->belongsTo(Report::class, 'contactreportid');
	}

	/**
	 * Get formatted created time
	 *
	 * @return  string
	 */
	public function getFormattedDateAttribute(): string
	{
		$startdate = $this->datetimecreated->toDateTimeString();

		$starttime = explode(' ', $startdate);
		$starttime = $starttime[1];

		$datestring = $this->datetimecreated->format('F j, Y');
		if ($starttime != '00:00:00')
		{
			$datestring .= ' ' . $this->datetimecreated->format('g:ia');
		}

		return $datestring;
	}

	/**
	 * Get the comment formatted as HTML
	 *
	 * @return string
	 */
	public function getFormattedCommentAttribute(): string
	{
		$text = $this->comment;

		$converter = new CommonMarkConverter([
			'html_input' => 'allow',
		]);
		$converter->getEnvironment()->addExtension(new TableExtension());
		$converter->getEnvironment()->addExtension(new StrikethroughExtension());
		$converter->getEnvironment()->addExtension(new AutolinkExtension());

		$text = (string) $converter->convertToHtml($text);

		// separate code blocks
		$text = preg_replace_callback("/\<pre\>(.*?)\<\/pre\>/uis", [$this, 'stripPre'], $text);
		$text = preg_replace_callback("/\<code\>(.*?)\<\/code\>/i", [$this, 'stripCode'], $text);

		// convert template variables
		if (auth()->user() && auth()->user()->can('manage contactreports'))
		{
			$text = preg_replace("/%%([\w\s]+)%%/", '<span style="color:red">$0</span>', $text);
		}

		$uvars = array(
			'updatedatetime' => $this->datetimecreated->format('Y-m-d h:i:s'), //$this->getOriginal('datetimecreated'),
			'updatedate'     => $this->datetimecreated->format('l, F jS, Y'),// date('l, F jS, Y', strtotime($this->getOriginal('datetimecreated'))),
			'updatetime'     => $this->datetimecreated->format('g:ia') //date("g:ia", strtotime($this->getOriginal('datetimecreated')))
		);

		$news = $this->report->getAttributes(); //$this->article->toArray();
		$news['resources'] = $this->report->resources->toArray();
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

		$vars = array_merge($news, $uvars);

		foreach ($vars as $var => $value)
		{
			if (is_array($value))
			{
				$value = implode(', ', $value);
			}
			$text = preg_replace("/%" . $var . "%/", $value, $text);
		}

		if (auth()->user() && auth()->user()->can('manage contactreports'))
		{
			$text = preg_replace("/%([\w\s]+)%/", '<span style="color:red">$0</span>', $text);
		}

		$text = preg_replace_callback("/\{\{PRE\}\}/", [$this, 'replacePre'], $text);
		$text = preg_replace_callback("/\{\{CODE\}\}/", [$this, 'replaceCode'], $text);

		$text = preg_replace("/<p>(.*)(<table.*?>)(.*<\/table>)/m", "<p>$2 <caption>$1</caption>$3", $text);

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
	 * Generate stemmed comment
	 *
	 * @param   string  $value
	 * @return  void
	 */
	public function setCommentAttribute($value): void
	{
		$this->attributes['comment'] = $value;

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

		$this->attributes['stemmedcomment'] = $stemmedreport;
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
}
