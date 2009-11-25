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
 * View helper which returns shortcut button with icon
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code title="Default">
 * <f:be.buttons.shortcut />
 * </code>
 *
 * Output:
 * Shortcut button as known from the TYPO3 backend.
 * By default the current page id, module name and all module arguments will be stored
 *
 * <code title="Explicitly set parameters to be stored in the shortcut">
 * <f:be.buttons.shortcut getVars="{0: 'M', 1: 'myOwnPrefix'}" setVars="{0: 'function'}" />
 * </code>
 *
 * Output:
 * Shortcut button as known from the TYPO3 backend.
 * This time only the specified GET parameters and SET[]-settings will be stored.
 * Note:
 * Normally you won't need to set getVars & setVars parameters in Extbase modules
 *
 * @package     Fluid
 * @subpackage  ViewHelpers\Be\Buttons
 * @author		Steffen Kamper <info@sk-typo3.de>
 * @author		Bastian Waidelich <bastian@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id:
 *
 */
class Tx_Fluid_ViewHelpers_Be_Buttons_ShortcutViewHelper extends Tx_Fluid_ViewHelpers_Be_AbstractBackendViewHelper {


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
