<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation;

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

/**
 * Test case
 */
class ValidatorResolverTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver | \PHPUnit_Framework_MockObject_MockObject | \TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $validatorResolver;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    protected function setUp()
    {
        $this->validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['dummy']);
        $this->mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->validatorResolver->_set('objectManager', $this->mockObjectManager);
    }

    /****************/

    /**
     * @test
     */
    public function resolveValidatorObjectNameWithShortHandNotationReturnsValidatorNameIfClassExists()
    {
        $extensionName = 'tx_foo';
        $className = $this->getUniqueId('Foo');
        $realClassName = 'Tx_' . $extensionName . '_Validation_Validator_' . $className . 'Validator';
        $validatorName = $extensionName . ':' . $className;
        eval('class ' . $realClassName . ' implements TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface {
		public function validate($value){} public function getOptions(){}
		}');
        $this->assertEquals($realClassName, $this->validatorResolver->_call('resolveValidatorObjectName', $validatorName));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameWithShortHandNotationThrowsExceptionIfClassNotExists()
    {
        $className = $this->getUniqueId('Foo');
        $validatorName = 'tx_foo:' . $className;
        $this->setExpectedException(\TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException::class, '', 1365799920);
        $this->validatorResolver->_call('resolveValidatorObjectName', $validatorName);
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameWithShortHandNotationReturnsValidatorNameIfClassExistsButDoesNotImplementValidatorInterface()
    {
        $extensionName = 'tx_foo';
        $className = $this->getUniqueId('Foo');
        $realClassName = 'Tx_' . $extensionName . '_Validation_Validator_' . $className . 'Validator';
        $validatorName = $extensionName . ':' . $className;
        eval('class ' . $realClassName . '{}');
        $this->setExpectedException(\TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException::class, '', 1365776838);
        $this->validatorResolver->_call('resolveValidatorObjectName', $validatorName);
    }

    /****************/

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsValidatorNameIfClassExists()
    {
        $className = $this->getUniqueId('Foo_');
        $expectedValidatorName = $className . 'Validator';
        eval('class ' . $expectedValidatorName . ' implements TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface {
		public function validate($value){} public function getOptions(){}
		}');
        $this->assertEquals(
            $expectedValidatorName,
            $this->validatorResolver->_call('resolveValidatorObjectName', $className)
        );
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameThrowsNoSuchValidatorExceptionIfClassNotExists()
    {
        $className = $this->getUniqueId('Foo');
        $this->setExpectedException(\TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException::class, '', 1365799920);
        $this->validatorResolver->_call('resolveValidatorObjectName', $className);
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameThrowsNoSuchValidatorExceptionIfClassExistsButDoesNotImplementValidatorInterface()
    {
        $className = $this->getUniqueId('Foo_');
        $expectedValidatorName = $className . 'Validator';
        eval('class ' . $expectedValidatorName . '{}');
        $this->setExpectedException(\TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException::class, '', 1365776838);
        $this->validatorResolver->_call('resolveValidatorObjectName', $className);
    }

    /****************/

    /**
     * @return array
     */
    public function namespacedShorthandValidatornames()
    {
        return [
            ['TYPO3\\CMS\\Mypkg\\Validation\\Validator', 'MySecondValidator', 'TYPO3.CMS.Mypkg:MySecond'],
            ['Acme\\Mypkg\\Validation\\Validator', 'MyThirdValidator', 'Acme.Mypkg:MyThird']
        ];
    }

    /**
     * @param string $namespace
     * @param string $className
     * @param string $shorthandValidatorname
     *
     * @test
     * @dataProvider namespacedShorthandValidatornames
     */
    public function resolveValidatorObjectNameCanResolveNamespacedShorthandValidatornames($namespace, $className, $shorthandValidatorname)
    {
        eval('namespace ' . $namespace . '; class ' . $className . ' implements \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface {
		public function validate($value){} public function getOptions(){}
		}');
        $this->assertSame($namespace . '\\' . $className, $this->validatorResolver->_call('resolveValidatorObjectName', $shorthandValidatorname));
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators()
    {
        eval('namespace TYPO3\\CMS\\Extbase\\Validation\\Validator;' . LF . 'class FooValidator implements \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface {
		public function validate($value){} public function getOptions(){}
		}');
        $this->assertSame('TYPO3\\CMS\\Extbase\\Validation\\Validator\\FooValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
    }

    /**
     * @test
     */
    public function createValidatorResolvesAndReturnsAValidatorAndPassesTheGivenOptions()
    {
        $className = $this->getUniqueId('Test');
        $mockValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ObjectValidatorInterface::class, ['validate', 'getOptions', 'setValidatedInstancesContainer'], [], $className);
        $this->mockObjectManager->expects($this->any())->method('get')->with($className)->will($this->returnValue($mockValidator));
        /** @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver */
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['resolveValidatorObjectName']);
        $validatorResolver->_set('objectManager', $this->mockObjectManager);
        $validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className)->will($this->returnValue($className));
        $validator = $validatorResolver->createValidator($className);
        $this->assertSame($mockValidator, $validator);
    }

    /**
     * @test
     */
    public function createValidatorThrowsNoSuchValidatorExceptionIfAValidatorCouldNotBeResolved()
    {
        $this->markTestSkipped('');
        $className = $this->getUniqueId('Test');
        $this->setExpectedException(\TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException::class, '', 1365799920);
        $this->validatorResolver->createValidator($className);
    }

    /**
     * @test
     */
    public function getBaseValidatorCachesTheResultOfTheBuildBaseValidatorChainCalls()
    {
        $this->markTestSkipped('Functionality is different now.');
        $mockConjunctionValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class, [], [], '', false);
        $validatorResolver = $this->getMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['buildBaseValidatorConjunction'], [], '', false);
        $validatorResolver->expects($this->once())->method('buildBaseValidatorConjunction')->with('Tx_Virtual_Foo')->will($this->returnValue($mockConjunctionValidator));
        $result = $validatorResolver->getBaseValidatorConjunction('Tx_Virtual_Foo');
        $this->assertSame($mockConjunctionValidator, $result, '#1');
        $result = $validatorResolver->getBaseValidatorConjunction('Tx_Virtual_Foo');
        $this->assertSame($mockConjunctionValidator, $result, '#2');
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyArrayIfMethodHasNoArguments()
    {
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['fooAction'], [], '', false);
        $methodParameters = [];
        $mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, [], [], '', false);
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockController), 'fooAction');
        $this->assertSame([], $result);
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsBuildsAConjunctionFromValidateAnnotationsOfTheSpecifiedMethod()
    {
        $mockObject = $this->getMock('stdClass', ['fooMethod'], [], '', false);
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
        $mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, [], [], '', false);
        $mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodTagsValues));
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
        $mockStringValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, [], [], '', false);
        $mockArrayValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, [], [], '', false);
        $mockFooValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, [], [], '', false);
        $mockBarValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, [], [], '', false);
        $mockQuuxValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, [], [], '', false);
        $conjunction1 = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class, [], [], '', false);
        $conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
        $conjunction1->expects($this->at(1))->method('addValidator')->with($mockFooValidator);
        $conjunction1->expects($this->at(2))->method('addValidator')->with($mockBarValidator);
        $conjunction2 = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class, [], [], '', false);
        $conjunction2->expects($this->at(0))->method('addValidator')->with($mockArrayValidator);
        $conjunction2->expects($this->at(1))->method('addValidator')->with($mockQuuxValidator);
        $mockArguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $mockArguments->addArgument(new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('arg1', 'dummyValue'));
        $mockArguments->addArgument(new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('arg2', 'dummyValue'));
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['createValidator']);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction1));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction2));
        $validatorResolver->expects($this->at(3))->method('createValidator')->with('array')->will($this->returnValue($mockArrayValidator));
        $validatorResolver->expects($this->at(4))->method('createValidator')->with('Foo', ['bar' => 'baz'])->will($this->returnValue($mockFooValidator));
        $validatorResolver->expects($this->at(5))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
        $validatorResolver->expects($this->at(6))->method('createValidator')->with('VENDOR\\ModelCollection\\Domain\\Model\\Model')->will($this->returnValue($mockQuuxValidator));
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
        $this->assertEquals(['arg1' => $conjunction1, 'arg2' => $conjunction2], $result);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException
     */
    public function buildMethodArgumentsValidatorConjunctionsThrowsExceptionIfValidationAnnotationForNonExistingArgumentExists()
    {
        $mockObject = $this->getMock('stdClass', ['fooMethod'], [], '', false);
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
        $mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, [], [], '', false);
        $mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodTagsValues));
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
        $mockStringValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, [], [], '', false);
        $mockQuuxValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, [], [], '', false);
        $conjunction1 = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class, [], [], '', false);
        $conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['createValidator']);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction1));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with('VENDOR\\ModelCollection\\Domain\\Model\\Model')->will($this->returnValue($mockQuuxValidator));
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionAddsValidatorsDefinedByAnnotationsInTheClassToTheReturnedConjunction()
    {
        $mockObject = $this->getMock('stdClass');
        $className = get_class($mockObject);
        $propertyTagsValues = [
            'foo' => [
                'var' => ['string'],
                'validate' => [
                    'Foo(bar= baz), Bar',
                    'Baz'
                ]
            ],
            'bar' => [
                'var' => ['integer'],
                'validate' => [
                    'VENDOR\\ModelCollection\\Domain\\Validator\\ModelValidator'
                ]
            ]
        ];

        $mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, [], [], '', false);
        $mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(['foo', 'bar']));
        $mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($className, 'foo')->will($this->returnValue($propertyTagsValues['foo']));
        $mockReflectionService->expects($this->at(2))->method('getPropertyTagsValues')->with($className, 'bar')->will($this->returnValue($propertyTagsValues['bar']));
        $mockObjectValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator::class, ['dummy'], [], '', false);
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class, [], [], '', false);
        $mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator::class)->will($this->returnValue($mockObjectValidator));
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with('Foo', ['bar' => 'baz'])->will($this->returnValue($mockObjectValidator));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('Bar')->will($this->returnValue($mockObjectValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with('Baz')->will($this->returnValue($mockObjectValidator));
        $validatorResolver->expects($this->at(3))->method('createValidator')->with('VENDOR\\ModelCollection\\Domain\\Validator\\ModelValidator')->will($this->returnValue($mockObjectValidator));
        $validatorResolver->_call('buildBaseValidatorConjunction', $className, $className);
    }

    /**
     * dataProvider for buildBaseValidatorConjunctionAddsValidatorFromConventionToTheReturnedConjunction
     *
     * @return array
     */
    public function modelNamesProvider()
    {
        return [
            'no replace' => ['VENDOR\\ModelCollection\\Domain\\Model\\Model', 'VENDOR\\ModelCollection\\Domain\\Validator\\ModelValidator'],
            'replace in not namespaced class' => ['Tx_ModelCollection_Domain_Model_Model', 'Tx_ModelCollection_Domain_Validator_ModelValidator'],
            'replace in namespaced class' => ['VENDOR\\ModelCollection\\Domain\\Model\\Model', 'VENDOR\\ModelCollection\\Domain\\Validator\\ModelValidator']
        ];
    }

    /**
     * @param string $modelClassName
     * @param string $validatorClassName
     *
     * @test
     * @dataProvider modelNamesProvider
     */
    public function buildBaseValidatorConjunctionCreatesValidatorFromClassName($modelClassName, $validatorClassName)
    {
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class, [], [], '', false);
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['resolveValidatorObjectName', 'createValidator']);
        $validatorResolver->_set('objectManager', $mockObjectManager);
        $validatorResolver->expects($this->once())->method('createValidator')->with($validatorClassName)->will($this->returnValue(null));
        $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName, $modelClassName);
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameCallsGetValidatorType()
    {
        $validatorName = $this->getUniqueId('FooValidator');
        eval('namespace TYPO3\CMS\Extbase\Validation\Validator;' . LF . 'class ' . $validatorName . 'Validator implements \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface {
		public function validate($value){} public function getOptions(){}
		}');
        $mockValidatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['getValidatorType']);
        $mockValidatorResolver->expects($this->once())->method('getValidatorType')->with($validatorName)->will($this->returnValue($validatorName));

        $mockValidatorResolver->_call('resolveValidatorObjectName', $validatorName);
    }

    /**
     * @test
     */
    public function getValidatorTypeCorrectlyRenamesPhpDataTypes()
    {
        $this->assertEquals('Integer', $this->validatorResolver->_call('getValidatorType', 'integer'));
        $this->assertEquals('Integer', $this->validatorResolver->_call('getValidatorType', 'int'));
        $this->assertEquals('String', $this->validatorResolver->_call('getValidatorType', 'string'));
        $this->assertEquals('Array', $this->validatorResolver->_call('getValidatorType', 'array'));
        $this->assertEquals('Float', $this->validatorResolver->_call('getValidatorType', 'float'));
        $this->assertEquals('Float', $this->validatorResolver->_call('getValidatorType', 'double'));
        $this->assertEquals('Boolean', $this->validatorResolver->_call('getValidatorType', 'boolean'));
        $this->assertEquals('Boolean', $this->validatorResolver->_call('getValidatorType', 'bool'));
        $this->assertEquals('Boolean', $this->validatorResolver->_call('getValidatorType', 'bool'));
        $this->assertEquals('Number', $this->validatorResolver->_call('getValidatorType', 'number'));
        $this->assertEquals('Number', $this->validatorResolver->_call('getValidatorType', 'numeric'));
    }

    /**
     * @test
     */
    public function getValidatorTypeRenamesMixedToRaw()
    {
        $this->assertEquals('Raw', $this->validatorResolver->_call('getValidatorType', 'mixed'));
    }

    /**
     * dataProvider for parseValidatorAnnotationCanParseAnnotations
     * @return array
     */
    public function validatorAnnotations()
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
                '$var F3_TestPackage_Quux',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'F3_TestPackage_Quux', 'validatorOptions' => []]
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
     * @param mixed $annotation
     * @param mixed $expectedResult
     */
    public function parseValidatorAnnotationCanParseAnnotations($annotation, $expectedResult)
    {
        $result = $this->validatorResolver->_call('parseValidatorAnnotation', $annotation);
        $this->assertEquals($result, $expectedResult);
    }
}
