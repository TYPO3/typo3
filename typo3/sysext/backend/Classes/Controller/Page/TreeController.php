<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller\Page;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Configuration\BackendUserConfiguration;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Site\PseudoSiteFinder;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
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
     * Constructor to set up common objects needed in various places.
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->useNavTitle = (bool)($this->getBackendUser()->getTSConfig()['options.']['pageTree.']['showNavTitle'] ?? false);
    }

    /**
     * Returns page tree configuration in JSON
     *
     * @return ResponseInterface
     */
    public function fetchConfigurationAction(): ResponseInterface
    {
        $configuration = [
            'allowRecursiveDelete' => !empty($this->getBackendUser()->uc['recursiveDelete']),
            'doktypes' => $this->getDokTypes(),
            'displayDeleteConfirmation' => $this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE),
            'temporaryMountPoint' => $this->getMountPointPath((int)($this->getBackendUser()->uc['pageTree_temporaryMountPoint'] ?? 0)),
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
            $label = htmlspecialchars($GLOBALS['LANG']->sL($doktypeLabelMap[$doktype]));
            $output[] = [
                'nodeType' => $doktype,
                'icon' => $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$doktype] ?? '',
                'title' => $label,
                'tooltip' => $label
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
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $this->hiddenRecords = GeneralUtility::intExplode(',', $userTsConfig['options.']['hideRecords.']['pages'] ?? '', true);
        $this->backgroundColors = $userTsConfig['options.']['pageTree.']['backgroundColor.'] ?? [];
        $this->addIdAsPrefix = (bool)($userTsConfig['options.']['pageTree.']['showPageIdWithTitle'] ?? false);
        $this->addDomainName = (bool)($userTsConfig['options.']['pageTree.']['showDomainNameWithTitle'] ?? false);
        $this->showMountPathAboveMounts = (bool)($userTsConfig['options.']['pageTree.']['showPathAboveMounts'] ?? false);
        $backendUserConfiguration = GeneralUtility::makeInstance(BackendUserConfiguration::class);
        $this->expandedState = $backendUserConfiguration->get('BackendComponents.States.Pagetree');
        if (is_object($this->expandedState) && is_object($this->expandedState->stateHash)) {
            $this->expandedState = (array)$this->expandedState->stateHash;
        } else {
            $this->expandedState = $this->expandedState['stateHash'] ?: [];
        }

        // Fetching a part of a pagetree
        if (!empty($request->getQueryParams()['pid'])) {
            $entryPoints = [(int)$request->getQueryParams()['pid']];
        } else {
            $entryPoints = $this->getAllEntryPointPageTrees();
        }
        $items = [];
        foreach ($entryPoints as $page) {
            $items = array_merge($items, $this->pagesToFlatArray($page, (int)$page['uid']));
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
            'mountPointPath' => $this->getMountPointPath($pid)
        ];
        return new JsonResponse($response);
    }

    /**
     * Converts nested tree structure produced by PageTreeRepository to a flat, one level array
     * and also adds visual representation information to the data.
     *
     * @param array $page
     * @param int $entryPoint
     * @param int $depth
     * @param array $inheritedData
     * @return array
     */
    protected function pagesToFlatArray(array $page, int $entryPoint, int $depth = 0, array $inheritedData = []): array
    {
        $pageId = (int)$page['uid'];
        if (in_array($pageId, $this->hiddenRecords, true)) {
            return [];
        }

        $stopPageTree = !empty($page['php_tree_stop']) && $depth > 0;
        $identifier = $entryPoint . '_' . $pageId;
        $expanded = !empty($page['expanded']) || (isset($this->expandedState[$identifier]) && $this->expandedState[$identifier]);
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
            $visibleText = htmlspecialchars('[' . $GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title') . ']');
        }
        $visibleText = GeneralUtility::fixed_lgd_cs($visibleText, (int)$this->getBackendUser()->uc['titleLen'] ?: 40);

        if ($this->addDomainName && $page['is_siteroot']) {
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
        $items[] = [
            // Used to track if the tree item is collapsed or not
            'stateIdentifier' => $identifier,
            'identifier' => $pageId,
            'depth' => $depth,
            'tip' => htmlspecialchars($tooltip),
            'hasChildren' => !empty($page['_children']),
            'icon' => $icon->getIdentifier(),
            'name' => $visibleText,
            'nameSourceField' => $nameSourceField,
            'alias' => htmlspecialchars($page['alias'] ?? ''),
            'prefix' => htmlspecialchars($prefix),
            'suffix' => htmlspecialchars($suffix),
            'locked' => is_array($lockInfo),
            'overlayIcon' => $icon->getOverlayIcon() ? $icon->getOverlayIcon()->getIdentifier() : '',
            'selectable' => true,
            'expanded' => (bool)$expanded,
            'checked' => false,
            'backgroundColor' => htmlspecialchars($backgroundColor),
            'stopPageTree' => $stopPageTree,
            'class' => $this->resolvePageCssClassNames($page),
            'readableRootline' => $depth === 0 && $this->showMountPathAboveMounts ? $this->getMountPointPath($pageId) : '',
            'isMountPoint' => $depth === 0,
            'mountPoint' => $entryPoint,
            'workspaceId' => !empty($page['t3ver_oid']) ? $page['t3ver_oid'] : $pageId,
        ];
        if (!$stopPageTree) {
            foreach ($page['_children'] as $child) {
                $items = array_merge($items, $this->pagesToFlatArray($child, $entryPoint, $depth + 1, ['backgroundColor' => $backgroundColor]));
            }
        }
        return $items;
    }

    /**
     * Fetches all entry points for the page tree that the user is allowed to see
     *
     * @return array
     */
    protected function getAllEntryPointPageTrees(): array
    {
        $backendUser = $this->getBackendUser();
        $repository = GeneralUtility::makeInstance(PageTreeRepository::class, (int)$backendUser->workspace);

        $entryPoints = (int)($backendUser->uc['pageTree_temporaryMountPoint'] ?? 0);
        if ($entryPoints > 0) {
            $entryPoints = [$entryPoints];
        } else {
            $entryPoints = array_map('intval', $backendUser->returnWebmounts());
            $entryPoints = array_unique($entryPoints);
            if (empty($entryPoints)) {
                // use a virtual root
                // the real mount points will be fetched in getNodes() then
                // since those will be the "sub pages" of the virtual root
                $entryPoints = [0];
            }
        }
        if (empty($entryPoints)) {
            return [];
        }

        foreach ($entryPoints as $k => &$entryPoint) {
            if (in_array($entryPoint, $this->hiddenRecords, true)) {
                unset($entryPoints[$k]);
                continue;
            }

            if (!empty($this->backgroundColors) && is_array($this->backgroundColors)) {
                try {
                    $entryPointRootLine = GeneralUtility::makeInstance(RootlineUtility::class, $entryPoint)->get();
                } catch (RootLineException $e) {
                    $entryPointRootLine = [];
                }
                foreach ($entryPointRootLine as $rootLineEntry) {
                    $parentUid = $rootLineEntry['uid'];
                    if (!empty($this->backgroundColors[$parentUid]) && empty($this->backgroundColors[$entryPoint])) {
                        $this->backgroundColors[$entryPoint] = $this->backgroundColors[$parentUid];
                    }
                }
            }

            $entryPoint = $repository->getTree($entryPoint, function ($page) use ($backendUser) {
                // check each page if the user has permission to access it
                return $backendUser->doesUserHaveAccess($page, Permission::PAGE_SHOW);
            });
            if (!is_array($entryPoint)) {
                unset($entryPoints[$k]);
            }
        }

        return $entryPoints;
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
            // No site found, let's see if it is a legacy-pseudo-site
            $pseudoSiteFinder = GeneralUtility::makeInstance(PseudoSiteFinder::class);
            try {
                $site = $pseudoSiteFinder->getSiteByRootPageId($pageId);
                $domain = trim((string)$site->getBase(), '/');
            } catch (SiteNotFoundException $e) {
                // No pseudo-site found either
            }
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
            if ($page['t3ver_oid'] > 0 && (int)$page['t3ver_wsid'] === $workspaceId) {
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
}
