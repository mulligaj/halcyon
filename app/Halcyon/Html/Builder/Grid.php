<?php

namespace App\Halcyon\Html\Builder;

use Carbon\Carbon;
use App\Halcyon\Traits\Checkable;

/**
 * Utility class for creating HTML Grids
 */
class Grid
{
	/**
	 * Display a boolean setting widget.
	 *
	 * @param   int  $i        The row index.
	 * @param   int  $value    The value of the boolean field.
	 * @param   string   $taskOn   Task to turn the boolean setting on.
	 * @param   string   $taskOff  Task to turn the boolean setting off.
	 * @return  string   The boolean setting widget.
	 */
	public static function boolean($i, $value, $taskOn = null, $taskOff = null)
	{
		// Load the behavior.
		self::behavior();

		// Build the <a> tag.
		$bool   = ($value) ? 'true icon-true' : 'false icon-false';
		//$bool  .= ($value) ? ' on' : ' off';

		$task   = ($value) ? $taskOff : $taskOn;
		$toggle = (!$task) ? false : true;

		// Build the title.
		$txt   = ($value) ? trans('global.yes') : trans('global.no');
		$title = $txt;

		if ($toggle)
		{
			$title .= ' :: ' . trans('global.click to toggle state');

			$html = '<a class="grid-action grid-boolean ' . $bool . ' hasTip" title="' . $title . '" data-id="cb' . $i . '" data-task="' . $task . '" href="#toggle" title="' . $title . '"><span>' . $txt . '</span></a>';
		}
		else
		{
			$html = '<a class="grid-action grid-boolean ' . $bool . '" title="' . $title . '"><span>' . $txt . '</span></a>';
		}

		return $html;
	}

	/**
	 * Method to sort a column in a grid
	 *
	 * @param   string  $title          The link title
	 * @param   string  $order          The order field for the column
	 * @param   string  $direction      The current direction
	 * @param   string  $selected       The selected ordering
	 * @return  string
	 */
	public static function sort($title, $order, $direction = 'asc', $selected = '', $new_direction = 'asc')
	{
		// Load the behavior.
		//self::behavior();

		$direction = strtolower($direction);
		$index = intval($direction == 'desc');

		if ($order != $selected)
		{
			$direction = $new_direction;
		}
		else
		{
			$direction = ($direction == 'desc') ? 'asc' : 'desc';
		}

		$html  = '<a href="?order=' . $order . '&amp;order_dir=' . $direction . '" data-order="' . $order . '" data-direction="' . $direction . '" title="' . trans('global.click to sort this column') . '" class="grid-order ';
		if ($order == $selected)
		{
			$html .= 'active ' . ($direction == 'desc' ? 'asc' : 'desc') . ' ';
		}
		$html .= 'sort">' . $title; //trans($title);
		$html .= '</a>';

		return $html;
	}

	/**
	 * Method to create a checkbox for a grid row.
	 *
	 * @param   int  $rowNum      The row index
	 * @param   int  $recId       The record id
	 * @param   bool  $checkedOut  True if item is checke out
	 * @param   string   $name        The name of the form element
	 * @return  mixed    String of html with a checkbox if item is not checked out, null if checked out.
	 */
	public static function id($rowNum, $recId, $checkedOut = false, $name = 'id')
	{
		if ($checkedOut)
		{
			return '';
		}

		$html  = '<span class="form-check">';
		$html .= '<input type="checkbox" id="cb' . $rowNum . '" name="' . $name . '[]" value="' . $recId . '" class="form-check-input checkbox-toggle" />';
		$html .= '<label for="cb' . $rowNum . '" class="form-check-label"><span class="sr-only">' . trans('global.admin.record id', ['id' => $recId]) . '</span></label>';
		$html .= '</span>';

		return $html;
	}

	/**
	 * Displays a checked out icon.
	 *
	 * @param   object   &$row        A data object (must contain checkedout as a property).
	 * @param   int  $i           The index of the row.
	 * @param   string   $identifier  The property name of the primary key or index of the row.
	 * @return  string
	 */
	public static function checkbox(&$row, $i, $identifier = 'id') //checkedOut
	{
		$userid = auth()->user()->id;

		$result = false;
		if ($row instanceof Checkable)
		{
			$result = $row->isCheckedOut($userid);
		}

		$checked = '';
		if ($result)
		{
			$checked = self::_checkedOut($row);
		}
		else
		{
			if ($identifier == 'id')
			{
				$checked = self::id($i, $row->$identifier);
			}
			else
			{
				$checked = self::id($i, $row->$identifier, $result, $identifier);
			}
		}

		return $checked;
	}

	/**
	 * Displays a "toggle all" checkbox
	 *
	 * @return  string
	 */
	public static function checkall()
	{
		$html  = '<span class="form-check">';
		$html .= '<input type="checkbox" name="toggle" value="" id="toggle-all" class="form-check-input checkbox-toggle toggle-all" />';
		$html .= '<label class="form-check-label" for="toggle-all"><span class="sr-only">' . trans('global.check all') . '</span></label>';
		$html .= '</span>';

		return $html;
	}

	/**
	 * Method to create a clickable icon to change the state of an item
	 *
	 * @param   mixed    $value     Either the scalar value or an object (for backward compatibility, deprecated)
	 * @param   int  $i         The index
	 * @param   string   $prefix    An optional prefix for the task
	 * @param   string   $checkbox  Checkbox ID prefix
	 * @return  string
	 */
	/*public static function published($value, $i, $prefix = '', $checkbox = 'cb')
	{
		if (is_object($value))
		{
			$value = $value->published;
		}

		$task   = $value ? 'unpublish' : 'publish';
		$alt    = $value ? trans('global.published') : trans('global.unpublished');
		$action = $value ? trans('global.unpublish item') : trans('global.publish item');

		$href = '<a href="#toggle" class="grid-action grid-state state ' . ($value ? 'publish' : 'unpublish') . '" data-id="' . $checkbox . $i . '" data-task="' . $prefix . $task . '" title="' . $action . '"><span>' . $alt . '</span></a>';

		return $href;
	}*/

	/**
	 * Returns an array of standard published state filter options.
	 *
	 * @param   array   $config  An array of configuration options.
	 *                           This array can contain a list of key/value pairs where values are boolean
	 *                           and keys can be taken from 'published', 'unpublished', 'archived', 'trash', 'all'.
	 *                           These pairs determine which values are displayed.
	 * @return  string  The HTML code for the select tag
	 */
	public static function publishedOptions($config = array())
	{
		// Build the active state filter options.
		$options = array();
		if (!array_key_exists('published', $config) || $config['published'])
		{
			$options[] = Select::option('1', 'global.published');
		}
		if (!array_key_exists('unpublished', $config) || $config['unpublished'])
		{
			$options[] = Select::option('0', 'global.unpublished');
		}
		if (!array_key_exists('archived', $config) || $config['archived'])
		{
			$options[] = Select::option('2', 'global.archived');
		}
		if (!array_key_exists('trash', $config) || $config['trash'])
		{
			$options[] = Select::option('-2', 'global.trashed');
		}
		if (!array_key_exists('all', $config) || $config['all'])
		{
			$options[] = Select::option('*', 'global.all');
		}
		return $options;
	}

	/**
	 * Method to create a select list of states for filtering
	 * By default the filter shows only published and unpublished items
	 *
	 * @param   string  $filter_state  The initial filter state
	 * @param   string  $published     The Text string for published
	 * @param   string  $unpublished   The Text string for Unpublished
	 * @param   string  $archived      The Text string for Archived
	 * @param   string  $trashed       The Text string for Trashed
	 * @return  string
	 */
	public static function states($filter_state = '*', $published = 'Published', $unpublished = 'Unpublished', $archived = null, $trashed = null)
	{
		$state = array(
			''  => '- ' . trans('global.select state') . ' -',
			'P' => trans($published),
			'U' => trans($unpublished)
		);

		if ($archived)
		{
			$state['A'] = trans($archived);
		}

		if ($trashed)
		{
			$state['T'] = trans($trashed);
		}

		return Select::genericlist(
			$state,
			'filter_state',
			array(
				'list.attr'   => 'class="inputbox filter filter-submit" size="1"',
				'list.select' => $filter_state,
				'option.key'  => null
			)
		);
	}

	/**
	 * Method to create an icon for saving a new ordering in a grid
	 *
	 * @param   array   $rows  The array of rows of rows
	 * @param   string  $cls   Classname to apply
	 * @param   string  $task  The task to use, defaults to save order
	 * @return  string
	 */
	public static function order($rows, $cls = 'saveoder', $task = 'saveorder')
	{
		// Load the behavior.
		//self::behavior();

		$href = '<a href="#" data-rows="' . (count($rows) - 1) . '" data-task="' . $task . '" class="grid-order-save ' . $cls . '" title="' . trans('global.save order') . '"><span class="fa fa-save" aria-hidden="true"></span></a>';

		return $href;
	}

	/**
	 * Method to create a checked out icon with optional overlib in a grid.
	 *
	 * @param   object   &$row     The row object
	 * @param   bool  $tooltip  True if an overlib with checkout information should be created.
	 * @return  string   HTML for the icon and tooltip
	 */
	protected static function _checkedOut(&$row, $tooltip = 1)
	{
		$hover = '<span class="checkedout">';

		if ($tooltip && isset($row->checked_out_time))
		{
			$text = addslashes(htmlspecialchars($row->editor, ENT_COMPAT, 'UTF-8'));

			$date = with(new Carbon($row->checked_out_time))->format('l, d F Y');
			$time = with(new Carbon($row->checked_out_time))->format('H:i');

			$hover = '<span class="editlinktip hasTip" title="' . trans('global.check out') . '::' . $text . '<br />' . $date . '<br />' . $time . '">';
		}

		return $hover . trans('global.checked out') . '</span>';
	}

	/**
	 * Method to build the behavior script and add it to the document head.
	 *
	 * @return  void
	 */
	public static function behavior()
	{
		static $loaded;

		if (!$loaded)
		{
			// Add the behavior to the document head.
			/*\App::get('document')->addScriptDeclaration(
				'jQuery(document).ready(function($){
					$("a.move_up, a.move_down, a.grid_true, a.grid_false, a.trash")
						.on("click", function(){
							if ($(this).attr("rel")) {
								args = jQuery.parseJSON($(this).attr("rel").replace(/\'/g, \'"\'));
								listItemTask(args.id, args.task);
							}
						});

					$("input.check-all-toggle").on("click", function(){
							if ($(this).checked) {
								$($(this).closest("form")).find("input[type=checkbox]").each(function(i){
									i.checked = true;
								})
							} else {
								$($(this).closest("form")).find("input[type=checkbox]").each(function(i){
									i.checked = false;
								})
							}
					});
				});'
			);*/

			Behavior::framework();

			$loaded = true;
		}
	}

	/**
	 * Returns a checked-out icon
	 *
	 * @param   int       $i           The row index.
	 * @param   string        $editorName  The name of the editor.
	 * @param   string        $time        The time that the object was checked out.
	 * @param   string|array  $prefix      An optional task prefix or an array of options
	 * @param   bool       $enabled     True to enable the action.
	 * @param   string        $checkbox    An optional prefix for checkboxes.
	 * @return  string  The required HTML.
	 */
	public static function checkedout($i, $editorName, $time, $prefix = '', $enabled = false, $checkbox = 'cb')
	{
		if (is_array($prefix))
		{
			$options  = $prefix;
			$enabled  = array_key_exists('enabled', $options)  ? $options['enabled']  : $enabled;
			$checkbox = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
			$prefix   = array_key_exists('prefix', $options)   ? $options['prefix']   : '';
		}

		$text = addslashes(htmlspecialchars($editorName, ENT_COMPAT, 'UTF-8'));
		$date = addslashes(htmlspecialchars(with(new Carbon($time))->format('l, d F Y'), ENT_COMPAT, 'UTF-8'));
		$time = addslashes(htmlspecialchars(with(new Carbon($time))->format('H:i'), ENT_COMPAT, 'UTF-8'));

		$active_title   = trans('global.check in') . '::' . $text . '<br />' . $date . '<br />' . $time;
		$inactive_title = trans('global.checked out') . '::' . $text . '<br />' . $date . '<br />' . $time;

		return self::action(
			$i, 'checkin', $prefix, trans('global.checked out'), $active_title, $inactive_title, true, 'checkedout',
			'checkedout', $enabled, false, $checkbox
		);
	}

	/**
	 * Returns a state on a grid
	 *
	 * @param   array         $states     array of value/state. Each state is an array of the form
	 *                                    (task, text, title,html active class, HTML inactive class)
	 *                                    or ('task'=>task, 'text'=>text, 'active_title'=>active title,
	 *                                    'inactive_title'=>inactive title, 'tip'=>boolean, 'active_class'=>html active class,
	 *                                    'inactive_class'=>html inactive class)
	 * @param   int       $value      The state value.
	 * @param   int       $i          The row index
	 * @param   string|array  $prefix     An optional task prefix or an array of options
	 * @param   bool       $enabled    An optional setting for access control on the action.
	 * @param   bool       $translate  An optional setting for translation.
	 * @param   string        $checkbox   An optional prefix for checkboxes.
	 * @return  string        The Html code
	 */
	public static function state($states, $value, $i, $prefix = '', $enabled = true, $translate = true, $checkbox = 'cb')
	{
		if (is_array($prefix))
		{
			$options   = $prefix;

			$enabled   = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
			$translate = array_key_exists('translate', $options) ? $options['translate'] : $translate;
			$checkbox  = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
			$prefix    = array_key_exists('prefix', $options) ? $options['prefix'] : '';
		}

		$state = $states[0];
		if (isset($states[(int) $value]))
		{
			$state = $states[(int) $value];
		}

		$task           = array_key_exists('task', $state) ? $state['task'] : $state[0];
		$text           = array_key_exists('text', $state) ? $state['text'] : (array_key_exists(1, $state) ? $state[1] : '');
		$active_title   = array_key_exists('active_title', $state) ? $state['active_title'] : (array_key_exists(2, $state) ? $state[2] : '');
		$inactive_title = array_key_exists('inactive_title', $state) ? $state['inactive_title'] : (array_key_exists(3, $state) ? $state[3] : '');
		$tip            = array_key_exists('tip', $state) ? $state['tip'] : (array_key_exists(4, $state) ? $state[4] : false);
		$active_class   = array_key_exists('active_class', $state) ? $state['active_class'] : (array_key_exists(5, $state) ? $state[5] : '');
		$inactive_class = array_key_exists('inactive_class', $state) ? $state['inactive_class'] : (array_key_exists(6, $state) ? $state[6] : '');

		return self::action(
			$i, $task, $prefix, $text, $active_title, $inactive_title, $tip,
			$active_class, $inactive_class, $enabled, $translate, $checkbox
		);
	}

	/**
	 * Returns a published state on a grid
	 *
	 * @param   int       $value         The state value.
	 * @param   int       $i             The row index
	 * @param   string|array  $prefix        An optional task prefix or an array of options
	 * @param   bool       $enabled       An optional setting for access control on the action.
	 * @param   string        $checkbox      An optional prefix for checkboxes.
	 * @param   string        $publish_up    An optional start publishing date.
	 * @param   string        $publish_down  An optional finish publishing date.
	 * @return  string        The Html code
	 */
	public static function published($value, $i, $prefix = '', $enabled = true, $checkbox = 'cb', $publish_up = null, $publish_down = null)
	{
		if (is_array($prefix))
		{
			$options  = $prefix;
			$enabled  = array_key_exists('enabled', $options)  ? $options['enabled']  : $enabled;
			$checkbox = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
			$prefix   = array_key_exists('prefix', $options)   ? $options['prefix']   : '';
		}

		$states = array(
			1  => array('unpublish', 'global.published',   'global.unpublish item', 'global.published',   false, 'publish',   'publish'),
			0  => array('publish',   'global.unpublished', 'global.publish item',   'global.unpublished', false, 'unpublish', 'unpublish'),
			2  => array('unpublish', 'global.archived',    'global.unpublish item', 'global.archived',    false, 'archive',   'archive'),
			-2 => array('publish',   'global.trashed',     'global.publish item',   'global.trashed',     false, 'trash',     'trash')
		);

		// Special state for dates
		if ($publish_up || $publish_down)
		{
			$nullDate = null; //\App::get('db')->getNullDate();
			$nowDate = Carbon::now()->timestamp;

			$tz = new \DateTimeZone(User::getParam('timezone', \Config::get('offset')));

			$publish_up   = ($publish_up != $nullDate)   ? Carbon::parse($publish_up)   : false;
			$publish_down = ($publish_down != $nullDate) ? Carbon::parse($publish_down) : false;

			// Create tip text, only we have publish up or down settings
			$tips = array();
			if ($publish_up)
			{
				$tips[] = trans('global.start publishing', $publish_up->toDateTimeString());
			}
			if ($publish_down)
			{
				$tips[] = trans('global.finish publishing', $publish_down->toDateTimeString());
			}
			$tip = empty($tips) ? false : implode('<br/>', $tips);

			// Add tips and special titles
			foreach ($states as $key => $state)
			{
				// Create special titles for published items
				if ($key == 1)
				{
					$states[$key][2] = $states[$key][3] = 'global.published';

					if ($publish_up > $nullDate && $nowDate < $publish_up->toUnix())
					{
						$states[$key][2] = $states[$key][3] = 'global.published but pending';
						$states[$key][5] = $states[$key][6] = 'pending';
					}
					if ($publish_down > $nullDate && $nowDate > $publish_down->toUnix())
					{
						$states[$key][2] = $states[$key][3] = 'global.published but expired';
						$states[$key][5] = $states[$key][6] = 'expired';
					}
				}

				// Add tips to titles
				if ($tip)
				{
					$states[$key][1] = trans($states[$key][1]);
					$states[$key][2] = trans($states[$key][2]) . '::' . $tip;
					$states[$key][3] = trans($states[$key][3]) . '::' . $tip;
					$states[$key][4] = true;
				}
			}
			return self::state($states, $value, $i, array('prefix' => $prefix, 'translate' => !$tip), $enabled, true, $checkbox);
		}

		return self::state($states, $value, $i, $prefix, $enabled, true, $checkbox);
	}

	/**
	 * Creates a order-up action icon.
	 *
	 * @param   int  $i         The row index.
	 * @param   string   $task      An optional task to fire.
	 * @param   mixed    $prefix    An optional task prefix or an array of options
	 * @param   string   $text      An optional text to display
	 * @param   bool  $enabled   An optional setting for access control on the action.
	 * @param   string   $checkbox  An optional prefix for checkboxes.
	 * @return  string   The required HTML.
	 */
	public static function orderUp($limitstart, $i, $condition = true, $task = 'orderup', $prefix = '', $text = 'global.move up', $enabled = true, $checkbox = 'cb')
	{
		if (($i > 0 || ($i + $limitstart > 0)) && $condition)
		{
			//return self::orderUp($i, $task, '', $alt, $enabled, $checkbox);

			if (is_array($prefix))
			{
				$options  = $prefix;
				$text     = array_key_exists('text', $options)     ? $options['text']     : $text;
				$enabled  = array_key_exists('enabled', $options)  ? $options['enabled']  : $enabled;
				$checkbox = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
				$prefix   = array_key_exists('prefix', $options)   ? $options['prefix']   : '';
			}

			$html = array();
			$html[] = '<a class="grid-actio"';
			$html[] = ' href="' . $task . '" data-id="' . $checkbox . $i . '" data-task="' . $prefix . $task . '"';
			$html[] = ' title="' . addslashes(htmlspecialchars(trans($text), ENT_COMPAT, 'UTF-8')) . '">';
			$html[] = '<span class="icon-arrow-up" aria-hidden="true"></span>';
			$html[] = '</a>';

			return implode("\n", $html); //self::action($i, $task, $prefix, $text, $text, $text, false, 'uparrow', 'uparrow_disabled', $enabled, true, $checkbox);
		}

		return '&#160;';
	}

	/**
	 * Creates a order-down action icon.
	 *
	 * @param   int  $i         The row index.
	 * @param   string   $task      An optional task to fire.
	 * @param   mixed    $prefix    An optional task prefix or an array of options
	 * @param   string   $text      An optional text to display
	 * @param   bool  $enabled   An optional setting for access control on the action.
	 * @param   string   $checkbox  An optional prefix for checkboxes.
	 * @return  string   The required HTML.
	 */
	public static function orderDown($limitstart, $i, $total, $condition = true, $task = 'orderdown', $prefix = '', $text = 'global.move down', $enabled = true, $checkbox = 'cb')
	{
		if (($i < $total - 1 || $i + $limitstart < $total - 1) && $condition)
		{
			if (is_array($prefix))
			{
				$options  = $prefix;
				$text     = array_key_exists('text', $options)     ? $options['text']     : $text;
				$enabled  = array_key_exists('enabled', $options)  ? $options['enabled']  : $enabled;
				$checkbox = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
				$prefix   = array_key_exists('prefix', $options)   ? $options['prefix']   : '';
			}

			$html = array();
			$html[] = '<a class="grid-actio"';
			$html[] = ' href="' . $task . '" data-id="' . $checkbox . $i . '" data-task="' . $prefix . $task . '"';
			$html[] = ' title="' . addslashes(htmlspecialchars(trans($text), ENT_COMPAT, 'UTF-8')) . '">';
			$html[] = '<span class="icon-arrow-down" aria-hidden="true"></span>';
			$html[] = '</a>';

			return implode("\n", $html); //self::action($i, $task, $prefix, $text, $text, $text, false, 'downarrow', 'downarrow_disabled', $enabled, true, $checkbox);
		}

		return '&#160;';
	}

	/**
	 * Returns a isDefault state on a grid
	 *
	 * @param   int       $value     The state value.
	 * @param   int       $i         The row index
	 * @param   string|array  $prefix    An optional task prefix or an array of options
	 * @param   bool       $enabled   An optional setting for access control on the action.
	 * @param   string        $checkbox  An optional prefix for checkboxes.
	 * @return  string        The HTML code
	 */
	public static function isDefault($value, $i, $prefix = '', $enabled = true, $checkbox = 'cb')
	{
		if (is_array($prefix))
		{
			$options  = $prefix;
			$enabled  = array_key_exists('enabled', $options)  ? $options['enabled']  : $enabled;
			$checkbox = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
			$prefix   = array_key_exists('prefix', $options)   ? $options['prefix']   : '';
		}

		$states = array(
			1 => array('unsetDefault', 'global.default', 'global.unset default', 'global.default', false, 'default', 'default', 'text' => '<span class="fa fa-star" aria-hidden="true"></span>'),
			0 => array('setDefault', '', 'global.set default', '', false, 'notdefault', 'notdefault', 'text' => '<span class="fa fa-star" aria-hidden="true"></span>'),
		);

		return self::state($states, $value, $i, $prefix, $enabled, true, $checkbox);
	}

	/**
	 * Returns an action on a grid
	 *
	 * @param   int  $i               The row index
	 * @param   string   $task            The task to fire
	 * @param   mixed    $prefix          An optional task prefix or an array of options
	 * @param   string   $text            An optional text to display
	 * @param   string   $active_title    An optional active tooltip to display if $enable is true
	 * @param   string   $inactive_title  An optional inactive tooltip to display if $enable is true
	 * @param   bool  $tip             An optional setting for tooltip
	 * @param   string   $active_class    An optional active HTML class
	 * @param   string   $inactive_class  An optional inactive HTML class
	 * @param   bool  $enabled         An optional setting for access control on the action.
	 * @param   bool  $translate       An optional setting for translation.
	 * @param   string   $checkbox        An optional prefix for checkboxes.
	 * @return  string   The Html code
	 */
	public static function action($i, $task, $prefix = '', $text = '', $active_title = '', $inactive_title = '', $tip = false, $active_class = '', $inactive_class = '', $enabled = true, $translate = true, $checkbox = 'cb')
	{
		// Load the behavior.
		//self::behavior();

		if (is_array($prefix))
		{
			$options = $prefix;
			$text           = array_key_exists('text', $options) ? $options['text'] : $text;
			$active_title   = array_key_exists('active_title', $options) ? $options['active_title'] : $active_title;
			$inactive_title = array_key_exists('inactive_title', $options) ? $options['inactive_title'] : $inactive_title;
			$tip            = array_key_exists('tip', $options) ? $options['tip'] : $tip;
			$active_class   = array_key_exists('active_class', $options) ? $options['active_class'] : $active_class;
			$inactive_class = array_key_exists('inactive_class', $options) ? $options['inactive_class'] : $inactive_class;
			$enabled        = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
			$translate      = array_key_exists('translate', $options) ? $options['translate'] : $translate;
			$checkbox       = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
			$prefix         = array_key_exists('prefix', $options) ? $options['prefix'] : '';
		}

		/*if ($tip)
		{
			Behavior::tooltip();
		}*/

		if ($enabled)
		{
			$html[] = '<a class="grid-action' . ($tip ? ' hasTip' : '') . '"';
			$html[] = ' href="#" data-id="' . $checkbox . $i . '" data-task="' . $prefix . $task . '"';
			$html[] = ' title="' . addslashes(htmlspecialchars($translate ? trans($active_title) : $active_title, ENT_COMPAT, 'UTF-8')) . '">';
			$html[] = '<span class="state ' . $active_class . '">';
			$html[] = $text ? ('<span class="text">' . ($translate ? trans($text) : $text) . '</span>') : '';
			$html[] = '</span>';
			$html[] = '</a>';
		}
		else
		{
			$html[] = '<a class="grid-action' . ($tip ? ' hasTip' : '') . '"';
			$html[] = ' title="' . addslashes(htmlspecialchars($translate ? trans($inactive_title) : $inactive_title, ENT_COMPAT, 'UTF-8')) . '">';
			$html[] = '<span class="state ' . $inactive_class . '">';
			$html[] = $text ? ('<span class="text">' . ($translate ? trans($text) : $text) . '</span>') :'';
			$html[] = '</span>';
			$html[] = '</a>';
		}
		return implode($html);
	}
}
