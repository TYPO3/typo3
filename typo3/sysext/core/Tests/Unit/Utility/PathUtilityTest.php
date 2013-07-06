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

}

?>