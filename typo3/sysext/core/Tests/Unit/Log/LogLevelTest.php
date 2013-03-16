<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Ingo Renner (ingo@typo3.org)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for \TYPO3\CMS\Core\Log\Level.
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class LevelTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

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
	 * @expectedException \RangeException
	 */
	public function isValidLevelThrowsExceptionOnInvalidLevelIfAskedToDoSo($inputValue) {
		\TYPO3\CMS\Core\Log\LogLevel::validateLevel($inputValue);
	}

}

?>