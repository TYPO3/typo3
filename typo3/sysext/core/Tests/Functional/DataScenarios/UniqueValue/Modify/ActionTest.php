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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\UniqueValue\Modify;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\UniqueValue\AbstractActionTestCase;

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
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst));
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
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst));
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
        ], $this->getUniqueValues(self::VALUE_ElementIdSecond));
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
        ], $this->getUniqueValues($this->recordIds['newElementId']));
    }

    #[Test]
    public function createElementInOtherSiteWithUsedValues(): void
    {
        parent::createElementInOtherSiteWithUsedValues();

        self::assertSame([
            // Taken globally
            'input unique' => 'alpha0',
            // Free on page 51
            'input uniqueInPid' => 'epsilon',
            // Taken globally
            'input unique (l10n excluded)' => 'omega0',
            // Taken globally
            'slug unique' => 'alpha-1',
            // Free in site "second"
            'slug uniqueInSite' => 'tau',
            // Free on page 51
            'slug uniqueInPid' => 'epsilon',
        ], $this->getUniqueValues($this->recordIds['newElementId']));
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
        ], $this->getUniqueValues($this->recordIds['copiedElementId']));
    }

    #[Test]
    public function moveElement(): void
    {
        parent::moveElement();

        self::assertSame([
            // Still unique globally
            'input unique' => 'alpha',
            // WRONG: element 3 on the target page already uses "delta", so this should be
            // "delta0". moveRecord() calls fixUniqueInPid() with the record as it was read
            // *before* the move, so the value is checked against the source page instead of
            // the target page and the duplicate is left in place.
            'input uniqueInPid' => 'delta',
            'input unique (l10n excluded)' => 'omega',
            'slug unique' => 'alpha',
            // Target page is in the same site, "sigma" is still free there
            'slug uniqueInSite' => 'sigma',
            // WRONG: should be "delta-1" for the same reason. On top of that, fixUniqueInPid()
            // only looks at type=input and type=email fields, so a slug with eval=uniqueInPid
            // is never re-evaluated on move at all.
            'slug uniqueInPid' => 'delta',
        ], $this->getUniqueValues(self::VALUE_ElementIdFirst));
    }

    #[Test]
    public function localizeElement(): void
    {
        parent::localizeElement();

        self::assertSame([
            // Uniqueness of input fields is not language aware, so the translation is
            // de-duplicated against its own default language record
            'input unique' => 'alpha0',
            'input uniqueInPid' => 'delta0',
            // "l10n_mode=exclude" copies the value of the default language record verbatim
            'input unique (l10n excluded)' => 'omega',
            // Uniqueness of slugs *is* language aware: a translation may use the same slug
            // as its default language record, since they never compete for the same URL
            'slug unique' => 'alpha',
            'slug uniqueInSite' => 'sigma',
            'slug uniqueInPid' => 'delta',
        ], $this->getUniqueValues($this->recordIds['localizedElementId']));
    }

    /**
     * Excluded fields keep their value in the default language record as well, even
     * though a translation with the same value exists by then.
     */
    #[Test]
    public function modifyElementWithItsOwnValuesAfterLocalization(): void
    {
        parent::localizeElement();
        parent::modifyElementWithItsOwnValues();

        self::assertSame('omega', $this->getUniqueValues(self::VALUE_ElementIdFirst)['input unique (l10n excluded)']);
        self::assertSame('omega', $this->getUniqueValues($this->recordIds['localizedElementId'])['input unique (l10n excluded)']);
    }

    /**
     * A new record however must not take over the value of an excluded field.
     */
    #[Test]
    public function createElementWithUsedValuesAfterLocalization(): void
    {
        parent::localizeElement();
        parent::createElementWithUsedValues();

        self::assertSame('omega0', $this->getUniqueValues($this->recordIds['newElementId'])['input unique (l10n excluded)']);
    }

    /**
     * A record created directly in a translation must not take over the value of an
     * excluded field either.
     */
    #[Test]
    public function createTranslatedElementWithUsedValues(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Element, self::VALUE_PageId, [
            'title' => 'Created in Dansk',
            'sys_language_uid' => self::VALUE_LanguageId,
            self::FIELD_UniqueExcludedInput => 'omega',
        ]);

        self::assertSame(
            'omega0',
            $this->getUniqueValues($newTableIds[self::TABLE_Element][0])['input unique (l10n excluded)']
        );
    }

    #[Test]
    public function createElementWithEmptyValues(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Element, self::VALUE_PageId, [
            'title' => 'Created',
            self::FIELD_UniqueInput => '',
            self::FIELD_UniqueInPidInput => '',
            self::FIELD_UniqueExcludedInput => '',
        ]);

        $values = $this->getUniqueValues($newTableIds[self::TABLE_Element][0]);
        // Empty input values are never made unique
        self::assertSame('', $values['input unique']);
        self::assertSame('', $values['input uniqueInPid']);
        self::assertSame('', $values['input unique (l10n excluded)']);
        // Empty slugs are generated from the title instead
        self::assertSame('created', $values['slug unique']);
    }

    /**
     * DataHandler tries 100 alternatives only. If all of them are taken, the last
     * one is stored even though it is not unique.
     */
    #[Test]
    public function valueIsNotMadeUniqueWhenAllAlternativesAreTaken(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable(self::TABLE_Element);
        foreach (array_merge(['many'], array_map(static fn(int $i): string => 'many' . $i, range(0, 100))) as $value) {
            $connection->insert(self::TABLE_Element, [
                'pid' => self::VALUE_PageId,
                'title' => 'Filler',
                self::FIELD_UniqueInput => $value,
            ]);
        }

        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Element, self::VALUE_PageId, [
            'title' => 'Created',
            self::FIELD_UniqueInput => 'many',
        ]);

        self::assertSame('many100', $this->getUniqueValues($newTableIds[self::TABLE_Element][0])['input unique']);
    }
}
