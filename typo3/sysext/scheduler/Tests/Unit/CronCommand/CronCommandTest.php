<?php
namespace TYPO3\CMS\Scheduler\Tests\Unit\CronCommand;

/*
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

use TYPO3\CMS\Scheduler\CronCommand\CronCommand;

/**
 * Test case
 */
class CronCommandTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @const integer timestamp of 1.1.2010 0:00 (Friday), timezone UTC/GMT
     */
    const TIMESTAMP = 1262304000;

    /**
     * @var string Selected timezone backup
     */
    protected $timezoneBackup = '';

    /**
     * We're fiddling with hard timestamps in the tests, but time methods in
     * the system under test do use timezone settings. Therefore we backup the
     * current timezone setting, set it to UTC explicitly and reconstitute it
     * again in tearDown()
     */
    protected function setUp()
    {
        $this->timezoneBackup = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown()
    {
        date_default_timezone_set($this->timezoneBackup);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function constructorSetsNormalizedCronCommandSections()
    {
        $instance = new CronCommand('2-3 * * * *');
        $this->assertSame(['2,3', '*', '*', '*', '*'], $instance->getCronCommandSections());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function constructorThrowsExceptionForInvalidCronCommand()
    {
        new CronCommand('61 * * * *');
    }

    /**
     * @test
     */
    public function constructorSetsTimestampToNowPlusOneMinuteRoundedDownToSixtySeconds()
    {
        $instance = new CronCommand('* * * * *');
        $currentTime = time();
        $expectedTime = $currentTime - ($currentTime % 60) + 60;
        $this->assertSame($expectedTime, $instance->getTimestamp());
    }

    /**
     * @test
     */
    public function constructorSetsTimestampToGivenTimestampPlusSixtySeconds()
    {
        $instance = new CronCommand('* * * * *', self::TIMESTAMP);
        $this->assertSame(self::TIMESTAMP + 60, $instance->getTimestamp());
    }

    /**
     * @test
     */
    public function constructorSetsTimestampToGiveTimestampRoundedDownToSixtySeconds()
    {
        $instance = new CronCommand('* * * * *', self::TIMESTAMP + 1);
        $this->assertSame(self::TIMESTAMP + 60, $instance->getTimestamp());
    }

    /**
     * @return array
     */
    public static function expectedTimestampDataProvider()
    {
        return [
            'every minute' => [
                '* * * * *',
                self::TIMESTAMP,
                self::TIMESTAMP + 60,
                self::TIMESTAMP + 120
            ],
            'once an hour at 1' => [
                '1 * * * *',
                self::TIMESTAMP,
                self::TIMESTAMP + 60,
                self::TIMESTAMP + 60 + 60 * 60
            ],
            'once an hour at 0' => [
                '0 * * * *',
                self::TIMESTAMP,
                self::TIMESTAMP + 60 * 60,
                self::TIMESTAMP + 60 * 60 + 60 * 60
            ],
            'once a day at 1:00' => [
                '0 1 * * *',
                self::TIMESTAMP,
                self::TIMESTAMP + 60 * 60,
                self::TIMESTAMP + 60 * 60 + 60 * 60 * 24
            ],
            'once a day at 0:00' => [
                '0 0 * * *',
                self::TIMESTAMP,
                self::TIMESTAMP + 60 * 60 * 24,
                self::TIMESTAMP + 60 * 60 * 24 * 2
            ],
            'once a month' => [
                '0 0 4 * *',
                self::TIMESTAMP,
                self::TIMESTAMP + 60 * 60 * 24 * 3,
                self::TIMESTAMP + 60 * 60 * 24 * 3 + 60 * 60 * 24 * 31
            ],
            'once every Saturday' => [
                '0 0 * * sat',
                self::TIMESTAMP,
                self::TIMESTAMP + 60 * 60 * 24,
                self::TIMESTAMP + 60 * 60 * 24 + 60 * 60 * 24 * 7
            ],
            'once every day in February' => [
                '0 0 * feb *',
                self::TIMESTAMP,
                self::TIMESTAMP + 60 * 60 * 24 * 31,
                self::TIMESTAMP + 60 * 60 * 24 * 31 + 60 * 60 * 24
            ],
            'day of week and day of month restricted, next match in day of month field' => [
                '0 0 2 * sun',
                self::TIMESTAMP,
                self::TIMESTAMP + 60 * 60 * 24,
                self::TIMESTAMP + 60 * 60 * 24 + 60 * 60 * 24
            ],
            'day of week and day of month restricted, next match in day of week field' => [
                '0 0 3 * sat',
                self::TIMESTAMP,
                self::TIMESTAMP + 60 * 60 * 24,
                self::TIMESTAMP + 60 * 60 * 24 + 60 * 60 * 24
            ],
            'list of minutes' => [
                '2,4 * * * *',
                self::TIMESTAMP,
                self::TIMESTAMP + 120,
                self::TIMESTAMP + 240
            ],
            'list of hours' => [
                '0 2,4 * * *',
                self::TIMESTAMP,
                self::TIMESTAMP + 60 * 60 * 2,
                self::TIMESTAMP + 60 * 60 * 4
            ],
        ];
    }

    /**
     * @return array
     */
    public static function expectedCalculatedTimestampDataProvider()
    {
        return [
            'every first day of month' => [
                '0 0 1 * *',
                self::TIMESTAMP,
                '01-02-2010',
                '01-03-2010',
            ],
            'once every February' => [
                '0 0 1 feb *',
                self::TIMESTAMP,
                '01-02-2010',
                '01-02-2011',
            ],
            'once every Friday February' => [
                '0 0 * feb fri',
                self::TIMESTAMP,
                '05-02-2010',
                '12-02-2010',
            ],
            'first day in February and every Friday' => [
                '0 0 1 feb fri',
                self::TIMESTAMP,
                '01-02-2010',
                '05-02-2010',
            ],
            '29th February leap year' => [
                '0 0 29 feb *',
                self::TIMESTAMP,
                '29-02-2012',
                '29-02-2016',
            ],
            'list of days in month' => [
                '0 0 2,4 * *',
                self::TIMESTAMP,
                '02-01-2010',
                '04-01-2010',
            ],
            'list of month' => [
                '0 0 1 2,3 *',
                self::TIMESTAMP,
                '01-02-2010',
                '01-03-2010',
            ],
            'list of days of weeks' => [
                '0 0 * * 2,4',
                self::TIMESTAMP,
                '05-01-2010',
                '07-01-2010',
            ]
        ];
    }

    /**
     * @test
     * @dataProvider expectedTimestampDataProvider
     * @param string $cronCommand Cron command
     * @param int $startTimestamp Timestamp for start of calculation
     * @param int $expectedTimestamp Expected result (next time of execution)
     */
    public function calculateNextValueDeterminesCorrectNextTimestamp($cronCommand, $startTimestamp, $expectedTimestamp)
    {
        $instance = new CronCommand($cronCommand, $startTimestamp);
        $instance->calculateNextValue();
        $this->assertSame($expectedTimestamp, $instance->getTimestamp());
    }

    /**
     * @test
     * @dataProvider expectedCalculatedTimestampDataProvider
     * @param string $cronCommand Cron command
     * @param int $startTimestamp Timestamp for start of calculation
     * @param string $expectedTimestamp Expected result (next time of execution), to be feeded to strtotime
     */
    public function calculateNextValueDeterminesCorrectNextCalculatedTimestamp($cronCommand, $startTimestamp, $expectedTimestamp)
    {
        $instance = new CronCommand($cronCommand, $startTimestamp);
        $instance->calculateNextValue();
        $this->assertSame(strtotime($expectedTimestamp), $instance->getTimestamp());
    }

    /**
     * @test
     * @dataProvider expectedTimestampDataProvider
     * @param string $cronCommand Cron command
     * @param int $startTimestamp [unused] Timestamp for start of calculation
     * @param int $firstTimestamp Timestamp of the next execution
     * @param int $secondTimestamp Timestamp of the further execution
     */
    public function calculateNextValueDeterminesCorrectNextTimestampOnConsecutiveCall($cronCommand, $startTimestamp, $firstTimestamp, $secondTimestamp)
    {
        $instance = new CronCommand($cronCommand, $firstTimestamp);
        $instance->calculateNextValue();
        $this->assertSame($secondTimestamp, $instance->getTimestamp());
    }

    /**
     * @test
     * @dataProvider expectedCalculatedTimestampDataProvider
     * @param string $cronCommand Cron command
     * @param int $startTimestamp [unused] Timestamp for start of calculation
     * @param string $firstTimestamp Timestamp of the next execution, to be fed to strtotime
     * @param string $secondTimestamp Timestamp of the further execution, to be fed to strtotime
     */
    public function calculateNextValueDeterminesCorrectNextCalculatedTimestampOnConsecutiveCall($cronCommand, $startTimestamp, $firstTimestamp, $secondTimestamp)
    {
        $instance = new CronCommand($cronCommand, strtotime($firstTimestamp));
        $instance->calculateNextValue();
        $this->assertSame(strtotime($secondTimestamp), $instance->getTimestamp());
    }

    /**
     * @test
     */
    public function calculateNextValueDeterminesCorrectNextTimestampOnChangeToSummertime()
    {
        $backupTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
        $instance = new CronCommand('* 3 28 mar *', self::TIMESTAMP);
        $instance->calculateNextValue();
        date_default_timezone_set($backupTimezone);
        $this->assertSame(1269741600, $instance->getTimestamp());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function calculateNextValueThrowsExceptionWithImpossibleCronCommand()
    {
        $instance = new CronCommand('* * 31 apr *', self::TIMESTAMP);
        $instance->calculateNextValue();
    }

    /**
     * @test
     */
    public function getTimestampReturnsInteger()
    {
        $instance = new CronCommand('* * * * *');
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT, $instance->getTimestamp());
    }

    /**
     * @test
     */
    public function getCronCommandSectionsReturnsArray()
    {
        $instance = new CronCommand('* * * * *');
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $instance->getCronCommandSections());
    }
}
