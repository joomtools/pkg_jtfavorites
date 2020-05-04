<?php
/**
 * @package      Joomla.Administrator
 * @subpackage   mod_jtfavorites
 *
 * @author       Guido De Gobbis <support@joomtools.de>
 * @copyright    2020 JoomTools.de - All rights reserved.
 * @license      GNU General Public License version 3 or later
 */

/**
 * @var   object  $params  Module params
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die;

// Include dependencies.
JLoader::register('ModJtFavoritesHelper', __DIR__ . '/helper.php');

// Check if plugin ist enabled
$isEnabledPlugin = ModJtFavoritesHelper::isEnabledPlugin();

if (!$isEnabledPlugin)
{
	return;
}

// Define params
$linkState        = $params->get('allow_change_state');
$linkItem         = $params->get('link_to_item');
$showTrashedItems = filter_var($params->get('show_trashed_items'), FILTER_VALIDATE_BOOLEAN);
$moduleclass_sfx  = $params->get('moduleclass_sfx', '');

// Get the list of favorites from database
$items = ModJtFavoritesHelper::getList();

$loadJs       = array();
$toDeleteInDb = array();

// Create item list for layout output
foreach ($items as $k => &$item)
{
	list ($extension, $item->type, $item->extension_id) = explode('.', $item->assets_name);

	// Validate authorization
	$item->access = ModJtFavoritesHelper::validateAuthorizations($extension, $item->assets_name);

	$loadJs[$item->extension_id] = $item->access['core.edit.state'];
	if ($item->access !== false)
	{
		if (!$linkState)
		{
			$loadJs[$item->extension_id]     = false;
			$item->access['core.edit.state'] = false;
		}

		if (!$linkState)
		{
			$item->access['core.edit'] = false;
		}
	}

	// Remove item, if access is not allowed
	if ($item->access === false
		|| ($item->access['core.edit.state'] === false && $item->access['core.edit'] === false)
		|| ($item->state == -2 && $showTrashedItems === false))
	{
		if ($item->access === false)
		{
			// Permission revoked, remove entry from database
			$toDeleteInDb[] = array(
				'user_id'     => Factory::getUser()->id,
				'assets_name' => $item->assets_name,
			);
		}

		unset($items[$k], $loadJs[$item->extension_id]);

		continue;
	}

	// Load extension language file, if the title is not individualized
	if (!empty($item->extension_name))
	{
		ModJtFavoritesHelper::loadExtensionLanguage($item->extension_name, $item->type, $item->client_id);
	}

	// Translate title
	$item->title = Text::_($item->title);

	// Distinction between page and administration extensions
	$item->client = $item->client_id ? 'administrator' : 'site';

	// Set param to show trashed items
	$item->access['show.trashed.items'] = $showTrashedItems;

	// Clean up the element for layout output
	unset(
		$item->client_id,
		$item->assets_name,
		$item->extension_name,
		$item->extension,
	);
}

// Delete entries from database
ModJtFavoritesHelper::deleteDbEntry($toDeleteInDb);

if (count($items) < 1)
{
	return;
}

// Rearrange item list by type
$items = ArrayHelper::pivot($items, 'type');

// Rearrange type list by client
foreach ($items as $k => $item)
{

	// Equalization of the entries as array list
	if (!is_array($item))
	{
		$items[$k] = array($item);
	}

	$items[$k] = ArrayHelper::pivot($items[$k], 'client');

	foreach ($items[$k] as $client => $clientlist)
	{
		// Equalization of the entries as array list
		if (!is_array($clientlist))
		{
			$items[$k][$client] = array($clientlist);
		}

		$items[$k][$client] = ArrayHelper::sortObjects($items[$k][$client], 'title');
	}
}

unset($item, $client, $clientlist);

$position = $module->position;
$layout   = $params->get('layout', 'default');

//Set the layout automatically by position if no individual layout is selected
$layout = $layout !== '_:default' ? $layout : '_:' . $position;

$layoutRenderer = ModJtFavoritesHelper::getLayoutRenderer($layout);

$displayData = array(
	'modules'         => !empty($items['module']) ? $items['module'] : null,
	'plugins'         => !empty($items['plugin']) ? $items['plugin'] : null,
	'moduleclass_sfx' => $moduleclass_sfx,
	'task'            => $position,
);

$loadJs = array_values($loadJs);
$loadJs = ArrayHelper::arrayUnique($loadJs);

if (count($loadJs) > 1 || $loadJs[0] === true)
{
	HTMLHelper::_('script', 'mod_jtfavorites/jtfavoritesClickAction.js', array('version' => 'auto', 'relative' => true));
}

echo $layoutRenderer->render($displayData);
