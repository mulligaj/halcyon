<?php
namespace App\Widgets\Notice;

use App\Modules\Widgets\Entities\Widget;
use Carbon\Carbon;

/**
 * Notice widget
 */
class Notice extends Widget
{
	/**
	 * Display
	 *
	 * @return  null|\Illuminate\View\View
	 */
	public function run()
	{
		// Set today's time and date
		$now = Carbon::now();

		// Get some initial parameters
		$start = $this->params->get('start_publishing', $this->model->publish_up);
		$stop  = $this->params->get('stop_publishing', $this->model->publish_down);

		$this->publish = false;
		if (!$start || $start == '0000-00-00 00:00:00')
		{
			$this->publish = true;
		}
		else
		{
			if ($start <= $now)
			{
				$this->publish = true;
			}
			else
			{
				$this->publish = false;
			}
		}
		if (!$stop || $stop == '0000-00-00 00:00:00')
		{
			$this->publish = true;
		}
		else
		{
			if ($stop >= $now && $this->publish)
			{
				$this->publish = true;
			}
			else
			{
				$this->publish = false;
			}
		}

		$hide = '';
		if ($this->publish && $this->params->get('allowClose', 1))
		{
			// Figure out days left

			// make a unix timestamp for the given date
			$the_countdown_date = Carbon::parse($stop); //$this->_mkt($stop);

			// get current unix timestamp
			//$now = time() + (Config::get('offset') * 60 * 60);

			$difference = $the_countdown_date->timestamp - $now->timestamp;
			if ($difference < 0)
			{
				$difference = 0;
			}

			$this->days_left = floor($difference/60/60/24);
			$this->days_left = ($this->days_left ? $this->days_left : 7);

			$expires = $now->timestamp + 60*60*24*$this->days_left;

			$hide = request()->cookie($this->params->get('id', 'sitenotice'));

			if (!$hide && request()->input($this->params->get('id', 'sitenotice')))
			{
				setcookie($this->params->get('id', 'sitenotice'), 'closed', $expires);
			}
		}

		// Only do something if the module's time frame hasn't expired
		if (!$this->publish || $hide)
		{
			return;
		}

		// Get some parameters
		$this->alertlevel = $this->params->get('alertlevel', 'medium');
		$timezone         = $this->params->get('timezone');
		$message          = $this->params->get('message');

		// Convert start time
		$start = $this->_mkt($start);
		$d = $this->_convert($start);
		$time_start = $d['hour'] . ':' . $d['minute'] . ' ' . $d['ampm'] . ', ' . $d['month'] . ' ' . $d['day'] . ', ' . $d['year'];

		// Convert end time
		$stop = $this->_mkt($stop);
		$u = $this->_convert($stop);
		$time_end  = $u['hour'] . ':' . $u['minute'] . ' ' . $u['ampm'] . ', ' . $u['month'] . ' ' . $u['day'] . ', ' . $u['year'];

		// Convert countdown-to-start time
		$d_month   = date('m', $start);
		$d_day     = date('d', $start);
		$d_hour    = date('H', $start);
		$time_left = $this->_countdown($d['year'], $d_month, $d_day, $d_hour, $d['minute']);
		$time_cd_tostart = $this->_timeto($time_left);

		// Convert countdown-to-return time
		$u_month   = date('m', $stop);
		$u_day     = date('d', $stop);
		$u_hour    = date('H', $stop);
		$time_left = $this->_countdown($u['year'], $u_month, $u_day, $u_hour, $u['minute']);
		$time_cd_toreturn = $this->_timeto($time_left);

		// Parse message for tags
		$message = str_replace('<notice:start>', $time_start, $message);
		$message = str_replace('<notice:end>', $time_end, $message);
		$message = str_replace('<notice:countdowntostart>', $time_cd_tostart, $message);
		$message = str_replace('<notice:countdowntoreturn>', $time_cd_toreturn, $message);
		$message = str_replace('<notice:timezone>', $timezone, $message);

		// auto link?
		if ($this->params->get('autolink', 1))
		{
			$message = self::autoLinkText($message);
		}

		if (!trim($message))
		{
			$publish = false;
		}

		$layout = (string)$this->params->get('layout', 'index');

		return view($this->getViewName($layout), [
			'params'     => $this->params,
			'message'    => $message,
			'publish'    => $this->publish,
			'alertlevel' => (string)$this->params->get('alertlevel', 'info'),
			'id'         => (string)$this->params->get('htmlid', 'notices'),
		]);
	}

	/**
	 * Calculate the time left from a date time
	 *
	 * @param   int  $year    Year
	 * @param   int  $month   Month
	 * @param   int  $day     Day
	 * @param   int  $hour    Hour
	 * @param   int  $minute  Minute
	 * @return  array<int,int>
	 */
	private function _countdown($year, $month, $day, $hour, $minute)
	{
		// Make a unix timestamp for the given date
		$the_countdown_date = mktime($hour, $minute, 0, $month, $day, $year);

		// Get current unix timestamp
		$date = Carbon::now();
		$now = $date->format('U');

		$difference = $the_countdown_date - $now;
		if ($difference < 0)
		{
			$difference = 0;
		}

		$days_left    = floor($difference/60/60/24);
		$hours_left   = floor(($difference - $days_left*60*60*24)/60/60);
		$minutes_left = floor(($difference - $days_left*60*60*24 - $hours_left*60*60)/60);

		$left = array($days_left, $hours_left, $minutes_left);
		return $left;
	}

	/**
	 * Turn datetime YYYY-MM-DD hh:mm:ss to time
	 *
	 * @param   string   $stime  Datetime to convert
	 * @return  int
	 */
	private function _mkt($stime)
	{
		if ($stime && preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})[ ]([0-9]{2}):([0-9]{2}):([0-9]{2})/", $stime, $regs))
		{
			$stime = mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
		}
		return $stime;
	}

	/**
	 * Break a timestamp into its parts
	 *
	 * @param   int  $stime  Timestamp
	 * @return  array<string,string>
	 */
	private function _convert($stime)
	{
		$t = array();
		$t['year']   = date('Y', $stime);
		$t['month']  = date('M', $stime);
		$t['day']    = date('jS', $stime);
		$t['hour']   = date('g', $stime);
		$t['minute'] = date('i', $stime);
		$t['ampm']   = date('A', $stime);
		return $t;
	}

	/**
	 * Show the amount of time left
	 *
	 * @param   array   $stime  Timestamp
	 * @return  string
	 */
	private function _timeto($stime)
	{
		if ($stime[0] == 0 && $stime[1] == 0 && $stime[2] == 0)
		{
			$o  = trans('widget.notice::notice.IMMEDIATELY');
		}
		else
		{
			$o  = trans('widget.notice::notice.IN') . ' ';
			$o .= ($stime[0] > 0) ? $stime[0] . ' ' . trans('widget.notice::notice.DAYS') . ', '  : '';
			$o .= ($stime[1] > 0) ? $stime[1] . ' ' . trans('widget.notice::notice.HOURS') . ', ' : '';
			$o .= ($stime[2] > 0) ? $stime[2] . ' ' . trans('widget.notice::notice.MINUTES')      : '';
		}
		return $o;
	}

	/**
	 * Auto Link Text
	 *
	 * @param   string  $text  Text to look for links
	 * @return  string
	 */
	private static function autoLinkText($text)
	{
		// Replace email links
		$text = preg_replace('/([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})/', '<a href="mailto:$1">$1</a>', $text);

		// Replace url links
		$text = preg_replace('#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#', '<a class="ext-link" rel="external" href="$1">$1</a>', $text);

		// Return auto-linked text
		return $text;
	}
}
