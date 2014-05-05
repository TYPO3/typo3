<?php
namespace TYPO3\CMS\Rsaauth\Tests\Unit\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Oliver Klee <typo3-coding@oliverklee.de>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Rsaauth\Backend\CommandLineBackend;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class CommandLineBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase  {
	/**
	 * @var CommandLineBackend
	 */
	protected $subject = NULL;

	public function setUp() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('This test is not available on Windows.');
		}

		$this->subject = new CommandLineBackend();
	}

	/**
	 * @test
	 */
	public function createNewKeyPairCreatesReadyKeyPair() {
		$keyPair = $this->subject->createNewKeyPair();
		if ($keyPair === NULL) {
			$this->markTestSkipped('KeyPair could not be generated. Maybe openssl was not found.');
		}

		$this->assertTrue($keyPair->isReady());
	}

	/**
	 * @test
	 */
	public function createNewKeyPairCreatesKeyPairWithDefaultExponent() {
		$keyPair = $this->subject->createNewKeyPair();
		if ($keyPair === NULL) {
			$this->markTestSkipped('KeyPair could not be generated. Maybe openssl was not found.');
		}

		$this->assertSame(
			CommandLineBackend::DEFAULT_EXPONENT,
			$keyPair->getExponent()
		);
	}

	/**
	 * @test
	 */
	public function createNewKeyPairCalledTwoTimesReturnsSameKeyPairInstance() {
		$this->assertSame(
			$this->subject->createNewKeyPair(),
			$this->subject->createNewKeyPair()
		);
	}
}