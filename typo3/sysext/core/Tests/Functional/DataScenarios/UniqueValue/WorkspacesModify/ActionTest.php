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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\UniqueValue\WorkspacesModify;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\UniqueValue\AbstractActionWorkspacesTestCase;

/**
 * Values as an editor sees them in the workspace, before publishing.
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

        self::assertSame([
            // WRONG, see https://forge.typo3.org/issues/101227: nothing was changed, so all
            // three values should be unchanged as well. Editing in a workspace writes to the
            // versioned record, which lives on the same pid as the live record and carries the
            // same values. getUniqueCountStatement() has no workspace restriction and does not
            // exclude the live counterpart of the record being checked, so the live record is
            // counted as a collision and a counter is appended on every single save.
            'input unique' => 'alpha0',
            'input uniqueInPid' => 'delta0',
            'input unique (l10n excluded)' => 'omega0',
            // Correct: SlugHelper::applyRecordConstraint() excludes the live counterpart and
            // applies a WorkspaceRestriction, which is exactly what is missing above
            'slug unique' => 'alpha',
            'slug uniqueInSite' => 'sigma',
            'slug uniqueInPid' => 'delta',
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst, true));
    }

    #[Test]
    public function modifyElementWithUnusedValues(): void
    {
        parent::modifyElementWithUnusedValues();

        self::assertSame([
            'input unique' => 'kappa',
            'input uniqueInPid' => 'lambda',
            'input unique (l10n excluded)' => 'my',
            'slug unique' => 'kappa',
            'slug uniqueInSite' => 'lambda',
            'slug uniqueInPid' => 'my',
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst, true));
    }

    #[Test]
    public function modifyElementWithValuesOfAnotherElement(): void
    {
        parent::modifyElementWithValuesOfAnotherElement();

        self::assertSame([
            'input unique' => 'alpha0',
            'input uniqueInPid' => 'delta0',
            'input unique (l10n excluded)' => 'omega0',
            'slug unique' => 'alpha-1',
            'slug uniqueInSite' => 'sigma-1',
            'slug uniqueInPid' => 'delta-1',
        ], $this->getUniqueValues(self::VALUE_ElementIdSecond, true));
    }

    #[Test]
    public function createElementWithUsedValues(): void
    {
        parent::createElementWithUsedValues();

        self::assertSame([
            'input unique' => 'alpha0',
            'input uniqueInPid' => 'delta0',
            'input unique (l10n excluded)' => 'omega0',
            'slug unique' => 'alpha-1',
            'slug uniqueInSite' => 'sigma-1',
            'slug uniqueInPid' => 'delta-1',
        ], $this->getUniqueValues($this->recordIds['newElementId'], true));
    }

    #[Test]
    public function copyElement(): void
    {
        parent::copyElement();

        self::assertSame([
            'input unique' => 'alpha0',
            'input uniqueInPid' => 'delta0',
            'input unique (l10n excluded)' => 'omega0',
            'slug unique' => 'alpha-1',
            'slug uniqueInSite' => 'sigma-1',
            'slug uniqueInPid' => 'delta-1',
        ], $this->getUniqueValues($this->recordIds['copiedElementId'], true));
    }

    #[Test]
    public function moveElement(): void
    {
        parent::moveElement();

        self::assertSame([
            // WRONG, see https://forge.typo3.org/issues/101227: moving a live record in a
            // workspace versionizes it first, and fixUniqueInPid() then compares the version
            // against its own live record. This is the "only the sorting was changed" symptom
            // of the issue - the values were never touched by the editor at all.
            'input unique' => 'alpha0',
            'input uniqueInPid' => 'delta0',
            'input unique (l10n excluded)' => 'omega0',
            'slug unique' => 'alpha',
            'slug uniqueInSite' => 'sigma',
            'slug uniqueInPid' => 'delta',
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst, true));
    }

    #[Test]
    public function localizeElement(): void
    {
        parent::localizeElement();

        self::assertSame([
            'input unique' => 'alpha0',
            'input uniqueInPid' => 'delta0',
            'input unique (l10n excluded)' => 'omega',
            'slug unique' => 'alpha',
            'slug uniqueInSite' => 'sigma',
            'slug uniqueInPid' => 'delta',
        ], $this->getUniqueValues($this->recordIds['localizedElementId'], true));
    }
}
