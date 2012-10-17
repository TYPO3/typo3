<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * DEPRECATED: Use <f:form.textfield> instead!
 *
 * View Helper which creates a simple Text Box (<input type="text">).
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.textbox name="myTextBox" value="default value" />
 * </code>
 * <output>
 * <input type="text" name="myTextBox" value="default value" />
 * </output>
 *
 * @deprecated since Extbase 1.4.0; will be removed in Extbase 1.6.0. Please use the <f:form.textfield> ViewHelper instead.
 */
class TextboxViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\TextfieldViewHelper {

	public function render() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction('<f:form.textbox> is deprecated. Please use <f:form.textfield> instead.');
		parent::render();
	}

}


?>