<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Security\Cryptography;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class HashServiceTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	protected $hashService;

	public function setUp() {
		$this->hashService = new \TYPO3\CMS\Extbase\Security\Cryptography\HashService();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function generateHmacReturnsHashStringIfStringIsGiven() {
		$hash = $this->hashService->generateHmac('asdf');
		$this->assertTrue(is_string($hash));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function generateHmacReturnsHashStringWhichContainsSomeSalt() {
		$hash = $this->hashService->generateHmac('asdf');
		$this->assertNotEquals(sha1('asdf'), $hash);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function generateHmacReturnsDifferentHashStringsForDifferentInputStrings() {
		$hash1 = $this->hashService->generateHmac('asdf');
		$hash2 = $this->hashService->generateHmac('blubb');
		$this->assertNotEquals($hash1, $hash2);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException
	 * @author Sebastian Kurfürst
	 */
	public function generateHmacThrowsExceptionIfNoStringGiven() {
		$hash = $this->hashService->generateHmac(NULL);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function generatedHmacCanBeValidatedAgain() {
		$string = 'asdf';
		$hash = $this->hashService->generateHmac($string);
		$this->assertTrue($this->hashService->validateHmac($string, $hash));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function generatedHmacWillNotBeValidatedIfHashHasBeenChanged() {
		$string = 'asdf';
		$hash = 'myhash';
		$this->assertFalse($this->hashService->validateHmac($string, $hash));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException
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
	 * @expectedException \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException
	 */
	public function validateAndStripHmacThrowsExceptionIfNoStringGiven() {
		$this->hashService->validateAndStripHmac(NULL);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException
	 */
	public function validateAndStripHmacThrowsExceptionIfGivenStringIsTooShort() {
		$this->hashService->validateAndStripHmac('string with less than 40 characters');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Security\Exception\InvalidHashException
	 */
	public function validateAndStripHmacThrowsExceptionIfGivenStringHasNoHashAppended() {
		$this->hashService->validateAndStripHmac('string with exactly a length 40 of chars');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Security\Exception\InvalidHashException
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