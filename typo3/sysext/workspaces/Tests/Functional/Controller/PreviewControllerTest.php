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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Workspaces\Controller\PreviewController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PreviewControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://example.com/'),
        );
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = 91;
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function sliderModeIsActiveIfNoPreviewModeIsStored(): void
    {
        $content = $this->renderPreview();

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

        $content = $this->renderPreview();

        self::assertStringContainsString('data-active-preview-mode="' . $previewMode . '"', $content);
        self::assertStringContainsString('typo3-workspace-preview-' . $previewMode, $content);
        self::assertStringNotContainsString('typo3-workspace-preview-slider', $content);
        self::assertStringContainsString('typo3-workspace-topbar-slider-hidden', $content);
    }

    #[Test]
    public function unknownStoredPreviewModeFallsBackToFirstAllowedMode(): void
    {
        $GLOBALS['BE_USER']->uc['workspaces']['previewMode'] = 'bogus';

        $content = $this->renderPreview();

        self::assertStringContainsString('data-active-preview-mode="slider"', $content);
        self::assertStringNotContainsString('bogus', $content);
        self::assertStringNotContainsString('typo3-workspace-topbar-slider-hidden', $content);
    }

    private function renderPreview(): string
    {
        $request = new ServerRequest('https://example.com/typo3/workspace/preview-control')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/workspace/preview-control', []))
            ->withQueryParams(['id' => 1]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $response = $this->get(PreviewController::class)->handleRequest($request);
        return (string)$response->getBody();
    }
}
