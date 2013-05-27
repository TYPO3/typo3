<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for the validator resolver
 */
class ValidatorResolverTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver | \PHPUnit_Framework_MockObject_MockObject | \TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $validatorResolver;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager | \PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockObjectManager;

	public function setUp() {
		$this->validatorResolver = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver', array('dummy'));
		$this->mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->validatorResolver->injectObjectManager($this->mockObjectManager);
	}

	/****************/

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameWithShortHandNotationReturnsValidatorNameIfClassExists() {
		$extensionName = 'tx_foo';
		$className = uniqid('Foo');
		$realClassName = 'Tx_' . $extensionName . '_Validation_Validator_' . $className . 'Validator';
		$validatorName = $extensionName . ':' . $className;
		eval('class ' . $realClassName . ' implements TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface {}');
		$this->assertEquals($realClassName, $this->validatorResolver->_call('resolveValidatorObjectName', $validatorName));
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameWithShortHandNotationThrowsExceptionIfClassNotExists() {
		$className = uniqid('Foo');
		$validatorName = 'tx_foo:' . $className;
		$this->setExpectedException('TYPO3\\CMS\\Extbase\\Validation\\Exception\\NoSuchValidatorException', '', 1365799920);
		$this->validatorResolver->_call('resolveValidatorObjectName', $validatorName);
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameWithShortHandNotationReturnsValidatorNameIfClassExistsButDoesNotImplementValidatorInterface() {
		$extensionName = 'tx_foo';
		$className = uniqid('Foo');
		$realClassName = 'Tx_' . $extensionName . '_Validation_Validator_' . $className . 'Validator';
		$validatorName = $extensionName . ':' . $className;
		eval('class ' . $realClassName . '{}');
		$this->setExpectedException('TYPO3\\CMS\\Extbase\\Validation\\Exception\\NoSuchValidatorException', '', 1365776838);
		$this->validatorResolver->_call('resolveValidatorObjectName', $validatorName);
	}

	/****************/

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameReturnsValidatorNameIfClassExists() {
		$className = uniqid('Foo_');
		$expectedValidatorName = $className . 'Validator';
		eval('class ' . $expectedValidatorName . ' implements TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface {}');
		$this->assertEquals(
			$expectedValidatorName,
			$this->validatorResolver->_call('resolveValidatorObjectName', $className)
		);
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameThrowsNoSuchValidatorExceptionIfClassNotExists() {
		$className = uniqid('Foo');
		$this->setExpectedException('TYPO3\\CMS\\Extbase\\Validation\\Exception\\NoSuchValidatorException', '', 1365799920);
		$this->validatorResolver->_call('resolveValidatorObjectName', $className);
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameThrowsNoSuchValidatorExceptionIfClassExistsButDoesNotImplementValidatorInterface() {
		$className = uniqid('Foo_');
		$expectedValidatorName = $className . 'Validator';
		eval('class ' . $expectedValidatorName . '{}');
		$this->setExpectedException('TYPO3\\CMS\\Extbase\\Validation\\Exception\\NoSuchValidatorException', '', 1365776838);
		$this->validatorResolver->_call('resolveValidatorObjectName', $className);
	}

	/****************/

	/**
	 * @return array
	 */
	public function namespacedShorthandValidatornames() {
		return array(
			array('TYPO3\\CMS\\Mypkg\\Validation\\Validator', 'MySecondValidator', 'TYPO3.CMS.Mypkg:MySecond'),
			array('Acme\\Mypkg\\Validation\\Validator', 'MyThirdValidator', 'Acme.Mypkg:MyThird')
		);
	}

	/**
	 * @param string $namespace
	 * @param string $className
	 * @param string $shorthandValidatorname
	 *
	 * @test
	 * @dataProvider namespacedShorthandValidatornames
	 */
	public function resolveValidatorObjectNameCanResolveNamespacedShorthandValidatornames($namespace, $className, $shorthandValidatorname) {
		eval('namespace ' . $namespace . '; class ' . $className . ' implements \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface {}');
		$this->assertSame($namespace . '\\' . $className, $this->validatorResolver->_call('resolveValidatorObjectName', $shorthandValidatorname));
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators() {
		eval('namespace TYPO3\\CMS\\Extbase\\Validation\\Validator;' . LF . 'class FooValidator implements \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface {}');
		$this->assertSame('TYPO3\\CMS\\Extbase\\Validation\\Validator\\FooValidator', $this->validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 */
	public function createValidatorResolvesAndReturnsAValidatorAndPassesTheGivenOptions() {
		$className = uniqid('Test');
		$mockValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ObjectValidatorInterface', array('setOptions', 'canValidate', 'isPropertyValid'), array(), $className);
		$mockValidator->expects($this->once())->method('setOptions')->with(array('foo' => 'bar'));
		$this->mockObjectManager->expects($this->any())->method('get')->with($className)->will($this->returnValue($mockValidator));
		$validatorResolver = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver', array('resolveValidatorObjectName'));
		$validatorResolver->injectObjectManager($this->mockObjectManager);
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className)->will($this->returnValue($className));
		$validator = $validatorResolver->createValidator($className, array('foo' => 'bar'));
		$this->assertSame($mockValidator, $validator);
	}

	/**
	 * @test
	 */
	public function createValidatorThrowsNoSuchValidatorExceptionIfAValidatorCouldNotBeResolved() {
		$className = uniqid('Test');
		$this->setExpectedException('\\TYPO3\\CMS\\Extbase\\Validation\\Exception\NoSuchValidatorException', '', 1365799920);
		$this->validatorResolver->createValidator($className);
	}

	/**
	 * @test
	 */
	public function getBaseValidatorCachesTheResultOfTheBuildBaseValidatorChainCalls() {
		$mockConjunctionValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator', array(), array(), '', FALSE);
		$validatorResolver = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver', array('buildBaseValidatorConjunction'), array(), '', FALSE);
		$validatorResolver->expects($this->once())->method('buildBaseValidatorConjunction')->with('Tx_Virtual_Foo')->will($this->returnValue($mockConjunctionValidator));
		$result = $validatorResolver->getBaseValidatorConjunction('Tx_Virtual_Foo');
		$this->assertSame($mockConjunctionValidator, $result, '#1');
		$result = $validatorResolver->getBaseValidatorConjunction('Tx_Virtual_Foo');
		$this->assertSame($mockConjunctionValidator, $result, '#2');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sbastian@typo3.org>
	 */
	public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyArrayIfMethodHasNoArguments() {
		$mockController = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ActionController', array('fooAction'), array(), '', FALSE);
		$methodTagsValues = array();
		$methodParameters = array();
		$mockReflectionService = $this->getMock('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));
		$validatorResolver = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver', array('createValidator'));
		$validatorResolver->injectReflectionService($mockReflectionService);
		$result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockController), 'fooAction');
		$this->assertSame(array(), $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildMethodArgumentsValidatorConjunctionsBuildsAConjunctionFromValidateAnnotationsOfTheSpecifiedMethod() {
		$mockObject = $this->getMock('stdClass', array('fooMethod'), array(), '', FALSE);
		$methodParameters = array(
			'arg1' => array(
				'type' => 'string'
			),
			'arg2' => array(
				'type' => 'array'
			)
		);
		$methodTagsValues = array(
			'param' => array(
				'string $arg1',
				'array $arg2'
			),
			'validate' => array(
				'$arg1 Foo(bar = baz), Bar',
				'$arg2 VENDOR\\ModelCollection\\Domain\\Model\\Model'
			)
		);
		$mockReflectionService = $this->getMock('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodTagsValues));
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
		$mockStringValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface', array(), array(), '', FALSE);
		$mockArrayValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface', array(), array(), '', FALSE);
		$mockFooValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface', array(), array(), '', FALSE);
		$mockBarValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface', array(), array(), '', FALSE);
		$conjunction1 = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
		$conjunction1->expects($this->at(1))->method('addValidator')->with($mockFooValidator);
		$conjunction1->expects($this->at(2))->method('addValidator')->with($mockBarValidator);
		$conjunction2 = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction2->expects($this->at(0))->method('addValidator')->with($mockArrayValidator);
		$conjunction2->expects($this->at(1))->method('addValidator')->with($mockQuuxValidator);
		$mockArguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
		$mockArguments->addArgument(new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('arg1', 'dummyValue'));
		$mockArguments->addArgument(new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('arg2', 'dummyValue'));
		$validatorResolver = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver', array('createValidator'));
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('Conjunction')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Conjunction')->will($this->returnValue($conjunction2));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('array')->will($this->returnValue($mockArrayValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(6))->method('createValidator')->with('VENDOR\\ModelCollection\\Domain\\Model\\Model')->will($this->returnValue($mockQuuxValidator));
		$validatorResolver->injectReflectionService($mockReflectionService);
		$result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
		$this->assertEquals(array('arg1' => $conjunction1, 'arg2' => $conjunction2), $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sbastian@typo3.org>
	 * @expectedException \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException
	 */
	public function buildMethodArgumentsValidatorConjunctionsThrowsExceptionIfValidationAnnotationForNonExistingArgumentExists() {
		$mockObject = $this->getMock('stdClass', array('fooMethod'), array(), '', FALSE);
		$methodParameters = array(
			'arg1' => array(
				'type' => 'string'
			)
		);
		$methodTagsValues = array(
			'param' => array(
				'string $arg1'
			),
			'validate' => array(
				'$arg2 VENDOR\\ModelCollection\\Domain\\Model\\Model'
			)
		);
		$mockReflectionService = $this->getMock('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodTagsValues));
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
		$mockStringValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface', array(), array(), '', FALSE);
		$conjunction1 = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
		$validatorResolver = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver', array('createValidator'));
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('Conjunction')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('VENDOR\\ModelCollection\\Domain\\Model\\Model')->will($this->returnValue($mockQuuxValidator));
		$validatorResolver->injectReflectionService($mockReflectionService);
		$validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function buildBaseValidatorConjunctionAddsValidatorsDefinedByAnnotationsInTheClassToTheReturnedConjunction() {
		$mockObject = $this->getMock('stdClass');
		$className = get_class($mockObject);
		$propertyTagsValues = array(
			'foo' => array(
				'var' => array('string'),
				'validate' => array(
					'Foo(bar = baz), Bar',
					'Baz'
				)
			),
			'bar' => array(
				'var' => array('integer'),
				'validate' => array(
					'VENDOR\\ModelCollection\\Domain\\Validator\\ModelValidator'
				)
			)
		);
		$mockReflectionService = $this->getMock('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('foo', 'bar')));
		$mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($className, 'foo')->will($this->returnValue($propertyTagsValues['foo']));
		$mockReflectionService->expects($this->at(2))->method('getPropertyTagsValues')->with($className, 'bar')->will($this->returnValue($propertyTagsValues['bar']));
		$mockObjectValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\GenericObjectValidator', array(), array(), '', FALSE);
		$mockConjunctionValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator', array(), array(), '', FALSE);
		$mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockObjectValidator);
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));
		$validatorResolver = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver', array('resolveValidatorObjectName', 'createValidator'));
		$validatorResolver->injectReflectionService($mockReflectionService);
		$validatorResolver->injectObjectManager($mockObjectManager);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('GenericObject')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Baz')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('VENDOR\\ModelCollection\\Domain\\Validator\\ModelValidator')->will($this->returnValue($mockObjectValidator));
		$result = $validatorResolver->_call('buildBaseValidatorConjunction', $className);
		$this->assertSame($mockConjunctionValidator, $result);
	}

	/**
	 * dataProvider for buildBaseValidatorConjunctionAddsValidatorFromConventionToTheReturnedConjunction
	 *
	 * @return array
	 */
	public function modelNamesProvider() {
		return array(
			'no replace' => array('VENDOR\\ModelCollection\\Domain\\Model\\Model', 'VENDOR\\ModelCollection\\Domain\\Validator\\ModelValidator'),
			'replace in not namespaced class' => array('Tx_ModelCollection_Domain_Model_Model', 'Tx_ModelCollection_Domain_Validator_ModelValidator'),
			'replace in namespaced class' => array('VENDOR\\ModelCollection\\Domain\\Model\\Model', 'VENDOR\\ModelCollection\\Domain\\Validator\\ModelValidator')
		);
	}

	/**
	 * @param string $modelClassName
	 * @param string $validatorClassName
	 *
	 * @test
	 * @dataProvider modelNamesProvider
	 */
	public function buildBaseValidatorConjunctionCreatesValidatorFromClassName($modelClassName, $validatorClassName) {
		$mockConjunctionValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));
		$validatorResolver = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver', array('resolveValidatorObjectName', 'createValidator'));
		$validatorResolver->injectObjectManager($mockObjectManager);
		$validatorResolver->expects($this->once())->method('createValidator')->with($validatorClassName)->will($this->returnValue(NULL));
		$result = $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName);
		$this->assertSame($mockConjunctionValidator, $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValidatorObjectNameCallsUnifyDataType() {
		$validatorName = uniqid('FooValidator');
		eval('namespace TYPO3\CMS\Extbase\Validation\Validator;' . LF . 'class ' . $validatorName . 'Validator implements \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface {}');
		$mockValidatorResolver = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver', array('unifyDataType'));
		$mockValidatorResolver->expects($this->once())->method('unifyDataType')->with($validatorName)->will($this->returnValue($validatorName));

		$mockValidatorResolver->_call('resolveValidatorObjectName', $validatorName);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unifyDataTypeCorrectlyRenamesPhpDataTypes() {
		$this->assertEquals('Integer', $this->validatorResolver->_call('unifyDataType', 'integer'));
		$this->assertEquals('Integer', $this->validatorResolver->_call('unifyDataType', 'int'));
		$this->assertEquals('String', $this->validatorResolver->_call('unifyDataType', 'string'));
		$this->assertEquals('Array', $this->validatorResolver->_call('unifyDataType', 'array'));
		$this->assertEquals('Float', $this->validatorResolver->_call('unifyDataType', 'float'));
		$this->assertEquals('Float', $this->validatorResolver->_call('unifyDataType', 'double'));
		$this->assertEquals('Boolean', $this->validatorResolver->_call('unifyDataType', 'boolean'));
		$this->assertEquals('Boolean', $this->validatorResolver->_call('unifyDataType', 'bool'));
		$this->assertEquals('Boolean', $this->validatorResolver->_call('unifyDataType', 'bool'));
		$this->assertEquals('Number', $this->validatorResolver->_call('unifyDataType', 'number'));
		$this->assertEquals('Number', $this->validatorResolver->_call('unifyDataType', 'numeric'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unifyDataTypeRenamesMixedToRaw() {
		$this->assertEquals('Raw', $this->validatorResolver->_call('unifyDataType', 'mixed'));
	}

	/**
	 * dataProvider for parseValidatorAnnotationCanParseAnnotations
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @return array
	 */
	public function validatorAnnotations() {
		return array(
			array(
				'$var Bar',
				array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'Bar', 'validatorOptions' => array())
					)
				)
			),
			array(
				'$var Bar, Foo',
				array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'Bar', 'validatorOptions' => array()),
						array('validatorName' => 'Foo', 'validatorOptions' => array())
					)
				)
			),
			array(
				'$var Baz (Foo=Bar)',
				array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => 'Bar'))
					)
				)
			),
			array(
				'$var Buzz (Foo="B=a, r", Baz=1)',
				array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'Buzz', 'validatorOptions' => array('Foo' => 'B=a, r', 'Baz' => '1'))
					)
				)
			),
			array(
				'$var Foo(Baz=1, Bar=Quux)',
				array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'Foo', 'validatorOptions' => array('Baz' => '1', 'Bar' => 'Quux'))
					)
				)
			),
			array(
				'$var Pax, Foo(Baz = \'1\', Bar = Quux)',
				array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'Pax', 'validatorOptions' => array()),
						array('validatorName' => 'Foo', 'validatorOptions' => array('Baz' => '1', 'Bar' => 'Quux'))
					)
				)
			),
			array(
				'$var Reg (P="[at]*(h|g)"), Quux',
				array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'Reg', 'validatorOptions' => array('P' => '[at]*(h|g)')),
						array('validatorName' => 'Quux', 'validatorOptions' => array())
					)
				)
			),
			array(
				'$var Baz (Foo="B\\"ar")',
				array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => 'B"ar'))
					)
				)
			),
			array(
				'$var F3_TestPackage_Quux',
				array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'F3_TestPackage_Quux', 'validatorOptions' => array())
					)
				)
			),
			array(
				'$var Baz(Foo="5"), Bar(Quux="123")',
				array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => '5')),
						array('validatorName' => 'Bar', 'validatorOptions' => array('Quux' => '123'))
					)
				)
			),
			array(
				'$var Baz(Foo="2"), Bar(Quux=123, Pax="a weird \\"string\\" with *freaky* \\stuff")',
				array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => '2')),
						array('validatorName' => 'Bar', 'validatorOptions' => array('Quux' => '123', 'Pax' => 'a weird "string" with *freaky* \\stuff'))
					)
				)
			),
			'namespaced validator class name' => array(
				'annotation' => '$var F3\TestPackage\Quux',
				'expected' => array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'F3\TestPackage\Quux', 'validatorOptions' => array())
					)
				)
			),
			'shorthand notation for system validator' => array(
				'annotation' => '$var TYPO3.CMS.Mypkg:MySecond',
				'expected' => array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'TYPO3.CMS.Mypkg:MySecond', 'validatorOptions' => array())
					)
				)
			),
			'shorthand notation for custom validator with parameter' => array(
				'annotation' => '$var Acme.Mypkg:MyThird(Foo="2")',
				'expected' => array(
					'argumentName' => 'var',
					'validators' => array(
						array('validatorName' => 'Acme.Mypkg:MyThird', 'validatorOptions' => array('Foo' => '2'))
					)
				)
			),
		);
	}

	/**
	 * @test
	 * @dataProvider validatorAnnotations
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @param mixed $annotation
	 * @param mixed $expectedResult
	 */
	public function parseValidatorAnnotationCanParseAnnotations($annotation, $expectedResult) {
		$result = $this->validatorResolver->_call('parseValidatorAnnotation', $annotation);
		$this->assertEquals($result, $expectedResult);
	}
}

?>