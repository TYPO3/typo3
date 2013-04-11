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
 * View helper which returns CSH (context sensitive help) button with icon
 * Note: The CSH button will only work, if the current BE user has
 * the "Context Sensitive Help mode" set to something else than
 * "Display no help information" in the Users settings
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code title="Default">
 * <f:be.buttons.csh />
 * </code>
 * <output>
 * CSH button as known from the TYPO3 backend.
 * </output>
 *
 * <code title="Full configuration">
 * <f:be.buttons.csh table="xMOD_csh_corebe" field="someCshKey" iconOnly="1" styleAttributes="border: 1px solid red" />
 * </code>
 * <output>
 * CSH button as known from the TYPO3 backend with some custom settings.
 * </output>
 */
class CshViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Render context sensitive help (CSH) for the given table
	 *
	 * @param string $table Table name ('_MOD_'+module name). If not set, the current module name will be used
	 * @param string $field Field name (CSH locallang main key)
	 * @param boolean $iconOnly If set, the full text will never be shown (only icon)
	 * @param string $styleAttributes Additional style-attribute content for wrapping table (full text mode only)
	 * @return string the rendered CSH icon
	 */
	public function render($table = NULL, $field = '', $iconOnly = FALSE, $styleAttributes = '') {
		if ($table === NULL) {
			$currentRequest = $this->controllerContext->getRequest();
			$moduleName = $currentRequest->getPluginName();
			$table = '_MOD_' . $moduleName;
		}
		$cshButton = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($table, $field, $GLOBALS['BACK_PATH'], '', $iconOnly, $styleAttributes);
		return '<div class="docheader-csh">' . $cshButton . '</div>';
	}
}

?>