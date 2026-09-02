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
 *
 * A versioned record lives on the same pid as its live counterpart and starts out with the
 * very same values, so the live record must never be treated as a collision of its own
 * version - neither when the version is created nor on any later save.
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
            'input unique' => 'alpha',
            'input uniqueInPid' => 'delta',
            'input unique (l10n excluded)' => 'omega',
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
            'input unique' => 'alpha',
            // Element 3 on the target page uses "delta" as well, so this should have been
            // de-duplicated. moveRecord() evaluates "uniqueInPid" against the source page
            // instead of the target page, which is an unrelated defect of its own.
            'input uniqueInPid' => 'delta',
            'input unique (l10n excluded)' => 'omega',
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
