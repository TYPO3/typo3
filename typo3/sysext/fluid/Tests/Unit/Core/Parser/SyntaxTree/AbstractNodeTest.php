<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Parser\SyntaxTree;

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
 * An AbstractNode Test
 */
class AbstractNodeTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	protected $renderingContext;

	protected $abstractNode;

	protected $childNode;

	public function setUp() {
		$this->renderingContext = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContext', array(), array(), '', FALSE);

		$this->abstractNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\AbstractNode', array('evaluate'));

		$this->childNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\AbstractNode');
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