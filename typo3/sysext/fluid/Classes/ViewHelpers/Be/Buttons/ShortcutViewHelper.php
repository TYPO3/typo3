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
 * View helper which returns shortcut button with icon
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code title="Default">
 * <f:be.buttons.shortcut />
 * </code>
 * <output>
 * Shortcut button as known from the TYPO3 backend.
 * By default the current page id, module name and all module arguments will be stored
 * </output>
 *
 * <code title="Explicitly set parameters to be stored in the shortcut">
 * <f:be.buttons.shortcut getVars="{0: 'M', 1: 'myOwnPrefix'}" setVars="{0: 'function'}" />
 * </code>
 * <output>
 * Shortcut button as known from the TYPO3 backend.
 * This time only the specified GET parameters and SET[]-settings will be stored.
 * Note:
 * Normally you won't need to set getVars & setVars parameters in Extbase modules
 * </output>
 */
class ShortcutViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Renders a shortcut button as known from the TYPO3 backend
	 *
	 * @param array $getVars list of GET variables to store. By default the current id, module and all module arguments will be stored
	 * @param array $setVars list of SET[] variables to store. See template::makeShortcutIcon(). Normally won't be used by Extbase modules
	 * @return string the rendered shortcut button
	 * @see template::makeShortcutIcon()
	 */
	public function render(array $getVars = array(), array $setVars = array()) {
		$doc = $this->getDocInstance();
		$currentRequest = $this->controllerContext->getRequest();
		$extensionName = $currentRequest->getControllerExtensionName();
		$moduleName = $currentRequest->getPluginName();
		if (count($getVars) === 0) {
			$modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
			$getVars = array('id', 'M', $modulePrefix);
		}
		$getList = implode(',', $getVars);
		$setList = implode(',', $setVars);
		return $doc->makeShortcutIcon($getList, $setList, $moduleName);
	}
}

?>