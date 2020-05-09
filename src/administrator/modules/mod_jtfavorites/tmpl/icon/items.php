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
 */
?>
<!-- Start mod_jtfavorites.icon.items -->
<?php if (!empty($items['site'])) : ?>
	<?php if ($type == 'modules') : ?>
		<h2 class="nav-header"><?php echo JFilterOutput::ampReplace($title . ' - ' . Text::_('JSITE')); ?></h2>
	<?php endif; ?>
	<?php if ($type == 'plugins') : ?>
		<h2 class="nav-header"><?php echo JFilterOutput::ampReplace($title); ?></h2>
	<?php endif; ?>
	<ul class="j-links-group nav nav-list">
		<?php foreach ($items['site'] as $item) : ?>
			<?php $itemSublayout = array(
				'item' => $item,
				'type' => $type,
				'task' => $task,
			); ?>
			<?php echo $this->sublayout('item', $itemSublayout) ?>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
<?php if (!empty($items['administrator'])) : ?>
	<h2 class="nav-header"><?php echo JFilterOutput::ampReplace($title . ' - ' . Text::_('JADMINISTRATOR')); ?></h2>
	<ul class="j-links-group nav nav-list">
		<?php foreach ($items['administrator'] as $item) : ?>
			<?php $itemSublayout = array(
				'item' => $item,
				'type' => $type,
				'task' => $task,
			); ?>
			<?php echo $this->sublayout('item', $itemSublayout) ?>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
<!-- End mod_jtfavorites.icon.items -->
