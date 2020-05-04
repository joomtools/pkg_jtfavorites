<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_custom
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\FileLayout;

defined('_JEXEC') or die;

extract($displayData);

/**
 * @var   FileLayout  $this
 * @var   array       $item  Single favorite entry
 * @var   string      $type  Type of favorite (modules/plugins)
 * @var   string      $task  Position of this module for clickaction
 */

$canCheckin = Factory::getUser()->authorise('core.manage', 'com_checkin');
$editor     = (int) $item->editor > 0 ? Factory::getUser((int) $item->editor)->name : '';
$row        =& ModJtFavoritesHelper::$row;

$target = array(
	'modules' => 'index.php?option=com_modules&task=module.edit&id=' . (int) $item->extension_id,
	'plugins' => 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . (int) $item->extension_id,
);
?>
<tr class="row<?php echo $row % 2; ?>">
	<td class="span1">
	<span class="btn-group">
		<span style="display:none;">
			<?php echo JHtml::_('grid.id', $row, (int) $item->extension_id, false, 'cid', $task . 'JtfCb'); ?>
		</span>
		<?php echo JHtml::_('jgrid.published', (int) $item->state, $row, $type . '.', $item->access['core.edit.state'], $task . 'JtfCb'); ?>
		<?php // Create dropdown items and render the dropdown list. ?>
		<?php if ($item->access['show.trashed.items'] && $type == 'modules') : ?>
			<?php JHtml::_('actionsdropdown.' . ((int) $item->state === -2 ? 'un' : '') . 'trash', $task . 'JtfCb' . $row, 'modules'); ?>
			<?php echo JHtml::_('actionsdropdown.render', $item->title); ?>
		<?php endif; ?>
		<?php if (!empty($editor)) : ?>
			<?php echo JHtml::_('jgrid.checkedout', $row, $editor, $item->checked_out_time, $type . '.', $canCheckin, $task . 'JtfCb'); ?>
		<?php endif; ?>
	</span>
	</td>
	<td>
	<span class="row-title">
	<?php if ($item->access['core.edit']) : ?>
		<a class="hasTooltip" href="<?php echo JRoute::_($target[$type]); ?>"
		   title="<?php echo JText::_('JACTION_EDIT'); ?>">
			<strong><?php echo $item->title; ?></strong>
		</a>
	<?php else : ?>
		<strong><?php echo $item->title; ?></strong>
	<?php endif; ?>
	</span>
		<?php $row++; ?>
	</td>
</tr>
