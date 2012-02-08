<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Oliver Hader <oliver.hader@typo3.org>
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
 * A copy is found in the textfile GPL.txt and important notices to the license
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

/**
 * Testcase for class t3lib_utility_Path
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 *
 * @package TYPO3
 * @subpackage t3lib
 */

class t3lib_utility_PathTest extends tx_phpunit_testcase {
	/**
	 * @param array $paths
	 * @param string $expected
	 * @dataProvider isCommonPrefixResolvedCorrectlyDataProvider
	 * @test
	 */
	public function isCommonPrefixResolvedCorrectly(array $paths, $expected) {
		$commonPrefix = t3lib_utility_Path::getCommonPrefix($paths);
		$this->assertEquals($expected, $commonPrefix);
	}

	/**
	 * @return array
	 */
	public function isCommonPrefixResolvedCorrectlyDataProvider() {
		return array(
			array(
				array(
					'/var/www/myhost.com/t3lib/',
				),
				'/var/www/myhost.com/t3lib/'
			),
			array(
				array(
					'/var/www/myhost.com/t3lib/',
					'/var/www/myhost.com/t3lib/',
				),
				'/var/www/myhost.com/t3lib/'
			),
			array(
				array(
					'/var/www/myhost.com/typo3/',
					'/var/www/myhost.com/t3lib/',
				),
				'/var/www/myhost.com/'
			),
			array(
				array(
					'/var/www/myhost.com/uploads/',
					'/var/www/myhost.com/typo3/',
					'/var/www/myhost.com/t3lib/',
				),
				'/var/www/myhost.com/'
			),
			array(
				array(
					'/var/www/myhost.com/uploads/directory/',
					'/var/www/myhost.com/typo3/sysext/',
					'/var/www/myhost.com/typo3/contrib/',
					'/var/www/myhost.com/t3lib/utility/',
				),
				'/var/www/myhost.com/'
			),
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
		$relativePath = t3lib_utility_Path::getRelativePath($source, $target);
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
		$sanitizedPath = t3lib_utility_Path::sanitizeTrailingSeparator($path, $separator);
		$this->assertEquals($expected, $sanitizedPath);
	}

	/**
	 * @return array
	 */
	public function isTrailingSeparatorSanitizedCorrectlyDataProvider() {
		return array(
			array('/var/www//', '/', '/var/www/'),
			array('/var/www/', '/', '/var/www/'),
			array('/var/www', '/', '/var/www/'),
		);
	}
}

?>