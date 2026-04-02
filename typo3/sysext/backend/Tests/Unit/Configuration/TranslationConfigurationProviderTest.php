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
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
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
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('translate')->with(self::anything(), self::anything())->willReturnCallback(
            static fn(string $key, string $domain): string => match ($domain . ':' . $key) {
                'core.general:LGL.defaultLanguage' => 'Default',
                default => $key,
            }
        );
        $GLOBALS['LANG'] = $languageService;
    }

    #[Test]
    public function defaultLanguageIsAlwaysReturned(): void
    {
        $pageId = 1;
        $site = new Site('dummy', $pageId, ['base' => 'http://sub.domainhostname.tld/path/']);
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteFinderMock->method('getSiteByPageId')->with($pageId)->willReturn($site);
        $subject = new TranslationConfigurationProvider(
            $this->createMock(FrontendInterface::class),
            $siteFinderMock,
            $this->createMock(ConnectionPool::class),
            $this->createMock(TcaSchemaFactory::class),
        );
        $languages = $subject->getSystemLanguages($pageId);
        self::assertArrayHasKey(0, $languages);
    }

    #[Test]
    public function getSystemLanguagesConsolidatesLanguagesForRootLevel(): void
    {
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteFinderMock->method('getAllSites')->willReturn([
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
                            'flag' => 'us',
                        ],
                        [
                            'languageId' => 1,
                            'locale' => 'de_DE.UTF-8',
                            'title' => 'Deutsch',
                            'flag' => 'de',
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
                            'flag' => 'us',
                        ],
                        [
                            'languageId' => 1,
                            'locale' => 'de_DE.UTF-8',
                            'title' => 'German',
                            'flag' => 'de',
                        ],
                        [
                            'languageId' => 2,
                            'locale' => 'da_DK.UTF-8',
                            'title' => 'Danish',
                            'flag' => 'dk',
                        ],
                    ],
                ]
            ),
        ]);
        $subject = new TranslationConfigurationProvider(
            $this->createMock(FrontendInterface::class),
            $siteFinderMock,
            $this->createMock(ConnectionPool::class),
            $this->createMock(TcaSchemaFactory::class),
        );
        $languages = $subject->getSystemLanguages(0);
        self::assertCount(3, $languages);
        // Language 0: same title across sites — keep actual title and flag
        self::assertSame('English [0]', $languages[0]['title']);
        self::assertSame('flags-us', $languages[0]['flagIcon']);
        // Language 1: different titles — list unique titles, use flags-multiple
        self::assertSame('Deutsch, German [1]', $languages[1]['title']);
        self::assertSame('flags-multiple', $languages[1]['flagIcon']);
        // Language 2: single site — just title and language ID
        self::assertSame('Danish [2]', $languages[2]['title']);
        self::assertSame('flags-dk', $languages[2]['flagIcon']);
    }

    #[Test]
    public function getSystemLanguagesShowsDefaultLabelForLanguageZeroWhenTitlesDiffer(): void
    {
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteFinderMock->method('getAllSites')->willReturn([
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
                            'flag' => 'us',
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
                            'locale' => 'de_DE.UTF-8',
                            'title' => 'German',
                            'flag' => 'de',
                        ],
                    ],
                ]
            ),
        ]);
        $subject = new TranslationConfigurationProvider(
            $this->createMock(FrontendInterface::class),
            $siteFinderMock,
            $this->createMock(ConnectionPool::class),
            $this->createMock(TcaSchemaFactory::class),
        );
        $languages = $subject->getSystemLanguages(0);
        self::assertSame('Default [0]', $languages[0]['title']);
        self::assertSame('flags-multiple', $languages[0]['flagIcon']);
    }

    #[Test]
    public function getSystemLanguagesShowsFlagsMultipleWhenFlagsDifferForRootLevel(): void
    {
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteFinderMock->method('getAllSites')->willReturn([
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
                            'flag' => 'us',
                        ],
                        [
                            'languageId' => 1,
                            'locale' => 'de_DE.UTF-8',
                            'title' => 'German',
                            'flag' => 'de',
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
                            'locale' => 'en_GB.UTF-8',
                            'title' => 'English',
                            'flag' => 'gb',
                        ],
                        [
                            'languageId' => 1,
                            'locale' => 'de_AT.UTF-8',
                            'title' => 'German',
                            'flag' => 'at',
                        ],
                    ],
                ]
            ),
        ]);
        $subject = new TranslationConfigurationProvider(
            $this->createMock(FrontendInterface::class),
            $siteFinderMock,
            $this->createMock(ConnectionPool::class),
            $this->createMock(TcaSchemaFactory::class),
        );
        $languages = $subject->getSystemLanguages(0);
        // Same title but different flags on a non-default language: flags-multiple icon
        self::assertSame('German [1]', $languages[1]['title']);
        self::assertSame('flags-multiple', $languages[1]['flagIcon']);
    }
}
