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
 * This test checks the Argument behavior before Extbase 1.4, i.e. with the old property mapper.
 *
 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0
 */
class Tx_Extbase_Tests_Unit_MVC_Controller_ArgumentBehaviorBeforeExtbase14Test extends Tx_Extbase_Tests_Unit_BaseTestCase {

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
		$this->enableDeprecatedPropertyMapperInArgument($argument);
		$returnedArgument = $argument->setValue('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValueUsesNullAsIs() {
		$argument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('transformValue'), array('dummy', 'ArrayObject'));
		$this->enableDeprecatedPropertyMapperInArgument($argument);
		$argument->expects($this->never())->method('transformValue');
		$argument->setValue(NULL);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	*/
	public function setValueUsesMatchingInstanceAsIs() {
		$argument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('transformValue'), array('dummy', 'ArrayObject'));
		$this->enableDeprecatedPropertyMapperInArgument($argument);
		$argument->expects($this->never())->method('transformValue');
		$argument->setValue(new ArrayObject());
	}

	/**
	 * @test
	 */
	public function setValueTriesToConvertAnUidIntoTheRealObjectIfTheDataTypeClassSchemaIsSet() {
		$object = new StdClass();

		$argument = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_Argument'), array('findObjectByUid'), array(), '', FALSE);
		$this->enableDeprecatedPropertyMapperInArgument($argument);
		$argument->expects($this->once())->method('findObjectByUid')->with('42')->will($this->returnValue($object));
		$argument->_set('dataTypeClassSchema', 'stdClass');
		$argument->_set('dataType', 'stdClass');
		// $argument->_set('queryFactory', $mockQueryFactory);
		$argument->setValue('42');

		$this->assertSame($object, $argument->_get('value'));
		$this->assertSame(Tx_Extbase_MVC_Controller_Argument::ORIGIN_PERSISTENCE, $argument->getOrigin());
	}


	/**
	 * @test
	 */
	public function toStringReturnsTheStringVersionOfTheArgumentsValue() {
		$argument = new Tx_Extbase_MVC_Controller_Argument('dummy', 'Text');
		$this->enableDeprecatedPropertyMapperInArgument($argument);
		$argument->setValue(123);

		$this->assertSame((string)$argument, '123', 'The returned argument is not a string.');
		$this->assertNotSame((string)$argument, 123, 'The returned argument is identical to the set value.');
	}

	/**
	 * @test
	 */
	public function setNewValidatorConjunctionCreatesANewValidatorConjunctionObject() {
		$argument = new Tx_Extbase_MVC_Controller_Argument('dummy', 'Text');
		$mockConjunctionValidator = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator');
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('Tx_Extbase_Validation_Validator_ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));
		$argument->injectObjectManager($mockObjectManager);
		$argument->setNewValidatorConjunction(array());
		$this->assertSame($mockConjunctionValidator, $argument->getValidator());
	}

	/**
	 * @test
	 */
	public function setNewValidatorConjunctionAddsThePassedValidatorsToTheCreatedValidatorChain() {
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

		$mockValidatorConjunction = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator');
		$mockValidatorConjunction->expects($this->at(0))->method('addValidator')->with($validator1);
		$mockValidatorConjunction->expects($this->at(1))->method('addValidator')->with($validator2);

		$argument = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_Argument'), array('dummy'), array(), '', FALSE);
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$mockObjectManager->expects($this->never())->method('create');
		$mockObjectManager->expects($this->at(0))->method('get')->with('Validator1')->will($this->returnValue($validator1));
		$mockObjectManager->expects($this->at(1))->method('get')->with('Validator2')->will($this->returnValue($validator2));
		$argument->injectObjectManager($mockObjectManager);
		$argument->_set('validator', $mockValidatorConjunction);
		$argument->setNewValidatorConjunction(array('Validator1', 'Validator2'));
	}

	/**
	 * @test
	 */
	public function settingDefaultValueReallySetsDefaultValue() {
		$argument = new Tx_Extbase_MVC_Controller_Argument('dummy', 'Text');
		$argument->setDefaultValue(42);

		$this->assertEquals(42, $argument->getValue(), 'The default value was not stored in the Argument.');
	}

	/**
	 * Helper which enables the deprecated property mapper in the Argument class.
	 *
	 * @param Tx_Extbase_MVC_Controller_Argument $argument
	 */
	protected function enableDeprecatedPropertyMapperInArgument(Tx_Extbase_MVC_Controller_Argument $argument) {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('isFeatureEnabled')->with('rewrittenPropertyMapper')->will($this->returnValue(FALSE));
		$argument->injectConfigurationManager($mockConfigurationManager);
	}
}
?>