<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
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
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id: ValidatorResolver_testcase.php 1709 2009-11-25 11:26:13Z jocrau $
 */
class Tx_Extbase_Validation_ValidatorResolver_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameReturnsFalseIfValidatorCantBeResolved() {
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ManagerInterface');
		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'));
		$validatorResolver->_set('objectManager', $objectManager);
		$this->assertSame(FALSE, $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameReturnsTheGivenArgumentIfAnObjectOfThatNameIsRegistered() {
		$validatorName = uniqid('FooValidator_') . 'Validator';
		eval('class ' . $validatorName . ' {}');
		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'));
		$this->assertSame($validatorName, $validatorResolver->_call('resolveValidatorObjectName', $validatorName));
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators() {
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ManagerInterface');
		eval('class Tx_Extbase_Validation_Validator_FooValidator {}');
		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'));
		$validatorResolver->_set('objectManager', $mockObjectManager);
		$this->assertSame('Tx_Extbase_Validation_Validator_FooValidator', $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 */
	public function createValidatorResolvesAndReturnsAValidatorAndPassesTheGivenOptions() {
		$className = uniqid('Test');
		$mockValidator = $this->getMock('Tx_Extbase_Validation_Validator_ObjectValidatorInterface', array(), array(), $className);
		$mockValidator->expects($this->once())->method('setOptions')->with(array('foo' => 'bar'));

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObject')->with($className)->will($this->returnValue($mockValidator));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'),array('resolveValidatorObjectName'));
		$validatorResolver->_set('objectManager', $mockObjectManager);
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className)->will($this->returnValue($className));
		$validator = $validatorResolver->createValidator($className, array('foo' => 'bar'));
		$this->assertSame($mockValidator, $validator);
	}

	/**
	 * @test
	 */
	public function createValidatorReturnsNullIfAValidatorCouldNotBeResolved() {
		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver',array('resolveValidatorObjectName'), array(), '', FALSE);
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('Foo')->will($this->returnValue(FALSE));
		$validator = $validatorResolver->createValidator('Foo', array('foo' => 'bar'));
		$this->assertNull($validator);
	}

	/**
	 * @test
	 */
	public function getBaseValidatorCachesTheResultOfTheBuildBaseValidatorChainCalls() {
		$mockConjunctionValidator = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);

		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array('buildBaseValidatorConjunction'), array(), '', FALSE);
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
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction'), array(), '', FALSE);

		$methodTagsValues = array();
		$methodParameters = array();

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));

		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array('createValidator'));
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
				'array $arg2',
			),
			'validate' => array(
				'$arg1 Foo(bar = baz), Bar',
				'$arg2 F3_TestPackage_Quux'
			)
		);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodTagsValues));
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));

		$mockStringValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$mockArrayValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$mockFooValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$mockBarValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array(), array(), '', FALSE);

		$conjunction1 = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
		$conjunction1->expects($this->at(1))->method('addValidator')->with($mockFooValidator);
		$conjunction1->expects($this->at(2))->method('addValidator')->with($mockBarValidator);

		$conjunction2 = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction2->expects($this->at(0))->method('addValidator')->with($mockArrayValidator);
		$conjunction2->expects($this->at(1))->method('addValidator')->with($mockQuuxValidator);

		$mockObjectFactory = $this->getMock('Tx_Extbase_Object_FactoryInterface');

		$mockArguments = new Tx_Extbase_MVC_Controller_Arguments();
		$mockArguments->addArgument(new Tx_Extbase_MVC_Controller_Argument('arg1', 'dummyValue'));
		$mockArguments->addArgument(new Tx_Extbase_MVC_Controller_Argument('arg2', 'dummyValue'));

		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array('createValidator'));
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('Conjunction')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Conjunction')->will($this->returnValue($conjunction2));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('array')->will($this->returnValue($mockArrayValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(6))->method('createValidator')->with('F3_TestPackage_Quux')->will($this->returnValue($mockQuuxValidator));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
		$this->assertEquals(array('arg1' => $conjunction1, 'arg2' => $conjunction2), $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sbastian@typo3.org>
	 * @expectedException Tx_Extbase_Validation_Exception_InvalidValidationConfiguration
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
				'string $arg1',
			),
			'validate' => array(
				'$arg2 F3_TestPackage_Quux'
			)
		);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodTagsValues));
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));

		$mockStringValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$conjunction1 = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);

		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array('createValidator'));
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('Conjunction')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('F3_TestPackage_Quux')->will($this->returnValue($mockQuuxValidator));

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
					'F3_TestPackage_Quux'
				)
			)
		);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('foo', 'bar')));
		$mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($className, 'foo')->will($this->returnValue($propertyTagsValues['foo']));
		$mockReflectionService->expects($this->at(2))->method('getPropertyTagsValues')->with($className, 'bar')->will($this->returnValue($propertyTagsValues['bar']));

		$mockObjectValidator = $this->getMock('Tx_Extbase_Validation_Validator_GenericObjectValidator', array(), array(), '', FALSE);

		$mockConjunctionValidator = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
		$mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockObjectValidator);

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('Tx_Extbase_Validation_Validator_ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('resolveValidatorObjectName', 'createValidator'));
		$validatorResolver->injectReflectionService($mockReflectionService);
		$validatorResolver->injectObjectManager($mockObjectManager);

		$validatorResolver->expects($this->at(0))->method('createValidator')->with('GenericObject')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Baz')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('F3_TestPackage_Quux')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with($className . 'Validator')->will($this->returnValue(NULL));

		$result = $validatorResolver->_call('buildBaseValidatorConjunction', $className);
		$this->assertSame($mockConjunctionValidator, $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValidatorObjectNameCallsUnifyDataType() {
		$mockValidatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('unifyDataType'));
		$mockValidatorResolver->expects($this->once())->method('unifyDataType')->with('someDataType');
		$mockValidatorResolver->_call('resolveValidatorObjectName', 'someDataType');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unifyDataTypeCorrectlyRenamesPHPDataTypes() {
		$mockValidatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'));
		$this->assertEquals('Integer', $mockValidatorResolver->_call('unifyDataType', 'integer'));
		$this->assertEquals('Integer', $mockValidatorResolver->_call('unifyDataType', 'int'));
		$this->assertEquals('String', $mockValidatorResolver->_call('unifyDataType', 'string'));
		$this->assertEquals('Array', $mockValidatorResolver->_call('unifyDataType', 'array'));
		$this->assertEquals('Float', $mockValidatorResolver->_call('unifyDataType', 'float'));
		$this->assertEquals('Float', $mockValidatorResolver->_call('unifyDataType', 'double'));
		$this->assertEquals('Boolean', $mockValidatorResolver->_call('unifyDataType', 'boolean'));
		$this->assertEquals('Boolean', $mockValidatorResolver->_call('unifyDataType', 'bool'));
		$this->assertEquals('Boolean', $mockValidatorResolver->_call('unifyDataType', 'bool'));
		$this->assertEquals('Number', $mockValidatorResolver->_call('unifyDataType', 'number'));
		$this->assertEquals('Number', $mockValidatorResolver->_call('unifyDataType', 'numeric'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unifyDataTypeRenamesMixedToRaw() {
		$mockValidator = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'));
		$this->assertEquals('Raw', $mockValidator->_call('unifyDataType', 'mixed'));
	}
	
	/**
	 * dataProvider for parseValidatorAnnotationCanParseAnnotations
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function validatorAnnotations() {
		return array(
			array('$var Bar', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Bar', 'validatorOptions' => array())))),
			array('$var Bar, Foo', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Bar', 'validatorOptions' => array()),
						array('validatorName' => 'Foo', 'validatorOptions' => array())
						))),
			array('$var Baz (Foo=Bar)', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => 'Bar'))))),
			array('$var Buzz (Foo="B=a, r", Baz=1)', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Buzz', 'validatorOptions' => array('Foo' => 'B=a, r', 'Baz' => '1'))))),
			array('$var Foo(Baz=1, Bar=Quux)', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Foo', 'validatorOptions' => array('Baz' => '1', 'Bar' => 'Quux'))))),
			array('$var Pax, Foo(Baz = \'1\', Bar = Quux)', array('argumentName' => 'var', 'validators' => array(
							array('validatorName' => 'Pax', 'validatorOptions' => array()),
							array('validatorName' => 'Foo', 'validatorOptions' => array('Baz' => '1', 'Bar' => 'Quux'))
						))),
			array('$var Reg (P="[at]*(h|g)"), Quux', array('argumentName' => 'var', 'validators' => array(
							array('validatorName' => 'Reg', 'validatorOptions' => array('P' => '[at]*(h|g)')),
							array('validatorName' => 'Quux', 'validatorOptions' => array())
						))),
			array('$var Baz (Foo="B\"ar")', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => 'B"ar'))))),
			array('$var F3_TestPackage_Quux', array('argumentName' => 'var', 'validators' => array(
						array('validatorName' => 'F3_TestPackage_Quux', 'validatorOptions' => array())))),
			array('$var Baz(Foo="5"), Bar(Quux="123")', array('argumentName' => 'var', 'validators' => array(
							array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => '5')),
							array('validatorName' => 'Bar', 'validatorOptions' => array('Quux' => '123'))))),
			array('$var Baz(Foo="2"), Bar(Quux=123, Pax="a weird \"string\" with *freaky* \\stuff")', array('argumentName' => 'var', 'validators' => array(
							array('validatorName' => 'Baz', 'validatorOptions' => array('Foo' => '2')),
							array('validatorName' => 'Bar', 'validatorOptions' => array('Quux' => '123', 'Pax' => 'a weird "string" with *freaky* \\stuff'))))),
		);
	}

	/**
	 *
	 * @test
	 * @dataProvider validatorAnnotations
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseValidatorAnnotationCanParseAnnotations($annotation, $expectedResult) {
		$mockValidatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'));
		$result = $mockValidatorResolver->_call('parseValidatorAnnotation', $annotation);

		$this->assertEquals($result, $expectedResult);
	}
	
}

?>