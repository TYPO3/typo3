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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\PageTsConfigFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test pageTS 'tree.pagelayout' related condition matching.
 */
final class PageTsConfigFactoryPageLayoutConditionTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'tree_page_layout_test',
            $this->buildSiteConfiguration(1, '/'),
        );
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function treePageLayoutConditionMetForBackendLayoutOnRootPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTsConfigFactoryPageLayoutCondition/backendLayoutOnRootPage.csv');
        $fullRootLine = BackendUtility::BEgetRootLine(1, '', true);
        ksort($fullRootLine);
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($fullRootLine, new NullSite());
        $pageTsConfigArray = $pageTsConfig->getPageTsConfigArray();
        self::assertStringContainsString('Default Layout', $pageTsConfigArray['layout'] ?? '');
    }

    #[Test]
    public function treePageLayoutConditionNotMetForBackendLayoutNextLevelOnRootPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTsConfigFactoryPageLayoutCondition/backendLayoutNextLevelOnRootPage.csv');
        $fullRootLine = BackendUtility::BEgetRootLine(1, '', true);
        ksort($fullRootLine);
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($fullRootLine, new NullSite());
        $pageTsConfigArray = $pageTsConfig->getPageTsConfigArray();
        self::assertStringNotContainsString('Default Layout', $pageTsConfigArray['layout'] ?? '');
    }

    #[Test]
    public function treePageLayoutConditionMetForBackendLayoutNextLevelInheritedOnSubpageLevel1(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTsConfigFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRootPage.csv');
        $fullRootLine = BackendUtility::BEgetRootLine(2, '', true);
        ksort($fullRootLine);
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($fullRootLine, new NullSite());
        $pageTsConfigArray = $pageTsConfig->getPageTsConfigArray();
        self::assertStringContainsString('Inherited Layout', $pageTsConfigArray['layout'] ?? '');
    }

    #[Test]
    public function treePageLayoutConditionMetForBackendLayoutNextLevelInheritedOnSubpageLevel2(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTsConfigFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRootPageSubOverride1.csv');
        $fullRootLine = BackendUtility::BEgetRootLine(3, '', true);
        ksort($fullRootLine);
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($fullRootLine, new NullSite());
        $pageTsConfigArray = $pageTsConfig->getPageTsConfigArray();
        self::assertStringContainsString('Inherited Layout', $pageTsConfigArray['layout'] ?? '');
    }

    #[Test]
    public function treePageLayoutConditionMetForBackendLayoutOnSubpageLevel3(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTsConfigFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRootPageSubOverride2.csv');
        $fullRootLine = BackendUtility::BEgetRootLine(4, '', true);
        ksort($fullRootLine);
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($fullRootLine, new NullSite());
        $pageTsConfigArray = $pageTsConfig->getPageTsConfigArray();
        self::assertStringContainsString('Extra Layout', $pageTsConfigArray['layout'] ?? '');
    }

    #[Test]
    public function treePageLayoutConditionMetForBackendLayoutNextLevelOverrideOnSubpageLevel3(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTsConfigFactoryPageLayoutCondition/backendLayoutAndNextLevelOnRootPageSubOverride3.csv');
        $fullRootLine = BackendUtility::BEgetRootLine(4, '', true);
        ksort($fullRootLine);
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($fullRootLine, new NullSite());
        $pageTsConfigArray = $pageTsConfig->getPageTsConfigArray();
        self::assertStringContainsString('Extra Layout', $pageTsConfigArray['layout'] ?? '');
    }
}
