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

namespace TYPO3\CMS\Core\Tests\Functional\Site;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteFinderTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SiteFinderTestPageImport.csv');
        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1, 'https://main.example.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );
        $this->writeSiteConfiguration(
            'nested',
            $this->buildSiteConfiguration(3, 'https://nested.example.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );
        $this->writeSiteConfiguration(
            'broken-nested',
            $this->buildSiteConfiguration(5, 'https:///'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );
    }

    public static function getSiteByPageResolvesSiteDataProvider(): iterable
    {
        yield 'site root page' => [['uid' => 1, 'pid' => 0], 'main'];
        yield 'page below site root' => [['uid' => 2, 'pid' => 1], 'main'];
        yield 'nested site root page' => [['uid' => 3, 'pid' => 2], 'nested'];
        yield 'page below nested site root' => [['uid' => 4, 'pid' => 3], 'nested'];
        yield 'translated page' => [['uid' => 8, 'pid' => 1, 'sys_language_uid' => 1, 'l10n_parent' => 2], 'main'];
    }

    #[DataProvider('getSiteByPageResolvesSiteDataProvider')]
    #[Test]
    public function getSiteByPageResolvesSite(array $pageRecord, string $expectedSiteIdentifier): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByPage(new Page($pageRecord));
        self::assertSame($expectedSiteIdentifier, $site->getIdentifier());
    }

    #[Test]
    public function getSiteByPageDoesNotLookUpThePageItself(): void
    {
        // Page 99 does not exist in the database, only its parent is resolved
        $site = $this->get(SiteFinder::class)->getSiteByPage(new Page(['uid' => 99, 'pid' => 4]));
        self::assertSame('nested', $site->getIdentifier());
    }

    public static function getSiteByPageThrowsExceptionDataProvider(): iterable
    {
        yield 'invalid nested site root page' => [['uid' => 5, 'pid' => 1]];
        yield 'page below invalid nested site root' => [['uid' => 6, 'pid' => 5]];
        yield 'root level page without site' => [['uid' => 7, 'pid' => 0]];
    }

    #[DataProvider('getSiteByPageThrowsExceptionDataProvider')]
    #[Test]
    public function getSiteByPageThrowsExceptionIfNoValidSiteIsFound(array $pageRecord): void
    {
        $this->expectException(SiteNotFoundException::class);
        $this->get(SiteFinder::class)->getSiteByPage(new Page($pageRecord));
    }
}
