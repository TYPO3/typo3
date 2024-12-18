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

namespace TYPO3\CMS\Backend\Tests\Functional\Routing;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Routing\SiteUrlResolver;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteUrlResolverTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8', 'iso' => 'de'],
    ];

    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTree.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);

        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.com/'),
                $this->buildLanguageConfiguration('DE', 'https://acme.com/de/', ['EN']),
            ]
        );
    }

    #[Test]
    public function urlResolveReturnsNullOnMissingUri(): void
    {
        $resolver = $this->get(SiteUrlResolver::class);
        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/this/is/not/the/page/you/are/looking/for');
        self::assertNull($result);

        // Invalid "index.html" at the end of an existing page, not resolving
        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/home/main/sub1/index.html');
        self::assertNull($result);
    }

    #[Test]
    public function urlResolveFindsSingleUri(): void
    {
        $resolver = $this->get(SiteUrlResolver::class);
        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/home/main/sub1');
        self::assertEquals(20, $result);

        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/home/main/sub1/');
        self::assertEquals(20, $result);

        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/home/main/sub1?someQueryPart');
        self::assertEquals(20, $result);

        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/home/main/sub1#someLocationHash');
        self::assertEquals(20, $result);
    }

    #[Test]
    public function urlResolveWithLanguageDataReturnsNullOnMissingUri(): void
    {
        $resolver = $this->get(SiteUrlResolver::class);
        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/this/is/not/the/page/you/are/looking/for');
        self::assertNull($result);

        // Invalid "index.html" at the end of an existing page, not resolving
        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/home/main/sub1/index.html');
        self::assertNull($result);
    }

    #[Test]
    public function urlResolveWithLanguageDataFindsSingleUri(): void
    {
        $resolver = $this->get(SiteUrlResolver::class);
        $result = $resolver->resolvePageUidAndLanguageBySiteUrl('https://acme.com/home/main/sub1');
        self::assertIsArray($result);
        self::assertEquals(20, $result['uid']);

        $result = $resolver->resolvePageUidAndLanguageBySiteUrl('https://acme.com/home/main/sub1/');
        self::assertIsArray($result);
        self::assertEquals(20, $result['uid']);

        $result = $resolver->resolvePageUidAndLanguageBySiteUrl('https://acme.com/home/main/sub1?someQueryPart');
        self::assertIsArray($result);
        self::assertEquals(20, $result['uid']);

        $result = $resolver->resolvePageUidAndLanguageBySiteUrl('https://acme.com/home/main/sub1#someLocationHash');
        self::assertIsArray($result);
        self::assertEquals(20, $result['uid']);
    }

    #[Test]
    public function urlResolveFindsLocalizedUri(): void
    {
        $resolver = $this->get(SiteUrlResolver::class);
        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/de/home/main/sub1');
        self::assertEquals(20, $result);

        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/de/home/main/sub1/');
        self::assertEquals(20, $result);

        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/de/home/main/sub1?someQueryPart');
        self::assertEquals(20, $result);

        $result = $resolver->resolvePageUidBySiteUrl('https://acme.com/de/home/main/sub1#someLocationHash');
        self::assertEquals(20, $result);
    }

    #[Test]
    public function urlResolveWithLanguageDataFindsLocalizedUri(): void
    {
        $resolver = $this->get(SiteUrlResolver::class);
        $result = $resolver->resolvePageUidAndLanguageBySiteUrl('https://acme.com/de/home/main/sub1');
        self::assertIsArray($result);
        self::assertEquals(20, $result['uid']);

        $result = $resolver->resolvePageUidAndLanguageBySiteUrl('https://acme.com/de/home/main/sub1/');
        self::assertIsArray($result);
        self::assertEquals(20, $result['uid']);

        $result = $resolver->resolvePageUidAndLanguageBySiteUrl('https://acme.com/de/home/main/sub1?someQueryPart');
        self::assertIsArray($result);
        self::assertEquals(20, $result['uid']);

        $result = $resolver->resolvePageUidAndLanguageBySiteUrl('https://acme.com/de/home/main/sub1#someLocationHash');
        self::assertIsArray($result);
        self::assertEquals(20, $result['uid']);
    }
}
