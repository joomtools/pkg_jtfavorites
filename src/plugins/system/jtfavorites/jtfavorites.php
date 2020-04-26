<?php
/**
 * @package      Joomla.Plugin
 * @subpackage   System.Jtfavorites
 *
 * @author       Guido De Gobbis <support@joomtools.de>
 * @copyright    2020 JoomTools.de - All rights reserved.
 * @license      GNU General Public License version 3 or later
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;

/**
 * System plugin to manage a list of favorites for plugin/module action.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgSystemJtfavorites extends CMSPlugin
{
	/**
	 * @var     JApplicationCms
	 * @since   __DEPLOY_VERSION__
	 */
	protected $app = null;

	/**
	 * Database object.
	 *
	 * @var     JDatabaseDriver
	 * @since   __DEPLOY_VERSION__
	 */
	protected $db;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var     boolean
	 * @since   __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Adds additional fields to plugins/modules editing form to activate as favorite
	 *
	 * @param Form  $form The form to be altered.
	 * @param mixed $data The associated data for the form.
	 *
	 * @return   boolean
	 *
	 * @throws   \Exception
	 * @since    __DEPLOY_VERSION__
	 */
	public function onContentPrepareForm($form, $data)
	{

		if ($this->app->isClient('administrator') && !isset($data->params))
		{
			return true;
		}

		if (!$form instanceof Form)
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		$formName = $form->getName();

		$allowedFormNames = array(
			// Backend
			'com_plugins.plugin',
			'com_modules.module',
			'com_modules.module.admin',

			// Frondend
			'com_config.modules',
		);

		if (!in_array($formName, $allowedFormNames))
		{
			return true;
		}

		/**
		 * We only allow users who has Super User permission change this setting for himself or for other users
		 * who has same Super User permission
		 */

		$user = Factory::getUser();

		if (!$user->authorise('core.edit'))
		{
			return true;
		}

		// If we are on the save command, no data is passed to $data variable, we need to get it directly from request
		$jformData = $this->app->input->get('jform', array(), 'array');

		if ($jformData && !$data)
		{
			$data = $jformData;
		}

		if (is_array($data))
		{
			$data = (object) $data;
		}

		Form::addFormPath(dirname(__FILE__) . '/forms');

		$oldXML = $form->getXml();
		$form->reset(true);

		$xmlFile = 'jtfavorites';

		if ($this->app->isClient('site'))
		{
			$xmlFile = 'jtfavorites.fe';
		}

		$form->loadFile($xmlFile);
		$form->load($oldXML);

		return true;
	}

	/**
	 * Method is called when an extension is being saved
	 *
	 * @param string  $context The extension
	 * @param JTable  $table   DataBase Table object
	 * @param boolean $isNew   If the extension is new or not
	 * @param array   $params  Extension params
	 *
	 * @return   void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onExtensionAfterSave($context, $table, $isNew)
	{
		$test = true;
		// Called after extension is saved successful
		// TODO update entry in table
	}

	public function onUserBeforeDataValidation($form, $data)
	{
		Form::addFormPath(dirname(__FILE__) . '/forms');
		$xmlFile = 'jtfavorites';

		if ($this->app->isClient('site'))
		{
			$xmlFile = 'jtfavorites.fe';
		}

		$form->loadFile($xmlFile);
		$form->bind($data);
		$test = true;
		// Called after extension is saved successful
		// TODO update entry in table
	}

	public function onExtensionBeforeSave($context, $table, $isNew)
	{
		$test = true;
		// Called after extension is saved successful
		// TODO update entry in table
	}

	/**
	 * Method is called when an extension is being deleted from trash
	 *
	 * @param string $context The extension
	 * @param JTable $table   DataBase Table object
	 *
	 * @return   void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onExtensionAfterDelete($context, $table)
	{
		$test = true;
		// Called after deleted in trash
		// TODO clear entry in table
	}
}