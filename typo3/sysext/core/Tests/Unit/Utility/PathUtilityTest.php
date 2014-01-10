<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\PathUtility
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class PathUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @param array $paths
	 * @param string $expected
	 * @dataProvider isCommonPrefixResolvedCorrectlyDataProvider
	 * @test
	 */
	public function isCommonPrefixResolvedCorrectly(array $paths, $expected) {
		$commonPrefix = \TYPO3\CMS\Core\Utility\PathUtility::getCommonPrefix($paths);
		$this->assertEquals($expected, $commonPrefix);
	}

	/**
	 * @return array
	 */
	public function isCommonPrefixResolvedCorrectlyDataProvider() {
		return array(
			array(
				array(
					'/var/www/myhost.com/t3lib/'
				),
				'/var/www/myhost.com/t3lib/'
			),
			array(
				array(
					'/var/www/myhost.com/t3lib/',
					'/var/www/myhost.com/t3lib/'
				),
				'/var/www/myhost.com/t3lib/'
			),
			array(
				array(
					'/var/www/myhost.com/typo3/',
					'/var/www/myhost.com/t3lib/'
				),
				'/var/www/myhost.com/'
			),
			array(
				array(
					'/var/www/myhost.com/uploads/',
					'/var/www/myhost.com/typo3/',
					'/var/www/myhost.com/t3lib/'
				),
				'/var/www/myhost.com/'
			),
			array(
				array(
					'/var/www/myhost.com/uploads/directory/',
					'/var/www/myhost.com/typo3/sysext/',
					'/var/www/myhost.com/typo3/contrib/',
					'/var/www/myhost.com/t3lib/utility/'
				),
				'/var/www/myhost.com/'
			),
			array(
				array(
					'C:\\www\\myhost.com\\t3lib\\'
				),
				'C:/www/myhost.com/t3lib/'
			),
			array(
				array(
					'C:\\www\\myhost.com\\t3lib\\',
					'C:\\www\\myhost.com\\t3lib\\'
				),
				'C:/www/myhost.com/t3lib/'
			),
			array(
				array(
					'C:\\www\\myhost.com\\typo3\\',
					'C:\\www\\myhost.com\\t3lib\\'
				),
				'C:/www/myhost.com/'
			),
			array(
				array(
					'C:\\www\\myhost.com\\uploads\\',
					'C:\\www\\myhost.com\\typo3\\',
					'C:\\www\\myhost.com\\t3lib\\'
				),
				'C:/www/myhost.com/'
			),
			array(
				array(
					'C:\\www\\myhost.com\\uploads\\directory\\',
					'C:\\www\\myhost.com\\typo3\\sysext\\',
					'C:\\www\\myhost.com\\typo3\\contrib\\',
					'C:\\www\\myhost.com\\t3lib\\utility\\'
				),
				'C:/www/myhost.com/'
			)
		);
	}

	/**
	 * @param string $source
	 * @param string $target
	 * @param string $expected
	 * @dataProvider isRelativePathResolvedCorrectlyDataProvider
	 * @test
	 */
	public function isRelativePathResolvedCorrectly($source, $target, $expected) {
		$relativePath = \TYPO3\CMS\Core\Utility\PathUtility::getRelativePath($source, $target);
		$this->assertEquals($expected, $relativePath);
	}

	/**
	 * @return array
	 */
	public function isRelativePathResolvedCorrectlyDataProvider() {
		return array(
			array(
				'/',
				PATH_site . 'directory',
				NULL
			),
			array(
				PATH_site . 't3lib/',
				PATH_site . 't3lib/',
				''
			),
			array(
				PATH_site . 'typo3/',
				PATH_site . 't3lib/',
				'../t3lib/'
			),
			array(
				PATH_site,
				PATH_site . 't3lib/',
				't3lib/'
			),
			array(
				PATH_site . 't3lib/',
				PATH_site . 't3lib/stddb/',
				'stddb/'
			),
			array(
				PATH_site . 'typo3/sysext/cms/',
				PATH_site . 't3lib/utility/',
				'../../../t3lib/utility/'
			),
		);
	}

	/**
	 * @param string $path
	 * @param string $separator
	 * @param string $expected
	 * @dataProvider isTrailingSeparatorSanitizedCorrectlyDataProvider
	 * @test
	 */
	public function isTrailingSeparatorSanitizedCorrectly($path, $separator, $expected) {
		$sanitizedPath = \TYPO3\CMS\Core\Utility\PathUtility::sanitizeTrailingSeparator($path, $separator);
		$this->assertEquals($expected, $sanitizedPath);
	}

	/**
	 * @return array
	 */
	public function isTrailingSeparatorSanitizedCorrectlyDataProvider() {
		return array(
			array('/var/www//', '/', '/var/www/'),
			array('/var/www/', '/', '/var/www/'),
			array('/var/www', '/', '/var/www/')
		);
	}

	/**
	 * Data Provider for getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectly
	 *
	 * @return array
	 */
	public function getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectlyDataProvider() {
		return array(
			'basic' => array(
				'/abc/def/one.txt',
				'../two.txt',
				'/abc/two.txt'
			),
			'same folder' => array(
				'/abc/one.txt',
				'./two.txt',
				'/abc/two.txt'
			),
			'preserve relative path if path goes above start path' => array(
				'abc/one.txt',
				'../../two.txt',
				'../two.txt'
			),
			'preserve absolute path even if path goes above start path' => array(
				'/abc/one.txt',
				'../../two.txt',
				'/two.txt',
			)
		);
	}

	/**
	 * @param $baseFileName
	 * @param $includeFileName
	 * @param $expectedFileName
	 * @test
	 * @dataProvider getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectlyDataProvider
	 */
	public function getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectly($baseFileName, $includeFileName, $expectedFileName) {
		$resolvedFilename = \TYPO3\CMS\Core\Utility\PathUtility::getAbsolutePathOfRelativeReferencedFileOrPath($baseFileName, $includeFileName);
		$this->assertEquals($expectedFileName, $resolvedFilename);
	}

	/**
	 * Data provider for getCanonicalPathCorrectlyCleansPath
	 *
	 * @return array
	 */
	public function getCanonicalPathCorrectlyCleansPathDataProvider() {
		return array(
			'removes single-dot-elements' => array(
				'abc/./def/././ghi',
				'abc/def/ghi'
			),
			'removes ./ at beginning' => array(
				'./abc/def/ghi',
				'abc/def/ghi'
			),
			'removes double-slashes' => array(
				'abc//def/ghi',
				'abc/def/ghi'
			),
			'removes double-slashes from front, but keeps absolute path' => array(
				'//abc/def/ghi',
				'/abc/def/ghi'
			),
			'makes double-dot-elements go one level higher, test #1' => array(
				'abc/def/ghi/../..',
				'abc'
			),
			'makes double-dot-elements go one level higher, test #2' => array(
				'abc/def/ghi/../123/456/..',
				'abc/def/123'
			),
			'makes double-dot-elements go one level higher, test #3' => array(
				'abc/../../def/ghi',
				'../def/ghi'
			),
			'makes double-dot-elements go one level higher, test #4' => array(
				'abc/def/ghi//../123/456/..',
				'abc/def/123'
			),
			'truncates slash at the end' => array(
				'abc/def/ghi/',
				'abc/def/ghi'
			),
			'keeps slash in front of absolute paths' => array(
				'/abc/def/ghi',
				'/abc/def/ghi'
			),
			'keeps slash in front of absolute paths even if double-dot-elements want to go higher' => array(
				'/abc/../../def/ghi',
				'/def/ghi'
			),
			'works with EXT-syntax-paths' => array(
				'EXT:abc/def/ghi/',
				'EXT:abc/def/ghi'
			),
			'truncates ending slash with space' => array(
				'abc/def/ ',
				'abc/def'
			),
			'truncates ending space' => array(
				'abc/def ',
				'abc/def'
			),
			'truncates ending dot' => array(
				'abc/def/.',
				'abc/def'
			),
			'does not truncates ending dot if part of name' => array(
				'abc/def.',
				'abc/def.'
			),
			'protocol is not removed' => array(
				'vfs://def/../text.txt',
				'vfs://text.txt'
			),
			'works with filenames' => array(
				'/def/../text.txt',
				'/text.txt'
			),
			'absolute windwos path' => array(
				'C:\def\..\..\test.txt',
				'C:/test.txt'
			),
			'double slashaes' => array(
				'abc//def',
				'abc/def'
			),
			'multiple slashes' => array(
				'abc///////def',
				'abc/def'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getCanonicalPathCorrectlyCleansPathDataProvider
	 */
	public function getCanonicalPathCorrectlyCleansPath($inputName, $expectedResult) {
		$className = uniqid('PathUtilityFixture');
		$fixtureClassString = '
			namespace ' . ltrim(__NAMESPACE__, '\\') . ';
			class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\PathUtility {
				static public function isWindows() {
					return TRUE;
				}
			}
		';
		eval($fixtureClassString);
		$fullyQualifiedClassName = __NAMESPACE__ . '\\' . $className;

		$this->assertEquals(
			$expectedResult,
			$fullyQualifiedClassName::getCanonicalPath($inputName)
		);
	}

}
