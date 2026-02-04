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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Backend\View\Event\ManipulateBackendLayoutColPosConfigurationForPageEvent;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageLayoutResolver;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend layout for CMS
 *
 * @todo: This class name is unfortunate and the scope of this class in general convoluted and unclear.
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
readonly class BackendLayoutView
{
    private const SELECTED_COMBINED_CACHE_IDENTIFIER = 'backend-layout-view-selected-combined-identifiers';
    private const SELECTED_BACKEND_LAYOUTS_CACHE_IDENTIFIER = 'backend-layout-view-selected-backend-layouts';

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
        private DataProviderCollection $dataProviderCollection,
        private TypoScriptStringFactory $typoScriptStringFactory,
        private PageLayoutResolver $pageLayoutResolver,
    ) {}

    /**
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
    public function addBackendLayoutItems(array &$parameters): void
    {
        $pageId = $this->determinePageId($parameters['table'], $parameters['row']) ?: 0;
        $pageTsConfig = BackendUtility::getPagesTSconfig($pageId);
        $identifiersToBeExcluded = [];
        if (isset($pageTsConfig['options.']['backendLayout.']['exclude'])) {
            $identifiersToBeExcluded = GeneralUtility::trimExplode(',', $pageTsConfig['options.']['backendLayout.']['exclude'], true);
        }
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
     * Gets colPos items to be shown in form engine. This method is called
     * as "itemsProcFunc" with the accordant context for tt_content.colPos.
     */
    public function colPosListItemProcFunc(array &$parameters): void
    {
        $pageId = $this->determinePageId($parameters['table'], $parameters['row']);
        if ($pageId !== false) {
            $layout = $this->getSelectedBackendLayout($pageId);
            if ($layout && !empty($layout['__items'])) {
                $parameters['items'] = $layout['__items'];
            }
        }
    }

    /**
     * Gets the selected backend layout structure as an array
     */
    public function getSelectedBackendLayout(int $pageId): array
    {
        return $this->getBackendLayoutForPage($pageId)->getStructure();
    }

    /**
     * Get the BackendLayout object and parse the structure based on the UserTSconfig
     */
    public function getBackendLayoutForPage(int $pageId): BackendLayout
    {
        $selectedBackendLayoutsByPageId = $this->runtimeCache->get(self::SELECTED_BACKEND_LAYOUTS_CACHE_IDENTIFIER);
        if (($selectedBackendLayoutsByPageId[$pageId] ?? null) instanceof BackendLayout) {
            return $selectedBackendLayoutsByPageId[$pageId];
        }
        if (!is_array($selectedBackendLayoutsByPageId)) {
            $selectedBackendLayoutsByPageId = [];
        }
        $selectedCombinedIdentifier = $this->getSelectedCombinedIdentifier($pageId);
        if (empty($selectedCombinedIdentifier)) {
            // If no backend layout is selected, use default
            $selectedCombinedIdentifier = 'default';
        }
        $backendLayout = $this->dataProviderCollection->getBackendLayout($selectedCombinedIdentifier, $pageId);
        if ($backendLayout === null) {
            // If backend layout is not found available anymore, use default
            $backendLayout = $this->dataProviderCollection->getBackendLayout('default', $pageId);
        }
        if ($backendLayout === null) {
            // The 'default' backend layout must *always* return something. We must have some layout at this
            // point and the method always returns a BackendLayout instance.
            throw new \RuntimeException('Fallback to default backend layout failed', 1768151069);
        }
        $selectedBackendLayoutsByPageId[$pageId] = $backendLayout;
        $this->runtimeCache->set(self::SELECTED_BACKEND_LAYOUTS_CACHE_IDENTIFIER, $selectedBackendLayoutsByPageId);
        return $backendLayout;
    }

    /**
     * This method is mainly used to retrieve the final allowed/disallowed content element configuration per colPos. It
     * is an implementation of what ext:content_defender provided as extension for a long time already. This method is
     * embedded in the overall rather convoluted handling around backend layouts. It is - at least for now - subject to change.
     * The method emits an event that is declared internal as well.
     *
     * When calling the method, the optional request argument should be hand whenever available to give event listeners
     * as much context as possible.
     *
     * @internal as the entire class. Note ManipulateBackendLayoutColPosConfigurationForPageEvent is declared internal
     *           as well. The event is needed for extensions like ext:container, but may still change when backend layout
     *           related code is consolidated.
     */
    public function getColPosConfigurationForPage(BackendLayout $backendLayout, int $colPos, int $pageUid, ?ServerRequestInterface $request = null): array
    {
        $configuration = [];
        $backendLayoutStructure = $backendLayout->getStructure();
        if (in_array($colPos, array_map('intval', $backendLayoutStructure['__colPosList']), true)) {
            foreach ($backendLayoutStructure['__config']['backend_layout.']['rows.'] as $row) {
                if (empty($row['columns.'])) {
                    continue;
                }
                foreach ($row['columns.'] as $column) {
                    if (isset($column['colPos']) && $column['colPos'] !== '' && $colPos === (int)$column['colPos']) {
                        $configuration = $column;
                        // Compatibility layer for ext:content_defender: allowed.CType is now allowedContentTypes and
                        // disallowed.CType is now disallowedContentTypes. Copy the old content_defender settings to
                        // the new names if new names are not set.
                        // @todo: These fallbacks could potentially be deprecated at some point. Maybe v15?
                        if (!empty($configuration['allowed.']['CType'] ?? '') && !isset($configuration['allowedContentTypes'])) {
                            $configuration['allowedContentTypes'] = $configuration['allowed.']['CType'];
                        }
                        if (!empty($configuration['disallowed.']['CType'] ?? '') && !isset($configuration['disallowedContentTypes'])) {
                            $configuration['disallowedContentTypes'] = $configuration['disallowed.']['CType'];
                        }
                        break 2;
                    }
                }
            }
        }
        $event = new ManipulateBackendLayoutColPosConfigurationForPageEvent(
            configuration: $configuration,
            backendLayout: $backendLayout,
            colPos: $colPos,
            pageUid: $pageUid,
            request: $request,
        );
        return $this->eventDispatcher->dispatch($event)->configuration;
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

    public function isCTypeAllowedInColPosByPage(string $cType, int $colPos, int $pageUid): bool
    {
        $backendLayout = $this->getBackendLayoutForPage($pageUid);
        $colPosConfiguration = $this->getColPosConfigurationForPage($backendLayout, $colPos, $pageUid);

        if (!empty($colPosConfiguration['disallowedContentTypes'])) {
            $disallowedContentTypes = GeneralUtility::trimExplode(',', $colPosConfiguration['disallowedContentTypes']);

            if (in_array($cType, $disallowedContentTypes, true)) {
                return false;
            }
        }

        if (!empty($colPosConfiguration['allowedContentTypes'])) {
            $allowedContentTypes = GeneralUtility::trimExplode(',', $colPosConfiguration['allowedContentTypes']);

            if (!in_array($cType, $allowedContentTypes, true)) {
                return false;
            }
        }

        return true;
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
            // Negative uid_pid values of content elements indicate that the element
            // has been inserted after an existing element so there is no pid to get
            // the backendLayout for, and we have to get that first.
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
     */
    protected function getSelectedCombinedIdentifier(int $pageId): string|false
    {
        $selectedCombinedIdentifiers = $this->runtimeCache->get(self::SELECTED_COMBINED_CACHE_IDENTIFIER);
        if (is_array($selectedCombinedIdentifiers) && array_key_exists($pageId, $selectedCombinedIdentifiers)) {
            return $selectedCombinedIdentifiers[$pageId];
        }
        if (!is_array($selectedCombinedIdentifiers)) {
            $selectedCombinedIdentifiers = [];
        }
        // If it not set check the rootline for a layout on next level and use this: Rootline
        // starts with current page and has page "0" at the end.
        $rootLine = BackendUtility::BEgetRootLine($pageId, '', true);
        if ($rootLine === []) {
            // Return for invalid rootline
            return false;
        }
        // Use first element as current page and remove last element (root page / pid=0)
        $page = reset($rootLine);
        array_pop($rootLine);
        $selectedLayout = $this->pageLayoutResolver->getLayoutIdentifierForPage($page, $rootLine);
        if ($selectedLayout === 'none') {
            // If it is set to "none" - don't use any
            $selectedLayout = false;
        } elseif ($selectedLayout === 'default') {
            $selectedLayout = '0';
        }
        $selectedCombinedIdentifiers[$pageId] = $selectedLayout;
        $this->runtimeCache->set(self::SELECTED_COMBINED_CACHE_IDENTIFIER, $selectedCombinedIdentifiers);
        return $selectedLayout;
    }
}
