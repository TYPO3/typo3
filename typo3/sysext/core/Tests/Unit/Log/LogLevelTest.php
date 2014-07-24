<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log;

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

/**
 * Test case
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class LogLevelTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function isValidLevelValidatesValidLevels() {
		$validLevels = array(0, 1, 2, 3, 4, 5, 6, 7);
		foreach ($validLevels as $validLevel) {
			$this->assertTrue(\TYPO3\CMS\Core\Log\LogLevel::isValidLevel($validLevel));
		}
	}

	/**
	 * @test
	 */
	public function isValidLevelDoesNotValidateInvalidLevels() {
		$invalidLevels = array(-1, 8, 1.5, 'string', array(), new \stdClass(), FALSE, NULL);
		foreach ($invalidLevels as $invalidLevel) {
			$this->assertFalse(\TYPO3\CMS\Core\Log\LogLevel::isValidLevel($invalidLevel));
		}
	}

	/**
	 * Data provider or isValidLevelThrowsExceptionOnInvalidLevelIfAskedToDoSo
	 */
	public function isValidLevelThrowsExceptionOnInvalidLevelIfAskedToDoSoDataProvider() {
		return array(
			'negative integer' => array(-1),
			'higher level than expected' => array(8),
			'float' => array(1.5),
			'string' => array('string'),
			'array' => array(array()),
			'object' => array(new \stdClass()),
			'boolean FALSE' => array(FALSE),
			'NULL' => array(NULL)
		);
	}

	/**
	 * @test
	 * @dataProvider isValidLevelThrowsExceptionOnInvalidLevelIfAskedToDoSoDataProvider
	 * @expectedException \Psr\Log\InvalidArgumentException
	 */
	public function isValidLevelThrowsExceptionOnInvalidLevelIfAskedToDoSo($inputValue) {
		\TYPO3\CMS\Core\Log\LogLevel::validateLevel($inputValue);
	}

	/**
	 * @test
	 */
	public function normalizeLevelConvertsValidLevelFromStringToInteger() {
		$this->assertEquals(7, \TYPO3\CMS\Core\Log\LogLevel::normalizeLevel('debug'));
	}

	/**
	 * @test
	 */
	public function normalizeLevelDoesNotConvertInvalidLevel() {
		$levelString = 'invalid';
		$this->assertEquals($levelString, \TYPO3\CMS\Core\Log\LogLevel::normalizeLevel($levelString));
	}
}
