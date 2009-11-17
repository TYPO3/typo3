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
 * Contains class "tx_saltedpasswords_salts_blowfish"
 * that provides Blowfish salted hashing.
 *
 * $Id$
 */

/**
 * Testcases for class tx_saltedpasswords_salts_blowfish.
 *
 * @author  Marcus Krause <marcus#exp2009@t3sec.info>
 * @package  TYPO3
 * @subpackage  tx_saltedpasswords
 */
class tx_saltedpasswords_salts_blowfish_testcase extends tx_phpunit_testcase {


	/**
	 * Keeps instance of object to test.
	 *
	 * @var tx_saltedpasswords_salts_blowfish
	 */
	protected $objectInstance = NULL;


	/**
	 * Sets up the fixtures for this testcase.
	 *
	 * @return	void
	 */
	public function setUp() {
		$this->objectInstance = t3lib_div::makeInstance('tx_saltedpasswords_salts_blowfish');
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
	 * Marks tests as skipped if the blowfish method is not available.
	 *
	 * @return	void
	 */
	protected function skipTestIfBlowfishIsNotAvailable() {
		if (!CRYPT_BLOWFISH) {
			$this->markTestSkipped('Blowfish is not supported on your platform.');
		}
	}

	/**
	 * @test
	 */
	public function hasCorrectBaseClass() {

		$hasCorrectBaseClass = (0 === strcmp('tx_saltedpasswords_salts_blowfish', get_class($this->objectInstance))) ? TRUE : FALSE;

			// XCLASS ?
		if (!$hasCorrectBaseClass && FALSE != get_parent_class($this->objectInstance)) {
			$hasCorrectBaseClass = is_subclass_of($this->objectInstance, 'tx_saltedpasswords_salts_blowfish');
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
		$this->skipTestIfBlowfishIsNotAvailable();

		$password = 'a';
		$this->assertNotNull($this->objectInstance->getHashedPassword($password));
	}

	/**
	 * @test
	 */
	public function createdSaltedHashOfProperStructure() {
		$this->skipTestIfBlowfishIsNotAvailable();

		$password = 'password';
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPW));
	}

	/**
	 * @test
	 */
	public function createdSaltedHashOfProperStructureForCustomSaltWithoutSetting() {
		$this->skipTestIfBlowfishIsNotAvailable();

		$password = 'password';

			// custom salt without setting
		$randomBytes = t3lib_div::generateRandomBytes($this->objectInstance->getSaltLength());
		$salt = $this->objectInstance->base64Encode($randomBytes, $this->objectInstance->getSaltLength());
		$this->assertTrue($this->objectInstance->isValidSalt($salt));

		$saltedHashPW = $this->objectInstance->getHashedPassword($password, $salt);
		$this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPW));
	}

	/**
	 * @test
	 */
	public function createdSaltedHashOfProperStructureForMaximumHashCount() {
		$this->skipTestIfBlowfishIsNotAvailable();

		$password = 'password';
		$maxHashCount = $this->objectInstance->getMaxHashCount();
		$this->objectInstance->setHashCount($maxHashCount);
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPW));
			// reset hashcount
		$this->objectInstance->setHashCount(NULL);
	}

	/**
	 * @test
	 */
	public function createdSaltedHashOfProperStructureForMinimumHashCount() {
		$this->skipTestIfBlowfishIsNotAvailable();

		$password = 'password';
		$minHashCount = $this->objectInstance->getMinHashCount();
		$this->objectInstance->setHashCount($minHashCount);
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->isValidSaltedPW($saltedHashPW));
			// reset hashcount
		$this->objectInstance->setHashCount(NULL);
	}

	/**
	 * @test
	 */
	public function authenticationWithValidPassword() {
		$this->skipTestIfBlowfishIsNotAvailable();

		$password = 'password';
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$this->assertTrue($this->objectInstance->checkPassword($password, $saltedHashPW));
	}

	/**
	 * @test
	 */
	public function authenticationWithNonValidPassword() {
		$this->skipTestIfBlowfishIsNotAvailable();

		$password = 'password';
		$password1 = $password . 'INVALID';
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$this->assertFalse($this->objectInstance->checkPassword($password1, $saltedHashPW));
	}

	/**
	 * @test
	 */
	public function passwordVariationsResultInDifferentHashes() {
		$this->skipTestIfBlowfishIsNotAvailable();

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
		$this->assertTrue(($criticalPwLength == 0) || ($criticalPwLength > 32), 'Duplicates of hashed passwords with plaintext password of length ' . $criticalPwLength . '+.');
	}

	/**
	 * @test
	 */
	public function modifiedMinHashCount() {
		$minHashCount = $this->objectInstance->getMinHashCount();
		$this->objectInstance->setMinHashCount($minHashCount - 1);
		$this->assertTrue($this->objectInstance->getMinHashCount() < $minHashCount);
		$this->objectInstance->setMinHashCount($minHashCount + 1);
		$this->assertTrue($this->objectInstance->getMinHashCount() > $minHashCount);
	}

	/**
	 * @test
	 */
	public function modifiedMaxHashCount() {
		$maxHashCount = $this->objectInstance->getMaxHashCount();
		$this->objectInstance->setMaxHashCount($maxHashCount + 1);
		$this->assertTrue($this->objectInstance->getMaxHashCount() > $maxHashCount);
		$this->objectInstance->setMaxHashCount($maxHashCount - 1);
		$this->assertTrue($this->objectInstance->getMaxHashCount() < $maxHashCount);
	}

	/**
	 * @test
	 */
	public function modifiedHashCount() {
		$hashCount = $this->objectInstance->getHashCount();
		$this->objectInstance->setMaxHashCount($hashCount + 1);
		$this->objectInstance->setHashCount($hashCount + 1);
		$this->assertTrue($this->objectInstance->getHashCount() > $hashCount);
		$this->objectInstance->setMinHashCount($hashCount - 1);
		$this->objectInstance->setHashCount($hashCount - 1);
		$this->assertTrue($this->objectInstance->getHashCount() < $hashCount);
			// reset hashcount
		$this->objectInstance->setHashCount(NULL);
	}

	/**
	 * @test
	 */
	public function updateNecessityForValidSaltedPassword() {
		$this->skipTestIfBlowfishIsNotAvailable();

		$password = 'password';
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$this->assertFalse($this->objectInstance->isHashUpdateNeeded($saltedHashPW));
	}

	/**
	 * @test
	 */
	public function updateNecessityForIncreasedHashcount() {
		$password = 'password';
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$increasedHashCount = $this->objectInstance->getHashCount() + 1;
		$this->objectInstance->setMaxHashCount($increasedHashCount);
		$this->objectInstance->setHashCount($increasedHashCount);
		$this->assertTrue($this->objectInstance->isHashUpdateNeeded($saltedHashPW));
			// reset hashcount
		$this->objectInstance->setHashCount(NULL);
	}

	/**
	 * @test
	 */
	public function updateNecessityForDecreasedHashcount() {
		$this->skipTestIfBlowfishIsNotAvailable();

		$password = 'password';
		$saltedHashPW = $this->objectInstance->getHashedPassword($password);
		$decreasedHashCount = $this->objectInstance->getHashCount() - 1;
		$this->objectInstance->setMinHashCount($decreasedHashCount);
		$this->objectInstance->setHashCount($decreasedHashCount);
		$this->assertFalse($this->objectInstance->isHashUpdateNeeded($saltedHashPW));
			// reset hashcount
		$this->objectInstance->setHashCount(NULL);
	}
}
?>