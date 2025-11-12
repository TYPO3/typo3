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
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Template\Components\Breadcrumb;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Routing\Route;
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
        self::assertSame('Page', $nodes[0]->label);
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

        $request = $this->createMockRequest('web_info');

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        self::assertGreaterThanOrEqual(2, count($nodes));

        self::assertSame('web_info', $nodes[0]->identifier);
        self::assertNotNull($nodes[0]->url);
        self::assertStringContainsString('/module/web/info', $nodes[0]->url, 'Module node should use web_info');

        $urlCount = 0;
        foreach ($nodes as $node) {
            if ($node->url !== null) {
                self::assertStringContainsString('/module/web/info', $node->url, 'All URLs should use detected module web_info');
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

        $request = (new ServerRequest('https://example.com/typo3/'))
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

        // web_info_overview is a third-level module (parent: web_info, grandparent: content)
        $request = $this->createMockRequest('web_info_overview');

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        // Should have: web_info (parent) + web_info_overview (current) + page
        self::assertGreaterThanOrEqual(3, count($nodes), 'Should have parent module, current module, and page');

        // First node should be the parent module (web_info)
        self::assertSame('web_info', $nodes[0]->identifier, 'First node should be parent module');

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

        // Should have: web_info (parent) + web_info_translations (current) + virtual root
        self::assertGreaterThanOrEqual(3, count($nodes), 'Should have parent module, current module, and virtual root');

        // First node should be the parent module (web_info)
        self::assertSame('web_info', $nodes[0]->identifier, 'First node should be parent module');

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

        // web_info is a second-level module (parent: content, which should be skipped)
        $request = $this->createMockRequest('web_info');

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        // Should have: web_info (current module) + page
        // Should NOT have: content (top-level parent is skipped)
        self::assertSame('web_info', $nodes[0]->identifier, 'First node should be the second-level module');
        self::assertNotSame('content', $nodes[0]->identifier, 'Should not show top-level parent module');
    }

    #[Test]
    public function breadcrumbUsesFullRouteIdentifierForCurrentModuleInHierarchy(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        // Create a request with a custom route identifier (simulating a sub-route scenario)
        // In real usage, this would be something like 'manage_search_index.Administration_externalDocuments'
        // For testing, we verify that the routing attribute is properly consulted
        $customRouteIdentifier = 'web_info_overview';
        $request = $this->createMockRequestWithSubRoute('web_info_overview', $customRouteIdentifier);

        $breadcrumb = $this->get(Breadcrumb::class);
        $nodes = $breadcrumb->getBreadcrumb($request, $context);

        // Should have: web_info (parent) + web_info_overview (current) + page
        self::assertGreaterThanOrEqual(3, count($nodes), 'Should have parent module, current module, and page');

        // First node should be the parent module (web_info) - uses base module identifier
        self::assertSame('web_info', $nodes[0]->identifier, 'First node should be parent module');
        self::assertNotNull($nodes[0]->url, 'Parent module should have URL');
        self::assertStringContainsString('/module/web/info', $nodes[0]->url);

        // Second node should be the current module with its URL
        self::assertSame('web_info_overview', $nodes[1]->identifier, 'Second node should be current module');
        self::assertNotNull($nodes[1]->url, 'Current module should have URL');
        self::assertStringContainsString('/module/web/info/overview', $nodes[1]->url);
    }

    #[Test]
    public function breadcrumbExtractsRouteIdentifierFromRoutingAttribute(): void
    {
        $recordFactory = $this->get(RecordFactory::class);
        $pageRecord = $recordFactory->createResolvedRecordFromDatabaseRow('pages', BackendUtility::getRecord('pages', 1));

        $context = new BreadcrumbContext($pageRecord);

        // Create two requests for comparison:
        // 1. With routing attribute containing different route identifier (simulates sub-route like 'web_info.action')
        // 2. Without routing attribute (falls back to module identifier)
        //
        // We use web_info_overview and web_info to demonstrate:
        // - When routing is set to 'web_info_overview', it should be used
        // - When no routing attribute, it falls back to the module's base identifier
        $moduleWithSubRoute = 'web_info_overview';
        $fallbackModule = 'web_info';

        $requestWithRoutingAttr = $this->createMockRequestWithSubRoute($moduleWithSubRoute, $moduleWithSubRoute);
        $requestWithoutRoutingAttr = $this->createMockRequest($fallbackModule);

        $breadcrumb = $this->get(Breadcrumb::class);

        $nodesWithRoutingAttr = $breadcrumb->getBreadcrumb($requestWithRoutingAttr, $context);
        $nodesWithoutRoutingAttr = $breadcrumb->getBreadcrumb($requestWithoutRoutingAttr, $context);

        self::assertGreaterThanOrEqual(2, count($nodesWithRoutingAttr), 'Should have module and page nodes with routing attribute');
        self::assertGreaterThanOrEqual(2, count($nodesWithoutRoutingAttr), 'Should have module and page nodes without routing attribute');

        self::assertSame('web_info_overview', $nodesWithRoutingAttr[1]->identifier, 'Second node should be current module');
        self::assertNotNull($nodesWithRoutingAttr[1]->url, 'Current module with routing attribute should have URL');

        self::assertSame('web_info', $nodesWithoutRoutingAttr[0]->identifier, 'First node should be current module');
        self::assertNotNull($nodesWithoutRoutingAttr[0]->url, 'Module without routing attribute should have URL');

        self::assertStringContainsString('/module/web/info/overview', $nodesWithRoutingAttr[1]->url, 'Should use route from routing attribute');
        self::assertStringContainsString('/module/web/info', $nodesWithoutRoutingAttr[0]->url, 'Should use module identifier as fallback');
        self::assertNotSame($nodesWithRoutingAttr[1]->url, $nodesWithoutRoutingAttr[0]->url, 'URLs should differ based on route identifier used');
    }

    private function createMockRequest(string $moduleIdentifier): ServerRequestInterface
    {
        $moduleProvider = $this->get(ModuleProvider::class);
        $module = $moduleProvider->getModule($moduleIdentifier, $GLOBALS['BE_USER']);

        $request = (new ServerRequest('https://example.com/typo3/module/' . $moduleIdentifier))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/' . $moduleIdentifier, [
                '_identifier' => $moduleIdentifier,
            ]))
            ->withAttribute('module', $module);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    private function createMockRequestWithSubRoute(string $moduleIdentifier, string $routeIdentifier): ServerRequestInterface
    {
        $moduleProvider = $this->get(ModuleProvider::class);
        $module = $moduleProvider->getModule($moduleIdentifier, $GLOBALS['BE_USER']);

        $route = new Route('/module/' . $moduleIdentifier, ['_identifier' => $routeIdentifier]);

        $request = (new ServerRequest('https://example.com/typo3/module/' . $moduleIdentifier))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $route)
            ->withAttribute('routing', new class ($route) {
                private Route $route;
                public function __construct(Route $route)
                {
                    $this->route = $route;
                }
                public function getRoute(): Route
                {
                    return $this->route;
                }
            })
            ->withAttribute('module', $module);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }
}
