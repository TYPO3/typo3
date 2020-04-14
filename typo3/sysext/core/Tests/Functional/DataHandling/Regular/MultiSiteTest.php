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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular;

use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the DataHandler when handling multiple pagetrees
 */
class MultiSiteTest extends AbstractDataHandlerActionTestCase
{
    const VALUE_PageIdWebsite = 1;
    const VALUE_PageIdSecondSite = 50;

    const TABLE_Page = 'pages';

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Regular/MultiSite/DataSet/';

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Regular/DataSet/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importScenarioDataSet('LiveDefaultMultiSitePages');
        $this->importScenarioDataSet('LiveDefaultElements');

        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);
    }

    /**
     * @test
     * See DataSet/moveRootPageToDifferentPageTree.csv
     */
    public function moveRootPageToDifferentPageTree()
    {
        // Warm up caches for the root line utility to identify side effects
        GeneralUtility::makeInstance(SiteMatcher::class)->matchByPageId(self::VALUE_PageIdWebsite);
        GeneralUtility::makeInstance(SiteMatcher::class)->matchByPageId(self::VALUE_PageIdSecondSite);

        // URL is now "/1" for the second site
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdSecondSite, self::VALUE_PageIdWebsite);
        $this->assertAssertionDataSet('moveRootPageToDifferentPageTree');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdSecondSite)->getResponseSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Second Root Page'));
    }
}
