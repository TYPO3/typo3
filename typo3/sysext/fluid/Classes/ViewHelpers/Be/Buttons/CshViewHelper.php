<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Buttons;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is backported from the TYPO3 Flow package "TYPO3.Fluid".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
	 * @param bool $iconOnly If set, the full text will never be shown (only icon)
	 * @param string $styleAttributes Additional style-attribute content for wrapping table (full text mode only)
	 * @return string the rendered CSH icon
	 */
	public function render($table = NULL, $field = '', $iconOnly = FALSE, $styleAttributes = '') {
		if ($table === NULL) {
			$currentRequest = $this->controllerContext->getRequest();
			$moduleName = $currentRequest->getPluginName();
			$table = '_MOD_' . $moduleName;
		}
		$cshButton = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($table, $field);
		return '<div class="docheader-csh">' . $cshButton . '</div>';
	}

}
