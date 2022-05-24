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

namespace TYPO3\CMS\Core\Tests\Unit\Localization;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\PageTsConfig;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\TcaSystemLanguageCollector;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaSystemLanguageCollectorTest extends UnitTestCase
{
    use ProphecyTrait;

    protected bool $resetSingletonInstances = true;

    public function setUp(): void
    {
        parent::setUp();
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('runtime')->willReturn($cacheFrontendProphecy->reveal());

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->groupData = ['allowed_languages' => ''];

        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
    }

    /**
     * @test
     */
    public function populateAvailableSiteLanguagesTest(): void
    {
        $siteFinder = $this->prophesize(SiteFinder::class);
        $siteFinder->getAllSites()->willReturn([
            new Site('site-1', 1, [
                'base' => '/',
                'languages' => [
                    [
                        'title' => 'English',
                        'languageId' => 0,
                        'base' => '/',
                        'locale' => 'en_US',
                        'flag' => 'us',
                    ],
                    [
                        'title' => 'German',
                        'languageId' => 2,
                        'base' => '/de/',
                        'locale' => 'de_DE',
                        'flag' => 'de',
                    ],
                ],
            ]),
            new Site('site-2', 2, [
                'base' => '/',
                'languages' => [
                    [
                        'title' => 'English',
                        'languageId' => 0,
                        'base' => '/',
                        'locale' => 'en_US',
                        'flag' => 'us',
                    ],
                ],
            ]),
        ]);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinder->reveal());

        $expectedItems = [
            0 => [
                0 => 'English [Site: site-1], English [Site: site-2]',
                1 => 0,
                2 => 'flags-us',
            ],
            1 => [
                0 => 'German [Site: site-1]',
                1 => 2,
                2 => 'flags-de',
            ],
        ];

        $fieldInformation = ['items' => []];

        (new TcaSystemLanguageCollector(new Locales()))->populateAvailableSiteLanguages($fieldInformation);

        self::assertSame($expectedItems, $fieldInformation['items']);
    }

    /**
     * @test
     */
    public function populateAvailableSiteLanguagesWithoutSiteTest(): void
    {
        $languageService = $this->prophesize(LanguageService::class);
        $languageService->sL(Argument::cetera())->willReturn('');
        $GLOBALS['LANG'] = $languageService->reveal();

        $siteFinder = $this->prophesize(SiteFinder::class);
        $siteFinder->getAllSites()->willReturn([]);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinder->reveal());
        $siteFinder->getSiteByPageId(0)->willThrow(SiteNotFoundException::class);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinder->reveal());
        $conditionMatcher = $this->prophesize(ConditionMatcher::class);
        GeneralUtility::addInstance(ConditionMatcher::class, $conditionMatcher->reveal());
        $tsConfig = $this->prophesize(PageTsConfig::class);
        $tsConfig->getWithUserOverride(Argument::cetera())->willReturn([]);
        GeneralUtility::addInstance(PageTsConfig::class, $tsConfig->reveal());

        $expectedItems = [
            0 => [
                0 => 'Default',
                1 => 0,
                2 => '',
            ],
        ];

        $fieldInformation = ['items' => []];

        (new TcaSystemLanguageCollector(new Locales()))->populateAvailableSiteLanguages($fieldInformation);

        self::assertSame($expectedItems, $fieldInformation['items']);
    }
}
