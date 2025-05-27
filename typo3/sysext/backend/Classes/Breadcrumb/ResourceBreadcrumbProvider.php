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
use TYPO3\CMS\Backend\Module\ModuleResolver;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * Breadcrumb provider for FAL resources (files and folders).
 *
 * Generates breadcrumb trails based on folder hierarchies and storage structures.
 *
 * @internal This class is not part of TYPO3's public API.
 */
final class ResourceBreadcrumbProvider implements BreadcrumbProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly ModuleResolver $moduleResolver,
    ) {}

    public function supports(?BreadcrumbContext $context): bool
    {
        return $context?->mainContext instanceof ResourceInterface;
    }

    public function generate(?BreadcrumbContext $context, ?ServerRequestInterface $request): array
    {
        if ($context === null || !$context->mainContext instanceof ResourceInterface) {
            return [];
        }

        $resource = $context->mainContext;
        $breadcrumb = [];
        $currentModule = $this->moduleResolver->resolveModule($request);

        // Add module node
        if ($currentModule !== null) {
            $languageService = $this->getLanguageService();
            $breadcrumb[] = new BreadcrumbNode(
                identifier: $currentModule->getIdentifier(),
                label: $languageService->sL($currentModule->getTitle()),
                icon: $currentModule->getIconIdentifier(),
                iconOverlay: null,
                route: new BreadcrumbNodeRoute(
                    module: $currentModule->getIdentifier(),
                    params: ['id' => ''],
                ),
                forceShowIcon: true,
            );
        }

        // Build resource hierarchy
        $resourceHierarchy = $this->buildResourceHierarchy($resource);

        // Add resource nodes
        foreach ($resourceHierarchy as $item) {
            try {
                $icon = $this->iconFactory->getIconForResource($item, IconSize::SMALL);
                $label = $item->getName();
                $combinedIdentifier = $this->getCombinedIdentifier($item);

                // Use storage name for root folder
                if ($item->getIdentifier() === $item->getStorage()->getRootLevelFolder()->getIdentifier()) {
                    $label = $item->getStorage()->getName();
                }

                $breadcrumb[] = new BreadcrumbNode(
                    identifier: $combinedIdentifier,
                    label: $label,
                    icon: $icon->getIdentifier(),
                    iconOverlay: $icon->getOverlayIcon()?->getIdentifier(),
                    route: new BreadcrumbNodeRoute(
                        module: $this->getTargetModule(),
                        params: ['id' => $combinedIdentifier],
                    ),
                );
            } catch (\Exception $e) {
                $this->logger?->warning(
                    'Failed to create breadcrumb node for resource',
                    ['identifier' => $item->getIdentifier(), 'exception' => $e->getMessage()]
                );
            }
        }

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
        return 'media_management';
    }

    /**
     * Builds the resource hierarchy from root to current resource.
     *
     * @return ResourceInterface[]
     */
    private function buildResourceHierarchy(ResourceInterface $resource): array
    {
        $hierarchy = [];
        $folder = null;

        // Start with the resource itself
        if ($resource instanceof FileInterface) {
            $hierarchy[] = $resource;
            try {
                $folder = $resource->getParentFolder();
            } catch (\Exception $e) {
                $this->logger?->warning(
                    'Failed to get parent folder for file',
                    ['identifier' => $resource->getIdentifier(), 'exception' => $e->getMessage()]
                );
                return $hierarchy;
            }
        } elseif ($resource instanceof FolderInterface) {
            $folder = $resource;
        }

        // Traverse up the folder hierarchy
        if ($folder instanceof Folder) {
            $currentFolder = $folder;
            $hierarchy[] = $folder;

            // Walk up to the root folder
            $maxDepth = 100; // Safety limit to prevent infinite loops
            $depth = 0;

            while ($depth < $maxDepth) {
                $depth++;

                try {
                    $parent = $currentFolder->getParentFolder();
                } catch (InsufficientFolderAccessPermissionsException $e) {
                    // User doesn't have access to parent folder, stop here
                    $this->logger?->info(
                        'Stopped breadcrumb traversal due to insufficient folder access',
                        ['folder' => $currentFolder->getCombinedIdentifier()]
                    );
                    break;
                }

                // Check if we've reached the root (parent points to itself)
                if ($parent->getCombinedIdentifier() === $currentFolder->getCombinedIdentifier()) {
                    break;
                }

                // Add parent to hierarchy and continue upwards
                $hierarchy[] = $parent;
                $currentFolder = $parent;
            }
        }

        // Reverse to get root-to-current order
        return array_reverse($hierarchy);
    }

    /**
     * Gets the combined identifier for a resource.
     * Constructs it from storage UID and resource identifier.
     */
    private function getCombinedIdentifier(ResourceInterface $resource): string
    {
        return $resource->getStorage()->getUid() . ':' . $resource->getIdentifier();
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
