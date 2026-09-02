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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\UniqueValue;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;

/**
 * Scenario for the TCA "eval" keywords "unique", "uniqueInSite" and "uniqueInPid".
 *
 * "unique" and "uniqueInPid" exist for type=input (and type=email), where DataHandler
 * appends a counter ("alpha" => "alpha0"). All three keywords exist for type=slug,
 * where SlugHelper appends a dash and a counter ("alpha" => "alpha-1"). "uniqueInSite"
 * is available for type=slug only.
 *
 * Scenario data
 * =============
 *
 * Site "test"   : root page 1 > page 88 > page 89 and page 90
 * Site "second" : root page 50 > page 51
 *
 * element 1 (page 89): unique "alpha", uniqueInPid "delta",   uniqueInSite "sigma"
 * element 2 (page 89): unique "beta",  uniqueInPid "epsilon", uniqueInSite "tau"
 * element 3 (page 90): unique "gamma", uniqueInPid "delta",   uniqueInSite "upsilon"
 * element 4 (page 51): unique "zeta",  uniqueInPid "delta",   uniqueInSite "sigma"
 *
 * "delta" is used on three different pages, which is allowed for "uniqueInPid".
 * "sigma" is used in both sites, which is allowed for "uniqueInSite".
 */
abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    use SiteBasedTestTrait;

    protected const VALUE_PageId = 89;
    protected const VALUE_TargetPageId = 90;
    protected const VALUE_OtherSitePageId = 51;
    protected const VALUE_LanguageId = 1;

    protected const VALUE_ElementIdFirst = 1;
    protected const VALUE_ElementIdSecond = 2;

    protected const TABLE_Element = 'tx_testunique_element';

    protected const FIELD_UniqueInput = 'unique_input';
    protected const FIELD_UniqueInPidInput = 'unique_in_pid_input';
    protected const FIELD_UniqueExcludedInput = 'unique_excluded_input';
    protected const FIELD_UniqueSlug = 'unique_slug';
    protected const FIELD_UniqueInSiteSlug = 'unique_in_site_slug';
    protected const FIELD_UniqueInPidSlug = 'unique_in_pid_slug';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_unique',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(static::SCENARIO_DataSet);
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/', ['EN']),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
        $this->writeSiteConfiguration(
            'second',
            $this->buildSiteConfiguration(50, 'https://second.example.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
    }

    /**
     * The six unique values of a record, keyed by the "eval" keyword they are configured with,
     * so that a failing assertion shows all of them at once.
     */
    protected function getUniqueValues(int $uid, bool $workspaceOverlay = false): array
    {
        $record = BackendUtility::getRecord(self::TABLE_Element, $uid);
        self::assertIsArray($record, 'Record ' . self::TABLE_Element . ':' . $uid . ' does not exist');
        if ($workspaceOverlay) {
            BackendUtility::workspaceOL(self::TABLE_Element, $record, $this->backendUser->workspace);
        }
        return [
            'input unique' => $record[self::FIELD_UniqueInput],
            'input uniqueInPid' => $record[self::FIELD_UniqueInPidInput],
            'input unique (l10n excluded)' => $record[self::FIELD_UniqueExcludedInput],
            'slug unique' => $record[self::FIELD_UniqueSlug],
            'slug uniqueInSite' => $record[self::FIELD_UniqueInSiteSlug],
            'slug uniqueInPid' => $record[self::FIELD_UniqueInPidSlug],
        ];
    }

    /**
     * Re-submit the values the record already has, as FormEngine does for every
     * untouched field. Nothing may change.
     */
    public function modifyElementWithItsOwnValues(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, [
            'title' => 'First changed',
            self::FIELD_UniqueInput => 'alpha',
            self::FIELD_UniqueInPidInput => 'delta',
            self::FIELD_UniqueExcludedInput => 'omega',
            self::FIELD_UniqueSlug => 'alpha',
            self::FIELD_UniqueInSiteSlug => 'sigma',
            self::FIELD_UniqueInPidSlug => 'delta',
        ]);
    }

    /**
     * Assign values that are not used anywhere. They must be stored as given.
     */
    public function modifyElementWithUnusedValues(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, [
            self::FIELD_UniqueInput => 'kappa',
            self::FIELD_UniqueInPidInput => 'lambda',
            self::FIELD_UniqueExcludedInput => 'my',
            self::FIELD_UniqueSlug => 'kappa',
            self::FIELD_UniqueInSiteSlug => 'lambda',
            self::FIELD_UniqueInPidSlug => 'my',
        ]);
    }

    /**
     * Assign the values of element 1 to element 2. All of them are taken within
     * their respective scope and must be de-duplicated.
     */
    public function modifyElementWithValuesOfAnotherElement(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Element, self::VALUE_ElementIdSecond, [
            self::FIELD_UniqueInput => 'alpha',
            self::FIELD_UniqueInPidInput => 'delta',
            self::FIELD_UniqueExcludedInput => 'omega',
            self::FIELD_UniqueSlug => 'alpha',
            self::FIELD_UniqueInSiteSlug => 'sigma',
            self::FIELD_UniqueInPidSlug => 'delta',
        ]);
    }

    /**
     * Create an element on the page of element 1, using its values.
     */
    public function createElementWithUsedValues(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Element, self::VALUE_PageId, [
            'title' => 'Created',
            self::FIELD_UniqueInput => 'alpha',
            self::FIELD_UniqueInPidInput => 'delta',
            self::FIELD_UniqueExcludedInput => 'omega',
            self::FIELD_UniqueSlug => 'alpha',
            self::FIELD_UniqueInSiteSlug => 'sigma',
            self::FIELD_UniqueInPidSlug => 'delta',
        ]);
        $this->recordIds['newElementId'] = $newTableIds[self::TABLE_Element][0];
    }

    /**
     * Create an element in the second site, using the values of element 1. Only the
     * globally unique values collide, the page and site scoped ones do not.
     */
    public function createElementInOtherSiteWithUsedValues(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Element, self::VALUE_OtherSitePageId, [
            'title' => 'Created in second site',
            self::FIELD_UniqueInput => 'alpha',
            self::FIELD_UniqueInPidInput => 'epsilon',
            self::FIELD_UniqueExcludedInput => 'omega',
            self::FIELD_UniqueSlug => 'alpha',
            self::FIELD_UniqueInSiteSlug => 'tau',
            self::FIELD_UniqueInPidSlug => 'epsilon',
        ]);
        $this->recordIds['newElementId'] = $newTableIds[self::TABLE_Element][0];
    }

    public function copyElement(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_PageId);
        $this->recordIds['copiedElementId'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdFirst];
    }

    /**
     * Move element 1 to page 90, where element 3 already uses the page scoped values.
     */
    public function moveElement(): void
    {
        $this->actionService->moveRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_TargetPageId);
    }

    public function localizeElement(): void
    {
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedElementId'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdFirst];
    }
}
