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

class Tx_Extbase_MVC_Controller_Arguments_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function argumentsObjectIsOfScopePrototype() {
		$arguments1 = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments2 = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$this->assertNotSame($arguments1, $arguments2, 'The arguments object is not of scope prototype!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingAnArgumentManuallyWorks() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$newArgument = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Argument', 'argumentName1234');

		$arguments->addArgument($newArgument);
		$this->assertSame($newArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingAnArgumentReplacesArgumentWithSameName() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');

		$firstArgument = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Argument', 'argumentName1234');
		$arguments->addArgument($firstArgument);

		$secondArgument = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Argument', 'argumentName1234');
		$arguments->addArgument($secondArgument);

		$this->assertSame($secondArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addNewArgumentProvidesFluentInterface() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->injectPersistenceManager($this->getMock('Tx_Extbase_Persistence_Manager', array(), array(), '', FALSE));
		$arguments->injectQueryFactory($this->getMock('Tx_Extbase_Persistence_QueryFactory'));

		$newArgument = $arguments->addNewArgument('someArgument');
		$this->assertType('Tx_Extbase_MVC_Controller_Argument', $newArgument, 'addNewArgument() did not return an argument object.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingArgumentThroughArrayAccessWorks() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$argument = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Argument', 'argumentName1234');
		$arguments[] = $argument;
		$this->assertTrue($arguments->hasArgument('argumentName1234'), 'Added argument does not exist.');
		$this->assertSame($argument, $arguments->getArgument('argumentName1234'), 'Added and retrieved arguments are not the same.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function retrievingArgumentThroughArrayAccessWorks() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->injectPersistenceManager($this->getMock('Tx_Extbase_Persistence_Manager', array(), array(), '', FALSE));
		$arguments->injectQueryFactory($this->getMock('Tx_Extbase_Persistence_QueryFactory'));

		$newArgument = $arguments->addNewArgument('someArgument');
		$this->assertSame($newArgument, $arguments['someArgument'], 'Argument retrieved by array access is not the one we added.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function issetReturnsCorrectResult() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->injectPersistenceManager($this->getMock('Tx_Extbase_Persistence_Manager', array(), array(), '', FALSE));
		$arguments->injectQueryFactory($this->getMock('Tx_Extbase_Persistence_QueryFactory'));

		$this->assertFalse(isset($arguments['someArgument']), 'isset() did not return FALSE.');
		$arguments->addNewArgument('someArgument');
		$this->assertTrue(isset($arguments['someArgument']), 'isset() did not return TRUE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentNamesReturnsNamesOfAddedArguments() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->injectPersistenceManager($this->getMock('Tx_Extbase_Persistence_Manager', array(), array(), '', FALSE));
		$arguments->injectQueryFactory($this->getMock('Tx_Extbase_Persistence_QueryFactory'));
		$arguments->addNewArgument('first');
		$arguments->addNewArgument('second');
		$arguments->addNewArgument('third');

		$expectedArgumentNames = array('first', 'second', 'third');
		$this->assertEquals($expectedArgumentNames, $arguments->getArgumentNames(), 'Returned argument names were not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentShortNamesReturnsShortNamesOfAddedArguments() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->injectPersistenceManager($this->getMock('Tx_Extbase_Persistence_Manager', array(), array(), '', FALSE));
		$arguments->injectQueryFactory($this->getMock('Tx_Extbase_Persistence_QueryFactory'));
		$arguments->addNewArgument('first')->setShortName('a');
		$arguments->addNewArgument('second')->setShortName('b');
		$arguments->addNewArgument('third')->setShortName('c');

		$expectedShortNames = array('a', 'b', 'c');
		$this->assertEquals($expectedShortNames, $arguments->getArgumentShortNames(), 'Returned argument short names were not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addNewArgumentCreatesAndAddsNewArgument() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->injectPersistenceManager($this->getMock('Tx_Extbase_Persistence_Manager', array(), array(), '', FALSE));
		$arguments->injectQueryFactory($this->getMock('Tx_Extbase_Persistence_QueryFactory'));

		$addedArgument = $arguments->addNewArgument('dummyName');
		$this->assertType('Tx_Extbase_MVC_Controller_Argument', $addedArgument, 'addNewArgument() either did not add a new argument or did not return it.');

		$retrievedArgument = $arguments['dummyName'];
		$this->assertSame($addedArgument, $retrievedArgument, 'The added and the retrieved argument are not the same.');

		$this->assertEquals('dummyName', $addedArgument->getName(), 'The name of the added argument is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addNewArgumentAssumesTextDataTypeByDefault() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->injectPersistenceManager($this->getMock('Tx_Extbase_Persistence_Manager', array(), array(), '', FALSE));
		$arguments->injectQueryFactory($this->getMock('Tx_Extbase_Persistence_QueryFactory'));

		$addedArgument = $arguments->addNewArgument('dummyName');
		$this->assertEquals('Text', $addedArgument->getDataType(), 'addNewArgument() did not create an argument of type "Text" by default.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addNewArgumentCanAddArgumentsMarkedAsRequired() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->injectPersistenceManager($this->getMock('Tx_Extbase_Persistence_Manager', array(), array(), '', FALSE));
		$arguments->injectQueryFactory($this->getMock('Tx_Extbase_Persistence_QueryFactory'));

		$addedArgument = $arguments->addNewArgument('dummyName', 'Text', TRUE);
		$this->assertTrue($addedArgument->isRequired(), 'addNewArgument() did not create an argument that is marked as required.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function addNewArgumentCanAddArgumentsMarkedAsOptionalWithDefaultValues() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->injectPersistenceManager($this->getMock('Tx_Extbase_Persistence_Manager', array(), array(), '', FALSE));
		$arguments->injectQueryFactory($this->getMock('Tx_Extbase_Persistence_QueryFactory'));

		$defaultValue = 'Default Value 42';
		$addedArgument = $arguments->addNewArgument('dummyName', 'Text', FALSE, $defaultValue);
		$this->assertEquals($defaultValue, $addedArgument->getValue(), 'addNewArgument() did not store the default value in the argument.');
	}

	/**
	 * @test
	 * @expectedException LogicException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function callingInvalidMethodThrowsException() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->injectPersistenceManager($this->getMock('Tx_Extbase_Persistence_Manager', array(), array(), '', FALSE));
		$arguments->injectQueryFactory($this->getMock('Tx_Extbase_Persistence_QueryFactory'));
		$arguments->nonExistingMethod();
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function removeAllClearsAllArguments() {
		$arguments = $this->objectManager->getObject('Tx_Extbase_MVC_Controller_Arguments');
		$arguments->injectPersistenceManager($this->getMock('Tx_Extbase_Persistence_Manager', array(), array(), '', FALSE));
		$arguments->injectQueryFactory($this->getMock('Tx_Extbase_Persistence_QueryFactory'));
		$arguments->addNewArgument('foo');

		$arguments->removeAll();

		$this->assertFalse($arguments->hasArgument('foo'));

		$arguments->addNewArgument('bar');

		$this->assertTrue($arguments->hasArgument('bar'));
	}
}
?>