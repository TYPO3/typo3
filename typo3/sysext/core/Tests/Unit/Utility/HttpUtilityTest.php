<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

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
