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

use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ReferenceIndexTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_refindex_softref',
    ];

    /**
     * @test
     */
    public function updateIndexRemovesRecordsOfNotExistingWorkspaces(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/UpdateIndexRemoveNonExistingWorkspaceImport.csv');
        $result = $this->get(ReferenceIndex::class)->updateIndex(false);
        self::assertSame('Index table hosted 1 indexes for non-existing or deleted workspaces, now removed.', $result['errors'][0]);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/UpdateIndexRemoveNonExistingWorkspaceResult.csv');
    }

    /**
     * @test
     */
    public function updateIndexHandlesSoftrefForDbFieldWithEmailSoftref(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/UpdateIndexHandlesSoftrefForDbFieldWithEmailSoftrefImport.csv');
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/UpdateIndexHandlesSoftrefForDbFieldWithEmailSoftrefResult.csv');
    }

    /**
     * @test
     */
    public function updateIndexHandlesSoftrefForFlexFieldSheetField(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/UpdateIndexHandlesSoftrefForFlexFieldSheetFieldImport.csv');
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/UpdateIndexHandlesSoftrefForFlexFieldSheetFieldResult.csv');
    }

    /**
     * @test
     */
    public function updateIndexHandlesSoftrefForFlexFieldContainerElementFields(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/UpdateIndexHandlesSoftrefForFlexFieldContainerElementFieldsImport.csv');
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ReferenceIndex/UpdateIndexHandlesSoftrefForFlexFieldContainerElementFieldsResult.csv');
    }
}
