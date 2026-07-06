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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Breadcrumb\BreadcrumbContext;
use TYPO3\CMS\Backend\Controller\SiteConfigurationController;
use TYPO3\CMS\Backend\Controller\SiteSettingsController;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteResult;
use TYPO3\CMS\Backend\Template\Components\Breadcrumb;
use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The site configuration module is not navigated via the page tree, so the site's root page is not
 * part of a rootline there: it stands for the site itself and links to the detail view, which is
 * the entry point for a single site.
 */
final class SiteBreadcrumbTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->importCSVDataSet(__DIR__ . '/../Template/Components/Fixtures/BreadcrumbTestPages.csv');
    }

    #[Test]
    public function detailViewMarksTheRootPageAsCurrentPosition(): void
    {
        $context = $this->buildSiteConfigurationBreadcrumb(['uid' => 1, 'title' => 'Root Page'], null, null);

        self::assertCount(1, $context->suffixNodes);
        self::assertSame('Root Page', $context->suffixNodes[0]->label);
        self::assertNull($context->suffixNodes[0]->url, 'The detail view is the current position and carries no link');
    }

    #[Test]
    public function editViewLinksTheRootPageToTheDetailView(): void
    {
        $context = $this->buildSiteConfigurationBreadcrumb(['uid' => 1, 'title' => 'Root Page'], 'main', 'Site configuration', 'actions-open');

        self::assertCount(2, $context->suffixNodes);

        self::assertSame('Root Page', $context->suffixNodes[0]->label);
        self::assertNotNull($context->suffixNodes[0]->url);
        self::assertStringContainsString('/module/site/configuration/detail', $context->suffixNodes[0]->url);
        self::assertStringContainsString('site=main', $context->suffixNodes[0]->url);

        self::assertSame('Site configuration', $context->suffixNodes[1]->label);
        self::assertSame('actions-open', $context->suffixNodes[1]->icon, 'Same icon as the overview button leading here');
        self::assertNull($context->suffixNodes[1]->url, 'The current step carries no link');
    }

    #[Test]
    public function newSiteHasNoRootPageAndNoDetailView(): void
    {
        $context = $this->buildSiteConfigurationBreadcrumb([], null, 'Create new site', 'actions-plus');

        self::assertCount(1, $context->suffixNodes);
        self::assertSame('Create new site', $context->suffixNodes[0]->label);
        self::assertSame('actions-plus', $context->suffixNodes[0]->icon);
        self::assertNull($context->suffixNodes[0]->url);
    }

    #[Test]
    public function siteSettingsLinkTheRootPageToTheDetailView(): void
    {
        $site = new Site('main', 1, []);
        $controller = $this->get(SiteSettingsController::class);
        $moduleTemplate = $this->get(ModuleTemplateFactory::class)->create($this->createSiteConfigurationRequest());

        (new \ReflectionMethod($controller, 'addDocHeaderBreadcrumb'))->invoke($controller, $moduleTemplate, $site);

        $context = $this->readBreadcrumbContext($moduleTemplate);

        self::assertCount(2, $context->suffixNodes);

        self::assertSame('Root Page', $context->suffixNodes[0]->label);
        self::assertNotNull($context->suffixNodes[0]->url);
        self::assertStringContainsString('/module/site/configuration/detail', $context->suffixNodes[0]->url);
        self::assertStringContainsString('site=main', $context->suffixNodes[0]->url);

        self::assertSame('actions-cog', $context->suffixNodes[1]->icon, 'Same icon as the overview button leading here');
        self::assertNull($context->suffixNodes[1]->url, 'The current step carries no link');
    }

    #[Test]
    public function renderedTrailCarriesTheModuleAndNoVirtualPageRoot(): void
    {
        $context = $this->buildSiteConfigurationBreadcrumb(['uid' => 1, 'title' => 'Root Page'], 'main', 'Site configuration', 'actions-open');

        $nodes = $this->get(Breadcrumb::class)->getBreadcrumb($this->createSiteConfigurationRequest(), $context);

        // Module node, root page, current step - the module is not page-tree navigated, so no
        // virtual page root node is added in front of them.
        self::assertCount(3, $nodes);
        self::assertSame('site_configuration', $nodes[0]->identifier);
        self::assertSame('Root Page', $nodes[1]->label);
        self::assertSame('Site configuration', $nodes[2]->label);

        // The module node must not lead back into the edit route the request came from.
        self::assertNotNull($nodes[0]->url);
        self::assertStringNotContainsString('/configuration/edit', $nodes[0]->url);
    }

    private function createSiteConfigurationRequest(): ServerRequestInterface
    {
        $module = $this->get(ModuleProvider::class)->getModule('site_configuration', $GLOBALS['BE_USER']);
        $route = new Route('/module/site/configuration/edit', [
            '_identifier' => 'site_configuration.edit',
            'module' => $module,
        ]);
        $request = (new ServerRequest('https://example.com/typo3/module/site/configuration/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $route)
            ->withAttribute('routing', new RouteResult($route))
            ->withAttribute('module', $module)
            ->withQueryParams(['site' => 'main']);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    /**
     * @param array<string, mixed> $pageRecord
     */
    private function buildSiteConfigurationBreadcrumb(array $pageRecord, ?string $siteIdentifier, ?string $currentStepLabel, ?string $currentStepIcon = null): BreadcrumbContext
    {
        $controller = $this->get(SiteConfigurationController::class);
        $moduleTemplate = $this->get(ModuleTemplateFactory::class)->create($this->createSiteConfigurationRequest());

        (new \ReflectionMethod($controller, 'addDocHeaderBreadcrumb'))
            ->invoke($controller, $moduleTemplate, $pageRecord, $siteIdentifier, $currentStepLabel, $currentStepIcon);

        return $this->readBreadcrumbContext($moduleTemplate);
    }

    private function readBreadcrumbContext(ModuleTemplate $moduleTemplate): BreadcrumbContext
    {
        $context = (new \ReflectionProperty(DocHeaderComponent::class, 'breadcrumbContext'))
            ->getValue($moduleTemplate->getDocHeaderComponent());
        self::assertInstanceOf(BreadcrumbContext::class, $context);

        return $context;
    }
}
