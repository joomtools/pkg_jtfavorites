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
 * @var   string      $task   Position of this module for clickaction
 */
?>
<div class="table table-striped unstyled <?php echo $type; ?>">
	<?php if (!empty($items['site'])) : ?>
		<table class="site table table-hover table-bordered table-striped unstyled">
			<?php if ($type == 'modules') : ?>
				<caption class="text-left"><h4><?php echo $title . ' - ' . Text::_('JSITE'); ?></h4></caption>
			<?php endif; ?>
			<?php if ($type == 'plugins') : ?>
				<caption class="text-left"><h4><?php echo $title; ?></h4></caption>
			<?php endif; ?>
			<tbody>
			<?php foreach ($items['site'] as $item) : ?>
					<?php $itemSublayout = array(
						'item' => $item,
						'type' => $type,
						'task' => $task,
					); ?>
					<?php echo $this->sublayout('item', $itemSublayout) ?>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<?php if (!empty($items['administrator'])) : ?>
		<table class="administrator table table-hover table-bordered table-striped unstyled">
			<caption class="text-left"><h4><?php echo $title . ' - ' . Text::_('JADMINISTRATOR'); ?></h4></caption>
			<tbody>
			<?php foreach ($items['administrator'] as $item) : ?>
					<?php $itemSublayout = array(
						'item' => $item,
						'type' => $type,
						'task' => $task,
					); ?>
					<?php echo $this->sublayout('item', $itemSublayout) ?>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
