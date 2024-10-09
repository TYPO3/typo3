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

namespace TYPO3\CMS\Backend\Controller\Page;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent;
use TYPO3\CMS\Backend\Dto\Tree\Label\Label;
use TYPO3\CMS\Backend\Dto\Tree\PageTreeItem;
use TYPO3\CMS\Backend\Dto\Tree\TreeItem;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\JsConfirmation;
use TYPO3\CMS\Core\Database\Query\Restriction\DocumentTypeExclusionRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller providing data to the page tree
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class TreeController
{
    /**
     * Option to use the nav_title field for outputting in the tree items, set via userTS.
     */
    protected bool $useNavTitle = false;

    /**
     * Option to prefix the page ID when outputting the tree items, set via userTS.
     */
    protected bool $addIdAsPrefix = false;

    /**
     * Option to prefix the domain name of sys_domains when outputting the tree items, set via userTS.
     */
    protected bool $addDomainName = false;

    /**
     * Option to add the rootline path above each mount point, set via userTS.
     */
    protected bool $showMountPathAboveMounts = false;

    /**
     * A list of pages not to be shown.
     */
    protected array $hiddenRecords = [];

    /**
     * An array of background colors for a branch in the tree, set via userTS.
     *
     * @var array
     * @deprecated will be removed in TYPO3 v14.0, please use labels instead
     */
    protected $backgroundColors = [];

    /**
     * An array of labels for a branch in the tree, set via userTS.
     */
    protected array $labels = [];

    /**
     * Number of tree levels which should be returned on the first page tree load
     */
    protected int $levelsToFetch = 2;

    /**
     * When set to true all nodes returend by API will be expanded
     */
    protected bool $expandAllNodes = false;

    /**
     * Used in the record link picker to limit the page tree only to a specific list
     * of alternative entry points for selecting only from a list of pages
     */
    protected array $alternativeEntryPoints = [];

    protected PageTreeRepository $pageTreeRepository;

    protected bool $userHasAccessToModifyPagesAndToDefaultLanguage = false;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly SiteFinder $siteFinder,
    ) {}

    protected function initializeConfiguration(ServerRequestInterface $request)
    {
        if ($request->getQueryParams()['readOnly'] ?? false) {
            $this->getBackendUser()->initializeWebmountsForElementBrowser();
        }
        if ($request->getQueryParams()['alternativeEntryPoints'] ?? false) {
            $this->alternativeEntryPoints = $request->getQueryParams()['alternativeEntryPoints'];
            $this->alternativeEntryPoints = array_filter($this->alternativeEntryPoints, function (int $pageId): bool {
                return $this->getBackendUser()->isInWebMount($pageId) !== null;
            });
            $this->alternativeEntryPoints = array_map(intval(...), $this->alternativeEntryPoints);
            $this->alternativeEntryPoints = array_unique($this->alternativeEntryPoints);
        }
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $this->hiddenRecords = GeneralUtility::intExplode(
            ',',
            (string)($userTsConfig['options.']['hideRecords.']['pages'] ?? ''),
            true
        );
        $this->backgroundColors = $userTsConfig['options.']['pageTree.']['backgroundColor.'] ?? [];
        $this->labels = $userTsConfig['options.']['pageTree.']['label.'] ?? [];
        $this->addIdAsPrefix = (bool)($userTsConfig['options.']['pageTree.']['showPageIdWithTitle'] ?? false);
        $this->addDomainName = (bool)($userTsConfig['options.']['pageTree.']['showDomainNameWithTitle'] ?? false);
        $this->useNavTitle = (bool)($userTsConfig['options.']['pageTree.']['showNavTitle'] ?? false);
        $this->showMountPathAboveMounts = (bool)($userTsConfig['options.']['pageTree.']['showPathAboveMounts'] ?? false);
        $this->userHasAccessToModifyPagesAndToDefaultLanguage = $this->getBackendUser()->check('tables_modify', 'pages') && $this->getBackendUser()->checkLanguageAccess(0);
    }

    /**
     * Returns page tree configuration in JSON
     */
    public function fetchConfigurationAction(): ResponseInterface
    {
        $configuration = [
            'allowDragMove' => $this->isDragMoveAllowed(),
            'doktypes' => $this->getDokTypes(),
            'displayDeleteConfirmation' => $this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE),
            'temporaryMountPoint' => $this->getMountPointPath((int)($this->getBackendUser()->uc['pageTree_temporaryMountPoint'] ?? 0)),
            'showIcons' => true,
            'dataUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_page_tree_data'),
            'filterUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_page_tree_filter'),
            'setTemporaryMountPointUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_page_tree_set_temporary_mount_point'),
        ];

        return new JsonResponse($configuration);
    }

    public function fetchReadOnlyConfigurationAction(ServerRequestInterface $request): ResponseInterface
    {
        $entryPoints = (string)($request->getQueryParams()['alternativeEntryPoints'] ?? '');
        $entryPoints = GeneralUtility::intExplode(',', $entryPoints, true);
        $additionalArguments = [
            'readOnly' => 1,
        ];
        if (!empty($entryPoints)) {
            $additionalArguments['alternativeEntryPoints'] = $entryPoints;
        }
        $configuration = [
            'displayDeleteConfirmation' => $this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE),
            'temporaryMountPoint' => $this->getMountPointPath((int)($this->getBackendUser()->uc['pageTree_temporaryMountPoint'] ?? 0)),
            'showIcons' => true,
            'dataUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_page_tree_data', $additionalArguments),
            'filterUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_page_tree_filter', $additionalArguments),
            'setTemporaryMountPointUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_page_tree_set_temporary_mount_point'),
        ];
        return new JsonResponse($configuration);
    }

    /**
     * Returns the list of doktypes to display in page tree toolbar drag area
     *
     * Note: The list can be filtered by the user TypoScript
     * option "options.pageTree.doktypesToShowInNewPageDragArea".
     */
    protected function getDokTypes(): array
    {
        $backendUser = $this->getBackendUser();
        $doktypeLabelMap = [];
        foreach ($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] as $doktypeItemConfig) {
            $selectionItem = SelectItem::fromTcaItemArray($doktypeItemConfig);
            if ($selectionItem->isDivider()) {
                continue;
            }
            $doktypeLabelMap[$selectionItem->getValue()] = $selectionItem->getLabel();
        }
        $doktypes = GeneralUtility::intExplode(',', (string)($backendUser->getTSConfig()['options.']['pageTree.']['doktypesToShowInNewPageDragArea'] ?? ''), true);
        $doktypes = array_unique($doktypes);
        $output = [];
        $allowedDoktypes = GeneralUtility::intExplode(',', (string)($backendUser->groupData['pagetypes_select'] ?? ''), true);
        $isAdmin = $backendUser->isAdmin();
        // Early return if backend user may not create any doktype
        if (!$isAdmin && empty($allowedDoktypes)) {
            return $output;
        }
        foreach ($doktypes as $doktype) {
            if (!isset($doktypeLabelMap[$doktype]) || (!$isAdmin && !in_array($doktype, $allowedDoktypes, true))) {
                continue;
            }
            $label = htmlspecialchars($this->getLanguageService()->sL($doktypeLabelMap[$doktype]));
            $output[] = [
                'nodeType' => $doktype,
                'icon' => $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$doktype] ?? '',
                'title' => $label,
            ];
        }
        return $output;
    }

    /**
     * Returns JSON representing page tree
     */
    public function fetchDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->initializeConfiguration($request);

        $items = [];
        $parentIdentifier = $request->getQueryParams()['parent'] ?? null;
        if ($parentIdentifier) {
            $parentDepth = (int)($request->getQueryParams()['depth'] ?? 0);
            // Fetching a part of a page tree
            $entryPoints = $this->getAllEntryPointPageTrees((int)$parentIdentifier);
            $mountPid = (int)($request->getQueryParams()['mount'] ?? 0);
            $this->levelsToFetch = $parentDepth + $this->levelsToFetch;
            foreach ($entryPoints as $page) {
                $items[] = $this->pagesToFlatArray($page, $mountPid, $parentDepth);
            }
        } else {
            $entryPoints = $this->getAllEntryPointPageTrees();
            foreach ($entryPoints as $page) {
                $items[] = $this->pagesToFlatArray($page, (int)$page['uid']);
            }
        }
        $items = array_merge(...$items);

        return new JsonResponse($this->getPostProcessedPageItems($request, $items));
    }

    /**
     * Returns JSON representing page tree filtered by keyword
     */
    public function filterDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $searchQuery = $request->getQueryParams()['q'] ?? '';
        if (trim($searchQuery) === '') {
            return new JsonResponse([]);
        }

        $this->initializeConfiguration($request);
        $this->expandAllNodes = true;

        $items = [];
        $entryPoints = $this->getAllEntryPointPageTrees(0, $searchQuery);

        foreach ($entryPoints as $page) {
            if (!empty($page)) {
                $items[] = $this->pagesToFlatArray($page, (int)$page['uid']);
            }
        }
        $items = array_merge(...$items);

        return new JsonResponse($this->getPostProcessedPageItems($request, $items));
    }

    /**
     * Sets a temporary mount point
     *
     * @throws \RuntimeException
     */
    public function setTemporaryMountPointAction(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($request->getParsedBody()['pid'])) {
            throw new \RuntimeException(
                'Required "pid" parameter is missing.',
                1511792197
            );
        }
        $pid = (int)$request->getParsedBody()['pid'];

        $this->getBackendUser()->uc['pageTree_temporaryMountPoint'] = $pid;
        $this->getBackendUser()->writeUC();
        $response = [
            'mountPointPath' => $this->getMountPointPath($pid),
        ];
        return new JsonResponse($response);
    }

    /**
     * Converts nested tree structure produced by PageTreeRepository to a flat, one level array
     * and also adds visual representation information to the data.
     *
     * The result is intended to be used as JSON result - dumping data directly to HTML might lead to XSS!
     *
     * @param array $page
     * @param int $entryPoint
     * @param int $depth
     */
    protected function pagesToFlatArray(array $page, int $entryPoint, int $depth = 0): array
    {
        $backendUser = $this->getBackendUser();
        $pageId = (int)$page['uid'];
        if (in_array($pageId, $this->hiddenRecords, true)) {
            return [];
        }

        $stopPageTree = !empty($page['php_tree_stop']) && $depth > 0;
        $identifier = $entryPoint . '_' . $pageId;

        $suffix = '';
        $prefix = '';
        $nameSourceField = 'title';
        $visibleText = $page['title'];
        $tooltip = BackendUtility::titleAttribForPages($page, '', false, $this->useNavTitle);
        if ($pageId !== 0) {
            $icon = $this->iconFactory->getIconForRecord('pages', $page, IconSize::SMALL);
        } else {
            $icon = $this->iconFactory->getIcon('apps-pagetree-root', IconSize::SMALL);
        }

        if ($this->useNavTitle && trim($page['nav_title'] ?? '') !== '') {
            $nameSourceField = 'nav_title';
            $visibleText = $page['nav_title'];
        }
        if (trim($visibleText) === '') {
            $visibleText = htmlspecialchars('[' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title') . ']');
        }

        if ($this->addDomainName && ($page['is_siteroot'] ?? false)) {
            $domain = $this->getDomainNameForPage($pageId);
            $suffix = $domain !== '' ? ' [' . $domain . ']' : '';
        }

        $lockInfo = BackendUtility::isRecordLocked('pages', $pageId);
        if (is_array($lockInfo)) {
            $tooltip .= ' - ' . $lockInfo['msg'];
        }
        if ($this->addIdAsPrefix) {
            $prefix = '[' . $pageId . '] ';
        }

        $labels = [];
        if (!empty($this->backgroundColors[$pageId])) {
            $labels[] = new Label(
                label: $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.color') . ': ' . $this->backgroundColors[$pageId],
                color: $this->backgroundColors[$pageId] ?? '#ff0000',
                priority: -1,
            );
        }
        if (!empty($this->labels[$pageId . '.']) && isset($this->labels[$pageId . '.']['label']) && trim($this->labels[$pageId . '.']['label']) !== '') {
            $labels[] = new Label(
                label: (string)($this->labels[$pageId . '.']['label']),
                color: (string)($this->labels[$pageId . '.']['color'] ?? '#ff8700'),
            );
        }

        $editable = false;
        if ($pageId !== 0) {
            $editable = $this->userHasAccessToModifyPagesAndToDefaultLanguage && $backendUser->doesUserHaveAccess($page, Permission::PAGE_EDIT);
        }

        $items = [];
        $item = [
            // identifier is not only used for pages, therefore it's a string
            'identifier' => (string)$pageId,
            'parentIdentifier' => (string)($page['pid'] ?? ''),
            'recordType' => 'pages',
            'name' => $visibleText,
            'prefix' => !empty($prefix) ? htmlspecialchars($prefix) : '',
            'suffix' => !empty($suffix) ? htmlspecialchars($suffix) : '',
            'tooltip' => $tooltip,
            'depth' => $depth,
            'icon' => $icon->getIdentifier(),
            'overlayIcon' => $icon->getOverlayIcon() ? $icon->getOverlayIcon()->getIdentifier() : '',
            'editable' => $editable,
            'deletable' => $backendUser->doesUserHaveAccess($page, Permission::PAGE_DELETE),
            'labels' => $labels,

            // _page is only for use in events so they do not need to fetch those
            // records again. The property will be removed from the final payload.
            '_page' => $page,
            'doktype' => (int)($page['doktype'] ?? 0),
            'nameSourceField' => $nameSourceField,
            'mountPoint' => $entryPoint,
            'workspaceId' => !empty($page['t3ver_oid']) ? $page['t3ver_oid'] : $pageId,
        ];

        if (!empty($page['_children']) || $this->pageTreeRepository->hasChildren($pageId)) {
            $item['hasChildren'] = true;
            if ($depth >= $this->levelsToFetch) {
                $page = $this->pageTreeRepository->getTreeLevels($page, 1);
            }
        }
        if (is_array($lockInfo)) {
            $item['locked'] = true;
        }
        if ($stopPageTree) {
            $item['stopPageTree'] = true;
        }
        if ($depth === 0) {
            if ($this->showMountPathAboveMounts) {
                $item['note'] = $this->getMountPointPath($pageId);
            }
        }

        $items[] = $item;
        if (!$stopPageTree && is_array($page['_children']) && !empty($page['_children']) && ($depth < $this->levelsToFetch || $this->expandAllNodes)) {
            $items[key($items)]['loaded'] = true;
            foreach ($page['_children'] as $child) {
                $items = array_merge($items, $this->pagesToFlatArray($child, $entryPoint, $depth + 1));
            }
        }

        return $items;
    }

    protected function initializePageTreeRepository(): PageTreeRepository
    {
        $backendUser = $this->getBackendUser();
        $userTsConfig = $backendUser->getTSConfig();
        $excludedDocumentTypes = GeneralUtility::intExplode(',', (string)($userTsConfig['options.']['pageTree.']['excludeDoktypes'] ?? ''), true);

        $additionalQueryRestrictions = [];
        if ($excludedDocumentTypes !== []) {
            $additionalQueryRestrictions[] = GeneralUtility::makeInstance(DocumentTypeExclusionRestriction::class, $excludedDocumentTypes);
        }

        $pageTreeRepository = GeneralUtility::makeInstance(
            PageTreeRepository::class,
            $backendUser->workspace,
            [],
            $additionalQueryRestrictions
        );
        $pageTreeRepository->setAdditionalWhereClause($backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        return $pageTreeRepository;
    }

    /**
     * Fetches all pages for all tree entry points the user is allowed to see
     *
     * @param string $query The search query can either be a string to be found in the title or the nav_title of a page or the uid of a page.
     */
    protected function getAllEntryPointPageTrees(int $startPid = 0, string $query = ''): array
    {
        $this->pageTreeRepository ??= $this->initializePageTreeRepository();
        $backendUser = $this->getBackendUser();
        if ($startPid === 0) {
            $startPid = (int)($backendUser->uc['pageTree_temporaryMountPoint'] ?? 0);
        }

        $entryPointIds = null;
        if ($startPid > 0) {
            $entryPointIds = [$startPid];
        } elseif (!empty($this->alternativeEntryPoints)) {
            $entryPointIds = $this->alternativeEntryPoints;
        }

        $permClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        if ($query !== '') {
            $this->levelsToFetch = 999;
            $this->pageTreeRepository->fetchFilteredTree(
                $query,
                $this->getAllowedMountPoints(),
                $permClause
            );
        }
        $rootRecord = [
            'uid' => 0,
            'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?: 'TYPO3',
        ];
        $entryPointRecords = [];
        $mountPoints = [];
        if ($entryPointIds === null) {
            //watch out for deleted pages returned as webmount
            $mountPoints = $backendUser->getWebmounts();
            $mountPoints = array_filter($mountPoints, fn(int $id): bool => !in_array($id, $this->hiddenRecords, true));

            // Switch to multiple-entryPoint-mode if the rootPage is to be mounted.
            // (other mounts would appear duplicated in the pid = 0 tree otherwise)
            if (in_array(0, $mountPoints, true)) {
                $entryPointIds = $mountPoints;
            }
        }

        if ($entryPointIds === null) {
            if ($query !== '') {
                $rootRecord = $this->pageTreeRepository->getTree(0, null, $mountPoints);
            } else {
                $rootRecord = $this->pageTreeRepository->getTreeLevels($rootRecord, $this->levelsToFetch, $mountPoints);
            }

            $mountPointOrdering = array_flip($mountPoints);
            if (isset($rootRecord['_children'])) {
                usort($rootRecord['_children'], static function ($a, $b) use ($mountPointOrdering) {
                    return ($mountPointOrdering[$a['uid']] ?? 0) <=> ($mountPointOrdering[$b['uid']] ?? 0);
                });
            }

            $entryPointRecords[] = $rootRecord;
        } else {
            $entryPointIds = array_filter($entryPointIds, fn(int $id): bool => !in_array($id, $this->hiddenRecords, true));
            foreach ($entryPointIds as $k => $entryPointId) {
                if ($entryPointId === 0) {
                    $entryPointRecord = $rootRecord;
                } else {
                    $entryPointRecord = BackendUtility::getRecordWSOL('pages', $entryPointId, '*', $permClause);

                    if ($entryPointRecord !== null && !$backendUser->isInWebMount($entryPointId)) {
                        $entryPointRecord = null;
                    }
                    if ($entryPointRecord === null) {
                        continue;
                    }
                }

                $entryPointRecord['uid'] = (int)$entryPointRecord['uid'];
                if ($query === '') {
                    $entryPointRecord = $this->pageTreeRepository->getTreeLevels($entryPointRecord, $this->levelsToFetch);
                } else {
                    $entryPointRecord = $this->pageTreeRepository->getTree($entryPointRecord['uid'], null, $entryPointIds);
                }

                if ($entryPointRecord !== []) {
                    $entryPointRecords[$k] = $entryPointRecord;
                }
            }
        }

        return $entryPointRecords;
    }

    /**
     * Returns the first configured domain name for a page
     */
    protected function getDomainNameForPage(int $pageId): string
    {
        try {
            $site = $this->siteFinder->getSiteByRootPageId($pageId);
            return (string)$site->getBase();
        } catch (SiteNotFoundException) {
            // No site found
        }
        return '';
    }

    /**
     * Returns the mount point path for a temporary mount or the given id
     */
    protected function getMountPointPath(int $uid): string
    {
        if ($uid <= 0) {
            return '';
        }
        $rootline = array_reverse(BackendUtility::BEgetRootLine($uid));
        array_shift($rootline);
        $path = [];
        foreach ($rootline as $rootlineElement) {
            $record = BackendUtility::getRecordWSOL('pages', $rootlineElement['uid'], 'title, nav_title', '', true, true);
            $text = $record['title'];
            if ($this->useNavTitle && trim($record['nav_title'] ?? '') !== '') {
                $text = $record['nav_title'];
            }
            $path[] = htmlspecialchars($text);
        }
        return '/' . implode('/', $path);
    }

    /**
     * Check if drag-move in the svg tree is allowed for the user
     */
    protected function isDragMoveAllowed(): bool
    {
        $backendUser = $this->getBackendUser();
        return $backendUser->isAdmin()
            || ($backendUser->check('tables_modify', 'pages') && $backendUser->checkLanguageAccess(0));
    }

    /**
     * Get allowed mountpoints. Returns temporary mountpoint when temporary mountpoint is used.
     *
     * @return int[]
     */
    protected function getAllowedMountPoints(): array
    {
        $mountPoints = (int)($this->getBackendUser()->uc['pageTree_temporaryMountPoint'] ?? 0);
        if (!$mountPoints) {
            if (!empty($this->alternativeEntryPoints)) {
                return $this->alternativeEntryPoints;
            }
            return $this->getBackendUser()->getWebmounts();
        }
        return [$mountPoints];
    }

    protected function getPostProcessedPageItems(ServerRequestInterface $request, array $items): array
    {
        return array_map(
            static function (array $item): PageTreeItem {
                return new PageTreeItem(
                    // TreeItem
                    new TreeItem(
                        identifier: $item['identifier'],
                        parentIdentifier: (string)($item['parentIdentifier'] ?? ''),
                        recordType: (string)($item['recordType'] ?? ''),
                        name: (string)($item['name'] ?? ''),
                        note: (string)($item['note'] ?? ''),
                        prefix: (string)($item['prefix'] ?? ''),
                        suffix: (string)($item['suffix'] ?? ''),
                        tooltip: (string)($item['tooltip'] ?? ''),
                        depth: (int)($item['depth'] ?? 0),
                        hasChildren: (bool)($item['hasChildren'] ?? false),
                        loaded: (bool)($item['loaded'] ?? false),
                        editable: (bool)($item['editable'] ?? false),
                        deletable: (bool)($item['deletable'] ?? false),
                        icon: (string)($item['icon'] ?? ''),
                        overlayIcon: (string)($item['overlayIcon'] ?? ''),
                        statusInformation: (array)($item['statusInformation'] ?? []),
                        labels: (array)($item['labels'] ?? []),
                    ),
                    // PageTreeItem
                    doktype: (int)($item['doktype'] ?? ''),
                    nameSourceField: (string)($item['nameSourceField'] ?? ''),
                    workspaceId: (int)($item['workspaceId'] ?? 0),
                    locked: (bool)($item['locked'] ?? false),
                    stopPageTree: (bool)($item['stopPageTree'] ?? false),
                    mountPoint: (int)($item['mountPoint'] ?? 0),
                );
            },
            $this->eventDispatcher->dispatch(
                new AfterPageTreeItemsPreparedEvent($request, $items)
            )->getItems()
        );
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
