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
 * Test case
 */
class AbstractNodeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    protected $renderingContext;

    protected $abstractNode;

    protected $childNode;

    protected function setUp()
    {
        $this->renderingContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext::class, [], [], '', false);

        $this->abstractNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode::class, ['evaluate']);

        $this->childNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode::class);
        $this->abstractNode->addChildNode($this->childNode);
    }

    /**
     * @test
     */
    public function evaluateChildNodesPassesRenderingContextToChildNodes()
    {
        $this->childNode->expects($this->once())->method('evaluate')->with($this->renderingContext);
        $this->abstractNode->evaluateChildNodes($this->renderingContext);
    }

    /**
     * @test
     */
    public function childNodeCanBeReadOutAgain()
    {
        $this->assertSame($this->abstractNode->getChildNodes(), [$this->childNode]);
    }
}
