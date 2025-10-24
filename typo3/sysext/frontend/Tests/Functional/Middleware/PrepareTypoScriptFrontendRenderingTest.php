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
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PrepareTypoScriptFrontendRenderingTest extends FunctionalTestCase
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
    public function notFoundFromHiddenPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/hidden/')
        );
        self::assertEquals(404, $response->getStatusCode());
        self::assertStringContainsString('The requested page does not exist!', (string)$response->getBody());
    }

    #[Test]
    public function okFromHiddenPageWithBackendUser(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationBeUsers.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/hidden/'),
            (new InternalRequestContext())->withBackendUserId(1)
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertStringContainsString('testing-typoScriptFrontendInitialization', (string)$response->getBody());
    }

    #[Test]
    public function notAccessibleFromLoginRestrictedPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/login-restricted/')
        );
        self::assertEquals(403, $response->getStatusCode());
        self::assertStringContainsString('ID was not an accessible page', (string)$response->getBody());
    }

    #[Test]
    public function okFromLoginRestrictedPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationFeGroupsFeUsers.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/login-restricted/'),
            (new InternalRequestContext())->withFrontendUserId(1)
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertStringContainsString('testing-typoScriptFrontendInitialization', (string)$response->getBody());
    }

    #[Test]
    public function notAccessibleFromLoginRestrictedGroupOnePage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/login-restricted-group-one/')
        );
        self::assertEquals(403, $response->getStatusCode());
        self::assertStringContainsString('ID was not an accessible page', (string)$response->getBody());
    }

    #[Test]
    public function notAccessibleFromLoginRestrictedGroupOnePageWithGroupTwo(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationFeGroupsFeUsers.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/login-restricted-group-one/'),
            (new InternalRequestContext())->withFrontendUserId(2)
        );
        self::assertEquals(403, $response->getStatusCode());
        self::assertStringContainsString('ID was not an accessible page', (string)$response->getBody());
    }

    #[Test]
    public function okFromLoginRestrictedGroupOnePageWithGroupOne(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationFeGroupsFeUsers.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/login-restricted-group-one/'),
            (new InternalRequestContext())->withFrontendUserId(1)
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertStringContainsString('testing-typoScriptFrontendInitialization', (string)$response->getBody());
    }

    #[Test]
    public function notFoundFromSpacerPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/spacer/')
        );
        self::assertEquals(404, $response->getStatusCode());
        self::assertStringContainsString('The requested page does not exist!', (string)$response->getBody());
    }

    #[Test]
    public function notFoundFromSysFolderPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/sysfolder/')
        );
        self::assertEquals(404, $response->getStatusCode());
        self::assertStringContainsString('The requested page does not exist!', (string)$response->getBody());
    }

    #[Test]
    public function notFoundFromShortcutTargetDoesNotExist(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/shortcut-target-not-exists/')
        );
        self::assertEquals(404, $response->getStatusCode());
        self::assertStringContainsString('ID was not an accessible page', (string)$response->getBody());
    }

    #[Test]
    public function redirectFoundFromValidShortcutTarget(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/shortcut/')
        );
        self::assertEquals(307, $response->getStatusCode());
    }

    #[Test]
    public function okFromDirectlyRequestedId(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/?id=9')
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertStringContainsString('testing-typoScriptFrontendInitialization', (string)$response->getBody());
    }

    #[Test]
    public function notFoundFromDirectlyRequestedIdOfDifferentDomain(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationCases.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/typoScriptFrontendInitializationSysTemplate.csv');
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/?id=10')
        );
        self::assertEquals(404, $response->getStatusCode());
        self::assertStringContainsString('ID was outside the domain', (string)$response->getBody());
    }
}
