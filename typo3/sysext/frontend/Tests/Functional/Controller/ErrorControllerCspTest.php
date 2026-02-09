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

namespace TYPO3\CMS\Frontend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ErrorControllerCspTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const ROOT_PAGE_ID = 1;
    private const ACCESS_PROTECTED_PAGE = 2;
    private const NON_EXISTING_PAGE_ID = 999;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'features' => [
                'security.frontend.enforceContentSecurityPolicy' => true,
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages-error-controller.csv');
        $this->writeSiteConfiguration(
            'error_controller_csp',
            $this->buildSiteConfiguration(self::ROOT_PAGE_ID, '/'),
        );
        $this->setUpFrontendRootPage(self::ROOT_PAGE_ID, ['EXT:frontend/Tests/Functional/Fixtures/TypoScript/page.typoscript']);
    }

    #[Test]
    public function cspHeadersAreAddedFor404Response(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::NON_EXISTING_PAGE_ID));

        self::assertEquals(404, $response->getStatusCode());
        self::assertStringContainsString('The requested page does not exist', (string)$response->getBody());
        $cspHeader = $response->getHeaderLine('Content-Security-Policy');
        self::assertStringContainsString('style-src-elem', $cspHeader);
        self::assertStringContainsString('nonce', $cspHeader);
    }

    #[Test]
    public function cspHeadersAreAddedFor403Response(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ACCESS_PROTECTED_PAGE));

        self::assertEquals(403, $response->getStatusCode());
        self::assertStringContainsString('ID was not an accessible page', (string)$response->getBody());
        $cspHeader = $response->getHeaderLine('Content-Security-Policy');
        self::assertStringContainsString('style-src-elem', $cspHeader);
        self::assertStringContainsString('nonce', $cspHeader);
    }
}
