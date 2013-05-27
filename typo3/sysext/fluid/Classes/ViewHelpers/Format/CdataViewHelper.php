<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Format;

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
 * Outputs an argument/value without any escaping and wraps it with CDATA tags.
 *
 * PAY SPECIAL ATTENTION TO SECURITY HERE (especially Cross Site Scripting),
 * as the output is NOT SANITIZED!
 *
 * = Examples =
 *
 * <code title="Child nodes">
 * <f:format.cdata>{string}</f:format.cdata>
 * </code>
 * <output>
 * <![CDATA[(Content of {string} without any conversion/escaping)]]>
 * </output>
 *
 * <code title="Value attribute">
 * <f:format.cdata value="{string}" />
 * </code>
 * <output>
 * <![CDATA[(Content of {string} without any conversion/escaping)]]>
 * </output>
 *
 * <code title="Inline notation">
 * {string -> f:format.cdata()}
 * </code>
 * <output>
 * <![CDATA[(Content of {string} without any conversion/escaping)]]>
 * </output>
 *
 * @api
 */
class CdataViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Disable the escaping interceptor because otherwise the child nodes would be escaped before this view helper
	 * can decode the text's entities.
	 *
	 * @var boolean
	 */
	protected $escapingInterceptorEnabled = FALSE;

	/**
	 * @param mixed $value The value to output
	 * @return string
	 */
	public function render($value = NULL) {
		if ($value === NULL) {
			$value = $this->renderChildren();
		}
		return sprintf('<![CDATA[%s]]>', $value);
	}
}

?>