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
 * @var   array       $modules          List of favorites modules
 * @var   array       $plugins          List of favorites plugins
 * @var   string      $task             Form id for this position using as clickaction too
 * @var   string      $moduleTitle      Module title
 * @var   string      $moduleclass_sfx  Module class suffix
 */

?>
<!-- Start mod_jtfavorites.icon -->
<div class="j-links-separator"></div>
<div class="mod_jtfavorites icon<?php echo $moduleclass_sfx; ?>">
	<form method="post" name="<?php echo $task; ?>" id="<?php echo $task; ?>"
		  data-modules-action="<?php echo Route::_('index.php?option=com_modules'); ?>"
		  data-plugins-action="<?php echo Route::_('index.php?option=com_plugins&view=plugins'); ?>"
	>
		<h2 class="quick-icons">
			<span class="module-title nav-header"><?php echo $moduleTitle; ?></span>
		</h2>
		<div class="sidebar-nav">
			<?php $parentLayout = new FileLayout('joomla.links.groupsopen'); ?>
			<?php echo $parentLayout->render(''); ?>
			<?php if (!is_null($modules)) : ?>
				<?php $sublayout = array(
					'title' => Text::_('MOD_JTFAVORITES_VIEW_MODULES_TITLE'),
					'type'  => 'modules',
					'items' => $modules,
					'task'  => $task,
				); ?>
				<?php echo $this->sublayout('items', $sublayout); ?>
			<?php endif; ?>
			<?php if (!is_null($plugins)) : ?>
				<?php $sublayout = array(
					'title' => Text::_('MOD_JTFAVORITES_VIEW_PLUFINS_TITLE'),
					'type'  => 'plugins',
					'items' => $plugins,
					'task'  => $task,
				); ?>
				<?php echo $this->sublayout('items', $sublayout); ?>
			<?php endif; ?>
			<?php $parentLayout = new FileLayout('joomla.links.groupsclose'); ?>
			<?php echo $parentLayout->render(''); ?>
		</div>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
<div class="j-links-separator"></div>
<!-- End mod_jtfavorites.icon -->
