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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;

extract($displayData);

/**
 * @var   FileLayout  $this
 * @var   array       $items            List of ordered favorites
 * @var   string      $task             Form id for this position using as clickaction too
 * @var   string      $view             View for items output (tabbed/list)
 * @var   string      $moduleTitle      Module title
 * @var   string      $moduleclass_sfx  Module class suffix
 */

$tabAction = array(
	'MOD_JTFAVORITES_VIEW_CUSTOMS_TITLE' => array(
		'type' => 'customs',
	),
	'MOD_JTFAVORITES_VIEW_CORE_TITLE' => array(
		'type' => 'core',
	),
	'MOD_JTFAVORITES_VIEW_MODULES_TITLE_JADMINISTRATOR' => array(
		'type' => 'modules',
	),
	'MOD_JTFAVORITES_VIEW_MODULES_TITLE_JSITE' => array(
		'type' => 'modules',
	),
	'MOD_JTFAVORITES_VIEW_PLUGINS_TITLE' => array(
		'type' => 'plugins',
	),
);
?>
<!-- Start mod_jtfavorites.menu -->
<form style="margin:0;" method="post" name="<?php echo $task; ?>" id="<?php echo $task; ?>"
  data-modules-action="<?php echo 'index.php?option=com_modules'; ?>"
  data-plugins-action="<?php echo 'index.php?option=com_plugins&view=plugins'; ?>"
>
	<div class="nav-collapse collapse">
		<ul class="nav mod_jtfavorites menu<?php echo $moduleclass_sfx; ?>">
			<li class="dropdown">
				<a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php echo $moduleTitle; ?> <span class="caret"></span></a>
				<ul class="dropdown-menu scroll-menu" style="min-width:300px;">
					<?php foreach ($items as $tabTitle => $itemList) : ?>
						<?php $sublayout = array(
							'title' => Text::_($tabTitle),
							'type'  => $tabAction[$tabTitle]['type'],
							'items' => $itemList,
							'task'  => $task,
							'view'  => $view,
						); ?>
						<?php echo $this->sublayout('items', $sublayout); ?>
					<?php endforeach; ?>
				</ul>
			</li>
		</ul>
	</div>
	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<!-- End mod_jtfavorites.menu -->
