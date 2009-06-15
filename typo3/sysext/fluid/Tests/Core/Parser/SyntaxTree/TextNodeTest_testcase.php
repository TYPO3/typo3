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
 * @subpackage Tests
 * @version $Id: TextNodeTest.php 2247 2009-05-18 20:02:37Z sebastian $
 */
/**
 * Testcase for TextNode
 *
 * @package Fluid
 * @subpackage Tests
 * @version $Id: TextNodeTest.php 2247 2009-05-18 20:02:37Z sebastian $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_Parser_SyntaxTree_TextNodeTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderReturnsSameStringAsGivenInConstructor() {
		$string = 'I can work quite effectively in a train!';
		$node = new Tx_Fluid_Core_Parser_SyntaxTree_TextNode($string);
		$this->assertEquals($node->evaluate(), $string, 'The rendered string of a text node is not the same as the string given in the constructor.');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function constructorThrowsExceptionIfNoStringGiven() {
		new Tx_Fluid_Core_Parser_SyntaxTree_TextNode(123);
	}
}



?>
