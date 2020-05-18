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
 * @var   array       $items            List of ordered favorites
 * @var   string      $task             Form id for this position using as clickaction too
 * @var   string      $view             View for items output (tabbed/list)
 * @var   string      $moduleclass_sfx  Module class suffix
 */

$tabAction = array(
	'MOD_JTFAVORITES_VIEW_CUSTOMS_TITLE' => array(
		'tabId' => 'cpanelcustomsactions',
		'type' => 'customs',
	),
	'MOD_JTFAVORITES_VIEW_CORE_TITLE' => array(
		'tabId' => 'cpanelcoreactions',
		'type' => 'core',
	),
	'MOD_JTFAVORITES_VIEW_MODULES_TITLE_JADMINISTRATOR' => array(
		'tabId' => 'cpanelmodulesjadministrator',
		'type' => 'modules',
	),
	'MOD_JTFAVORITES_VIEW_MODULES_TITLE_JSITE' => array(
		'tabId' => 'cpanelmodulesjsite',
		'type' => 'modules',
	),
	'MOD_JTFAVORITES_VIEW_PLUGINS_TITLE' => array(
		'tabId' => 'cpanelpluginsjsite',
		'type' => 'plugins',
	),
);

$activeTab = $tabAction[array_key_first($items)]['tabId'];
?>
<!-- Start mod_jtfavorites.cpanel -->
<div class="mod_jtfavorites cpanel<?php echo $moduleclass_sfx; ?>">
	<form method="post" name="<?php echo $task; ?>" id="<?php echo $task; ?>"
		  data-modules-action="<?php echo Route::_('index.php?option=com_modules'); ?>"
		  data-plugins-action="<?php echo Route::_('index.php?option=com_plugins&view=plugins'); ?>"
	>
		<?php if ($view == 'tabbed') : ?>
			<?php echo HTMLHelper::_('bootstrap.startTabSet', 'cpanelListFavorites', array('active' => $activeTab)); ?>
		<?php endif; ?>
		<?php foreach ($items as $tabTitle => $itemList) : ?>
			<?php $sublayout = array(
				'title' => Text::_($tabTitle),
				'tabId' => $tabAction[$tabTitle]['tabId'],
				'type'  => $tabAction[$tabTitle]['type'],
				'items' => $itemList,
				'task'  => $task,
				'view'  => $view,
			); ?>
			<?php echo $this->sublayout('items', $sublayout); ?>
		<?php endforeach; ?>
		<?php if ($view == 'tabbed') : ?>
			<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
		<?php endif; ?>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
<!-- End mod_jtfavorites.cpanel -->
