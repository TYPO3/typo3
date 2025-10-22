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

namespace TYPO3\CMS\Backend\Breadcrumb;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;
use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNodeRoute;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleResolver;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Breadcrumb provider for TYPO3 records (pages, content elements, etc.).
 *
 * Generates breadcrumb trails based on page rootlines and record hierarchies.
 *
 * @internal This class is not part of TYPO3's public API.
 */
final class RecordBreadcrumbProvider implements BreadcrumbProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly ModuleResolver $moduleResolver,
    ) {}

    public function supports(?BreadcrumbContext $context): bool
    {
        return $context?->mainContext instanceof RecordInterface;
    }

    public function generate(?BreadcrumbContext $context, ?ServerRequestInterface $request): array
    {
        if ($context === null || !$context->mainContext instanceof RecordInterface) {
            return [];
        }

        $record = $context->mainContext;
        $breadcrumb = [];
        $currentModule = $this->moduleResolver->resolveModule($request);
        $showRootline = $this->shouldShowRootline($currentModule);
        $targetModule = $currentModule?->getIdentifier() ?? $this->getTargetModule();

        // Add module hierarchy (for third-level modules, this includes parent modules)
        if ($currentModule !== null) {
            $breadcrumb = array_merge($breadcrumb, $this->buildModuleHierarchy($currentModule, $showRootline));
        } else {
            $breadcrumb[] = new BreadcrumbNode(
                identifier: '0',
                label: (string)$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                icon: 'apps-pagetree-root',
                route: new BreadcrumbNodeRoute(
                    module: $targetModule,
                    params: $showRootline ? ['id' => '0'] : [],
                ),
            );
        }

        // Add page rootline if applicable
        if ($showRootline) {
            $breadcrumb = array_merge($breadcrumb, $this->buildRootline($record, $targetModule));
        }

        // Add the current record
        $breadcrumb[] = $this->buildRecordNode($record, $targetModule);

        return $breadcrumb;
    }

    public function getPriority(): int
    {
        return 10;
    }

    /**
     * Returns the target module identifier for navigation.
     */
    private function getTargetModule(): string
    {
        // Default to web_layout for page-based navigation
        return 'web_layout';
    }

    /**
     * Builds the page rootline for a record.
     *
     * @return BreadcrumbNode[]
     */
    private function buildRootline(RecordInterface $record, string $targetModule): array
    {
        $breadcrumb = [];
        $pid = $record->getPid();

        try {
            $rootline = BackendUtility::BEgetRootLine($pid);
            if ($rootline === []) {
                return [];
            }

            // Remove the site root (already added as first node)
            array_pop($rootline);
            ksort($rootline);

            foreach ($rootline as $item) {
                if (!is_array($item) || !isset($item['uid'])) {
                    continue;
                }

                try {
                    $icon = $this->iconFactory->getIconForRecord('pages', $item, IconSize::SMALL);
                    $breadcrumb[] = new BreadcrumbNode(
                        identifier: (string)$item['uid'],
                        label: $item['title'] ?? '',
                        icon: $icon->getIdentifier(),
                        iconOverlay: $icon->getOverlayIcon()?->getIdentifier(),
                        route: new BreadcrumbNodeRoute(
                            module: $targetModule,
                            params: ['id' => $item['uid']],
                        ),
                    );
                } catch (\Exception $e) {
                    $this->logger?->warning(
                        'Failed to create breadcrumb node for page',
                        ['uid' => $item['uid'], 'exception' => $e->getMessage()]
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger?->warning(
                'Failed to build rootline for record',
                ['table' => $record->getMainType(), 'uid' => $record->getUid(), 'exception' => $e->getMessage()]
            );
        }

        return $breadcrumb;
    }

    /**
     * Builds a breadcrumb node for the current record.
     */
    private function buildRecordNode(RecordInterface $record, string $targetModule): BreadcrumbNode
    {
        try {
            $icon = $this->iconFactory->getIconForRecord(
                $record->getMainType(),
                $record->getRawRecord()?->toArray(),
                IconSize::SMALL
            );

            return new BreadcrumbNode(
                identifier: (string)$record->getUid(),
                label: BackendUtility::getRecordTitle(
                    $record->getMainType(),
                    $record->getRawRecord()?->toArray(),
                ),
                icon: $icon->getIdentifier(),
                iconOverlay: $icon->getOverlayIcon()?->getIdentifier(),
                route: $record->getMainType() !== 'pages' ? null : new BreadcrumbNodeRoute(
                    module: $targetModule,
                    params: ['id' => (string)$record->getUid()],
                ),
            );
        } catch (\Exception $e) {
            $this->logger?->error(
                'Failed to create breadcrumb node for record',
                ['table' => $record->getMainType(), 'uid' => $record->getUid(), 'exception' => $e->getMessage()]
            );

            // Return a minimal fallback node
            return new BreadcrumbNode(
                identifier: (string)$record->getUid(),
                label: $record->getMainType() . ' [' . $record->getUid() . ']',
            );
        }
    }

    /**
     * Builds the module hierarchy including parent modules.
     *
     * For third-level modules, this returns [parent, current].
     * For second-level modules, this returns [current].
     * For standalone modules, this returns [current].
     *
     * @return BreadcrumbNode[]
     */
    private function buildModuleHierarchy(ModuleInterface $currentModule, bool $showRootline): array
    {
        $modules = [];
        $moduleChain = [];

        // Build chain from current to root
        $module = $currentModule;
        while ($module !== null) {
            $moduleChain[] = $module;
            $module = $module->getParentModule();
        }

        // Reverse to get root-to-current order and skip the top-level parent (main menu item)
        $moduleChain = array_reverse($moduleChain);

        // Skip the first item if we have more than one (first is the main menu container like "web")
        if (count($moduleChain) > 1) {
            array_shift($moduleChain);
        }

        // Build breadcrumb nodes for each module in the chain
        foreach ($moduleChain as $module) {
            $modules[] = new BreadcrumbNode(
                identifier: $module->getIdentifier(),
                label: $this->getLanguageService()->sL($module->getTitle()),
                icon: $module->getIconIdentifier(),
                route: new BreadcrumbNodeRoute(
                    module: $module->getIdentifier(),
                    params: $showRootline ? ['id' => '0'] : [],
                ),
                forceShowIcon: true,
            );
        }

        return $modules;
    }

    /**
     * Determines if the rootline should be shown based on the current module.
     *
     * Modules using the page tree navigation component typically support page-based navigation.
     */
    private function shouldShowRootline(?ModuleInterface $currentModule): bool
    {
        // @todo This is quite implicit, but using the page-tree-element as navigation component
        //       signals that the current module can handle ?id= as a page parameter.
        return $currentModule === null || $currentModule->getNavigationComponent() === '@typo3/backend/tree/page-tree-element';
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
