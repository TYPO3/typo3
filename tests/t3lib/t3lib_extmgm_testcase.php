<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Oliver Hader <oliver@typo3.org>
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
 * Testcase for class t3lib_extMgm
 *
 * @author	Oliver Hader <oliver@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_extmgm_testcase extends tx_phpunit_testcase {
	/**
	 * Contains backup of defined GLOBALS
	 * @var array
	 */
	protected $globals = array();

	public function setUp() {
		$this->globals = array(
			'TYPO3_LOADED_EXT' => serialize($GLOBALS['TYPO3_LOADED_EXT']),
		);
	}

	/**
	 * @test
	 * @see t3lib_extMgm::getExtensionKeyByPrefix
	 */
	public function checkGetExtensionKeyByPrefix() {
		$uniqueSuffix = uniqid('test');
		$GLOBALS['TYPO3_LOADED_EXT']['tt_news' . $uniqueSuffix] = array();
		$GLOBALS['TYPO3_LOADED_EXT']['kickstarter' . $uniqueSuffix] = array();

		$this->assertEquals(
			'tt_news' . $uniqueSuffix,
			t3lib_extMgm::getExtensionKeyByPrefix('tx_ttnews' . $uniqueSuffix)
		);
		$this->assertEquals(
			'kickstarter' . $uniqueSuffix,
			t3lib_extMgm::getExtensionKeyByPrefix('tx_kickstarter' . $uniqueSuffix)
		);
		$this->assertFalse(
			t3lib_extMgm::getExtensionKeyByPrefix('tx_unloadedextension' . $uniqueSuffix)
		);
	}

	public function tearDown() {
		foreach ($this->globals as $key => $value) {
			$GLOBALS[$key] = unserialize($value);
		}
	}
}

?>