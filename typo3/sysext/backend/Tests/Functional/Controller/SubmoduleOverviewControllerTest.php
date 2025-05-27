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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\SubmoduleOverviewController;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SubmoduleOverviewControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['info'];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function handleRequestRendersOverviewWithAccessibleSubmodules(): void
    {
        $moduleProvider = $this->get(ModuleProvider::class);
        $module = $moduleProvider->getModule('web_info', $GLOBALS['BE_USER']);

        $request = (new ServerRequest('https://example.com/typo3/module/web/info'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/web/info', [
                'packageName' => 'typo3/cms-info',
                '_identifier' => 'web_info',
            ]))
            ->withAttribute('module', $module);

        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $subject = $this->get(SubmoduleOverviewController::class);
        $response = $subject->handleRequest($request);

        $content = (string)$response->getBody();

        // Verify the overview page is rendered
        self::assertStringContainsString('card-container', $content);

        // Verify submodule cards are present
        self::assertStringContainsString('Pagetree Overview', $content);
        self::assertStringContainsString('Localization Overview', $content);

        // Verify descriptions are shown
        self::assertStringContainsString('View page records and settings in a tree structure with detailed metadata', $content);
        self::assertStringContainsString('Check translation status and manage localized content for pages', $content);

        // Verify action buttons are present
        self::assertStringContainsString('Open module', $content);
    }

    #[Test]
    public function handleRequestIncludesDocHeaderElements(): void
    {
        $moduleProvider = $this->get(ModuleProvider::class);
        $module = $moduleProvider->getModule('web_info', $GLOBALS['BE_USER']);

        $request = (new ServerRequest('https://example.com/typo3/module/web/info'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/web/info', [
                'packageName' => 'typo3/cms-info',
                '_identifier' => 'web_info',
            ]))
            ->withAttribute('module', $module);

        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $subject = $this->get(SubmoduleOverviewController::class);
        $response = $subject->handleRequest($request);

        $content = (string)$response->getBody();

        // Verify reload button is present
        self::assertStringContainsString('actions-refresh', $content);

        // Verify shortcut button is present
        self::assertStringContainsString('actions-star', $content);
    }

    #[Test]
    public function handleRequestIncludesPageInformationWhenIdParameterProvided(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');

        $moduleProvider = $this->get(ModuleProvider::class);
        $module = $moduleProvider->getModule('web_info', $GLOBALS['BE_USER']);

        $request = (new ServerRequest('https://example.com/typo3/module/web/info'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['id' => 1])
            ->withAttribute('route', new Route('/module/web/info', [
                'packageName' => 'typo3/cms-info',
                '_identifier' => 'web_info',
            ]))
            ->withAttribute('module', $module);

        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $subject = $this->get(SubmoduleOverviewController::class);
        $response = $subject->handleRequest($request);

        $content = (string)$response->getBody();

        self::assertStringContainsString(htmlspecialchars(json_encode([
            [
                'identifier' => 'web_info',
                'label' => 'Info',
                'icon' => 'module-info',
                'iconOverlay' => null,
                'route' => [
                    'module' => 'web_info',
                    'params' => [
                        'id' => '0',
                    ],
                ],
                'forceShowIcon' => true,
            ],
            [
                'identifier' => '1',
                'label' => 'Root',
                'icon' => 'apps-pagetree-page-default',
                'iconOverlay' => null,
                'route' => [
                    'module' => 'web_info',
                    'params' => [
                        'id' => '1',
                    ],
                ],
                'forceShowIcon' => false,
            ],
        ]), ENT_QUOTES), $content);
    }

    #[Test]
    public function handleRequestWithoutModuleAttributeRendersEmptyPage(): void
    {
        $request = (new ServerRequest('https://example.com/typo3/module/web/info'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/web/info', [
                'packageName' => 'typo3/cms-info',
                '_identifier' => 'web_info',
            ]));

        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $subject = $this->get(SubmoduleOverviewController::class);
        $response = $subject->handleRequest($request);

        $content = (string)$response->getBody();

        // When no module is present, the no access message is displayed
        self::assertStringContainsString('No module access', $content);
    }

    #[Test]
    public function handleRequestAddsIdParameterToSubmoduleLinks(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');

        $moduleProvider = $this->get(ModuleProvider::class);
        $module = $moduleProvider->getModule('web_info', $GLOBALS['BE_USER']);

        $pageId = 42;
        $request = (new ServerRequest('https://example.com/typo3/module/web/info'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['id' => $pageId])
            ->withAttribute('route', new Route('/module/web/info', [
                'packageName' => 'typo3/cms-info',
                '_identifier' => 'web_info',
            ]))
            ->withAttribute('module', $module);

        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $subject = $this->get(SubmoduleOverviewController::class);
        $response = $subject->handleRequest($request);

        $content = (string)$response->getBody();

        self::assertMatchesRegularExpression(
            '#href="[^"]*/typo3/module/web/info/overview\?[^"]*&amp;id=' . $pageId . '[^"]*"#',
            $content,
            'Pagetree Overview link should include id parameter after token'
        );
        self::assertMatchesRegularExpression(
            '#href="[^"]*/typo3/module/web/info/translations\?[^"]*&amp;id=' . $pageId . '[^"]*"#',
            $content,
            'Localization Overview link should include id parameter after token'
        );
    }

    #[Test]
    public function handleRequestShowsInfoboxWhenNoSubmodulesAvailable(): void
    {
        // Use a backend user with no access to info submodules
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_no_info_access.csv');
        $backendUser = $this->setUpBackendUser(2);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Get the web_info module for the restricted user
        // The module exists but has no accessible submodules for this user
        $moduleProvider = $this->get(ModuleProvider::class);
        $module = $moduleProvider->getModule('web_info', $backendUser);

        $request = (new ServerRequest('https://example.com/typo3/module/web/info'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/web/info', [
                'packageName' => 'typo3/cms-info',
                '_identifier' => 'web_info',
            ]))
            ->withAttribute('module', $module);

        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $subject = $this->get(SubmoduleOverviewController::class);
        $response = $subject->handleRequest($request);

        $content = (string)$response->getBody();

        self::assertStringContainsString('No modules available', $content);
        self::assertStringContainsString('There are no submodules available or you do not have access to any of them', $content);
        self::assertStringNotContainsString('card-container', $content);
        self::assertStringContainsString('callout-info', $content);
    }
}
