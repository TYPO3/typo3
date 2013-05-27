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
 */
class Tx_Fluid_Tests_Unit_Core_Parser_SyntaxTree_AbstractNodeTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	protected $renderingContext;

	protected $abstractNode;

	protected $childNode;

	public function setUp() {
		$this->renderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContext', array(), array(), '', FALSE);

		$this->abstractNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode', array('evaluate'));

		$this->childNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode');
		$this->abstractNode->addChildNode($this->childNode);
	}

	/**
	 * @test
	 */
	public function evaluateChildNodesPassesRenderingContextToChildNodes() {
		$this->childNode->expects($this->once())->method('evaluate')->with($this->renderingContext);
		$this->abstractNode->evaluateChildNodes($this->renderingContext);
	}

	/**
	 * @test
	 */
	public function childNodeCanBeReadOutAgain() {
		$this->assertSame($this->abstractNode->getChildNodes(), array($this->childNode));
	}
}

?>