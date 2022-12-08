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

use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\TypoScript\PageTsConfigFactory;
use TYPO3\CMS\Core\TypoScript\UserTsConfigFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests PageTsConfigFactory and indirectly IncludeTree/TsConfigTreeBuilder
 */
class PageTsConfigFactoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_typoscript_pagetsconfigfactory',
    ];

    /**
     * @test
     */
    public function pageTsConfigLoadsDefaultsFromGlobals(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] = 'loadedFromGlobals = loadedFromGlobals';
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create([], new NullSite(), new ConditionMatcher());
        self::assertSame('loadedFromGlobals', $pageTsConfig->getPageTsConfigArray()['loadedFromGlobals']);
    }

    /**
     * @test
     */
    public function pageTsConfigLoadsFromTestExtensionConfigurationFile(): void
    {
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create([], new NullSite(), new ConditionMatcher());
        self::assertSame('loadedFromTestExtensionConfigurationPageTsConfig', $pageTsConfig->getPageTsConfigArray()['loadedFromTestExtensionConfigurationPageTsConfig']);
    }

    /**
     * @test
     */
    public function pageTsConfigLoadsFromPagesTestExtensionConfigurationFile(): void
    {
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create([], new NullSite(), new ConditionMatcher());
        self::assertSame('loadedFromTestExtensionConfigurationPageTsConfig', $pageTsConfig->getPageTsConfigArray()['loadedFromTestExtensionConfigurationPageTsConfig']);
    }

    /**
     * @test
     */
    public function pageTsConfigLoadsFromPageRecordTsconfigField(): void
    {
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => 'loadedFromTsConfigField = loadedFromTsConfigField',
            ],
        ];
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite(), new ConditionMatcher());
        self::assertSame('loadedFromTsConfigField', $pageTsConfig->getPageTsConfigArray()['loadedFromTsConfigField']);
    }

    /**
     * @test
     */
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
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite(), new ConditionMatcher());
        self::assertSame('loadedFromTsConfigField1', $pageTsConfig->getPageTsConfigArray()['loadedFromTsConfigField1']);
        self::assertSame('loadedFromTsConfigField2Override', $pageTsConfig->getPageTsConfigArray()['loadedFromTsConfigField2']);
    }

    /**
     * @test
     */
    public function pageTsConfigSubstitutesSettingsFromSite(): void
    {
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => 'siteSetting = {$aSiteSetting}',
            ],
        ];
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $siteSettings = new SiteSettings(['aSiteSetting' => 'aSiteSettingValue']);
        $site = new Site('siteIdentifier', 1, [], $siteSettings);
        $pageTsConfig = $subject->create($rootLine, $site, new ConditionMatcher());
        self::assertSame('aSiteSettingValue', $pageTsConfig->getPageTsConfigArray()['siteSetting']);
    }

    /**
     * @test
     */
    public function pageTsConfigMatchesRequestHttpsCondition(): void
    {
        $request = (new ServerRequest('https://www.example.com/', null, 'php://input', [], ['HTTPS' => 'ON']));
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => 'isHttps = off'
                    . chr(10) . '[request.getNormalizedParams().isHttps()]'
                    . chr(10) . '  isHttps = on'
                    . chr(10) . '[end]',
            ],
        ];
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite(), new ConditionMatcher());
        self::assertSame('on', $pageTsConfig->getPageTsConfigArray()['isHttps']);
    }

    /**
     * @test
     */
    public function pageTsConfigMatchesRequestHttpsElseCondition(): void
    {
        $request = new ServerRequest('http://www.example.com/');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => '[request.getNormalizedParams().isHttps()]'
                    . chr(10) . '  isHttps = on'
                    . chr(10) . '[else]'
                    . chr(10) . '  isHttps = off',
            ],
        ];
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite(), new ConditionMatcher());
        self::assertSame('off', $pageTsConfig->getPageTsConfigArray()['isHttps']);
    }

    /**
     * @test
     */
    public function pageTsConfigMatchesRequestHttpsConditionUsingSiteConstant(): void
    {
        $request = (new ServerRequest('https://www.example.com/', null, 'php://input', [], ['HTTPS' => 'ON']));
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => 'isHttps = off'
                    . chr(10) . '[{$aSiteSetting}]'
                    . chr(10) . '  isHttps = on'
                    . chr(10) . '[end]',
            ],
        ];
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $siteSettings = new SiteSettings(['aSiteSetting' => 'request.getNormalizedParams().isHttps()']);
        $site = new Site('siteIdentifier', 1, [], $siteSettings);
        $pageTsConfig = $subject->create($rootLine, $site, new ConditionMatcher());
        self::assertSame('on', $pageTsConfig->getPageTsConfigArray()['isHttps']);
    }

    /**
     * @test
     */
    public function pageTsConfigLoadsFromEvent(): void
    {
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create([], new NullSite(), new ConditionMatcher());
        self::assertSame('loadedFromEvent', $pageTsConfig->getPageTsConfigArray()['loadedFromEvent']);
    }

    /**
     * @test
     */
    public function pageTsConfigLoadsFromLegacyEvent(): void
    {
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create([], new NullSite(), new ConditionMatcher());
        self::assertSame('loadedFromLegacyEvent', $pageTsConfig->getPageTsConfigArray()['loadedFromLegacyEvent']);
    }

    /**
     * @test
     */
    public function pageTsConfigCanBeOverloadedWithUserTsConfig(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageTsConfigTestFixture.csv');
        $backendUser = $this->setUpBackendUser(1);
        /** @var UserTsConfigFactory $userTsConfigFactory */
        $userTsConfigFactory = $this->get(UserTsConfigFactory::class);
        $userTsConfig = $userTsConfigFactory->create($backendUser);
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => 'valueOverriddenByUserTsConfig = base',
            ],
        ];
        /** @var PageTsConfigFactory $subject */
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite(), new ConditionMatcher(), $userTsConfig);
        self::assertSame('overridden', $pageTsConfig->getPageTsConfigArray()['valueOverriddenByUserTsConfig']);
    }
}
