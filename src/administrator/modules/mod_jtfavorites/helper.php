<?php
/**
 * @package      Joomla.Administrator
 * @subpackage   mod_jtfavorites
 *
 * @author       Guido De Gobbis <support@joomtools.de>
 * @copyright    2020 JoomTools.de - All rights reserved.
 * @license      GNU General Public License version 3 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Helper for mod_jtfavorites
 *
 * @since   1.0.0
 */
class ModJtFavoritesHelper
{
	/**
	 * Define integer for the state 'trash'
	 * @var     int
	 * @since   1.0.0
	 */
	const TRASH = -2;

	/**
	 * @var     int
	 * @since   1.0.0
	 */
	public static $row = 0;

	/**
	 * @var     bool
	 * @since   1.0.0
	 */
	public static $loadJs = true;

	/**
	 * Get a list of articles.
	 *
	 * @param   \Joomla\Registry\Registry  $params  Module params
	 *
	 * @return   array  A list of entries.
	 * @since    1.0.0
	 */
	public static function getList($params)
	{
		$self   = new self;
		$db     = Factory::getDbo();
		$userId = Factory::getUser()->get('id');

		$query = $db->getQuery(true);

		$select = array(
			$db->qn('jtf.assets_name'),
			$db->qn('jtf.client_id'),
			$db->qn('jtf.state'),
			'CASE ' . $db->qn('jtf.favorite_title')
			. ' WHEN \'\' THEN (CASE (SUBSTRING_INDEX(' . $db->qn('jtf.assets_name') . ', \'.\', 1))'
			. ' WHEN \'com_modules\' THEN ' . $db->qn('mdl.title')
			. ' WHEN \'com_plugins\' THEN ' . $db->qn('plg.name')
			. ' END) ELSE ' . $db->qn('jtf.favorite_title') . ' END AS title',
			'CASE ' . $db->qn('jtf.favorite_title')
			. ' WHEN \'\' THEN (CASE (SUBSTRING_INDEX(' . $db->qn('jtf.assets_name') . ', \'.\', 1))'
			. ' WHEN \'com_modules\' THEN ' . $db->qn('mdl.module')
			. ' WHEN \'com_plugins\' THEN CONCAT(\'plg_\',' . $db->qn('plg.folder') . ',\'_\',' . $db->qn('plg.element') . ')'
			. ' END) ELSE NULL END AS extension_name',
			'CASE (SUBSTRING_INDEX(' . $db->qn('jtf.assets_name') . ', \'.\', 1))'
			. ' WHEN \'com_modules\' THEN ' . $db->qn('mdl.checked_out')
			. ' WHEN \'com_plugins\' THEN CONCAT(\'plg_\',' . $db->qn('plg.checked_out') . ',\'_\',' . $db->qn('plg.element') . ')'
			. ' ELSE NULL END AS editor',
			'CASE (SUBSTRING_INDEX(' . $db->qn('jtf.assets_name') . ', \'.\', 1))'
			. ' WHEN \'com_modules\' THEN ' . $db->qn('mdl.checked_out_time')
			. ' WHEN \'com_plugins\' THEN CONCAT(\'plg_\',' . $db->qn('plg.checked_out_time') . ',\'_\',' . $db->qn('plg.element') . ')'
			. ' ELSE NULL END AS checked_out_time',
		);

		$query->select($select)
			->from($db->qn('#__jtfavorites') . ' AS jtf')
			->join('LEFT', $db->qn('#__modules')
				. ' AS mdl ON SUBSTRING_INDEX(' . $db->qn('jtf.assets_name')
				. ', \'.\', -1)=' . $db->qn('mdl.id'))
			->join('LEFT', $db->qn('#__extensions')
				. ' AS plg ON SUBSTRING_INDEX(' . $db->qn('jtf.assets_name')
				. ', \'.\', -1)=' . $db->qn('plg.extension_id'))
			->where($db->qn('jtf.user_id') . '=' . (int) $userId);

		$result = $db->setQuery($query)->loadObjectList();
		$result = $self->sortList($result, $params);

		return $result;
	}

	/**
	 * @param   array                      $items   Object list with database entries
	 * @param   \Joomla\Registry\Registry  $params  Module params
	 *
	 * @return   array
	 * @since    1.0.0
	 */
	private function sortList($items, $params)
	{
		$loadJs           = array();
		$toDeleteInDb     = array();
		$isRoot           = Factory::getUser()->authorise('root.1');
		$changeState      = $params->get('allow_change_state');
		$linkItem         = $params->get('link_to_item');
		$showTrashedItems = $isRoot ?: filter_var($params->get('show_trashed_items'), FILTER_VALIDATE_BOOLEAN);

		// Create item list for layout output
		foreach ($items as $k => &$item)
		{
			list ($extension, $item->type, $item->extension_id) = explode('.', $item->assets_name);

			// Validate authorization
			$item->access = $this->validateAuthorizations($extension, $item->assets_name);

			if ($item->access !== false && !$isRoot)
			{
				if (!$changeState)
				{
					$item->access['core.edit.state'] = false;
				}

				if (!$linkItem)
				{
					$item->access['core.edit'] = false;
				}
			}

			$loadJs[$item->extension_id] = $item->access['core.edit.state'];

			// Remove item, if access is not allowed
			if ($item->access === false)
			{
				// Permission revoked, remove entry from database
				$toDeleteInDb[] = array(
					'user_id'     => Factory::getUser()->id,
					'assets_name' => $item->assets_name,
				);
			}

			if ($item->access === false
				|| ($item->access['core.edit.state'] === false && $item->access['core.edit'] === false)
				|| ($item->state == self::TRASH && $showTrashedItems === false))
			{
				unset($items[$k], $loadJs[$item->extension_id]);

				continue;
			}

			// Load extension language file, if the title is not individualized
			if (!empty($item->extension_name))
			{
				$this->loadExtensionLanguage($item->extension_name, $item->type, $item->client_id);
			}

			// Translate title
			$item->title = Text::_($item->title);

			// Distinction between page and administration extensions
			$item->client = $item->client_id ? 'administrator' : 'site';

			// Set param to show trashed items
			$item->access['show.trashed.items'] = $showTrashedItems;

			// Clean up the element for layout output
			unset(
				$item->client_id,
				$item->assets_name,
				$item->extension_name,
				$item->extension,
			);
		}

		// Delete entries from database
		if (!empty($toDeleteInDb))
		{
			$this->deleteDbEntry($toDeleteInDb);
		}

		if (count($items) < 1)
		{
			return array();
		}

		// Rearrange item list by type
		$items = ArrayHelper::pivot($items, 'type');

		// Rearrange type list by client
		foreach ($items as $k => $item)
		{

			// Equalization of the entries as array list
			if (!is_array($item))
			{
				$items[$k] = array($item);
			}

			$items[$k] = ArrayHelper::pivot($items[$k], 'client');

			foreach ($items[$k] as $client => $clientlist)
			{
				// Equalization of the entries as array list
				if (!is_array($clientlist))
				{
					$items[$k][$client] = array($clientlist);
				}

				$items[$k][$client] = ArrayHelper::sortObjects($items[$k][$client], 'title');
			}
		}

		$loadJs = array_values($loadJs);
		$loadJs = ArrayHelper::arrayUnique($loadJs);

		self::$loadJs = (count($loadJs) > 1 || $loadJs[0] === true);

		return $items;
	}

	/**
	 * Validate the authorization of the user for the extension
	 *
	 * @param   string  $extension   The extension (com_modules/com_plugins)
	 * @param   string  $assetsName  The asset to validate
	 *
	 * @return   array|bool  false if not allowed, or asrray with permissions
	 * @since    1.0.0
	 */
	private function validateAuthorizations($extension, $assetsName)
	{
		$neededPermissions = array(
			// Access to backend
			'core.login.admin',

			// Access to extension
			'core.manage',

			// Permission to edit
			'core.edit',

			// Permission to change state
			'core.edit.state',
		);

		$return     = array();
		$assetsName = $extension == 'com_plugins' ? $extension : $assetsName;

		foreach ($neededPermissions as $permission)
		{
			// Checking if user has the right permissions
			$return[$permission] = Factory::getUser()->authorise($permission, $assetsName);
		}

		if ($return['core.login.admin'] === false || $return['core.manage'] === false)
		{
			return false;
		}

		if ($return['core.edit'] === false && $return['core.edit.state'] === false)
		{
			return false;
		}

		unset($return['core.login.admin'], $return['core.manage']);

		return $return;
	}

	/**
	 * Load the extension language file
	 *
	 * @param   string  $extension  The extension (com_modules/com_plugins)
	 * @param   string  $type       The extension type (module/plugin)
	 * @param   int     $client_id  The client_id (0 = site / 1 = adminstration)
	 *
	 * @return   void
	 * @since    1.0.0
	 */
	private function loadExtensionLanguage($extension, $type, $client_id = 0)
	{
		$basePath = JPATH_SITE;

		$extensionPath = $type . 's/' . $extension;

		if ($type == 'plugin')
		{
			list($_, $folder, $element) = explode('_', $extension);

			$extensionPath = $type . 's/' . $folder . '/' . $element;
		}

		if ($client_id)
		{
			$basePath = JPATH_ADMINISTRATOR;
		}

		$path = $basePath . '/' . $extensionPath;

		$lang = Factory::getLanguage();
		$lang->load($extension, $path, null, true, false);
		$lang->load($extension . '.sys', $path, null, true, false);
	}

	/**
	 * Delete entries from database
	 *
	 * @param   array  $rows  The entries to delete from database
	 *
	 * @return   void
	 * @since    1.0.0
	 */
	private function deleteDbEntry($rows)
	{
		$db = Factory::getDbo();

		PluginHelper::importPlugin('system', 'jtfavorites');
		$dispatcher = JEventDispatcher::getInstance();

		foreach ($rows as $row)
		{
			$options                   = array();
			$options['where']['and'][] = $db->qn('user_id') . '=' . $db->q($row['user_id']);
			$options['where']['and'][] = $db->qn('assets_name') . '=' . $db->q($row['assets_name']);

			$dispatcher->trigger('deleteDbEntry', array($options));
		}
	}

	/**
	 * Load FileLayout renderer
	 *
	 * @param   string  $layout  Layout name
	 *
	 * @return   FileLayout
	 * @since    1.0.0
	 */
	public static function getLayoutRenderer($layout)
	{
		$pathParts = pathinfo(ModuleHelper::getLayoutPath('mod_jtfavorites', $layout));

		$layoutRenderer = new FileLayout($pathParts['filename']);

		$layoutRenderer->addIncludePath($pathParts['dirname']);

		return $layoutRenderer;
	}

	/**
	 * Check if the plugin is enabled
	 * Throws a notice, if not
	 *
	 * @return   bool
	 * @throws   \Exception
	 * @since    1.0.0
	 */
	public static function isEnabledPlugin()
	{
		$self      = new self;
		$isEnabled = PluginHelper::isEnabled('system', 'jtfavorites');

		if ($isEnabled)
		{
			return true;
		}

		// Load languagefiles
		$self->loadExtensionLanguage('plg_system_jtfavorites', 'plugin');

		$access   = Factory::getUser()->authorise('core.manage', 'com_plugins');
		$pluginId = $self->getPluginId();
		$plgTitle = Text::_('PLG_JTFAVORITES_XML_NAME');
		$msg      = Text::sprintf('MOD_JTFAVORITES_NOTICE_ENABLE_PLUGIN', $pluginId, $plgTitle);
		$msg      = $access ? $msg : strip_tags($msg, '<strong>');

		Factory::getApplication()->enqueueMessage($msg, 'notice');

		return false;
	}

	/**
	 * Get the plugin id from database
	 *
	 * @return   mixed|null
	 * @since    1.0.0
	 */
	private function getPluginId()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . '=' . $db->q('plugin'))
			->where($db->qn('element') . '=' . $db->q('jtfavorites'));
		$db->setQuery($query);

		return $db->loadResult();

	}
}
