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

class Tx_Extbase_MVC_Controller_Arguments_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 */
	public function argumentsObjectIsOfScopePrototype() {
		$arguments1 = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
		$arguments2 = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
		$this->assertNotSame($arguments1, $arguments2, 'The arguments object is not of scope prototype!');
	}
	
	/**
	 * @test
	 */
	public function addingAnArgumentManuallyWorks() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
		$newArgument = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Argument', 'argumentName1234');
	
		$arguments->addArgument($newArgument);
		$this->assertSame($newArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
	}
	
	/**
	 * @test
	 */
	public function addingAnArgumentReplacesArgumentWithSameName() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
	
		$firstArgument = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Argument', 'argumentName1234');
		$arguments->addArgument($firstArgument);
	
		$secondArgument = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Argument', 'argumentName1234');
		$arguments->addArgument($secondArgument);
	
		$this->assertSame($secondArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
	}
	
	/**
	 * @test
	 */
	public function addNewArgumentProvidesFluentInterface() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
		$newArgument = $arguments->addNewArgument('someArgument');
		$this->assertType('Tx_Extbase_MVC_Controller_Argument', $newArgument, 'addNewArgument() did not return an argument object.');
	}
	
	/**
	 * @test
	 */
	public function addingArgumentThroughArrayAccessWorks() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
		$argument = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Argument', 'argumentName1234');
		$arguments[] = $argument;
		$this->assertTrue($arguments->hasArgument('argumentName1234'), 'Added argument does not exist.');
		$this->assertSame($argument, $arguments->getArgument('argumentName1234'), 'Added and retrieved arguments are not the same.');
	}
	
	/**
	 * @test
	 */
	public function retrievingArgumentThroughArrayAccessWorks() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
		$newArgument = $arguments->addNewArgument('someArgument');
		$this->assertSame($newArgument, $arguments['someArgument'], 'Argument retrieved by array access is not the one we added.');
	}
	
	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_NoSuchArgument
	 */
	public function getArgumentWithNonExistingArgumentNameThrowsException() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->getArgument('someArgument');
	}
	
	/**
	 * @test
	 */
	public function issetReturnsCorrectResult() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
		$this->assertFalse(isset($arguments['someArgument']), 'isset() did not return FALSE.');
		$arguments->addNewArgument('someArgument');
		$this->assertTrue(isset($arguments['someArgument']), 'isset() did not return TRUE.');
	}
	
	/**
	 * @test
	 */
	public function getArgumentNamesReturnsNamesOfAddedArguments() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->addNewArgument('first');
		$arguments->addNewArgument('second');
		$arguments->addNewArgument('third');
	
		$expectedArgumentNames = array('first', 'second', 'third');
		$this->assertEquals($expectedArgumentNames, $arguments->getArgumentNames(), 'Returned argument names were not as expected.');
	}
	
	/**
	 * @test
	 */
	public function getArgumentShortNamesReturnsShortNamesOfAddedArguments() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
		$argument = $arguments->addNewArgument('first')->setShortName('a');
		$arguments->addNewArgument('second')->setShortName('b');
		$arguments->addNewArgument('third')->setShortName('c');
	
		$expectedShortNames = array('a', 'b', 'c');
		$this->assertEquals($expectedShortNames, $arguments->getArgumentShortNames(), 'Returned argument short names were not as expected.');
	}
	
	/**
	 * @test
	 */
	public function addNewArgumentCreatesAndAddsNewArgument() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
	
		$addedArgument = $arguments->addNewArgument('dummyName');
		$this->assertType('Tx_Extbase_MVC_Controller_Argument', $addedArgument, 'addNewArgument() either did not add a new argument or did not return it.');
	
		$retrievedArgument = $arguments['dummyName'];
		$this->assertSame($addedArgument, $retrievedArgument, 'The added and the retrieved argument are not the same.');
	
		$this->assertEquals('dummyName', $addedArgument->getName(), 'The name of the added argument is not as expected.');
	}
	
	/**
	 * @test
	 */
	public function addNewArgumentAssumesTextDataTypeByDefault() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
	
		$addedArgument = $arguments->addNewArgument('dummyName');
		$this->assertEquals('Text', $addedArgument->getDataType(), 'addNewArgument() did not create an argument of type "Text" by default.');
	}
	
	/**
	 * @test
	 */
	public function addNewArgumentCanAddArgumentsMarkedAsRequired() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
	
		$addedArgument = $arguments->addNewArgument('dummyName', 'Text', TRUE);
		$this->assertTrue($addedArgument->isRequired(), 'addNewArgument() did not create an argument that is marked as required.');
	}
	
	/**
	 * @test
	 */
	public function addNewArgumentCanAddArgumentsMarkedAsOptionalWithDefaultValues() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
	
		$defaultValue = 'Default Value 42';
		$addedArgument = $arguments->addNewArgument('dummyName', 'Text', FALSE, $defaultValue);
		$this->assertEquals($defaultValue, $addedArgument->getValue(), 'addNewArgument() did not store the default value in the argument.');
	}
	
	/**
	 * @test
	 * @expectedException LogicException
	 */
	public function callingInvalidMethodThrowsException() {
		$arguments = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->nonExistingMethod();
	}
}
?>