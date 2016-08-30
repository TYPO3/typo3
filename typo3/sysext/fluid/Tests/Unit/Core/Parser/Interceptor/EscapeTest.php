<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Parser\Interceptor;

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
class EscapeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Fluid\Core\Parser\Interceptor\Escape
     */
    protected $escapeInterceptor;

    /**
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
     */
    protected $mockViewHelper;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
     */
    protected $mockNode;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Parser\ParsingState
     */
    protected $mockParsingState;

    protected function setUp()
    {
        $this->escapeInterceptor = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\Interceptor\Escape::class, ['dummy']);
        $this->mockViewHelper = $this->getMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class);
        $this->mockNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class, [], [], '', false);
        $this->mockParsingState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
    }

    /**
     * @test
     */
    public function processDoesNotDisableEscapingInterceptorByDefault()
    {
        $interceptorPosition = \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER;
        $this->mockViewHelper->expects($this->once())->method('isEscapingInterceptorEnabled')->will($this->returnValue(true));
        $this->mockNode->expects($this->once())->method('getUninitializedViewHelper')->will($this->returnValue($this->mockViewHelper));

        $this->assertTrue($this->escapeInterceptor->_get('interceptorEnabled'));
        $this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
        $this->assertTrue($this->escapeInterceptor->_get('interceptorEnabled'));
    }

    /**
     * @test
     */
    public function processDisablesEscapingInterceptorIfViewHelperDisablesIt()
    {
        $interceptorPosition = \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER;
        $this->mockViewHelper->expects($this->once())->method('isEscapingInterceptorEnabled')->will($this->returnValue(false));
        $this->mockNode->expects($this->once())->method('getUninitializedViewHelper')->will($this->returnValue($this->mockViewHelper));

        $this->assertTrue($this->escapeInterceptor->_get('interceptorEnabled'));
        $this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
        $this->assertFalse($this->escapeInterceptor->_get('interceptorEnabled'));
    }

    /**
     * @test
     */
    public function processReenablesEscapingInterceptorOnClosingViewHelperTagIfItWasDisabledBefore()
    {
        $interceptorPosition = \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER;

        $this->escapeInterceptor->_set('interceptorEnabled', false);
        $this->escapeInterceptor->_set('viewHelperNodesWhichDisableTheInterceptor', [$this->mockNode]);

        $this->escapeInterceptor->process($this->mockNode, $interceptorPosition, $this->mockParsingState);
        $this->assertTrue($this->escapeInterceptor->_get('interceptorEnabled'));
    }

    /**
     * @test
     */
    public function processWrapsCurrentViewHelperInHtmlspecialcharsViewHelperOnObjectAccessor()
    {
        $interceptorPosition = \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_OBJECTACCESSOR;
        $mockNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::class, [], [], '', false);
        $mockEscapeViewHelper = $this->getMock(\TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper::class);
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper::class)->will($this->returnValue($mockEscapeViewHelper));
        $mockObjectManager->expects($this->at(1))->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class, $mockEscapeViewHelper, ['value' => $mockNode])->will($this->returnValue($this->mockNode));
        $this->escapeInterceptor->_set('objectManager', $mockObjectManager);

        $actualResult = $this->escapeInterceptor->process($mockNode, $interceptorPosition, $this->mockParsingState);
        $this->assertSame($this->mockNode, $actualResult);
    }
}
