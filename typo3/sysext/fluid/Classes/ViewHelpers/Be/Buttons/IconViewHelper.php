<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Buttons;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
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
 * View helper which returns button with icon
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code title="Default">
 * <f:be.buttons.icon uri="{f:uri.action()}" />
 * </code>
 * <output>
 * An icon button as known from the TYPO3 backend, skinned and linked with the default action of the current controller.
 * Note: By default the "close" icon is used as image
 * </output>
 *
 * <code title="Default">
 * <f:be.buttons.icon uri="{f:uri.action(action:'new')}" icon="actions-document-new" title="Create new Foo" />
 * </code>
 * <output>
 * This time the "actions-document-new" icon is returned, the button has the title attribute set and links to the "new" action of the current controller.
 * </output>
 */
class IconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Renders an icon link as known from the TYPO3 backend
	 *
	 * @param string $uri the target URI for the link. If you want to execute JavaScript here, prefix the URI with "javascript:
	 * @param string $icon Icon to be used. See self::allowedIcons for a list of allowed icon names
	 * @param string $title Title attribte of the resulting link
	 * @return string the rendered icon link
	 */
	public function render($uri, $icon = 'actions-document-close', $title = '') {
		return '<a href="' . $uri . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon, array('title' => $title)) . '</a>';
	}
}

?>