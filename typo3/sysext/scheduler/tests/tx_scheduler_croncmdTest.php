<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase for class "tx_scheduler_CronCmd"
 *
 * @package TYPO3
 * @subpackage tx_scheduler
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class tx_scheduler_croncmdTest extends tx_phpunit_testcase {
	/**
	 * @const	integer	timestamp of 1.1.2010 0:00 (Friday)
	 */
	const TIMESTAMP = 1262300400;

	/**
	 * @test
	 */
	public function constructorSetsNormalizedCronCommandSections() {
		$instance = new tx_scheduler_CronCmd('2-3 * * * *');
		$this->assertSame($instance->getCronCommandSections(), array('2,3', '*', '*', '*', '*'));
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function constructorThrowsExceptionForInvalidCronCommand() {
		new tx_scheduler_CronCmd('61 * * * *');
	}

	/**
	 * @test
	 */
	public function constructorSetsTimestampToNowPlusOneMinuteRoundedDownToSixtySeconds() {
		$instance = new tx_scheduler_CronCmd('* * * * *');
		$this->assertSame($instance->getTimestamp(), $GLOBALS['ACCESS_TIME'] + 60);
	}

	/**
	 * @test
	 */
	public function constructorSetsTimestampToGivenTimestampPlusSixtySeconds() {
		$instance = new tx_scheduler_CronCmd('* * * * *', self::TIMESTAMP);
		$this->assertSame($instance->getTimestamp(), self::TIMESTAMP + 60);
	}

	/**
	 * @test
	 */
	public function constructorSetsTimestampToGiveTimestampRoundedDownToSixtySeconds() {
		$instance = new tx_scheduler_CronCmd('* * * * *', self::TIMESTAMP + 1);
		$this->assertSame($instance->getTimestamp(), self::TIMESTAMP + 60);
	}

	/**
	 * @return array
	 *	0 => cron command
	 *	1 => start timestamp
	 *	2 => expected timestamp after first calculateNextValue()
	 *	3 => expected timestamp after second calculateNextValue()
	 */
	public static function expectedTimestampDataProvider() {
		return array(
			'every minute' => array(
				'* * * * *',
				self::TIMESTAMP,
				self::TIMESTAMP + 60,
				self::TIMESTAMP + 120,
			),
			'once an hour at 1' => array(
				'1 * * * *',
				self::TIMESTAMP,
				self::TIMESTAMP + 60,
				self::TIMESTAMP + 60 + 60*60,
			),
			'once an hour at 0' => array(
				'0 * * * *',
				self::TIMESTAMP,
				self::TIMESTAMP + 60*60,
				self::TIMESTAMP + 60*60 + 60*60,
			),
			'once a day at 1:00' => array(
				'0 1 * * *',
				self::TIMESTAMP,
				self::TIMESTAMP + 60*60,
				self::TIMESTAMP + 60*60 + 60*60*24,
			),
			'once a day at 0:00' => array(
				'0 0 * * *',
				self::TIMESTAMP,
				self::TIMESTAMP + 60*60*24,
				self::TIMESTAMP + 60*60*24*2,
			),
			'every first day of month' => array(
				'0 0 1 * *',
				self::TIMESTAMP,
				strtotime('01-02-2010'),
				strtotime('01-03-2010'),
			),
			'once a month' => array(
				'0 0 4 * *',
				self::TIMESTAMP,
				self::TIMESTAMP + 60*60*24*3,
				self::TIMESTAMP + 60*60*24*3 + 60*60*24*31,
			),
			'once every Saturday' => array(
				'0 0 * * sat',
				self::TIMESTAMP,
				self::TIMESTAMP + 60*60*24,
				self::TIMESTAMP + 60*60*24 + 60*60*24*7,
			),
			'once every day in February' => array(
				'0 0 * feb *',
				self::TIMESTAMP,
				self::TIMESTAMP + 60*60*24*31,
				self::TIMESTAMP + 60*60*24*31 + 60*60*24
			),
			'once every February' => array(
				'0 0 1 feb *',
				self::TIMESTAMP,
				self::TIMESTAMP + 60*60*24*31,
				strtotime('01-02-2011'),
			),
			'once every Friday February' => array(
				'0 0 * feb fri',
				self::TIMESTAMP,
				strtotime('05-02-2010'),
				strtotime('12-02-2010'),
			),
			'first day in February and every Friday' => array(
				'0 0 1 feb fri',
				self::TIMESTAMP,
				strtotime('01-02-2010'),
				strtotime('05-02-2010'),
			),
			'day of week and day of month restricted, next match in day of month field' => array(
				'0 0 2 * sun',
				self::TIMESTAMP,
				self::TIMESTAMP + 60*60*24,
				self::TIMESTAMP + 60*60*24 + 60*60*24,
			),
			'day of week and day of month restricted, next match in day of week field' => array(
				'0 0 3 * sat',
				self::TIMESTAMP,
				self::TIMESTAMP + 60*60*24,
				self::TIMESTAMP + 60*60*24 + 60*60*24,
			),
			'29th February leap year' => array(
				'0 0 29 feb *',
				self::TIMESTAMP,
				strtotime('29-02-2012'),
				strtotime('29-02-2016'),
			),
			'list of minutes' => array(
				'2,4 * * * *',
				self::TIMESTAMP,
				self::TIMESTAMP + 120,
				self::TIMESTAMP + 240,
			),
			'list of hours' => array(
				'0 2,4 * * *',
				self::TIMESTAMP,
				self::TIMESTAMP + 60*60*2,
				self::TIMESTAMP + 60*60*4,
			),
			'list of days in month' => array(
				'0 0 2,4 * *',
				self::TIMESTAMP,
				strtotime('02-01-2010'),
				strtotime('04-01-2010'),
			),
			'list of month' => array(
				'0 0 1 2,3 *',
				self::TIMESTAMP,
				strtotime('01-02-2010'),
				strtotime('01-03-2010'),
			),
			'list of days of weeks' => array(
				'0 0 * * 2,4',
				self::TIMESTAMP,
				strtotime('05-01-2010'),
				strtotime('07-01-2010'),
			),
		);
	}

	/**
	 * @test
	 * @dataProvider expectedTimestampDataProvider
	 */
	public function calculateNextValueDeterminesCorrectNextTimestamp($cronCommand, $startTimestamp, $expectedTimestamp) {
		$instance = new tx_scheduler_CronCmd($cronCommand, $startTimestamp);
		$instance->calculateNextValue();
		$this->assertSame($instance->getTimestamp(), $expectedTimestamp);
	}

	/**
	 * @test
	 * @dataProvider expectedTimestampDataProvider
	 */
	public function calculateNextValueDeterminesCorrectNextTimestampOnConsecutiveCall($cronCommand, $startTimestamp, $firstTimestamp, $secondTimestamp) {
		$instance = new tx_scheduler_CronCmd($cronCommand, $firstTimestamp);
		$instance->calculateNextValue();
		$this->assertSame($instance->getTimestamp(), $secondTimestamp);
	}

	/**
	 * @test
	 */
	public function calculateNextValueDeterminesCorrectNextTimestampOnChangeToSummertime() {
		$backupTimezone = date_default_timezone_get();
		date_default_timezone_set('Europe/Berlin');
		$instance = new tx_scheduler_CronCmd('* 3 28 mar *', self::TIMESTAMP);
		$instance->calculateNextValue();
		date_default_timezone_set($backupTimezone);
		$this->assertSame($instance->getTimestamp(), 1269741600);
	}

	/**
	 * @test
	 * @expectedException RuntimeException
	 */
	public function calculateNextValueThrowsExceptionWithImpossibleCronCommand() {
		$instance = new tx_scheduler_CronCmd('* * 31 apr *', self::TIMESTAMP);
		$instance->calculateNextValue();
	}

	/**
	 * @test
	 */
	public function getTimestampReturnsInteger() {
		$instance = new tx_scheduler_CronCmd('* * * * *');
		$this->assertType('integer', $instance->getTimestamp());
	}

	/**
	 * @test
	 */
	public function getCronCommandSectionsReturnsArray() {
		$instance = new tx_scheduler_CronCmd('* * * * *');
		$this->assertType('array', $instance->getCronCommandSections());
	}
}
?>