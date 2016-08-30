<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\ViewHelper;

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
class AbstractViewHelperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $mockReflectionService;

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
        $this->mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, [], [], '', false);
    }

    /**
     * @test
     */
    public function argumentsCanBeRegistered()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render'], [], '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $name = 'This is a name';
        $description = 'Example desc';
        $type = 'string';
        $isRequired = true;
        $expected = new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition($name, $type, $description, $isRequired);

        $viewHelper->_call('registerArgument', $name, $type, $description, $isRequired);
        $this->assertEquals([$name => $expected], $viewHelper->prepareArguments(), 'Argument definitions not returned correctly.');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function registeringTheSameArgumentNameAgainThrowsException()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render'], [], '', false);

        $name = 'shortName';
        $description = 'Example desc';
        $type = 'string';
        $isRequired = true;

        $viewHelper->_call('registerArgument', $name, $type, $description, $isRequired);
        $viewHelper->_call('registerArgument', $name, 'integer', $description, $isRequired);
    }

    /**
     * @test
     */
    public function overrideArgumentOverwritesExistingArgumentDefinition()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render'], [], '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $name = 'argumentName';
        $description = 'argument description';
        $overriddenDescription = 'overwritten argument description';
        $type = 'string';
        $overriddenType = 'integer';
        $isRequired = true;
        $expected = new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition($name, $overriddenType, $overriddenDescription, $isRequired);

        $viewHelper->_call('registerArgument', $name, $type, $description, $isRequired);
        $viewHelper->_call('overrideArgument', $name, $overriddenType, $overriddenDescription, $isRequired);
        $this->assertEquals($viewHelper->prepareArguments(), [$name => $expected], 'Argument definitions not returned correctly. The original ArgumentDefinition could not be overridden.');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function overrideArgumentThrowsExceptionWhenTryingToOverwriteAnNonexistingArgument()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render'], [], '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $viewHelper->_call('overrideArgument', 'argumentName', 'string', 'description', true);
    }

    /**
     * @test
     */
    public function prepareArgumentsCallsInitializeArguments()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render', 'initializeArguments'], [], '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $viewHelper->expects($this->once())->method('initializeArguments');

        $viewHelper->prepareArguments();
    }

    /**
     * @test
     */
    public function prepareArgumentsRegistersAnnotationBasedArgumentsWithDescriptionIfDebugModeIsEnabled()
    {
        \TYPO3\CMS\Fluid\Fluid::$debugMode = true;

        $dataCacheMock = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class, [], [], '', false);
        $dataCacheMock->expects($this->any())->method('has')->will($this->returnValue(true));
        $dataCacheMock->expects($this->any())->method('get')->will($this->returnValue([]));

        $viewHelper = new \TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures\TestViewHelper();

        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with(\TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures\TestViewHelper::class, 'render')->will($this->returnValue($this->fixtureMethodParameters));
        $this->mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(\TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures\TestViewHelper::class, 'render')->will($this->returnValue($this->fixtureMethodTags));
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $expected = [
            'param1' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('param1', 'integer', 'P1 Stuff', true, null, true),
            'param2' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('param2', 'array', 'P2 Stuff', true, null, true),
            'param3' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('param3', 'string', 'P3 Stuff', false, 'default', true),
        ];

        $this->assertEquals($expected, $viewHelper->prepareArguments(), 'Annotation based arguments were not registered.');

        \TYPO3\CMS\Fluid\Fluid::$debugMode = false;
    }

    /**
     * @test
     */
    public function prepareArgumentsRegistersAnnotationBasedArgumentsWithoutDescriptionIfDebugModeIsDisabled()
    {
        \TYPO3\CMS\Fluid\Fluid::$debugMode = false;

        $dataCacheMock = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class, [], [], '', false);
        $dataCacheMock->expects($this->any())->method('has')->will($this->returnValue(true));
        $dataCacheMock->expects($this->any())->method('get')->will($this->returnValue([]));

        $viewHelper = new \TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures\TestViewHelper2();

        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with(\TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures\TestViewHelper2::class, 'render')->will($this->returnValue($this->fixtureMethodParameters));
        $this->mockReflectionService->expects($this->never())->method('getMethodTagsValues');
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $expected = [
            'param1' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('param1', 'integer', '', true, null, true),
            'param2' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('param2', 'array', '', true, null, true),
            'param3' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('param3', 'string', '', false, 'default', true)
        ];

        $this->assertEquals($expected, $viewHelper->prepareArguments(), 'Annotation based arguments were not registered.');
    }

    /**
     * @test
     */
    public function validateArgumentsCallsPrepareArguments()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

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
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $viewHelper->setArguments(['test' => 'Value of argument']);

        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue([
            'test' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('test', 'string', false, 'documentation')
        ]));

        $viewHelper->validateArguments();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validateArgumentsCallsTheRightValidatorsAndThrowsExceptionIfValidationIsWrong()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

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
        $templateVariableContainer = $this->getMock(\TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer::class);
        $viewHelperVariableContainer = $this->getMock(\TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer::class);
        $controllerContext = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class, [], [], '', false);

        $renderingContext = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext::class, ['dummy']);
        $renderingContext->injectTemplateVariableContainer($templateVariableContainer);
        $renderingContext->_set('viewHelperVariableContainer', $viewHelperVariableContainer);
        $renderingContext->setControllerContext($controllerContext);

        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);

        $viewHelper->setRenderingContext($renderingContext);

        $this->assertSame($viewHelper->_get('templateVariableContainer'), $templateVariableContainer);
        $this->assertSame($viewHelper->_get('viewHelperVariableContainer'), $viewHelperVariableContainer);
        $this->assertSame($viewHelper->_get('controllerContext'), $controllerContext);
    }
}
