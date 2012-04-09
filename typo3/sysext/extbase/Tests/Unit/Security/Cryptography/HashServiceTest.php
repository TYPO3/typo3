<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Kurfürst <sebastian@typo3.org>
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
 * Testcase for the Hash Service
 *
 * @version $Id: HashService_testcase.php 1729 2009-11-25 21:37:20Z stucki $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Tx_Extbase_Tests_Unit_Security_Cryptography_HashServiceTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	protected $hashService;

	public function setUp() {
		$this->hashService = new Tx_Extbase_Security_Cryptography_HashService();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function generateHashReturnsHashStringIfStringIsGiven() {
		$hash = $this->hashService->generateHash('asdf');
		$this->assertTrue(is_string($hash));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function generateHashReturnsHashStringWhichContainsSomeSalt() {
		$hash = $this->hashService->generateHash('asdf');
		$this->assertNotEquals(sha1('asdf'), $hash);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function generateHashReturnsDifferentHashStringsForDifferentInputStrings() {
		$hash1 = $this->hashService->generateHash('asdf');
		$hash2 = $this->hashService->generateHash('blubb');
		$this->assertNotEquals($hash1, $hash2);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Security_Exception_InvalidArgumentForHashGeneration
	 * @author Sebastian Kurfürst
	 */
	public function generateHashThrowsExceptionIfNoStringGiven() {
		$hash = $this->hashService->generateHash(NULL);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function generatedHashCanBeValidatedAgain() {
		$string = 'asdf';
		$hash = $this->hashService->generateHash($string);
		$this->assertTrue($this->hashService->validateHash($string, $hash));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function generatedHashWillNotBeValidatedIfHashHasBeenChanged() {
		$string = 'asdf';
		$hash = 'myhash';
		$this->assertFalse($this->hashService->validateHash($string, $hash));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Security_Exception_InvalidArgumentForHashGeneration
	 */
	public function appendHmacThrowsExceptionIfNoStringGiven() {
		$this->hashService->appendHmac(NULL);
	}

	/**
	 * @test
	 */
	public function appendHmacAppendsHmacToGivenString() {
		$string = 'This is some arbitrary string ';
		$hashedString = $this->hashService->appendHmac($string);
		$this->assertSame($string, substr($hashedString, 0, -40));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Security_Exception_InvalidArgumentForHashGeneration
	 */
	public function validateAndStripHmacThrowsExceptionIfNoStringGiven() {
		$this->hashService->validateAndStripHmac(NULL);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Security_Exception_InvalidArgumentForHashGeneration
	 */
	public function validateAndStripHmacThrowsExceptionIfGivenStringIsTooShort() {
		$this->hashService->validateAndStripHmac('string with less than 40 characters');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Security_Exception_InvalidHash
	 */
	public function validateAndStripHmacThrowsExceptionIfGivenStringHasNoHashAppended() {
		$this->hashService->validateAndStripHmac('string with exactly a length 40 of chars');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Security_Exception_InvalidHash
	 */
	public function validateAndStripHmacThrowsExceptionIfTheAppendedHashIsInvalid() {
		$this->hashService->validateAndStripHmac('some Stringac43682075d36592d4cb320e69ff0aa515886eab');
	}

	/**
	 * @test
	 */
	public function validateAndStripHmacReturnsTheStringWithoutHmac() {
		$string = ' Some arbitrary string with special characters: öäüß!"§$ ';
		$hashedString = $this->hashService->appendHmac($string);
		$actualResult = $this->hashService->validateAndStripHmac($hashedString);
		$this->assertSame($string, $actualResult);
	}
}
?>