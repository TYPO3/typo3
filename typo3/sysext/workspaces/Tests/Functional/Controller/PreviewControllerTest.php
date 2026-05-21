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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Workspaces\Controller\PreviewController;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PreviewControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8', 'iso' => 'de'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/fe_groups.csv');
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = 91;
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(91));
        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1, 'https://example.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://example.com/'),
                $this->buildLanguageConfiguration('DE', 'https://example.com/de/'),
            ],
        );
    }

    public static function previewUrlsDataProvider(): array
    {
        return [
            'page without access restriction' => [
                2,
                ['path' => '/public', 'ADMCMD_simUser' => null],
                ['path' => '/public', 'ADMCMD_simUser' => null],
            ],
            'restricted page without draft version' => [
                3,
                ['path' => '/restricted', 'ADMCMD_simUser' => '1'],
                ['path' => '/restricted', 'ADMCMD_simUser' => '1'],
            ],
            'restricted page with draft version restricted to the same group' => [
                4,
                ['path' => '/restricted-modified', 'ADMCMD_simUser' => '1'],
                ['path' => '/restricted-modified', 'ADMCMD_simUser' => '1'],
            ],
            'page restricted in draft version only' => [
                5,
                ['path' => '/restricted-only-in-draft', 'ADMCMD_simUser' => null],
                ['path' => '/restricted-only-in-draft', 'ADMCMD_simUser' => '2'],
            ],
            'live and draft version restricted to different groups' => [
                6,
                ['path' => '/different-groups', 'ADMCMD_simUser' => '1'],
                ['path' => '/different-groups', 'ADMCMD_simUser' => '1,2'],
            ],
            'restricted page created in workspace' => [
                7,
                null,
                ['path' => '/new-restricted', 'ADMCMD_simUser' => '3'],
            ],
            'page shown at any login' => [
                10,
                ['path' => '/show-at-any-login', 'ADMCMD_simUser' => '1'],
                ['path' => '/show-at-any-login', 'ADMCMD_simUser' => '1'],
            ],
        ];
    }

    #[DataProvider('previewUrlsDataProvider')]
    #[Test]
    public function handleRequestRendersPreviewUrlsForPage(int $pageId, ?array $expectedLiveUrl, array $expectedDraftUrl): void
    {
        $content = $this->renderPreview($pageId);

        self::assertSame($expectedLiveUrl, $this->extractPreviewUrl($content, 'live'));
        self::assertSame($expectedDraftUrl, $this->extractPreviewUrl($content, 'workspace'));
    }

    public static function draftUrlOfTranslationCreatedInWorkspaceDataProvider(): array
    {
        return [
            'page without access restriction' => [
                8,
                ['path' => '/de/translated-in-draft-de', 'ADMCMD_simUser' => null],
            ],
            'restricted page' => [
                9,
                ['path' => '/de/restricted-translated-in-draft-de', 'ADMCMD_simUser' => '1'],
            ],
        ];
    }

    #[DataProvider('draftUrlOfTranslationCreatedInWorkspaceDataProvider')]
    #[Test]
    public function handleRequestRendersDraftUrlOfTranslationCreatedInWorkspace(int $pageId, array $expectedDraftUrl): void
    {
        $content = $this->renderPreview($pageId, 1);

        self::assertSame($expectedDraftUrl, $this->extractPreviewUrl($content, 'workspace'));
    }

    public static function draftUrlRendersDraftVersionInFrontendDataProvider(): array
    {
        return [
            'restricted page with draft version restricted to the same group' => [4, 'Restricted modified (draft)'],
            'page restricted in draft version only' => [5, 'Restricted only in draft (draft)'],
            'live and draft version restricted to different groups' => [6, 'Different groups (draft)'],
        ];
    }

    #[DataProvider('draftUrlRendersDraftVersionInFrontendDataProvider')]
    #[Test]
    public function draftUrlRendersDraftVersionInFrontend(int $pageId, string $expectedTitle): void
    {
        $this->setUpFrontendRootPage(1);
        $this->addTypoScriptToTemplateRecord(1, 'page = PAGE' . LF . 'page.10 = TEXT' . LF . 'page.10.field = title');
        preg_match('/class="typo3-workspace-view-workspace [^"]*" src="([^"]*)"/', $this->renderPreview($pageId), $matches);

        $response = $this->executeFrontendSubRequest(
            new InternalRequest(html_entity_decode($matches[1])),
            new InternalRequestContext()->withBackendUserId(1)->withWorkspaceId(91)
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString($expectedTitle, (string)$response->getBody());
    }

    #[Test]
    public function sliderModeIsActiveIfNoPreviewModeIsStored(): void
    {
        $content = $this->renderPreview(1);

        self::assertStringContainsString('data-active-preview-mode="slider"', $content);
        self::assertStringContainsString('typo3-workspace-preview-slider', $content);
        self::assertStringNotContainsString('typo3-workspace-topbar-slider-hidden', $content);
    }

    public static function storedPreviewModeIsActiveDataProvider(): array
    {
        return [
            'vbox' => ['vbox'],
            'hbox' => ['hbox'],
        ];
    }

    #[Test]
    #[DataProvider('storedPreviewModeIsActiveDataProvider')]
    public function storedPreviewModeIsActive(string $previewMode): void
    {
        $GLOBALS['BE_USER']->uc['workspaces']['previewMode'] = $previewMode;

        $content = $this->renderPreview(1);

        self::assertStringContainsString('data-active-preview-mode="' . $previewMode . '"', $content);
        self::assertStringContainsString('typo3-workspace-preview-' . $previewMode, $content);
        self::assertStringNotContainsString('typo3-workspace-preview-slider', $content);
        self::assertStringContainsString('typo3-workspace-topbar-slider-hidden', $content);
    }

    #[Test]
    public function unknownStoredPreviewModeFallsBackToFirstAllowedMode(): void
    {
        $GLOBALS['BE_USER']->uc['workspaces']['previewMode'] = 'bogus';

        $content = $this->renderPreview(1);

        self::assertStringContainsString('data-active-preview-mode="slider"', $content);
        self::assertStringNotContainsString('bogus', $content);
        self::assertStringNotContainsString('typo3-workspace-topbar-slider-hidden', $content);
    }

    private function renderPreview(int $pageId, int $languageId = 0): string
    {
        $request = new ServerRequest('https://example.com/typo3/module/workspaces/preview')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/workspaces/preview', ['packageName' => 'typo3/cms-workspaces', '_identifier' => 'workspace_previewcontrols']))
            ->withQueryParams(['id' => $pageId, 'L' => $languageId]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return (string)$this->get(PreviewController::class)->handleRequest($request)->getBody();
    }

    private function extractPreviewUrl(string $content, string $frame): ?array
    {
        if (!preg_match('/class="typo3-workspace-view-' . $frame . ' [^"]*" src="([^"]*)"/', $content, $matches)) {
            return null;
        }
        $url = html_entity_decode($matches[1]);
        parse_str((string)parse_url($url, PHP_URL_QUERY), $queryParameters);
        return [
            'path' => parse_url($url, PHP_URL_PATH),
            'ADMCMD_simUser' => $queryParameters['ADMCMD_simUser'] ?? null,
        ];
    }
}
