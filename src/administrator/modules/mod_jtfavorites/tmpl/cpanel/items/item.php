<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_custom
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

extract($displayData);

/**
 * @var   FileLayout  $this
 * @var   object      $item  Single favorite entry
 * @var   string      $type  Type of favorite (modules/plugins)
 * @var   string      $task  Form id for this position using as clickaction too
 */

$canCheckin  = Factory::getUser()->authorise('core.manage', 'com_checkin');
$editor      = (int) $item->editor > 0 ? Factory::getUser((int) $item->editor)->name : '';
$row         =& ModJtFavoritesHelper::$row;
$clickAction = $task . (int) $item->extension_id . 'Cb';
$extId       = (int) $item->extension_id;
$target      = array(
	'modules' => 'index.php?option=com_modules&task=module.edit&id=' . $extId,
	'plugins' => 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $extId,
); ?>
<!-- Start mod_jtfavorites.cpanel.items.item -->
<tr class="row<?php echo $row % 2; ?>">
	<td class="span1">
	<span class="btn-group click-action">
		<?php echo HTMLHelper::_('jgrid.published', (int) $item->state, $row, $type . '.', $item->access['core.edit.state'], $clickAction); ?>
		<?php // Create dropdown items and render the dropdown list. ?>
		<?php if ($item->access['show.trashed.items'] && $type == 'modules') : ?>
			<?php HTMLHelper::_('actionsdropdown.' . ((int) $item->state === -2 ? 'un' : '') . 'trash', $clickAction . $row, $type); ?>
			<?php echo HTMLHelper::_('actionsdropdown.render', $item->title); ?>
		<?php endif; ?>
		<?php if (!empty($editor)) : ?>
			<?php echo HTMLHelper::_('jgrid.checkedout', $row, $editor, $item->checked_out_time, $type . '.', $canCheckin, $clickAction); ?>
		<?php endif; ?>
	</span>
	</td>
	<td>
	<span class="ext-title">
	<?php if ($item->access['core.edit']) : ?>
		<a class="hasTooltip" href="<?php echo OutputFilter::ampReplace($target[$type]); ?>"
		   title="<?php echo Text::_('JACTION_EDIT'); ?>">
			<strong><?php echo $item->title; ?></strong>
		</a>
	<?php else : ?>
		<strong><?php echo $item->title; ?></strong>
	<?php endif; ?>
	</span>
	<span style="display:none;">
		<?php echo HTMLHelper::_('grid.id', $row, $extId, false, 'cid', $clickAction); ?>
	</span>

		<?php $row++; ?>
	</td>
</tr>
<!-- End mod_jtfavorites.cpanel.items.item -->
