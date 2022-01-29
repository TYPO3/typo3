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

namespace TYPO3\CMS\Install\Tests\Functional\Updates\RowUpdater;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseRowsUpdateWizard;
use TYPO3\CMS\Install\Updates\RowUpdater\WorkspaceNewPlaceholderRemovalMigration;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class WorkspaceNewPlaceholderRemovalTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv',
    ];

    /**
     * @var MockObject|DatabaseRowsUpdateWizard|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    protected ActionService $actionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actionService = GeneralUtility::makeInstance(ActionService::class);
        // Register only WorkspaceNewPlaceholderRemovalMigration in the row updater wizard
        $this->subject = $this->getAccessibleMock(DatabaseRowsUpdateWizard::class, ['dummy']);
        $this->subject->_set('rowUpdater', [WorkspaceNewPlaceholderRemovalMigration::class]);
    }

    /**
     * @test
     */
    public function workspaceRecordsUpdatedWithIrreCsv(): void
    {
        // Data set inspired by workspaces IRRE/CSV/Modify/DataSet/copyPage.csv
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceNewPlaceholderRemovalIrreCsvImport.csv');
        $this->subject->executeUpdate();
        $this->assertCSVDataSet('typo3/sysext/install/Tests/Functional/Updates/RowUpdater/Fixtures/WorkspaceNewPlaceholderRemovalIrreCsvResult.csv');
    }

    /**
     * @test
     */
    public function workspaceRecordsUpdatedWithIrreForeignField(): void
    {
        // Data set inspired by workspaces IRRE/ForeignField/Modify/DataSet/copyPage.csv
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceNewPlaceholderRemovalIrreForeignFieldImport.csv');
        $this->subject->executeUpdate();
        $this->assertCSVDataSet('typo3/sysext/install/Tests/Functional/Updates/RowUpdater/Fixtures/WorkspaceNewPlaceholderRemovalIrreForeignFieldResult.csv');
    }

    /**
     * @test
     */
    public function workspaceRecordsUpdatedWithManyToMany(): void
    {
        // Data set inspired by workspaces ManyToMany/Modify/DataSet/copyPage.csv
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceNewPlaceholderRemovalManyToManyImport.csv');
        $this->subject->executeUpdate();
        $this->assertCSVDataSet('typo3/sysext/install/Tests/Functional/Updates/RowUpdater/Fixtures/WorkspaceNewPlaceholderRemovalManyToManyResult.csv');
    }
}
