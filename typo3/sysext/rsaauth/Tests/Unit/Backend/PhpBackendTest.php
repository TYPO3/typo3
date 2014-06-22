<?php
namespace TYPO3\CMS\Rsaauth\Tests\Unit\Backend;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Rsaauth\Backend\PhpBackend;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class PhpBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase  {
	/**
	 * @var PhpBackend
	 */
	protected $subject = NULL;

	public function setUp() {
		$this->subject = new PhpBackend();
	}

	/**
	 * @test
	 */
	public function createNewKeyPairCreatesReadyKeyPair() {
		$this->assertTrue(
			$this->subject->createNewKeyPair()->isReady()
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