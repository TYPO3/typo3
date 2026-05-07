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

namespace TYPO3\CMS\Filelist\Search\LiveSearch;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Search\LiveSearch\ResultItem;
use TYPO3\CMS\Backend\Search\LiveSearch\ResultItemAction;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\Search\FileSearchQuery;
use TYPO3\CMS\Core\Resource\Search\QueryRestrictions\FolderMountsRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Search provider to query files (sys_file + sys_file_metadata) for the
 * backend live search. Results respect the user's file mounts and are
 * deduplicated per file.
 *
 * @internal
 */
final class FileProvider implements SearchProviderInterface
{
    private LanguageService $languageService;

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly ResourceFactory $resourceFactory,
        LanguageServiceFactory $languageServiceFactory,
    ) {
        $this->languageService = $languageServiceFactory->createFromUserPreferences($this->getBackendUser());
    }

    public function getFilterLabel(): string
    {
        return $this->languageService->sL('filelist.messages:live_search.file_provider.filter_label');
    }

    public function count(SearchDemand $searchDemand): int
    {
        $query = FileSearchQuery::createCountForSearchDemand($this->buildFileSearchDemand($searchDemand));
        $query->additionalRestriction(new FolderMountsRestriction($this->getBackendUser()));
        return (int)$query->execute()->fetchOne();
    }

    /**
     * @return ResultItem[]
     */
    public function find(SearchDemand $searchDemand): array
    {
        $fileSearchDemand = $this->buildFileSearchDemand($searchDemand)
            ->withStartResult($searchDemand->getOffset())
            ->withMaxResults($searchDemand->getLimit());

        $query = FileSearchQuery::createForSearchDemand($fileSearchDemand);
        $result = $query->execute();

        $items = [];
        while ($row = $result->fetchAssociative()) {
            try {
                $file = $this->resourceFactory->getFileObject((int)$row['uid'], $row);
            } catch (Exception) {
                continue;
            }

            try {
                $parentFolder = $file->getParentFolder();
                $parentFolderIdentifier = $parentFolder->getCombinedIdentifier();
            } catch (Exception) {
                // Orphaned sys_file row referring to a folder that no longer exists on disk
                continue;
            }

            $actions = [];
            $editAction = $this->buildEditMetadataAction($file);
            if ($editAction !== null) {
                $actions['edit'] = $editAction;
            }
            $actions['show'] = $this->buildShowInListAction($parentFolderIdentifier, $searchDemand->getQuery());

            $resultItem = (new ResultItem(self::class))
                ->setItemTitle($file->getName())
                ->setTypeLabel($this->languageService->sL('filelist.messages:live_search.file_provider.type_label'))
                ->setIcon($this->iconFactory->getIconForResource($file, IconSize::SMALL))
                ->setThumbnailUrl($this->buildThumbnailUrl($file))
                ->setActions(...array_values($actions))
                ->setDefaultAction($actions['edit'] ?? $actions['show'])
                ->setExtraData([
                    'breadcrumb' => $this->buildBreadcrumb($parentFolder),
                ]);

            foreach ($this->buildProperties($file, $parentFolder) as $label => $value) {
                $resultItem->addProperty($label, $value);
            }

            $items[] = $resultItem;
        }

        return $items;
    }

    private function buildFileSearchDemand(SearchDemand $searchDemand): FileSearchDemand
    {
        return FileSearchDemand::createForSearchTerm($searchDemand->getQuery())->withRecursive();
    }

    private function buildEditMetadataAction(File $file): ?ResultItemAction
    {
        if (!$file->isIndexed() || !$file->checkActionPermission('editMeta')) {
            return null;
        }
        $metaDataUid = $file->getMetaData()->offsetGet('uid');
        if (!$metaDataUid) {
            return null;
        }
        $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => ['sys_file_metadata' => [$metaDataUid => 'edit']],
            'module' => 'media_management',
        ]);

        return (new ResultItemAction('edit'))
            ->setLabel($this->languageService->sL('core.core:cm.editMetadata'))
            ->setIcon($this->iconFactory->getIcon('actions-open', IconSize::SMALL))
            ->setUrl($url);
    }

    private function buildShowInListAction(string $parentFolderIdentifier, string $query): ResultItemAction
    {
        $url = (string)$this->uriBuilder->buildUriFromRoute('media_management', [
            'id' => $parentFolderIdentifier,
            'searchTerm' => $query,
        ]);

        return (new ResultItemAction('show'))
            ->setLabel($this->languageService->sL('filelist.messages:live_search.file_provider.show_in_list'))
            ->setIcon($this->iconFactory->getIcon('actions-list', IconSize::SMALL))
            ->setUrl($url);
    }

    private function buildBreadcrumb(Folder $parentFolder): string
    {
        return $parentFolder->getStorage()->getName() . $parentFolder->getReadablePath();
    }

    /**
     * @return array<string, string>
     */
    private function buildProperties(File $file, Folder $parentFolder): array
    {
        $items = [
            $this->languageService->sL('filelist.messages:live_search.file_provider.property.location') => $this->buildBreadcrumb($parentFolder),
            $this->languageService->sL('filelist.messages:live_search.file_provider.property.size') => GeneralUtility::formatSize(
                (int)$file->getSize(),
                $this->languageService->sL('core.common:byteSizeUnits')
            ),
        ];
        if ($file->getModificationTime() > 0) {
            $items[$this->languageService->sL('filelist.messages:live_search.file_provider.property.modified')] = BackendUtility::datetime($file->getModificationTime());
        }
        return $items;
    }

    private function buildThumbnailUrl(File $file): ?string
    {
        if (!$file->isImage() && !$file->isMediaFile()) {
            return null;
        }
        try {
            $processedFile = $file->process(
                ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
                ['maxWidth' => 166, 'maxHeight' => 115]
            );
        } catch (\Throwable) {
            return null;
        }

        return $processedFile->getPublicUrl() ?: null;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
