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
		// $this->markTestIncomplete('Not yet fully implemented.');
		$object = new StdClass();
	
		$mockQuery = $this->getMock('Tx_Extbase_Persistence_Query', array(), array(), '', FALSE);
		# TODO Insert more expectations here
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue(array($object)));
	
		$mockQueryFactory = $this->getMock('Tx_Extbase_Persistence_QueryFactory', array(), array(), '', FALSE);
		$mockQueryFactory->expects($this->once())->method('create')->with('MyClass')->will($this->returnValue($mockQuery));
	
		$argument = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_Argument'), array('dummy'), array(), '', FALSE);
		$argument->_set('dataType', 'MyClass');
		$argument->_set('queryFactory', $mockQueryFactory);
		$argument->setValue(array('uid' => '42'));
	
		$this->assertSame($object, $argument->_get('value'));
	}
	
	// /**
	//  * @test
	//  */
	// public function setValueConvertsAnArrayIntoAFreshObjectWithThePropertiesSetToTheArrayValuesIfDataTypeIsAClassAndNoIdentityInformationIsFoundInTheValue() {
	// 	eval('class MyClass {}');		
	// 	$object = new MyClass;
	// 	
	// 	$theValue = array('property1' => 'value1', 'property2' => 'value2');
	// 
	// 	$mockPropertyMapper = $this->getMock('Tx_Extbase_Property_Mapper', array('map'), array(), '', FALSE);
	// 	$mockPropertyMapper->expects($this->once())->method('map')->with(array('property1', 'property2'), $theValue, $object)->will($this->returnValue(TRUE));
	// 
	// 	$argument = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_Argument'), array('dummy'), array(), '', FALSE);
	// 	$argument->_set('dataType', 'MyClass');
	// 	$argument->_set('propertyMapper', $mockPropertyMapper);
	// 	$argument->setValue($theValue);
	// 
	// 	$this->assertSame($object, $argument->_get('value'));
	// }
	// 
	// /**
	//  * @test
	//  * @author Robert Lemke <robert@typo3.org>
	//  */
	// public function setShortHelpMessageProvidesFluentInterface() {
	// 	$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
	// 	$returnedArgument = $argument->setShortHelpMessage('x');
	// 	$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	// }
	// 
	// /**
	//  * @test
	//  * @author Robert Lemke <robert@typo3.org>
	//  */
	// public function toStringReturnsTheStringVersionOfTheArgumentsValue() {
	// 	$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
	// 	$argument->setValue(123);
	// 
	// 	$this->assertSame((string)$argument, '123', 'The returned argument is not a string.');
	// 	$this->assertNotSame((string)$argument, 123, 'The returned argument is identical to the set value.');
	// }
	// 
	// /**
	//  * @test
	//  * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	//  */
	// public function dataTypeValidatorCanBeAFullClassName() {
	// 	$this->markTestIncomplete();
	// 
	// 	$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue(TRUE));
	// 	$this->mockObjectManager->expects($this->any())->method('getObject')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\TextValidator')));
	// 
	// 	$argument = new \F3\FLOW3\MVC\Controller\Argument('SomeArgument', 'F3\FLOW3\Validation\Validator\TextValidator');
	// 	$argument->injectObjectManager($this->mockObjectManager);
	// 
	// 	$this->assertType('F3\FLOW3\Validation\Validator\TextValidator', $argument->getDatatypeValidator(), 'The returned datatype validator is not a text validator as expected.');
	// }
	// 
	// /**
	//  * @test
	//  * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	//  * @author Robert Lemke <robert@typo3.org>
	//  */
	// public function dataTypeValidatorCanBeAShortName() {
	// 	$this->markTestIncomplete();
	// 
	// 	$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue(TRUE));
	// 	$this->mockObjectManager->expects($this->any())->method('getObject')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\TextValidator')));
	// 
	// 	$argument = new \F3\FLOW3\MVC\Controller\Argument('SomeArgument', 'Text');
	// 	$argument->injectObjectManager($this->mockObjectManager);
	// 
	// 	$this->assertType('F3\FLOW3\Validation\Validator\TextValidator', $argument->getDatatypeValidator(), 'The returned datatype validator is not a text validator as expected.');
	// }
	// 
	// /**
	//  * @test
	//  * @author Robert Lemke <robert@typo3.org>
	//  */
	// public function defaultDataTypeIsText() {
	// 	$argument = new \F3\FLOW3\MVC\Controller\Argument('SomeArgument');
	// 	$this->assertSame('Text', $argument->getDataType());
	// }
	// 
	// /**
	//  * @test
	//  * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	//  */
	// public function setNewValidatorChainCreatesANewValidatorChainObject() {
	// 	$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\ChainValidator')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\ChainValidator')));
	// 
	// 	$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
	// 	$argument->injectObjectFactory($this->mockObjectFactory);
	// 	$argument->setNewValidatorChain(array());
	// 
	// 	$this->assertType('F3\FLOW3\Validation\Validator\ChainValidator', $argument->getValidator(), 'The returned validator is not a chain as expected.');
	// }
	// 
	// /**
	//  * @test
	//  * @author Robert Lemke <robert@typo3.org>
	//  */
	// public function setNewValidatorChainAddsThePassedValidatorsToTheCreatedValidatorChain() {
	// 	$mockValidator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
	// 	$mockValidator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
	// 
	// 	$mockValidatorChain = $this->getMock('F3\FLOW3\Validation\Validator\ChainValidator', array(), array(), '', FALSE);
	// 	$mockValidatorChain->expects($this->at(0))->method('addValidator')->with($mockValidator1);
	// 	$mockValidatorChain->expects($this->at(1))->method('addValidator')->with($mockValidator2);
	// 
	// 	$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\ChainValidator')->will($this->returnValue($mockValidatorChain));
	// 
	// 	$this->mockObjectManager->expects($this->any())->method('isObjectRegistered')->will($this->returnValue(TRUE));
	// 	$this->mockObjectManager->expects($this->exactly(2))->method('getObject')->will($this->onConsecutiveCalls($mockValidator1, $mockValidator2));
	// 
	// 	$argument = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\Argument'), array('dummy'), array(), '', FALSE);
	// 	$argument->_set('objectManager', $this->mockObjectManager);
	// 	$argument->_set('objectFactory', $this->mockObjectFactory);
	// 
	// 	$argument->setNewValidatorChain(array('Validator1', 'Validator2'));
	// }
	// 
	// /**
	//  * @test
	//  * @author Robert Lemke <robert@typo3.org>
	//  */
	// public function setNewValidatorChainCanHandleShortValidatorNames() {
	// 	$mockValidator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
	// 	$mockValidator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
	// 
	// 	$mockValidatorChain = $this->getMock('F3\FLOW3\Validation\Validator\ChainValidator', array(), array(), '', FALSE);
	// 	$mockValidatorChain->expects($this->at(0))->method('addValidator')->with($mockValidator1);
	// 	$mockValidatorChain->expects($this->at(1))->method('addValidator')->with($mockValidator2);
	// 
	// 	$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\ChainValidator')->will($this->returnValue($mockValidatorChain));
	// 
	// 	$this->mockObjectManager->expects($this->any())->method('isObjectRegistered')->will($this->returnValue(FALSE));
	// 	$this->mockObjectManager->expects($this->exactly(2))->method('getObject')->will($this->onConsecutiveCalls($mockValidator1, $mockValidator2));
	// 
	// 	$argument = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\Argument'), array('dummy'), array(), '', FALSE);
	// 	$argument->_set('objectManager', $this->mockObjectManager);
	// 	$argument->_set('objectFactory', $this->mockObjectFactory);
	// 
	// 	$argument->setNewValidatorChain(array('Validator1', 'Validator2'));
	// }
	// 
	// /**
	//  * @test
	//  * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	//  */
	// public function setNewFilterChainCreatesANewFilterChainObject() {
	// 	$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Filter\Chain')));
	// 
	// 	$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
	// 	$argument->injectObjectFactory($this->mockObjectFactory);
	// 	$argument->setNewFilterChain(array());
	// 
	// 	$this->assertType('F3\FLOW3\Validation\Filter\Chain', $argument->getFilter(), 'The returned filter is not a chain as expected.');
	// }
	// 
	// /**
	//  * @test
	//  * @author Sebastian Kurfürst <sebastian@typo3.org>
	//  */
	// public function settingDefaultValueReallySetsDefaultValue() {
	// 	$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
	// 	$argument->injectObjectFactory($this->mockObjectFactory);
	// 	$argument->setDefaultValue(42);
	// 
	// 	$this->assertEquals(42, $argument->getValue(), 'The default value was not stored in the Argument.');
	// }
	// 
	// /**
	//  * @test
	//  * @author Robert Lemke <robert@typo3.org>
	//  */
	// public function setNewFilterChainAddsThePassedFiltersToTheCreatedFilterChain() {
	// 	$this->markTestIncomplete('Implement this test with a new Filter Resolver');
	// }
	// 
	// /**
	//  * @test
	//  * @author Robert Lemke <robert@typo3.org>
	//  */
	// public function setNewFilterChainCanHandleShortFilterNames() {
	// 	$this->markTestIncomplete('Implement this test with a new Filter Resolver');
	// }
}
?>