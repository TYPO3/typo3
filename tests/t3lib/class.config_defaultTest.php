<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Oliver Hader <oliver@typo3.org>
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
 * Test case for basic core related constants in config_default.php
 *
 * @author Oliver Hader <oliver@typo3.org>
 *
 * @package TYPO3
 */
class config_defaultTest extends tx_phpunit_testcase {
	/**
	 * Tests whether an accordant PHP extension is denied.
	 *
	 * @param string phpExtension
	 * @dataProvider phpExtensionsDataProvider
	 * @test
	 */
	public function fileDenyPatternMatchesPhpExtension($phpExtension) {
		$this->assertGreaterThan(0, preg_match('/' . FILE_DENY_PATTERN_DEFAULT . '/', $phpExtension), $phpExtension);
	}

	/**
	 * Data provider for 'fileDenyPatternMatchesPhpExtension' test case.
	 *
	 * @return array
	 */
	public function phpExtensionsDataProvider() {
		$fileName = uniqid('filename');

		$data = array();
		$phpExtensions = t3lib_div::trimExplode(',', 'php,php3,php4,php5,php6,phpsh,phtml', TRUE);

		foreach ($phpExtensions as $extension) {
			$data[] = array($fileName . '.' . $extension);
			$data[] = array($fileName . '.' . $extension . '.txt');
		}

		return $data;
	}
}
?>