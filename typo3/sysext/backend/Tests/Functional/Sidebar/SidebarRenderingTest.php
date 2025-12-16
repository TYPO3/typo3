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

namespace TYPO3\CMS\Backend\Tests\Functional\Sidebar;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Sidebar\ModuleMenuSidebarComponent;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentContext;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentsRegistry;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SidebarRenderingTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function sidebarContainerStructureIsProperlyRendered(): void
    {
        $request = $this->createBackendRequest();
        $response = $this->get(BackendController::class)->mainAction($request);
        $content = (string)$response->getBody();

        self::assertStringContainsString('<div class="sidebar-container">', $content);
    }

    #[Test]
    public function moduleMenuSidebarComponentIsRegistered(): void
    {
        $registry = $this->get(SidebarComponentsRegistry::class);

        self::assertTrue($registry->hasComponent('module-menu'));

        $moduleMenu = $registry->getComponent('module-menu');
        self::assertInstanceOf(ModuleMenuSidebarComponent::class, $moduleMenu);
    }

    #[Test]
    public function moduleMenuIsRenderedWithProperAttributes(): void
    {
        $request = $this->createBackendRequest();
        $response = $this->get(BackendController::class)->mainAction($request);
        $content = (string)$response->getBody();

        self::assertStringContainsString(
            '<div class="sidebar-component" data-identifier="module-menu">',
            $content,
        );

        self::assertMatchesRegularExpression(
            '/<nav\s+[^>]*class="[^"]*modulemenu[^"]*"[^>]*data-modulemenu[^>]*>/',
            $content,
        );
    }

    #[Test]
    public function registryReturnsComponents(): void
    {
        $registry = $this->get(SidebarComponentsRegistry::class);
        $components = $registry->getComponents();

        self::assertArrayHasKey('module-menu', $components);
        self::assertInstanceOf(ModuleMenuSidebarComponent::class, $components['module-menu']);
    }

    #[Test]
    public function registryGetComponentThrowsExceptionForNonExistingComponent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1765923035);
        $this->expectExceptionMessage('Sidebar component with identifier "non-existing-component" is not registered');

        $registry = $this->get(SidebarComponentsRegistry::class);
        $registry->getComponent('non-existing-component');
    }

    #[Test]
    public function sidebarComponentReturnsJavaScriptModule(): void
    {
        $registry = $this->get(SidebarComponentsRegistry::class);
        $context = new SidebarComponentContext(
            $this->createBackendRequest(),
            $GLOBALS['BE_USER'],
        );
        $moduleMenu = $registry->getComponent('module-menu');

        $jsModule = $moduleMenu->getResult($context)->module;
        self::assertEquals('@typo3/backend/module-menu.js', $jsModule);
    }

    #[Test]
    public function moduleMenuCheckAccessReturnsTrue(): void
    {
        $registry = $this->get(SidebarComponentsRegistry::class);
        $moduleMenu = $registry->getComponent('module-menu');

        $context = new SidebarComponentContext(
            $this->createBackendRequest(),
            $GLOBALS['BE_USER'],
        );

        self::assertTrue($moduleMenu->hasAccess($context), 'Module menu should always be accessible for logged in users');
    }

    private function createBackendRequest(): ServerRequest
    {
        $request = (new ServerRequest('https://example.com/typo3/main'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/main', ['packageName' => 'typo3/cms-backend', '_identifier' => 'main']));

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }
}
