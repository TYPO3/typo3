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
 * View helper which returns CSH (context sensitive help) button with icon
 * Note: The CSH button will only work, if the current BE user has
 * the "Context Sensitive Help mode" set to something else than
 *  "Display no help information" in the Users settings
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code title="Default">
 * <f:be.buttons.csh />
 * </code>
 *
 * Output:
 * CSH button as known from the TYPO3 backend.
 *
 *
 * @package     Fluid
 * @subpackage  ViewHelpers\Be\Buttons
 * @author		Steffen Kamper <info@sk-typo3.de>
 * @author		Bastian Waidelich <bastian@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id:
 *
 */
class Tx_Fluid_ViewHelpers_Be_Buttons_CshViewHelper extends Tx_Fluid_ViewHelpers_Be_AbstractBackendViewHelper {


	/**
	 * Render context sensitive help (CSH) for the given table
	 *
	 * @param string $table Table name ('_MOD_'+module name). If not set, the current module name will be used
	 * @param string $field Field name (CSH locallang main key)
	 * @param boolean $iconOnly If set, the full text will never be shown (only icon)
	 * @param string $styleAttributes Additional style-attribute content for wrapping table (full text mode only)
	 * @return string the rendered CSH icon
	 * @see t3lib_BEfunc::cshItem
	 */
	public function render($table = NULL, $field = '', $iconOnly = FALSE, $styleAttributes = '') {
		if ($table === NULL) {
			$currentRequest = $this->controllerContext->getRequest();
			$moduleName = $currentRequest->getPluginName();
			$table = '_MOD_' . $moduleName;
		}
		$cshButton = t3lib_BEfunc::cshItem($table, $field, $GLOBALS['BACK_PATH'], '', $iconOnly, $styleAttributes);

		return '<div class="docheader-csh">' . $cshButton . '</div>';
	}
}
?>
