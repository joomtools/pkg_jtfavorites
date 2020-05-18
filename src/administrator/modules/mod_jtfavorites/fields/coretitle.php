<?php
/**
 * @package      Joomla.Administrator
 * @subpackage   mod_jtfavorites
 *
 * @author       Guido De Gobbis <support@joomtools.de>
 * @copyright    2020 JoomTools.de - All rights reserved.
 * @license      GNU General Public License version 3 or later
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Coretitle field.
 *
 * @since  2.5
 */
class  JFormFieldCoretitle extends JFormField
{
	/**
	 * The field type.
	 *
	 * @var     string
	 * @since   1.1.0
	 */
	protected $type = 'Coretitle';

	/**
	 * Method to get the field input markup.
	 *
	 * @return   string  The field input markup.
	 * @since    1.1.0
	 */
	public function getInput()
	{
		$lang = Factory::getLanguage();
		$lang->load('mod_menu', JPATH_ADMINISTRATOR);
		$lang->load('mod_menu' . '.sys', JPATH_ADMINISTRATOR);

		$field = '<h4>' . Text::_($this->value) . '</h4>';
		$field .= '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="'. htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '" />';

		return $field;
	}
}