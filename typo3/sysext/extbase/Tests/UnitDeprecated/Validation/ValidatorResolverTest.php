<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Validation;

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

use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ValidatorResolverTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver | \PHPUnit_Framework_MockObject_MockObject | \TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $validatorResolver;

    protected function setUp()
    {
        $this->validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['dummy']);
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyArrayIfMethodHasNoArguments()
    {
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['fooAction'], [], '', false);
        $className = get_class($mockController);
        $methodName = 'fooAction';

        $classSchemaMock = $this->createMock(ClassSchema::class);
        $classSchemaMock->expects($this->any())->method('getMethod')->with($methodName)->willReturn([
            'params' => [],
        ]);

        $mockReflectionService = $this->createMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->any())->method('getClassSchema')->with($className)->willReturn($classSchemaMock);

        /** @var ValidatorResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $validatorResolver */
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $result = $validatorResolver->buildMethodArgumentsValidatorConjunctions($className, $methodName);
        $this->assertSame([], $result);
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsBuildsAConjunctionFromValidateAnnotationsOfTheSpecifiedMethod()
    {
        $mockObject = $this->getMockBuilder('stdClass')
            ->setMethods(['fooMethod'])
            ->disableOriginalConstructor()
            ->getMock();

        $className = get_class($mockObject);
        $methodName = 'fooAction';

        $methodParameters = [
            'arg1' => [
                'type' => 'string'
            ],
            'arg2' => [
                'type' => 'array'
            ]
        ];
        $methodTagsValues = [
            'param' => [
                'string $arg1',
                'array $arg2'
            ],
            'validate' => [
                '$arg1 Foo(bar = baz), Bar',
                '$arg2 VENDOR\\ModelCollection\\Domain\\Model\\Model'
            ]
        ];

        $classSchemaMock = $this->createMock(ClassSchema::class);
        $classSchemaMock->expects($this->any())->method('getMethod')->with($methodName)->willReturn([
            'params' => $methodParameters,
            'tags' => $methodTagsValues
        ]);

        $mockReflectionService = $this->createMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->any())->method('getClassSchema')->with($className)->willReturn($classSchemaMock);

        $mockStringValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $mockArrayValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $mockFooValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $mockBarValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $mockQuuxValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $conjunction1 = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class);
        $conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
        $conjunction1->expects($this->at(1))->method('addValidator')->with($mockFooValidator);
        $conjunction1->expects($this->at(2))->method('addValidator')->with($mockBarValidator);
        $conjunction2 = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class);
        $conjunction2->expects($this->at(0))->method('addValidator')->with($mockArrayValidator);
        $conjunction2->expects($this->at(1))->method('addValidator')->with($mockQuuxValidator);
        $mockArguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $mockArguments->addArgument(new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('arg1', 'dummyValue'));
        $mockArguments->addArgument(new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('arg2', 'dummyValue'));

        /** @var ValidatorResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $validatorResolver */
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['createValidator']);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction1));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction2));
        $validatorResolver->expects($this->at(3))->method('createValidator')->with('array')->will($this->returnValue($mockArrayValidator));
        $validatorResolver->expects($this->at(4))->method('createValidator')->with('Foo', ['bar' => 'baz'])->will($this->returnValue($mockFooValidator));
        $validatorResolver->expects($this->at(5))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
        $validatorResolver->expects($this->at(6))->method('createValidator')->with('VENDOR\\ModelCollection\\Domain\\Model\\Model')->will($this->returnValue($mockQuuxValidator));
        $validatorResolver->_set('reflectionService', $mockReflectionService);

        $result = $validatorResolver->buildMethodArgumentsValidatorConjunctions($className, $methodName);
        $this->assertEquals(['arg1' => $conjunction1, 'arg2' => $conjunction2], $result);
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsThrowsExceptionIfValidationAnnotationForNonExistingArgumentExists()
    {
        $this->expectException(InvalidValidationConfigurationException::class);
        $this->expectExceptionCode(1253172726);
        $mockObject = $this->getMockBuilder('stdClass')
            ->setMethods(['fooMethod'])
            ->disableOriginalConstructor()
            ->getMock();

        $className = get_class($mockObject);
        $methodName = 'fooAction';

        $methodParameters = [
            'arg1' => [
                'type' => 'string'
            ]
        ];
        $methodTagsValues = [
            'param' => [
                'string $arg1'
            ],
            'validate' => [
                '$arg2 VENDOR\\ModelCollection\\Domain\\Model\\Model'
            ]
        ];

        $classSchemaMock = $this->createMock(ClassSchema::class);
        $classSchemaMock->expects($this->any())->method('getMethod')->with($methodName)->willReturn([
            'params' => $methodParameters,
            'tags' => $methodTagsValues
        ]);

        $mockReflectionService = $this->createMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->any())->method('getClassSchema')->with($className)->willReturn($classSchemaMock);

        $mockStringValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $mockQuuxValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $conjunction1 = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class);
        $conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);

        /** @var ValidatorResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $validatorResolver */
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['createValidator']);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction1));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with('VENDOR\\ModelCollection\\Domain\\Model\\Model')->will($this->returnValue($mockQuuxValidator));
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->buildMethodArgumentsValidatorConjunctions($className, $methodName);
    }

    /**
     * dataProvider for parseValidatorAnnotationCanParseAnnotations
     * @return array
     */
    public function validatorAnnotations(): array
    {
        return [
            [
                '$var Bar',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Bar', 'validatorOptions' => []]
                    ]
                ]
            ],
            [
                '$var Bar, Foo',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Bar', 'validatorOptions' => []],
                        ['validatorName' => 'Foo', 'validatorOptions' => []]
                    ]
                ]
            ],
            [
                '$var Baz (Foo=Bar)',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Baz', 'validatorOptions' => ['Foo' => 'Bar']]
                    ]
                ]
            ],
            [
                '$var Buzz (Foo="B=a, r", Baz=1)',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Buzz', 'validatorOptions' => ['Foo' => 'B=a, r', 'Baz' => '1']]
                    ]
                ]
            ],
            [
                '$var Foo(Baz=1, Bar=Quux)',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Foo', 'validatorOptions' => ['Baz' => '1', 'Bar' => 'Quux']]
                    ]
                ]
            ],
            [
                '$var Pax, Foo(Baz = \'1\', Bar = Quux)',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Pax', 'validatorOptions' => []],
                        ['validatorName' => 'Foo', 'validatorOptions' => ['Baz' => '1', 'Bar' => 'Quux']]
                    ]
                ]
            ],
            [
                '$var Reg (P="[at]*(h|g)"), Quux',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Reg', 'validatorOptions' => ['P' => '[at]*(h|g)']],
                        ['validatorName' => 'Quux', 'validatorOptions' => []]
                    ]
                ]
            ],
            [
                '$var Baz (Foo="B\\"ar")',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Baz', 'validatorOptions' => ['Foo' => 'B"ar']]
                    ]
                ]
            ],
            [
                '$var Baz(Foo="5"), Bar(Quux="123")',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Baz', 'validatorOptions' => ['Foo' => '5']],
                        ['validatorName' => 'Bar', 'validatorOptions' => ['Quux' => '123']]
                    ]
                ]
            ],
            [
                '$var Baz(Foo="2"), Bar(Quux=123, Pax="a weird \\"string\\" with *freaky* \\stuff")',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Baz', 'validatorOptions' => ['Foo' => '2']],
                        ['validatorName' => 'Bar', 'validatorOptions' => ['Quux' => '123', 'Pax' => 'a weird "string" with *freaky* \\stuff']]
                    ]
                ]
            ],
            'namespaced validator class name' => [
                'annotation' => '$var F3\TestPackage\Quux',
                'expected' => [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'F3\TestPackage\Quux', 'validatorOptions' => []]
                    ]
                ]
            ],
            'shorthand notation for system validator' => [
                'annotation' => '$var TYPO3.CMS.Mypkg:MySecond',
                'expected' => [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'TYPO3.CMS.Mypkg:MySecond', 'validatorOptions' => []]
                    ]
                ]
            ],
            'shorthand notation for custom validator with parameter' => [
                'annotation' => '$var Acme.Mypkg:MyThird(Foo="2")',
                'expected' => [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Acme.Mypkg:MyThird', 'validatorOptions' => ['Foo' => '2']]
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validatorAnnotations
     *
     * @param mixed $annotation
     * @param mixed $expectedResult
     */
    public function parseValidatorAnnotationCanParseAnnotations($annotation, $expectedResult)
    {
        $result = $this->validatorResolver->_call('parseValidatorAnnotation', $annotation);
        static::assertEquals($result, $expectedResult);
    }
}
