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

namespace TYPO3\CMS\Backend\View;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageLayoutResolver;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend layout for CMS
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendLayoutView implements SingletonInterface
{
    protected array $selectedCombinedIdentifier = [];
    protected array $selectedBackendLayout = [];

    public function __construct(
        private readonly DataProviderCollection $dataProviderCollection,
        private readonly TypoScriptStringFactory $typoScriptStringFactory,
        private readonly PageLayoutResolver $pageLayoutResolver,
    ) {}

    /**
     * Gets backend layout items to be shown in the forms engine.
     * This method is called as "itemsProcFunc" with the accordant context
     * for pages.backend_layout and pages.backend_layout_next_level.
     * Also used in the info module, since we need those items with
     * the appropriate labels and backend layout identifiers there, too.
     *
     * @todo This method should return the items array instead of
     *       using the whole parameters array as reference. This
     *       has to be adjusted, as soon as the itemsProcFunc
     *       functionality is changed in this regard.
     */
    public function addBackendLayoutItems(array &$parameters)
    {
        $pageId = $this->determinePageId($parameters['table'], $parameters['row']) ?: 0;
        $pageTsConfig = BackendUtility::getPagesTSconfig($pageId);
        $identifiersToBeExcluded = $this->getIdentifiersToBeExcluded($pageTsConfig);
        $dataProviderContext = new DataProviderContext(
            pageId: $pageId,
            tableName: $parameters['table'],
            fieldName: $parameters['field'],
            data: $parameters['row'],
            pageTsConfig: $pageTsConfig,
        );
        $backendLayoutCollections = $this->dataProviderCollection->getBackendLayoutCollections($dataProviderContext);
        foreach ($backendLayoutCollections as $backendLayoutCollection) {
            $combinedIdentifierPrefix = '';
            if ($backendLayoutCollection->getIdentifier() !== 'default') {
                $combinedIdentifierPrefix = $backendLayoutCollection->getIdentifier() . '__';
            }
            foreach ($backendLayoutCollection->getAll() as $backendLayout) {
                $combinedIdentifier = $combinedIdentifierPrefix . $backendLayout->getIdentifier();
                if (in_array($combinedIdentifier, $identifiersToBeExcluded, true)) {
                    continue;
                }
                $parameters['items'][] = [
                    'label' => $backendLayout->getTitle(),
                    'value' => $combinedIdentifier,
                    'icon' => $backendLayout->getIconPath(),
                ];
            }
        }
    }

    /**
     * Determines the page id for a given record of a database table.
     *
     * @return int|false Returns page id or false on error
     */
    protected function determinePageId(string $tableName, array $data): int|false
    {
        if ($data === []) {
            return false;
        }

        if (str_starts_with((string)$data['uid'], 'NEW')) {
            // negative uid_pid values of content elements indicate that the element
            // has been inserted after an existing element so there is no pid to get
            // the backendLayout for and we have to get that first
            if ($data['pid'] < 0) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($tableName);
                $queryBuilder->getRestrictions()
                    ->removeAll();
                $pageId = $queryBuilder
                    ->select('pid')
                    ->from($tableName)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter(abs($data['pid']), Connection::PARAM_INT)
                        )
                    )
                    ->executeQuery()
                    ->fetchOne();
            } else {
                $pageId = $data['pid'];
            }
        } elseif ($tableName === 'pages') {
            $pageId = $data['uid'];
        } else {
            $pageId = $data['pid'];
        }

        return (int)$pageId;
    }

    /**
     * Returns the backend layout which should be used for this page.
     *
     * @return false|string Identifier of the backend layout to be used, or FALSE if none
     * @internal only public for testing purposes
     */
    public function getSelectedCombinedIdentifier(int $pageId): string|false
    {
        if (!isset($this->selectedCombinedIdentifier[$pageId])) {
            // If it not set check the root-line for a layout on next level and use this
            // (root-line starts with current page and has page "0" at the end)
            $rootLine = BackendUtility::BEgetRootLine($pageId, '', true);
            // Use first element as current page,
            $page = reset($rootLine);
            // and remove last element (root page / pid=0)
            array_pop($rootLine);
            $selectedLayout = $this->pageLayoutResolver->getLayoutIdentifierForPage($page, $rootLine);
            if ($selectedLayout === 'none') {
                // If it is set to "none" - don't use any
                $selectedLayout = false;
            } elseif ($selectedLayout === 'default') {
                $selectedLayout = '0';
            }
            $this->selectedCombinedIdentifier[$pageId] = $selectedLayout;
        }
        // If it is set to a positive value use this
        return $this->selectedCombinedIdentifier[$pageId];
    }

    /**
     * Gets backend layout identifiers to be excluded
     */
    protected function getIdentifiersToBeExcluded(array $pageTSconfig): array
    {
        if (isset($pageTSconfig['options.']['backendLayout.']['exclude'])) {
            return GeneralUtility::trimExplode(
                ',',
                $pageTSconfig['options.']['backendLayout.']['exclude'],
                true
            );
        }
        return [];
    }

    /**
     * Gets colPos items to be shown in the forms engine.
     * This method is called as "itemsProcFunc" with the accordant context
     * for tt_content.colPos.
     */
    public function colPosListItemProcFunc(array &$parameters): void
    {
        $pageId = $this->determinePageId($parameters['table'], $parameters['row']);

        if ($pageId !== false) {
            $parameters['items'] = $this->addColPosListLayoutItems($pageId, $parameters['items']);
        }
    }

    /**
     * Adds items to a colpos list
     */
    protected function addColPosListLayoutItems(int $pageId, array $items): array
    {
        $layout = $this->getSelectedBackendLayout($pageId);
        if ($layout && !empty($layout['__items'])) {
            $items = $layout['__items'];
        }
        return $items;
    }

    /**
     * Gets the selected backend layout structure as an array
     */
    public function getSelectedBackendLayout(int $pageId): ?array
    {
        return $this->getBackendLayoutForPage($pageId)?->getStructure();
    }

    /**
     * Get the BackendLayout object and parse the structure based on the UserTSconfig
     */
    public function getBackendLayoutForPage(int $pageId): ?BackendLayout
    {
        if (isset($this->selectedBackendLayout[$pageId])) {
            return $this->selectedBackendLayout[$pageId];
        }
        $selectedCombinedIdentifier = $this->getSelectedCombinedIdentifier($pageId);
        // If no backend layout is selected, use default
        if (empty($selectedCombinedIdentifier)) {
            $selectedCombinedIdentifier = 'default';
        }
        $backendLayout = $this->dataProviderCollection->getBackendLayout($selectedCombinedIdentifier, $pageId);
        // If backend layout is not found available anymore, use default
        if ($backendLayout === null) {
            $backendLayout = $this->dataProviderCollection->getBackendLayout('default', $pageId);
        }

        if ($backendLayout !== null) {
            $this->selectedBackendLayout[$pageId] = $backendLayout;
        }
        return $backendLayout;
    }

    /**
     * @internal
     */
    public function parseStructure(BackendLayout $backendLayout): array
    {
        $typoScriptTree = $this->typoScriptStringFactory->parseFromStringWithIncludes('backend-layout', $backendLayout->getConfiguration());

        $backendLayoutData = [];
        $backendLayoutData['config'] = $backendLayout->getConfiguration();
        $backendLayoutData['__config'] = $typoScriptTree->toArray();
        $backendLayoutData['__items'] = [];
        $backendLayoutData['__colPosList'] = [];
        $backendLayoutData['usedColumns'] = [];
        $backendLayoutData['colCount'] = (int)($backendLayoutData['__config']['backend_layout.']['colCount'] ?? 0);
        $backendLayoutData['rowCount'] = (int)($backendLayoutData['__config']['backend_layout.']['rowCount'] ?? 0);

        // create items and colPosList
        if (!empty($backendLayoutData['__config']['backend_layout.']['rows.'])) {
            $rows = $backendLayoutData['__config']['backend_layout.']['rows.'];
            ksort($rows);
            foreach ($rows as $row) {
                if (!empty($row['columns.'])) {
                    foreach ($row['columns.'] as $column) {
                        if (!isset($column['colPos'])) {
                            continue;
                        }
                        $backendLayoutData['__items'][] = [
                            'label' => $column['name'],
                            'value' => $column['colPos'],
                            'icon' => null,
                        ];
                        $backendLayoutData['__colPosList'][] = $column['colPos'];
                        $backendLayoutData['usedColumns'][(int)$column['colPos']] = $column['name'];
                    }
                }
            }
        }
        return $backendLayoutData;
    }
}
