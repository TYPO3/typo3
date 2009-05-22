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

class Tx_Extbase_MVC_Controller_Argument_testcase extends Tx_Extbase_Base_testcase {
		
	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function constructingArgumentWithoutNameThrowsException() {
		new Tx_Extbase_MVC_Controller_Argument(NULL, 'Text');
	}
	
	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function constructingArgumentWithInvalidNameThrowsException() {
		new Tx_Extbase_MVC_Controller_Argument(new ArrayObject(), 'Text');
	}
	
	/**
	 * @test
	 */
	public function passingDataTypeToConstructorReallySetsTheDataType() {
		$argument = new Tx_Extbase_MVC_Controller_Argument('dummy', 'Number');
		$this->assertEquals('Number', $argument->getDataType(), 'The specified data type has not been set correctly.');
	}
	
	/**
	 * @test
	 */
	public function setShortNameProvidesFluentInterface() {
		$argument = new Tx_Extbase_MVC_Controller_Argument('dummy', 'Text');
		$returnedArgument = $argument->setShortName('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}
	
	/**
	 * @test
	 */
	public function setValueProvidesFluentInterface() {
		$argument = new Tx_Extbase_MVC_Controller_Argument('dummy', 'Text');
		$returnedArgument = $argument->setValue('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}
	
	/**
	 * @test
	 */
	public function setValueTriesToConvertAnUIDIntoTheRealObject() {
		$object = new StdClass();
		
		$argument = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_Argument'), array('findObjectByUid'), array(), '', FALSE);
		$argument->expects($this->once())->method('findObjectByUid')->with('42')->will($this->returnValue($object));
		$argument->setValue(array('uid' => '42'));
	
		$this->assertSame($object, $argument->_get('value'));
	}
	
	/**
	 * @test
	 */
	public function setValueTriesToConvertAnIdentityArrayContainingIdentifiersIntoTheRealObject() {
		$object = new stdClass();
			
		// $mockQuery = $this->getMock('Tx_Extbase_Persistence_Query', array(), array(), '', FALSE);
		# TODO Insert more expectations here
		// $mockQuery->expects($this->once())->method('execute')->will($this->returnValue(array($object)));
	
		// $mockQueryFactory = $this->getMock('Tx_Extbase_Persistence_QueryFactory', array(), array(), '', FALSE);
		// $mockQueryFactory->expects($this->once())->method('create')->with('MyClass')->will($this->returnValue($mockQuery));
	
		$argument = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_Argument'), array('findObjectByUid'), array(), '', FALSE);
		$argument->expects($this->once())->method('findObjectByUid')->with('42')->will($this->returnValue($object));
		$argument->_set('dataType', 'MyClass');
		// $argument->_set('queryFactory', $mockQueryFactory);
		$argument->setValue(array('uid' => '42'));
	
		$this->assertSame($object, $argument->_get('value'));
	}
	
	/**
	 * @test
	 */
	public function setValueConvertsAnArrayIntoAFreshObjectWithThePropertiesSetToTheArrayValuesIfDataTypeIsAClassAndNoIdentityInformationIsFoundInTheValue() {
		eval('class MyClass {}');		
		$object = new MyClass;
		
		$theValue = array('property1' => 'value1', 'property2' => 'value2');
	
		$mockPropertyMapper = $this->getMock('Tx_Extbase_Property_Mapper', array('map'), array(), '', FALSE);
		$mockPropertyMapper->expects($this->once())->method('map')->with(array('property1', 'property2'), $theValue, $object)->will($this->returnValue(TRUE));
	
		$argument = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_Argument'), array('dummy'), array(), '', FALSE);
		$argument->_set('dataType', 'MyClass');
		$argument->_set('propertyMapper', $mockPropertyMapper);
		$argument->setValue($theValue);
	
		$this->assertTrue($argument->_get('value') instanceof MyClass);
	}
		
	/**
	 * @test
	 */
	public function toStringReturnsTheStringVersionOfTheArgumentsValue() {
		$argument = new Tx_Extbase_MVC_Controller_Argument('dummy', 'Text');
		$argument->setValue(123);
	
		$this->assertSame((string)$argument, '123', 'The returned argument is not a string.');
		$this->assertNotSame((string)$argument, 123, 'The returned argument is identical to the set value.');
	}
	
	/**
	 * @test
	 */
	public function defaultDataTypeIsText() {
		$argument = new Tx_Extbase_MVC_Controller_Argument('SomeArgument');
		$this->assertSame('Text', $argument->getDataType());
	}
	
	/**
	 * @test
	 */
	public function setNewValidatorChainCreatesANewValidatorChainObject() {
		$argument = new Tx_Extbase_MVC_Controller_Argument('dummy', 'Text');
		$argument->setNewValidatorChain(array());
	
		$this->assertType('Tx_Extbase_Validation_Validator_ChainValidator', $argument->getValidator(), 'The returned validator is not a chain as expected.');
	}
	
	/**
	 * @test
	 */
	public function setNewValidatorChainAddsThePassedValidatorsToTheCreatedValidatorChain() {
		eval('class Validator1 implements Tx_Extbase_Validation_Validator_ValidatorInterface {
			public function isValid($value) {}
			public function setOptions(array $validationOptions) {}
			public function getErrors() {}
		}');
		eval('class Validator2 implements Tx_Extbase_Validation_Validator_ValidatorInterface {
			public function isValid($value) {}
			public function setOptions(array $validationOptions) {}
			public function getErrors() {}
		}');
		
		$validator1 = new Validator1;
		$validator2 = new Validator2;
	
		$mockValidatorChain = $this->getMock('Tx_Extbase_Validation_Validator_ChainValidator');
		$mockValidatorChain->expects($this->at(0))->method('addValidator')->with($validator1);
		$mockValidatorChain->expects($this->at(1))->method('addValidator')->with($validator2);
		
		$argument = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_Argument'), array('dummy'), array(), '', FALSE);
		$argument->_set('validator', $mockValidatorChain);
		$argument->setNewValidatorChain(array('Validator1', 'Validator2'));
	}
	
	/**
	 * @test
	 */
	public function settingDefaultValueReallySetsDefaultValue() {
		$argument = new Tx_Extbase_MVC_Controller_Argument('dummy', 'Text');
		$argument->setDefaultValue(42);
	
		$this->assertEquals(42, $argument->getValue(), 'The default value was not stored in the Argument.');
	}
	
}
?>