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
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Fluid\Core\Exception;
use TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures\TestViewHelper;
use TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture;

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
    protected $fixtureMethodParameters = array(
        'param1' => array(
            'position' => 0,
            'optional' => false,
            'type' => 'integer',
            'defaultValue' => null
        ),
        'param2' => array(
            'position' => 1,
            'optional' => false,
            'type' => 'array',
            'array' => true,
            'defaultValue' => null
        ),
        'param3' => array(
            'position' => 2,
            'optional' => true,
            'type' => 'string',
            'array' => false,
            'defaultValue' => 'default'
        ),
    );

    /**
     * @var array
     */
    protected $fixtureMethodTags = array(
        'param' => array(
            'integer $param1 P1 Stuff',
            'array $param2 P2 Stuff',
            'string $param3 P3 Stuff'
        )
    );

    protected function setUp()
    {
        $this->mockReflectionService = $this->createMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
    }

    /**
     * @test
     * @dataProvider getCallRenderMethodTestValues
     * @param array $arguments
     * @param bool $expectsException
     */
    public function registerRenderMethodArgumentsThrowsExceptionOnMissingType(array $arguments, $expectsException = false)
    {
        $reflectionService = $this->getMockBuilder(ReflectionService::class)
            ->setMethods(array('getMethodParameters', 'getMethodTagsValues'))
            ->getMock();
        $reflectionService->expects($this->once())->method('getMethodParameters')->willReturn(
            array(
                'param1' => array(
                    'position' => 0,
                    'byReference' => false,
                    'optional' => false,
                    'allowsNull' => true
                )
            )
        );
        $reflectionService->expects($this->once())->method('getMethodTagsValues')->willReturn(array());
        $fixture = $this->getAccessibleMock(TestViewHelper::class, array('render'));
        $fixture->injectReflectionService($reflectionService);
        $this->expectException(Exception::class);
        $this->callInaccessibleMethod($fixture, 'registerRenderMethodArguments');
    }

    /**
     * @test
     * @dataProvider getCallRenderMethodTestValues
     * @param array $arguments
     * @param bool $expectsException
     */
    public function callRenderMethodBehavesAsExpected(array $arguments, $expectsException = false)
    {
        $reflectionService = $this->getMockBuilder(ReflectionService::class)
            ->setMethods(array('getMethodParameters', 'getMethodTagsValues'))
            ->getMock();
        $reflectionService->expects($this->once())->method('getMethodParameters')->willReturn(
            array(
                'param1' => array(
                    'position' => 0,
                    'type' => 'integer',
                    'byReference' => false,
                    'array' => false,
                    'optional' => false,
                    'allowsNull' => true
                ),
                'param2' => array(
                    'position' => 1,
                    'type' => 'array',
                    'byReference' => false,
                    'array' => true,
                    'optional' => false,
                    'allowsNull' => true
                ),
                'param3' => array(
                    'position' => 2,
                    'type' => 'string',
                    'byReference' => false,
                    'array' => false,
                    'optional' => false,
                    'allowsNull' => true
                ),
            )
        );
        $reflectionService->expects($this->once())->method('getMethodTagsValues')->willReturn(
            array()
        );
        $fixture = $this->getAccessibleMock(TestViewHelper::class, array('render'));
        $namedArguments = array_combine(array('param1', 'param2', 'param3'), $arguments);
        $fixture->injectReflectionService($reflectionService);
        $this->callInaccessibleMethod($fixture, 'registerRenderMethodArguments');
        $fixture->setArguments($namedArguments);
        if ($expectsException) {
            $exception = new \TYPO3Fluid\Fluid\Core\ViewHelper\Exception('test');
            $this->expectException(get_class($exception));
            $fixture->expects($this->once())->method('render')->willThrowException($exception);
            $this->assertEquals('test', $this->callInaccessibleMethod($fixture, 'callRenderMethod'));
        } else {
            $fixture->expects($this->once())
                ->method('render')
                ->with($arguments[0], $arguments[1], $arguments[2])
                ->willReturn('okay');
            $this->assertEquals('okay', $this->callInaccessibleMethod($fixture, 'callRenderMethod'));
        }
    }

    /**
     * @return array
     */
    public function getCallRenderMethodTestValues()
    {
        return array(
            array(array(3, array('bar'), 'baz'), false),
            array(array(2, array('baz'), 'bar'), false),
            array(array(3, array('bar'), 'baz'), true),
        );
    }

    /**
     * @test
     */
    public function argumentsCanBeRegistered()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, array('render'), array(), '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $name = 'This is a name';
        $description = 'Example desc';
        $type = 'string';
        $isRequired = true;
        $expected = new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition($name, $type, $description, $isRequired);

        $viewHelper->_call('registerArgument', $name, $type, $description, $isRequired);
        $this->assertEquals(array($name => $expected), $viewHelper->prepareArguments(), 'Argument definitions not returned correctly.');
    }

    /**
     * @test
     */
    public function registeringTheSameArgumentNameAgainThrowsException()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, array('render'), array(), '', false);

        $name = 'shortName';
        $description = 'Example desc';
        $type = 'string';
        $isRequired = true;

        $this->expectException(\TYPO3\CMS\Fluid\Core\ViewHelper\Exception::class);
        $this->expectExceptionCode(1253036401);

        $viewHelper->_call('registerArgument', $name, $type, $description, $isRequired);
        $viewHelper->_call('registerArgument', $name, 'integer', $description, $isRequired);
    }

    /**
     * @test
     */
    public function overrideArgumentOverwritesExistingArgumentDefinition()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, array('render'), array(), '', false);
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
        $this->assertEquals($viewHelper->prepareArguments(), array($name => $expected), 'Argument definitions not returned correctly. The original ArgumentDefinition could not be overridden.');
    }

    /**
     * @test
     */
    public function overrideArgumentThrowsExceptionWhenTryingToOverwriteAnNonexistingArgument()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, array('render'), array(), '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $this->expectException(\TYPO3\CMS\Fluid\Core\ViewHelper\Exception::class);
        $this->expectExceptionCode(1279212461);

        $viewHelper->_call('overrideArgument', 'argumentName', 'string', 'description', true);
    }

    /**
     * @test
     */
    public function prepareArgumentsCallsInitializeArguments()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, array('render', 'initializeArguments'), array(), '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $viewHelper->expects($this->once())->method('initializeArguments');

        $viewHelper->prepareArguments();
    }

    /**
     * @test
     */
    public function prepareArgumentsRegistersAnnotationBasedArgumentsWithDescriptionIfDebugModeIsEnabled()
    {
        $dataCacheMock = $this->createMock(\TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class);
        $dataCacheMock->expects($this->any())->method('has')->will($this->returnValue(true));
        $dataCacheMock->expects($this->any())->method('get')->will($this->returnValue(array()));

        $viewHelper = new \TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures\TestViewHelper();

        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with(\TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures\TestViewHelper::class, 'render')->will($this->returnValue($this->fixtureMethodParameters));
        $this->mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(\TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures\TestViewHelper::class, 'render')->will($this->returnValue($this->fixtureMethodTags));
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $expected = array(
            'param1' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('param1', 'integer', 'P1 Stuff', true, null, true),
            'param2' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('param2', 'array', 'P2 Stuff', true, null, true),
            'param3' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('param3', 'string', 'P3 Stuff', false, 'default', true),
        );

        $this->assertEquals($expected, $viewHelper->prepareArguments(), 'Annotation based arguments were not registered.');
    }

    /**
     * @test
     */
    public function validateArgumentsCallsPrepareArguments()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, array('render', 'prepareArguments'), array(), '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array()));

        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsAcceptsAllObjectsImplemtingArrayAccessAsAnArray()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, array('render', 'prepareArguments'), array(), '', false);

        $viewHelper->setArguments(array('test' => new \ArrayObject));
        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array('test' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('test', 'array', false, 'documentation'))));
        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsCallsTheRightValidators()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, array('render', 'prepareArguments'), array(), '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $viewHelper->setArguments(array('test' => 'Value of argument'));

        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array(
            'test' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('test', 'string', false, 'documentation')
        )));

        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsCallsTheRightValidatorsAndThrowsExceptionIfValidationIsWrong()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, array('render', 'prepareArguments'), array(), '', false);
        $viewHelper->injectReflectionService($this->mockReflectionService);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1256475113);

        $viewHelper->setArguments(array('test' => 'test'));

        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array(
            'test' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('test', 'stdClass', false, 'documentation')
        )));

        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderCallsTheCorrectSequenceOfMethods()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, array('validateArguments', 'initialize', 'callRenderMethod'));
        $viewHelper->expects($this->at(0))->method('validateArguments');
        $viewHelper->expects($this->at(1))->method('initialize');
        $viewHelper->expects($this->at(2))->method('callRenderMethod')->will($this->returnValue('Output'));

        $expectedOutput = 'Output';
        $actualOutput = $viewHelper->initializeArgumentsAndRender(array('argument1' => 'value1'));
        $this->assertEquals($expectedOutput, $actualOutput);
    }

    /**
     * @test
     */
    public function setRenderingContextShouldSetInnerVariables()
    {
        $templateVariableContainer = $this->createMock(\TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer::class);
        $viewHelperVariableContainer = $this->createMock(\TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer::class);
        $controllerContext = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class)
            ->setMethods(array('getRequest'))
            ->disableOriginalConstructor()
            ->getMock();
        $controllerContext->expects($this->atLeastOnce())->method('getRequest')->willReturn($this->createMock(Request::class));

        $renderingContext = $this->getAccessibleMock(RenderingContextFixture::class, array('getControllerContext'));
        $renderingContext->expects($this->any())->method('getControllerContext')->willReturn($controllerContext);
        $renderingContext->setVariableProvider($templateVariableContainer);
        $renderingContext->_set('viewHelperVariableContainer', $viewHelperVariableContainer);
        $renderingContext->setControllerContext($controllerContext);

        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class, array('render', 'prepareArguments'), array(), '', false);

        $viewHelper->setRenderingContext($renderingContext);

        $this->assertSame($viewHelper->_get('templateVariableContainer'), $templateVariableContainer);
        $this->assertSame($viewHelper->_get('viewHelperVariableContainer'), $viewHelperVariableContainer);
        $this->assertSame($viewHelper->_get('controllerContext'), $controllerContext);
    }
}
