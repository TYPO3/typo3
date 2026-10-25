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

namespace TYPO3\CMS\Core\Tests\Functional\Database;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ReferenceIndexWorkspaceLoadedTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'workspaces',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_mm',
    ];

    #[Test]
    public function updateIndexRemovesRecordsOfNotExistingWorkspaces(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/WorkspaceLoadedUpdateIndexRemoveNonExistingWorkspaceImport.csv');
        $result = $this->get(ReferenceIndex::class)->updateIndex(false);
        self::assertSame('Index table hosted 2 indexes for non-existing or deleted workspaces, now removed.', $result['errors'][0]);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/WorkspaceLoadedUpdateIndexRemoveNonExistingWorkspaceResult.csv');
    }

    #[Test]
    public function updateIndexAddsRowsForLocalSideMmHavingForeignWorkspaceRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/WorkspaceLoadedUpdateIndexAddsRowsForLocalSideMmHavingForeignWorkspaceRecordImport.csv');
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/WorkspaceLoadedUpdateIndexAddsRowsForLocalSideMmHavingForeignWorkspaceRecordResult.csv');
    }

    #[Test]
    public function updateIndexAddsSoftReferencesOfLiveRecordToLiveWorkspaceOnly(): void
    {
        // Make tt_content the local side of a bidirectional MM relation: Its live records
        // are then indexed for each workspace, as workspace records may point to them.
        unset($GLOBALS['TCA']['tt_content']['columns']['select_mm_1_foreign']['config']['MM_opposite_field']);
        $GLOBALS['TCA']['tx_test_mm']['columns']['select_mm_1_local']['config']['MM_opposite_field'] = 'select_mm_1_foreign';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/WorkspaceLoadedUpdateIndexAddsSoftReferencesOfLiveRecordToLiveOnlyImport.csv');

        $this->get(ReferenceIndex::class)->updateIndex(false);

        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        $workspacesOfSoftReferences = $queryBuilder
            ->select('workspace')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter('tt_content')),
                $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter(297, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('field', $queryBuilder->createNamedParameter('bodytext')),
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter('pages'))
            )
            ->executeQuery()
            ->fetchFirstColumn();
        self::assertSame([0], array_map(intval(...), $workspacesOfSoftReferences));
    }

    #[Test]
    public function getListOfActiveWorkspacesReturnsActiveWorkspaces(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/WorkspaceLoadedActiveWorkspaces.csv');
        $subject = $this->get(ReferenceIndex::class);
        $subjectMethodReflection = new \ReflectionMethod($subject, 'getListOfActiveWorkspaces');
        $expected = [0, 1, 2];
        self::assertSame($expected, $subjectMethodReflection->invoke($subject));
    }
}
