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
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

extract($displayData);

/**
 * @var   FileLayout  $this
 * @var   array       $modules          List of favorites modules
 * @var   array       $plugins          List of favorites plugins
 * @var   string      $task             Position of this module for clickaction
 * @var   string      $moduleclass_sfx  Module param
 */

// TODO Javascript in Datei auslagern (Media Ordner)

?>
<div class="mod_jtfavorites<?php echo $moduleclass_sfx; ?>">
	<form method="post" name="jtFavoritesForm" id="jtFavoritesForm"
		  data-modules-action="<?php echo Route::_('index.php?option=com_modules'); ?>"
		  data-plugins-action="<?php echo Route::_('index.php?option=com_plugins&view=plugins'); ?>"
	>
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
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
