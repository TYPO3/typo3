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

class Tx_Extbase_MVC_Controller_Arguments_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 */
	public function argumentsObjectIsOfScopePrototype() {
		$arguments1 = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments2 = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$this->assertNotSame($arguments1, $arguments2, 'The arguments object is not of scope prototype!');
	}

	/**
	 * @test
	 */
	public function addingAnArgumentManuallyWorks() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$newArgument = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Argument', 'argumentName1234', 'dummyValue');

		$arguments->addArgument($newArgument);
		$this->assertSame($newArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
	}

	/**
	 * @test
	 */
	public function addingAnArgumentReplacesArgumentWithSameName() {
		$arguments = new Tx_Extbase_MVC_Controller_Arguments;

		$mockFirstArgument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName'), array(), '', FALSE);
		$mockFirstArgument->expects($this->any())->method('getName')->will($this->returnValue('argumentName1234'));
		$arguments->addArgument($mockFirstArgument);

		$mockSecondArgument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName'), array(), '', FALSE);
		$mockSecondArgument->expects($this->any())->method('getName')->will($this->returnValue('argumentName1234'));
		$arguments->addArgument($mockSecondArgument);

		$this->assertSame($mockSecondArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
	}

	/**
	 * @test
	 */
	public function addNewArgumentProvidesFluentInterface() {
		$mockArgument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array(), array(), '', FALSE);
		$mockArguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array('createArgument'));
		$mockArguments->expects($this->any())->method('createArgument')->will($this->returnValue($mockArgument));
		
		$newArgument = $mockArguments->addNewArgument('someArgument');
		$this->assertType('Tx_Extbase_MVC_Controller_Argument', $newArgument, 'addNewArgument() did not return an argument object.');
	}

	/**
	 * @test
	 */
	public function addingArgumentThroughArrayAccessWorks() {
		$mockArgument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName'), array(), '', FALSE);
		$mockArgument->expects($this->any())->method('getName')->will($this->returnValue('argumentName1234'));
		$arguments = new Tx_Extbase_MVC_Controller_Arguments;
		
		$arguments[] = $mockArgument;
		$this->assertTrue($arguments->hasArgument('argumentName1234'), 'Added argument does not exist.');
		$this->assertSame($mockArgument, $arguments->getArgument('argumentName1234'), 'Added and retrieved arguments are not the same.');
	}

	/**
	 * @test
	 */
	public function retrievingArgumentThroughArrayAccessWorks() {
		$mockArgument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName'), array(), '', FALSE);
		$mockArgument->expects($this->any())->method('getName')->will($this->returnValue('argumentName1234'));
		$arguments = new Tx_Extbase_MVC_Controller_Arguments;

		$arguments[] = $mockArgument;
		$this->assertSame($mockArgument, $arguments['argumentName1234'], 'Argument retrieved by array access is not the one we added.');
	}

	/**
	 * @test
	 */
	public function getArgumentWithNonExistingArgumentNameThrowsException() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		try {
			$arguments->getArgument('someArgument');
			$this->fail('getArgument() did not throw an exception although the specified argument does not exist.');
		} catch (Tx_Extbase_MVC_Exception_NoSuchArgument $exception) {
		}
	}

	/**
	 * @test
	 */
	public function issetReturnsCorrectResult() {
		$mockArgument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName'), array(), '', FALSE);
		$mockArgument->expects($this->any())->method('getName')->will($this->returnValue('argumentName1234'));
		$arguments = new Tx_Extbase_MVC_Controller_Arguments;

		$this->assertFalse(isset($arguments['argumentName1234']), 'isset() did not return FALSE.');
		$arguments[] = $mockArgument;
		$this->assertTrue(isset($arguments['argumentName1234']), 'isset() did not return TRUE.');
	}

	/**
	 * @test
	 */
	public function getArgumentNamesReturnsNamesOfAddedArguments() {
		$mockArgument1 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName'), array(), '', FALSE);
		$mockArgument1->expects($this->any())->method('getName')->will($this->returnValue('argumentName1'));
		$mockArgument2 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName'), array(), '', FALSE);
		$mockArgument2->expects($this->any())->method('getName')->will($this->returnValue('argumentName2'));
		$mockArgument3 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName'), array(), '', FALSE);
		$mockArgument3->expects($this->any())->method('getName')->will($this->returnValue('argumentName3'));
		$arguments = new Tx_Extbase_MVC_Controller_Arguments;
		$arguments[] = $mockArgument1;
		$arguments[] = $mockArgument2;
		$arguments[] = $mockArgument3;

		$expectedArgumentNames = array('argumentName1', 'argumentName2', 'argumentName3');
		$this->assertEquals($expectedArgumentNames, $arguments->getArgumentNames(), 'Returned argument names were not as expected.');
	}

	/**
	 * @test
	 */
	public function getArgumentShortNamesReturnsShortNamesOfAddedArguments() {
		$mockArgument1 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName', 'getShortName'), array(), '', FALSE);
		$mockArgument1->expects($this->any())->method('getName')->will($this->returnValue('argumentName1'));
		$mockArgument1->expects($this->any())->method('getShortName')->will($this->returnValue('a'));
		$mockArgument2 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName', 'getShortName'), array(), '', FALSE);
		$mockArgument2->expects($this->any())->method('getName')->will($this->returnValue('argumentName2'));
		$mockArgument2->expects($this->any())->method('getShortName')->will($this->returnValue('b'));
		$mockArgument3 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName', 'getShortName'), array(), '', FALSE);
		$mockArgument3->expects($this->any())->method('getName')->will($this->returnValue('argumentName3'));
		$mockArgument3->expects($this->any())->method('getShortName')->will($this->returnValue('c'));
		$arguments = new Tx_Extbase_MVC_Controller_Arguments;
		$arguments[] = $mockArgument1;
		$arguments[] = $mockArgument2;
		$arguments[] = $mockArgument3;

		$expectedShortNames = array('a', 'b', 'c');
		$this->assertEquals($expectedShortNames, $arguments->getArgumentShortNames(), 'Returned argument short names were not as expected.');
	}

	/**
	 * @test
	 */
	public function addNewArgumentCreatesAndAddsNewArgument() {
		$mockArgument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName'), array(), '', FALSE);
		$mockArgument->expects($this->any())->method('getName')->will($this->returnValue('dummyName'));
		$mockArguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array('createArgument'));
		$mockArguments->expects($this->any())->method('createArgument')->with($this->equalTo('dummyName'))->will($this->returnValue($mockArgument));

		$addedArgument = $mockArguments->addNewArgument('dummyName');
		$this->assertType('Tx_Extbase_MVC_Controller_Argument', $addedArgument, 'addNewArgument() either did not add a new argument or did not return it.');

		$retrievedArgument = $mockArguments['dummyName'];
		$this->assertSame($addedArgument, $retrievedArgument, 'The added and the retrieved argument are not the same.');
	}

	/**
	 * @test
	 */
	public function addNewArgumentAssumesTextDataTypeByDefault() {
		$mockArgument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName'), array(), '', FALSE);
		$mockArgument->expects($this->any())->method('getName')->will($this->returnValue('dummyName'));
		$mockArguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array('createArgument'));
		$mockArguments->expects($this->any())->method('createArgument')->with($this->equalTo('dummyName'), $this->equalTo('Text'))->will($this->returnValue($mockArgument));

		$addedArgument = $mockArguments->addNewArgument('dummyName');
	}

	/**
	 * @test
	 */
	public function addNewArgumentCanAddArgumentsMarkedAsRequired() {
		$mockArgument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName', 'setRequired'), array(), '', FALSE);
		$mockArgument->expects($this->any())->method('getName')->will($this->returnValue('dummyName'));
		$mockArgument->expects($this->any())->method('setRequired')->with(TRUE);
		$mockArguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array('createArgument'));
		$mockArguments->expects($this->any())->method('createArgument')->with($this->equalTo('dummyName'), $this->equalTo('Text'))->will($this->returnValue($mockArgument));

		$addedArgument = $mockArguments->addNewArgument('dummyName', 'Text', TRUE);
	}

	/**
	 * @test
	 */
	public function addNewArgumentCanAddArgumentsMarkedAsOptionalWithDefaultValues() {
		$mockArgument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName', 'setRequired'), array(), '', FALSE);
		$mockArgument->expects($this->any())->method('getName')->will($this->returnValue('dummyName'));
		$mockArgument->expects($this->any())->method('setRequired')->with(TRUE);
		$mockArguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array('createArgument'));
		$mockArguments->expects($this->any())->method('createArgument')->with($this->equalTo('dummyName'), $this->equalTo('Text'))->will($this->returnValue($mockArgument));

		$addedArgument = $mockArguments->addNewArgument('dummyName', 'Text', TRUE);
	}

	/**
	 * @test
	 * @expectedException LogicException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function callingInvalidMethodThrowsException() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->nonExistingMethod();
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function removeAllClearsAllArguments() {
		$mockArgument1 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName', 'getShortName'), array(), '', FALSE);
		$mockArgument1->expects($this->any())->method('getName')->will($this->returnValue('argumentName1'));
		$mockArgument2 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName', 'getShortName'), array(), '', FALSE);
		$mockArgument2->expects($this->any())->method('getName')->will($this->returnValue('argumentName2'));
		$arguments = new Tx_Extbase_MVC_Controller_Arguments;
		$arguments[] = $mockArgument1;
		$arguments[] = $mockArgument2;

		$this->assertTrue($arguments->hasArgument('argumentName2'));
		$arguments->removeAll();
		$this->assertFalse($arguments->hasArgument('argumentName2'));
	}
}
?>