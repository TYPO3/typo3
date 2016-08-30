<?php
namespace TYPO3\CMS\Scheduler\Tests\Unit\Controller;

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

/**
 * Test case
 */
class SchedulerModuleControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController
     */
    protected $testObject;

    /**
     * Sets up each test case.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->testObject = $this->getMock(\TYPO3\CMS\Scheduler\Controller\SchedulerModuleController::class, ['dummy'], [], '', false);
    }

    /**
     * Data provider for checkDateWithStrtotimeValues
     *
     * @see checkDateWithStrtotimeValues
     * @return array Testdata for "checkDateWithStrtotimeValues".
     */
    public function checkDateWithStrtotimeValuesDataProvider()
    {
        return [
            'now' => [
                'now',
            ],
            '10 September 2000' => [
                '10 September 2000',
            ],
            '+1 day' => [
                '+1 day',
            ],
            '+1 week' => [
                '+1 week',
            ],
            '+1 week 2 days 4 hours 2 seconds' => [
                '+1 week 2 days 4 hours 2 seconds',
            ],
            'next Thursday' => [
                'next Thursday',
            ],
            'last Monday' => [
                'last Monday',
            ]
        ];
    }

    /**
     * @dataProvider checkDateWithStrtotimeValuesDataProvider
     * @test
     * @see http://de.php.net/manual/de/function.strtotime.php
     * @param string $strToTimeValue Test value which will be passed to $this->testObject->checkDate
     */
    public function checkDateWithStrtotimeValues($strToTimeValue)
    {
        $expectedTimestamp = strtotime($strToTimeValue);
        $checkDateResult = $this->testObject->checkDate($strToTimeValue);
        // We use assertLessThan here, because we test with relative values (eg. next Thursday, now, ..)
        // If this tests runs over 1 seconds the test will fail if we use assertSame / assertEquals
        // With assertLessThan the tests could run 0 till 3 seconds ($delta = 4)
        $delta = 4;
        $this->assertLessThan($delta, $checkDateResult - $expectedTimestamp, 'assertLessThan fails with value "' . $strToTimeValue . '"');
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT, $checkDateResult, 'assertType fails with value "' . $strToTimeValue . '"');
    }

    /**
     * Provides dates in TYPO3 date field formats (non-US), i.e. H:i Y-m-d
     *
     * @see checkDateWithTypo3DateSyntax
     * @return array Test data for "checkDateWithTypo3DateSyntax".
     */
    public function checkDateWithTypo3DateSyntaxDataProvider()
    {
        return [
            '00:00 2011-05-30' => [
                '00:00 2011-05-30',
                mktime(0, 0, 0, 5, 30, 2011)
            ],
            '00:01 2011-05-30' => [
                '00:01 2011-05-30',
                mktime(0, 1, 0, 5, 30, 2011)
            ],
            '23:59 2011-05-30' => [
                '23:59 2011-05-30',
                mktime(23, 59, 0, 5, 30, 2011)
            ],
            '15:35 2000-12-24' => [
                '15:35 2000-12-24',
                mktime(15, 35, 0, 12, 24, 2000)
            ],
            '00:01 1970-01-01' => [
                '00:01 1970-01-01',
                mktime(0, 1, 0, 1, 1, 1970)
            ],
            '17:26 2020-03-15' => [
                '17:26 2020-03-15',
                mktime(17, 26, 0, 3, 15, 2020)
            ],
            '1:5 2020-03-15' => [
                '1:5 2020-03-15',
                mktime(1, 5, 0, 3, 15, 2020)
            ],
            '10:50 2020-3-5' => [
                '10:50 2020-3-5',
                mktime(10, 50, 0, 3, 5, 2020)
            ],
            '01:01 1968-01-01' => [
                '01:01 1968-01-01',
                mktime(1, 1, 0, 1, 1, 1968)
            ]
        ];
    }

    /**
     * @dataProvider checkDateWithTypo3DateSyntaxDataProvider
     * @test
     * @param string $typo3DateValue Test value which will be passed to $this->testObject->checkDate
     * @param int $expectedTimestamp Expected value to compare with result from operation
     */
    public function checkDateWithTypo3DateSyntax($typo3DateValue, $expectedTimestamp)
    {
        $this->assertSame($expectedTimestamp, $this->testObject->checkDate($typo3DateValue), 'Fails with value "' . $typo3DateValue . '"');
    }

    /**
     * Provides some invalid dates
     *
     * @see checkDateWithInvalidDateValues
     * @return array Test data for "checkDateWithInvalidDateValues".
     */
    public function checkDateWithInvalidDateValuesDataProvider()
    {
        return [
            'Not Good' => [
                'Not Good'
            ],
            'HH:ii yyyy-mm-dd' => [
                'HH:ii yyyy-mm-dd'
            ]
        ];
    }

    /**
     * This test must be raised an InvalidArgumentException
     *
     * @dataProvider checkDateWithInvalidDateValuesDataProvider
     * @expectedException \InvalidArgumentException
     * @test
     * @param string $dateValue Test value which will be passed to $this->testObject->checkDate.
     */
    public function checkDateWithInvalidDateValues($dateValue)
    {
        $this->testObject->checkDate($dateValue);
    }
}
