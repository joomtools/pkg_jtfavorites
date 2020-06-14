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

use Joomla\CMS\Factory;

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
		$app = Factory::getApplication();
		$newManifest = $installer->get('manifest');

		if (version_compare(PHP_VERSION, $newManifest->php_minimum, 'lt'))
		{
			$app->enqueueMessage(Text::sprintf('Minimum PHP-Version: %s', $newManifest->php_minimum), 'error');

			return false;
		}

		return true;
	}
}
