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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\UniqueValue\WorkspacesDiscard;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\UniqueValue\AbstractActionWorkspacesTestCase;

/**
 * Discarding must always restore the untouched live values.
 */
final class ActionTest extends AbstractActionWorkspacesTestCase
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
    public function modifyElementWithItsOwnValues(): void
    {
        parent::modifyElementWithItsOwnValues();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);

        self::assertSame([
            'input unique' => 'alpha',
            'input uniqueInPid' => 'delta',
            'input unique (l10n excluded)' => 'omega',
            'slug unique' => 'alpha',
            'slug uniqueInSite' => 'sigma',
            'slug uniqueInPid' => 'delta',
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst));
    }

    #[Test]
    public function modifyElementWithUnusedValues(): void
    {
        parent::modifyElementWithUnusedValues();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);

        self::assertSame([
            'input unique' => 'alpha',
            'input uniqueInPid' => 'delta',
            'input unique (l10n excluded)' => 'omega',
            'slug unique' => 'alpha',
            'slug uniqueInSite' => 'sigma',
            'slug uniqueInPid' => 'delta',
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst));
    }

    #[Test]
    public function modifyElementWithValuesOfAnotherElement(): void
    {
        parent::modifyElementWithValuesOfAnotherElement();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, self::VALUE_ElementIdSecond);

        self::assertSame([
            'input unique' => 'beta',
            'input uniqueInPid' => 'epsilon',
            'input unique (l10n excluded)' => 'psi',
            'slug unique' => 'beta',
            'slug uniqueInSite' => 'tau',
            'slug uniqueInPid' => 'epsilon',
        ], $this->getUniqueValues(self::VALUE_ElementIdSecond));
    }

    #[Test]
    public function createElementWithUsedValues(): void
    {
        parent::createElementWithUsedValues();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, $this->recordIds['newElementId']);

        self::assertNull(BackendUtility::getRecord(self::TABLE_Element, $this->recordIds['newElementId']));
        // The values of the element the new one was de-duplicated against are untouched
        self::assertSame([
            'input unique' => 'alpha',
            'input uniqueInPid' => 'delta',
            'input unique (l10n excluded)' => 'omega',
            'slug unique' => 'alpha',
            'slug uniqueInSite' => 'sigma',
            'slug uniqueInPid' => 'delta',
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst));
    }

    #[Test]
    public function moveElement(): void
    {
        parent::moveElement();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);

        $record = BackendUtility::getRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
        self::assertSame(self::VALUE_PageId, (int)$record['pid']);
        self::assertSame([
            'input unique' => 'alpha',
            'input uniqueInPid' => 'delta',
            'input unique (l10n excluded)' => 'omega',
            'slug unique' => 'alpha',
            'slug uniqueInSite' => 'sigma',
            'slug uniqueInPid' => 'delta',
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst));
    }
}
