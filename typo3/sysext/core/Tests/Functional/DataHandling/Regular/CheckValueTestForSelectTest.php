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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

/**
 * Functional Test for DataHandler::checkValue() concerning checkboxes
 */
class CheckValueTestForSelectTest extends AbstractDataHandlerActionTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/ImportDefault.csv');
    }

    /**
     * @test
     */
    public function selectValueMustBeDefinedInTcaItems(): void
    {
        // pid 88 comes from ImportDefault
        $result = $this->actionService->createNewRecord('tt_content', 88, [
            'tx_testdatahandler_select_dynamic' => 'predefined value',
        ]);
        $recordUid = $result['tt_content'][0];

        $record = BackendUtility::getRecord('tt_content', $recordUid);

        self::assertEquals('predefined value', $record['tx_testdatahandler_select_dynamic']);
    }

    /**
     * @test
     */
    public function selectValueMustComeFromItemsProcFuncIfNotDefinedInTcaItems(): void
    {
        // pid 88 comes from ImportDefault
        $result = $this->actionService->createNewRecord('tt_content', 88, [
            'tx_testdatahandler_select_dynamic' => 'processed value',
        ]);
        $recordUid = $result['tt_content'][0];

        $record = BackendUtility::getRecord('tt_content', $recordUid);

        self::assertEquals('processed value', $record['tx_testdatahandler_select_dynamic']);
    }
}
