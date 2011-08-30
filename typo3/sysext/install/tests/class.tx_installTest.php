<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Steffen Gebert <steffen.gebert@typo3.org>
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
 * Unit Tests for tx_install
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 * @package TYPO3
 * @subpackage tx_install
 */
class tx_installTest extends tx_phpunit_testcase {

	/** @var tx_install */
	protected $fixture;

	public function setUp() {
		if (!class_exists('tx_installNotSelfInitializing')) {
			// Empty the constructor so no session is started
			eval('class tx_installNotSelfInitializing extends tx_install {
				public function __construct() {
					// nothing
				}
			}');
		}
		$this->fixture = t3lib_div::makeInstance('tx_installNotSelfInitializing');
	}

	public function tearDown() {
		unset($this->fixture);
	}

	////////////////////////////////////////
	// Tests concerning createEncryptionKey
	////////////////////////////////////////

	/**
	 * @test
	 */
	public function createEncryptionKeyReturnsRandomKey() {
		$key = $this->fixture->createEncryptionKey();
		$this->assertNotEmpty($key, 'Empty Encryption Key returned');
	}

	/**
	 * @test
	 */
	public function createEncryptionKeyReturnsDifferentResultOnDifferentCalls() {
		$key1 = $this->fixture->createEncryptionKey();
		$key2 = $this->fixture->createEncryptionKey();
		$this->assertNotEquals($key1, $key2, 'Same Encryption Key returned twice');
	}

	/**
	 * @test
	 */
	public function createEncryptionKeyReturnsKeyOffRequestedLength() {
		$keyLengthsToTest = array(1, 2, 3, 4, 5, 8, 9, 16, 17, 32, 33, 64, 65, 128, 129, 256, 257);

		foreach ($keyLengthsToTest as $keyLength) {
			$key = $this->fixture->createEncryptionKey($keyLength);
			$this->assertNotEmpty($key);
			$this->assertEquals($keyLength, strlen($key), 'Length of generated Encryption Key differs from requested length');
		}
	}
}

?>