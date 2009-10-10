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
 * @version $Id: TextNodeTest.php 2813 2009-07-16 14:02:34Z k-fish $
 */
/**
 * Testcase for TextNode
 *
 * @version $Id: TextNodeTest.php 2813 2009-07-16 14:02:34Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_Fluid_Core_Parser_SyntaxTree_TextNodeTest_testcase extends Tx_Extbase_BaseTestCase {

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
