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

namespace TYPO3\CMS\Backend\Controller\FileStorage;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Dto\Tree\FileTreeItem;
use TYPO3\CMS\Backend\Dto\Tree\TreeItem;
use TYPO3\CMS\Backend\Tree\FileStorageTreeProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller providing data to the file storage tree.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class TreeController
{
    protected IconFactory $iconFactory;
    protected FileStorageTreeProvider $treeProvider;
    protected ResourceFactory $resourceFactory;

    public function __construct(?IconFactory $iconFactory = null)
    {
        $this->iconFactory = $iconFactory ?? GeneralUtility::makeInstance(IconFactory::class);
        $this->treeProvider = GeneralUtility::makeInstance(FileStorageTreeProvider::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
    }

    /**
     * Loads data for the first time, or when expanding a folder.
     */
    public function fetchDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $parentIdentifier = $request->getQueryParams()['parent'] ?? null;
        if ($parentIdentifier) {
            $currentDepth = (int)($request->getQueryParams()['depth'] ?? 1);
            $parentIdentifier = rawurldecode($parentIdentifier);
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($parentIdentifier);
            $items = $this->treeProvider->getSubfoldersRecursively($folder, $currentDepth + 1);
        } else {
            $items = $this->treeProvider->getRootNodes($this->getBackendUser());
        }
        $items = array_map(function (array $item): FileTreeItem {
            return $this->prepareItemForOutput($item);
        }, $items);
        return new JsonResponse($items);
    }

    /**
     * Used when the search / filter is used.
     *
     * @throws \Exception
     */
    public function filterDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $search = $request->getQueryParams()['q'] ?? '';
        $foundFolders = $this->treeProvider->getFilteredTree($this->getBackendUser(), $search);

        $items = [];
        foreach ($foundFolders as $folder) {
            if (!$folder instanceof Folder) {
                continue;
            }
            $storage = $folder->getStorage();
            $itemsInRootLine = [];

            // Go back the root folder structure until the root folder
            $nextFolder = $folder;
            $isParent = false;
            do {
                $itemsInRootLine[$nextFolder->getCombinedIdentifier()] = array_merge(
                    $this->treeProvider->prepareFolderInformation($nextFolder),
                    [
                        'expanded' => $isParent,
                    ]
                );
                $isParent = true;
                try {
                    $nextFolder = $nextFolder->getParentFolder();
                } catch (InsufficientFolderAccessPermissionsException) {
                    $nextFolder = null;
                }
            } while ($nextFolder instanceof Folder && $nextFolder->getIdentifier() !== '/');
            // Add the storage / sys_filemount itself
            $storageData = $this->treeProvider->prepareFolderInformation(
                $storage->getRootLevelFolder(true),
                $storage->getName()
            );
            $storageData = array_merge($storageData, [
                'depth' => 0,
                'expanded' => true,
            ]);
            $itemsInRootLine[$storage->getUid() . ':/'] = $storageData;

            $itemsInRootLine = array_reverse($itemsInRootLine);
            $depth = 0;
            foreach ($itemsInRootLine as $k => $itm) {
                $itm['depth'] = $depth++;
                $items[$k] = $itm;
            }
        }

        ksort($items);
        $finalItems = [];
        $items = array_values($items);
        foreach ($items as $item) {
            $finalItems[] = $this->prepareItemForOutput($item);
        }
        return new JsonResponse($finalItems);
    }

    /**
     * Adds information for the JSON result to be rendered.
     */
    protected function prepareItemForOutput(array $item): FileTreeItem
    {
        $folder = $item['resource'];
        $isStorage = $item['recordType'] !== 'sys_file';
        if ($isStorage && !$folder->getStorage()->isOnline()) {
            $item['name'] .= ' (' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_file.xlf:sys_file_storage.isOffline') . ')';
        }
        $icon = $this->iconFactory->getIconForResource($folder, IconSize::SMALL, null, $isStorage ? ['mount-root' => true] : []);
        $item['icon'] = $icon->getIdentifier();
        $item['overlayIcon'] = $icon->getOverlayIcon() ? $icon->getOverlayIcon()->getIdentifier() : '';

        $treeItem = new FileTreeItem(
            item: new TreeItem(
                identifier: $item['identifier'],
                parentIdentifier: (string)($item['parentIdentifier'] ?? ''),
                recordType: (string)($item['recordType'] ?? ''),
                name: (string)($item['name'] ?? ''),
                prefix: (string)($item['prefix'] ?? ''),
                suffix: (string)($item['suffix'] ?? ''),
                tooltip: (string)($item['tooltip'] ?? ''),
                depth: (int)($item['depth'] ?? 0),
                hasChildren: (bool)($item['hasChildren'] ?? false),
                loaded: (bool)($item['loaded'] ?? false),
                icon: $item['icon'],
                overlayIcon: $item['overlayIcon'],
                statusInformation: (array)($item['statusInformation'] ?? []),
                labels: (array)($item['labels'] ?? []),
            ),
            pathIdentifier: (string)($item['pathIdentifier'] ?? ''),
            storage: (int)($item['storage'] ?? 0),
            resourceType: $isStorage ? 'storage' : 'folder',
        );

        return $treeItem;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
