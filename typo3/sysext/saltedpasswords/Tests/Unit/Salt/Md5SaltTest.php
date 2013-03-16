<?php
namespace TYPO3\CMS\Saltedpasswords\Tests\Unit\Salt;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Marcus Krause <marcus#exp2009@t3sec.info>
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
 * Testcases for Md5Salt
 *
 * @author Marcus Krause <marcus#exp2009@t3sec.info>
 */
class Md5SaltTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Keeps instance of object to test.
	 *
	 * @var \TYPO3\CMS\Saltedpasswords\Salt\Md5Salt
	 */
	protected $objectInstance = NULL;

	/**
	 * Sets up the fixtures for this testcase.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->objectInstance = $this->getMock('TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt', array('dummy'));
	}

	/**
	 * Tears down objects and settings created in this testcase.
	 *
	 * @return void
	 */
	public function tearDown() {
		$this->objectInstance = NULL;
	}

	/**
	 * Prepares a message to be shown when a salted hashing is not supported.
	 *
	 * @return string Empty string if salted hashing method is available, otherwise an according warning
	 */
	protected function getWarningWhenMethodUnavailable() {
		$warningMsg = '';
		if (!CRYPT_MD5) {
			$warningMsg = 'MD5 is not supported on your platform. ' . 'Then, some of the md5 tests will fail.';
		}
		return $warningMsg;
	}

	/**
	 * @test
	 */
	public function hasCorrectBaseClass() {
		$hasCorrectBaseClass = 0 === strcmp('TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt', get_class($this->objectInstance)) ? TRUE : FALSE;
		// XCLASS ?
		if (!$hasCorrectBaseClass && FALSE != get_parent_class($this->objectInstance)) {
			$hasCorrectBaseClass = is_subclass_of($this->objectInstance, 'TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt');
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
		$saltedHashPassword = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPassword), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * @test
	 */
	public function createdSaltedHashOfProperStructureForCustomSaltWithoutSetting() {
		$password = 'password';
		// custom salt without setting
		$randomBytes = \TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes($this->objectInstance->getSaltLength());
		$salt = $this->objectInstance->base64Encode($randomBytes, $this->objectInstance->getSaltLength());
		$this->assertTrue($this->objectInstance->isValidSalt($salt), $this->getWarningWhenMethodUnavailable());
		$saltedHashPassword = $this->objectInstance->getHashedPassword($password, $salt);
		$this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPassword), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * Tests authentication procedure with alphabet characters.
	 *
	 * Checks if a "plain-text password" is everytime mapped to the
	 * same "salted password hash" when using the same salt.
	 *
	 * @test
	 */
	public function authenticationWithValidAlphaCharClassPassword() {
		$password = 'aEjOtY';
		$saltedHashPassword = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPassword), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * Tests authentication procedure with numeric characters.
	 *
	 * Checks if a "plain-text password" is everytime mapped to the
	 * same "salted password hash" when using the same salt.
	 *
	 * @test
	 */
	public function authenticationWithValidNumericCharClassPassword() {
		$password = '01369';
		$saltedHashPassword = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPassword), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * Tests authentication procedure with US-ASCII special characters.
	 *
	 * Checks if a "plain-text password" is everytime mapped to the
	 * same "salted password hash" when using the same salt.
	 *
	 * @test
	 */
	public function authenticationWithValidAsciiSpecialCharClassPassword() {
		$password = ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
		$saltedHashPassword = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPassword), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * Tests authentication procedure with latin1 special characters.
	 *
	 * Checks if a "plain-text password" is everytime mapped to the
	 * same "salted password hash" when using the same salt.
	 *
	 * @test
	 */
	public function authenticationWithValidLatin1SpecialCharClassPassword() {
		$password = '';
		for ($i = 160; $i <= 191; $i++) {
			$password .= chr($i);
		}
		$password .= chr(215) . chr(247);
		$saltedHashPassword = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPassword), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * Tests authentication procedure with latin1 umlauts.
	 *
	 * Checks if a "plain-text password" is everytime mapped to the
	 * same "salted password hash" when using the same salt.
	 *
	 * @test
	 */
	public function authenticationWithValidLatin1UmlautCharClassPassword() {
		$password = '';
		for ($i = 192; $i <= 214; $i++) {
			$password .= chr($i);
		}
		for ($i = 216; $i <= 246; $i++) {
			$password .= chr($i);
		}
		for ($i = 248; $i <= 255; $i++) {
			$password .= chr($i);
		}
		$saltedHashPassword = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPassword), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * @test
	 */
	public function authenticationWithNonValidPassword() {
		$password = 'password';
		$password1 = $password . 'INVALID';
		$saltedHashPassword = $this->objectInstance->getHashedPassword($password);
		$this->assertFalse($this->objectInstance->checkPassword($password1, $saltedHashPassword), $this->getWarningWhenMethodUnavailable());
	}

	/**
	 * @test
	 */
	public function passwordVariationsResultInDifferentHashes() {
		$pad = 'a';
		$criticalPwLength = 0;
		// We're using a constant salt.
		$saltedHashPasswordCurrent = $salt = $this->objectInstance->getHashedPassword($pad);
		for ($i = 0; $i <= 128; $i += 8) {
			$password = str_repeat($pad, max($i, 1));
			$saltedHashPasswordPrevious = $saltedHashPasswordCurrent;
			$saltedHashPasswordCurrent = $this->objectInstance->getHashedPassword($password, $salt);
			if ($i > 0 && 0 == strcmp($saltedHashPasswordPrevious, $saltedHashPasswordCurrent)) {
				$criticalPwLength = $i;
				break;
			}
		}
		$this->assertTrue($criticalPwLength == 0 || $criticalPwLength > 32, $this->getWarningWhenMethodUnavailable() . 'Duplicates of hashed passwords with plaintext password of length ' . $criticalPwLength . '+.');
	}

	/**
	 * @test
	 */
	public function noUpdateNecessityForMd5() {
		$password = 'password';
		$saltedHashPassword = $this->objectInstance->getHashedPassword($password);
		$this->assertFalse($this->objectInstance->isHashUpdateNeeded($saltedHashPassword));
	}

}


?>