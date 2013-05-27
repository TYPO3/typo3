<?php
/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * View helper which returns save button with icon
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code title="Default">
 * <f:be.buttons.icon uri="{f:uri.action()}" />
 * </code>
 *
 * Output:
 * An icon button as known from the TYPO3 backend, skinned and linked with the default action of the current controller.
 * Note: By default the "close" icon is used as image
 *
 * <code title="Default">
 * <f:be.buttons.icon uri="{f:uri.action(action='new')}" icon="new_el" title="Create new Foo" />
 * </code>
 *
 * Output:
 * This time the "new_el" icon is returned, the button has the title attribute set and links to the "new" action of the current controller.
 *
 * @package     Fluid
 * @subpackage  ViewHelpers\Be\Buttons
 * @author		Steffen Kamper <info@sk-typo3.de>
 * @author		Bastian Waidelich <bastian@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id:
 *
 */
class Tx_Fluid_ViewHelpers_Be_Buttons_IconViewHelper extends Tx_Fluid_ViewHelpers_Be_AbstractBackendViewHelper {

	/**
	 * @var array allowed icons to be used with this view helper
	 */
	protected $allowedIcons = array('add', 'add_workspace', 'button_down', 'button_hide', 'button_left', 'button_unhide', 'button_right', 'button_up', 'clear_cache', 'clip_copy', 'clip_cut', 'clip_pasteafter', 'closedok', 'datepicker', 'deletedok', 'edit2', 'helpbubble', 'icon_fatalerror', 'icon_note', 'icon_ok', 'icon_warning', 'new_el', 'options', 'perm', 'refresh_n', 'saveandclosedok', 'savedok', 'savedoknew', 'savedokshow', 'viewdok', 'zoom');

	/**
	 * Renders an icon link as known from the TYPO3 backend
	 *
	 * @param string $uri the target URI for the link. If you want to execute JavaScript here, prefix the URI with "javascript:"
	 * @param string $icon Icon to be used. See self::allowedIcons for a list of allowed icon names
	 * @param string $title Title attribte of the resulting link
	 * @return string the rendered icon link
	 */
	public function render($uri, $icon = 'closedok', $title = '') {
		if (!in_array($icon, $this->allowedIcons)) {
			throw new Tx_Fluid_Core_ViewHelper_Exception('"' . $icon . '" is no valid icon. Allowed are "' . implode('", "', $this->allowedIcons) .'".', 1253208523);
		}

		$skinnedIcon = t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/' . $icon . '.gif', '');
		return '<a href="' . $uri . '"><img' . $skinnedIcon . '" title="' . htmlspecialchars($title) . '" alt="" /></a>';
	}
}
?>
