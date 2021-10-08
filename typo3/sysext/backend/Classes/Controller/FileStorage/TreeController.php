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
use TYPO3\CMS\Backend\Tree\FileStorageTreeProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
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

    public function __construct(IconFactory $iconFactory = null)
    {
        $this->iconFactory = $iconFactory ?? GeneralUtility::makeInstance(IconFactory::class);
        $this->treeProvider = GeneralUtility::makeInstance(FileStorageTreeProvider::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
    }

    /**
     * Loads data for the first time, or when expanding a folder.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function fetchDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $parentIdentifier = $request->getQueryParams()['parent'] ?? null;
        if ($parentIdentifier) {
            $currentDepth = (int)($request->getQueryParams()['currentDepth'] ?? 1);
            $parentIdentifier = rawurldecode($parentIdentifier);
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($parentIdentifier);
            $items = $this->treeProvider->getSubfoldersRecursively($folder, $currentDepth + 1);
        } else {
            $items = $this->treeProvider->getRootNodes($this->getBackendUser());
        }
        $items = array_map(function (array $item) {
            return $this->prepareItemForOutput($item);
        }, $items);
        return new JsonResponse($items);
    }

    /**
     * Used when the search / filter is used.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function filterDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $search = $request->getQueryParams()['q'] ?? '';
        $foundFolders = $this->treeProvider->getFilteredTree($this->getBackendUser(), $search);

        $items = [];
        foreach ($foundFolders as $folder) {
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
                $nextFolder = $nextFolder->getParentFolder();
            } while ($nextFolder instanceof Folder && $nextFolder->getIdentifier() !== '/');
            // Add the storage / sys_filemount itself
            $storageData = $this->treeProvider->prepareFolderInformation(
                $storage->getRootLevelFolder(true),
                $storage->getName()
            );
            $storageData = array_merge($storageData, [
                'depth' => 0,
                'expanded' => true,
                'siblingsCount' => 1,
                'siblingsPosition' => 1,
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
        // Make sure siblingsCount and siblingsPosition works
        $finalItems = [];
        $items = array_values($items);
        foreach ($items as $item) {
            $stateIdentifier = $item['stateIdentifier'];
            $parentIdentifier = $item['parentIdentifier'];
            $siblings = array_filter($items, static function ($itemInArray) use ($parentIdentifier) {
                if ($itemInArray['parentIdentifier'] === $parentIdentifier) {
                    return true;
                }
                return false;
            });
            $positionFound = false;
            $siblingsBeforeInSameDepth = array_filter($siblings, static function ($itemInArray) use ($stateIdentifier, &$positionFound): bool {
                if ($itemInArray['stateIdentifier'] === $stateIdentifier) {
                    $positionFound = true;
                    return false;
                }
                return !$positionFound;
            });
            $item['siblingsCount'] = count($siblings);
            $item['siblingsPosition'] = count($siblingsBeforeInSameDepth) + 1;
            $finalItems[] = $this->prepareItemForOutput($item);
        }
        // now lets do the siblingsCount
        return new JsonResponse($finalItems);
    }

    /**
     * Adds information for the JSON result to be rendered.
     *
     * @param array $item
     * @return array
     */
    protected function prepareItemForOutput(array $item): array
    {
        $folder = $item['resource'];
        $isStorage = $item['itemType'] !== 'sys_file';
        if ($isStorage && !$folder->getStorage()->isOnline()) {
            $item['name'] .= ' (' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_file.xlf:sys_file_storage.isOffline') . ')';
        }
        $icon = $this->iconFactory->getIconForResource($folder, Icon::SIZE_SMALL, null, $isStorage ? ['mount-root' => true] : []);
        $item['icon'] = $icon->getIdentifier();
        $item['overlayIcon'] = $icon->getOverlayIcon() ? $icon->getOverlayIcon()->getIdentifier() : '';
        unset($item['resource']);
        return $item;
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
