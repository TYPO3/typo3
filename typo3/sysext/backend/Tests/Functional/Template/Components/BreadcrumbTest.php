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

namespace TYPO3\CMS\Backend\Tests\Functional\Template\Components;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Breadcrumb\BreadcrumbContext;
use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\RouteResult;
use TYPO3\CMS\Backend\Template\Components\Breadcrumb;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BreadcrumbTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['info'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BreadcrumbTestPages.csv');
    }

    #[Test]
    public function breadcrumbGeneratesPageHierarchy(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 3));

        $context = new BreadcrumbContext($pageRecord);

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb(null, $context);

        self::assertCount(4, $nodes);

        foreach ($nodes as $node) {
            self::assertNotEmpty($node->label);
        }
    }

    #[Test]
    public function breadcrumbHandlesNullContext(): void
    {
        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb(null, null);

        // NullContextBreadcrumbProvider should handle this and return a virtual root node
        self::assertNotEmpty($nodes, 'Should return nodes from NullContextBreadcrumbProvider');
        self::assertSame('0', $nodes[0]->identifier, 'Should have virtual page root node');
    }

    #[Test]
    public function breadcrumbHandlesContextWithNullMainContextAndNoSuffixNodes(): void
    {
        $context = new BreadcrumbContext(null);

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb(null, $context);

        // NullContextBreadcrumbProvider should handle this and return a virtual root node
        self::assertNotEmpty($nodes, 'Should return nodes from NullContextBreadcrumbProvider');
        self::assertSame('0', $nodes[0]->identifier, 'Should have virtual page root node');
    }

    #[Test]
    public function breadcrumbIncludesBothProviderNodesAndSuffixNodesWhenMainContextIsNull(): void
    {
        $suffixNode = new BreadcrumbNode(
            identifier: 'suffix-only',
            label: 'Suffix Only',
            icon: 'actions-document',
        );

        $context = new BreadcrumbContext(null, [$suffixNode]);

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb(null, $context);

        // Should have provider node(s) + suffix node
        self::assertGreaterThanOrEqual(2, count($nodes), 'Should have provider nodes and suffix node');

        // First node should be from NullContextBreadcrumbProvider
        self::assertSame('0', $nodes[0]->identifier, 'First node should be virtual page root from provider');

        // Last node should be the suffix node
        $lastNode = end($nodes);
        self::assertSame('suffix-only', $lastNode->identifier);
        self::assertSame('Suffix Only', $lastNode->label);
    }

    #[Test]
    public function breadcrumbHandlesMultipleSuffixNodes(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $suffixNodes = [
            new BreadcrumbNode(identifier: 'suffix-1', label: 'Edit', icon: 'actions-edit'),
            new BreadcrumbNode(identifier: 'suffix-2', label: 'Preview', icon: 'actions-eye'),
            new BreadcrumbNode(identifier: 'suffix-3', label: 'Save', icon: 'actions-save'),
        ];

        $context = new BreadcrumbContext($pageRecord, $suffixNodes);

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb(null, $context);

        // Verify all suffix nodes are present
        $nodeIdentifiers = array_map(static fn($node) => $node->identifier, $nodes);
        self::assertContains('suffix-1', $nodeIdentifiers);
        self::assertContains('suffix-2', $nodeIdentifiers);
        self::assertContains('suffix-3', $nodeIdentifiers);

        // Verify suffix nodes appear at the end
        $lastThreeNodes = array_slice($nodes, -3);
        self::assertSame('suffix-1', $lastThreeNodes[0]->identifier);
        self::assertSame('suffix-2', $lastThreeNodes[1]->identifier);
        self::assertSame('suffix-3', $lastThreeNodes[2]->identifier);
    }

    #[Test]
    public function breadcrumbNodePropertiesArePreserved(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb(null, $context);

        // Verify nodes have expected properties
        foreach ($nodes as $node) {
            self::assertNotSame('', $node->identifier, 'Each node must have an identifier');
            self::assertNotEmpty($node->label, 'Each node must have a label');

            // URL is optional but if present must be a non-empty string
            if ($node->url !== null) {
                self::assertNotEmpty($node->url);
            }
        }
    }

    #[Test]
    public function nullContextProviderGeneratesVirtualPageRootNode(): void
    {
        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb(null, null);

        // NullContextBreadcrumbProvider should generate a virtual page root node
        self::assertCount(1, $nodes);

        $expectedJson = [[
            'identifier' => '0',
            'label' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
            'icon' => 'apps-pagetree-root',
            'iconOverlay' => null,
            'url' => null,
            'forceShowIcon' => false,
        ]];

        self::assertSame($expectedJson, json_decode(json_encode($nodes), true));
    }

    #[Test]
    public function recordProviderGeneratesCorrectJsonStructureWithModule(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        $request = $this->createMockRequest('web_layout');

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        // Should have module node + current page (no root node when module is available)
        self::assertCount(2, $nodes);

        // First node should be the module
        self::assertSame('web_layout', $nodes[0]->identifier);
        self::assertSame('Layout', $nodes[0]->label);
        self::assertSame('module-page', $nodes[0]->icon);
        self::assertTrue($nodes[0]->forceShowIcon);

        // Module should have URL
        self::assertNotNull($nodes[0]->url);
        self::assertStringContainsString('/module/web/layout', $nodes[0]->url);

        // Last node should be the current page (has URL since it's a page record)
        $lastNode = $nodes[count($nodes) - 1];
        self::assertSame('1', $lastNode->identifier);
        self::assertNotNull($lastNode->url, 'Page records have URLs');
        self::assertStringContainsString('/module/web/layout', $lastNode->url);
        self::assertStringContainsString('id=1', $lastNode->url);
    }

    #[Test]
    public function recordProviderUsesModuleFallbackWhenNoModuleInRequest(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 2));

        $context = new BreadcrumbContext($pageRecord);

        $breadcrumb = $this->get(Breadcrumb::class);
        // No request = no module
        $nodes = $breadcrumb->getBreadcrumb(null, $context);

        // Should have root node + rootline + current page
        self::assertGreaterThanOrEqual(2, count($nodes));

        // First node should be the site root (fallback when no module)
        self::assertSame('0', $nodes[0]->identifier);
        self::assertSame($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'], $nodes[0]->label);
        self::assertSame('apps-pagetree-root', $nodes[0]->icon);

        // Should use web_layout as fallback module in URLs
        if ($nodes[0]->url !== null) {
            self::assertStringContainsString('/module/web/layout', $nodes[0]->url);
        }
    }

    #[Test]
    public function recordProviderGeneratesCompletePageHierarchyWithModuleDetection(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 3));

        $context = new BreadcrumbContext($pageRecord);

        $request = $this->createMockRequest('content_status');

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        self::assertGreaterThanOrEqual(2, count($nodes));

        self::assertSame('content_status', $nodes[0]->identifier);
        self::assertNotNull($nodes[0]->url);
        self::assertStringContainsString('/module/content/status', $nodes[0]->url, 'Module node should use content_status');

        $urlCount = 0;
        foreach ($nodes as $node) {
            if ($node->url !== null) {
                self::assertStringContainsString('/module/content/status', $node->url, 'All URLs should use detected module content_status');
                $urlCount++;
            }
        }
        self::assertGreaterThan(0, $urlCount, 'Should have at least one URL');

        $jsonNodes = json_decode(json_encode($nodes), true);
        self::assertIsArray($jsonNodes);

        foreach ($jsonNodes as $jsonNode) {
            self::assertArrayHasKey('identifier', $jsonNode);
            self::assertArrayHasKey('label', $jsonNode);
            self::assertArrayHasKey('icon', $jsonNode);
            self::assertArrayHasKey('iconOverlay', $jsonNode);
            self::assertArrayHasKey('url', $jsonNode);
            self::assertArrayHasKey('forceShowIcon', $jsonNode);
        }
    }

    #[Test]
    public function suffixNodesAreProperlySerializedToJson(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $suffixNode = new BreadcrumbNode(
            identifier: 'new-content',
            label: 'Create New Content',
            icon: 'actions-plus',
            iconOverlay: 'overlay-new',
        );

        $context = new BreadcrumbContext($pageRecord, [$suffixNode]);

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb(null, $context);

        $lastNode = $nodes[count($nodes) - 1];
        self::assertSame('new-content', $lastNode->identifier);
        self::assertSame('Create New Content', $lastNode->label);
        self::assertSame('actions-plus', $lastNode->icon);
        self::assertSame('overlay-new', $lastNode->iconOverlay);

        $jsonNodes = json_decode(json_encode($nodes), true);
        $lastJsonNode = $jsonNodes[count($jsonNodes) - 1];

        $expectedSuffixNode = [
            'identifier' => 'new-content',
            'label' => 'Create New Content',
            'icon' => 'actions-plus',
            'iconOverlay' => 'overlay-new',
            'url' => null,
            'forceShowIcon' => false,
        ];

        self::assertSame($expectedSuffixNode, $lastJsonNode);
    }

    #[Test]
    public function moduleResolverWorksWithQueryParams(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        $request = new ServerRequest('https://example.com/typo3/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams([
                'id' => 1,
                'module' => 'web_layout',
            ]);

        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        self::assertSame('web_layout', $nodes[0]->identifier);
        self::assertNotNull($nodes[0]->url);
        self::assertStringContainsString('/module/web/layout', $nodes[0]->url);
    }

    #[Test]
    public function breadcrumbIncludesModuleHierarchyForThirdLevelModule(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        // web_info_overview is a third-level module (parent: content_status, grandparent: content)
        $request = $this->createMockRequest('web_info_overview');

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        // Should have: content_status (parent) + web_info_overview (current) + page
        self::assertGreaterThanOrEqual(3, count($nodes), 'Should have parent module, current module, and page');

        // First node should be the parent module (content_status)
        self::assertSame('content_status', $nodes[0]->identifier, 'First node should be parent module');

        // Second node should be the current third-level module
        self::assertSame('web_info_overview', $nodes[1]->identifier, 'Second node should be current third-level module');

        // All module nodes should be clickable with URLs
        self::assertNotNull($nodes[0]->url, 'Parent module should have URL');
        self::assertNotNull($nodes[1]->url, 'Current module should have URL');

        // Last node should be the current page
        $lastNode = $nodes[count($nodes) - 1];
        self::assertSame('1', $lastNode->identifier, 'Last node should be the current page');
    }

    #[Test]
    public function breadcrumbIncludesModuleHierarchyForThirdLevelModuleWithNullContext(): void
    {
        // Test with null context (uses NullContextBreadcrumbProvider)
        $request = $this->createMockRequest('web_info_translations');

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, null);

        // Should have: content_status (parent) + web_info_translations (current) + virtual root
        self::assertGreaterThanOrEqual(3, count($nodes), 'Should have parent module, current module, and virtual root');

        // First node should be the parent module (content_status)
        self::assertSame('content_status', $nodes[0]->identifier, 'First node should be parent module');

        // Second node should be the current third-level module
        self::assertSame('web_info_translations', $nodes[1]->identifier, 'Second node should be current third-level module');

        // Both module nodes should have forceShowIcon enabled
        self::assertTrue($nodes[0]->forceShowIcon, 'Parent module should force show icon');
        self::assertTrue($nodes[1]->forceShowIcon, 'Current module should force show icon');
    }

    #[Test]
    public function breadcrumbOnlyShowsSecondLevelModuleForNonThirdLevelModule(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        // content_status is a second-level module (parent: content, which should be skipped)
        $request = $this->createMockRequest('content_status');

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        // Should have: content_status (current module) + page
        // Should NOT have: content (top-level parent is skipped)
        self::assertSame('content_status', $nodes[0]->identifier, 'First node should be the second-level module');
        self::assertNotSame('content', $nodes[0]->identifier, 'Should not show top-level parent module');
    }

    #[Test]
    public function breadcrumbUsesFullRouteIdentifierForCurrentModuleInHierarchy(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        // 'pagetsconfig_includes' is navigated via the page tree and has a sub-route 'source'.
        // The request carries the page id only, so that sub-route can be entered for any other
        // page as well and is preserved.
        $request = $this->createMockRequestWithSubRoute('pagetsconfig_includes', 'pagetsconfig_includes.source');

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        // Should have: pagetsconfig (parent) + pagetsconfig_includes (current) + page
        self::assertGreaterThanOrEqual(3, count($nodes), 'Should have parent module, current module, and page');

        // First node should be the parent module - uses base module identifier
        self::assertSame('pagetsconfig', $nodes[0]->identifier, 'First node should be parent module');
        self::assertNotNull($nodes[0]->url, 'Parent module should have URL');
        self::assertStringNotContainsString('/includes/source', $nodes[0]->url, 'Parent module must not use the sub-route');

        // Second node should be the current module, keeping the active sub-route
        self::assertSame('pagetsconfig_includes', $nodes[1]->identifier, 'Second node should be current module');
        self::assertNotNull($nodes[1]->url, 'Current module should have URL');
        self::assertStringContainsString('/module/pagetsconfig/includes/source', $nodes[1]->url, 'Current module should keep the sub-route');
    }

    #[Test]
    public function breadcrumbExtractsRouteIdentifierFromRoutingAttribute(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        // Two requests for the same module: one whose routing attribute carries the sub-route,
        // one without a routing attribute at all, which falls back to the module identifier.
        $requestWithRoutingAttr = $this->createMockRequestWithSubRoute('pagetsconfig_includes', 'pagetsconfig_includes.source');
        $requestWithoutRoutingAttr = $this->createMockRequest('pagetsconfig_includes');

        $breadcrumb = $this->get(Breadcrumb::class);

        $nodesWithRoutingAttr = $breadcrumb->getBreadcrumb($requestWithRoutingAttr, $context);
        $nodesWithoutRoutingAttr = $breadcrumb->getBreadcrumb($requestWithoutRoutingAttr, $context);

        self::assertSame('pagetsconfig_includes', $nodesWithRoutingAttr[1]->identifier);
        self::assertSame('pagetsconfig_includes', $nodesWithoutRoutingAttr[1]->identifier);

        self::assertStringContainsString('/module/pagetsconfig/includes/source', $nodesWithRoutingAttr[1]->url, 'Should use route from routing attribute');
        self::assertStringNotContainsString('/includes/source', $nodesWithoutRoutingAttr[1]->url, 'Should use module identifier as fallback');
    }

    #[Test]
    public function breadcrumbUsesModuleIdentifierWhenCurrentRouteNeedsFurtherParameters(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        // The 'source' sub-route needs 'includeType' and 'identifier' besides the page id. The
        // breadcrumb only supplies the id, so the sub-route can not be rendered for another page.
        $request = $this->createMockRequestWithSubRoute(
            'pagetsconfig_includes',
            'pagetsconfig_includes.source',
            ['id' => 1, 'includeType' => 'setup', 'identifier' => 'someInclude']
        );

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        self::assertSame('pagetsconfig_includes', $nodes[1]->identifier);
        self::assertNotNull($nodes[1]->url);
        self::assertStringContainsString('/module/pagetsconfig/includes', $nodes[1]->url);
        self::assertStringNotContainsString('/includes/source', $nodes[1]->url, 'Sub-route requiring further parameters must not be reused');

        foreach ($nodes as $node) {
            if ($node->url !== null) {
                self::assertStringNotContainsString('/includes/source', $node->url, 'No node may link to the unrestorable sub-route');
            }
        }
    }

    #[Test]
    public function breadcrumbUsesModuleIdentifierForModuleWithoutPageTreeNavigation(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        // 'site_configuration' is not navigated via the page tree, so the breadcrumb supplies no
        // parameter at all and the 'edit' route, which needs a site identifier, is dropped.
        $request = $this->createMockRequestWithSubRoute(
            'site_configuration',
            'site_configuration.edit',
            ['site' => 'main']
        );

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        self::assertSame('site_configuration', $nodes[0]->identifier);
        self::assertNotNull($nodes[0]->url);
        self::assertStringContainsString('/module/site/configuration', $nodes[0]->url);
        self::assertStringNotContainsString('/configuration/edit', $nodes[0]->url, 'Edit route must not be reused');
    }

    #[Test]
    public function breadcrumbUsesModuleIdentifierWhenCurrentRouteBelongsToNoModule(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        // Editing a record from within a module: the current route is the generic 'record_edit'
        // route, which belongs to no module and can not be entered without its 'edit' parameters.
        // The module itself is only known from the 'module' query parameter.
        $symfonyRoute = $this->get(Router::class)->getRoute('record_edit');
        self::assertNotNull($symfonyRoute);
        $route = Route::fromSymfonyRoute($symfonyRoute, 'record_edit');
        self::assertNull($route->getOption('module'), 'record_edit is expected to carry no module');

        $request = $this->createMockRequestForRoute($route, null, [
            'edit' => ['be_users' => [1 => 'edit']],
            'module' => 'site_configuration',
        ]);

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        self::assertSame('site_configuration', $nodes[0]->identifier);
        self::assertNotNull($nodes[0]->url);
        self::assertStringContainsString('/module/site/configuration', $nodes[0]->url);
        self::assertStringNotContainsString('/record/edit', $nodes[0]->url, 'Breadcrumb must not link back to the record edit route');
    }

    private function createMockRequest(string $moduleIdentifier): ServerRequestInterface
    {
        $moduleProvider = $this->get(ModuleProvider::class);
        $module = $moduleProvider->getModule($moduleIdentifier, $GLOBALS['BE_USER']);

        $request = new ServerRequest('https://example.com/typo3/module/' . $moduleIdentifier)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/' . $moduleIdentifier, [
                '_identifier' => $moduleIdentifier,
            ]))
            ->withAttribute('module', $module);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    /**
     * Builds a request as the backend routing produces it for a (sub-)route of a module: the
     * matched route carries the module it belongs to in its "module" option, and the route
     * identifier in "_identifier".
     *
     * @param array<string, mixed> $queryParams
     */
    private function createMockRequestWithSubRoute(string $moduleIdentifier, string $routeIdentifier, array $queryParams = ['id' => 1]): ServerRequestInterface
    {
        $moduleProvider = $this->get(ModuleProvider::class);
        $module = $moduleProvider->getModule($moduleIdentifier, $GLOBALS['BE_USER']);

        $route = new Route('/module/' . $moduleIdentifier, [
            '_identifier' => $routeIdentifier,
            'module' => $module,
        ]);

        return $this->createMockRequestForRoute($route, $module, $queryParams);
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    private function createMockRequestForRoute(Route $route, ?ModuleInterface $module, array $queryParams): ServerRequestInterface
    {
        $request = new ServerRequest('https://example.com/typo3' . $route->getPath())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $route)
            ->withAttribute('routing', new RouteResult($route))
            ->withAttribute('module', $module)
            ->withQueryParams($queryParams);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }
}
