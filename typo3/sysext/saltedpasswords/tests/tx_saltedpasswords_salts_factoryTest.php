<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Marcus Krause <marcus#exp2009@t3sec.info>
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
 * Contains class "tx_saltedpasswords_salts_factory"
 * that provides a salted hashing method factory.
 *
 * $Id$
 */

/**
 * Testcases for class tx_saltedpasswords_salts_factory.
 *
 * @author      Marcus Krause <marcus#exp2009@t3sec.info>
 *
 * @since   	2009-09-06
 * @package     TYPO3
 * @subpackage  tx_saltedpasswords
 */
class tx_saltedpasswords_salts_factoryTest extends tx_phpunit_testcase {


	/**
	 * Keeps instance of object to test.
	 *
	 * @var tx_saltedpasswords_abstract_salts
	 */
	protected $objectInstance = NULL;

	/**
	 * Sets up the fixtures for this testcase.
	 *
	 * @return	void
	 */
	protected function setUp() {
		$this->objectInstance = tx_saltedpasswords_salts_factory::getSaltingInstance();
	}

	/**
	 * Tears down objects and settings created in this testcase.
	 *
	 * @return	void
	 */
	public function tearDown() {
		unset($this->objectInstance);
	}

	/**
	 * @test
	 */
	public function objectInstanceNotNull() {
		$this->assertNotNull($this->objectInstance);
	}

	/**
	 * @test
	 */
	public function objectInstanceExtendsAbstractClass() {
		$this->assertTrue(is_subclass_of($this->objectInstance, 'tx_saltedpasswords_abstract_salts'));
	}

	/**
	 * @test
	 */
	public function objectInstanceImplementsInterface() {
		$this->assertTrue(method_exists($this->objectInstance, 'checkPassword'), 'Missing method checkPassword() from interface tx_saltedpasswords_salts.');
		$this->assertTrue(method_exists($this->objectInstance, 'isHashUpdateNeeded'), 'Missing method isHashUpdateNeeded() from interface tx_saltedpasswords_salts.');
		$this->assertTrue(method_exists($this->objectInstance, 'isValidSalt') , 'Missing method isValidSalt() from interface tx_saltedpasswords_salts.');
		$this->assertTrue(method_exists($this->objectInstance, 'isValidSaltedPW') , 'Missing method isValidSaltedPW() from interface tx_saltedpasswords_salts.');
		$this->assertTrue(method_exists($this->objectInstance, 'getHashedPassword'), 'Missing method getHashedPassword() from interface tx_saltedpasswords_salts.');
		$this->assertTrue(method_exists($this->objectInstance, 'getSaltLength'), 'Missing method getSaltLength() from interface tx_saltedpasswords_salts.');
	}

	/**
	 * @test
	 */
	public function base64EncodeReturnsProperLength() {
			// 3 Bytes should result in a 6 char length base64 encoded string
			// used for MD5 and PHPass salted hashing
		$byteLength = 3;
		$reqLengthBase64 = intval(ceil(($byteLength * 8) / 6));
		$randomBytes = t3lib_div::generateRandomBytes($byteLength);
		$this->assertTrue(strlen($this->objectInstance->base64Encode($randomBytes, $byteLength)) == $reqLengthBase64);

			// 16 Bytes should result in a 22 char length base64 encoded string
			// used for Blowfish salted hashing
		$byteLength = 16;
		$reqLengthBase64 = intval(ceil(($byteLength * 8) / 6));
		$randomBytes = t3lib_div::generateRandomBytes($byteLength);
		$this->assertTrue(strlen($this->objectInstance->base64Encode($randomBytes, $byteLength)) == $reqLengthBase64);
	}

	/**
	 * @test
	 */
	public function objectInstanceForMD5Salts() {
		$saltMD5 = '$1$rasmusle$rISCgZzpwk3UhDidwXvin0';
		$this->objectInstance = tx_saltedpasswords_salts_factory::getSaltingInstance($saltMD5);

		$this->assertTrue((get_class($this->objectInstance) == 'tx_saltedpasswords_salts_md5') || (is_subclass_of($this->objectInstance, 'tx_saltedpasswords_salts_md5')) );
	}

	/**
	 * @test
	 */
	public function objectInstanceForBlowfishSalts() {
		$saltBlowfish = '$2a$07$abcdefghijklmnopqrstuuIdQV69PAxWYTgmnoGpe0Sk47GNS/9ZW';
		$this->objectInstance = tx_saltedpasswords_salts_factory::getSaltingInstance($saltBlowfish);
		$this->assertTrue((get_class($this->objectInstance) == 'tx_saltedpasswords_salts_blowfish') || (is_subclass_of($this->objectInstance, 'tx_saltedpasswords_salts_blowfish')) );
	}

	/**
	 * @test
	 */
	public function objectInstanceForPhpassSalts() {
		$saltPhpass = '$P$CWF13LlG/0UcAQFUjnnS4LOqyRW43c.';
		$this->objectInstance = tx_saltedpasswords_salts_factory::getSaltingInstance($saltPhpass);
		$this->assertTrue((get_class($this->objectInstance) == 'tx_saltedpasswords_salts_phpass') || (is_subclass_of($this->objectInstance, 'tx_saltedpasswords_salts_phpass')) );
	}

	/**
	 * @test
	 */
	public function resettingFactoryInstanceSucceeds() {
		$defaultClassNameToUse = tx_saltedpasswords_div::getDefaultSaltingHashingMethod();

		$saltedPW = '';
		if ($defaultClassNameToUse == 'tx_saltedpasswords_salts_md5') {
			$saltedPW = '$P$CWF13LlG/0UcAQFUjnnS4LOqyRW43c.';
		} else {
			$saltedPW = '$1$rasmusle$rISCgZzpwk3UhDidwXvin0';
		}
		$this->objectInstance = tx_saltedpasswords_salts_factory::getSaltingInstance($saltedPW);

			// resetting
		$this->objectInstance = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL);
		$this->assertTrue((get_class($this->objectInstance) == $defaultClassNameToUse) || (is_subclass_of($this->objectInstance, $defaultClassNameToUse)));
	}
}
?>