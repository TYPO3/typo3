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
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\PageTsConfigFactory;
use TYPO3\CMS\Core\TypoScript\UserTsConfigFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests PageTsConfigFactory and indirectly IncludeTree/TsConfigTreeBuilder
 */
final class PageTsConfigFactoryTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var array Used by buildDefaultLanguageConfiguration() of SiteBasedTestTrait
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_typoscript_pagetsconfigfactory',
    ];

    #[Test]
    public function pageTsConfigLoadsFromPagesTsconfigTestExtensionConfigurationFile(): void
    {
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create([], new NullSite());
        self::assertSame('loadedFromTestExtensionConfigurationPageTsConfig', $pageTsConfig->getPageTsConfigArray()['loadedFromTestExtensionConfigurationPageTsConfig']);
        // Verify relative includes are resolved as well.
        self::assertSame('loadedFromRelativeIncludeTarget20', $pageTsConfig->getPageTsConfigArray()['loadedFromRelativeIncludeTarget20']);
        self::assertSame('loadedFromRelativeIncludeTarget20Sub', $pageTsConfig->getPageTsConfigArray()['loadedFromRelativeIncludeTarget20Sub']);
        self::assertSame('loadedFromRelativeIncludeTarget22', $pageTsConfig->getPageTsConfigArray()['loadedFromRelativeIncludeTarget22']);
        self::assertSame('loadedFromRelativeIncludeTarget23', $pageTsConfig->getPageTsConfigArray()['loadedFromRelativeIncludeTarget23']);
        // 24 is a relative include with path traversal and not allowed to be loaded.
        self::assertArrayNotHasKey('loadedFromRelativeIncludeTarget24', $pageTsConfig->getPageTsConfigArray());
    }

    #[Test]
    public function pageTsConfigLoadsFromSiteSetPagesTsconfig(): void
    {
        $rootLine = [
            [
                'uid' => 1,
            ],
        ];
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
            [],
            [
                'typo3tests/set-with-pagets-config',
            ],
        );
        $site = $this->get(SiteFinder::class)->getSiteByRootPageId(1);
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, $site);
        self::assertSame('loadedFromSiteSetPageTsConfig', $pageTsConfig->getPageTsConfigArray()['loadedFromSiteSetPageTsConfig'] ?? '');
        // Verify relative includes are resolved as well.
        self::assertSame('loadedFromSiteSetRelativeIncludeTarget20', $pageTsConfig->getPageTsConfigArray()['loadedFromSiteSetRelativeIncludeTarget20'] ?? '');
        self::assertSame('loadedFromSiteSetRelativeIncludeTarget20Sub', $pageTsConfig->getPageTsConfigArray()['loadedFromSiteSetRelativeIncludeTarget20Sub'] ?? '');
        self::assertSame('loadedFromSiteSetRelativeIncludeTarget22', $pageTsConfig->getPageTsConfigArray()['loadedFromSiteSetRelativeIncludeTarget22'] ?? '');
        self::assertSame('loadedFromSiteSetRelativeIncludeTarget23', $pageTsConfig->getPageTsConfigArray()['loadedFromSiteSetRelativeIncludeTarget23'] ?? '');
        // 24 is a relative include with path traversal and not allowed to be loaded.
        self::assertArrayNotHasKey('loadedFromSiteSetRelativeIncludeTarget24', $pageTsConfig->getPageTsConfigArray());
    }

    #[Test]
    public function pageTsConfigLoadsFromPageRecordTsconfigField(): void
    {
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => 'loadedFromTsConfigField = loadedFromTsConfigField',
            ],
        ];
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite());
        self::assertSame('loadedFromTsConfigField', $pageTsConfig->getPageTsConfigArray()['loadedFromTsConfigField']);
    }

    #[Test]
    public function pageTsConfigLoadsRelativeTargets(): void
    {
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => '@import \'EXT:test_typoscript_pagetsconfigfactory/Configuration/TsConfig/Relative/includes.tsconfig\'',
            ],
        ];
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite());
        self::assertSame('loadedFromRelativeIncludeTarget10', $pageTsConfig->getPageTsConfigArray()['loadedFromRelativeIncludeTarget10']);
        self::assertSame('loadedFromRelativeIncludeTarget10Sub', $pageTsConfig->getPageTsConfigArray()['loadedFromRelativeIncludeTarget10Sub']);
        self::assertSame('loadedFromRelativeIncludeTarget11', $pageTsConfig->getPageTsConfigArray()['loadedFromRelativeIncludeTarget11']);
        self::assertSame('loadedFromRelativeIncludeTarget12', $pageTsConfig->getPageTsConfigArray()['loadedFromRelativeIncludeTarget12']);
        self::assertSame('loadedFromRelativeIncludeTarget13', $pageTsConfig->getPageTsConfigArray()['loadedFromRelativeIncludeTarget13']);
        // 14 is a relative include with path traversal and not allowed to be loaded.
        self::assertArrayNotHasKey('loadedFromRelativeIncludeTarget14', $pageTsConfig->getPageTsConfigArray());
    }

    #[Test]
    public function pageTsConfigLoadsFromWildcardAtImportWithTsconfigSuffix(): void
    {
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => '@import \'EXT:test_typoscript_pagetsconfigfactory/Configuration/TsConfig/*.tsconfig\'',
            ],
        ];
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite());
        self::assertSame('loadedFromTsconfigIncludesWithTsconfigSuffix', $pageTsConfig->getPageTsConfigArray()['loadedFromTsconfigIncludesWithTsconfigSuffix']);
    }

    #[Test]
    public function pageTsConfigLoadsFromWildcardAtImportWithTypoScriptSuffix(): void
    {
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => '@import \'EXT:test_typoscript_pagetsconfigfactory/Configuration/TsConfig/*.typoscript\'',
            ],
        ];
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite());
        self::assertSame('loadedFromTsconfigIncludesWithTyposcriptSuffix', $pageTsConfig->getPageTsConfigArray()['loadedFromTsconfigIncludesWithTyposcriptSuffix']);
    }

    #[Test]
    public function pageTsConfigLoadsFromPageRecordTsconfigFieldOverridesByLowerLevel(): void
    {
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => 'loadedFromTsConfigField1 = loadedFromTsConfigField1'
                    . chr(10) . 'loadedFromTsConfigField2 = loadedFromTsConfigField2',
            ],
            [
                'uid' => 2,
                'TSconfig' => 'loadedFromTsConfigField1 = loadedFromTsConfigField1'
                    . chr(10) . 'loadedFromTsConfigField2 = loadedFromTsConfigField2Override',
            ],
        ];
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite());
        self::assertSame('loadedFromTsConfigField1', $pageTsConfig->getPageTsConfigArray()['loadedFromTsConfigField1']);
        self::assertSame('loadedFromTsConfigField2Override', $pageTsConfig->getPageTsConfigArray()['loadedFromTsConfigField2']);
    }

    #[Test]
    public function pageTsConfigSubstitutesSettingsFromSite(): void
    {
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => 'siteSetting = {$aSiteSetting}',
            ],
        ];
        $subject = $this->get(PageTsConfigFactory::class);
        $siteSettings = SiteSettings::createFromSettingsTree(
            ['aSiteSetting' => 'aSiteSettingValue'],
        );
        $site = new Site('siteIdentifier', 1, [], $siteSettings);
        $pageTsConfig = $subject->create($rootLine, $site);
        self::assertSame('aSiteSettingValue', $pageTsConfig->getPageTsConfigArray()['siteSetting']);
    }

    #[Test]
    public function pageTsConfigLoadsFromEvent(): void
    {
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create([], new NullSite());
        self::assertSame('loadedFromEvent', $pageTsConfig->getPageTsConfigArray()['loadedFromEvent']);
    }

    #[Test]
    public function pageTsConfigCanBeOverloadedWithUserTsConfig(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageTsConfigTestFixture.csv');
        $backendUser = $this->setUpBackendUser(1);
        $userTsConfigFactory = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $userTsConfigFactory->create($backendUser);
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => 'valueOverriddenByUserTsConfig = base',
            ],
        ];
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite(), $userTsConfig);
        self::assertSame('overridden', $pageTsConfig->getPageTsConfigArray()['valueOverriddenByUserTsConfig']);
    }
}
