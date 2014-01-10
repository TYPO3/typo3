<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Andreas Wolf <andreas.wolf@typo3.org>
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
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\ResourceUtility;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\ResourceUtility
 */
class ResourceUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	public function recursiveFileListSortingHelperTestDataProvider() {
		return array(
			'normal file list' => array(
				array('fileB', 'fileA', 'someFile'),
				array('fileA', 'fileB', 'someFile')
			),
			'already in correct order' => array(
				array('fileA', 'fileB', 'someFile'),
				array('fileA', 'fileB', 'someFile')
			),
			'hidden file' => array(
				array('someFile', '.hiddenFile'),
				array('.hiddenFile', 'someFile')
			),
			'mixed capitalization' => array(
				array('alllower', 'allCAPS', 'ALLcaps', 'mIxedinanotherway', 'ALLCAPS', 'MiXeDcApItAlIzAtIoN'),
				array('ALLCAPS', 'ALLcaps', 'allCAPS', 'alllower', 'MiXeDcApItAlIzAtIoN', 'mIxedinanotherway')
			),
			'recursive list with one sublevel' => array(
				array('fileA', 'fileB', 'anotherDir/someFile', 'someDir/someFile', 'anotherDir/anotherFile'),
				array('anotherDir/anotherFile', 'anotherDir/someFile', 'someDir/someFile', 'fileA', 'fileB')
			),
			'recursive list with two sub-levels' => array(
				array('file', 'someDir/someFile', 'someDir/subdir/file', 'someDir/subdir/somefile', 'someDir/anotherDir/somefile', 'anotherDir/someFile'),
				array('anotherDir/someFile', 'someDir/anotherDir/somefile', 'someDir/subdir/file', 'someDir/subdir/somefile', 'someDir/someFile', 'file')
			),
			'recursive list with three sub-levels' => array(
				array('someDir/someSubdir/file', 'someDir/someSubdir/someSubsubdir/someFile', 'someDir/someSubdir/someSubsubdir/anotherFile'),
				array('someDir/someSubdir/someSubsubdir/anotherFile', 'someDir/someSubdir/someSubsubdir/someFile', 'someDir/someSubdir/file')
			)
		);
	}

	/**
	 * @dataProvider recursiveFileListSortingHelperTestDataProvider
	 * @test
	 */
	public function recursiveFileListSortingHelperCorrectlySorts($unsortedList, $expectedList) {
		$result = $unsortedList;
		usort(
			$result,
			array('\\TYPO3\\CMS\\Core\\Utility\\ResourceUtility', 'recursiveFileListSortingHelper')
		);

		$this->assertEquals($expectedList, $result);
	}
}
