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

namespace TYPO3\CMS\Dashboard\Widgets\Provider;

use Doctrine\DBAL\Exception\TableNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * Data provider for recently opened documents widget.
 *
 * @internal
 */
readonly class RecentlyOpenedDocuments
{
    public function __construct(
        protected UriBuilder $uriBuilder,
        protected ResourceFactory $resourceFactory
    ) {}

    /**
     * Get recently opened documents for the current backend user
     *
     * @param int $limit Maximum number of items to return
     * @return array Array of recently opened document data
     */
    public function getItems(int $limit = 10): array
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser === null) {
            return [];
        }

        $openDocuments = $backendUser->getModuleData('opendocs::recent') ?: [];

        if (!is_array($openDocuments) || $openDocuments === []) {
            return [];
        }

        // Initialize UriBuilder once outside the loop
        $dashboardUri = $this->uriBuilder->buildUriFromRoute('dashboard', [], UriBuilder::ABSOLUTE_URL);

        $items = [];
        foreach ($openDocuments as $openDocData) {
            if (count($items) >= $limit) {
                break;
            }

            $table = $openDocData['table'] ?? '';
            $uid = $openDocData['uid'] ?? 0;

            try {
                $record = BackendUtility::getRecordWSOL($table, $uid);
            } catch (TableNotFoundException) {
                // Handle cases where a table no longer exists (e.g., extension uninstalled
                // after opening its records). Without catching this exception, the widget
                // backend cannot be rendered at all.
                $record = null;
            }

            if ($record === null) {
                continue;
            }

            $parameters = $openDocData['parameters'] ?? [];
            $uri = $this->uriBuilder->buildUriFromRoute('record_edit', ['returnUrl' => $dashboardUri, ...$parameters]);

            $items[] = [
                'table' => $table,
                'record' => $record,
                'uid' => $uid,
                'title' => $openDocData['title'] ?? BackendUtility::getRecordTitle($table, $record),
                'uri' => $uri,
                'breadcrumb' => $this->getBreadcrumb($table, $record),
                'type' => $this->getRecordType($table, $record),
                'typeIcon' => $this->getTypeIcon($table),
            ];
        }

        return $items;
    }

    /**
     * Get the type label: CType for tt_content, sys_file for sys_file_metadata, table label for other records
     */
    protected function getRecordType(string $table, array $record): string
    {
        if ($table === 'sys_file_metadata') {
            $table = 'sys_file';
        }
        $label = $GLOBALS['TCA'][$table]['ctrl']['title'] ?? $table;

        return str_starts_with($label, 'LLL:') ? $GLOBALS['LANG']->sL($label) : $label;
    }

    /**
     * Get the type icon identifier for the record's table
     */
    protected function getTypeIcon(string $table): string
    {
        $typeIconClasses = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'] ?? [];

        return $typeIconClasses['default'] ?? 'apps-pagetree-page-default';
    }

    /**
     * Get breadcrumb path for a record (page path or folder path for files)
     */
    protected function getBreadcrumb(string $table, array $record): string
    {
        if (in_array($table, ['sys_file', 'sys_file_metadata'], true)) {
            return $this->getFileBreadcrumb($table, $record);
        }

        return $this->getPageBreadcrumb($table, $record);
    }

    /**
     * Get breadcrumb for file records
     */
    protected function getFileBreadcrumb(string $table, array $record): string
    {
        try {
            $fileUid = $table === 'sys_file' ? (int)($record['uid'] ?? 0) : (int)($record['file'] ?? 0);

            if ($fileUid === 0) {
                return '';
            }

            $file = $this->resourceFactory->getFileObject($fileUid);
            $folder = $file->getParentFolder();
            $storage = $folder->getStorage();
            $breadcrumbParts = [$storage->getName()];
            $folderIdentifier = $folder->getIdentifier();

            if ($folderIdentifier !== '/') {
                $pathSegments = array_filter(explode('/', trim($folderIdentifier, '/')));
                $breadcrumbParts = array_merge($breadcrumbParts, $pathSegments);
            }

            return implode(' / ', $breadcrumbParts);
        } catch (\Exception) {
            return '';
        }
    }

    /**
     * Get breadcrumb for page-based records
     */
    protected function getPageBreadcrumb(string $table, array $record): string
    {
        $breadcrumbParts = [];
        $pid = $table === 'pages' ? (int)($record['uid'] ?? 0) : (int)($record['pid'] ?? 0);

        if ($pid > 0) {
            $rootline = BackendUtility::BEgetRootLine($pid);
            foreach (array_reverse($rootline) as $page) {
                if ((int)($page['uid'] ?? 0) > 0) {
                    $breadcrumbParts[] = $page['title'] ?? '';
                }
            }
        }

        return implode(' / ', $breadcrumbParts);
    }

    /**
     * Get the current backend user
     */
    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
