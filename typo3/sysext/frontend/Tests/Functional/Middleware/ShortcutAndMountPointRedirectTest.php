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

namespace TYPO3\CMS\Frontend\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ShortcutAndMountPointRedirectTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ]
        );
    }

    #[Test]
    public function pageLinkToPageResolved(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageLinkAndShortcutCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/page-link/')
        );
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function pageLinkToPageLinkPageResolved(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageLinkAndShortcutCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/page-link-cycle-2/')
        );
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function pageLinkToShortcutPageResolved(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageLinkAndShortcutCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/page-link-to-shortcut/')
        );
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function pageLinkCircleResponseWith404(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageLinkAndShortcutCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/page-link-circle-3/')
        );
        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function standardPageTranslationOfPageLinkIsRendered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageLinkAndShortcutCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/de/page-link-translated-to-standard-page/')
        );
        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($response->hasHeader('X-Redirect-By'));
    }

    #[Test]
    public function pageLinkTranslationOfStandardPageIsResolved(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageLinkAndShortcutCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/de/standard-page-translated-to-page-link/')
        );
        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($response->hasHeader('X-Redirect-By'));
        self::assertStringContainsString('page-uid-25', (string)$response->getBody());
    }

    #[Test]
    public function shortcutTranslationOfStandardPageRedirects(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageLinkAndShortcutCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/de/standard-page-translated-to-shortcut/')
        );
        self::assertSame(307, $response->getStatusCode());
        self::assertSame('https://acme.com/de/shortcut-target/', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function standardPageTranslationOfSysfolderIsRendered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageLinkAndShortcutCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/de/sysfolder-translated-to-standard-page/')
        );
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function sysfolderTranslationOfStandardPageResponseWith404(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageLinkAndShortcutCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        // Requested by id, since routing does not resolve the slug of a sysfolder translation at all
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/de/?id=23')
        );
        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function standardPageTranslationOfMountPointIsNotMounted(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageLinkAndShortcutCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $connection = $this->getConnectionPool()->getConnectionForTable('pages');
        // Mount point fields are excluded from translation, so the translation carries the same values
        foreach ([27, 28] as $uid) {
            $connection->update('pages', ['mount_pid' => 25, 'mount_pid_ol' => 1], ['uid' => $uid]);
        }
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/de/mount-point-translated-to-standard-page/')
        );
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('page-uid-27', (string)$response->getBody());
    }
}
