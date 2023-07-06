<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

final class DateTimeTest extends AbstractDataHandlerActionTestCase
{
    protected const PAGE_ID = 88;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler',
    ];

    /**
     * @test
     */
    public function testNumericConvertsToProperDateTimeValueInDatabase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');

        $this->actionService->createNewRecord('tx_testdatahandler_element', self::PAGE_ID, [
            'title' => 'Every',
            'birthday' => 586173600,
        ]);
        $addedRecord = BackendUtility::getRecord('tx_testdatahandler_element', 1);
        self::assertEquals('1988-07-29 10:00:00', $addedRecord['birthday']);
    }

    /**
     * @test
     */
    public function testDateTimeFormatConvertsToProperDateTimeValueInDatabase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');

        $this->actionService->createNewRecord('tx_testdatahandler_element', self::PAGE_ID, [
            'title' => 'Time',
            'birthday' => '1988-07-29 12:00:00',
        ]);
        $addedRecord = BackendUtility::getRecord('tx_testdatahandler_element', 1);
        self::assertEquals('1988-07-29 12:00:00', $addedRecord['birthday']);
    }
}
