<?php
namespace TYPO3\CMS\Fluid\Tests\UnitDeprecated\Core\ViewHelper;

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
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Fluid\Core\Exception;
use TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures\TestViewHelper;

/**
 * Test case for deprecated functionality to still behave as before
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
     * @dataProvider getCallRenderMethodTestValues
     * @param array $arguments
     * @param bool $expectsException
     */
    public function registerRenderMethodArgumentsThrowsExceptionOnMissingType(array $arguments, $expectsException = false)
    {
        $reflectionService = $this->getMockBuilder(ReflectionService::class)
            ->setMethods(['getMethodParameters', 'getMethodTagsValues'])
            ->getMock();
        $reflectionService->expects($this->once())->method('getMethodParameters')->willReturn(
            [
                'param1' => [
                    'position' => 0,
                    'byReference' => false,
                    'optional' => false,
                    'allowsNull' => true
                ]
            ]
        );
        $reflectionService->expects($this->once())->method('getMethodTagsValues')->willReturn([]);
        $fixture = $this->getAccessibleMock(TestViewHelper::class, ['render', 'getReflectionService']);
        $fixture->expects($this->once())->method('getReflectionService')->willReturn($reflectionService);
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
            ->setMethods(['getMethodParameters', 'getMethodTagsValues'])
            ->getMock();
        $reflectionService->expects($this->once())->method('getMethodParameters')->willReturn(
            [
                'param1' => [
                    'position' => 0,
                    'type' => 'integer',
                    'byReference' => false,
                    'array' => false,
                    'optional' => false,
                    'allowsNull' => true
                ],
                'param2' => [
                    'position' => 1,
                    'type' => 'array',
                    'byReference' => false,
                    'array' => true,
                    'optional' => false,
                    'allowsNull' => true
                ],
                'param3' => [
                    'position' => 2,
                    'type' => 'string',
                    'byReference' => false,
                    'array' => false,
                    'optional' => false,
                    'allowsNull' => true
                ],
            ]
        );
        $reflectionService->expects($this->once())->method('getMethodTagsValues')->willReturn(
            []
        );
        $fixture = $this->getAccessibleMock(TestViewHelper::class, ['render', 'getReflectionService']);
        $namedArguments = array_combine(['param1', 'param2', 'param3'], $arguments);
        $fixture->expects($this->once())->method('getReflectionService')->willReturn($reflectionService);
        $this->callInaccessibleMethod($fixture, 'registerRenderMethodArguments');
        $fixture->setArguments($namedArguments);
        if ($expectsException) {
            $exception = new \TYPO3Fluid\Fluid\Core\ViewHelper\Exception('test', 1476108352);
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
        return [
            [[3, ['bar'], 'baz'], false],
            [[2, ['baz'], 'bar'], false],
            [[3, ['bar'], 'baz'], true],
        ];
    }

    /**
     * @test
     */
    public function prepareArgumentsRegistersAnnotationBasedArgumentsWithDescriptionIfDebugModeIsEnabled()
    {
        $dataCacheMock = $this->createMock(\TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class);
        $dataCacheMock->expects($this->any())->method('has')->will($this->returnValue(true));
        $dataCacheMock->expects($this->any())->method('get')->will($this->returnValue([]));

        $reflectionServiceMock = $this->getMockBuilder(ReflectionService::class)->getMock();
        $reflectionServiceMock->expects($this->once())->method('getMethodParameters')->willReturn(['fake' => ['type' => 'int', 'defaultValue' => 'def']]);
        $reflectionServiceMock->expects($this->once())->method('getMethodTagsValues')->willReturn(['param']);

        $viewHelper = $this->getMockBuilder(\TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures\TestViewHelper::class)->setMethods(['getReflectionService'])->getMock();
        $viewHelper->expects($this->once())->method('getReflectionService')->willReturn($reflectionServiceMock);

        $expected = [
            'fake' => new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('fake', 'int', '', false, 'def', true)
        ];

        $this->callInaccessibleMethod($viewHelper, 'registerRenderMethodArguments');
        $this->assertAttributeEquals($expected, 'argumentDefinitions', $viewHelper, 'Annotation based arguments were not registered.');
    }
}
