<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_custom
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;

defined('_JEXEC') or die;

extract($displayData);

/**
 * @var   FileLayout  $this
 * @var   string      $title  Title of grouped favorites (Module/Plugins)
 * @var   string      $type   Type of favorite (modules/plugins)
 * @var   array       $items  Grouped list of favorites (site/administator)
 * @var   string      $task   Form id for this position using as clickaction too
 * @var   string      $view   View for items output (tabbed/list)
 */
?>
<!-- Start mod_jtfavorites.icon.items -->
<?php if (!empty($items['JSITE']) || !empty($items['JADMINISTRATOR'])) : ?>
	<?php foreach ($items as $interface => $itemList) : ?>
		<?php if ($type == 'modules') : ?>
			<?php $newTitle = JFilterOutput::ampReplace($title) . ' - ' . Text::_($interface); ?>
		<?php endif; ?>
		<?php if ($type == 'plugins' || $type == 'customs') : ?>
			<?php $newTitle = JFilterOutput::ampReplace($title); ?>
		<?php endif; ?>
		<?php if ($view == 'tabbed') : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'iconListFavorites', $type . strtolower($interface), $newTitle); ?>
		<?php else : ?>
			<h2 class="nav-header"><?php echo $newTitle; ?></h2>
		<?php endif; ?>
		<ul class="j-links-group nav nav-list">
			<?php foreach ($itemList as $item) : ?>
				<?php $itemSublayout = array(
					'item' => $item,
					'type' => $type,
					'task' => $task,
				); ?>
				<?php echo $this->sublayout('item', $itemSublayout) ?>
			<?php endforeach; ?>
		</ul>
		<?php if ($view == 'tabbed') : ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>
	<?php endforeach; ?>
<?php endif; ?>
<!-- End mod_jtfavorites.icon.items -->
