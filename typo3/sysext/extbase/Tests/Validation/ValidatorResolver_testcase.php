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
 * @version $Id$
 */
class Tx_Extbase_Validation_ValidatorResolver_testcase extends Tx_Extbase_Base_testcase {

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
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ManagerInterface');
		$validatorName = uniqid('FooValidator_');
		eval('class ' . $validatorName . ' {}');
		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'));
		$validatorResolver->_set('objectManager', $mockObjectManager);
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
	 */
	public function buildMethodArgumentsValidatorConjunctionsDetectsValidateAnnotationsAndRegistersNewValidatorsForEachArgument() {
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction'), array(), '', FALSE);

		$methodTagsValues = array(
			'param' => array(
				'string $arg1',
				'array $arg2',
			),
			'validate' => array(
				'$arg1 Foo(bar = baz), Bar',
				'$arg2 Quux'
			)
		);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));

		$mockFooValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$mockBarValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array(), array(), '', FALSE);

		$conjunction1 = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockFooValidator);
		$conjunction1->expects($this->at(1))->method('addValidator')->with($mockBarValidator);

		$conjunction2 = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction2->expects($this->at(0))->method('addValidator')->with($mockQuuxValidator);

		$mockArguments = new Tx_Extbase_MVC_Controller_Arguments();
		$mockArguments->addArgument(new Tx_Extbase_MVC_Controller_Argument('arg1'));
		$mockArguments->addArgument(new Tx_Extbase_MVC_Controller_Argument('arg2'));

		$mockArguments['arg2'] = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array(), array(), '', FALSE);

		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Conjunction')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Quux')->will($this->returnValue($mockQuuxValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Conjunction')->will($this->returnValue($conjunction2));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockController), 'fooAction');
		$this->assertSame(array('arg1' => $conjunction1, 'arg2' => $conjunction2), $result);
	}

	/**
	 * @test
	 */
	public function buildBaseValidatorConjunctionAddsCustomValidatorToTheReturnedConjunction() {
		// TODO implement Data Provider
		$modelClassName = 'Tx_Fruux_Domain_Model_Blog';
		$validatorClassName = 'Tx_Fruux_Domain_Validator_BlogValidator';
		eval('class Tx_Fruux_Domain_Validator_BlogValidator implements Tx_Extbase_Validation_Validator_ValidatorInterface {
			public function isValid($value) {}
			public function setOptions(array $validationOptions) {}
			public function getErrors() {}
			}');

		$mockValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');

		$mockConjunctionValidator = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
		$mockConjunctionValidator->expects($this->once())->method('addValidator')->with($mockValidator);

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('Tx_Extbase_Validation_Validator_ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));
		$mockObjectManager->expects($this->at(1))->method('getObject')->with($validatorClassName)->will($this->returnValue($mockValidator));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('resolveValidatorObjectName'));
		$validatorResolver->_set('objectManager', $mockObjectManager);
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($validatorClassName)->will($this->returnValue($validatorClassName));

		$result = $validatorResolver->_call('buildBaseValidatorConjunction', $modelClassName);
		$this->assertSame($mockConjunctionValidator, $result);
	}

	/**
	 * @test
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
					'Quux'
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
		$validatorResolver->_set('objectManager', $mockObjectManager);
		$validatorResolver->injectReflectionService($mockReflectionService);

		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className . 'Validator')->will($this->returnValue(FALSE));
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('GenericObject')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Baz')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Quux')->will($this->returnValue($mockObjectValidator));

		$result = $validatorResolver->_call('buildBaseValidatorConjunction', $className);
		$this->assertSame($mockConjunctionValidator, $result);
	}

	/**
	 * test
	 */
	public function buildMethodArgumentsValidatorConjunctionsBuildsAConjunctionFromValidateAnnotationsOfTheSpecifiedMethod() {
		$mockObject = $this->getMock('stdClass', array('fooMethod'), array(), '', FALSE);

		$methodTagsValues = array(
			'param' => array(
				'string $arg1',
				'array $arg2',
			),
			'validate' => array(
				'$arg1 Foo(bar = baz), Bar',
				'$arg2 Quux'
			)
		);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));

		$mockFooValidator = $this->getMock('Tx_Extbase_validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$mockBarValidator = $this->getMock('Tx_Extbase_validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('Tx_Extbase_validation_Validator_ValidatorInterface', array(), array(), '', FALSE);

		$conjunction1 = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction1->expects($this->at(0))->method('addValidator')->with($mockFooValidator);
		$conjunction1->expects($this->at(1))->method('addValidator')->with($mockBarValidator);

		$conjunction2 = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction2->expects($this->at(0))->method('addValidator')->with($mockQuuxValidator);

		$mockArguments = new Tx_Extbase_MVC_Controller_Arguments();
		$mockArguments->addArgument(new Tx_Extbase_MVC_Controller_Argument('arg1'));
		$mockArguments->addArgument(new Tx_Extbase_MVC_Controller_Argument('arg2'));

		$mockArguments['arg2'] = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array(), array(), '', FALSE);

		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Chain')->will($this->returnValue($conjunction1));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Quux')->will($this->returnValue($mockQuuxValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Chain')->will($this->returnValue($conjunction2));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockController), 'fooAction');
		$this->assertSame(array('arg1' => $conjunction1, 'arg2' => $conjunction2), $result);
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameCallsUnifyDataType() {
		$mockValidator = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('unifyDataType'), array(), '', FALSE);
		$mockValidator->expects($this->once())->method('unifyDataType')->with('someDataType');
		$mockValidator->_call('resolveValidatorObjectName', 'someDataType');
	}

	/**
	 * @test
	 */
	public function unifyDataTypeCorrectlyRenamesPhpDataTypes() {
		$mockValidator = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'), array(), '', FALSE);
		$this->assertEquals('Integer', $mockValidator->_call('unifyDataType', 'integer'));
		$this->assertEquals('Integer', $mockValidator->_call('unifyDataType', 'int'));
		$this->assertEquals('Text', $mockValidator->_call('unifyDataType', 'string'));
		$this->assertEquals('Array', $mockValidator->_call('unifyDataType', 'array'));
		$this->assertEquals('Float', $mockValidator->_call('unifyDataType', 'float'));
		$this->assertEquals('Float', $mockValidator->_call('unifyDataType', 'double'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'boolean'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'bool'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'bool'));
		$this->assertEquals('Number', $mockValidator->_call('unifyDataType', 'number'));
		$this->assertEquals('Number', $mockValidator->_call('unifyDataType', 'numeric'));
	}

	/**
	 * @test
	 */
	public function unifyDataTypeRenamesMixedToRaw() {
		$mockValidator = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'), array($mockObjectManager), '', FALSE);
		$this->assertEquals('Raw', $mockValidator->_call('unifyDataType', 'mixed'));
	}
}

?>