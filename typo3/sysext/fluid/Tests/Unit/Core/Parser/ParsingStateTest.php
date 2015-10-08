<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Parser;

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
class ParsingStateTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Parsing state
     *
     * @var \TYPO3\CMS\Fluid\Core\Parser\ParsingState
     */
    protected $parsingState;

    protected function setUp()
    {
        $this->parsingState = new \TYPO3\CMS\Fluid\Core\Parser\ParsingState();
    }

    /**
     * @test
     */
    public function setRootNodeCanBeReadOutAgain()
    {
        $rootNode = new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode();
        $this->parsingState->setRootNode($rootNode);
        $this->assertSame($this->parsingState->getRootNode(), $rootNode, 'Root node could not be read out again.');
    }

    /**
     * @test
     */
    public function pushAndGetFromStackWorks()
    {
        $rootNode = new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode();
        $this->parsingState->pushNodeToStack($rootNode);
        $this->assertSame($rootNode, $this->parsingState->getNodeFromStack($rootNode), 'Node returned from stack was not the right one.');
        $this->assertSame($rootNode, $this->parsingState->popNodeFromStack($rootNode), 'Node popped from stack was not the right one.');
    }

    /**
     * @test
     */
    public function renderCallsTheRightMethodsOnTheRootNode()
    {
        $renderingContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface::class);

        $rootNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode::class);
        $rootNode->expects($this->once())->method('evaluate')->with($renderingContext)->will($this->returnValue('T3DD09 Rock!'));
        $this->parsingState->setRootNode($rootNode);
        $renderedValue = $this->parsingState->render($renderingContext);
        $this->assertEquals($renderedValue, 'T3DD09 Rock!', 'The rendered value of the Root Node is not returned by the ParsingState.');
    }
}
