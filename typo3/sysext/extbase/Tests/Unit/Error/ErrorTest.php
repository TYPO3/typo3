<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Error;

/*
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

/**
 * Test case
 */
class ErrorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theConstructorSetsTheErrorMessageCorrectly() {
		$errorMessage = 'The message';
		$error = new \TYPO3\CMS\Extbase\Error\Error($errorMessage, 0);
		$this->assertEquals($errorMessage, $error->getMessage());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theConstructorSetsTheErrorCodeCorrectly() {
		$errorCode = 123456789;
		$error = new \TYPO3\CMS\Extbase\Error\Error('', $errorCode);
		$this->assertEquals($errorCode, $error->getCode());
	}
}
