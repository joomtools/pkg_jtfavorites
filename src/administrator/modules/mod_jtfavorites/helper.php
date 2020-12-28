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
use Joomla\Filter\InputFilter;
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
	 * 
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
	 * Default order of groups
	 * 
	 * @var     array
	 * @since   1.1.0
	 */
	private $defaultGroupsOrder = array(
		'MOD_JTFAVORITES_VIEW_CUSTOMS_TITLE',
		'MOD_JTFAVORITES_VIEW_CORE_TITLE',
		'MOD_JTFAVORITES_VIEW_MODULES_TITLE_JSITE',
		'MOD_JTFAVORITES_VIEW_MODULES_TITLE_JADMINISTRATOR',
		'MOD_JTFAVORITES_VIEW_PLUGINS_TITLE',
	);

	/**
	 * List of groups titles
	 *
	 * @var     array
	 * @since   1.1.0
	 */
	private $groupsTitles = array(
		"custom" => 'MOD_JTFAVORITES_VIEW_CUSTOMS_TITLE',
		"core" => 'MOD_JTFAVORITES_VIEW_CORE_TITLE',
		"plugin" => 'MOD_JTFAVORITES_VIEW_PLUGINS_TITLE',
		"module" => array(
			'MOD_JTFAVORITES_VIEW_MODULES_TITLE_JSITE',
			'MOD_JTFAVORITES_VIEW_MODULES_TITLE_JADMINISTRATOR',
		),
	);

	/**
	 * List of links for core actions
	 *
	 * @var     array
	 * @since   1.1.0
	 */
	private $coreLinks = array(
		"com_cache" => 'index.php?option=com_cache&task=deleteAll&boxchecked=999',
		"com_checkin" => 'index.php?option=com_checkin&task=checkin&boxchecked=999',
		"clear_trash_menu" => 'index.php?option=com_ajax&task=menu.0&group=system&plugin=jtvaforitesClearTrash&format=json',
		"clear_trash_content" => 'index.php?option=com_ajax&task=content.0&group=system&plugin=jtvaforitesClearTrash&format=json',
		"clear_trash_modules_site" => 'index.php?option=com_ajax&task=modules.0&group=system&plugin=jtvaforitesClearTrash&format=json',
		"clear_trash_modules_admin" => 'index.php?option=com_ajax&task=modules.1&group=system&plugin=jtvaforitesClearTrash&format=json',
	);

	/**
	 * List of tables to use on global checkin
	 * @var     array
	 * @since   1.1.0
	 */
	private $globalChekinTables = array(
		"#__banners",
		"#__banner_clients",
		"#__categories",
		"#__contact_details",
		"#__content",
		"#__extensions",
		"#__fields",
		"#__fields_groups",
		"#__finder_filters",
		"#__menu",
		"#__modules",
		"#__tags",
		"#__ucm_content",
	);

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
		$changeState      = $params->get('allow_change_state');
		$linkItem         = $params->get('link_to_item');
		$showTrashedItems = $params->get('show_trashed_items');

		// Create item list for layout output
		foreach ($items as $i => &$item)
		{
			list ($extension, $item->type, $item->extension_id) = explode('.', $item->assets_name);

			// Validate authorization
			$item->access = $this->validateAuthorizations($extension, $item->assets_name);

			if ($item->access !== false)
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
				unset($items[$i], $loadJs[$item->extension_id]);

				continue;
			}

			// Load extension language file, if the title is not individualized
			if (!empty($item->extension_name))
			{
				$this->loadExtensionLanguage($item->extension_name, $item->type, $item->client_id);
			}

			// Translate title
			$item->title = Text::_($item->title);

			// Defins tab title
			$item->tab = $this->groupsTitles[$item->type];

			if ($item->type == 'module')
			{
				$item->tab = $this->groupsTitles[$item->type][$item->client_id];
			}

			// Set param to show trashed items
			$item->access['show.trashed.items'] = $showTrashedItems;

			// Clean up the element for layout output
			unset(
				$item->client_id,
				$item->assets_name,
				$item->extension_name,
				$item->extension
			);
		}

		// Delete entries from database
		if (!empty($toDeleteInDb))
		{
			$this->deleteDbEntry($toDeleteInDb);
		}

		if ($params->get('use_custom_actions'))
		{
			// Custom actions
			$customActions = (array) $params->get('custom_actions');
			$customActions = $this->getCustomAndCoreActions($customActions, 'custom');

			if (!empty($customActions))
			{
				$items          = array_merge($items, $customActions);
				$loadJs['custom'] = true;
			}
		}

		if ($params->get('use_core_actions'))
		{
			$this->loadExtensionLanguage('mod_menu', 'module',1);

			// Core actions
			$coreActions = (array) $params->get('core_actions');
			$coreActions = $this->getCustomAndCoreActions($coreActions, 'core');

			if (!empty($coreActions))
			{
				$items          = array_merge($items, $coreActions);
				$loadJs['core'] = true;
			}
		}

		if (!count($items))
		{
			return array();
		}

		// Rearrange item list by type
		$items = ArrayHelper::pivot($items, 'tab');

		if ($groupsOrder = $params->get('sort_groups', null))
		{
			$groupsOrder = ArrayHelper::fromObject($groupsOrder);
			$groupsOrder = ArrayHelper::pivot($groupsOrder, 'group_title');
			$groupsOrder = array_keys($groupsOrder);
		}

		if (empty($groupsOrder))
		{
			$groupsOrder = $this->defaultGroupsOrder;
		}

		$items = $this->sortItems($groupsOrder, $items);

		// Rearrange type list by client
		foreach ($items as $tab => $itemlist)
		{
			if (in_array($tab, array('MOD_JTFAVORITES_VIEW_CUSTOMS_TITLE', 'MOD_JTFAVORITES_VIEW_CORE_TITLE'))
				|| count($itemlist) <= 1)
			{
				continue;
			}

			$items[$tab] = ArrayHelper::sortObjects($itemlist, 'title');
		}

		$loadJs = array_values($loadJs);
		$loadJs = ArrayHelper::arrayUnique($loadJs);

		self::$loadJs = (count($loadJs) && $loadJs[0] === true);

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
		$extensionPath = $type . 's/' . $extension;

		if ($type == 'plugin')
		{
			list($_, $folder, $element) = explode('_', $extension);

			$extensionPath = $type . 's/' . $folder . '/' . $element;
		}

		$basePath = JPATH_SITE;

		if ($client_id)
		{
			$basePath = JPATH_ADMINISTRATOR;
		}

		$path = $basePath . '/' . $extensionPath;

		$lang = Factory::getLanguage();
		$lang->load($extension, JPATH_SITE);
		$lang->load($extension . '.sys', JPATH_SITE);
		$lang->load($extension, JPATH_ADMINISTRATOR);
		$lang->load($extension . '.sys', JPATH_ADMINISTRATOR);
		$lang->load($extension, $path);
		$lang->load($extension . '.sys', $path);
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

	/**
	 * Get prepared items for output
	 *
	 * @param   array   $customActions
	 * @param   string  $type
	 *
	 * @return   array
	 * @since    1.0.0
	 */
	private function getCustomAndCoreActions($customActions, $type)
	{
		$filter = new InputFilter;
		$items  = array();

		foreach ($customActions as $customAction)
		{
			if (($type == 'custom' && (empty($customAction->action_title) || empty($customAction->action_link)))
				|| ($type == 'core' && (empty($customAction->use_core_action) || empty($customAction->action_title) || empty($customAction->action_option))))
			{
				continue;
			}

			$customItem                      = new stdClass;
			$customItem->type                = $type;
			$customItem->tab                 = $this->groupsTitles[$type];;
			$customItem->title               = Text::_($customAction->action_title);
			$customItem->link                = $type == 'core' ? $this->getCoreLink($customAction) : $filter->clean($customAction->action_link);
			$customItem->target              = !empty($customAction->action_link_target) ? $customAction->action_link_target : null;
			$customItem->access['core.edit'] = true;

			$items[] = $customItem;
		}

		return $items;
	}

	/**
	 * Get core link for action
	 *
	 * @param   object  $item
	 *
	 * @return   string  Url for core action
	 * @since    1.1.0
	 */
	private function getCoreLink($item)
	{
		$link = $this->coreLinks[$item->action_option];

		if ($item->action_option == 'com_checkin')
		{
			$tablePrefix  = Factory::getConfig()->get('dbprefix');
			$options      = $this->globalChekinTables;
			$customTables = explode(',', $item->action_checkin_tables);
			$customTables = array_filter($customTables);;

			if (!empty($customTables))
			{
				$options = array_merge($options, $customTables);
			}

			$link .= '&cid[]=' . implode('&cid[]=', $options);
			$link = str_replace('#__', $tablePrefix, $link);
		}

		return $link;
	}

	/**
	 * Sort items by order
	 * @param   array  $order  List of keys to sort by
	 * @param   array  $items
	 *
	 * @return   array
	 * @since    1.1.0
	 */
	private function sortItems(array $order, array $items)
	{
		$sortedItems = array();

		foreach ($order as $type)
		{
			if (!empty($items[$type]))
			{
				$sortedItems[$type] = !is_array($items[$type]) ? array($items[$type]) : $items[$type];
			}
		}

		return $sortedItems;
}
}
