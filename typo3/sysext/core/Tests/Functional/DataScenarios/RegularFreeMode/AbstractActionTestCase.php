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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\RegularFreeMode;

use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;

abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    use SiteBasedTestTrait;

    protected const VALUE_PageId = 89;
    protected const VALUE_PageIdTarget = 90;
    protected const VALUE_PageIdWebsite = 1;
    protected const VALUE_ContentIdFirst = 297;
    protected const VALUE_ContentIdFreeMode = 310;
    protected const VALUE_ContentIdFreeModeLocalized = 311;
    protected const VALUE_ContentIdFreeModeLocalized2 = 312;
    protected const VALUE_LanguageId = 1;
    protected const VALUE_LanguageIdSecond = 2;

    protected const TABLE_Page = 'pages';
    protected const TABLE_Content = 'tt_content';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected function setUp(): void
    {
        parent::setUp();
        // Show copied pages records in frontend request
        $GLOBALS['TCA']['pages']['ctrl']['hideAtCopy'] = false;
        // Show copied tt_content records in frontend request
        $GLOBALS['TCA']['tt_content']['ctrl']['hideAtCopy'] = false;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        $this->setUpFrontendRootPage(1, ['EXT:core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    /**
     * Note: workspaces has an additional variant of this test "localizeContentAfterMovedInLive" that performs
     * the localization of the content element after it has been moved in live first.
     *
     * @see localizeContentAfterMovedInLiveContent - additional workspace related variant
     */
    public function localizeContentAfterMovedContent(): void
    {
        // Default language element 310 on page 90 that has two 'free mode' localizations is moved to page 89.
        // Note the two localizations are NOT moved along with the default language element, due to free mode.
        // Note l10n_source of first localization 311 is kept and still points to 310, even though 310 is moved to different page.
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFreeMode, self::VALUE_PageId);
        // Create new record after (relative to) previously moved one.
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, -self::VALUE_ContentIdFreeMode, ['header' => 'Testing #1']);
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][0];
        // Localize this new record
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $newTableIds[self::TABLE_Content][0], self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][$newTableIds[self::TABLE_Content][0]];
    }

    public function copyPage(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageIdTarget];
        $this->recordIds['newContentIdTenth'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFreeMode];
        $this->recordIds['newContentIdTenthLocalized'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFreeModeLocalized];
        $this->recordIds['newContentIdTenthLocalized2'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFreeModeLocalized2];
    }
}
