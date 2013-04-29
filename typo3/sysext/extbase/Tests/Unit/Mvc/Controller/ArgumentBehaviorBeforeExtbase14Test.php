<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

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
 * This test checks the Argument behavior before Extbase 1.4, i.e. with the old property mapper.
 *
 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
 */
class ArgumentBehaviorBeforeExtbase14Test extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function constructingArgumentWithoutNameThrowsException() {
		new \TYPO3\CMS\Extbase\Mvc\Controller\Argument(NULL, 'Text');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function constructingArgumentWithInvalidNameThrowsException() {
		new \TYPO3\CMS\Extbase\Mvc\Controller\Argument(new \ArrayObject(), 'Text');
	}

	/**
	 * @test
	 */
	public function passingDataTypeToConstructorReallySetsTheDataType() {
		$argument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('dummy', 'Number');
		$this->assertEquals('Number', $argument->getDataType(), 'The specified data type has not been set correctly.');
	}

	/**
	 * @test
	 */
	public function setShortNameProvidesFluentInterface() {
		$argument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('dummy', 'Text');
		$returnedArgument = $argument->setShortName('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 */
	public function setValueProvidesFluentInterface() {
		$argument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('dummy', 'Text');
		$this->enableDeprecatedPropertyMapperInArgument($argument);
		$returnedArgument = $argument->setValue('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValueUsesNullAsIs() {
		$argument = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Argument', array('transformValue'), array('dummy', 'ArrayObject'));
		$this->enableDeprecatedPropertyMapperInArgument($argument);
		$argument->expects($this->never())->method('transformValue');
		$argument->setValue(NULL);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValueUsesMatchingInstanceAsIs() {
		$argument = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Argument', array('transformValue'), array('dummy', 'ArrayObject'));
		$this->enableDeprecatedPropertyMapperInArgument($argument);
		$argument->expects($this->never())->method('transformValue');
		$argument->setValue(new \ArrayObject());
	}

	/**
	 * @test
	 */
	public function setValueTriesToConvertAnUidIntoTheRealObjectIfTheDataTypeClassSchemaIsSet() {
		$object = new \StdClass();
		$argument = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Argument', array('findObjectByUid'), array(), '', FALSE);
		$this->enableDeprecatedPropertyMapperInArgument($argument);
		$argument->expects($this->once())->method('findObjectByUid')->with('42')->will($this->returnValue($object));
		$argument->_set('dataTypeClassSchema', 'stdClass');
		$argument->_set('dataType', 'stdClass');
		// $argument->_set('queryFactory', $mockQueryFactory);
		$argument->setValue('42');
		$this->assertSame($object, $argument->_get('value'));
		$this->assertSame(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::ORIGIN_PERSISTENCE, $argument->getOrigin());
	}

	/**
	 * @test
	 */
	public function toStringReturnsTheStringVersionOfTheArgumentsValue() {
		$argument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('dummy', 'Text');
		$this->enableDeprecatedPropertyMapperInArgument($argument);
		$argument->setValue(123);
		$this->assertSame((string) $argument, '123', 'The returned argument is not a string.');
		$this->assertNotSame((string) $argument, 123, 'The returned argument is identical to the set value.');
	}

	/**
	 * @test
	 */
	public function setNewValidatorConjunctionCreatesANewValidatorConjunctionObject() {
		$argument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('dummy', 'Text');
		$mockConjunctionValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator');
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator')->will($this->returnValue($mockConjunctionValidator));
		$argument->injectObjectManager($mockObjectManager);
		$argument->setNewValidatorConjunction(array());
		$this->assertSame($mockConjunctionValidator, $argument->getValidator());
	}

	/**
	 * @test
	 */
	public function setNewValidatorConjunctionAddsThePassedValidatorsToTheCreatedValidatorChain() {
		eval('class Validator1 implements TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface {
			public function isValid($value) {}
			public function setOptions(array $validationOptions) {}
			public function getErrors() {}
		}');
		eval('class Validator2 implements TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface {
			public function isValid($value) {}
			public function setOptions(array $validationOptions) {}
			public function getErrors() {}
		}');
		$validator1 = new \Validator1();
		$validator2 = new \Validator2();
		$mockValidatorConjunction = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator');
		$mockValidatorConjunction->expects($this->at(0))->method('addValidator')->with($validator1);
		$mockValidatorConjunction->expects($this->at(1))->method('addValidator')->with($validator2);
		$argument = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Argument', array('dummy'), array(), '', FALSE);
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
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
		$argument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('dummy', 'Text');
		$argument->setDefaultValue(42);
		$this->assertEquals(42, $argument->getValue(), 'The default value was not stored in the Argument.');
	}

	/**
	 * Helper which enables the deprecated property mapper in the Argument class.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\Argument $argument
	 */
	protected function enableDeprecatedPropertyMapperInArgument(\TYPO3\CMS\Extbase\Mvc\Controller\Argument $argument) {
		$mockConfigurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('isFeatureEnabled')->with('rewrittenPropertyMapper')->will($this->returnValue(FALSE));
		$argument->injectConfigurationManager($mockConfigurationManager);
	}
}

?>