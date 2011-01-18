<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Bastian Waidelich <bastian@typo3.org>
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
 * Testcase for class t3lib_Registry
 *
 * @author	Bastian Waidelich <bastian@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_RegistryTest extends tx_phpunit_testcase {

	/**
	 * @var t3lib_Registry
	 */
	protected $registry;

	/**
	 * @var t3lib_DB
	 */
	protected $typo3DbBackup;

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		$this->typo3DbBackup = $GLOBALS['TYPO3_DB'];
		$GLOBALS['TYPO3_DB'] = $this->getMock('t3lib_DB', array());
		$GLOBALS['TYPO3_DB']->expects($this->any())
			->method('fullQuoteStr')
			->will($this->onConsecutiveCalls('\'tx_phpunit\'', '\'someKey\'', '\'tx_phpunit\'', '\'someKey\''));

		$this->registry = new t3lib_Registry();
	}

	/**
	 * Tears down this testcase
	 */
	public function tearDown() {
		$GLOBALS['TYPO3_DB'] = $this->typo3DbBackup;
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function getThrowsExceptionForInvalidNamespaces() {
		$this->registry->get('invalidNamespace', 'someKey');
	}

	/**
	 * @test
	 */
	public function getRetrievesTheCorrectEntry() {
		$testKey = 't3lib_Registry_testcase.testData.getRetrievesTheCorrectEntry';
		$testValue = 'getRetrievesTheCorrectEntry';

		$GLOBALS['TYPO3_DB']->expects($this->once())
			->method('exec_SELECTgetRows')
			->with('*', 'sys_registry', 'entry_namespace = \'tx_phpunit\'')
			->will(
				$this->returnValue(
					array(
						array('entry_key' => $testKey, 'entry_value' => serialize($testValue))
					)
				)
			);

		$this->assertEquals(
			$this->registry->get('tx_phpunit', $testKey),
			$testValue,
			'The actual data did not match the expected data.'
		);
	}

	/**
	 * @test
	 */
	public function getLazyLoadsEntriesOfOneNamespace() {
		$testKey1 = 't3lib_Registry_testcase.testData.getLazyLoadsEntriesOfOneNamespace1';
		$testValue1 = 'getLazyLoadsEntriesOfOneNamespace1';
		$testKey2 = 't3lib_Registry_testcase.testData.getLazyLoadsEntriesOfOneNamespace2';
		$testValue2 = 'getLazyLoadsEntriesOfOneNamespace2';

		$GLOBALS['TYPO3_DB']->expects($this->once())
			->method('exec_SELECTgetRows')
			->with('*', 'sys_registry', 'entry_namespace = \'tx_phpunit\'')
			->will(
				$this->returnValue(
					array(
						array('entry_key' => $testKey1, 'entry_value' => serialize($testValue1)),
						array('entry_key' => $testKey2, 'entry_value' => serialize($testValue2))
					)
				)
			);

		$this->assertEquals(
			$this->registry->get('tx_phpunit', $testKey1),
			$testValue1,
			'The actual data did not match the expected data.'
		);
		$this->assertEquals(
			$this->registry->get('tx_phpunit', $testKey2),
			$testValue2,
			'The actual data did not match the expected data.'
		);
	}

	/**
	 * @test
	 */
	public function getReturnsTheDefaultValueIfTheRequestedKeyWasNotFound() {
		$defaultValue = 'getReturnsTheDefaultValueIfTheRequestedKeyWasNotFound';

		$GLOBALS['TYPO3_DB']->expects($this->once())
			->method('exec_SELECTgetRows')
			->with('*', 'sys_registry', 'entry_namespace = \'tx_phpunit\'')
			->will(
				$this->returnValue(
					array(
						array('entry_key' => 'foo', 'entry_value' => 'bar'),
					)
				)
			);

		$this->assertEquals(
			$defaultValue,
			$this->registry->get('tx_phpunit', 'someNonExistingKey', $defaultValue),
			'A value other than the default value was returned.'
		);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setThrowsAnExceptionOnEmptyNamespace() {
		$this->registry->set('', 'someKey', 'someValue');
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setThrowsAnExceptionOnWrongNamespace() {
		$this->registry->set('invalidNamespace', 'someKey', 'someValue');
	}

	/**
	 * @test
	 */
	public function setAllowsValidNamespaces() {
		$this->registry->set('tx_thisIsValid', 'someKey', 'someValue');
		$this->registry->set('user_soIsThis', 'someKey', 'someValue');
		$this->registry->set('core', 'someKey', 'someValue');
	}

	/**
	 * @test
	 */
	public function setReallySavesTheGivenValueToTheDatabase() {
		$GLOBALS['TYPO3_DB']->expects($this->once())
			->method('exec_INSERTquery')
			->with(
				'sys_registry',
				array(
					'entry_namespace' => 'tx_phpunit',
					'entry_key' => 'someKey',
					'entry_value' => serialize('someValue')
				)
			);

		$this->registry->set('tx_phpunit', 'someKey', 'someValue');
	}

	/**
	 * @test
	 */
	public function setUpdatesExistingKeys() {
		$GLOBALS['TYPO3_DB']->expects($this->once())
			->method('exec_SELECTquery')
			->with(
				'uid',
				'sys_registry',
				'entry_namespace = \'tx_phpunit\' AND entry_key = \'someKey\''
			)
			->will(
				$this->returnValue('DBResource')
			);

		$GLOBALS['TYPO3_DB']->expects($this->once())
			->method('sql_num_rows')
			->with('DBResource')
			->will(
				$this->returnValue(1)
			);

		$GLOBALS['TYPO3_DB']->expects($this->once())
			->method('exec_UPDATEquery')
			->with(
				'sys_registry',
				'entry_namespace = \'tx_phpunit\' AND entry_key = \'someKey\'',
				array(
					'entry_value' => serialize('someValue')
				)
			);

		$GLOBALS['TYPO3_DB']->expects($this->never())
			->method('exec_INSERTquery');

		$this->registry->set('tx_phpunit', 'someKey', 'someValue');
	}

	/**
	 * @test
	 */
	public function setStoresValueInTheInternalEntriesCache() {
		$registry = $this->getMock('t3lib_Registry', array('loadEntriesByNamespace'));
		$registry->expects($this->never())
			->method('loadEntriesByNamespace');

		$registry->set('tx_phpunit', 'someKey', 'someValue');

		$this->assertEquals(
			'someValue',
			$registry->get('tx_phpunit', 'someKey'),
			'The actual data did not match the expected data.'
		);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function removeThrowsAnExceptionOnWrongNamespace() {
		$this->registry->remove('coreInvalid', 'someKey');
	}

	/**
	 * @test
	 */
	public function removeReallyRemovesTheEntryFromTheDatabase() {
		$GLOBALS['TYPO3_DB']->expects($this->once())
			->method('exec_DELETEquery')
			->with(
				'sys_registry',
				'entry_namespace = \'tx_phpunit\' AND entry_key = \'someKey\''
			);

		$this->registry->remove('tx_phpunit', 'someKey');
	}

	/**
	 * @test
	 */
	public function removeUnsetsValueFromTheInternalEntriesCache() {
		$registry = $this->getMock('t3lib_Registry', array('loadEntriesByNamespace'));
		$registry->set('tx_phpunit', 'someKey', 'someValue');
		$registry->set('tx_phpunit', 'someOtherKey', 'someOtherValue');
		$registry->set('tx_otherNamespace', 'someKey', 'someValueInOtherNamespace');
		$registry->remove('tx_phpunit', 'someKey');

		$this->assertEquals(
			'defaultValue',
			$registry->get('tx_phpunit', 'someKey', 'defaultValue'),
			'A value other than the default value was returned, thus the entry was still present.'
		);

		$this->assertEquals(
			'someOtherValue',
			$registry->get('tx_phpunit', 'someOtherKey', 'defaultValue'),
			'A value other than the stored value was returned, thus the entry was removed.'
		);

		$this->assertEquals(
			'someValueInOtherNamespace',
			$registry->get('tx_otherNamespace', 'someKey', 'defaultValue'),
			'A value other than the stored value was returned, thus the entry was removed.'
		);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function removeAllByNamespaceThrowsAnExceptionOnWrongNamespace() {
		$this->registry->removeAllByNamespace('');
	}

	/**
	 * @test
	 */
	public function removeAllByNamespaceReallyRemovesAllEntriesOfTheSpecifiedNamespaceFromTheDatabase() {
		$GLOBALS['TYPO3_DB']->expects($this->once())
			->method('exec_DELETEquery')
			->with(
				'sys_registry',
				'entry_namespace = \'tx_phpunit\''
			);

		$this->registry->removeAllByNamespace('tx_phpunit');
	}

	/**
	 * @test
	 */
	public function removeAllByNamespaceUnsetsValuesOfTheSpecifiedNamespaceFromTheInternalEntriesCache() {
		$registry = $this->getMock('t3lib_Registry', array('loadEntriesByNamespace'));
		$registry->set('tx_phpunit', 'someKey', 'someValue');
		$registry->set('tx_phpunit', 'someOtherKey', 'someOtherValue');
		$registry->set('tx_otherNamespace', 'someKey', 'someValueInOtherNamespace');
		$registry->removeAllByNamespace('tx_phpunit');

		$this->assertEquals(
			'defaultValue',
			$registry->get('tx_phpunit', 'someKey', 'defaultValue'),
			'A value other than the default value was returned, thus the entry was still present.'
		);

		$this->assertEquals(
			'defaultValue',
			$registry->get('tx_phpunit', 'someOtherKey', 'defaultValue'),
			'A value other than the default value was returned, thus the entry was still present.'
		);

		$this->assertEquals(
			'someValueInOtherNamespace',
			$registry->get('tx_otherNamespace', 'someKey', 'defaultValue'),
			'A value other than the stored value was returned, thus the entry was removed.'
		);
	}
}

?>