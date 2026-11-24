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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class WorkspacePreviewTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspacePreview.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')],
            $this->buildErrorHandlingConfiguration('Page', [404])
        );
        $this->setUpFrontendRootPage(1, ['EXT:workspaces/Tests/Functional/Middleware/Fixtures/WorkspacePreview.typoscript']);
    }

    public static function previewInfoIsAddedOnceDataProvider(): array
    {
        return [
            'page' => ['https://acme.com/', 200, 'Root', 'https://acme.com/?ADMCMD_prev=LIVE'],
            'error page of a URI that does not exist' => ['https://acme.com/does-not-exist', 404, 'Not found', 'https://acme.com/does-not-exist?ADMCMD_prev=LIVE'],
        ];
    }

    #[DataProvider('previewInfoIsAddedOnceDataProvider')]
    #[Test]
    public function previewInfoIsAddedOnce(string $uri, int $expectedStatusCode, string $expectedContent, string $expectedLiveUrl): void
    {
        $context = new InternalRequestContext();
        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            $context->withBackendUserId(1)->withWorkspaceId(91)
        );
        $body = (string)$response->getBody();

        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertStringContainsString($expectedContent, $body);
        self::assertSame(1, substr_count($body, 'id="typo3-preview-info"'));
        self::assertStringContainsString('data-live-url="' . $expectedLiveUrl . '"', $body);
    }

    #[Test]
    public function previewInfoIsAddedIfOnlyOriginalRequestAttributeIsSet(): void
    {
        $context = new InternalRequestContext();
        $request = new InternalRequest('https://acme.com/')
            ->withAttribute('originalRequest', new ServerRequest('https://acme.com/'));
        $response = $this->executeFrontendSubRequest(
            $request,
            $context->withBackendUserId(1)->withWorkspaceId(91)
        );

        self::assertSame(1, substr_count((string)$response->getBody(), 'id="typo3-preview-info"'));
    }
}
