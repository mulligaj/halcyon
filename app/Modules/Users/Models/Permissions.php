<?php
namespace App\Modules\Users\Models;

use App\Halcyon\Access\Asset;
use App\Halcyon\Access\Gate;
use App\Halcyon\Access\Rules;
use App\Halcyon\Form\Form;
use App\Modules\Users\Models\User;
use Illuminate\Support\Fluent;
use Illuminate\Config\Repository;

/**
 * Permissions model
 *
 * @property int    $id
 * @property int    $parent_id
 * @property int    $lft
 * @property int    $rgt
 * @property int    $level
 * @property string $name
 * @property string $title
 * @property string $rules
 */
class Permissions extends Fluent
{
	/**
	 * Method to get a form object.
	 *
	 * @param   array   $data  Data for the form.
	 * @return  Form
	 */
	public function getForm($data = array()): Form
	{
		$file = __DIR__ . '/Forms/Permissions.xml';

		Form::addFieldPath(__DIR__ . '/fields');

		$form = new Form('users.permissions', array('control' => 'permissions'));

		if (!$form->loadFile($file, false, '//form'))
		{
			throw new \Exception(trans('global.error LOADFILE_FAILED'));
		}

		if (!empty($data))
		{
			$form->bind($data);
		}

		return $form;
	}

	/**
	 * Method to get the configuration data.
	 *
	 * This method will load the global configuration data straight from
	 * Config. If configuration data has been saved in the session, that
	 * data will be merged into the original data, overwriting it.
	 *
	 * @return  array  An array containing all global config data.
	 */
	public function getData(): array
	{
		// Get the config data.
		$data = config();

		// Set the site code, if not present
		if (isset($data['app']))
		{
			if (!isset($data['app']['sitecode']) || !$data['app']['sitecode'])
			{
				// This should be 4 alpha-numeric characters at most
				$sitename = preg_replace("/[^a-zA-Z0-9]/", '', $data['app']['sitename']);
				$data['app']['sitecode'] = strtolower(substr($sitename, 0, 4));
			}
		}

		// Prime the asset_id for the rules.
		$data['asset_id'] = 1;

		// Get the text filter data
		$data['filters'] = Arr::fromObject(config('modules.config.filters'));

		// If no filter data found, get from com_content (update of 1.6/1.7 site)
		if (empty($data['filters']))
		{
			$data['filters'] = Arr::fromObject(config('modules.pages.filters'));
		}

		// Check for data in the session.
		$temp = User::getState('module.config.global.data');

		// Merge in the session data.
		if (!empty($temp))
		{
			$data = array_merge($data, $temp);
		}

		return $data;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   object  $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 * @return  array|false   Array of filtered data if valid, false otherwise.
	 */
	public function validate($form, $data, $group = null)
	{
		// Filter and validate the form data.
		$data = $form->filter($data);
		$return = $form->validate($data, $group);

		// Check for an error.
		if ($return instanceof \Exception)
		{
			//throw new \Exception($return->getMessage());
			return false;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				throw new \Exception(trans($message));
			}

			return false;
		}

		return $data;
	}

	/**
	 * Method to save the configuration data.
	 *
	 * @param   array  $data  An array containing all global config data.
	 * @return  bool   True on success, false on failure.
	 */
	public function save($data): bool
	{
		// Save the rules
		if (isset($data['rules']))
		{
			$rules = new Rules($data['rules']);

			// Check that we aren't removing our Super User permission
			// Need to get groups from database, since they might have changed
			$myGroups = Access::getGroupsByUser(User::find('id'));
			$myRules = $rules->getData();
			$hasSuperAdmin = $myRules['admin']->allow($myGroups);
			if (!$hasSuperAdmin)
			{
				$this->setError(trans('config::config.error.removing super admin'));
				return false;
			}

			$asset = Asset::oneByName('root.1');
			if ($asset->get('id'))
			{
				$asset->set('rules', (string) $rules);
				$asset->save();
			}
			else
			{
				$this->setError(trans('config::config.error.root asset not found'));
				return false;
			}
			unset($data['rules']);
		}

		// Save the text filters
		if (isset($data['filters']))
		{
			//$registry = new Repository(array('filters' => $data['filters']));

			$extension = Extension::findByElement('config');

			if (!$extension->isNew())
			{
				$extension->params->set('filters', $data['filters']);
				$extension->save();
			}
			else
			{
				$this->setError(trans('config::config.error.extension not found'));
				return false;
			}
			unset($data['filters']);
		}

		// Get the previous configuration.
		$config = new Repository();

		$prev = $config->all();

		// We do this to preserve values that were not in the form.
		// Note: We can't use array_merge() as we're trying to preserve
		//       options that were explicitely set to blank and merging
		//       will return the previous, filled-in value
		foreach ($prev as $key => $vals)
		{
			$values = isset($data[$key]) ? $data[$key] : array();

			foreach ($vals as $k => $v)
			{
				if (!isset($values[$k]))
				{
					// Database password isn't apart of the config form
					// and we don't want to overwrite it. So we need to
					// inherit from previous settings.
					if ($key == 'database' && $k == 'password')
					{
						$values[$k] = $v;
						continue;
					}

					if (is_numeric($v))
					{
						$values[$k] = 0;
					}
					elseif (is_array($v))
					{
						$values[$k] = array();
					}
					else
					{
						$values[$k] = '';
					}
				}
			}
			ksort($values);

			$data[$key] = $values;
		}
		ksort($data);

		// Perform miscellaneous options based on configuration settings/changes.

		// Purge the database session table if we are changing to the database handler.
		if ($prev['session']['session_handler'] != 'database'
		 && $data['session']['session_handler'] == 'database')
		{
			$db = App::get('db');

			$past = time() + 1;
			$query = $db->getQuery()
				->delete('#__sessions')
				->where('time', '<', (int) $past);

			$db->setQuery($query->toString());
			$db->execute();
		}

		if (empty($data['cache']['cache_handler']))
		{
			$data['cache']['caching'] = 0;
		}

		// Clean the cache if disabled but previously enabled.
		if ((!$data['cache']['caching'] && $prev['cache']['caching'])
		 || $data['cache']['cache_handler'] !== $prev['cache']['cache_handler'])
		{
			try
			{
				Cache::clean();
			}
			catch (\Exception $e)
			{
				Notify::error('SOME_ERROR_CODE', $e->getMessage());
			}
		}

		// Overwrite the old FTP credentials with the new ones.
		if (isset($data['ftp']))
		{
			// Fix misnamed FTP key
			// Not sure how or where this originally happened...
			if (!isset($data['ftp']['ftp_enable'])
			 && isset($data['ftp']['ftp_enabled']))
			{
				$data['ftp']['ftp_enable'] = $data['ftp']['ftp_enabled'];
				unset($data['ftp']['ftp_enabled']);
			}

			$temp = config();
			$temp->set('ftp.ftp_enable', isset($data['ftp']['ftp_enable']) ? $data['ftp']['ftp_enable'] : 0);
			$temp->set('ftp.ftp_host', isset($data['ftp']['ftp_host']) ? $data['ftp']['ftp_host'] : '');
			$temp->set('ftp.ftp_port', isset($data['ftp']['ftp_port']) ? $data['ftp']['ftp_port'] : '');
			$temp->set('ftp.ftp_user', isset($data['ftp']['ftp_user']) ? $data['ftp']['ftp_user'] : '');
			$temp->set('ftp.ftp_pass', isset($data['ftp']['ftp_pass']) ? $data['ftp']['ftp_pass'] : '');
			$temp->set('ftp.ftp_root', isset($data['ftp']['ftp_root']) ? $data['ftp']['ftp_root'] : '');
		}

		// Clear cache of com_config component.
		Cache::clean('_system');

		$result = Event::trigger('onApplicationBeforeSave', array($data));

		// Store the data.
		if (in_array(false, $result, true))
		{
			throw new \RuntimeException(trans('config::config.error.unknown before saving'));
		}

		// Write the configuration file.
		$return = $this->writeConfigFile($data);

		// Trigger the after save event.
		event('onApplicationAfterSave', array($data));

		return $result;
	}
}
