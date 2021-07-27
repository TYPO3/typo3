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

namespace TYPO3\CMS\Backend\Backend\Shortcut;

use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for backend shortcuts
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class ShortcutRepository
{
    /**
     * @var int Number of super global (All) group
     */
    protected const SUPERGLOBAL_GROUP = -100;

    /**
     * @var array
     */
    protected $shortcuts;

    /**
     * @var array
     */
    protected $shortcutGroups;

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
        $this->moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
        $this->moduleLoader->load($GLOBALS['TBE_MODULES']);

        $this->shortcutGroups = $this->initShortcutGroups();
        $this->shortcuts = $this->initShortcuts();
    }

    /**
     * Gets a shortcut by its uid
     *
     * @param int $shortcutId Shortcut id to get the complete shortcut for
     * @return mixed An array containing the shortcut's data on success or FALSE on failure
     */
    public function getShortcutById(int $shortcutId)
    {
        foreach ($this->shortcuts as $shortcut) {
            if ($shortcut['raw']['uid'] === $shortcutId) {
                return $shortcut;
            }
        }

        return false;
    }

    /**
     * Gets shortcuts for a specific group
     *
     * @param int $groupId Group Id
     * @return array Array of shortcuts that matched the group
     */
    public function getShortcutsByGroup(int $groupId): array
    {
        $shortcuts = [];

        foreach ($this->shortcuts as $shortcut) {
            if ($shortcut['group'] === $groupId) {
                $shortcuts[] = $shortcut;
            }
        }

        return $shortcuts;
    }

    /**
     * Get shortcut groups the current user has access to
     *
     * @return array
     */
    public function getShortcutGroups(): array
    {
        $shortcutGroups = $this->shortcutGroups;

        if (!$this->getBackendUser()->isAdmin()) {
            foreach ($shortcutGroups as $groupId => $groupName) {
                if ((int)$groupId < 0) {
                    unset($shortcutGroups[$groupId]);
                }
            }
        }

        return $shortcutGroups;
    }

    /**
     * runs through the available shortcuts and collects their groups
     *
     * @return array Array of groups which have shortcuts
     */
    public function getGroupsFromShortcuts(): array
    {
        $groups = [];

        foreach ($this->shortcuts as $shortcut) {
            $groups[$shortcut['group']] = $this->shortcutGroups[$shortcut['group']];
        }

        return array_unique($groups);
    }

    /**
     * Returns if there already is a shortcut entry for a given TYPO3 URL
     *
     * @param string $url
     * @return bool
     */
    public function shortcutExists(string $url): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_be_shortcuts');
        $queryBuilder->getRestrictions()->removeAll();

        $uid = $queryBuilder->select('uid')
            ->from('sys_be_shortcuts')
            ->where(
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($this->getBackendUser()->user['uid'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'url',
                    $queryBuilder->createNamedParameter($url, \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchColumn();

        return (bool)$uid;
    }

    /**
     * Add a shortcut
     *
     * @param string $url URL of the new shortcut
     * @param string $module module identifier of the new shortcut
     * @param string $parentModule parent module identifier of the new shortcut
     * @param string $title title of the new shortcut
     * @return bool
     * @throws \RuntimeException if the given URL is invalid
     */
    public function addShortcut(string $url, string $module, string $parentModule = '', string $title = ''): bool
    {
        if (empty($url) || empty($module)) {
            return false;
        }

        $queryParts = parse_url($url);
        $queryParameters = [];
        parse_str($queryParts['query'] ?? '', $queryParameters);

        if (!empty($queryParameters['scheme'])) {
            throw new \RuntimeException('Shortcut URLs must be relative', 1518785877);
        }

        $languageService = $this->getLanguageService();
        $title = $title ?: 'Shortcut';
        $titlePrefix = '';
        $type = 'other';
        $table = '';
        $recordId = 0;
        $pageId = 0;

        if (is_array($queryParameters['edit'])) {
            $table = (string)key($queryParameters['edit']);
            $recordId = (int)key($queryParameters['edit'][$table]);
            $pageId = (int)BackendUtility::getRecord($table, $recordId)['pid'];
            $languageFile = 'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf';
            $action = $queryParameters['edit'][$table][$recordId];

            switch ($action) {
                case 'edit':
                    $type = 'edit';
                    $titlePrefix = $languageService->sL($languageFile . ':shortcut_edit');
                    break;
                case 'new':
                    $type = 'new';
                    $titlePrefix = $languageService->sL($languageFile . ':shortcut_create');
                    break;
            }
        }

        $moduleName = $module ?: $parentModule;
        $id = $this->extractIdFromShortcutUrl($moduleName, $url);
        if ($moduleName === 'file_FilelistList' && $id !== '') {
            // If filelist module, check if the id is a valid module identifier
            try {
                $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
                $resource = $resourceFactory->getObjectFromCombinedIdentifier($id);
                $title = trim(sprintf(
                    '%s (%s)',
                    $titlePrefix,
                    $resource->getName()
                ));
            } catch (ResourceDoesNotExistException $e) {
            }
        } else {
            // Lookup the title of this page and use it as default description
            $pageId = $pageId ?: $recordId ?: (int)$id;
            $page = $pageId ? BackendUtility::getRecord('pages', $pageId) : null;

            if (!empty($page)) {
                // Set the name to the title of the page
                if ($type === 'other') {
                    $title = sprintf(
                        '%s (%s)',
                        $title,
                        $page['title']
                    );
                } else {
                    $title = sprintf(
                        '%s %s (%s)',
                        $titlePrefix,
                        $languageService->sL($GLOBALS['TCA'][$table]['ctrl']['title']),
                        $page['title']
                    );
                }
            } elseif (!empty($table)) {
                $title = trim(sprintf(
                    '%s %s',
                    $titlePrefix,
                    $languageService->sL($GLOBALS['TCA'][$table]['ctrl']['title'])
                ));
            }
        }

        if ($title === 'Shortcut') {
            $moduleLabels = $this->moduleLoader->getLabelsForModule($module);

            if (!empty($moduleLabels['shortdescription'])) {
                $title = $this->getLanguageService()->sL($moduleLabels['shortdescription']);
            }
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_be_shortcuts');
        $affectedRows = $queryBuilder
            ->insert('sys_be_shortcuts')
            ->values([
                'userid' => $this->getBackendUser()->user['uid'],
                'module_name' => $module . '|' . $parentModule,
                'url' => $url,
                'description' => $title,
                'sorting' => $GLOBALS['EXEC_TIME'],
            ])
            ->execute();

        return $affectedRows === 1;
    }

    /**
     * Update a shortcut
     *
     * @param int $id identifier of a shortcut
     * @param string $title new title of the shortcut
     * @param int $groupId new group identifier of the shortcut
     * @return bool
     */
    public function updateShortcut(int $id, string $title, int $groupId): bool
    {
        $backendUser = $this->getBackendUser();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_be_shortcuts');
        $queryBuilder->update('sys_be_shortcuts')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                )
            )
            ->set('description', $title)
            ->set('sc_group', $groupId);

        if (!$backendUser->isAdmin()) {
            // Users can only modify their own shortcuts
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($backendUser->user['uid'], \PDO::PARAM_INT)
                )
            );

            if ($groupId < 0) {
                $queryBuilder->set('sc_group', 0);
            }
        }

        $affectedRows = $queryBuilder->execute();

        return $affectedRows === 1;
    }

    /**
     * Remove a shortcut
     *
     * @param int $id identifier of a shortcut
     * @return bool
     */
    public function removeShortcut(int $id): bool
    {
        $shortcut = $this->getShortcutById($id);
        $success = false;

        if ($shortcut['raw']['userid'] == $this->getBackendUser()->user['uid']) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_be_shortcuts');
            $affectedRows = $queryBuilder->delete('sys_be_shortcuts')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                    )
                )
                ->execute();

            if ($affectedRows === 1) {
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Gets the available shortcut groups from default groups, user TSConfig, and global groups
     *
     * @return array
     */
    protected function initShortcutGroups(): array
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();
        // By default, 5 groups are set
        $shortcutGroups = [
            1 => '1',
            2 => '1',
            3 => '1',
            4 => '1',
            5 => '1',
        ];

        // Groups from TSConfig
        $bookmarkGroups = $backendUser->getTSConfig()['options.']['bookmarkGroups.'] ?? [];

        if (is_array($bookmarkGroups)) {
            foreach ($bookmarkGroups as $groupId => $label) {
                if (!empty($label)) {
                    $shortcutGroups[$groupId] = (string)$label;
                } elseif ($backendUser->isAdmin()) {
                    unset($shortcutGroups[$groupId]);
                }
            }
        }

        // Generate global groups, all global groups have negative IDs.
        if (!empty($shortcutGroups)) {
            foreach ($shortcutGroups as $groupId => $groupLabel) {
                $shortcutGroups[$groupId * -1] = $groupLabel;
            }
        }

        // Group -100 is kind of superglobal and can't be changed.
        $shortcutGroups[self::SUPERGLOBAL_GROUP] = '1';

        // Add labels
        $languageFile = 'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf';

        foreach ($shortcutGroups as $groupId => $groupLabel) {
            $groupId = (int)$groupId;
            $label = $groupLabel;

            if ($groupLabel === '1') {
                $label = $languageService->sL($languageFile . ':bookmark_group_' . abs($groupId));

                if (empty($label)) {
                    // Fallback label
                    $label = $languageService->sL($languageFile . ':bookmark_group') . ' ' . abs($groupId);
                }
            }

            if ($groupId < 0) {
                // Global group
                $label = $languageService->sL($languageFile . ':bookmark_global') . ': ' . (!empty($label) ? $label : abs($groupId));

                if ($groupId === self::SUPERGLOBAL_GROUP) {
                    $label = $languageService->sL($languageFile . ':bookmark_global') . ': ' . $languageService->sL($languageFile . ':bookmark_all');
                }
            }

            $shortcutGroups[$groupId] = htmlspecialchars($label);
        }

        return $shortcutGroups;
    }

    /**
     * Retrieves the shortcuts for the current user
     *
     * @return array Array of shortcuts
     */
    protected function initShortcuts(): array
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
            $pageId = 0;
            $shortcut = ['raw' => $row];

            [$row['module_name'], $row['M_module_name']] = explode('|', $row['module_name']);

            $queryParts = parse_url($row['url']);
            // Explode GET vars recursively
            $queryParameters = [];
            parse_str($queryParts['query'] ?? '', $queryParameters);

            if ($row['module_name'] === 'xMOD_alt_doc.php' && is_array($queryParameters['edit'])) {
                $shortcut['table'] = key($queryParameters['edit']);
                $shortcut['recordid'] = key($queryParameters['edit'][$shortcut['table']]);

                if ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] === 'edit') {
                    $shortcut['type'] = 'edit';
                } elseif ($queryParameters['edit'][$shortcut['table']][$shortcut['recordid']] === 'new') {
                    $shortcut['type'] = 'new';
                }

                if (substr((string)$shortcut['recordid'], -1) === ',') {
                    $shortcut['recordid'] = substr((string)$shortcut['recordid'], 0, -1);
                }
            } else {
                $shortcut['type'] = 'other';
            }

            // Check for module access
            $moduleName = $row['M_module_name'] ?: $row['module_name'];

            // Check if the user has access to this module
            // @todo Hack for EditDocumentController / FormEngine, see issues #91368 and #91210
            if (!is_array($this->moduleLoader->checkMod($moduleName)) && $moduleName !== 'xMOD_alt_doc.php') {
                continue;
            }

            if ($moduleName === 'file_FilelistList') {
                $combinedIdentifier = $this->extractIdFromShortcutUrl($moduleName, $row['url'] ?? '');
                if ($combinedIdentifier !== '') {
                    try {
                        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
                        $storage = $resourceFactory->getStorageObjectFromCombinedIdentifier($combinedIdentifier);
                        if ($storage === null || $storage->getUid() === 0) {
                            // Continue, if invalid storage or disallowed fallback storage
                            continue;
                        }
                        $folderIdentifier = substr($combinedIdentifier, strpos($combinedIdentifier, ':') + 1);
                        // By using $storage->getFolder() we implicitly check whether the folder
                        // still exists and the user has necessary permissions to access it.
                        $storage->getFolder($folderIdentifier);
                    } catch (InsufficientFolderAccessPermissionsException $e) {
                        // Continue, since current user does not have access to the folder
                        continue;
                    } catch (FolderDoesNotExistException $e) {
                        // Folder does not longer exists. However, the shortcut
                        // is still displayed, allowing the user to remove it.
                    }
                }
            } else {
                if ($moduleName === 'xMOD_alt_doc.php' && isset($shortcut['table'], $shortcut['recordid'])) {
                    // Check if user is allowed to edit the requested record
                    if (!$backendUser->check('tables_modify', $shortcut['table'])) {
                        continue;
                    }
                    // if type new we get the page id in $shortcut['recordid']
                    if($shortcut['type']=='new'){
                        if((int)$shortcut['recordid']==0) {
                            //root page
                            $pageId = 0;
                        }else {
                            $pageRecord = BackendUtility::getRecord('pages', (int)$shortcut['recordid']);
                            //page exists?
                            if ($pageRecord === null || $pageRecord === []) {
                                continue;
                            }
                            $pageId = $pageRecord['uid'];
                        }
                    }else {

                        $record = BackendUtility::getRecord($shortcut['table'], (int)$shortcut['recordid']);
                        // Check if requested record exists
                        if ($record === null || $record === []) {
                            continue;
                        }
                        // Store the page id of the record in question
                        $pageId = ($shortcut['table'] === 'pages' ? (int)($record['uid'] ?? 0) : (int)($record['pid']));
                    }
                } else {
                    // In case this is no record edit shortcut, treat a possible "id" as page id
                    $pageId = (int)$this->extractIdFromShortcutUrl($moduleName, $row['url'] ?? '');
                }
                if ($pageId > 0 && !$backendUser->isAdmin()) {
                    // Check for webmount access
                    if ($backendUser->isInWebMount($pageId) === null) {
                        continue;
                    }
                    // Check for record access
                    $pageRow = BackendUtility::getRecord('pages', $pageId);
                    if ($pageRow === null || !$backendUser->doesUserHaveAccess($pageRow, Permission::PAGE_SHOW)) {
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
            $shortcut['action'] = 'jump(' . GeneralUtility::quoteJSvalue($this->getTokenUrl($row['url'])) . ',' . GeneralUtility::quoteJSvalue($moduleName) . ',' . GeneralUtility::quoteJSvalue($moduleParts[0]) . ', ' . $pageId . ');';

            $shortcuts[] = $shortcut;
        }

        return $shortcuts;
    }

    /**
     * Gets a list of global groups, shortcuts in these groups are available to all users
     *
     * @return array Array of global groups
     */
    protected function getGlobalShortcutGroups(): array
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
     * Gets the label for a shortcut group
     *
     * @param int $groupId A shortcut group id
     * @return string The shortcut group label, can be an empty string if no group was found for the id
     */
    protected function getShortcutGroupLabel(int $groupId): string
    {
        return $this->shortcutGroups[$groupId] ?? '';
    }

    /**
     * Gets the icon for the shortcut
     *
     * @param array $row
     * @param array $shortcut
     * @return string Shortcut icon as img tag
     */
    protected function getShortcutIcon(array $row, array $shortcut): string
    {
        switch ($row['module_name']) {
            case 'xMOD_alt_doc.php':
                $table = $shortcut['table'];
                $recordid = $shortcut['recordid'];
                $icon = '';

                if ($shortcut['type'] === 'edit') {
                    $row = BackendUtility::getRecordWSOL($table, $recordid) ?? [];
                    $icon = $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render();
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
                    [$mainModule, $subModule] = explode('_', $moduleName, 2);
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
    protected function getShortcutIconTitle(string $shortcutLabel, string $moduleName, string $parentModuleName = ''): string
    {
        $languageService = $this->getLanguageService();

        if (strpos($moduleName, 'xMOD_') === 0) {
            $title = substr($moduleName, 5);
        } else {
            [$mainModule, $subModule] = explode('_', $moduleName);
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
     * Get the id from the shortcut url. This could
     * either be the page id or the combined identifier.
     *
     * @param string $moduleName
     * @param string $shortcutUrl
     * @return string
     */
    protected function extractIdFromShortcutUrl(string $moduleName, string $shortcutUrl): string
    {
        $parsedUrl = parse_url($shortcutUrl);
        $queryParams = [];
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $id = (string)($queryParams['id'] ?? '');

        if ($id === '' && $moduleName === 'xMOD_alt_doc.php' && ($queryParams['returnUrl'] ?? '') !== '') {
            $parsedReturlUrl = parse_url($queryParams['returnUrl']);
            $returnUrlQueryParams = [];
            parse_str($parsedReturlUrl['query'] ?? '', $returnUrlQueryParams);
            $id = (string)($returnUrlQueryParams['id'] ?? '');
        }

        return $id;
    }

    /**
     * Adds the correct token, if the url is an index.php script
     * @todo: this needs love
     *
     * @param string $url
     * @return string
     */
    protected function getTokenUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $parameters = [];
        parse_str($parsedUrl['query'] ?? '', $parameters);

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        // parse the returnUrl and replace the module token of it
        if (!empty($parameters['returnUrl'])) {
            $parsedReturnUrl = parse_url($parameters['returnUrl']);
            $returnUrlParameters = [];
            parse_str($parsedReturnUrl['query'] ?? '', $returnUrlParameters);

            if (strpos($parsedReturnUrl['path'] ?? '', 'index.php') !== false && !empty($returnUrlParameters['route'])) {
                $module = $returnUrlParameters['route'];
                $parameters['returnUrl'] = (string)$uriBuilder->buildUriFromRoutePath($module, $returnUrlParameters);
                $url = $parsedUrl['path'] . '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
            }
        }

        if (strpos($parsedUrl['path'], 'index.php') !== false && isset($parameters['route'])) {
            $routePath = $parameters['route'];
            /** @var \TYPO3\CMS\Backend\Routing\Router $router */
            $router = GeneralUtility::makeInstance(Router::class);

            try {
                $route = $router->match($routePath);

                if ($route) {
                    $routeIdentifier = $route->getOption('_identifier');
                    unset($parameters['route']);
                    $url = (string)$uriBuilder->buildUriFromRoute($routeIdentifier, $parameters);
                }
            } catch (ResourceNotFoundException $e) {
                $url = '';
            }
        }
        return $url;
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
