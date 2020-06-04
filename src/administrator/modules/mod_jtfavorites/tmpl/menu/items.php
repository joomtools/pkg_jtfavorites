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

use Joomla\CMS\Layout\FileLayout;

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
<!-- Start mod_jtfavorites.menu.items -->
<li class="nav-header menu-<?php echo $type; ?>"><?php echo $title; ?></li>
<li class="dropdown-submenu">
	<ul class="nav nav-list <?php echo $type; ?>">
		<?php foreach ($items as $item) : ?>
			<?php $itemSublayout = array(
				'item' => $item,
				'type' => $type,
				'task' => $task,
			); ?>
			<?php echo $this->sublayout('item', $itemSublayout) ?>
		<?php endforeach; ?>
	</ul>
</li>
<li style="clear:both;">&nbsp;</li>
<!-- End mod_jtfavorites.menu.items -->
