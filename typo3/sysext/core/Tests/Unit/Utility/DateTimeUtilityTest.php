<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Alexander Opitz <opitz.alexander@googlemail.com>
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

use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\DateTimeUtilityFixture;
use TYPO3\CMS\Core\Utility\DateTimeUtility;

/**
 * Test case
 *
 * @author Alexander Opitz <opitz@pluspol.info>
 */
class DateTimeUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Test the converting the string output of microtime into an float/double
	 * @test
	 */
	public function convertMicrotimeProperlyConvertsAMicrotime() {
		$this->assertSame(
			1381567522451,
			DateTimeUtility::convertMicrotime('0.45148500 1381567522')
		);
	}

	/**
	 * Test the converting the string output of microtime into an float/double
	 * @test
	 */
	public function getTimeDiffStringUnixCreatesCorrectStringResult() {
		DateTimeUtilityFixture::$mockGetTimeDiffString = '1 foo';

		$this->assertSame(
			  '1 foo',
			DateTimeUtilityFixture::getTimeDiffStringUnix(500, time(), 'foo|bar')
		);
		DateTimeUtilityFixture::$mockGetTimeDiffString = '';
	}

	///////////////////////////////////////
	// Tests concerning getTimeDiffString
	///////////////////////////////////////
	/**
	 * Data provider for getTimeDiffString function
	 *
	 * @return array
	 */
	public function getTimeDiffStringProvider() {
		$baseTime = new \DateTime();
		return array(
			'Single year' => array(
				'time1' => new \DateTime('-1year'),
				'time2' => $baseTime,
				'expectedLabel' => '1 testYear'
			),
			'Plural years' => array(
				'time1' => new \DateTime('-2years'),
				'time2' => $baseTime,
				'expectedLabel' => '2 testYears'
			),
			'Single negative year' => array(
				'time1' => new \DateTime('+1year'),
				'time2' => $baseTime,
				'expectedLabel' => '-1 testYear'
			),
			'Plural negative years' => array(
				'time1' => new \DateTime('+2years'),
				'time2' => $baseTime,
				'expectedLabel' => '-2 testYears'
			),
			'Single month' => array(
				'time1' => new \DateTime('-1month'),
				'time2' => $baseTime,
				'expectedLabel' => '1 testMonth'
			),
			'Plural months' => array(
				'time1' => new \DateTime('-2months'),
				'time2' => $baseTime,
				'expectedLabel' => '2 testMonths'
			),
			'Single negative month' => array(
				'time1' => new \DateTime('+1month'),
				'time2' => $baseTime,
				'expectedLabel' => '-1 testMonth'
			),
			'Plural negative months' => array(
				'time1' => new \DateTime('+2months'),
				'time2' => $baseTime,
				'expectedLabel' => '-2 testMonths'
			),
			'Single day' => array(
				'time1' => new \DateTime('-1day'),
				'time2' => $baseTime,
				'expectedLabel' => '1 testDay'
			),
			'Plural days' => array(
				'time1' => new \DateTime('-2days'),
				'time2' => $baseTime,
				'expectedLabel' => '2 testDays'
			),
			'Single negative day' => array(
				'time1' => new \DateTime('+1day'),
				'time2' => $baseTime,
				'expectedLabel' => '-1 testDay'
			),
			'Plural negative days' => array(
				'time1' => new \DateTime('+2days'),
				'time2' => $baseTime,
				'expectedLabel' => '-2 testDays'
			),
			'Single hour' => array(
				'time1' => new \DateTime('-1hour'),
				'time2' => $baseTime,
				'expectedLabel' => '1 testHour'
			),
			'Plural hours' => array(
				'time1' => new \DateTime('-2hours'),
				'time2' => $baseTime,
				'expectedLabel' => '2 testHours'
			),
			'Single negative hour' => array(
				'time1' => new \DateTime('+1hour'),
				'time2' => $baseTime,
				'expectedLabel' => '-1 testHour'
			),
			'Plural negative hours' => array(
				'time1' => new \DateTime('+2hours'),
				'time2' => $baseTime,
				'expectedLabel' => '-2 testHours'
			),
			'Single minute' => array(
				'time1' => new \DateTime('-1min'),
				'time2' => $baseTime,
				'expectedLabel' => '1 testMinute'
			),
			'Plural minutes' => array(
				'time1' => new \DateTime('-2min'),
				'time2' => $baseTime,
				'expectedLabel' => '2 testMinutes'
			),
			'Single negative minute' => array(
				'time1' => new \DateTime('+1min'),
				'time2' => $baseTime,
				'expectedLabel' => '-1 testMinute'
			),
			'Plural negative minutes' => array(
				'time1' => new \DateTime('+2min'),
				'time2' => $baseTime,
				'expectedLabel' => '-2 testMinutes'
			),
			'Zero seconds' => array(
				'time1' => new \DateTime(),
				'time2' => $baseTime,
				'expectedLabel' => '0 testMinutes'
			)
		);
	}

	/**
	 * Test the converting the string output of microtime into an float/double
	 * @test
	 * @dataProvider getTimeDiffStringProvider
	 */
	public function getTimeDiffStringCreatesCorrectStringResult($time1, $time2, $result) {
		$this->assertSame(
			  $result,
			  DateTimeUtility::getTimeDiffString(
				$time1,
				$time2,
				array(
					'min' => ' testMinute',
					'mins' => ' testMinutes',
					'hour' => ' testHour',
					'hours' => ' testHours',
					'day' => ' testDay',
					'days' => ' testDays',
					'month' => ' testMonth',
					'months' => ' testMonths',
					'year' => ' testYear',
					'years' => ' testYears',
				)
			)
		);
	}

	/**
	 * Data provider for getTimeDiffString function
	 * For the backward compatible version without month
	 *
	 * @return array
	 */
	public function getTimeDiffStringWithoutMonthProvider() {
		$baseTime = new \DateTime();
		return array(
			'Single year' => array(
				'time1' => new \DateTime('-1year'),
				'time2' => $baseTime,
				'expectedLabel' => '1 testYear'
			),
			'Single negative year' => array(
				'time1' => new \DateTime('+1year'),
				'time2' => $baseTime,
				'expectedLabel' => '-1 testYear'
			),
			'Single month' => array(
				'time1' => new \DateTime('-30days'),
				'time2' => $baseTime,
				'expectedLabel' => '30 testDays'
			),
			'Plural months' => array(
				'time1' => new \DateTime('-90days'),
				'time2' => $baseTime,
				'expectedLabel' => '90 testDays'
			),
			'Single negative month' => array(
				'time1' => new \DateTime('+30days'),
				'time2' => $baseTime,
				'expectedLabel' => '-30 testDays'
			),
			'Plural negative months' => array(
				'time1' => new \DateTime('+90days'),
				'time2' => $baseTime,
				'expectedLabel' => '-90 testDays'
			),
		);
	}

	/**
	 * Test the converting the string output of microtime into an float/double
	 * @test
	 * @dataProvider getTimeDiffStringWithoutMonthProvider
	 */
	public function getTimeDiffStringWithoutMonthCreatesCorrectStringResult($time1, $time2, $result) {
		$this->assertSame(
			$result,
			DateTimeUtility::getTimeDiffString(
				$time1,
				$time2,
				array(
					'min' => ' testMinute',
					'mins' => ' testMinutes',
					'hour' => ' testHour',
					'hours' => ' testHours',
					'day' => ' testDay',
					'days' => ' testDays',
					'year' => ' testYear',
					'years' => ' testYears',
				)
			)
		);
	}

	///////////////////////////////////////
	// Tests concerning splitTimeUnitsFromLabel
	///////////////////////////////////////
	/**
	 * Test the converting of the pipe split time units into an array
	 * @test
	 */
	public function splitTimeUnitsFromLabelOldPlural() {
		$this->assertSame(
			  array(
				'min' => ' mins',
				'mins' => ' mins',
				'hour' => ' hrs',
				'hours' => ' hrs',
				'day' => ' days',
				'days' => ' days',
				'year' => ' yrs',
				'years' => ' yrs',
			  ),
			  DateTimeUtility::splitTimeUnitsFromLabel('" mins| hrs| days| yrs"')
		);
	}

	/**
	 * Test the converting of the pipe split time units into an array
	 * @test
	 */
	public function splitTimeUnitsFromLabelOldSingularPlural() {
		$this->assertSame(
			  array(
				'min' => ' min',
				'mins' => ' min',
				'hour' => ' hour',
				'hours' => ' hrs',
				'day' => ' day',
				'days' => ' days',
				'year' => ' year',
				'years' => ' yrs',
			  ),
			  DateTimeUtility::splitTimeUnitsFromLabel(' min| hrs|" days"| yrs| min| hour| day| year')
		);
	}

	/**
	 * Test the converting of the pipe split time units into an array
	 * @test
	 */
	public function splitTimeUnitsFromLabelNewSingularPlural() {
		$this->assertSame(
			  array(
				'min' => ' min',
				'mins' => ' mins',
				'hour' => ' hour',
				'hours' => ' hours',
				'day' => ' day',
				'days' => ' days',
				'month' => ' month',
				'months' => ' months',
				'year' => ' year',
				'years' => ' years',
			  ),
			  DateTimeUtility::splitTimeUnitsFromLabel(' mins| hours| days| months| years| min| hour| day| month| year')
		);
	}

	/**
	 * Test the converting of the pipe split time units into an array
	 * @test
	 */
	public function splitTimeUnitsFromLabelDefaultLabels() {
		$this->assertSame(
			  array(
				'min' => ' min',
				'mins' => ' min',
				'hour' => ' hour',
				'hours' => ' hrs',
				'day' => ' day',
				'days' => ' days',
				'month' => ' month',
				'months' => ' months',
				'year' => ' year',
				'years' => ' yrs',
			  ),
			  DateTimeUtility::splitTimeUnitsFromLabel()
		);
	}

	/**
	 * Data provider for getSimpleAgeStringReturnsExpectedValues
	 * Adding one day to the years to not collide with leap years. (366)
	 *
	 * @return array
	 */
	public function getSimpleAgeStringReturnsExpectedValuesDataProvider() {
		return array(
			'Single year' => array(
				'seconds' => 60 * 60 * 24 * 366,
				'expectedLabel' => '1 year'
			),
			'Plural years' => array(
				'seconds' => 60 * 60 * 24 * 366 * 2,
				'expectedLabel' => '2 yrs'
			),
			'Single negative year' => array(
				'seconds' => 60 * 60 * 24 * 366 * -1,
				'expectedLabel' => '-1 year'
			),
			'Plural negative years' => array(
				'seconds' => 60 * 60 * 24 * 366 * 2 * -1,
				'expectedLabel' => '-2 yrs'
			),
			'Single month' => array(
				'seconds' => 60 * 60 * 24 * 31,
				'expectedLabel' => '1 month'
			),
			'Plural months' => array(
				'seconds' => 60 * 60 * 24 * 31 * 2,
				'expectedLabel' => '2 months'
			),
			'Single negative month' => array(
				'seconds' => 60 * 60 * 24 * 31 * -1,
				'expectedLabel' => '-1 month'
			),
			'Plural negative months' => array(
				'seconds' => 60 * 60 * 24 * 31 * 2 * -1,
				'expectedLabel' => '-2 months'
			),
			'Single day' => array(
				'seconds' => 60 * 60 * 24,
				'expectedLabel' => '1 day'
			),
			'Plural days' => array(
				'seconds' => 60 * 60 * 24 * 2,
				'expectedLabel' => '2 days'
			),
			'Single negative day' => array(
				'seconds' => 60 * 60 * 24 * -1,
				'expectedLabel' => '-1 day'
			),
			'Plural negative days' => array(
				'seconds' => 60 * 60 * 24 * 2 * -1,
				'expectedLabel' => '-2 days'
			),
			'Single hour' => array(
				'seconds' => 60 * 60,
				'expectedLabel' => '1 hour'
			),
			'Plural hours' => array(
				'seconds' => 60 * 60 * 2,
				'expectedLabel' => '2 hrs'
			),
			'Single negative hour' => array(
				'seconds' => 60 * 60 * -1,
				'expectedLabel' => '-1 hour'
			),
			'Plural negative hours' => array(
				'seconds' => 60 * 60 * 2 * -1,
				'expectedLabel' => '-2 hrs'
			),
			'Single minute' => array(
				'seconds' => 60,
				'expectedLabel' => '1 min'
			),
			'Plural minutes' => array(
				'seconds' => 60 * 2,
				'expectedLabel' => '2 min'
			),
			'Single negative minute' => array(
				'seconds' => 60 * -1,
				'expectedLabel' => '-1 min'
			),
			'Plural negative minutes' => array(
				'seconds' => 60 * 2 * -1,
				'expectedLabel' => '-2 min'
			),
			'Zero seconds' => array(
				'seconds' => 0,
				'expectedLabel' => '0 min'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider getSimpleAgeStringReturnsExpectedValuesDataProvider
	 */
	public function getSimpleAgeStringReturnsExpectedValues($seconds, $expectedLabel) {
		$this->assertSame($expectedLabel, DateTimeUtility::getSimpleAgeString($seconds));
	}

	/**
	 * Data provider for getSimpleAgeStringReturnsExpectedValues
	 * Adding one day to the years to not collide with leap years. (366)
	 * For the backward compatible version without month
	 *
	 * @return array
	 */
	public function getSimpleAgeStringWithoutMonthReturnsExpectedValuesDataProvider() {
		return array(
			'Single year' => array(
				'seconds' => 60 * 60 * 24 * 366,
				'expectedLabel' => '1 testYear'
			),
			'Single negative year' => array(
				'seconds' => 60 * 60 * 24 * 366 * -1,
				'expectedLabel' => '-1 testYear'
			),
			'Single month' => array(
				'seconds' => 60 * 60 * 24 * 31,
				'expectedLabel' => '31 testDays'
			),
			'Plural months' => array(
				'seconds' => 60 * 60 * 24 * 31 * 2,
				'expectedLabel' => '62 testDays'
			),
			'Single negative month' => array(
				'seconds' => 60 * 60 * 24 * 31 * -1,
				'expectedLabel' => '-31 testDays'
			),
			'Plural negative months' => array(
				'seconds' => 60 * 60 * 24 * 31 * 2 * -1,
				'expectedLabel' => '-62 testDays'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getSimpleAgeStringWithoutMonthReturnsExpectedValuesDataProvider
	 */
	public function getSimpleAgeStringWithoutMonthReturnsExpectedValues($seconds, $expectedLabel) {
		$this->assertSame(
			$expectedLabel,
			DateTimeUtility::getSimpleAgeString(
				$seconds,
				array(
					'min' => ' testMinute',
					'mins' => ' testMinutes',
					'hour' => ' testHour',
					'hours' => ' testHours',
					'day' => ' testDay',
					'days' => ' testDays',
					'year' => ' testYear',
					'years' => ' testYears',
				)
			)
		);
	}
}
