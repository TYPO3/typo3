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
 * @package
 * @subpackage
 * @version $Id: TemplateParserBuilder.php 2043 2010-03-16 08:49:45Z sebastian $
 */
/**
 * Build a template parser.
 * Use this class to get a fresh instance of a correctly initialized Fluid template parser.
 */
class Tx_Fluid_Compatibility_TemplateParserBuilder {
	/**
	 * Creates a new TemplateParser which is correctly initialized. This is the correct
	 * way to get a Fluid parser instance.
	 *
	 * @return Tx_Fluid_Core_TemplateParser A correctly initialized Template Parser
	 */
	static public function build() {
		$templateParser = t3lib_div::makeInstance('Tx_Fluid_Core_Parser_TemplateParser');
		$templateParser->injectObjectManager(t3lib_div::makeInstance('Tx_Fluid_Compatibility_ObjectManager'));
		return $templateParser;
	}
}


?>