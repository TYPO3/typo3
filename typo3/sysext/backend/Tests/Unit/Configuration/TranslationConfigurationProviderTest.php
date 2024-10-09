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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TranslationConfigurationProviderTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $backendUserAuthentication = $this->createMock(BackendUserAuthentication::class);
        $backendUserAuthentication->method('checkLanguageAccess')->with(self::anything())->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUserAuthentication;
    }

    #[Test]
    public function defaultLanguageIsAlwaysReturned(): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->with(self::anything())->willReturn('');
        $GLOBALS['LANG'] = $languageService;

        $pageId = 1;
        $site = new Site('dummy', $pageId, ['base' => 'http://sub.domainhostname.tld/path/']);
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteFinderMock->method('getSiteByPageId')->with($pageId)->willReturn($site);
        $subject = new TranslationConfigurationProvider(
            $this->createMock(FrontendInterface::class),
            $siteFinderMock,
            $this->createMock(ConnectionPool::class)
        );
        $languages = $subject->getSystemLanguages($pageId);
        self::assertArrayHasKey(0, $languages);
    }

    #[Test]
    public function getSystemLanguagesAggregatesLanguagesOfAllSitesForRootLevel(): void
    {
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteFinderMock->method('getAllSites')->willReturn($this->getDummySites());
        $subject = new TranslationConfigurationProvider(
            $this->createMock(FrontendInterface::class),
            $siteFinderMock,
            $this->createMock(ConnectionPool::class)
        );
        $languages = $subject->getSystemLanguages(0);
        self::assertCount(3, $languages);
    }

    #[Test]
    public function getSystemLanguagesConcatenatesTitlesOfLanguagesForRootLevel(): void
    {
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteFinderMock->method('getAllSites')->willReturn($this->getDummySites());
        $subject = new TranslationConfigurationProvider(
            $this->createMock(FrontendInterface::class),
            $siteFinderMock,
            $this->createMock(ConnectionPool::class)
        );
        $languages = $subject->getSystemLanguages(0);
        self::assertEquals('Deutsch [Site: dummy1], German [Site: dummy2]', $languages[1]['title']);
    }

    /**
     * @return Site[]
     */
    private function getDummySites(): array
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
                            'title' => 'Deutsch',
                        ],
                    ],
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
                            'title' => 'English',
                        ],
                        [
                            'languageId' => 1,
                            'locale' => 'de_DE.UTF-8',
                            'title' => 'German',
                        ],
                        [
                            'languageId' => 2,
                            'locale' => 'da_DK.UTF-8',
                            'title' => 'Danish',
                        ],
                    ],
                ]
            ),
        ];
    }
}
