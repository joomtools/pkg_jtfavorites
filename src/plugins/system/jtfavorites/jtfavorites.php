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

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

/**
 * System plugin to manage a list of favorites for plugin/module action.
 *
 * @since  1.0.0
 */
class PlgSystemJtfavorites extends CMSPlugin
{
	/**
	 * Define integer for the state 'trash'
	 *
	 * @var     int
	 * @since   1.2.0
	 */
	const TRASH = -2;

	/**
	 * @var     JApplicationCms
	 * @since   1.0.0
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var     JDatabaseDriver
	 * @since   1.0.0
	 */
	protected $db;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var     bool
	 * @since   1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * List of allowed extensions to add to favorites
	 *
	 * @var     string
	 * @since   1.0.0
	 */
	private $assetsName;

	/**
	 * List of allowed extensions to add to favorites
	 *
	 * @var     bool
	 * @since   1.0.0
	 */
	private $accessAllowed;

	/**
	 * List of allowed extensions to add to favorites
	 *
	 * @var     array
	 * @since   1.0.0
	 */
	private $allowedExtensions = array(
		// Plugins
		'com_plugins.plugin',

		// Site module
		'com_modules.module',

		// Administrator module
		'com_modules.module.admin',
	);

	/**
	 * List of needed permissions to add to favorites
	 *
	 * @var     array
	 * @since   1.0.0
	 */
	private $neededPermissions = array(
		// Access to backend
		'core.login.admin',

		// Access to extension
		'core.manage',

		// Permission to edit
		'core.edit',
	);

	/**
	 * Set the values in extensions for favorite options.
	 *
	 * @param   string  $context  The context for the data
	 * @param   object  $data     An object containing the data for the form.
	 *
	 * @return   bool
	 * @since    1.0.0
	 */
	public function onContentPrepareData($context, $data)
	{
		if (!$this->validateAccess($context))
		{
			return true;
		}

		if (!empty($data))
		{
			$userParams    = array();
			$defaultParams = array(
				'add_to_favorites' => '0',
				'favorite_title'   => '',
			);

			$dataParams         = $data->getProperties();
			$options            = array();
			$options['where']['and'][] = $this->db->qn('user_id') . '=' . (int) Factory::getUser()->id;
			$options['where']['and'][] = $this->db->qn('assets_name') . '=' . $this->db->q($this->assetsName);

			// Get name from database, if entry exists
			$favoriteTitle = $this->findInDb($options);

			if (!is_null($favoriteTitle))
			{
				$userParams = array(
					'add_to_favorites' => '1',
					'favorite_title'   => $favoriteTitle,
				);
			}

			$params = array_merge($dataParams, $defaultParams, $userParams);

			$data->setProperties($params);
		}

		return true;
	}

	/**
	 * Adds additional fields to plugins/modules editing form to activate as favorite,
	 * in frontend as disabled
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return   bool
	 * @throws   \Exception
	 * @since    1.0.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		if (!$this->validateAccess($form->getName()))
		{
			return true;
		}

		Form::addFormPath(dirname(__FILE__) . '/forms');

		$oldXML = $form->getXml();
		$form->reset(true);
		$form->loadFile('jtfavorites');
		$form->load($oldXML);

		return true;
	}

	/**
	 * Change the state in database if the state in a table is changed
	 *
	 * An authorization check is not necessary because Joomla! blocks the action before this event is triggered.
	 *
	 * @param   string   $context  The context for the extension passed to the plugin.
	 * @param   array    $pks      A list of primary key ids of the extensions that has changed state.
	 * @param   int      $state    The value of the state that the extensions has been changed to.
	 *
	 * @return   void
	 * @since    1.0.0
	 */
	public function onContentChangeState($context, $pks, $state)
	{
		$options = array();

		foreach ($pks as $extensionId)
		{
			$assetName                = (string) $context . '.' . $extensionId;
			$search                   = array();
			$search['where']['and'][] = $this->db->qn('assets_name') . '=' . $this->db->q($assetName);

			// Get name from database, if entry exists
			$favoriteTitle = $this->findInDb($search);

			if (is_null($favoriteTitle))
			{
				continue;
			}

			$options['where']['or'][] = $this->db->qn('assets_name') . '=' . $this->db->q($assetName);
		}

		if (!empty($options))
		{
			$options['set'][] = $this->db->qn('state') . '=' . (int) $state;

			$this->updateDbEntry($options);
		}

		return;
	}

	/**
	 * Method is called when an extension is being saved
	 *
	 * @param   string   $context  The extension
	 * @param   JTable   $table    DataBase Table object
	 * @param   bool     $isNew    If the extension is new or not
	 *
	 * @return   void
	 * @since    1.0.0
	 */
	public function onExtensionAfterSave($context, $table, $isNew)
	{
		if (!$this->validateAccess($context))
		{
			return;
		}

		if ($isNew)
		{
			$this->assetsName = (string) $context . '.' . $table->get('id');
		}

		$params                   = $this->app->input->get('jform', array(), 'array');
		$options                  = array();
		$search                   = array();
		$search['where']['and'][] = $this->db->qn('user_id') . '=' . (int) Factory::getUser()->id;
		$search['where']['and'][] = $this->db->qn('assets_name') . '=' . $this->db->q($this->assetsName);

		// Get name from database, if entry exists
		$favoriteTitle = $this->findInDb($search);

		if (!is_null($favoriteTitle) && (int) $params['add_to_favorites'] === 0)
		{
			$this->deleteDbEntry($search);

			return;
		}

		if (is_null($favoriteTitle) && (int) $params['add_to_favorites'] === 0)
		{
			return;
		}

		$options['set']   = $search['where']['and'];
		$options['set'][] = $this->db->qn('client_id') . '=' . (int) $table->get('client_id');
		$options['set'][] = $this->db->qn('favorite_title') . '=' . $this->db->q((string) strip_tags($params['favorite_title']));

		switch (true)
		{
			case 'com_plugins.plugin' == $context :
				$options['set'][] = $this->db->qn('state') . '=' . (int) $params['enabled'];
				break;

			default:
				$options['set'][] = $this->db->qn('state') . '=' . (int) $params['published'];
				break;
		}

		if (is_null($favoriteTitle))
		{
			$this->insertDbEntry($options);

			return;
		}

		$options['where'] = $search['where'];

		$this->updateDbEntry($options);

		return;
	}

	/**
	 * Method is called when an extension is being deleted from trash
	 *
	 * @param   string  $context  The extension
	 * @param   JTable  $table    DataBase Table object
	 *
	 * @return   void
	 * @since    1.0.0
	 */
	public function onExtensionAfterDelete($context, $table)
	{
		$assetsName = $context . '.' . (int) $table->get('id');

		$options                  = array();
		$options['where']['or'][] = $this->db->qn('assets_name') . '=' . $this->db->q($assetsName);

		// Delete all deleted extensions entries from database
		$favoriteTitle = $this->deleteDbEntry($options);
	}

	/**
	 * On uninstalling extensions logging method
	 * This method adds a record to #__action_logs contains (message, date, context, user)
	 * Method is called when an extension is uninstalled
	 *
	 * @param   JInstaller  $installer  Installer instance
	 * @param   int         $eid        Extension id
	 * @param   int         $result     Installation result
	 *
	 * @return   void
	 * @since    1.0.0
	 */
	public function onExtensionAfterUninstall($installer, $eid, $result)
	{
		if (!$result)
		{
			return;
		}

		$options                  = array();
		$options['where']['or'][] = $this->db->qn('assets_name') . ' LIKE ' . $this->db->q('%.' . (int) $eid);

		// Delete all uninstalled extensions entries from database
		$this->deleteDbEntry($options);

		return;
	}

	/**
	 * We only allow users who has the right permission to set this setting for himself
	 *
	 * @param   string  $context  The extension
	 *
	 * @return   bool
	 * @since    1.0.0
	 */
	private function validateAccess($context)
	{
		if (!is_null($this->accessAllowed))
		{
			return $this->accessAllowed;
		}

		if ($this->app->isClient('site'))
		{
			$this->accessAllowed = false;

			return false;
		}

		if (!in_array($context, $this->allowedExtensions))
		{
			$this->accessAllowed = false;

			return false;
		}

		switch (true)
		{
			case !is_null($this->app->input->get('id', null)) :
				$extensionId = $this->app->input->get('id');
				break;

			case !is_null($this->app->input->get('extension_id', null)) :
				$extensionId = $this->app->input->get('extension_id');
				break;

			default :
				$extensionId = null;
				break;
		}

		if ($context == 'com_modules.module.admin')
		{
			$context = 'com_modules.module';
		}

		if (!is_null($extensionId))
		{
			$context = (string) $context . '.' . $extensionId;
		}

		$this->assetsName    = $context;
		$this->accessAllowed = $this->validateAuthorizations();

		return $this->accessAllowed;
	}

	/**
	 * Validate user permissions
	 *
	 * @return   bool
	 * @since    1.0.0
	 */
	private function validateAuthorizations()
	{
		list($extension, $_) = explode('.', $this->assetsName, 2);

		$return     = true;
		$assetsName = $extension == 'com_plugins' ? $extension : $this->assetsName;

		foreach ($this->neededPermissions as $permission)
		{
			// Checking if user has the right permissions
			if (!Factory::getUser()->authorise($permission, $assetsName))
			{
				$return = false;

				break;
			}
		}

		return $return;
	}

	/**
	 * Search entry in database
	 *
	 * @param   array  $options  The options to create the condition.
	 *
	 * @return   bool
	 * @since    1.0.0
	 */
	private function findInDb($options)
	{
		$query = $this->db->getQuery(true);

		$query->select($this->db->qn('favorite_title'))
			->from($this->db->qn('#__jtfavorites'));

		foreach ($options['where'] as $k => $where)
		{
			$query->where($where, $k);
		}

		return $this->db->setQuery($query)->loadResult();
	}

	/**
	 * Delete entry from database
	 *
	 * @param   array  $options  The options to create the condition.
	 *
	 * @return   bool
	 * @since    1.0.0
	 */
	public function deleteDbEntry($options)
	{
		$query = $this->db->getQuery(true);

		$query->delete($this->db->qn('#__jtfavorites'));

		foreach ($options['where'] as $k => $where)
		{
			$query->where($where, $k);
		}

		return $this->db->setQuery($query)->execute();
	}

	/**
	 * Update entry in database
	 *
	 * @param   array  $options  The options to create the condition.
	 *
	 * @return   bool
	 * @since    1.0.0
	 */
	private function insertDbEntry($options)
	{
		$query = $this->db->getQuery(true);

		$query->insert($this->db->qn('#__jtfavorites'))
			->set($options['set']);

		return $this->db->setQuery($query)->execute();
	}

	/**
	 * Update entry in database
	 *
	 * @param   array  $options  The options to create the condition.
	 *
	 * @return   void
	 * @since    1.0.0
	 */
	private function updateDbEntry($options)
	{
		$query = $this->db->getQuery(true);

		$query->update($this->db->qn('#__jtfavorites'))
			->set($options['set']);

		foreach ($options['where'] as $k => $where)
		{
			$query->where($where, $k);
		}

		$this->db->setQuery($query)->execute();

		return;
	}

	/**
	 * Check if current user is allowed to send the data
	 *
	 * @return   bool
	 * @since    1.2.0
	 */
	private function isAllowedUser()
	{
		return JFactory::getUser()->authorise('core.admin');
	}

	/**
	 * Check valid AJAX request
	 *
	 * @return   bool
	 * @since    1.2.0
	 */
	private function isAjaxRequest()
	{
		return strtolower($this->app->input->server->get('HTTP_X_REQUESTED_WITH', '')) === 'xmlhttprequest';
	}

	/**
	 * Ajax methode
	 *
	 * @return   void
	 * @throws   Exception
	 * @since    1.2.0
	 */
	public function onAjaxJtvaforitesClearTrash()
	{
		$accessDenied = Text::_('JGLOBAL_AUTH_ACCESS_DENIED');

		if (!$this->app->getSession()->checkToken())
		{
			throw new Exception(Text::sprintf('PLG_SYSTEM_JTFAVORITES_CLEAR_TRASH_ERROR_TOKEN', $accessDenied), 403);
		}

		if (!$this->isAllowedUser())
		{
			throw new Exception(Text::sprintf('PLG_SYSTEM_JTFAVORITES_CLEAR_TRASH_ERROR_VALID_USER', $accessDenied), 403);
		}

		if (!$this->isAjaxRequest())
		{
			throw new Exception(Text::sprintf('PLG_SYSTEM_JTFAVORITES_CLEAR_TRASH_ERROR_AJAX_REQUEST', $accessDenied), 403);
		}

		if (!empty($task = $this->app->input->getString('task')))
		{
			list($trash, $clientId) = explode('.', $task);

			switch (true)
			{
				case $trash == 'content':
					$this->clearContentTrash((int) $clientId);
					break;

				case $trash == 'menu':
					$ids = $this->getTrashedItems($trash, $clientId);

					if (!empty($ids))
					{
						Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_menus/tables', 'MenusTable');
						BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_menus/models', 'MenusModel');
						$model = BaseDatabaseModel::getInstance('Item', 'MenusModel');
						$model->delete($ids);
					}
					break;

				case $trash == 'modules':
					$ids = $this->getTrashedItems($trash, $clientId);

					if (!empty($ids))
					{
						BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_modules/models', 'ModulesModel');
						$model = BaseDatabaseModel::getInstance('Module', 'ModulesModel');
						$model->delete($ids);
					}
					break;

				default:
					break;
			}
		}
	}

	/**
	 * Clear content trashes items
	 *
	 * @param    int  $clientId
	 *
	 * @return   void
	 * @throws   Exception
	 * @since    1.2.0
	 */
	private function clearContentTrash($clientId)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_content/models', 'ContentModel');
		$model = BaseDatabaseModel::getInstance('Article', 'ContentModel');

		$query = $this->db->getQuery(true)
			->select($this->db->qn('id'))
			->from($this->db->qn('#__content'))
			->where($this->db->qn('state') . '=' . $this->db->q((int) self::TRASH));
		try
		{
			$ids = $this->db->setQuery($query)->loadAssocList('id');
		}
		catch (Exception $e)
		{
			throw new Exception(Text::_('PLG_SYSTEM_JTFAVORITES_CLEAR_TRASH_ERROR_DB'), 500);
		}

		$ids = array_keys($ids);

		$model->delete($ids);
	}

	/**
	 * Get trashed items
	 *
	 * @param    string  $trash
	 * @param    int     $clientId
	 *
	 * @return   array
	 * @throws   Exception
	 * @since    1.2.0
	 */
	private function getTrashedItems($trash, $clientId)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->qn('id'))
			->from($this->db->qn('#__' . $trash))
			->where($this->db->qn('published') . '=' . $this->db->q((int) self::TRASH));
		try
		{
			$ids = $this->db->setQuery($query)->loadAssocList('id');
		}
		catch (Exception $e)
		{
			throw new Exception(Text::_('PLG_SYSTEM_JTFAVORITES_CLEAR_TRASH_ERROR_DB'), 500);
		}

		return array_keys($ids);
	}
}