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

            // Route is optional but if present must have module and params
            if ($node->route !== null) {
                self::assertNotEmpty($node->route->module);
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
            'route' => null,
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

        // Module should have route to root
        self::assertNotNull($nodes[0]->route);
        self::assertSame('web_layout', $nodes[0]->route->module);
        self::assertSame(['id' => '0'], $nodes[0]->route->params);

        // Last node should be the current page (has route since it's a page record)
        $lastNode = $nodes[count($nodes) - 1];
        self::assertSame('1', $lastNode->identifier);
        self::assertNotNull($lastNode->route, 'Page records have routes');
        self::assertSame('web_layout', $lastNode->route->module);
        self::assertSame(['id' => '1'], $lastNode->route->params);
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

        // Should use web_layout as fallback module in routes
        if ($nodes[0]->route !== null) {
            self::assertSame('web_layout', $nodes[0]->route->module);
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
        self::assertNotNull($nodes[0]->route);
        self::assertSame('web_info', $nodes[0]->route->module, 'Module node should use web_info');

        $routeCount = 0;
        foreach ($nodes as $node) {
            if ($node->route !== null) {
                self::assertSame('web_info', $node->route->module, 'All routes should use detected module web_info');
                $routeCount++;
            }
        }
        self::assertGreaterThan(0, $routeCount, 'Should have at least one route');

        $jsonNodes = json_decode(json_encode($nodes), true);
        self::assertIsArray($jsonNodes);

        foreach ($jsonNodes as $jsonNode) {
            self::assertArrayHasKey('identifier', $jsonNode);
            self::assertArrayHasKey('label', $jsonNode);
            self::assertArrayHasKey('icon', $jsonNode);
            self::assertArrayHasKey('iconOverlay', $jsonNode);
            self::assertArrayHasKey('route', $jsonNode);
            self::assertArrayHasKey('forceShowIcon', $jsonNode);

            if ($jsonNode['route'] !== null) {
                self::assertArrayHasKey('module', $jsonNode['route']);
                self::assertArrayHasKey('params', $jsonNode['route']);
            }
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
            'route' => null,
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
        self::assertNotNull($nodes[0]->route);
        self::assertSame('web_layout', $nodes[0]->route->module);
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
}
