<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Marcus Krause <marcus#exp2009@t3sec.info>
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
 * Contains testcases for "tx_saltedpasswords_salts_md5"
 * that provides MD5 salted hashing.
 *
 * $Id$
 */

/**
 * Testcases for class tx_saltedpasswords_salts_md5.
 *
 * @author  Marcus Krause <marcus#exp2009@t3sec.info>
 * @package  TYPO3
 * @subpackage  tx_saltedpasswords
 */
class tx_saltedpasswords_salts_md5_testcase extends tx_phpunit_testcase {


	/**
	 * Keeps instance of object to test.
	 *
	 * @var tx_saltedpasswords_salts_md5
	 */
	protected $objectInstance = NULL;


	/**
	 * Sets up the fixtures for this testcase.
	 *
	 * @return	void
	 */
	public function setUp() {
		$this->objectInstance = t3lib_div::makeInstance('tx_saltedpasswords_salts_md5');
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
	 * Prepares a message to be shown when a salted hashing is not supported.
	 *
	 * @return  string     empty string if salted hashing method is available, otherwise an according warning
	 */
	protected function getWarningWhenMethodUnavailable() {
		$warningMsg = '';
		if (!CRYPT_MD5) {
			$warningMsg .= 'MD5 is not supported on your platform. '
						.  'Then, some of the md5 tests will fail.';
		}
	}

	/**
	 * @test
	 */
	public function hasCorrectBaseClass() {

		$hasCorrectBaseClass = (0 === strcmp('tx_saltedpasswords_salts_md5', get_class($this->objectInstance))) ? TRUE : FALSE;

			// XCLASS ?
		if (!$hasCorrectBaseClass && FALSE != get_parent_class($this->objectInstance)) {
			$hasCorrectBaseClass = is_subclass_of($this->objectInstance, 'tx_saltedpasswords_salts_md5');
		}

		$this->assertTrue($hasCorrectBaseClass);
	}

	/**
	 * @test
	 */
	public function nonZeroSaltLength() {
		$this->assertTrue($this->objectInstance->getSaltLength() > 0);
	}

	/**
	 * @test
	 */
	public function emptyPasswordResultsInNullSaltedPassword() {
		$password = '';
		$this->assertNull($this->objectInstance->getHashedPassword($password));
	}

	/**
	 * @test
	 */
	public function nonEmptyPasswordResultsInNonNullSaltedPassword() {
		$password = 'a';
		$this->assertNotNull($this->objectInstance->getHashedPassword($password), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * @test
	 */
	public function createdSaltedHashOfProperStructure() {
		$password = 'password';
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPW), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * @test
	 */
	public function createdSaltedHashOfProperStructureForCustomSaltWithoutSetting() {
		$password = 'password';

			// custom salt without setting
		$randomBytes = t3lib_div::generateRandomBytes($this->objectInstance->getSaltLength());
		$salt = $this->objectInstance->base64Encode($randomBytes, $this->objectInstance->getSaltLength());
		$this->assertTrue($this->objectInstance->isValidSalt($salt), $this->getWarningWhenMethodUnavailable());

		$saltedHashPW = $this->objectInstance->getHashedPassword($password, $salt);
		$this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPW), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * @test
	 */
	public function authenticationWithValidPassword() {
		$password = 'password';
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPW), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * @test
	 */
	public function authenticationWithNonValidPassword() {
		$password = 'password';
		$password1 = $password . 'INVALID';
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$this->assertFalse($this->objectInstance->checkPassword($password1, $saltedHashPW), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * @test
	 */
	public function passwordVariationsResultInDifferentHashes() {
		$pad = 'a';
		$password = '';
		$criticalPwLength = 0;
			// We're using a constant salt.
		$saltedHashPWPrevious = $saltedHashPWCurrent = $salt = $this->objectInstance->getHashedPassword($pad);
		for ($i = 0; $i <= 128; $i += 8) {
			$password = str_repeat($pad, max($i, 1));
			$saltedHashPWPrevious = $saltedHashPWCurrent;
			$saltedHashPWCurrent = $this->objectInstance->getHashedPassword($password, $salt);
			if ($i > 0 && 0 == strcmp($saltedHashPWPrevious, $saltedHashPWCurrent)) {
				$criticalPwLength = $i;
				break;
			}
		}
		$this->assertTrue(($criticalPwLength == 0) || ($criticalPwLength > 32), $this->getWarningWhenMethodUnavailable() . 'Duplicates of hashed passwords with plaintext password of length ' . $criticalPwLength . '+.');
	}

	/**
	 * @test
	 */
	public function noUpdateNecessityForMd5() {
		$password = 'password';
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$this->assertFalse($this->objectInstance->isHashUpdateNeeded($saltedHashPW));
	}
}
?>