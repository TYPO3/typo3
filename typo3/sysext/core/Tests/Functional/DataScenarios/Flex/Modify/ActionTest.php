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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\Flex\Modify;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\Flex\AbstractActionTestCase;

final class ActionTest extends AbstractActionTestCase
{
    #[Test]
    public function verifyCleanReferenceIndex(): void
    {
        // Fix refindex, then compare with import csv again to verify nothing changed.
        // This is to make sure the import csv is 'clean' - important for the other tests.
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(self::SCENARIO_DataSet);
    }

    #[Test]
    public function moveRecordBelowOtherRecordOnSamePage(): void
    {
        parent::moveRecordBelowOtherRecordOnSamePage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveRecordBelowOtherRecordOnSamePage.csv');
    }

    #[Test]
    public function moveRecordToDifferentPageMovesFlexChildren(): void
    {
        parent::moveRecordToDifferentPageMovesFlexChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveRecordToDifferentPageMovesFlexChildren.csv');
    }

    #[Test]
    public function moveRecordToDifferentPageBelowOtherRecordMovesFlexChildren(): void
    {
        parent::moveRecordToDifferentPageBelowOtherRecordMovesFlexChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveRecordToDifferentPageBelowOtherRecordMovesFlexChildren.csv');
    }

    #[Test]
    public function localizeRecord(): void
    {
        parent::localizeRecord();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeRecord.csv');
    }

    #[Test]
    public function deleteRecord(): void
    {
        parent::deleteRecord();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteRecord.csv');
    }

    #[Test]
    public function deleteRecordWithoutSoftDelete(): void
    {
        parent::deleteRecordWithoutSoftDelete();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteRecordWithoutSoftDelete.csv');
    }

    #[Test]
    public function deleteRecordThenHardDeleteRecord(): void
    {
        // This test is only done in live since this is a "recycler" scenario and "recycler" is disabled in workspaces.
        parent::deleteRecordThenHardDeleteRecord();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteRecordThenHardDeleteRecord.csv');
    }
}
