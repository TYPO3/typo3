<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: HtmlViewHelper.php 2043 2010-03-16 08:49:45Z sebastian $
 */

/**
 * Renders a string by passing it to a TYPO3 parseFunc.
 * You can either specify a path to the TypoScript setting or set the parseFunc options directly.
 * By default lib.parseFunc_RTE is used to parse the string.
 *
 * Example:
 *
 * (1) default parameters:
 * <f:format.html>foo <b>bar</b>. Some <LINK 1>link</LINK>.</f:format.html>
 *
 * Result:
 * <p class="bodytext">foo <b>bar</b>. Some <a href="index.php?id=1" >link</a>.</p>
 * (depending on your TYPO3 setup)
 *
 * (2) custom parseFunc
 * <f:format.html parseFuncTSPath="lib.parseFunc">foo <b>bar</b>. Some <LINK 1>link</LINK>.</f:format.html>
 *
 * Output:
 * foo <b>bar</b>. Some <a href="index.php?id=1" >link</a>.
 *
 * @see http://typo3.org/documentation/document-library/references/doc_core_tsref/4.2.0/view/1/5/#id4198758
 *
 * @package
 * @subpackage
 * @version $Id: HtmlViewHelper.php 2043 2010-03-16 08:49:45Z sebastian $
 */
class Tx_Fluid_ViewHelpers_Format_HtmlViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @var	tslib_cObj
	 */
	protected $contentObject;

	/**
	 * If the escaping interceptor should be disabled inside this ViewHelper, then set this value to FALSE.
	 * This is internal and NO part of the API. It is very likely to change.
	 *
	 * @var boolean
	 * @internal
	 */
	protected $escapingInterceptorEnabled = FALSE;

	/**
	 * Constructor. Used to create an instance of tslib_cObj used by the render() method.
	 * @param tslib_cObj $contentObject injector for tslib_cObj (optional)
	 * @return void
	 */
	public function __construct($contentObject = NULL) {
		$this->contentObject = $contentObject !== NULL ? $contentObject : t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * @param string $parseFuncTSPath path to TypoScript parseFunc setup.
	 * @return the parsed string.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Niels Pardon <mail@niels-pardon.de>
	 */
	public function render($parseFuncTSPath = 'lib.parseFunc_RTE') {
		$value = $this->renderChildren();
		return $this->contentObject->parseFunc($value, array(), '< ' . $parseFuncTSPath);
	}
}

?>