<?php

namespace App\Modules\Widgets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Halcyon\Traits\Checkable;
use App\Halcyon\Form\Form;
use App\Halcyon\Models\Casts\Params;
use App\Modules\History\Traits\Historable;
use App\Modules\Widgets\Events\WidgetCreating;
use App\Modules\Widgets\Events\WidgetCreated;
use App\Modules\Widgets\Events\WidgetUpdating;
use App\Modules\Widgets\Events\WidgetUpdated;
use App\Modules\Widgets\Events\WidgetDeleted;
use Carbon\Carbon;

/**
 * Widget model
 *
 * @property int    $id
 * @property string $title
 * @property string $note
 * @property string $content
 * @property int    $ordering
 * @property string $position
 * @property int    $checked_out
 * @property Carbon|null $checked_out_time
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Widget extends Model
{
	use Checkable, Historable;

	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * The table to which the class pertains
	 *
	 * @var  string
	 **/
	protected $table = 'widgets';

	/**
	 * Default order by for model
	 *
	 * @var  string
	 */
	public static $orderBy = 'position';

	/**
	 * Default order direction for select queries
	 *
	 * @var  string
	 */
	public static $orderDir = 'asc';

	/**
	 * Fields and their validation criteria
	 *
	 * @var  array<string,string>
	 */
	protected $rules = array(
		'title'    => 'required|string',
		'position' => 'required|string'
	);

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int,string>
	 */
	protected $guarded = [
		'id',
		'params',
	];

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array<string,string>
	 */
	protected $casts = [
		'published' => 'integer',
		'access' => 'integer',
		'params' => Params::class,
		'publish_up' => 'datetime:Y-m-d H:i:s',
		'publish_down' => 'datetime:Y-m-d H:i:s',
	];

	/**
	 * The event map for the model.
	 *
	 * @var array<string,string>
	 */
	protected $dispatchesEvents = [
		'creating' => WidgetCreating::class,
		'created'  => WidgetCreated::class,
		'updating' => WidgetUpdating::class,
		'updated'  => WidgetUpdated::class,
		'deleted'  => WidgetDeleted::class,
	];

	/**
	 * The path to the installed files
	 *
	 * @var  string
	 */
	protected $path = null;

	/**
	 * The "booted" method of the model.
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected static function booted(): void
	{
		static::creating(function ($model)
		{
			$result = self::query()
				->select(DB::raw('MAX(ordering) + 1 AS seq'))
				->where('position', '=', $model->position)
				->get()
				->first()
				->seq;

			$model->setAttribute('ordering', (int)$result);
		});

		static::deleting(function ($model)
		{
			// Delete old widget to menu item associations
			if (!Menu::deleteByWidget($model->id))
			{
				throw new \Exception('Failed to remove previous menu assignments.');
			}
		});
	}

	/**
	 * Determine if record is enabled
	 * 
	 * @return  bool
	 */
	public function isEnabled(): bool
	{
		return ($this->published == 1);
	}

	/**
	 * Determine if record is disabled
	 * 
	 * @return  bool
	 */
	public function isDisabled(): bool
	{
		return !$this->isEnabled();
	}

	/**
	 * Determine if record is published
	 * 
	 * @return  bool
	 */
	public function isPublished(): bool
	{
		if ($this->published != 1)
		{
			return false;
		}

		if ($this->publish_up
		 && $this->publish_up > Carbon::now()->toDateTimeString())
		{
			return false;
		}

		if ($this->publish_down
		 && $this->publish_down <= Carbon::now()->toDateTimeString())
		{
			return false;
		}

		return true;
	}

	/**
	 * Method to change the title.
	 *
	 * @param   string  $title     The title.
	 * @param   string  $position  The position.
	 * @return  string
	 */
	public function generateNewTitle($title, $position): string
	{
		// Alter the title & alias
		$models = self::query()
			->where('position', '=', $position)
			->where('title', '=', $title)
			->count();

		for ($i = 0; $i < $models; $i++)
		{
			$title = self::incrementString($title);
		}

		return $title;
	}

	/**
	 * Increments a trailing number in a string.
	 *
	 * Used to easily create distinct labels when copying objects. The method has the following styles:
	 *
	 * default: "Label" becomes "Label (2)"
	 * dash:    "Label" becomes "Label-2"
	 *
	 * @param   string   $string  The source string.
	 * @param   string   $style   The the style (default|dash).
	 * @param   int  $n       If supplied, this number is used for the copy, otherwise it is the 'next' number.
	 * @return  string   The incremented string.
	 */
	protected static function incrementString($string, $style = 'default', $n = 0): string
	{
		$incrementStyles = array(
			'dash' => array(
				'#-(\d+)$#',
				'-%d'
			),
			'default' => array(
				array('#\((\d+)\)$#', '#\(\d+\)$#'),
				array(' (%d)', '(%d)'),
			),
		);

		$styleSpec = isset($incrementStyles[$style]) ? $incrementStyles[$style] : $incrementStyles['default'];

		// Regular expression search and replace patterns.
		if (is_array($styleSpec[0]))
		{
			$rxSearch  = $styleSpec[0][0];
			$rxReplace = $styleSpec[0][1];
		}
		else
		{
			$rxSearch = $rxReplace = $styleSpec[0];
		}

		// New and old (existing) sprintf formats.
		if (is_array($styleSpec[1]))
		{
			$newFormat = $styleSpec[1][0];
			$oldFormat = $styleSpec[1][1];
		}
		else
		{
			$newFormat = $oldFormat = $styleSpec[1];
		}

		// Check if we are incrementing an existing pattern, or appending a new one.
		if (preg_match($rxSearch, $string, $matches))
		{
			$n = empty($n) ? ($matches[1] + 1) : $n;
			$string = preg_replace($rxReplace, sprintf($oldFormat, $n), $string);
		}
		else
		{
			$n = empty($n) ? 2 : $n;
			$string .= sprintf($newFormat, $n);
		}

		return $string;
	}

	/**
	 * Get the installed path
	 *
	 * @return  string
	 */
	public function path(): string
	{
		if (is_null($this->path))
		{
			$this->path = '';

			if ($widget = $this->widget)
			{
				$widget = ucfirst($widget);

				$path = app_path() . '/Widgets/' . $widget . '/' . $widget . '.php';

				if (file_exists($path))
				{
					$this->path = dirname($path);
				}
			}
		}

		return $this->path;
	}

	/**
	 * Get a form
	 *
	 * @return  Form
	 * @throws  \Exception
	 */
	public function getForm(): Form
	{
		$file = __DIR__ . '/Forms/Widget.xml';

		Form::addFieldPath(__DIR__ . '/Fields');

		$form = new Form('widget', array('control' => 'fields'));

		if (!$form->loadFile($file, false, '//form'))
		{
			throw new \Exception(trans('global.load file failed'));
		}

		$paths = array();
		$paths[] = $this->path() . '/Config/Params.xml';

		foreach ($paths as $file)
		{
			if (file_exists($file))
			{
				// Get the plugin form.
				if (!$form->loadFile($file, false, '//config'))
				{
					throw new \Exception(trans('global.load file failed'));
				}
				break;
			}
		}

		$data = $this->toArray();
		$data['publish_up'] = $this->publish_up ? $this->publish_up->toDateTimeString() : '';
		$data['publish_down'] = $this->publish_down ? $this->publish_down->toDateTimeString() : '';
		$data['params'] = $this->params->all();

		$form->bind($data);

		return $form;
	}

	/**
	 * Register the widget's language directory
	 *
	 * @return  void
	 */
	public function registerLanguage(): void
	{
		$name = strtolower($this->widget);

		app('translator')->addNamespace('widget.' . $name, $this->path() . '/lang');
	}

	/**
	 * Get menu assignments
	 *
	 * @return  array<int,int>
	 */
	public function menuAssigned()
	{
		return Menu::query()
			->where('widgetid', '=', (int)$this->id)
			->get()
			->pluck('menuid')
			->toArray();
	}

	/**
	 * Determine the assignment
	 *
	 * @return  string|int
	 */
	public function menuAssignment()
	{
		// Determine the page assignment mode.
		$assigned = $this->menuAssigned();

		if (!$this->id)
		{
			// If this is a new widget, assign to all pages.
			$assignment = 0;
		}
		elseif (empty($assigned))
		{
			// For an existing widget it is assigned to none.
			$assignment = '-';
		}
		else
		{
			if ($assigned[0] > 0)
			{
				$assignment = +1;
			}
			elseif ($assigned[0] < 0)
			{
				$assignment = -1;
			}
			else
			{
				$assignment = 0;
			}
		}

		return $assignment;
	}

	/**
	 * Save menu assignments for a widget
	 *
	 * @param   int  $assignment
	 * @param   array    $assigned
	 * @return  bool
	 * @throws  \Exception
	 */
	public function saveAssignment($assignment, $assigned): bool
	{
		$assignment = $assignment ? $assignment : 0;

		// Delete old widget to menu item associations
		if (!Menu::deleteByWidget($this->id))
		{
			throw new \Exception('Failed to remove previous menu assignments.');
		}

		// If the assignment is numeric, then something is selected (otherwise it's none).
		if (is_numeric($assignment))
		{
			// Variable is numeric, but could be a string.
			$assignment = (int) $assignment;

			// Logic check: if no widget excluded then convert to display on all.
			if ($assignment == -1 && empty($assigned))
			{
				$assignment = 0;
			}

			// Check needed to stop a widget being assigned to `All`
			// and other menu items resulting in a widget being displayed twice.
			if ($assignment === 0)
			{
				// assign new widget to `all` menu item associations
				$menu = new Menu(array(
					'widgetid' => $this->id,
					'menuid'   => 0
				));

				if (!$menu->save())
				{
					return false;
				}
			}
			elseif (!empty($assigned))
			{
				// Get the sign of the number.
				$sign = $assignment < 0 ? -1 : +1;

				// Preprocess the assigned array.
				$tuples = array();
				foreach ($assigned as &$pk)
				{
					$menu = new Menu(array(
						'widgetid' => $this->id,
						'menuid'   => ((int) $pk * $sign)
					));

					if (!$menu->save())
					{
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Method to move a row in the ordering sequence of a group of rows defined by an SQL WHERE clause.
	 * Negative numbers move the row up in the sequence and positive numbers move it down.
	 *
	 * @param   int  $delta  The direction and magnitude to move the row in the ordering sequence.
	 * @param   string   $where  WHERE clause to use for limiting the selection of rows to compact the ordering values.
	 * @return  bool     True on success.
	 */
	public function move($delta, $where = ''): bool
	{
		// If the change is none, do nothing.
		if (empty($delta))
		{
			return true;
		}

		// Select the primary key and ordering values from the table.
		$query = self::query()
			->where('position', '=', $this->position);

		// If the movement delta is negative move the row up.
		if ($delta < 0)
		{
			$query->where('ordering', '<', (int) $this->ordering);
			$query->orderBy('ordering', 'desc');
		}
		// If the movement delta is positive move the row down.
		elseif ($delta > 0)
		{
			$query->where('ordering', '>', (int) $this->ordering);
			$query->orderBy('ordering', 'asc');
		}

		// Add the custom WHERE clause if set.
		if ($where)
		{
			$query->where(DB::raw($where));
		}

		// Select the first row with the criteria.
		$row = $query->first();

		// If a row is found, move the item.
		if ($row)
		{
			$prev = $this->ordering;

			// Update the ordering field for this instance to the row's ordering value.
			if (!$this->update(['ordering' => (int) $row->ordering]))
			{
				return false;
			}

			// Update the ordering field for the row to this instance's ordering value.
			if (!$row->update(['ordering' => (int) $prev]))
			{
				return false;
			}
		}

		$all = self::query()
			->where('position', '=', $this->position)
			->orderBy('ordering', 'asc')
			->get();

		foreach ($all as $i => $row)
		{
			if ($row->ordering != ($i + 1))
			{
				$row->update(['ordering' => $i + 1]);
			}
		}

		return true;
	}

	/**
	 * Saves the manually set order of records.
	 *
	 * @param   array  $pks    An array of primary key ids.
	 * @param   array  $order  An array of order values.
	 * @return  bool
	 */
	public static function saveOrder(array $pks, array $order): bool
	{
		if (empty($pks))
		{
			return false;
		}

		// Update ordering values
		foreach ($pks as $i => $pk)
		{
			$model = self::findOrFail((int) $pk);

			if ($model->ordering != $order[$i])
			{
				$model->ordering = $order[$i];

				if (!$model->save())
				{
					return false;
				}
			}
		}

		return true;
	}
}
