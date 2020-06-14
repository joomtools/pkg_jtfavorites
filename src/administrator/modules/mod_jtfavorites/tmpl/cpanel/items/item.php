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
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;

extract($displayData);

/**
 * @var   FileLayout  $this
 * @var   object      $item  Single favorite entry
 * @var   string      $type  Type of favorite (modules/plugins)
 * @var   string      $task  Form id for this position using as clickaction too
 */

$target = '';
$row    =& ModJtFavoritesHelper::$row;

if (in_array($type, array('customs', 'core')))
{
	$targetLink = array($type => $item->link);
	$target = !empty($item->target) ? ' target="_blank"' : '';
}
else
{
	$canCheckin  = Factory::getUser()->authorise('core.manage', 'com_checkin');
	$editor      = (int) $item->editor ? Factory::getUser((int) $item->editor)->name : '';
	$clickAction = $task . (int) $item->extension_id . 'Cb';
	$extId       = (int) $item->extension_id;
	$targetLink  = array(
		'modules' => 'index.php?option=com_modules&task=module.edit&id=' . $extId,
		'plugins' => 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $extId,
	);
} ?>
<!-- Start mod_jtfavorites.cpanel.items.item -->
<tr class="row<?php echo $row % 2; ?>">
	<?php if (!in_array($type, array('customs', 'core'))) : ?>
		<td class="span1">
		<span class="btn-group click-action">
			<?php echo HTMLHelper::_('jgrid.published', (int) $item->state, $row, $type . '.', $item->access['core.edit.state'], $clickAction); ?>
			<?php // Create dropdown items and render the dropdown list. ?>
			<?php if ($item->access['core.edit.state'] && $type == 'modules') : ?>
				<?php HTMLHelper::_('actionsdropdown.' . ((int) $item->state === -2 ? 'un' : '') . 'trash', $clickAction . $row, $type); ?>
				<?php echo HTMLHelper::_('actionsdropdown.render', $item->title); ?>
			<?php endif; ?>
			<?php if (!empty($editor)) : ?>
				<?php echo HTMLHelper::_('jgrid.checkedout', $row, $editor, $item->checked_out_time, $type . '.', $canCheckin, $clickAction); ?>
			<?php endif; ?>
		</span>
		</td>
	<?php endif; ?>
	<td>
	<span class="ext-title">
	<?php if ($item->access['core.edit'] && !empty($targetLink[$type])) : ?>
		<a class="hasTooltip ext-link" href="<?php echo OutputFilter::ampReplace($targetLink[$type]); ?>"
		   title="<?php echo Text::_('JACTION_EDIT'); ?>"<?php echo $target; ?>>
			<strong><?php echo $item->title; ?></strong>
		</a>
	<?php else : ?>
		<strong><?php echo $item->title; ?></strong>
	<?php endif; ?>
	<?php if (!in_array($type, array('customs', 'core'))) : ?>
		<span class="hidden"><?php echo HTMLHelper::_('grid.id', $row, $extId, false, 'cid', $clickAction); ?></span>
	<?php endif; ?>

		<?php $row++; ?>
	</td>
</tr>
<!-- End mod_jtfavorites.cpanel.items.item -->
