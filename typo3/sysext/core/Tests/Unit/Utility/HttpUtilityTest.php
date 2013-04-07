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
 * Testcase for class \TYPO3\CMS\Core\Utility\HttpUtility
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class HttpUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @param array $urlParts
	 * @param string $expected
	 * @dataProvider isUrlBuiltCorrectlyDataProvider
	 * @test
	 */
	public function isUrlBuiltCorrectly(array $urlParts, $expected) {
		$url = \TYPO3\CMS\Core\Utility\HttpUtility::buildUrl($urlParts);
		$this->assertEquals($expected, $url);
	}

	/**
	 * @return array
	 */
	public function isUrlBuiltCorrectlyDataProvider() {
		return array(
			'rebuild url without scheme' => array(
				parse_url('typo3.org/path/index.php'),
				'typo3.org/path/index.php'
			),
			'rebuild url with scheme' => array(
				parse_url('http://typo3.org/path/index.php'),
				'http://typo3.org/path/index.php'
			),
			'rebuild url with all properties' => array(
				parse_url('http://editor:secret@typo3.org:8080/path/index.php?query=data#fragment'),
				'http://editor:secret@typo3.org:8080/path/index.php?query=data#fragment'
			),
			'url without username, but password' => array(
				array(
					'scheme' => 'http',
					'pass' => 'secrept',
					'host' => 'typo3.org'
				),
				'http://typo3.org'
			)
		);
	}

}

?>