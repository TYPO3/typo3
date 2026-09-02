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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\UniqueValue\WorkspacesPublish;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\UniqueValue\AbstractActionWorkspacesTestCase;

/**
 * Values that end up in the live workspace after publishing a single record.
 *
 * DataHandlerHook::getFieldNamesToKeep() declares every type=input and type=email field
 * with eval=unique or eval=uniqueInPid a "keep field". During version_swap() the live value
 * is swapped back into the versioned record, which is hard deleted right afterwards, so an
 * edit of such a field is never published. Slug fields are not affected by this.
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

    /**
     * The counter that https://forge.typo3.org/issues/101227 wrongly appends in the workspace
     * does not reach the live record - the "keep fields" of
     * https://forge.typo3.org/issues/52070 happen to discard it along with everything else.
     */
    #[Test]
    public function modifyElementWithItsOwnValues(): void
    {
        parent::modifyElementWithItsOwnValues();
        $this->actionService->publishRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);

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
        $this->actionService->publishRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);

        self::assertSame([
            // WRONG, see https://forge.typo3.org/issues/52070: the editor changed these to
            // "kappa", "lambda" and "my" in the workspace, the values were free, and the
            // workspace record holds them. Publishing silently drops the change and the live
            // record keeps what it had.
            'input unique' => 'alpha',
            'input uniqueInPid' => 'delta',
            'input unique (l10n excluded)' => 'omega',
            // Correct: slugs are not "keep fields" and are published as edited
            'slug unique' => 'kappa',
            'slug uniqueInSite' => 'lambda',
            'slug uniqueInPid' => 'my',
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst));
    }

    #[Test]
    public function modifyElementWithValuesOfAnotherElement(): void
    {
        parent::modifyElementWithValuesOfAnotherElement();
        $this->actionService->publishRecord(self::TABLE_Element, self::VALUE_ElementIdSecond);

        self::assertSame([
            // WRONG, see https://forge.typo3.org/issues/52070: the workspace record holds the
            // de-duplicated "alpha0", "delta0" and "omega0", live keeps its own values
            'input unique' => 'beta',
            'input uniqueInPid' => 'epsilon',
            'input unique (l10n excluded)' => 'psi',
            'slug unique' => 'alpha-1',
            'slug uniqueInSite' => 'sigma-1',
            'slug uniqueInPid' => 'delta-1',
        ], $this->getUniqueValues(self::VALUE_ElementIdSecond));
    }

    /**
     * Records created in a workspace have no live counterpart, are published by
     * publishNewRecord() instead of version_swap() and therefore keep their values.
     */
    #[Test]
    public function createElementWithUsedValues(): void
    {
        parent::createElementWithUsedValues();
        $this->actionService->publishRecord(self::TABLE_Element, $this->recordIds['newElementId']);

        self::assertSame([
            'input unique' => 'alpha0',
            'input uniqueInPid' => 'delta0',
            'input unique (l10n excluded)' => 'omega0',
            'slug unique' => 'alpha-1',
            'slug uniqueInSite' => 'sigma-1',
            'slug uniqueInPid' => 'delta-1',
        ], $this->getUniqueValues($this->recordIds['newElementId']));
    }

    #[Test]
    public function moveElement(): void
    {
        parent::moveElement();
        $this->actionService->publishRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);

        $record = BackendUtility::getRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
        self::assertSame(self::VALUE_TargetPageId, (int)$record['pid']);
        self::assertSame([
            'input unique' => 'alpha',
            // WRONG: element 3 on the target page already uses "delta", so the published record
            // should have been de-duplicated to "delta0". Neither the move in the workspace nor
            // the publishing re-evaluates the value against the target page.
            'input uniqueInPid' => 'delta',
            'input unique (l10n excluded)' => 'omega',
            'slug unique' => 'alpha',
            'slug uniqueInSite' => 'sigma',
            // WRONG: should be "delta-1" as well
            'slug uniqueInPid' => 'delta',
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst));
    }
}
