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

namespace TYPO3\CMS\Core\Tests\Unit\Routing;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SiteMatcherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function fullUrlMatchesSpecificLanguageWithSubdomainsAndDomainSuffixes(): void
    {
        $site = new Site('main', 1, [
            'base' => '/',
            'languages' => [
                0 => [
                    'title' => 'English',
                    'languageId' => 0,
                    'base' => 'http://9-5.typo3.test/',
                    'locale' => 'en_US-UTF-8',
                ],
                2 => [
                    'title' => 'Deutsch',
                    'languageId' => 2,
                    'base' => 'http://de.9-5.typo3.test/',
                    'locale' => 'en_US-UTF-8',
                ],
                1 => [
                    'title' => 'Dansk',
                    'languageId' => 1,
                    'base' => 'http://9-5.typo3.test/da/',
                    'locale' => 'da_DK.UTF-8',
                ],
            ],
        ]);
        $secondSite = new Site('second', 13, [
            'base' => '/',
            'languages' => [
                0 => [
                    'title' => 'English',
                    'languageId' => 0,
                    'base' => '/en/',
                    'locale' => 'en_US-UTF-8',
                ],
                1 => [
                    'title' => 'Dansk',
                    'languageId' => 1,
                    'base' => '/da/',
                    'locale' => 'da_DK.UTF-8',
                ],
            ],
        ]);
        $finderMock = $this->createSiteFinderMock($site, $secondSite);
        $subject = new SiteMatcher($finderMock);

        $request = new ServerRequest('http://9-5.typo3.test/da/my-page/');
        /** @var SiteRouteResult $result */
        $result = $subject->matchRequest($request);
        self::assertEquals(1, $result->getLanguage()->getLanguageId());

        $request = new ServerRequest('http://9-5.typo3.test/da');
        /** @var SiteRouteResult $result */
        $result = $subject->matchRequest($request);
        // Matches danish, as path fits
        self::assertEquals(1, $result->getLanguage()->getLanguageId());

        $request = new ServerRequest('https://9-5.typo3.test/da');
        /** @var SiteRouteResult $result */
        $result = $subject->matchRequest($request);
        // Matches the second site, as this is HTTPS and HTTP
        self::assertEquals('second', $result->getSite()->getIdentifier());
        self::assertEquals(1, $result->getLanguage()->getLanguageId());

        $request = new ServerRequest('http://de.9-5.typo3.test/da');
        /** @var SiteRouteResult $result */
        $result = $subject->matchRequest($request);
        // Matches german, as the domain fits!
        self::assertEquals(2, $result->getLanguage()->getLanguageId());

        $request = new ServerRequest('http://9-5.typo3.test/');
        /** @var SiteRouteResult $result */
        $result = $subject->matchRequest($request);
        // Matches english
        self::assertEquals(0, $result->getLanguage()->getLanguageId());

        // @todo this is a random result and needs to be refined
        // www.example.com is not defined at all, but it actually "matches"...
        $request = new ServerRequest('http://www.example.com/');
        /** @var SiteRouteResult $result */
        $result = $subject->matchRequest($request);
        // Nothing found, only the empty site, but finds a random site ("main") according to the algorithm
        self::assertNull($result->getLanguage());
        self::assertEquals('main', $result->getSite()->getIdentifier());
    }

    /**
     * Contains a FQDN as base for the site
     * @test
     */
    public function fullUrlMatchesSpecificLanguageWithSubdomainsAndPathSuffixes(): void
    {
        $site = new Site('main', 1, [
            'base' => 'https://www.example.com/',
            'languages' => [
                0 => [
                    'title' => 'English',
                    'languageId' => 0,
                    'base' => 'http://example.us/',
                    'locale' => 'en_US-UTF-8',
                ],
                2 => [
                    'title' => 'Deutsch',
                    'languageId' => 2,
                    'base' => 'http://www.example.de/',
                    'locale' => 'en_US-UTF-8',
                ],
                1 => [
                    'title' => 'Dansk',
                    'languageId' => 1,
                    'base' => 'http://www.example.com/da/',
                    'locale' => 'da_DK.UTF-8',
                ],
                3 => [
                    'title' => 'French',
                    'languageId' => 3,
                    'base' => '/fr/',
                    'locale' => 'fr_FR.UTF-8',
                ],
            ],
        ]);
        $secondSite = new Site('second', 13, [
            'base' => '/',
            'languages' => [
                0 => [
                    'title' => 'English',
                    'languageId' => 0,
                    'base' => '/en/',
                    'locale' => 'en_US-UTF-8',
                ],
                1 => [
                    'title' => 'Dansk',
                    'languageId' => 1,
                    'base' => '/da/',
                    'locale' => 'da_DK.UTF-8',
                ],
            ],
        ]);
        $finderMock = $this->createSiteFinderMock($site, $secondSite);
        $subject = new SiteMatcher($finderMock);

        $request = new ServerRequest('https://www.example.com/de');
        /** @var SiteRouteResult $result */
        $result = $subject->matchRequest($request);
        // Site found, but no language
        self::assertEquals($site, $result->getSite());
        self::assertNull($result->getLanguage());

        $request = new ServerRequest('http://www.other-domain.com/da');
        /** @var SiteRouteResult $result */
        $result = $subject->matchRequest($request);
        self::assertEquals($secondSite, $result->getSite());
        self::assertEquals(1, $result->getLanguage()->getLanguageId());

        $request = new ServerRequest('http://www.other-domain.com/de');
        /** @var SiteRouteResult $result */
        $result = $subject->matchRequest($request);
        // No language for this solution
        self::assertEquals($secondSite, $result->getSite());
        self::assertNull($result->getLanguage());
    }

    public static function bestMatchingUrlIsUsedDataProvider(): \Generator
    {
        yield  ['https://example.org/page', '1-main', 'en_US'];
        yield  ['https://example.org/', '1-main', 'en_US'];
        yield  ['https://example.org/f', '1-main', 'en_US'];
        yield  ['https://example.org/fr', '3-fr', 'fr_FR'];
        yield  ['https://example.org/friendly-page', '1-main', 'en_US'];
        yield  ['https://example.org/fr/', '3-fr', 'fr_FR'];
        yield  ['https://example.org/fr/page', '3-fr', 'fr_FR'];
        yield  ['https://example.org/de', '1-main', 'de_DE'];
        yield  ['https://example.org/deterministic', '1-main', 'en_US'];
        yield  ['https://example.org/dk', '2-dk', 'da_DK'];
        yield  ['https://example.org/dkother', '1-main', 'en_US'];
    }

    /**
     * @test
     * @dataProvider bestMatchingUrlIsUsedDataProvider
     */
    public function bestMatchingUrlIsUsed(string $requestUri, string $expectedSite, string $expectedLocale): void
    {
        $mainSite = new Site('1-main', 31, [
            'base' => 'https://example.org/',
            'languages' => [
                [
                    'languageId' => 0,
                    'base' => 'https://example.org/',
                    'locale' => 'en_US',
                ],
                [
                    'languageId' => 2,
                    'base' => 'https://example.org/de/',
                    'locale' => 'de_DE',
                ],
            ],
        ]);
        $dkSite = new Site('2-dk', 21, [
            'base' => 'https://example.org/',
            'languages' => [
                [
                    'languageId' => 0,
                    'base' => 'https://example.org/dk/',
                    'locale' => 'da_DK',
                ],
            ],
        ]);
        $frSite = new Site('3-fr', 11, [
            'base' => 'https://example.org/',
            'languages' => [
                [
                    'languageId' => 0,
                    'base' => 'https://example.org/fr/',
                    'locale' => 'fr_FR',
                ],
            ],
        ]);

        $finderMock = $this->createSiteFinderMock($mainSite, $dkSite, $frSite);
        $subject = new SiteMatcher($finderMock);

        $request = new ServerRequest($requestUri);
        /** @var SiteRouteResult $result */
        $result = $subject->matchRequest($request);

        self::assertSame($expectedSite, $result->getSite()->getIdentifier());
        self::assertSame($expectedLocale, $result->getLanguage()->getLocale());
    }

    private function createSiteFinderMock(Site ...$sites): SiteFinder
    {
        /** @var SiteFinder $finderMock */
        $finderMock = $this
            ->getMockBuilder(SiteFinder::class)
            ->onlyMethods(['getAllSites'])
            ->disableOriginalConstructor()
            ->getMock();
        $finderMock->method('getAllSites')->willReturn(array_combine(
            array_map(static function (Site $site) { return $site->getIdentifier(); }, $sites),
            $sites
        ));
        return $finderMock;
    }
}
