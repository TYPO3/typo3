<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Neufeind <info (at) speedpartner.de>
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
 * Testcase for class \TYPO3\CMS\Core\Utility\PathUtility
 *
 * @author Stefan Neufeind <info (at) speedpartner.de>
 */
class PathUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Data provider for cleanDirectoryNameCorrectlyCleansName
	 *
	 * @return array
	 */
	public function cleanDirectoryNameCorrectlyCleansNameDataProvider() {
		return array(
			'single-dot-elements removed' => array(
				'abc/./def/././ghi',
				'abc/def/ghi'
			),
			'double-slashes removed' => array(
				'abc//def/ghi',
				'abc/def/ghi'
			),
			'double-dot-elements to go one level higher, test #1' => array(
				'abc/def/ghi/../..',
				'abc'
			),
			'double-dot-elements to go one level higher, test #2' => array(
				'abc/def/ghi/../123/456/..',
				'abc/def/123'
			),
			'removes slash at the end' => array(
				'abc/def/ghi/',
				'abc/def/ghi'
			),
			'keeps slash in front of absolute paths' => array(
				'/abc/def/ghi/',
				'/abc/def/ghi'
			),
			'works with EXT-syntax-paths' => array(
				'EXT:abc/def/ghi/',
				'EXT:abc/def/ghi'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider cleanDirectoryNameCorrectlyCleansNameDataProvider
	 */
	public function cleanDirectoryNameCorrectlyCleansName($inputName, $expectedResult) {
		$this->assertEquals(
			$expectedResult,
			\TYPO3\CMS\Core\Utility\PathUtility::cleanDirectoryName($inputName)
		);
	}

}

?>