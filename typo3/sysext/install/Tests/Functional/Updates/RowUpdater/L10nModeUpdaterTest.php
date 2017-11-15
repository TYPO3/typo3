<?php
namespace TYPO3\CMS\Install\Tests\Functional\Updates\RowUpdater;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\RowUpdater\L10nModeUpdater;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test Class for L10nModeUpdater
 */
class L10nModeUpdaterTest extends FunctionalTestCase
{
    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/install/Tests/Functional/Updates/RowUpdater/DataSet/';

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/install/Tests/Functional/Updates/RowUpdater/DataSet/';

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'workspaces',
    ];

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');

        $GLOBALS['TCA']['tt_content']['columns']['image']['l10n_mode'] = 'exclude';
        $GLOBALS['TCA']['tt_content']['columns']['header']['config']['behaviour']['allowLanguageSynchronization'] = true;
        $GLOBALS['TCA']['tt_content']['columns']['tx_irretutorial_1nff_hotels']['config']['behaviour']['allowLanguageSynchronization'] = true;
    }

    /**
     * @param string $dataSetName
     */
    protected function importScenarioDataSet($dataSetName)
    {
        $fileName = rtrim($this->scenarioDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        $this->importCSVDataSet($fileName);
    }

    protected function assertAssertionDataSet($dataSetName)
    {
        $fileName = rtrim($this->assertionDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        $this->assertCSVDataSet($fileName);
    }

    /**
     * @return array
     */
    protected function getTableNames(): array
    {
        return array_keys($GLOBALS['TCA']);
    }

    /**
     * @test
     */
    public function recordsCanBeUpdated()
    {
        $updater = new L10nModeUpdater();
        foreach ($this->getTableNames() as $tableName) {
            $updater->hasPotentialUpdateForTable($tableName);
            foreach ($this->getAllRecords($tableName) as $record) {
                $updater->updateTableRow($tableName, $record);
            }
        }

        $this->assertAssertionDataSet('recordsCanBeUpdated');
    }
}
