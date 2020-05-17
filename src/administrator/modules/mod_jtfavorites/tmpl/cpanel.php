<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_custom
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

extract($displayData);

/**
 * @var   FileLayout  $this
 * @var   array       $customs          List of favorites custom actions
 * @var   array       $modules          List of favorites modules
 * @var   array       $plugins          List of favorites plugins
 * @var   string      $task             Form id for this position using as clickaction too
 * @var   string      $view             View for items output (tabbed/list)
 * @var   string      $moduleclass_sfx  Module class suffix
 */

switch (true)
{
	case !is_null($customs) :
		$activeTab = 'cpanelcustomsactions';
		break;

	case !is_null($modules) && isset($modules['JADMINISTRATOR']) :
		$activeTab = 'cpanelmodulesjadministrator';
		break;

	case !is_null($modules) && isset($modules['JSITE']) :
		$activeTab = 'cpanelmodulesjsite';
		break;

	case !is_null($plugins) :
		$activeTab = 'cpanelpluginsjsite';
		break;

	default :
		break;
} ?>
<!-- Start mod_jtfavorites.cpanel -->
<div class="mod_jtfavorites cpanel<?php echo $moduleclass_sfx; ?>">
	<form method="post" name="<?php echo $task; ?>" id="<?php echo $task; ?>"
		  data-modules-action="<?php echo Route::_('index.php?option=com_modules'); ?>"
		  data-plugins-action="<?php echo Route::_('index.php?option=com_plugins&view=plugins'); ?>"
	>
		<?php if ($view == 'tabbed') : ?>
			<?php echo HTMLHelper::_('bootstrap.startTabSet', 'cpanelListFavorites', array('active' => $activeTab)); ?>
		<?php endif; ?>
		<?php if (!is_null($customs)) : ?>
			<?php $sublayout = array(
				'title' => Text::_('MOD_JTFAVORITES_VIEW_CUSTOMS_TITLE'),
				'type'  => 'customs',
				'items' => $customs,
				'task'  => $task,
				'view'  => $view,
			); ?>
			<?php echo $this->sublayout('items', $sublayout); ?>
		<?php endif; ?>
		<?php if (!is_null($modules)) : ?>
			<?php $sublayout = array(
				'title' => Text::_('MOD_JTFAVORITES_VIEW_MODULES_TITLE'),
				'type'  => 'modules',
				'items' => $modules,
				'task'  => $task,
				'view'  => $view,
			); ?>
			<?php echo $this->sublayout('items', $sublayout); ?>
		<?php endif; ?>
		<?php if (!is_null($plugins)) : ?>
			<?php $sublayout = array(
				'title' => Text::_('MOD_JTFAVORITES_VIEW_PLUGINS_TITLE'),
				'type'  => 'plugins',
				'items' => $plugins,
				'task'  => $task,
				'view'  => $view,
			); ?>
			<?php echo $this->sublayout('items', $sublayout); ?>
		<?php endif; ?>
		<?php if ($view == 'tabbed') : ?>
			<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
		<?php endif; ?>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
<!-- End mod_jtfavorites.cpanel -->
