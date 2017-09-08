<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\ViewHelper;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Test case
 */
class AbstractViewHelperTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var array
     */
    protected $fixtureMethodParameters = [
        'param1' => [
            'position' => 0,
            'optional' => false,
            'type' => 'integer',
            'defaultValue' => null
        ],
        'param2' => [
            'position' => 1,
            'optional' => false,
            'type' => 'array',
            'array' => true,
            'defaultValue' => null
        ],
        'param3' => [
            'position' => 2,
            'optional' => true,
            'type' => 'string',
            'array' => false,
            'defaultValue' => 'default'
        ],
    ];

    /**
     * @var array
     */
    protected $fixtureMethodTags = [
        'param' => [
            'integer $param1 P1 Stuff',
            'array $param2 P2 Stuff',
            'string $param3 P3 Stuff'
        ]
    ];

    protected function setUp()
    {
        $this->mockReflectionService = $this->createMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
    }

    /**
     * @test
     */
    public function prepareArgumentsCallsInitializeArguments()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render', 'initializeArguments'], [], '', false);

        $viewHelper->expects($this->once())->method('initializeArguments');

        $viewHelper->prepareArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsCallsPrepareArguments()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);

        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue([]));

        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsAcceptsAllObjectsImplemtingArrayAccessAsAnArray()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);

        $viewHelper->setArguments(['test' => new \ArrayObject]);
        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(['test' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('test', 'array', false, 'documentation')]));
        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsCallsTheRightValidators()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);

        $viewHelper->setArguments(['test' => 'Value of argument']);

        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue([
            'test' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('test', 'string', false, 'documentation')
        ]));

        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsCallsTheRightValidatorsAndThrowsExceptionIfValidationIsWrong()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1256475113);

        $viewHelper->setArguments(['test' => 'test']);

        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue([
            'test' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('test', 'stdClass', false, 'documentation')
        ]));

        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderCallsTheCorrectSequenceOfMethods()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['validateArguments', 'initialize', 'callRenderMethod']);
        $viewHelper->expects($this->at(0))->method('validateArguments');
        $viewHelper->expects($this->at(1))->method('initialize');
        $viewHelper->expects($this->at(2))->method('callRenderMethod')->will($this->returnValue('Output'));

        $expectedOutput = 'Output';
        $actualOutput = $viewHelper->initializeArgumentsAndRender(['argument1' => 'value1']);
        $this->assertEquals($expectedOutput, $actualOutput);
    }

    /**
     * @test
     */
    public function setRenderingContextShouldSetInnerVariables()
    {
        $templateVariableContainer = $this->createMock(StandardVariableProvider::class);
        $viewHelperVariableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $controllerContext = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $controllerContext->expects($this->atLeastOnce())->method('getRequest')->willReturn($this->createMock(Request::class));

        $renderingContext = $this->getAccessibleMock(RenderingContextFixture::class, ['getControllerContext']);
        $renderingContext->expects($this->any())->method('getControllerContext')->willReturn($controllerContext);
        $renderingContext->setVariableProvider($templateVariableContainer);
        $renderingContext->_set('viewHelperVariableContainer', $viewHelperVariableContainer);
        $renderingContext->setControllerContext($controllerContext);

        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);

        $viewHelper->setRenderingContext($renderingContext);

        $this->assertSame($viewHelper->_get('templateVariableContainer'), $templateVariableContainer);
        $this->assertSame($viewHelper->_get('viewHelperVariableContainer'), $viewHelperVariableContainer);
        $this->assertSame($viewHelper->_get('controllerContext'), $controllerContext);
    }
}
