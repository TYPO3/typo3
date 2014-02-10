<?php
namespace TYPO3\CMS\Rsaauth\Tests\Unit;

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

use TYPO3\CMS\Rsaauth\Keypair;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class KeypairTest extends \TYPO3\CMS\Core\Tests\UnitTestCase  {
	/**
	 * @var Keypair
	 */
	protected $subject = NULL;

	public function setUp() {
		$this->subject = new Keypair();
	}

	/**
	 * @test
	 */
	public function classIsSingleton() {
		$this->assertInstanceOf(
			'TYPO3\\CMS\\Core\\SingletonInterface',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getExponentInitiallyReturnsZero() {
		$this->assertSame(
			0,
			$this->subject->getExponent()
		);
	}

	/**
	 * @test
	 */
	public function setExponentSetsExponent() {
		$this->subject->setExponent(123456);

		$this->assertSame(
			123456,
			$this->subject->getExponent()
		);
	}

	/**
	 * @test
	 *
	 * @expectedException \BadMethodCallException
	 */
	public function setExponentCalledTwoTimesThrowsException() {
		$this->subject->setExponent(123456);
		$this->subject->setExponent(123456);
	}

	/**
	 * @test
	 */
	public function getPrivateKeyInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->subject->getPrivateKey()
		);
	}

	/**
	 * @test
	 */
	public function setPrivateKeySetsPrivateKey() {
		$this->subject->setPrivateKey('foo bar');

		$this->assertSame(
			'foo bar',
			$this->subject->getPrivateKey()
		);
	}

	/**
	 * @test
	 *
	 * @expectedException \BadMethodCallException
	 */
	public function setPrivateKeyCalledTwoTimesThrowsException() {
		$this->subject->setPrivateKey('foo');
		$this->subject->setPrivateKey('foo');
	}

	/**
	 * @test
	 */
	public function getPublicKeyModulusInitiallyReturnsZero() {
		$this->assertSame(
			0,
			$this->subject->getPublicKeyModulus()
		);
	}

	/**
	 * @test
	 */
	public function setPublicKeySetsPublicKeyModulus() {
		$this->subject->setPublicKey(123456);

		$this->assertSame(
			123456,
			$this->subject->getPublicKeyModulus()
		);
	}

	/**
	 * @test
	 *
	 * @expectedException \BadMethodCallException
	 */
	public function setPublicKeyCalledTwoTimesThrowsException() {
		$this->subject->setPublicKey(123456);
		$this->subject->setPublicKey(123456);
	}

	/**
	 * @test
	 */
	public function isReadyForExponentSetAndPrivateKeySetAndPublicKeyModulusSetReturnsTrue() {
		$this->subject->setExponent(1861234);
		$this->subject->setPrivateKey('lkjasbe');
		$this->subject->setPublicKey(745786268712);

		$this->assertTrue(
			$this->subject->isReady()
		);
	}

	/**
	 * @test
	 */
	public function isReadyForNothingSetReturnsFalse() {
		$this->assertFalse(
			$this->subject->isReady()
		);
	}

	/**
	 * @test
	 */
	public function isReadyForExponentSetAndPrivateKeySetAndPublicKeyModulusMissingReturnsFalse() {
		$this->subject->setExponent(1861234);
		$this->subject->setPrivateKey('lkjasbe');

		$this->assertFalse(
			$this->subject->isReady()
		);
	}

	/**
	 * @test
	 */
	public function isReadyForExponentSetAndPrivateMissingSetAndPublicKeyModulusSetReturnsFalse() {
		$this->subject->setExponent(1861234);
		$this->subject->setPublicKey(745786268712);

		$this->assertFalse(
			$this->subject->isReady()
		);
	}

	/**
	 * @test
	 */
	public function isReadyForExponentMissingAndPrivateKeySetAndPublicKeyModulusSetReturnsFalse() {
		$this->subject->setPrivateKey('lkjasbe');
		$this->subject->setPublicKey(745786268712);

		$this->assertFalse(
			$this->subject->isReady()
		);
	}
}