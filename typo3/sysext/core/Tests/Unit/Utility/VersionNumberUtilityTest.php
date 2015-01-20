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

use \TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\VersionNumberUtility
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 */
class VersionNumberUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Data Provider for convertVersionNumberToIntegerConvertsVersionNumbersToIntegers
	 *
	 * @return array
	 */
	public function validVersionNumberDataProvider() {
		return array(
			array('4003003', '4.3.3'),
			array('4012003', '4.12.3'),
			array('5000000', '5.0.0'),
			array('3008001', '3.8.1'),
			array('1012', '0.1.12')
		);
	}

	/**
	 * Data Provider for convertIntegerToVersionNumberConvertsOtherTypesAsIntegerToVersionNumber
	 *
	 * @see http://php.net/manual/en/language.types.php
	 * @return array
	 */
	public function invalidVersionNumberDataProvider() {
		return array(
			'boolean' => array(TRUE),
			'float' => array(5.4),
			'array' => array(array()),
			'string' => array('300ABCD'),
			'object' => array(new \stdClass()),
			'NULL' => array(NULL),
			'function' => array(function () {

			})
		);
	}

	/**
	 * @test
	 * @dataProvider validVersionNumberDataProvider
	 */
	public function convertVersionNumberToIntegerConvertsVersionNumbersToIntegers($expected, $version) {
		$this->assertEquals($expected, VersionNumberUtility::convertVersionNumberToInteger($version));
	}

	/**
	 * @test
	 * @dataProvider validVersionNumberDataProvider
	 */
	public function convertIntegerToVersionNumberConvertsIntegerToVersionNumber($versionNumber, $expected) {
		// Make sure incoming value is an integer
		$versionNumber = (int)$versionNumber;
		$this->assertEquals($expected, VersionNumberUtility::convertIntegerToVersionNumber($versionNumber));
	}

	/**
	 * @test
	 * @dataProvider invalidVersionNumberDataProvider
	 */
	public function convertIntegerToVersionNumberConvertsOtherTypesAsIntegerToVersionNumber($version) {
		$this->setExpectedException('\\InvalidArgumentException', '', 1334072223);
		VersionNumberUtility::convertIntegerToVersionNumber($version);
	}

	/**
	 * @return array
	 */
	public function getNumericTypo3VersionNumberDataProvider() {
		return array(
			array(
				'6.0-dev',
				'6.0.0'
			),
			array(
				'4.5-alpha',
				'4.5.0'
			),
			array(
				'4.5-beta',
				'4.5.0'
			),
			array(
				'4.5-RC',
				'4.5.0'
			),
			array(
				'6.0.1',
				'6.0.1'
			),
			array(
				'6.2.0beta5',
				'6.2.0'
			),
		);
	}

	/**
	 * Check whether getNumericTypo3Version handles all kinds of valid
	 * version strings
	 *
	 * @dataProvider getNumericTypo3VersionNumberDataProvider
	 * @test
	 * @param string $currentVersion
	 * @param string $expectedVersion
	 */
	public function getNumericTypo3VersionNumber($currentVersion, $expectedVersion) {
		$className = $this->getUniqueId('VersionNumberUtility');
		eval(
			'namespace ' . __NAMESPACE__ . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\VersionNumberUtility {' .
			'  public static function getCurrentTypo3Version() {' .
			'    return \'' . $currentVersion . '\';' .
			'  }' .
			'}'
		);
		$className = __NAMESPACE__ . '\\' . $className;
		$this->assertEquals($expectedVersion, $className::getNumericTypo3Version());
	}

	/**
	 * Data provider for convertVersionsStringToVersionNumbersForcesVersionNumberInRange
	 *
	 * @return array
	 */
	public function convertVersionsStringToVersionNumbersForcesVersionNumberInRangeDataProvider() {
		return array(
			'everything ok' => array(
				'4.2.0-4.4.99',
				array(
					'4.2.0',
					'4.4.99'
				)
			),
			'too high value' => array(
				'4.2.0-4.4.2990',
				array(
					'4.2.0',
					'4.4.999'
				)
			),
			'empty high value' => array(
				'4.2.0-0.0.0',
				array(
					'4.2.0',
					''
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider convertVersionsStringToVersionNumbersForcesVersionNumberInRangeDataProvider
	 */
	public function convertVersionsStringToVersionNumbersForcesVersionNumberInRange($versionString, $expectedResult) {
		$versions = VersionNumberUtility::convertVersionsStringToVersionNumbers($versionString);
		$this->assertEquals($expectedResult, $versions);
	}
}
