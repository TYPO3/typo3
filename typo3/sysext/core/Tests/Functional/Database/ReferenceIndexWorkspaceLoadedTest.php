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
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ReferenceIndexWorkspaceLoadedTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'workspaces',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
    ];

    #[Test]
    public function updateIndexRemovesRecordsOfNotExistingWorkspaces(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/WorkspaceLoadedUpdateIndexRemoveNonExistingWorkspaceImport.csv');
        $result = (new ReferenceIndex())->updateIndex(false);
        self::assertSame('Index table hosted 2 indexes for non-existing or deleted workspaces, now removed.', $result['errors'][0]);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/WorkspaceLoadedUpdateIndexRemoveNonExistingWorkspaceResult.csv');
    }

    #[Test]
    public function updateIndexAddsRowsForLocalSideMmHavingForeignWorkspaceRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/WorkspaceLoadedUpdateIndexAddsRowsForLocalSideMmHavingForeignWorkspaceRecordImport.csv');
        (new ReferenceIndex())->updateIndex(false);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/WorkspaceLoadedUpdateIndexAddsRowsForLocalSideMmHavingForeignWorkspaceRecordResult.csv');
    }

    #[Test]
    public function getListOfActiveWorkspacesReturnsActiveWorkspaces(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/WorkspaceLoadedActiveWorkspaces.csv');
        $subject = new ReferenceIndex();
        $subjectMethodReflection = new \ReflectionMethod($subject, 'getListOfActiveWorkspaces');
        $expected = [0, 1, 2];
        self::assertSame($expected, $subjectMethodReflection->invoke($subject));
    }
}
