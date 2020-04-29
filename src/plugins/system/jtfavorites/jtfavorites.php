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
use Joomla\Registry\Registry;

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
	 * List of allowed extensions to add to favorites
	 *
	 * @var     bool
	 * @since   __DEPLOY_VERSION__
	 */
	private $accessAllowed = false;

	/**
	 * List of allowed extensions to add to favorites
	 *
	 * @var     array
	 * @since   __DEPLOY_VERSION__
	 */
	private $allowedExtensions = array(
		// Backend
		'com_plugins.plugin',
		'com_modules.module',
		'com_modules.module.admin',

		// Frondend
		'com_config.modules',
	);

	/**
	 * List of needed permissions to add to favorites
	 *
	 * @var     array
	 * @since   __DEPLOY_VERSION__
	 */
	private $neededPermissions = array(
		// Access to backend
		'core.login.admin',

		// Access to extension
		'core.manage',

		// Permission to edit
		'core.edit',
	);

	public function onAfterRoute()
	{
		$_context = array();
		$input = $this->app->input;
		$_context[] = $input->getCmd('option');
		$view = $input->getCmd('view');
		$_task = $input->getCmd('task');
		$id = $input->getInt('id');

		if (!empty($task) && strpos('.', $_task))
		{
			list($view, $task) = explode('.', $_task);
		}

		if (!empty($task) && $task == 'cancel')
		{
			return;
		}

		if (!empty($view))
		{
			$_context[] = $view;
		}

		$context =  implode('.', $_context);

		$this->validateAccess($context, $id);

		// TODO delete entry in DB if $this->accessAllowed is false
	}

	/**
	 * Adding our fields before data validation to prevent the deletion of submitted values in backend
	 * Setting attribute disabled for our fields in order to prevent any changes in frontend
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return   void
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	public function onUserBeforeDataValidation($form, $data)
	{
		if (!$this->accessAllowed)
		{
			return;
		}

		if ($this->app->isClient('administrator'))
		{
			Form::addFormPath(dirname(__FILE__) . '/forms');
			$form->loadFile('jtfavorites');

			return;
		}

		$this->disableFields($form);
	}

	/**
	 * Adds additional fields to plugins/modules editing form to activate as favorite,
	 * in frontend as disabled
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return   boolean
	 *
	 * @throws   \Exception
	 * @since    __DEPLOY_VERSION__
	 */
	public function onContentPrepareForm($form, $data)
	{
		if (!$form instanceof Form)
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		if (!$this->accessAllowed)
		{
			return true;
		}

		Form::addFormPath(dirname(__FILE__) . '/forms');

		$oldXML = $form->getXml();
		$form->reset(true);
		$form->loadFile('jtfavorites');
		$form->load($oldXML);

		if ($this->app->isClient('site'))
		{
			$this->disableFields($form);
		}

		return true;
	}

	/**
	 * Setting attribute disabled for our fields in order to prevent any changes in frontend
	 *
	 * @param   Form  $form  The form to be altered.
	 *
	 * @return   void
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	private function disableFields($form)
	{
		$fields = $form->getFieldset('jtfavorites');

		foreach ($fields as $field)
		{
			$attribute = 'disabled';
			$value     = 'true';

			if ((string) $field->type == 'Note')
			{
				$attribute = 'type';
				$value     = 'hidden';
			}

			$form->setFieldAttribute((string) $field->fieldname, $attribute, $value, 'params');
		}

		$extraNote = new SimpleXMLElement('<field name="favorite_frontend_note" type="note" class="alert" description="PLG_SYSTEM_JTFAVORITES_FRONTEND_NOTE" ></field>');
		$form->setField($extraNote, 'params', false, 'jtfavorites');
	}

	/**
	 * Method is called when an extension is being saved
	 *
	 * @param   string   $context  The extension
	 * @param   JTable   $table    DataBase Table object
	 * @param   boolean  $isNew    If the extension is new or not
	 *
	 * @return   void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onExtensionAfterSave($context, $table, $isNew)
	{
		if (!$this->accessAllowed)
		{
			return;
		}

		$test = true;
		// Called after extension is saved successful
		// TODO update entry in table
	}

	public function onExtensionBeforeSave($context, $table, $isNew)
	{
		if (!$this->accessAllowed)
		{
			return;
		}

		$test = true;
		// Called after extension is saved successful
		// TODO update entry in table
	}

	/**
	 * Method is called when an extension is being deleted from trash
	 *
	 * @param   string  $context  The extension
	 * @param   JTable  $table    DataBase Table object
	 *
	 * @return   void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onExtensionAfterDelete($context, $table)
	{
		if (!$this->accessAllowed)
		{
			return;
		}

		$test = true;
		// Called after deleted in trash
		// TODO clear entry in table
	}

	/**
	 * We only allow users who has the right permission to set this setting for himself
	 *
	 * @param   string  $context  Form name
	 * @param   int     $id       Extension id
	 *
	 * @return   void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function validateAccess($context, $id)
	{
		$this->accessAllowed = true;

		if (!in_array($context, $this->allowedExtensions))
		{
			$this->accessAllowed = false;
		}

		if ($this->accessAllowed)
		{
			$this->accessAllowed = $this->validatePermissions($context . '.' . $id);
		}
	}

	/**
	 * Validate user permissions
	 *
	 * @param   string  $context  Form name
	 *
	 * @return   bool
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function validatePermissions($context)
	{
		if ($this->app->isClient('site') && $context == 'com_config.module')
		{
			$context = 'com_modules.module';
		}

		$user   = Factory::getUser();
		$return = true;

		foreach ($this->neededPermissions as $permission)
		{
			// Checking if user has the right permissions
			if (!$user->authorise($permission, $context))
			{
				$return = false;

				break;
			}
		}

		return $return;
	}
}