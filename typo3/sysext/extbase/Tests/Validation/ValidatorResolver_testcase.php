<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * @package TYPO3
 * @subpackage extbase
 * @version $Id$
 */
class Tx_Extbase_Validation_ValidatorResolver_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameReturnsFalseIfValidatorCantBeResolved() {
		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'), array());
		$this->assertSame(FALSE, $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 */
	public function resolveValidatorObjectNameReturnsTheGivenArgumentIfAnObjectOfThatNameIsRegistered() {
		eval('class Tx_MyExtension_Validation_Validators_FooValidator {}');
		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'), array());
		$validatorObjectName = $validatorResolver->_call('resolveValidatorObjectName', 'Tx_MyExtension_Validation_Validators_Foo');
		$this->assertSame('Tx_MyExtension_Validation_Validators_FooValidator', $validatorObjectName);
	}
	
	/**
	 * @test
	 */
	public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators() {		
		eval('class Tx_Extbase_Validation_Validator_FooValidator {}');
		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'), array());
		$this->assertSame('Tx_Extbase_Validation_Validator_FooValidator', $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}
	
	/**
	 * @test
	 */
	public function createValidatorResolvesAndReturnsAValidatorAndPassesTheGivenOptions() {		
		$this->markTestIncomplete();

		$className = uniqid('Test');
		$mockValidator = $this->getMock('Tx_Extbase_Validation_Validator_ObjectValidatorInterface', array(), array(), $className);
		$mockValidator->expects($this->once())->method('setOptions')->with(array('foo' => 'bar'));
			
		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver',array('resolveValidatorObjectName'), array());
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className)->will($this->returnValue($className));
		$validator = $validatorResolver->createValidator($className, array('foo' => 'bar'));
		$this->assertSame($mockValidator, $validator);
	}
	
	/**
	 * @test
	 */
	public function createValidatorReturnsNullIfAValidatorCouldNotBeResolved() {
		$this->markTestIncomplete();
		
		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver',array('resolveValidatorObjectName'), array(), '', FALSE);
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('Foo')->will($this->returnValue(FALSE));
		$validator = $validatorResolver->createValidator('Foo', array('foo' => 'bar'));
		$this->assertNull($validator);
	}
	
	/**
	 * @test
	 */
	public function getBaseValidatorCachesTheResultOfTheBuildBaseValidatorChainCalls() {
		$mockChainValidator = $this->getMock('Tx_Extbase_Validation_Validator_ChainValidator', array(), array(), '', FALSE);
	
		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array('buildBaseValidatorChain'), array(), '', FALSE);
		$validatorResolver->expects($this->once())->method('buildBaseValidatorChain')->with('Tx_Virtual_Foo')->will($this->returnValue($mockChainValidator));
	
		$result = $validatorResolver->getBaseValidatorChain('Tx_Virtual_Foo');
		$this->assertSame($mockChainValidator, $result, '#1');
	
		$result = $validatorResolver->getBaseValidatorChain('Tx_Virtual_Foo');
		$this->assertSame($mockChainValidator, $result, '#2');
	}
	
	/**
	 * @test
	 */
	public function buildMethodArgumentsValidatorChainsDetectsValidateAnnotationsAndRegistersNewValidatorsForEachArgument() {
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
	
		$mockFooValidator = $this->getMock('Tx_Extbase_validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$mockBarValidator = $this->getMock('Tx_Extbase_validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('Tx_Extbase_validation_Validator_ValidatorInterface', array(), array(), '', FALSE);
	
		$chain1 = $this->getMock('Tx_Extbase_Validation_Validator_ChainValidator', array(), array(), '', FALSE);
		$chain1->expects($this->at(0))->method('addValidator')->with($mockFooValidator);
		$chain1->expects($this->at(1))->method('addValidator')->with($mockBarValidator);
	
		$chain2 = $this->getMock('Tx_Extbase_Validation_Validator_ChainValidator', array(), array(), '', FALSE);
		$chain2->expects($this->at(0))->method('addValidator')->with($mockQuuxValidator);
			
		$mockArguments = new Tx_Extbase_MVC_Controller_Arguments();
		$mockArguments->addArgument(new Tx_Extbase_MVC_Controller_Argument('arg1'));
		$mockArguments->addArgument(new Tx_Extbase_MVC_Controller_Argument('arg2'));
	
		$mockArguments['arg2'] = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array(), array(), '', FALSE);
	
		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Chain')->will($this->returnValue($chain1));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Quux')->will($this->returnValue($mockQuuxValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Chain')->will($this->returnValue($chain2));
	
		$validatorResolver->injectReflectionService($mockReflectionService);
	
		$result = $validatorResolver->buildMethodArgumentsValidatorChains(get_class($mockController), 'fooAction');
		$this->assertSame(array('arg1' => $chain1, 'arg2' => $chain2), $result);
	}
	
	/**
	 * @test
	 */
	public function buildBaseValidatorChainAddsCustomValidatorToTheReturnedChain() {
		$this->markTestIncomplete();

		eval('
			class Tx_Virtual_FooValidator implements Tx_Extbase_Validation_Validator_ValidatorInterface {
				public function isValid($value) {}
				public function setOptions(array $validationOptions) {}
				public function getErrors() {}
			}
		');
		
		$mockValidator = $this->getMock('Tx_Extbase_validation_Validator_ValidatorInterface');
			
		$mockChainValidator = $this->getMock('Tx_Extbase_Validation_Validator_ChainValidator', array(), array(), '', FALSE);
		$mockChainValidator->expects($this->once())->method('addValidator')->with($mockValidator);
			
		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('resolveValidatorObjectName'), array());
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('Tx_Virtual_FooValidator')->will($this->returnValue('Tx_Virtual_FooValidator'));
			
		$result = $validatorResolver->_call('buildBaseValidatorChain', 'Tx_Virtual_Foo');
		$this->assertSame($mockChainValidator, $result);
	}
	
	/**
	 * @test
	 */
	public function buildBaseValidatorChainAddsValidatorsDefinedByAnnotationsInTheClassToTheReturnedChain() {
		$this->markTestIncomplete();
		
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
	
		$mockChainValidator = $this->getMock('Tx_Extbase_Validation_Validator_ChainValidator', array(), array(), '', FALSE);
		$mockChainValidator->expects($this->once())->method('addValidator')->with($mockObjectValidator);
	
		$validatorResolver = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('resolveValidatorObjectName', 'createValidator'), array());
		$validatorResolver->injectReflectionService($mockReflectionService);
	
		$validatorResolver->expects($this->at(0))->method('resolveValidatorObjectName')->with($className . 'Validator')->will($this->returnValue(FALSE));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('GenericObject')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Bar')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Baz')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with('Quux')->will($this->returnValue($mockObjectValidator));
	
		$result = $validatorResolver->_call('buildBaseValidatorChain', $className);
		$this->assertSame($mockChainValidator, $result);
	}
	
	/**
	 * test
	 */
	public function buildMethodArgumentsValidatorChainsBuildsAChainFromValidateAnnotationsOfTheSpecifiedMethod() {
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
	
		$chain1 = $this->getMock('Tx_Extbase_Validation_Validator_ChainValidator', array(), array(), '', FALSE);
		$chain1->expects($this->at(0))->method('addValidator')->with($mockFooValidator);
		$chain1->expects($this->at(1))->method('addValidator')->with($mockBarValidator);
	
		$chain2 = $this->getMock('Tx_Extbase_Validation_Validator_ChainValidator', array(), array(), '', FALSE);
		$chain2->expects($this->at(0))->method('addValidator')->with($mockQuuxValidator);
		
		$mockArguments = new Tx_Extbase_MVC_Controller_Arguments();
		$mockArguments->addArgument(new Tx_Extbase_MVC_Controller_Argument('arg1'));
		$mockArguments->addArgument(new Tx_Extbase_MVC_Controller_Argument('arg2'));
	
		$mockArguments['arg2'] = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array(), array(), '', FALSE);
	
		$validatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Chain')->will($this->returnValue($chain1));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Quux')->will($this->returnValue($mockQuuxValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Chain')->will($this->returnValue($chain2));
	
		$validatorResolver->injectReflectionService($mockReflectionService);
	
		$result = $validatorResolver->buildMethodArgumentsValidatorChains(get_class($mockController), 'fooAction');
		$this->assertSame(array('arg1' => $chain1, 'arg2' => $chain2), $result);
	}
	
	/**
	 * @test
	 */
	public function resolveValidatorObjectNameCallsUnifyDataType() {
		$mockValidator = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('unifyDataType'), array());
		$mockValidator->expects($this->once())->method('unifyDataType')->with('someDataType');
		$mockValidator->_call('resolveValidatorObjectName', 'someDataType');
	}
	
	/**
	 * @test
	 */
	public function unifyDataTypeCorrectlyRenamesPHPDataTypes() {
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
		$mockValidator = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Validation_ValidatorResolver'), array('dummy'), array(), '', FALSE);
		$this->assertEquals('Raw', $mockValidator->_call('unifyDataType', 'mixed'));
	}
}

?>