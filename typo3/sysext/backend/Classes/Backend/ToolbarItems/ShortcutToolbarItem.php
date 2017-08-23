<?php
namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

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
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class to render the shortcut menu
 */
class ShortcutToolbarItem implements ToolbarItemInterface
{
    /**
     * @const integer Number of super global group
     */
    const SUPERGLOBAL_GROUP = -100;

    /**
     * @var string
     */
    public $perms_clause;

    /**
     * @var array
     */
    public $fieldArray;

    /**
     * All available shortcuts
     *
     * @var array
     */
    protected $shortcuts;

    /**
     * @var array
     */
    protected $shortcutGroups;

    /**
     * Labels of all groups.
     * If value is 1, the system will try to find a label in the locallang array.
     *
     * @var array
     */
    protected $groupLabels;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var ModuleLoader
     */
    protected $moduleLoader;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_misc.xlf');
        // Needed to get the correct icons when reloading the menu after saving it
        $this->moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
        $this->moduleLoader->load($GLOBALS['TBE_MODULES']);

        // By default, 5 groups are set
        $this->shortcutGroups = [
            1 => '1',
            2 => '1',
            3 => '1',
            4 => '1',
            5 => '1'
        ];
        $this->shortcutGroups = $this->initShortcutGroups();
        $this->shortcuts = $this->initShortcuts();

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Toolbar/ShortcutMenu');
        $languageService = $this->getLanguageService();
        $this->getPageRenderer()->addInlineLanguageLabelArray([
            'bookmark.delete' => $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.bookmarksDelete'),
            'bookmark.confirmDelete' => $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.confirmBookmarksDelete'),
            'bookmark.create' => $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.createBookmark'),
            'bookmark.savedTitle' => $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.bookmarkSavedTitle'),
            'bookmark.savedMessage' => $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.bookmarkSavedMessage'),
        ]);
    }

    /**
     * Checks whether the user has access to this toolbar item
     *
     * @return bool TRUE if user has access, FALSE if not
     */
    public function checkAccess()
    {
        return (bool)$this->getBackendUser()->getTSConfigVal('options.enableBookmarks');
    }

    /**
     * Render shortcut icon
     *
     * @return string HTML
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     * @throws \InvalidArgumentException
     */
    public function getItem()
    {
        return $this->getFluidTemplateObject('Item.html')->render();
    }

    /**
     * Render drop down content
     *
     * @return string HTML
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     * @throws \InvalidArgumentException
     */
    public function getDropDown()
    {
        $shortcutMenu = [];
        $groups = $this->getGroupsFromShortcuts();
        arsort($groups, SORT_NUMERIC);
        foreach ($groups as $groupId => $groupLabel) {
            $shortcutMenu[] = [
                'id' => (int)$groupId,
                'title' => $groupLabel,
                'shortcuts' => $this->getShortcutsByGroup($groupId)
            ];
        }

        $dropDownView = $this->getFluidTemplateObject('DropDown.html');
        $dropDownView->assign('shortcutMenu', $shortcutMenu);
        return $dropDownView->render();
    }

    /**
     * Renders the menu so that it can be returned as response to an AJAX call
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function menuAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $menuContent = $this->getDropDown();

        $response->getBody()->write($menuContent);
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    /**
     * This toolbar item needs no additional attributes
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return [];
    }

    /**
     * This item has a drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return true;
    }

    /**
     * Retrieves the shortcuts for the current user
     *
     * @return array Array of shortcuts
     */
    protected function initShortcuts()
    {
        $backendUser = $this->getBackendUser();
        // Traverse shortcuts
        $lastGroup = 0;
        $shortcuts = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_be_shortcuts');
        $result = $queryBuilder->select('*')
            ->from('sys_be_shortcuts')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'userid',
                        $queryBuilder->createNamedParameter($backendUser->user['uid'], \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gte(
                        'sc_group',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            )
            ->orWhere(
                $queryBuilder->expr()->in(
                    'sc_group',
                    $queryBuilder->createNamedParameter(
                        array_keys($this->getGlobalShortcutGroups()),
                        Connection::PARAM_INT_ARRAY
                    )
                )
            )
            ->orderBy('sc_group')
            ->addOrderBy('sorting')
            ->execute();

        while ($row = $result->fetch()) {
            $shortcut = ['raw' => $row];

            list($row['module_name'], $row['M_module_name']) = explode('|', $row['module_name']);

            $queryParts = parse_url($row['url']);
            $queryParameters = GeneralUtility::explodeUrl2Array($queryParts['query'], 1);
            if ($row['module_name'] === 'xMOD_alt_doc.php' && is_array($queryParameters['edit'])) {
                $shortcut['table'] = key($queryParameters['edit']);
                $shortcut['recordid'] = key($queryParameters['edit'][$shortcut['table']]);
                if ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] === 'edit') {
                    $shortcut['type'] = 'edit';
                } elseif ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] === 'new') {
                    $shortcut['type'] = 'new';
                }
                if (substr($shortcut['recordid'], -1) === ',') {
                    $shortcut['recordid'] = substr($shortcut['recordid'], 0, -1);
                }
            } else {
                $shortcut['type'] = 'other';
            }
            // Check for module access
            $moduleName = $row['M_module_name'] ?: $row['module_name'];

            // Check if the user has access to this module
            if (!is_array($this->moduleLoader->checkMod($moduleName))) {
                continue;
            }
            $pageId = $this->getLinkedPageId($row['url']);

            if (!$backendUser->isAdmin()) {
                if (MathUtility::canBeInterpretedAsInteger($pageId)) {
                    // Check for webmount access
                    if ($backendUser->isInWebMount($pageId) === null) {
                        continue;
                    }
                    // Check for record access
                    $pageRow = BackendUtility::getRecord('pages', $pageId);
                    if ($pageRow === null) {
                        continue;
                    }
                    if (!$backendUser->doesUserHaveAccess($pageRow, ($perms = 1))) {
                        continue;
                    }
                }
            }
            $moduleParts = explode('_', $moduleName);
            $shortcutGroup = (int)$row['sc_group'];
            if ($shortcutGroup && $lastGroup !== $shortcutGroup && $shortcutGroup !== self::SUPERGLOBAL_GROUP) {
                $shortcut['groupLabel'] = $this->getShortcutGroupLabel($shortcutGroup);
            }
            $lastGroup = $shortcutGroup;

            if ($row['description']) {
                $shortcut['label'] = $row['description'];
            } else {
                $shortcut['label'] = GeneralUtility::fixed_lgd_cs(rawurldecode($queryParts['query']), 150);
            }
            $shortcut['group'] = $shortcutGroup;
            $shortcut['icon'] = $this->getShortcutIcon($row, $shortcut);
            $shortcut['iconTitle'] = $this->getShortcutIconTitle($shortcut['label'], $row['module_name'], $row['M_module_name']);
            $shortcut['action'] = 'jump(' . GeneralUtility::quoteJSvalue($this->getTokenUrl($row['url'])) . ',' . GeneralUtility::quoteJSvalue($moduleName) . ',' . GeneralUtility::quoteJSvalue($moduleParts[0]) . ', ' . (int)$pageId . ');';

            $shortcuts[] = $shortcut;
        }
        return $shortcuts;
    }

    /**
     * Adds the correct token, if the url is an index.php script
     *
     * @param string $url
     * @return string
     */
    protected function getTokenUrl($url)
    {
        $parsedUrl = parse_url($url);
        parse_str($parsedUrl['query'], $parameters);

        // parse the returnUrl and replace the module token of it
        if (isset($parameters['returnUrl'])) {
            $parsedReturnUrl = parse_url($parameters['returnUrl']);
            parse_str($parsedReturnUrl['query'], $returnUrlParameters);
            if (strpos($parsedReturnUrl['path'], 'index.php') !== false && isset($returnUrlParameters['M'])) {
                $module = $returnUrlParameters['M'];
                $returnUrl = BackendUtility::getModuleUrl($module, $returnUrlParameters);
                $parameters['returnUrl'] = $returnUrl;
                $url = $parsedUrl['path'] . '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
            }
        }

        if (strpos($parsedUrl['path'], 'index.php') !== false && isset($parameters['M'])) {
            $module = $parameters['M'];
            $url = BackendUtility::getModuleUrl($module, $parameters);
        } elseif (strpos($parsedUrl['path'], 'index.php') !== false && isset($parameters['route'])) {
            $routePath = $parameters['route'];
            /** @var \TYPO3\CMS\Backend\Routing\Router $router */
            $router = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\Router::class);
            try {
                $route = $router->match($routePath);
                if ($route) {
                    $routeIdentifier = $route->getOption('_identifier');
                    /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
                    $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
                    unset($parameters['route']);
                    $url = (string)$uriBuilder->buildUriFromRoute($routeIdentifier, $parameters);
                }
            } catch (\TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException $e) {
                $url = '';
            }
        }
        return $url;
    }

    /**
     * Gets shortcuts for a specific group
     *
     * @param int $groupId Group Id
     * @return array Array of shortcuts that matched the group
     */
    protected function getShortcutsByGroup($groupId)
    {
        $shortcuts = [];
        foreach ($this->shortcuts as $shortcut) {
            if ($shortcut['group'] == $groupId) {
                $shortcuts[] = $shortcut;
            }
        }
        return $shortcuts;
    }

    /**
     * Gets a shortcut by its uid
     *
     * @param int $shortcutId Shortcut id to get the complete shortcut for
     * @return mixed An array containing the shortcut's data on success or FALSE on failure
     */
    protected function getShortcutById($shortcutId)
    {
        $returnShortcut = false;
        foreach ($this->shortcuts as $shortcut) {
            if ($shortcut['raw']['uid'] == (int)$shortcutId) {
                $returnShortcut = $shortcut;
                continue;
            }
        }
        return $returnShortcut;
    }

    /**
     * Gets the available shortcut groups from default groups, user TSConfig, and global groups
     *
     * @return array
     */
    protected function initShortcutGroups()
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();
        // Groups from TSConfig
        $bookmarkGroups = $backendUser->getTSConfigProp('options.bookmarkGroups');
        if (is_array($bookmarkGroups) && !empty($bookmarkGroups)) {
            foreach ($bookmarkGroups as $groupId => $label) {
                if (!empty($label)) {
                    $this->shortcutGroups[$groupId] = (string)$label;
                } elseif ($backendUser->isAdmin()) {
                    unset($this->shortcutGroups[$groupId]);
                }
            }
        }
        // Generate global groups, all global groups have negative IDs.
        if (!empty($this->shortcutGroups)) {
            $groups = $this->shortcutGroups;
            foreach ($groups as $groupId => $groupLabel) {
                $this->shortcutGroups[$groupId * -1] = $groupLabel;
            }
        }
        // Group -100 is kind of superglobal and can't be changed.
        $this->shortcutGroups[self::SUPERGLOBAL_GROUP] = 1;
        // Add labels
        foreach ($this->shortcutGroups as $groupId => $groupLabel) {
            $groupId = (int)$groupId;
            $label = $groupLabel;
            if ($groupLabel == '1') {
                $label = htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_misc.xlf:bookmark_group_' . abs($groupId)));
                if (empty($label)) {
                    // Fallback label
                    $label = htmlspecialchars($languageService->getLL('bookmark_group')) . ' ' . abs($groupId);
                }
            }
            if ($groupId < 0) {
                // Global group
                $label = htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_misc.xlf:bookmark_global')) . ': ' . (!empty($label) ? $label : abs($groupId));
                if ($groupId === self::SUPERGLOBAL_GROUP) {
                    $label = htmlspecialchars($languageService->getLL('bookmark_global')) . ': ' . htmlspecialchars($languageService->getLL('bookmark_all'));
                }
            }
            $this->shortcutGroups[$groupId] = $label;
        }

        return $this->shortcutGroups;
    }

    /**
     * Fetches the available shortcut groups, renders a form so it can be saved later on, usually called via AJAX
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface the full HTML for the form
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function editFormAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $selectedShortcutId = (int)($parsedBody['shortcutId'] ?? $queryParams['shortcutId']);
        $selectedShortcutGroupId = (int)($parsedBody['shortcutGroup'] ?? $queryParams['shortcutGroup']);
        $selectedShortcut = $this->getShortcutById($selectedShortcutId);
        $shortcutGroups = $this->shortcutGroups;
        if (!$this->getBackendUser()->isAdmin()) {
            foreach ($shortcutGroups as $groupId => $groupName) {
                if ((int)$groupId < 0) {
                    unset($shortcutGroups[$groupId]);
                }
            }
        }

        $editFormView = $this->getFluidTemplateObject('EditForm.html');
        $editFormView->assign('selectedShortcutId', $selectedShortcutId);
        $editFormView->assign('selectedShortcutGroupId', $selectedShortcutGroupId);
        $editFormView->assign('selectedShortcut', $selectedShortcut);
        $editFormView->assign('shortcutGroups', $shortcutGroups);

        $response->getBody()->write($editFormView->render());
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Deletes a shortcut through an AJAX call
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function removeShortcutAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $shortcutId = (int)(isset($parsedBody['shortcutId']) ? $parsedBody['shortcutId'] : $queryParams['shortcutId']);
        $fullShortcut = $this->getShortcutById($shortcutId);
        $success = false;
        if ($fullShortcut['raw']['userid'] == $this->getBackendUser()->user['uid']) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_be_shortcuts');
            $affectedRows = $queryBuilder->delete('sys_be_shortcuts')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($shortcutId, \PDO::PARAM_INT)
                    )
                )
                ->execute();
            if ($affectedRows === 1) {
                $success = true;
            }
        }
        $response->getBody()->write(json_encode(['success' => $success]));
        return $response;
    }

    /**
     * Creates a shortcut through an AJAX call
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function createShortcutAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $languageService = $this->getLanguageService();
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Default name
        $shortcutName = 'Shortcut';
        $shortcutNamePrepend = '';
        $url = isset($parsedBody['url']) ? $parsedBody['url'] : $queryParams['url'];

        // Use given display name
        if (!empty($parsedBody['displayName'])) {
            $shortcutName = $parsedBody['displayName'];
        }

        // Determine shortcut type
        $url = rawurldecode($url);
        $queryParts = parse_url($url);
        $queryParameters = GeneralUtility::explodeUrl2Array($queryParts['query'], true);

        // Proceed only if no scheme is defined, as URL is expected to be relative
        if (empty($queryParts['scheme'])) {
            if (is_array($queryParameters['edit'])) {
                $shortcut['table'] = key($queryParameters['edit']);
                $shortcut['recordid'] = key($queryParameters['edit'][$shortcut['table']]);
                $shortcut['pid'] = BackendUtility::getRecord($shortcut['table'], $shortcut['recordid'])['pid'];
                if ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] === 'edit') {
                    $shortcut['type'] = 'edit';
                    $shortcutNamePrepend = htmlspecialchars($languageService->getLL('shortcut_edit'));
                } elseif ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] === 'new') {
                    $shortcut['type'] = 'new';
                    $shortcutNamePrepend = htmlspecialchars($languageService->getLL('shortcut_create'));
                }
            } else {
                $shortcut['type'] = 'other';
                $shortcut['table'] = '';
                $shortcut['recordid'] = 0;
            }

            // Check if given id is a combined identifier
            if (!empty($queryParameters['id']) && preg_match('/^[0-9]+:/', $queryParameters['id'])) {
                try {
                    $resourceFactory = ResourceFactory::getInstance();
                    $resource = $resourceFactory->getObjectFromCombinedIdentifier($queryParameters['id']);
                    $shortcutName = trim($shortcutNamePrepend . ' ' . $resource->getName());
                } catch (ResourceDoesNotExistException $e) {
                }
            } else {
                // Lookup the title of this page and use it as default description
                $pageId = (int)($shortcut['pid'] ?: ($shortcut['recordid'] ?: $this->getLinkedPageId($url)));
                $page = false;
                if ($pageId) {
                    $page = BackendUtility::getRecord('pages', $pageId);
                }
                if (!empty($page)) {
                    // Set the name to the title of the page
                    if ($shortcut['type'] === 'other') {
                        if (empty($shortcutName)) {
                            $shortcutName = $page['title'];
                        } else {
                            $shortcutName .= ' (' . $page['title'] . ')';
                        }
                    } else {
                        $shortcutName = $shortcutNamePrepend . ' ' .
                            $languageService->sL($GLOBALS['TCA'][$shortcut['table']]['ctrl']['title']) .
                            ' (' . $page['title'] . ')';
                    }
                } elseif ($shortcut['table'] !== '' && $shortcut['type'] !== 'other') {
                    $shortcutName = $shortcutNamePrepend . ' ' .
                        $languageService->sL($GLOBALS['TCA'][$shortcut['table']]['ctrl']['title']);
                }
            }

            return $this->tryAddingTheShortcut($response, $url, $shortcutName);
        }
    }

    /**
     * Try to adding a shortcut
     *
     * @param ResponseInterface $response
     * @param string $url
     * @param string $shortcutName
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    protected function tryAddingTheShortcut(ResponseInterface $response, $url, $shortcutName)
    {
        $module = GeneralUtility::_POST('module');
        $shortcutCreated = 'failed';

        if (!empty($module) && !empty($url)) {
            $shortcutCreated = 'alreadyExists';

            if (!BackendUtility::shortcutExists($url) && !is_array($module)) {
                $shortcutCreated = $this->addShortcut($url, $shortcutName, $module);
            }
        }

        $response->getBody()->write($shortcutCreated);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Add a shortcut now with some user stuffs
     *
     * @param string $url
     * @param string $shortcutName
     * @param string $module
     *
     * @return string
     */
    protected function addShortcut($url, $shortcutName, $module)
    {
        $moduleLabels = $this->moduleLoader->getLabelsForModule($module);
        if ($shortcutName === 'Shortcut' && !empty($moduleLabels['shortdescription'])) {
            $shortcutName = $this->getLanguageService()->sL($moduleLabels['shortdescription']);
        }

        $motherModule = GeneralUtility::_POST('motherModName');
        $fieldValues = [
            'userid' => $this->getBackendUser()->user['uid'],
            'module_name' => $module . '|' . $motherModule,
            'url' => $url,
            'description' => $shortcutName,
            'sorting' => $GLOBALS['EXEC_TIME']
        ];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_be_shortcuts');
        $affectedRows = $queryBuilder
            ->insert('sys_be_shortcuts')
            ->values($fieldValues)
            ->execute();

        if ($affectedRows === 1) {
            return 'success';
        }
        return 'failed';
    }

    /**
     * Exists already a shortcut entry for this TYPO3 url?
     *
     * @param string $url
     *
     * @return bool
     */
    protected function shortcutExists($url)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_be_shortcuts');
        $uid = $queryBuilder->select('uid')
            ->from('sys_be_shortcuts')
            ->where(
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($this->getBackendUser()->user['uid'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('url', $queryBuilder->createNamedParameter($url, \PDO::PARAM_STR))
            )
            ->execute()
            ->fetchColumn();

        return (bool)$uid;
    }

    /**
     * Gets called when a shortcut is changed, checks whether the user has
     * permissions to do so and saves the changes if everything is ok
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function saveFormAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $backendUser = $this->getBackendUser();
        $shortcutId = (int)(isset($parsedBody['shortcutId']) ? $parsedBody['shortcutId'] : $queryParams['shortcutId']);
        $shortcutName = strip_tags(isset($parsedBody['shortcutTitle']) ? $parsedBody['shortcutTitle'] : $queryParams['shortcutTitle']);
        $shortcutGroupId = (int)(isset($parsedBody['shortcutGroup']) ? $parsedBody['shortcutGroup'] : $queryParams['shortcutGroup']);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_be_shortcuts');
        $queryBuilder->update('sys_be_shortcuts')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($shortcutId, \PDO::PARAM_INT)
                )
            )
            ->set('description', $shortcutName)
            ->set('sc_group', $shortcutGroupId);

        if (!$backendUser->isAdmin()) {
            // Users can only modify their own shortcuts
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($backendUser->user['uid'], \PDO::PARAM_INT)
                )
            );

            if ($shortcutGroupId < 0) {
                $queryBuilder->set('sc_group', 0);
            }
        }

        if ($queryBuilder->execute() === 1) {
            $response->getBody()->write($shortcutName);
        } else {
            $response->getBody()->write('failed');
        }
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Gets the label for a shortcut group
     *
     * @param int $groupId A shortcut group id
     * @return string The shortcut group label, can be an empty string if no group was found for the id
     */
    protected function getShortcutGroupLabel($groupId)
    {
        return isset($this->shortcutGroups[$groupId]) ? $this->shortcutGroups[$groupId] : '';
    }

    /**
     * Gets a list of global groups, shortcuts in these groups are available to all users
     *
     * @return array Array of global groups
     */
    protected function getGlobalShortcutGroups()
    {
        $globalGroups = [];
        foreach ($this->shortcutGroups as $groupId => $groupLabel) {
            if ($groupId < 0) {
                $globalGroups[$groupId] = $groupLabel;
            }
        }
        return $globalGroups;
    }

    /**
     * runs through the available shortcuts an collects their groups
     *
     * @return array Array of groups which have shortcuts
     */
    protected function getGroupsFromShortcuts()
    {
        $groups = [];
        foreach ($this->shortcuts as $shortcut) {
            $groups[$shortcut['group']] = $this->shortcutGroups[$shortcut['group']];
        }
        return array_unique($groups);
    }

    /**
     * Gets the icon for the shortcut
     *
     * @param array $row
     * @param array $shortcut
     * @return string Shortcut icon as img tag
     * @throws \InvalidArgumentException
     */
    protected function getShortcutIcon($row, $shortcut)
    {
        switch ($row['module_name']) {
            case 'xMOD_alt_doc.php':
                $table = $shortcut['table'];
                $recordid = $shortcut['recordid'];
                $icon = '';
                if ($shortcut['type'] === 'edit') {
                    // Creating the list of fields to include in the SQL query:
                    $selectFields = $this->fieldArray;
                    $selectFields[] = 'uid';
                    $selectFields[] = 'pid';
                    if ($table === 'pages') {
                        $selectFields[] = 'module';
                        $selectFields[] = 'extendToSubpages';
                        $selectFields[] = 'doktype';
                    }
                    if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
                        $selectFields = array_merge($selectFields, $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']);
                    }
                    if ($GLOBALS['TCA'][$table]['ctrl']['typeicon_column']) {
                        $selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
                    }
                    if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
                        $selectFields[] = 't3ver_state';
                    }

                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable($table);
                    $queryBuilder->select(...array_unique(array_values($selectFields)))
                        ->from($table)
                        ->where(
                            $queryBuilder->expr()->in(
                                'uid',
                                $queryBuilder->createNamedParameter($recordid, \PDO::PARAM_INT)
                            )
                        );

                    if ($table === 'pages' && $this->perms_clause) {
                        $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($this->perms_clause));
                    }

                    $row = $queryBuilder->execute()->fetch();

                    $icon = $this->iconFactory->getIconForRecord($table, (array)$row, Icon::SIZE_SMALL)->render();
                } elseif ($shortcut['type'] === 'new') {
                    $icon = $this->iconFactory->getIconForRecord($table, [], Icon::SIZE_SMALL)->render();
                }
                break;
            case 'file_edit':
                $icon = $this->iconFactory->getIcon('mimetypes-text-html', Icon::SIZE_SMALL)->render();
                break;
            case 'wizard_rte':
                $icon = $this->iconFactory->getIcon('mimetypes-word', Icon::SIZE_SMALL)->render();
                break;
            default:
                $iconIdentifier = '';
                $moduleName = $row['module_name'];
                if (strpos($moduleName, '_') !== false) {
                    list($mainModule, $subModule) = explode('_', $moduleName, 2);
                    $iconIdentifier = $this->moduleLoader->modules[$mainModule]['sub'][$subModule]['iconIdentifier'];
                } elseif (!empty($moduleName)) {
                    $iconIdentifier = $this->moduleLoader->modules[$moduleName]['iconIdentifier'];
                }
                if (!$iconIdentifier) {
                    $iconIdentifier = 'empty-empty';
                }
                $icon = $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render();
        }

        return $icon;
    }

    /**
     * Returns title for the shortcut icon
     *
     * @param string $shortcutLabel Shortcut label
     * @param string $moduleName Backend module name (key)
     * @param string $parentModuleName Parent module label
     * @return string Title for the shortcut icon
     */
    protected function getShortcutIconTitle($shortcutLabel, $moduleName, $parentModuleName = '')
    {
        $languageService = $this->getLanguageService();
        if (substr($moduleName, 0, 5) === 'xMOD_') {
            $title = substr($moduleName, 5);
        } else {
            list($mainModule, $subModule) = explode('_', $moduleName);
            $mainModuleLabels = $this->moduleLoader->getLabelsForModule($mainModule);
            $title = $languageService->sL($mainModuleLabels['title']);
            if (!empty($subModule)) {
                $subModuleLabels = $this->moduleLoader->getLabelsForModule($moduleName);
                $title .= '>' . $languageService->sL($subModuleLabels['title']);
            }
        }
        if ($parentModuleName) {
            $title .= ' (' . $parentModuleName . ')';
        }
        $title .= ': ' . $shortcutLabel;
        return $title;
    }

    /**
     * Return the ID of the page in the URL if found.
     *
     * @param string $url The URL of the current shortcut link
     * @return string If a page ID was found, it is returned. Otherwise: 0
     */
    protected function getLinkedPageId($url)
    {
        return preg_replace('/.*[\\?&]id=([^&]+).*/', '$1', $url);
    }

    /**
     * Position relative to others, live search should be very right
     *
     * @return int
     */
    public function getIndex()
    {
        return 20;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns current PageRenderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @param string $templateFilename
     * @return StandaloneView
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     * @throws \InvalidArgumentException
     * @internal param string $templateFile
     */
    protected function getFluidTemplateObject(string $templateFilename): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/ShortcutToolbarItem']);

        $view->setTemplate($templateFilename);

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }
}
