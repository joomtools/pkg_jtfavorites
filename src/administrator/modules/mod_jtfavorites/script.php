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
use Joomla\CMS\Language\Text;

/**
 * Script file of Joomla CMS
 *
 * @since   1.2.0
 */
class Mod_JtfavoritesInstallerScript
{
	/**
	 * Database object.
	 *
	 * @var     JDatabaseDriver
	 * @since   1.2.0
	 */
	protected $db;

	/**
	 * Previous version
	 *
	 * @var     string
	 * @since   1.2.0
	 */
	private $previousVersion;

	/**
	 * Function to act prior to installation process begins
	 *
	 * @param   string      $action     Which action is happening (install|uninstall|discover_install|update)
	 * @param   JInstaller  $installer  The class calling this method
	 *
	 * @return   boolean  True on success
	 * @since    1.2.0
	 */
	public function preflight($action, $installer)
	{
		$this->db = Factory::getDbo();

		if ($action == 'update')
		{
			$this->setPreviousVersion();
		}

		return true;
	}

	/**
	 * Set previous Version
	 *
	 * @return   void
	 * @since    1.2.0
	 */
	private function setPreviousVersion()
	{
		$db    = $this->db;
		$query = $db->getQuery(true);

		$query->select($db->qn('manifest_cache'))
			->from($db->qn('#__extensions'))
			->where($db->qn('element') . '=' . $db->q('mod_jtfavorites'));

		$result = $db->setQuery($query)->loadResult();

		if (!empty($result))
		{
			$result = json_decode($result);

			$this->previousVersion = $result->version;
		}
	}

	/**
	 * @param   string      $action     Which action is happening (install|uninstall|discover_install|update)
	 * @param   JInstaller  $installer  The class calling this method
	 *
	 * @return   boolean  True on success
	 * @since    1.2.0
	 */
	public function postflight($action, $installer)
	{
		if ($action == 'update')
		{
			if (version_compare($this->previousVersion, '1.1.2', 'lt'))
			{
				$this->updateManifestCache();
			}
		}

		return true;
	}

	/**
	 * Update manifest cache for existing modules
	 *
	 * @return   void
	 * @throws   Exception
	 * @since    1.2.0
	 */
	private function updateManifestCache()
	{
		$db        = $this->db;
		$newParams = json_decode('{"core_actions0":{"action_title":"MOD_MENU_CLEAR_CACHE","use_core_action":"1","action_option":"com_cache"},"core_actions1":{"action_title":"MOD_MENU_GLOBAL_CHECKIN","use_core_action":"1","action_option":"com_checkin"},"core_actions2":{"action_title":"MOD_JTFAVORITES_FIELDSET_CORE_ACTIONS_CLEAR_TRASH_CONTENT","use_core_action":"1","action_option":"clear_trash_content"},"core_actions3":{"action_title":"MOD_JTFAVORITES_FIELDSET_CORE_ACTIONS_CLEAR_TRASH_MENU","use_core_action":"0","action_option":"clear_trash_menu"},"core_actions4":{"action_title":"MOD_JTFAVORITES_FIELDSET_CORE_ACTIONS_CLEAR_TRASH_MODULES_SITE","use_core_action":"0","action_option":"clear_trash_modules_site"},"core_actions5":{"action_title":"MOD_JTFAVORITES_FIELDSET_CORE_ACTIONS_CLEAR_TRASH_MODULES_ADMIN","use_core_action":"0","action_option":"clear_trash_modules_admin"}}');

		$query = $db->getQuery(true);

		$query->select(array($db->qn('id'), $db->qn('params')))
			->from($db->qn('#__modules'))
			->where($db->qn('module') . '=' . $db->q('mod_jtfavorites'));

		$resultList = $db->setQuery($query)->loadObjectList();

		foreach ($resultList as $row)
		{
			$params = json_decode($row->params);
			$params->core_actions = (object) array_merge((array) $newParams, (array) $params->core_actions);
			$params = json_encode($params);

			$query = $db->getQuery(true);
			$query->update($db->qn('#__modules'))
				->set($db->qn('params') . '=' . $db->q($params))
				->where($db->qn('id') . '=' . $db->q($row->id));

			try
			{
				$db->setQuery($query)->execute();
			}
			catch (Exception $e)
			{
				Factory::getLanguage()->load('mod_jtfavorites', dirname(__FILE__));

				Factory::getApplication()->enqueueMessage(Text::_('MOD_JTFAVORITES_INSTALLER_SCRIPT_WARNING_DATABASE_UPDATE'), 'warning');
			}
		}
	}
}
