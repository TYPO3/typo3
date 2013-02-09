<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Else-Branch of a condition. Only has an effect inside of "If". See the If-ViewHelper for documentation.
 *
 * = Examples =
 *
 * <code title="Output content if condition is not met">
 * <f:if condition="{someCondition}">
 *   <f:else>
 *     condition was not true
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Everything inside the "else" tag is displayed if the condition evaluates to FALSE.
 * Otherwise nothing is outputted in this example.
 * </output>
 *
 * @see TYPO3\CMS\Fluid\ViewHelpers\IfViewHelper
 * @api
 */
class ElseViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @return string the rendered string
	 * @api
	 */
	public function render() {
		return $this->renderChildren();
	}
}

?>