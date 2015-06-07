<?php
/**
 * @package     Com_Localise
 * @subpackage  helper
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.path');
jimport("joomla.utilities.date");

/**
 * Localise Helper class
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 * @since       4.0
 */
abstract class LocaliseHelper
{
	/**
	 * Array containing the origin information
	 *
	 * @var    array
	 * @since  4.0
	 */
	protected static $origins = array('site' => null, 'administrator' => null, 'installation' => null);

	/**
	 * Array containing the package information
	 *
	 * @var    array
	 * @since  4.0
	 */
	protected static $packages = array();

	/**
	 * Prepares the component submenu
	 *
	 * @param   string  $vName  Name of the active view
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_LOCALISE_SUBMENU_LANGUAGES'),
			'index.php?option=com_localise&view=languages',
			$vName == 'languages'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_LOCALISE_SUBMENU_TRANSLATIONS'),
			'index.php?option=com_localise&view=translations',
			$vName == 'translations'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_LOCALISE_SUBMENU_PACKAGES'),
			'index.php?option=com_localise&view=packages',
			$vName == 'packages'
		);
	}

	/**
	 * Determines if a given path is writable in the current environment
	 *
	 * @param   string  $path  Path to check
	 *
	 * @return  boolean  True if writable
	 *
	 * @since   4.0
	 */
	public static function isWritable($path)
	{
		if (JFactory::getConfig()->get('config.ftp_enable'))
		{
			return true;
		}
		else
		{
			while (!file_exists($path))
			{
				$path = dirname($path);
			}

			return is_writable($path) || JPath::isOwner($path) || JPath::canChmod($path);
		}
	}

	/**
	 * Check if the installation path exists
	 *
	 * @return  boolean  True if the installation path exists
	 *
	 * @since   4.0
	 */
	public static function hasInstallation()
	{
		$params        = JComponentHelper::getParams('com_localise');
		$customisedref = $params->get('customisedref', '0');

		if ($customisedref != '0')
		{
			// If enabled installation client with normal reference
			// This also allow work with customised references of installation client.
			if (is_dir(LOCALISEPATH_INSTALLATION))
			{
				return is_dir(LOCALISEPATH_CUSTOMISED_INSTALLATION);
			}
		}

		// Else verify it as normal way.
		return is_dir(LOCALISEPATH_INSTALLATION);
	}

	/**
	 * Retrieve the packages array
	 *
	 * @return  array
	 *
	 * @since   4.0
	 */
	public static function getPackages()
	{
		if (empty(static::$packages))
		{
			static::scanPackages();
		}

		return static::$packages;
	}

	/**
	 * Scans the filesystem for language files in each package
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	protected static function scanPackages()
	{
		$model         = JModelLegacy::getInstance('Packages', 'LocaliseModel', array('ignore_request' => true));
		$model->setState('list.start', 0);
		$model->setState('list.limit', 0);
		$packages       = $model->getItems();

		foreach ($packages as $package)
		{
			static::$packages[$package->name] = $package;

			foreach ($package->administrator as $file)
			{
				static::$origins['administrator'][$file] = $package->name;
			}

			foreach ($package->site as $file)
			{
				static::$origins['site'][$file] = $package->name;
			}
		}
	}

	/**
	 * Retrieves the origin information
	 *
	 * @param   string  $filename  The filename to check
	 * @param   string  $client    The client to check
	 *
	 * @return  string  Origin data
	 *
	 * @since   4.0
	 */
	public static function getOrigin($filename, $client)
	{
		if ($filename == 'override')
		{
			return '_override';
		}

		// If the $origins array doesn't contain data, fill it
		if (empty(static::$origins['site']))
		{
			static::scanPackages();
		}

		if (isset(static::$origins[$client][$filename]))
		{
			return static::$origins[$client][$filename];
		}
		else
		{
			return '_thirdparty';
		}
	}

	/**
	 * Scans the filesystem
	 *
	 * @param   string  $client  The client to scan
	 * @param   string  $type    The extension type to scan
	 *
	 * @return  array
	 *
	 * @since   4.0
	 */
	public static function getScans($client = '', $type = '')
	{
		$params   = JComponentHelper::getParams('com_localise');
		$suffixes = explode(',', $params->get('suffixes', '.sys'));

		$filter_type   = $type ? $type : '.';
		$filter_client = $client ? $client : '.';
		$scans         = array();

		// Scan installation folders
		if (preg_match("/$filter_client/", 'installation'))
		{
			// TODO ;-)
		}

		// Scan administrator folders
		if (preg_match("/$filter_client/", 'administrator'))
		{
			// Scan administrator components folders
			if (preg_match("/$filter_type/", 'component'))
			{
				$scans[] = array(
					'prefix' => '',
					'suffix' => '',
					'type'   => 'component',
					'client' => 'administrator',
					'path'   => LOCALISEPATH_ADMINISTRATOR . '/components/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => '',
						'suffix' => $suffix,
						'type'   => 'component',
						'client' => 'administrator',
						'path'   => LOCALISEPATH_ADMINISTRATOR . '/components/',
						'folder' => ''
					);
				}
			}

			// Scan administrator modules folders
			if (preg_match("/$filter_type/", 'module'))
			{
				$scans[] = array(
					'prefix' => '',
					'suffix' => '',
					'type'   => 'module',
					'client' => 'administrator',
					'path'   => LOCALISEPATH_ADMINISTRATOR . '/modules/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => '',
						'suffix' => $suffix,
						'type'   => 'module',
						'client' => 'administrator',
						'path'   => LOCALISEPATH_ADMINISTRATOR . '/modules/',
						'folder' => ''
					);
				}
			}

			// Scan administrator templates folders
			if (preg_match("/$filter_type/", 'template'))
			{
				$scans[] = array(
					'prefix' => 'tpl_',
					'suffix' => '',
					'type'   => 'template',
					'client' => 'administrator',
					'path'   => LOCALISEPATH_ADMINISTRATOR . '/templates/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => 'tpl_',
						'suffix' => $suffix,
						'type'   => 'template',
						'client' => 'administrator',
						'path'   => LOCALISEPATH_ADMINISTRATOR . '/templates/',
						'folder' => ''
					);
				}
			}

			// Scan plugins folders
			if (preg_match("/$filter_type/", 'plugin'))
			{
				$plugin_types = JFolder::folders(JPATH_PLUGINS);

				foreach ($plugin_types as $plugin_type)
				{
					// Scan administrator language folders as this is where plugin languages are installed
					$scans[] = array(
						'prefix' => 'plg_' . $plugin_type . '_',
						'suffix' => '',
						'type'   => 'plugin',
						'client' => 'administrator',
						'path'   => JPATH_PLUGINS . "/$plugin_type/",
						'folder' => ''
					);

					foreach ($suffixes as $suffix)
					{
						$scans[] = array(
							'prefix' => 'plg_' . $plugin_type . '_',
							'suffix' => $suffix,
							'type'   => 'plugin',
							'client' => 'administrator',
							'path'   => JPATH_PLUGINS . "/$plugin_type/",
							'folder' => ''
						);
					}
				}
			}
		}

		// Scan site folders
		if (preg_match("/$filter_client/", 'site'))
		{
			// Scan site components folders
			if (preg_match("/$filter_type/", 'component'))
			{
				$scans[] = array(
					'prefix' => '',
					'suffix' => '',
					'type'   => 'component',
					'client' => 'site',
					'path'   => LOCALISEPATH_SITE . '/components/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => '',
						'suffix' => $suffix,
						'type'   => 'component',
						'client' => 'site',
						'path'   => LOCALISEPATH_SITE . '/components/',
						'folder' => ''
					);
				}
			}

			// Scan site modules folders
			if (preg_match("/$filter_type/", 'module'))
			{
				$scans[] = array(
					'prefix' => '',
					'suffix' => '',
					'type'   => 'module',
					'client' => 'site',
					'path'   => LOCALISEPATH_SITE . '/modules/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => '',
						'suffix' => $suffix,
						'type'   => 'module',
						'client' => 'site',
						'path'   => LOCALISEPATH_SITE . '/modules/',
						'folder' => ''
					);
				}
			}

			// Scan site templates folders
			if (preg_match("/$filter_type/", 'template'))
			{
				$scans[] = array(
					'prefix' => 'tpl_',
					'suffix' => '',
					'type'   => 'template',
					'client' => 'site',
					'path'   => LOCALISEPATH_SITE . '/templates/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => 'tpl_',
						'suffix' => $suffix,
						'type'   => 'template',
						'client' => 'site',
						'path'   => LOCALISEPATH_SITE . '/templates/',
						'folder' => ''
					);
				}
			}
		}

		return $scans;
	}

	/**
	 * Get file ID in the database for the given file path
	 *
	 * @param   string  $path  Path to lookup
	 *
	 * @return  integer  File ID
	 *
	 * @since   4.0
	 */
	public static function getFileId($path)
	{
		static $fileIds = null;

		if (!isset($fileIds))
		{
			$db = JFactory::getDbo();

			$db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName(array('id', 'path')))
					->from($db->quoteName('#__localise'))
			);

			$fileIds = $db->loadObjectList('path');
		}

		if (is_file($path) || preg_match('/.ini$/', $path))
		{
			if (!array_key_exists($path, $fileIds))
			{
				JTable::addIncludePath(JPATH_COMPONENT . '/tables');

				/* @type  LocaliseTableLocalise  $table */
				$table       = JTable::getInstance('Localise', 'LocaliseTable');
				$table->path = $path;
				$table->store();

				$fileIds[$path] = new stdClass;
				$fileIds[$path]->id = $table->id;
			}

			return $fileIds[$path]->id;
		}
		else
		{
			$id = 0;
		}

		return $id;
	}

	/**
	 * Get file path in the database for the given file id
	 *
	 * @param   integer  $id  Id to lookup
	 *
	 * @return  string   File Path
	 *
	 * @since   4.0
	 */
	public static function getFilePath($id)
	{
		static $filePaths = null;

		if (!isset($filePaths))
		{
			$db = JFactory::getDbo();

			$db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName(array('id', 'path')))
					->from($db->quoteName('#__localise'))
			);

			$filePaths = $db->loadObjectList('id');
		}

		return array_key_exists("$id", $filePaths) ?
		$filePaths["$id"]->path : '';
	}

	/**
	 * Determine if a package at given path is core or not.
	 *
	 * @param   string  $path  Path to lookup
	 *
	 * @return  mixed  null if file is invalid | True if core else false.
	 *
	 * @since   4.0
	 */
	public static function isCorePackage($path)
	{
		if (is_file($path) || preg_match('/.ini$/', $path))
		{
			$xml = simplexml_load_file($path);

			return ((string) $xml->attributes()->core) == 'true';
		}
	}

	/**
	 * Find a translation file
	 *
	 * @param   string  $client    Client to lookup
	 * @param   string  $tag       Language tag to lookup
	 * @param   string  $filename  Filename to lookup
	 *
	 * @return  string  Path to the requested file
	 *
	 * @since   4.0
	 */
	public static function findTranslationPath($client, $tag, $filename)
	{
		$params = JComponentHelper::getParams('com_localise');
		$priority = $params->get('priority', '0') == '0' ? 'global' : 'local';
		$path = static::getTranslationPath($client, $tag, $filename, $priority);

		if (!is_file($path))
		{
			$priority = $params->get('priority', '0') == '0' ? 'local' : 'global';
			$path = static::getTranslationPath($client, $tag, $filename, $priority);
		}

		return $path;
	}

	/**
	 * Get a translation path
	 *
	 * @param   string  $client    Client to lookup
	 * @param   string  $tag       Language tag to lookup
	 * @param   string  $filename  Filename to lookup
	 * @param   string  $storage   Storage location to check
	 *
	 * @return  string  Path to the requested file
	 *
	 * @since   4.0
	 */
	public static function getTranslationPath($client, $tag, $filename, $storage)
	{
		if ($filename == 'override')
		{
			$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/language/overrides/$tag.override.ini";
		}
		elseif ($filename == 'joomla')
		{
			$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/language/$tag/$tag.ini";
		}
		elseif ($storage == 'global')
		{
			$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/language/$tag/$tag.$filename.ini";
		}
		else
		{
			$parts     = explode('.', $filename);
			$extension = $parts[0];

			switch (substr($extension, 0, 3))
			{
				case 'com':
					$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/components/$extension/language/$tag/$tag.$filename.ini";

					break;

				case 'mod':
					$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/modules/$extension/language/$tag/$tag.$filename.ini";

					break;

				case 'plg':
					$parts  = explode('_', $extension);
					$group  = $parts[1];
					$parts	= explode('.', $filename);
					$pluginname = $parts[0];
					$plugin = substr($pluginname, 5 + strlen($group));
					$path   = JPATH_PLUGINS . "/$group/$plugin/language/$tag/$tag.$filename.ini";

					break;

				case 'tpl':
					$template = substr($extension, 4);
					$path     = constant('LOCALISEPATH_' . strtoupper($client)) . "/templates/$template/language/$tag/$tag.$filename.ini";

					break;

				case 'lib':
					$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/language/$tag/$tag.$filename.ini";

					if (!is_file($path))
					{
						$path = $client == 'administrator' ? 'LOCALISEPATH_' . 'SITE' : 'LOCALISEPATH_' . 'ADMINISTRATOR' . "/language/$tag/$tag.$filename.ini";
					}

					break;

				default   :
					$path = '';

					break;
			}
		}

		return $path;
	}

	/**
	 * Load a language file for translating the package name
	 *
	 * @param   string  $extension  The extension to load
	 * @param   string  $client     The client from where to load the file
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public static function loadLanguage($extension, $client)
	{
		$extension = strtolower($extension);
		$lang      = JFactory::getLanguage();
		$prefix    = substr($extension, 0, 3);

		switch ($prefix)
		{
			case 'com':
				$lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)), null, false, true)
					|| $lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)) . "/components/$extension/", null, false, true);

				break;

			case 'mod':
				$lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)), null, false, true)
					|| $lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)) . "/modules/$extension/", null, false, true);

				break;

			case 'plg':
				$lang->load($extension, 'LOCALISEPATH_' . 'ADMINISTRATOR', null, false, true)
					|| $lang->load($extension, LOCALISEPATH_ADMINISTRATOR . "/components/$extension/", null, false, true);

				break;

			case 'tpl':
				$template = substr($extension, 4);
				$lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)), null, false, true)
					|| $lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)) . "/templates/$template/", null, false, true);

				break;

			case 'lib':
			case 'fil':
			case 'pkg':
				$lang->load($extension, JPATH_ROOT, null, false, true);

				break;
		}
	}

	/**
	 * Parses the sections of a language file
	 *
	 * @param   string  $filename  The filename to parse
	 *
	 * @return  array  Array containing the file data
	 *
	 * @since   4.0
	 */
	public static function parseSections($filename)
	{
		static $sections = array();

		if (!array_key_exists($filename, $sections))
		{
			if (file_exists($filename))
			{
				$error = '';

				if (!defined('_QQ_'))
				{
					define('_QQ_', '"');
				}

				ini_set('track_errors', '1');

				$contents = file_get_contents($filename);
				$contents = str_replace('_QQ_', '"\""', $contents);
				$strings  = @parse_ini_string($contents, true);

				if (!empty($php_errormsg))
				{
					$error = "Error parsing " . basename($filename) . ": $php_errormsg";
				}

				ini_restore('track_errors');

				if ($strings !== false)
				{
					$default = array();

					foreach ($strings as $key => $value)
					{
						if (is_string($value))
						{
							$default[$key] = $value;

							unset($strings[$key]);
						}
						else
						{
							break;
						}
					}

					if (!empty($default))
					{
						$strings = array_merge(array('Default' => $default), $strings);
					}

					$keys = array();

					foreach ($strings as $section => $value)
					{
						foreach ($value as $key => $string)
						{
							$keys[$key] = $strings[$section][$key];
						}
					}
				}
				else
				{
					$keys = false;
				}

				$sections[$filename] = array('sections' => $strings, 'keys' => $keys, 'error' => $error);
			}
			else
			{
				$sections[$filename] = array('sections' => array(), 'keys' => array(), 'error' => '');
			}
		}

		if (!empty($sections[$filename]['error']))
		{
			$model = JModelLegacy::getInstance('Translation', 'LocaliseModel');
			$model->setError($sections[$filename]['error']);
		}

		return $sections[$filename];
	}

	/**
	 * Gets the files to use as source reference from Github
	 *
	 * @param   array  $gh_data  Array with the required data
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getSourceGithubfiles($gh_data = array())
	{
		if (!empty($gh_data))
		{
			$params        = JComponentHelper::getParams('com_localise');
			$ref_tag       = $params->get('reference', 'en-GB');
			$saved_ref     = $params->get('customisedref', '0');
			$allow_develop = $params->get('gh_allow_develop', 0);
			$customisedref = $saved_ref;

			if ($customisedref == '0')
			{
				$installed_version = new JVersion;
				$customisedref     = $installed_version->getShortVersion();
			}

			if ($ref_tag != 'en-GB' || $allow_develop == 0)
			{
				JFactory::getApplication()->enqueueMessage(
					JText::_('COM_LOCALISE_ERROR_GETTING_UNALLOWED_CONFIGURATION'),
					'warning');

				return false;
			}

			$gh_data['customisedref']  = $customisedref;
			$gh_target                 = self::getCustomisedsource($gh_data);
			$gh_paths                  = array();
			$gh_client                 = $gh_data['github_client'];
			$gh_user                   = $gh_target['user'];
			$gh_project                = $gh_target['project'];
			$gh_branch                 = $gh_target['branch'];
			$gh_token                  = $params->get('gh_token', '');
			$gh_paths['administrator'] = 'administrator/language/en-GB';
			$gh_paths['site']          = 'language/en-GB';
			$gh_paths['installation']  = 'installation/language/en-GB';

			$reference_client_path = JPATH_ROOT . '/' . $gh_paths[$gh_client];
			$reference_client_path = JFolder::makeSafe($reference_client_path);

			$custom_client_path = JPATH_ROOT . '/media/com_localise/customisedref/github/'
					. $gh_data['github_client']
					. '/'
					. $gh_data['customisedref'];

			$custom_client_path = JFolder::makeSafe($custom_client_path);

			$customisedref_status      = JPATH_COMPONENT_ADMINISTRATOR
							. '/customisedref/'
							. $gh_data['github_client']
							. '_'
							. $customisedref
							. '_customised_ref.txt';

			if (JFile::exists($customisedref_status))
			{
				// We have done this trunk and is not required get the files from Github again.

				// So we can move the customised source reference files to core client folder
				$update_files = self::updateSourcereference($gh_client, $custom_client_path, $reference_client_path);

				if ($update_files == false)
				{
					return false;
				}

			return true;
			}

			if (!JFolder::exists($custom_client_path))
			{
				$create_folder = self::createFolder($gh_data, $index = 'true');

				if ($create_folder == false)
				{
					return false;
				}
			}

			$options = new JRegistry;

			if (!empty($gh_token))
			{
				$options->set('gh.token', $gh_token);
				$github = new JGithub($options);
			}
			else
			{
				// Without a token runs fatal.
				// $github = new JGithub;

				// Trying with a 'read only' public repositories token
				// But base 64 encoded to avoid Github alarms sharing it.
				$gh_token = base64_decode('MzY2NzYzM2ZkMzZmMWRkOGU5NmRiMTdjOGVjNTFiZTIyMzk4NzVmOA==');
				$options->set('gh.token', $gh_token);
				$github = new JGithub($options);
			}

			try
			{
				$repostoryfiles = $github->repositories->contents->get(
					$gh_user,
					$gh_project,
					$gh_paths[$gh_client],
					$gh_branch
					);
			}
			catch (Exception $e)
			{
				if ($saved_ref == '0')
				{
					JFactory::getApplication()->enqueueMessage(
						JText::_('COM_LOCALISE_ERROR_GITHUB_GETTING_LOCAL_INSTALLED_FILES'),
						'warning');
				}
				else
				{
					JFactory::getApplication()->enqueueMessage(
						JText::_('COM_LOCALISE_ERROR_GITHUB_GETTING_REPOSITORY_FILES'),
						'warning');
				}

				return false;
			}

			$all_files_list = self::getLanguagefileslist($custom_client_path);
			$ini_files_list = self::getInifileslist($custom_client_path);

			$files_to_include = array();

			foreach ($repostoryfiles as $repostoryfile)
			{
				$file_to_include = $repostoryfile->name;
				$file_path = JFolder::makeSafe($custom_client_path . '/' . $file_to_include);
				$reference_file_path = JFolder::makeSafe($reference_client_path . '/' . $file_to_include);

				$custom_file = $github->repositories->contents->get(
						$gh_user,
						$gh_project,
						$repostoryfile->path,
						$gh_branch
						);

				$files_to_include[] = $file_to_include;

				if (!empty($custom_file) && isset($custom_file->content))
				{
					$file_to_include = $repostoryfile->name;
					$file_contents = base64_decode($custom_file->content);
					JFile::write($file_path, $file_contents);

					if (!JFile::exists($file_path))
					{
						JFactory::getApplication()->enqueueMessage(
							JText::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_CREATE_DEV_FILE'),
							'warning');

						return false;
					}
				}
			}

			if (!empty($all_files_list) && !empty($files_to_include))
			{
				// For files not present in dev yet.
				$files_to_delete = array_diff($all_files_list, $files_to_include);

				if (!empty($files_to_delete))
				{
					foreach ($files_to_delete as $file_to_delete)
					{
						if ($file_to_delete != 'index.html')
						{
							$file_path = JFolder::makeSafe($custom_client_path . "/" . $file_to_delete);
							JFile::delete($file_path);

							if (JFile::exists($file_path))
							{
								JFactory::getApplication()->enqueueMessage(
									JText::_('COM_LOCALISE_ERROR_GITHUB_FILE_TO_DELETE_IS_PRESENT'),
									'warning');

								return false;
							}
						}
					}
				}
			}

			$customisedref_path  = JPATH_COMPONENT_ADMINISTRATOR
					. '/customisedref/'
					. $gh_client
					. '_'
					. $gh_data['customisedref']
					. '_customised_ref.txt';

			$customisedref_path  = JFolder::makeSafe($customisedref_path);

			// After we have all the files we can mark as done this trunk.
			$file_contents = $gh_data['customisedref'] . "=DONE\n";
			JFile::write($customisedref_path, $file_contents);

			if (JFile::exists($customisedref_status))
			{
				// We have done this trunk.

				// So we can move the customised source reference files to core client folder
				$update_files = self::updateSourcereference($gh_client, $custom_client_path, $reference_client_path);

				if ($update_files == false)
				{
					return false;
				}

			return true;
			}

			return false;
		}

		JFactory::getApplication()->enqueueMessage(JText::_('COM_LOCALISE_ERROR_GITHUB_NO_DATA_PRESENT'), 'warning');

		return false;
	}

	/**
	 * Gets the reference name to use at Github
	 *
	 * @param   array  $gh_data  Array with the required data
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getCustomisedsource($gh_data = array())
	{
		$source_ref = $gh_data['customisedref'];

		$sources = array();

		// Detailing it one by one due in this case we can add other Github users or projects
		// To get the language files for a determined Joomla's version that is not present from main repository.
		$sources['3.4.1']['user'] = 'joomla';
		$sources['3.4.1']['project'] = 'joomla-cms';
		$sources['3.4.1']['branch'] = '3.4.1';

		$sources['3.4.0']['user'] = 'joomla';
		$sources['3.4.0']['project'] = 'joomla-cms';
		$sources['3.4.0']['branch'] = '3.4.0';

		$sources['3.3.6']['user'] = 'joomla';
		$sources['3.3.6']['project'] = 'joomla-cms';
		$sources['3.3.6']['branch'] = '3.3.6';

		$sources['3.3.5']['user'] = 'joomla';
		$sources['3.3.5']['project'] = 'joomla-cms';
		$sources['3.3.5']['branch'] = '3.3.5';

		$sources['3.3.4']['user'] = 'joomla';
		$sources['3.3.4']['project'] = 'joomla-cms';
		$sources['3.3.4']['branch'] = '3.3.4';

		$sources['3.3.3']['user'] = 'joomla';
		$sources['3.3.3']['project'] = 'joomla-cms';
		$sources['3.3.3']['branch'] = '3.3.3';

		$sources['3.3.2']['user'] = 'joomla';
		$sources['3.3.2']['project'] = 'joomla-cms';
		$sources['3.3.2']['branch'] = '3.3.2';

		$sources['3.3.1']['user'] = 'joomla';
		$sources['3.3.1']['project'] = 'joomla-cms';
		$sources['3.3.1']['branch'] = '3.3.1';

		$sources['3.3.0']['user'] = 'joomla';
		$sources['3.3.0']['project'] = 'joomla-cms';
		$sources['3.3.0']['branch'] = '3.3.0';

		if (array_key_exists($source_ref, $sources))
		{
			return ($sources[$source_ref]);
		}

		// For undefined REF 0 cases due Joomla releases at Github are following a version name patern.
		$sources[$source_ref]['user'] = 'joomla';
		$sources[$source_ref]['project'] = 'joomla-cms';
		$sources[$source_ref]['branch'] = $source_ref;

	return ($sources[$source_ref]);
	}

	/**
	 * Keep updated the reference client path with the selected customised sourcefiles as reference.
	 *
	 * @param   string  $client                 The client
	 *
	 * @param   string  $custom_client_path     The path where the new source reference is stored
	 *
	 * @param   string  $reference_client_path  The path where the old source reference is stored
	 *
	 * @return  boolean
	 *
	 * @since   4.11
	 */
	public static function updateSourcereference($client, $custom_client_path, $reference_client_path)
	{
		$develop_client_path   = JPATH_ROOT . '/media/com_localise/develop/github/joomla-cms/en-GB/' . $client;
		$develop_client_path   = JFolder::makeSafe($develop_client_path);

		$custom_ini_files_list = self::getInifileslist($custom_client_path);
		$last_ini_files_list   = self::getInifileslist($develop_client_path);

		$files_to_exclude = array();

			if (!empty($custom_ini_files_list) && !empty($last_ini_files_list))
			{
				// Verify that we have stored a full set of core files.

				if (JFile::exists($develop_client_path . '/en-GB.xml'))
				{
					// This one is for core files not present within last in dev yet.
					// Due have no sense add old language files to translate for the comming soon package.

					$files_to_exclude = array_diff($custom_ini_files_list, $last_ini_files_list);

					if (!empty($files_to_exclude))
					{
						foreach ($files_to_exclude as $file_to_delete)
						{
							$custom_file_path = JFolder::makeSafe($custom_client_path . "/" . $file_to_delete);
							$actual_file_path = JFolder::makeSafe($reference_client_path . "/" . $file_to_delete);

							JFile::delete($custom_file_path);

							// Also verify if the same file is also present in core language folder.
							if (JFile::exists($actual_file_path))
							{
								JFile::delete($custom_file_path);

								JFactory::getApplication()->enqueueMessage(
									JText::_('COM_LOCALISE_OLD_FILE_DELETED') . $file_to_delete,
									'notice');
							}
						}

						// Getting the new list again
						$custom_ini_files_list = self::getInifileslist($custom_client_path);
					}
				}
			}

		$errors = 0;

			foreach ($custom_ini_files_list as $customised_source_file)
			{
				$source_path = $custom_client_path . '/' . $customised_source_file;
				$file_contents = file_get_contents($source_path);
				$target_path = $reference_client_path . '/' . $customised_source_file;

				if (!JFile::write($target_path, $file_contents))
				{
					$errors = 1;
				}
			}

			if ($errors == 1)
			{
				return false;
			}

		return true;
	}

	/**
	 * Gets from zero or keept updated the files in develop to use as target reference from Github
	 *
	 * @param   array  $gh_data  Array with the required data
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getTargetgithubfiles($gh_data = array())
	{
		if (!empty($gh_data))
		{
			$now                = new JDate;
			$now                = $now->toSQL();
			$params             = JComponentHelper::getParams('com_localise');
			$client_to_update   = 'gh_' . $gh_data['github_client'] . '_last_update';
			$last_stored_update = $params->get($client_to_update, '');
			$ref_tag            = $params->get('reference', 'en-GB');
			$allow_develop      = $params->get('gh_allow_develop', 0);

			if ($allow_develop == 0)
			{
				return false;
			}

			if ($ref_tag != 'en-GB')
			{
				JFactory::getApplication()->enqueueMessage(
					JText::_('COM_LOCALISE_ERROR_GETTING_ALLOWED_REFERENCE_TAG'),
					'warning');

				return false;
			}

			if (!empty($last_stored_update))
			{
				$last_update = new JDate($last_stored_update);
				$last_update = $last_update->toSQL();
				$interval    = $params->get('gh_updates_interval', '1') == '1' ? 24 : 1;
				$interval    = $last_update . " +" . $interval . " hours";
				$next_update = new JDate($interval);
				$next_update = $next_update->toSQL();

				if ($now >= $next_update)
				{
					$get_files = 1;
				}
				else
				{
					$get_files = 0;
				}
			}
			else
			{
				$get_files = 1;
			}

			if ($get_files == 0)
			{
				return false;
			}

			$gh_paths                  = array();
			$gh_client                 = $gh_data['github_client'];
			$gh_user                   = 'joomla';
			$gh_project                = 'joomla-cms';
			$gh_branch                 = $params->get('gh_branch', 'master');
			$gh_token                  = $params->get('gh_token', '');
			$gh_paths['administrator'] = 'administrator/language/en-GB';
			$gh_paths['site']          = 'language/en-GB';
			$gh_paths['installation']  = 'installation/language/en-GB';

			$reference_client_path = JPATH_ROOT . '/' . $gh_paths[$gh_client];
			$reference_client_path = JFolder::makeSafe($reference_client_path);

			$develop_client_path = JPATH_ROOT . '/media/com_localise/develop/github/joomla-cms/en-GB/' . $gh_client;
			$develop_client_path = JFolder::makeSafe($develop_client_path);

			$options = new JRegistry;

			if (!empty($gh_token))
			{
				$options->set('gh.token', $gh_token);
				$github = new JGithub($options);
			}
			else
			{
				// Without a token runs fatal.
				// $github = new JGithub;

				// Trying with a 'read only' public repositories token
				// But base 64 encoded to avoid Github alarms sharing it.
				$gh_token = base64_decode('MzY2NzYzM2ZkMzZmMWRkOGU5NmRiMTdjOGVjNTFiZTIyMzk4NzVmOA==');
				$options->set('gh.token', $gh_token);
				$github = new JGithub($options);
			}

			try
			{
				$repostoryfiles = $github->repositories->contents->get(
					$gh_user,
					$gh_project,
					$gh_paths[$gh_client],
					$gh_branch
					);
			}
			catch (Exception $e)
			{
				JFactory::getApplication()->enqueueMessage(
					JText::_('COM_LOCALISE_ERROR_GITHUB_GETTING_REPOSITORY_FILES'),
					'warning');

				return false;
			}

			$all_files_list = self::getLanguagefileslist($develop_client_path);
			$ini_files_list = self::getInifileslist($develop_client_path);
			$sha_files_list = self::getShafileslist($gh_data);

			$sha = '';
			$files_to_include = array();

			foreach ($repostoryfiles as $repostoryfile)
			{
				$file_to_include = $repostoryfile->name;
				$file_path = JFolder::makeSafe($develop_client_path . '/' . $file_to_include);
				$reference_file_path = JFolder::makeSafe($reference_client_path . '/' . $file_to_include);

				if (	(array_key_exists($file_to_include, $sha_files_list)
					&& ($sha_files_list[$file_to_include] != $repostoryfile->sha))
					|| empty($sha_files_list)
					|| !JFile::exists($file_path))
				{
					$in_dev_file = $github->repositories->contents->get(
							$gh_user,
							$gh_project,
							$repostoryfile->path,
							$gh_branch
							);
				}
				else
				{
					$in_dev_file = '';
				}

				$files_to_include[] = $file_to_include;
				$sha_path  = JPATH_COMPONENT_ADMINISTRATOR . '/develop/gh_joomla_' . $gh_client . '_files.txt';
				$sha_path  = JFolder::makeSafe($sha_path);

				if (!empty($in_dev_file) && isset($in_dev_file->content))
				{
					$file_to_include = $repostoryfile->name;
					$file_contents = base64_decode($in_dev_file->content);
					JFile::write($file_path, $file_contents);

					if (!JFile::exists($file_path))
					{
						JFactory::getApplication()->enqueueMessage(
							JText::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_CREATE_DEV_FILE'),
							'warning');

						return false;
					}

					if (!JFile::exists($reference_file_path)
						&& ($gh_client == 'administrator' || $gh_client == 'site'))
					{
						// Adding files only present in develop to core reference location.
						JFile::write($reference_file_path, $file_contents);

						if (!JFile::exists($reference_file_path))
						{
							JFactory::getApplication()->enqueueMessage(
								JText::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_ADD_NEW_FILES'),
								'warning');

							return false;
						}
					}
				}

				// Saved for each time due few times get all the github files at same time can crash.
				// This one can help to remember the last one saved correctly and next time continue from there.
				$sha .= $repostoryfile->name . "::" . $repostoryfile->sha . "\n";
				JFile::write($sha_path, $sha);

				if (!JFile::exists($sha_path))
				{
					JFactory::getApplication()->enqueueMessage(
						JText::_('COM_LOCALISE_ERROR_GITHUB_NO_SHA_FILE_PRESENT'),
						'warning');

					return false;
				}
			}

			if (!empty($all_files_list) && !empty($files_to_include))
			{
				// For files not present in dev yet.
				$files_to_delete = array_diff($all_files_list, $files_to_include);

				if (!empty($files_to_delete))
				{
					foreach ($files_to_delete as $file_to_delete)
					{
						if ($file_to_delete != 'index.html')
						{
							$file_path = JFolder::makeSafe($develop_client_path . "/" . $file_to_delete);
							JFile::delete($file_path);

							if (JFile::exists($file_path))
							{
								JFactory::getApplication()->enqueueMessage(
									JText::_('COM_LOCALISE_ERROR_GITHUB_FILE_TO_DELETE_IS_PRESENT'),
									'warning');

								return false;
							}
						}
					}
				}
			}

			self::saveLastupdate($client_to_update);

			return true;
		}

		JFactory::getApplication()->enqueueMessage(JText::_('COM_LOCALISE_ERROR_GITHUB_NO_DATA_PRESENT'), 'warning');

		return false;
	}

	/**
	 * Gets the changes between language files versions
	 *
	 * @param   array  $refsections       The released reference data
	 * @param   array  $develop_sections  The developed reference data
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getDevelopchanges($refsections = array(), $develop_sections = array())
	{
		if (isset($refsections['keys']) && isset($develop_sections['keys']))
		{
			$keys_in_reference = array_keys($refsections['keys']);
			$keys_in_develop   = array_keys($develop_sections['keys']);

			// Catching new keys in develop
			$developdata['extra_keys']['amount'] = 0;
			$developdata['extra_keys']['keys'] = array();
			$developdata['extra_keys']['strings'] = array();
			$extras_in_develop = array_diff($keys_in_develop, $keys_in_reference);

			if (!empty($extras_in_develop))
			{
				foreach ($extras_in_develop as $extra_key)
				{
					$developdata['extra_keys']['amount']++;
					$developdata['extra_keys']['keys'][] = $extra_key;
					$developdata['extra_keys']['strings'][$extra_key] = $develop_sections['keys'][$extra_key];
				}
			}

			// Catching text changes in develop
			$developdata['text_changes']['amount'] = 0;
			$developdata['text_changes']['keys'] = array();
			$developdata['text_changes']['ref_in_dev'] = array();
			$developdata['text_changes']['diff'] = array();

			foreach ($refsections['keys'] as $key => $string)
			{
				if (array_key_exists($key, $develop_sections['keys']))
				{
					$string_in_develop = $develop_sections['keys'][$key];
					$text_changes = self::htmlgetTextchanges($string, $string_in_develop);

					if (!empty($text_changes))
					{
						$developdata['text_changes']['amount']++;
						$developdata['text_changes']['keys'][] = $key;
						$developdata['text_changes']['ref_in_dev'][$key] = $develop_sections['keys'][$key];
						$developdata['text_changes']['ref'][$key] = $string;
						$developdata['text_changes']['diff'][$key] = $text_changes;
					}
				}
			}

		return $developdata;
		}

	return array();
	}

	/**
	 * Gets the list of ini files
	 *
	 * @param   string  $client_path  The data to the client path
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getInifileslist($client_path = '')
	{
		if (!empty($client_path))
		{
			$files = JFolder::files($client_path, ".ini$");

			return $files;
		}

	return array();
	}

	/**
	 * Gets the list of all type of files in develop
	 *
	 * @param   string  $develop_client_path  The data to the client path
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getLanguagefileslist($develop_client_path = '')
	{
		if (!empty($develop_client_path))
		{
			$files = JFolder::files($develop_client_path);

			return $files;
		}

	return array();
	}

	/**
	 * Gets the stored SHA id for the files in develop.
	 *
	 * @param   array  $gh_data  The required data.
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getShafileslist($gh_data = array())
	{
		$sha_files = array();
		$gh_client = $gh_data['github_client'];
		$sha_path  = JFolder::makeSafe(JPATH_COMPONENT_ADMINISTRATOR . '/develop/gh_joomla_' . $gh_client . '_files.txt');

		if (JFile::exists($sha_path))
		{
			$file_contents = file_get_contents($sha_path);
			$lines = preg_split("/\\r\\n|\\r|\\n/", $file_contents);

			if (!empty($lines))
			{
				foreach ($lines as $line)
				{
					if (!empty($line))
					{
						list($filename, $sha) = explode('::', $line, 2);

						if (!empty($filename) && !empty($sha))
						{
							$sha_files[$filename] = $sha;
						}
					}
				}
			}
		}

	return $sha_files;
	}

	/**
	 * Save the date of the last Github files update by client.
	 *
	 * @param   string  $client_to_update  The client language files.
	 *
	 * @return  bolean
	 *
	 * @since   4.11
	 */
	public static function saveLastupdate($client_to_update)
	{
		$now    = new JDate;
		$now    = $now->toSQL();
		$params = JComponentHelper::getParams('com_localise');
		$params->set($client_to_update, $now);

		$localise_id = JComponentHelper::getComponent('com_localise')->id;

		$table = JTable::getInstance('extension');
		$table->load($localise_id);
		$table->bind(array('params' => $params->toString()));

		if (!$table->check())
		{
			JFactory::getApplication()->enqueueMessage($table->getError(), 'warning');

			return false;
		}

		if (!$table->store())
		{
			JFactory::getApplication()->enqueueMessage($table->getError(), 'warning');

			return false;
		}

		return true;
	}

	/**
	 * Create the required folders for develop
	 *
	 * @param   array   $gh_data  Array with the data
	 * @param   string  $index    If true, allow to create an index.html file
	 *
	 * @return  bolean
	 *
	 * @since   4.11
	 */
	public static function createFolder($gh_data = array(), $index = 'true')
	{
		if (!empty($gh_data) && isset($gh_data['customisedref']))
		{
		$full_path = JPATH_ROOT . '/media/com_localise/customisedref/github/'
					. $gh_data['github_client']
					. '/'
					. $gh_data['customisedref'];

		$full_path = JFolder::makeSafe($full_path);

			if (!JFolder::create($full_path))
			{
			}

			if (JFolder::exists($full_path))
			{
				if ($index == 'true')
				{
				$cretate_index = self::createIndex($full_path);

					if ($cretate_index == 1)
					{
						return true;
					}

				JFactory::getApplication()->enqueueMessage(JText::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_CREATE_INDEX_FILE'), 'warning');

				return false;
				}

			return true;
			}
			else
			{
				JFactory::getApplication()->enqueueMessage(JText::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_CREATE_FOLDERS'), 'warning');

				return false;
			}
		}

	return false;
	}

	/**
	 * Creates an index.html file within folders for develop
	 *
	 * @param   string  $full_path  The full path.
	 *
	 * @return  bolean
	 *
	 * @since   4.11
	 */
	public static function createIndex($full_path = '')
	{
		if (!empty($full_path))
		{
		$path = JFolder::makeSafe($full_path . '/index.html');

		$index_content = '<!DOCTYPE html><title></title>';

			if (!JFile::exists($path))
			{
				JFile::write($path, $index_content);
			}

			if (!JFile::exists($path))
			{
				return false;
			}
			else
			{
				return true;
			}
		}

	return false;
	}

	/**
	 * Gets the text changes.
	 *
	 * @param   array  $old  The string parts in reference.
	 * @param   array  $new  The string parts in develop.
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getTextchanges($old, $new)
	{
		$maxlen = 0;

		foreach ($old as $oindex => $ovalue)
		{
			$nkeys = array_keys($new, $ovalue);

			foreach ($nkeys as $nindex)
			{
				$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;

				if ($matrix[$oindex][$nindex] > $maxlen)
				{
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
				}

			unset ($nkeys, $nindex);
			}

		unset ($oindex, $ovalue);
		}

		if ($maxlen == 0)
		{
			return array(array ('d' => $old, 'i' => $new));
		}

		return array_merge(
			self::getTextchanges(
			array_slice($old, 0, $omax),
			array_slice($new, 0, $nmax)
			),
			array_slice($new, $nmax, $maxlen),
			self::getTextchanges(
			array_slice($old, $omax + $maxlen),
			array_slice($new, $nmax + $maxlen)
			)
			);
	}

	/**
	 * Gets the html text changes.
	 *
	 * @param   string  $old  The string in reference.
	 * @param   string  $new  The string in develop.
	 *
	 * @return  string
	 *
	 * @since   4.11
	 */
	public static function htmlgetTextchanges($old, $new)
	{
		$text_changes = '';

		if ($old == $new)
		{
			return $text_changes;
		}

		$old = str_replace('  ', 'LOCALISEDOUBLESPACES', $old);
		$new = str_replace('  ', 'LOCALISEDOUBLESPACES', $new);

		$diff = self::getTextchanges(explode(' ', $old), explode(' ', $new));

		foreach ($diff as $k)
		{
			if (is_array($k))
			{
				$text_changes .= (!empty ($k['d'])?"LOCALISEDELSTART"
					. implode(' ', $k['d']) . "LOCALISEDELSTOP ":'')
					. (!empty($k['i']) ? "LOCALISEINSSTART"
					. implode(' ', $k['i'])
					. "LOCALISEINSSTOP " : '');
			}
			else
			{
				$text_changes .= $k . ' ';
			}

		unset ($k);
		}

		$text_changes = htmlspecialchars($text_changes);
		$text_changes = preg_replace('/LOCALISEINSSTART/', "<ins class='diff_ins'>", $text_changes);
		$text_changes = preg_replace('/LOCALISEINSSTOP/', "</ins>", $text_changes);
		$text_changes = preg_replace('/LOCALISEDELSTART/', "<del class='diff_del'>", $text_changes);
		$text_changes = preg_replace('/LOCALISEDELSTOP/', "</del>", $text_changes);
		$double_spaces = '<span class="red-space"><font color="red">XX</font></span>';
		$text_changes = str_replace('LOCALISEDOUBLESPACES', $double_spaces, $text_changes);

	return $text_changes;
	}
}
