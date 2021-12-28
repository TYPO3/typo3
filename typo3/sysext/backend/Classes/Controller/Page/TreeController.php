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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Configuration\BackendUserConfiguration;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Query\Restriction\DocumentTypeExclusionRestriction;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Controller providing data to the page tree
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class TreeController
{
    /**
     * Option to use the nav_title field for outputting in the tree items, set via userTS.
     *
     * @var bool
     */
    protected $useNavTitle = false;

    /**
     * Option to prefix the page ID when outputting the tree items, set via userTS.
     *
     * @var bool
     */
    protected $addIdAsPrefix = false;

    /**
     * Option to prefix the domain name of sys_domains when outputting the tree items, set via userTS.
     *
     * @var bool
     */
    protected $addDomainName = false;

    /**
     * Option to add the rootline path above each mount point, set via userTS.
     *
     * @var bool
     */
    protected $showMountPathAboveMounts = false;

    /**
     * An array of background colors for a branch in the tree, set via userTS.
     *
     * @var array
     */
    protected $backgroundColors = [];

    /**
     * A list of pages not to be shown.
     *
     * @var array
     */
    protected $hiddenRecords = [];

    /**
     * Contains the state of all items that are expanded.
     *
     * @var array
     */
    protected $expandedState = [];

    /**
     * Instance of the icon factory, to be used for generating the items.
     *
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Number of tree levels which should be returned on the first page tree load
     *
     * @var int
     */
    protected $levelsToFetch = 2;

    /**
     * When set to true all nodes returend by API will be expanded
     * @var bool
     */
    protected $expandAllNodes = false;

    /**
     * Used in the record link picker to limit the page tree only to a specific list
     * of alternative entry points for selecting only from a list of pages
     */
    protected array $alternativeEntryPoints = [];

    protected UriBuilder $uriBuilder;

    /**
     * Constructor to set up common objects needed in various places.
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    }

    protected function initializeConfiguration(ServerRequestInterface $request)
    {
        if ($request->getQueryParams()['readOnly'] ?? false) {
            $this->getBackendUser()->initializeWebmountsForElementBrowser();
        }
        if ($request->getQueryParams()['alternativeEntryPoints'] ?? false) {
            $this->alternativeEntryPoints = $request->getQueryParams()['alternativeEntryPoints'];
            $this->alternativeEntryPoints = array_filter($this->alternativeEntryPoints, function ($pageId) {
                return $this->getBackendUser()->isInWebMount($pageId) !== null;
            });
            $this->alternativeEntryPoints = array_map('intval', $this->alternativeEntryPoints);
            $this->alternativeEntryPoints = array_unique($this->alternativeEntryPoints);
        }
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $this->hiddenRecords = GeneralUtility::intExplode(
            ',',
            $userTsConfig['options.']['hideRecords.']['pages'] ?? '',
            true
        );
        $this->backgroundColors = $userTsConfig['options.']['pageTree.']['backgroundColor.'] ?? [];
        $this->addIdAsPrefix = (bool)($userTsConfig['options.']['pageTree.']['showPageIdWithTitle'] ?? false);
        $this->addDomainName = (bool)($userTsConfig['options.']['pageTree.']['showDomainNameWithTitle'] ?? false);
        $this->useNavTitle = (bool)($userTsConfig['options.']['pageTree.']['showNavTitle'] ?? false);
        $this->showMountPathAboveMounts = (bool)($userTsConfig['options.']['pageTree.']['showPathAboveMounts'] ?? false);
        $backendUserConfiguration = GeneralUtility::makeInstance(BackendUserConfiguration::class);
        $backendUserPageTreeState = $backendUserConfiguration->get('BackendComponents.States.Pagetree');
        if (is_object($backendUserPageTreeState) && is_object($backendUserPageTreeState->stateHash)) {
            $this->expandedState = (array)$backendUserPageTreeState->stateHash;
        } else {
            $stateHash = $backendUserPageTreeState['stateHash'] ?? [];
            $this->expandedState = is_array($stateHash) ? $stateHash : [];
        }
    }

    /**
     * Returns page tree configuration in JSON
     *
     * @return ResponseInterface
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
        $entryPoints = $request->getQueryParams()['alternativeEntryPoints'] ?? '';
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
     *
     * @return array
     */
    protected function getDokTypes(): array
    {
        $backendUser = $this->getBackendUser();
        $doktypeLabelMap = [];
        foreach ($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] as $doktypeItemConfig) {
            if ($doktypeItemConfig[1] === '--div--') {
                continue;
            }
            $doktypeLabelMap[$doktypeItemConfig[1]] = $doktypeItemConfig[0];
        }
        $doktypes = GeneralUtility::intExplode(',', $backendUser->getTSConfig()['options.']['pageTree.']['doktypesToShowInNewPageDragArea'] ?? '', true);
        $doktypes = array_unique($doktypes);
        $output = [];
        $allowedDoktypes = GeneralUtility::intExplode(',', $backendUser->groupData['pagetypes_select'], true);
        $isAdmin = $backendUser->isAdmin();
        // Early return if backend user may not create any doktype
        if (!$isAdmin && empty($allowedDoktypes)) {
            return $output;
        }
        foreach ($doktypes as $doktype) {
            if (!$isAdmin && !in_array($doktype, $allowedDoktypes, true)) {
                continue;
            }
            $label = htmlspecialchars($this->getLanguageService()->sL($doktypeLabelMap[$doktype]));
            $output[] = [
                'nodeType' => $doktype,
                'icon' => $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$doktype] ?? '',
                'title' => $label,
                'tooltip' => $label,
            ];
        }
        return $output;
    }

    /**
     * Returns JSON representing page tree
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function fetchDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->initializeConfiguration($request);

        $items = [];
        if (!empty($request->getQueryParams()['pid'])) {
            // Fetching a part of a page tree
            $entryPoints = $this->getAllEntryPointPageTrees((int)$request->getQueryParams()['pid']);
            $mountPid = (int)($request->getQueryParams()['mount'] ?? 0);
            $parentDepth = (int)($request->getQueryParams()['pidDepth'] ?? 0);
            $this->levelsToFetch = $parentDepth + $this->levelsToFetch;
            foreach ($entryPoints as $page) {
                $items = array_merge($items, $this->pagesToFlatArray($page, $mountPid, $parentDepth));
            }
        } else {
            $entryPoints = $this->getAllEntryPointPageTrees();
            foreach ($entryPoints as $page) {
                $items = array_merge($items, $this->pagesToFlatArray($page, (int)$page['uid']));
            }
        }

        return new JsonResponse($items);
    }

    /**
     * Returns JSON representing page tree filtered by keyword
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
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
                $items = array_merge($items, $this->pagesToFlatArray($page, (int)$page['uid']));
            }
        }

        return new JsonResponse($items);
    }

    /**
     * Sets a temporary mount point
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
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
     * @param array $inheritedData
     * @return array
     */
    protected function pagesToFlatArray(array $page, int $entryPoint, int $depth = 0, array $inheritedData = []): array
    {
        $backendUser = $this->getBackendUser();
        $pageId = (int)$page['uid'];
        if (in_array($pageId, $this->hiddenRecords, true)) {
            return [];
        }

        $stopPageTree = !empty($page['php_tree_stop']) && $depth > 0;
        $identifier = $entryPoint . '_' . $pageId;
        $expanded = !empty($page['expanded'])
            || (isset($this->expandedState[$identifier]) && $this->expandedState[$identifier])
            || $this->expandAllNodes;

        $backgroundColor = !empty($this->backgroundColors[$pageId]) ? $this->backgroundColors[$pageId] : ($inheritedData['backgroundColor'] ?? '');

        $suffix = '';
        $prefix = '';
        $nameSourceField = 'title';
        $visibleText = $page['title'];
        $tooltip = BackendUtility::titleAttribForPages($page, '', false);
        if ($pageId !== 0) {
            $icon = $this->iconFactory->getIconForRecord('pages', $page, Icon::SIZE_SMALL);
        } else {
            $icon = $this->iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL);
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
            $prefix = htmlspecialchars('[' . $pageId . '] ');
        }

        $items = [];
        $item = [
            // Used to track if the tree item is collapsed or not
            'stateIdentifier' => $identifier,
            // identifier is not only used for pages, therefore it's a string
            'identifier' => (string)$pageId,
            'depth' => $depth,
            // fine in JSON - if used in HTML directly, e.g. quotes can be used for XSS
            'tip' => strip_tags(htmlspecialchars_decode($tooltip)),
            'icon' => $icon->getIdentifier(),
            'name' => $visibleText,
            'type' => (int)($page['doktype'] ?? 0),
            'nameSourceField' => $nameSourceField,
            'mountPoint' => $entryPoint,
            'workspaceId' => !empty($page['t3ver_oid']) ? $page['t3ver_oid'] : $pageId,
            'siblingsCount' => $page['siblingsCount'] ?? 1,
            'siblingsPosition' => $page['siblingsPosition'] ?? 1,
            'allowDelete' => $backendUser->doesUserHaveAccess($page, Permission::PAGE_DELETE),
            'allowEdit' => $backendUser->doesUserHaveAccess($page, Permission::PAGE_EDIT)
                && $backendUser->check('tables_modify', 'pages')
                && $backendUser->checkLanguageAccess(0),
        ];

        if (!empty($page['_children']) || $this->getPageTreeRepository()->hasChildren($pageId)) {
            $item['hasChildren'] = true;
            if ($depth >= $this->levelsToFetch) {
                $page = $this->getPageTreeRepository()->getTreeLevels($page, 1);
            }
        }
        if (!empty($prefix)) {
            $item['prefix'] = htmlspecialchars($prefix);
        }
        if (!empty($suffix)) {
            $item['suffix'] = htmlspecialchars($suffix);
        }
        if (is_array($lockInfo)) {
            $item['locked'] = true;
        }
        if ($icon->getOverlayIcon()) {
            $item['overlayIcon'] = $icon->getOverlayIcon()->getIdentifier();
        }
        if ($expanded && is_array($page['_children']) && !empty($page['_children'])) {
            $item['expanded'] = $expanded;
        }
        if ($backgroundColor) {
            $item['backgroundColor'] = htmlspecialchars($backgroundColor);
        }
        if ($stopPageTree) {
            $item['stopPageTree'] = $stopPageTree;
        }
        $class = $this->resolvePageCssClassNames($page);
        if (!empty($class)) {
            $item['class'] = $class;
        }
        if ($depth === 0) {
            $item['isMountPoint'] = true;

            if ($this->showMountPathAboveMounts) {
                $item['readableRootline'] = $this->getMountPointPath($pageId);
            }
        }

        $items[] = $item;
        if (!$stopPageTree && is_array($page['_children']) && !empty($page['_children']) && ($depth < $this->levelsToFetch || $expanded)) {
            $siblingsCount = count($page['_children']);
            $siblingsPosition = 0;
            $items[key($items)]['loaded'] = true;
            foreach ($page['_children'] as $child) {
                $child['siblingsCount'] = $siblingsCount;
                $child['siblingsPosition'] = ++$siblingsPosition;
                $items = array_merge($items, $this->pagesToFlatArray($child, $entryPoint, $depth + 1, ['backgroundColor' => $backgroundColor]));
            }
        }
        return $items;
    }

    protected function getPageTreeRepository(): PageTreeRepository
    {
        $backendUser = $this->getBackendUser();
        $userTsConfig = $backendUser->getTSConfig();
        $excludedDocumentTypes = GeneralUtility::intExplode(',', $userTsConfig['options.']['pageTree.']['excludeDoktypes'] ?? '', true);

        $additionalQueryRestrictions = [];
        if (!empty($excludedDocumentTypes)) {
            $additionalQueryRestrictions[] = GeneralUtility::makeInstance(DocumentTypeExclusionRestriction::class, $excludedDocumentTypes);
        }

        return GeneralUtility::makeInstance(
            PageTreeRepository::class,
            (int)$backendUser->workspace,
            [],
            $additionalQueryRestrictions
        );
    }

    /**
     * Fetches all pages for all tree entry points the user is allowed to see
     *
     * @param int $startPid
     * @param string $query The search query can either be a string to be found in the title or the nav_title of a page or the uid of a page.
     * @return array
     */
    protected function getAllEntryPointPageTrees(int $startPid = 0, string $query = ''): array
    {
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

        $repository = $this->getPageTreeRepository();
        $permClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        if ($query !== '') {
            $this->levelsToFetch = 999;
            $repository->fetchFilteredTree(
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
            $mountPoints = array_map('intval', $backendUser->returnWebmounts());
            $mountPoints = array_unique($mountPoints);
            $mountPoints = array_filter($mountPoints, fn ($id) => !in_array($id, $this->hiddenRecords, true));

            // Switch to multiple-entryPoint-mode if the rootPage is to be mounted.
            // (other mounts would appear duplicated in the pid = 0 tree otherwise)
            if (in_array(0, $mountPoints, true)) {
                $entryPointIds = $mountPoints;
            }
        }

        if ($entryPointIds === null) {
            if ($query !== '') {
                $rootRecord = $repository->getTree(0, null, $mountPoints, true);
            } else {
                $rootRecord = $repository->getTreeLevels($rootRecord, $this->levelsToFetch, $mountPoints);
            }

            $mountPointOrdering = array_flip($mountPoints);
            if (isset($rootRecord['_children'])) {
                usort($rootRecord['_children'], static function ($a, $b) use ($mountPointOrdering) {
                    return ($mountPointOrdering[$a['uid']] ?? 0) <=> ($mountPointOrdering[$b['uid']] ?? 0);
                });
            }

            $entryPointRecords[] = $rootRecord;
        } else {
            $entryPointIds = array_filter($entryPointIds, fn ($id) => !in_array($id, $this->hiddenRecords, true));
            $this->calculateBackgroundColors($entryPointIds);
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
                    $entryPointRecord = $repository->getTreeLevels($entryPointRecord, $this->levelsToFetch);
                } else {
                    $entryPointRecord = $repository->getTree($entryPointRecord['uid'], null, $entryPointIds, true);
                }

                if (is_array($entryPointRecord) && !empty($entryPointRecord)) {
                    $entryPointRecords[$k] = $entryPointRecord;
                }
            }
        }

        return $entryPointRecords;
    }

    protected function calculateBackgroundColors(array $pageIds)
    {
        foreach ($pageIds as $k => $pageId) {
            if (!empty($this->backgroundColors) && is_array($this->backgroundColors)) {
                try {
                    $entryPointRootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
                } catch (RootLineException $e) {
                    $entryPointRootLine = [];
                }
                foreach ($entryPointRootLine as $rootLineEntry) {
                    $parentUid = $rootLineEntry['uid'];
                    if (!empty($this->backgroundColors[$parentUid]) && empty($this->backgroundColors[$pageId])) {
                        $this->backgroundColors[$pageId] = $this->backgroundColors[$parentUid];
                    }
                }
            }
        }
    }

    /**
     * Returns the first configured domain name for a page
     *
     * @param int $pageId
     * @return string
     */
    protected function getDomainNameForPage(int $pageId): string
    {
        $domain = '';
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        try {
            $site = $siteFinder->getSiteByRootPageId($pageId);
            $domain = (string)$site->getBase();
        } catch (SiteNotFoundException $e) {
            // No site found
        }

        return $domain;
    }

    /**
     * Returns the mount point path for a temporary mount or the given id
     *
     * @param int $uid
     * @return string
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
     * Fetches possible css class names to be used when a record was modified in a workspace
     *
     * @param array $page Page record (workspace overlaid)
     * @return string CSS class names to be applied
     */
    protected function resolvePageCssClassNames(array $page): string
    {
        $classes = [];

        if ($page['uid'] === 0) {
            return '';
        }
        $workspaceId = (int)$this->getBackendUser()->workspace;
        if ($workspaceId > 0 && ExtensionManagementUtility::isLoaded('workspaces')) {
            if ((int)$page['t3ver_wsid'] === $workspaceId
                && ((int)$page['t3ver_oid'] > 0 || (int)$page['t3ver_state'] === VersionState::NEW_PLACEHOLDER)
            ) {
                $classes[] = 'ver-element';
                $classes[] = 'ver-versions';
            } elseif (
                $this->getWorkspaceService()->hasPageRecordVersions(
                    $workspaceId,
                    $page['t3ver_oid'] ?: $page['uid']
                )
            ) {
                $classes[] = 'ver-versions';
            }
        }

        return implode(' ', $classes);
    }

    /**
     * Check if drag-move in the svg tree is allowed for the user
     *
     * @return bool
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
            $mountPoints = array_map('intval', $this->getBackendUser()->returnWebmounts());
            return array_unique($mountPoints);
        }
        return [$mountPoints];
    }

    /**
     * @return WorkspaceService
     */
    protected function getWorkspaceService(): WorkspaceService
    {
        return GeneralUtility::makeInstance(WorkspaceService::class);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService|null
     */
    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
