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

namespace TYPO3\CMS\Backend\Tests\Unit\Configuration;

use Prophecy\Argument;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TranslationConfigurationProviderTest extends UnitTestCase
{
    /**
     * @var TranslationConfigurationProvider
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new TranslationConfigurationProvider();
        $backendUserAuthentication = $this->prophesize(BackendUserAuthentication::class);
        $backendUserAuthentication->checkLanguageAccess(Argument::any())->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUserAuthentication->reveal();
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
    }

    /**
     * @test
     */
    public function defaultLanguageIsAlwaysReturned(): void
    {
        $pageId = 1;
        $site = new Site('dummy', $pageId, ['base' => 'http://sub.domainhostname.tld/path/']);
        $siteFinderProphecy = $this->prophesize(SiteFinder::class);
        $siteFinderProphecy->getSiteByPageId($pageId)->willReturn($site);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinderProphecy->reveal());
        $languages = $this->subject->getSystemLanguages($pageId);
        self::assertArrayHasKey(0, $languages);
    }

    /**
     * @test
     */
    public function getSystemLanguagesAggregatesLanguagesOfAllSitesForRootLevel(): void
    {
        $siteFinderProphecy = $this->prophesize(SiteFinder::class);
        $siteFinderProphecy->getAllSites()->willReturn($this->getDummySites());
        GeneralUtility::addInstance(SiteFinder::class, $siteFinderProphecy->reveal());
        $languages = $this->subject->getSystemLanguages(0);
        self::assertCount(3, $languages);
    }

    /**
     * @test
     */
    public function getSystemLanguagesConcatenatesTitlesOfLanguagesForRootLevel(): void
    {
        $siteFinderProphecy = $this->prophesize(SiteFinder::class);
        $siteFinderProphecy->getAllSites()->willReturn($this->getDummySites());
        GeneralUtility::addInstance(SiteFinder::class, $siteFinderProphecy->reveal());
        $languages = $this->subject->getSystemLanguages(0);
        self::assertEquals('Deutsch [Site: dummy1], German [Site: dummy2]', $languages[1]['title']);
    }

    protected function getDummySites(): array
    {
        return [
            new Site(
                'dummy1',
                1,
                [
                    'base' => 'https://domain.tld/',
                    'languages' => [
                        [
                            'languageId' => 0,
                            'locale' => 'en_US.UTF-8',
                            'title' => 'English',
                        ],
                        [
                            'languageId' => 1,
                            'locale' => 'de_DE.UTF-8',
                            'title' => 'Deutsch'
                        ]
                    ]
                ]
            ),
            new Site(
                'dummy2',
                2,
                [
                    'base' => 'https://domain.tld/',
                    'languages' => [
                        [
                            'languageId' => 0,
                            'locale' => 'en_US.UTF-8',
                            'title' => 'English'
                        ],
                        [
                            'languageId' => 1,
                            'locale' => 'de_DE.UTF-8',
                            'title' => 'German'
                        ],
                        [
                            'languageId' => 2,
                            'locale' => 'da_DK.UTF-8',
                            'title' => 'Danish'
                        ],
                    ]
                ]
            )
        ];
    }
}
