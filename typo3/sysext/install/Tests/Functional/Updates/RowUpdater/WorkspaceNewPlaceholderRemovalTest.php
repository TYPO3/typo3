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
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Install\Updates\DatabaseRowsUpdateWizard;
use TYPO3\CMS\Install\Updates\RowUpdater\WorkspaceNewPlaceholderRemovalMigration;

class WorkspaceNewPlaceholderRemovalTest extends AbstractDataHandlerActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/install/Tests/Functional/Updates/RowUpdater/Fixtures/';

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/install/Tests/Functional/Updates/RowUpdater/Fixtures/';

    protected $coreExtensionsToLoad = ['workspaces'];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
    ];

    /**
     * @var bool Reference index will be broken after this row updater and is not checked.
     */
    protected $assertCleanReferenceIndex = false;

    /**
     * @var MockObject|DatabaseRowsUpdateWizard|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actionService = $this->getActionService();
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
        $this->importScenarioDataSet('WorkspaceNewPlaceholderRemovalIrreCsvImport');
        $this->subject->executeUpdate();
        $this->assertAssertionDataSet('WorkspaceNewPlaceholderRemovalIrreCsvResult');
    }

    /**
     * @test
     */
    public function workspaceRecordsUpdatedWithIrreForeignField(): void
    {
        // Data set inspired by workspaces IRRE/ForeignField/Modify/DataSet/copyPage.csv
        $this->importScenarioDataSet('WorkspaceNewPlaceholderRemovalIrreForeignFieldImport');
        $this->subject->executeUpdate();
        $this->assertAssertionDataSet('WorkspaceNewPlaceholderRemovalIrreForeignFieldResult');
    }

    /**
     * @test
     */
    public function workspaceRecordsUpdatedWithManyToMany(): void
    {
        // Data set inspired by workspaces ManyToMany/Modify/DataSet/copyPage.csv
        $this->importScenarioDataSet('WorkspaceNewPlaceholderRemovalManyToManyImport');
        $this->subject->executeUpdate();
        $this->assertAssertionDataSet('WorkspaceNewPlaceholderRemovalManyToManyResult');
    }
}
