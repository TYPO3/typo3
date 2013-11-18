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
 * View helper which returns a button icon
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
 *
 * <code title="Default">
 * <f:be.buttons.icon icon="actions-document-new" title="Create new Foo" />
 * </code>
 * <output>
 * Here the "actions-document-new" icon is returned, but without link.
 * </output>
 */
class IconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Initialize arguments
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments() {
		$this->registerArgument('additionalAttributes', 'array', 'Additional tag attributes. They will be added directly to the resulting HTML tag.', FALSE);
	}

	/**
	 * Renders a linked icon as known from the TYPO3 backend.
	 *
	 * If the URI is left empty, the icon is rendered without link.
	 *
	 * @param string $uri The target URI for the link. If you want to execute JavaScript here, prefix the URI with "javascript:". Leave empty to render just an icon.
	 * @param string $icon Icon to be used.
	 * @param string $title Title attribute of the icon construct
	 * @return string The rendered icon with or without link
	 */
	public function render($uri = '', $icon = 'actions-document-close', $title = '') {
		$additionalAttributes = '';
		if ($this->hasArgument('additionalAttributes') && is_array($this->arguments['additionalAttributes'])) {
			foreach ($this->arguments['additionalAttributes'] as $argumentKey => $argumentValue) {
				$additionalAttributes .= ' ' . $argumentKey . '="' . htmlspecialchars($argumentValue) . '"';
			}
		}
		$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon, array('title' => $title));
		if (empty($uri)) {
			return $icon;
		} else {
			return '<a href="' . $uri . '"' . $additionalAttributes . '>' . $icon . '</a>';
		}
	}
}
