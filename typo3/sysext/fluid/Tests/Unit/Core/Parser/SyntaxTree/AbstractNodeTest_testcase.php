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
 * @version $Id: AbstractNodeTest.php 2813 2009-07-16 14:02:34Z k-fish $
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_Parser_SyntaxTree_AbstractNodeTest_testcase extends Tx_Extbase_Base_testcase {

	protected $renderingContext;

	protected $abstractNode;

	protected $childNode;

	public function setUp() {
		$this->renderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContext', array(), array(), '', FALSE);

		$this->abstractNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode', array('evaluate'));
		$this->abstractNode->setRenderingContext($this->renderingContext);

		$this->childNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode');
		$this->abstractNode->addChildNode($this->childNode);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function evaluateChildNodesPassesRenderingContextToChildNodes() {
		$this->childNode->expects($this->once())->method('setRenderingContext')->with($this->renderingContext);
		$this->abstractNode->evaluateChildNodes();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function childNodeCanBeReadOutAgain() {
		$this->assertSame($this->abstractNode->getChildNodes(), array($this->childNode));
	}
}

?>