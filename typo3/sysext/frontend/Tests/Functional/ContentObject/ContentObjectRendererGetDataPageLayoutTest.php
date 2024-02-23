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

namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests for COR->getData() with pagelayout.
 */
final class ContentObjectRendererGetDataPageLayoutTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];
    private const ROOT_PAGE_ID = 1;

    public function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'tree_page_layout_test',
            $this->buildSiteConfiguration(self::ROOT_PAGE_ID, '/'),
        );
    }

    #[Test]
    public function pageLayoutResolvedForBackendLayoutOnRootPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContentObjectRendererGetDataPageLayout/backendLayoutOnRootPage.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:frontend/Tests/Functional/ContentObject/Fixtures/ContentObjectRendererGetDataPageLayout/setup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('pagets__default', (string)$response->getBody());
    }

    #[Test]
    public function pageLayoutNotResolvedForBackendLayoutNextLevelOnRootPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContentObjectRendererGetDataPageLayout/backendLayoutNextLevelOnRootPage.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:frontend/Tests/Functional/ContentObject/Fixtures/ContentObjectRendererGetDataPageLayout/setup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringNotContainsString('pagets__default', (string)$response->getBody());
    }

    #[Test]
    public function pageLayoutResolvedForBackendLayoutNextLevelInheritedOnSubpageLevel1(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContentObjectRendererGetDataPageLayout/backendLayoutAndNextLevelOnRootPage.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:frontend/Tests/Functional/ContentObject/Fixtures/ContentObjectRendererGetDataPageLayout/setup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(2));
        self::assertStringContainsString('pagets__inherit', (string)$response->getBody());
    }

    #[Test]
    public function pageLayoutResolvedForBackendLayoutNextLevelInheritedOnSubpageLevel2(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContentObjectRendererGetDataPageLayout/backendLayoutAndNextLevelOnRootPageSubOverride1.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:frontend/Tests/Functional/ContentObject/Fixtures/ContentObjectRendererGetDataPageLayout/setup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(3));
        self::assertStringContainsString('pagets__inherit', (string)$response->getBody());
    }

    #[Test]
    public function pageLayoutResolvedForBackendLayoutOnSubpageLevel3(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContentObjectRendererGetDataPageLayout/backendLayoutAndNextLevelOnRootPageSubOverride2.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:frontend/Tests/Functional/ContentObject/Fixtures/ContentObjectRendererGetDataPageLayout/setup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(4));
        self::assertStringContainsString('pagets__bar', (string)$response->getBody());
    }

    #[Test]
    public function pageLayoutResolvedForBackendLayoutNextLevelOverrideOnSubpageLevel3(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContentObjectRendererGetDataPageLayout/backendLayoutAndNextLevelOnRootPageSubOverride3.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:frontend/Tests/Functional/ContentObject/Fixtures/ContentObjectRendererGetDataPageLayout/setup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(4));
        self::assertStringContainsString('pagets__extra', (string)$response->getBody());
    }
}
