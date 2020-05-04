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

/**
 * Helper for mod_jtfavorites
 *
 * @since   __DEPLOY_VERSION__
 */
abstract class ModJtFavoritesHelper
{
	public static $row = 0;

	/**
	 * Get a list of articles.
	 *
	 * @return   mixed  An array of entries, or false on error.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getList()
	{
		$db     = Factory::getDbo();
		$userId = (int) Factory::getUser()->get('id');

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
			->where($db->qn('jtf.user_id') . '=' . $userId);

		return $db->setQuery($query)->loadObjectList();
	}

	/**
	 * Validate the authorization of the user for the extension
	 *
	 * @param   string  $extension   The extension (com_modules/com_plugins)
	 * @param   string  $assetsName  The asset to validate
	 *
	 * @return   array|bool  false if not allowed, or asrray with permissions
	 * @since    __DEPLOY_VERSION__
	 */
	public static function validateAuthorizations($extension, $assetsName)
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
	 * Load FileLayout renderer
	 *
	 * @param   string  $layout  Layout name
	 *
	 * @return   FileLayout
	 * @since    __DEPLOY_VERSION__
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
	 * @since    __DEPLOY_VERSION__
	 */
	public static function isEnabledPlugin()
	{
		$isEnabled = PluginHelper::isEnabled('system', 'jtfavorites');

		if ($isEnabled)
		{
			return true;
		}

		// Load languagefiles
		self::loadExtensionLanguage('plg_system_jtfavorites', 'plugin');

		$access   = Factory::getUser()->authorise('core.manage', 'com_plugins');
		$pluginId = self::getPluginId();
		$plgTitle = Text::_('PLG_JTFAVORITES_XML_NAME');
		$msg      = Text::sprintf('MOD_JTFAVORITES_NOTICE_ENABLE_PLUGIN', $pluginId, $plgTitle);
		$msg      = $access ? $msg : strip_tags($msg, '<strong>');

		Factory::getApplication()->enqueueMessage($msg, 'notice');

		return false;
	}

	/**
	 * Load the extension language file
	 *
	 * @param   string  $extension  The extension (com_modules/com_plugins)
	 * @param   string  $type       The extension type (module/plugin)
	 * @param   int     $client_id  The client_id (0 = site / 1 = adminstration)
	 *
	 * @return   void
	 * @since    __DEPLOY_VERSION__
	 */
	public static function loadExtensionLanguage($extension, $type, $client_id = 0)
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
	 * Get the plugin id from database
	 *
	 * @return   mixed|null
	 * @since    __DEPLOY_VERSION__
	 */
	protected static function getPluginId()
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
	 * Delete entries from database
	 *
	 * @param   array  $rows  The entries to delete from database
	 *
	 * @return   void
	 * @since    __DEPLOY_VERSION__
	 */
	public static function deleteDbEntry($rows)
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
}
