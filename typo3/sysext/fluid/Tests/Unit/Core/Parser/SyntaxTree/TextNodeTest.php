<?php

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
 * Testcase for TextNode
 *
 */
class Tx_Fluid_Tests_Unit_Core_Parser_SyntaxTree_TextNodeTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function renderReturnsSameStringAsGivenInConstructor() {
		$string = 'I can work quite effectively in a train!';
		$node = new Tx_Fluid_Core_Parser_SyntaxTree_TextNode($string);
		$this->assertEquals($node->evaluate($this->getMock('Tx_Fluid_Core_Rendering_RenderingContext')), $string, 'The rendered string of a text node is not the same as the string given in the constructor.');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 */
	public function constructorThrowsExceptionIfNoStringGiven() {
		new Tx_Fluid_Core_Parser_SyntaxTree_TextNode(123);
	}
}



?>
