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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\MultiSite;

use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Functional test for the DataHandler when handling multiple page trees
 */
class MultiSiteTest extends AbstractDataHandlerActionTestCase
{
    protected const VALUE_PageIdWebsite = 1;
    protected const VALUE_PageIdSecondSite = 50;

    protected const TABLE_Page = 'pages';

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../DataSet/ImportDefault.csv');

        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);
    }

    /**
     * @test
     * See DataSet/moveRootPageToDifferentPageTree.csv
     */
    public function moveRootPageToDifferentPageTree(): void
    {
        // Warm up caches for the root line utility to identify side effects
        GeneralUtility::makeInstance(SiteMatcher::class)->matchByPageId(self::VALUE_PageIdWebsite);
        GeneralUtility::makeInstance(SiteMatcher::class)->matchByPageId(self::VALUE_PageIdSecondSite);

        // URL is now "/1" for the second site
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdSecondSite, self::VALUE_PageIdWebsite);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveRootPageToDifferentPageTree.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdSecondSite));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Second Root Page'));
    }
}
