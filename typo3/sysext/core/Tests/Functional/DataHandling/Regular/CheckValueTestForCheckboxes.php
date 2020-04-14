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
class CheckValueTestForCheckboxes extends AbstractDataHandlerActionTestCase
{

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Regular/DataSet/';

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler';

        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
    }

    /**
     * @test
     */
    public function checkBoxValueMustBeDefinedInTcaItems()
    {
        // pid 88 comes from LiveDefaultPages
        $result = $this->actionService->createNewRecord('tt_content', 88, [
            'tx_testdatahandler_checkbox' => '1'
        ]);
        $recordUid = $result['tt_content'][0];

        $record = BackendUtility::getRecord('tt_content', $recordUid);

        self::assertEquals(1, $record['tx_testdatahandler_checkbox']);
    }

    /**
     * @test
     */
    public function checkBoxValueMustComeFromItemsProcFuncIfNotDefinedInTcaItems()
    {
        // pid 88 comes from LiveDefaultPages
        $result = $this->actionService->createNewRecord('tt_content', 88, [
            'tx_testdatahandler_checkbox' => '2'
        ]);
        $recordUid = $result['tt_content'][0];

        $record = BackendUtility::getRecord('tt_content', $recordUid);

        self::assertEquals(2, $record['tx_testdatahandler_checkbox']);
    }
}
