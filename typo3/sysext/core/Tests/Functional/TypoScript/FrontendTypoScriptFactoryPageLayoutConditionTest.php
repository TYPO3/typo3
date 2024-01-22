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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript;

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test Frontend TypoScript 'tree.pagelayout' related condition matching.
 */
final class FrontendTypoScriptFactoryPageLayoutConditionTest extends FunctionalTestCase
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

    /**
     * @test
     */
    public function treePageLayoutConditionMetForBackendLayoutOnRootPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutOnRoot.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:core/Tests/Functional/TypoScript/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutOnRootSetup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('Default Layout', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function treePageLayoutConditionNotMetForBackendLayoutNextLevelOnRootPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutNextLevelOnRoot.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:core/Tests/Functional/TypoScript/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutNextLevelOnRootSetup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringNotContainsString('Default Layout', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function treePageLayoutConditionMetForBackendLayoutNextLevelInheritedOnSubpageLevel1(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRoot.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:core/Tests/Functional/TypoScript/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRootSetup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(2));
        self::assertStringContainsString('Inherited Layout', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function treePageLayoutConditionMetForBackendLayoutNextLevelInheritedOnSubpageLevel2(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRootSubOverride1.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:core/Tests/Functional/TypoScript/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRootSubOverride1Setup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(3));
        self::assertStringContainsString('Inherited Layout', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function treePageLayoutConditionMetForBackendLayoutOnSubpageLevel3(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRootSubOverride2.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:core/Tests/Functional/TypoScript/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRootSubOverride2Setup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(4));
        self::assertStringContainsString('Extra Layout', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function treePageLayoutConditionMetForBackendLayoutNextLevelOverrideOnSubpageLevel3(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRootSubOverride3.csv');
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:core/Tests/Functional/TypoScript/Fixtures/FrontendTypoScriptFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRootSubOverride3Setup.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(4));
        self::assertStringContainsString('Extra Layout', (string)$response->getBody());
    }
}
