<?php
/**
 * @package      Joomla.Administrator
 * @subpackage   pkg_jtfavorites
 *
 * @author       Guido De Gobbis <support@joomtools.de>
 * @copyright    2020 JoomTools.de - All rights reserved.
 * @license      GNU General Public License version 3 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\PackageAdapter;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;

/**
 * Script file of Joomla CMS
 *
 * @since   1.2.0
 */
class Pkg_JtfavoritesInstallerScript
{
	/**
	 * Previous version
	 *
	 * @var   string
	 *
	 * @since  1.2.0
	 */
	private $previousVersion;

	/**
	 * A list of extensions (modules, plugins) to enable after installation. Each item has four values, in this order:
	 * type (plugin, module, ...), name (of the extension), client (0=site, 1=admin), group (for plugins).
	 *
	 * These extensions are ONLY enabled when you do a clean installation of the package, i.e. it will NOT run on update
	 *
	 * @var   array
	 *
	 * @since  1.2.1
	 */
	private $extensionsToEnable = array(
		array('plugin', 'jtfavorites', 0, 'system'),
	);

	/**
	 * Function to act prior to installation process begins
	 *
	 * @param   string      $action     Which action is happening (install|uninstall|discover_install|update)
	 * @param   Installer   $installer  The class calling this method
	 *
	 * @return  boolean     True on success
	 * @throws  \Exception
	 *
	 * @since  1.2.0
	 */
	public function preflight($action, $installer)
	{
		$app = Factory::getApplication();
		$newManifest = $installer->get('manifest');

		if (version_compare(PHP_VERSION, $newManifest->php_minimum, 'lt'))
		{
			$app->enqueueMessage(Text::sprintf('Minimum PHP-Version: %s', $newManifest->php_minimum), 'error');

			return false;
		}

		return true;
	}

	/**
	 * Runs on installation (but not on upgrade). This happens in install and discover_install installation routes.
	 *
	 * @param   PackageAdapter  $parent  Parent object
	 *
	 * @return  boolean
	 *
	 * @since   1.2.1
	 */
	public function install($parent)
	{
		// Enable the extensions we need to install
		$this->enableExtensions();

		return true;
	}

	/**
	 * Enable modules and plugins after installing them
	 *
	 * @param   array[]  $extensions  Array with extensions
	 *
	 * @return  void
	 * @see     $extensionsToEnable
	 *
	 * @since  1.2.1
	 */
	private function enableExtensions($extensions = array())
	{
		if (empty($extensions))
		{
			$extensions = $this->extensionsToEnable;
		}

		foreach ($extensions as $ext)
		{
			$this->enableExtension($ext[0], $ext[1], $ext[2], $ext[3]);
		}
	}

	/**
	 * Enable an extension
	 *
	 * @param   string   $type    The extension type.
	 * @param   string   $name    The name of the extension (the element field).
	 * @param   integer  $client  The application id (0: Joomla CMS site; 1: Joomla CMS administrator).
	 * @param   string   $group   The extension group (for plugins).
	 *
	 * @return  void
	 *
	 * @since  1.2.1
	 */
	private function enableExtension($type, $name, $client = 1, $group = null)
	{
		try
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->update($db->quoteName('#__extensions'))
				->set($db->quoteName('enabled') . ' = ' . $db->quote(1))
				->where('type = ' . $db->quote($type))
				->where('element = ' . $db->quote($name));
		}
		catch (\Exception $e)
		{
			return;
		}


		switch ($type)
		{
			case 'plugin':
				// Plugins have a folder but not a client
				$query->where('folder = ' . $db->quote($group));
				break;

			case 'language':
			case 'module':
			case 'template':
				// Languages, modules and templates have a client but not a folder
				$client = ApplicationHelper::getClientInfo($client, true);
				$query->where('client_id = ' . (int) $client->id);
				break;

			default:
			case 'library':
			case 'package':
			case 'component':
				// Components, packages and libraries don't have a folder or client.
				// Included for completeness.
				break;
		}

		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (\Exception $e)
		{
		}
	}
}
