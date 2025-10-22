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
use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;
use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNodeRoute;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleResolver;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Breadcrumb provider for null contexts (virtual pages, empty states).
 *
 * Provides fallback breadcrumbs when no record or resource context is available,
 * such as virtual pages (e.g., id=0) or file storage roots.
 *
 * @internal This class is not part of TYPO3's public API.
 */
final class NullContextBreadcrumbProvider implements BreadcrumbProviderInterface
{
    public function __construct(
        private readonly ModuleResolver $moduleResolver,
        private readonly StorageRepository $storageRepository,
    ) {}

    public function supports(?BreadcrumbContext $context): bool
    {
        // This provider handles null contexts
        return $context === null || !$context->hasContext();
    }

    public function generate(?BreadcrumbContext $context, ?ServerRequestInterface $request): array
    {
        $breadcrumb = [];

        $currentModule = $this->moduleResolver->resolveModule($request);
        if ($currentModule !== null) {
            // Add parent modules first (for third-level modules)
            $breadcrumb = $this->buildModuleHierarchy($currentModule);
        }

        // Handle file storage tree
        if ($currentModule?->getNavigationComponent() === '@typo3/backend/tree/file-storage-tree-container') {
            $id = $request?->getQueryParams()['id'] ?? null;
            $label = $this->getLanguageService()->sL($currentModule->getTitle());
            $icon = 'apps-filetree-folder';

            if ($id !== null && $storage = $this->storageRepository->findByCombinedIdentifier($id)) {
                $label = $storage->getName();
                if (!$storage->isOnline() || !$storage->isBrowsable()) {
                    $icon = 'apps-filetree-folder-locked';
                }
            }

            $breadcrumb[] = new BreadcrumbNode(
                identifier: (string)$id,
                label: $label,
                icon: $icon,
            );
        }

        // Handle page tree (default for null context or no module)
        if ($currentModule === null || $currentModule->getNavigationComponent() === '@typo3/backend/tree/page-tree-element') {
            $breadcrumb[] = new BreadcrumbNode(
                identifier: '0',
                label: (string)$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                icon: 'apps-pagetree-root',
            );
        }

        return $breadcrumb;
    }

    public function getPriority(): int
    {
        // Low priority - only handles null contexts as fallback
        return 0;
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
    private function buildModuleHierarchy(ModuleInterface $currentModule): array
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
                    params: $module->getNavigationComponent() === '@typo3/backend/tree/page-tree-element' ? ['id' => '0'] : [],
                ),
                forceShowIcon: true,
            );
        }

        return $modules;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
