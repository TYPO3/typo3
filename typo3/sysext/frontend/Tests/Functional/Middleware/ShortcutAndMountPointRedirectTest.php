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
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
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
}
