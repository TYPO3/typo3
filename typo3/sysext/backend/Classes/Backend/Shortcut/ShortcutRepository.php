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

use Symfony\Component\Routing\Route;
use TYPO3\CMS\Backend\Module\ModuleLoader;
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
use TYPO3\CMS\Core\Resource\StorageRepository;
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

    protected const TABLE_NAME = 'sys_be_shortcuts';

    protected array $shortcuts;

    protected array $shortcutGroups;

    protected ConnectionPool $connectionPool;

    protected IconFactory $iconFactory;

    protected ModuleLoader $moduleLoader;

    public function __construct(ConnectionPool $connectionPool, IconFactory $iconFactory, ModuleLoader $moduleLoader)
    {
        $this->connectionPool = $connectionPool;
        $this->iconFactory = $iconFactory;
        $this->moduleLoader = $moduleLoader;
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
            $groups[$shortcut['group']] = $this->shortcutGroups[$shortcut['group']] ?? '';
        }

        return array_unique($groups);
    }

    /**
     * Returns if there already is a shortcut entry for a given TYPO3 URL
     *
     * @param string $routeIdentifier
     * @param string $arguments
     * @return bool
     */
    public function shortcutExists(string $routeIdentifier, string $arguments): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();

        $uid = $queryBuilder->select('uid')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($this->getBackendUser()->user['uid'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('route', $queryBuilder->createNamedParameter($routeIdentifier)),
                $queryBuilder->expr()->eq('arguments', $queryBuilder->createNamedParameter($arguments))
            )
            ->executeQuery()
            ->fetchOne();

        return (bool)$uid;
    }

    /**
     * Add a shortcut
     *
     * @param string $routeIdentifier route identifier of the new shortcut
     * @param string $arguments arguments of the new shortcut
     * @param string $title title of the new shortcut
     * @return bool
     * @throws \RuntimeException if the given URL is invalid
     */
    public function addShortcut(string $routeIdentifier, string $arguments = '', string $title = ''): bool
    {
        // Do not add shortcuts for routes which do not exist
        if (!$this->routeExists($routeIdentifier)) {
            return false;
        }

        $languageService = $this->getLanguageService();

        // Only apply "magic" if title is not set
        // @todo This is deprecated and can be removed in v12
        if ($title === '') {
            $queryParameters = json_decode($arguments, true);
            $titlePrefix = '';
            $type = 'other';
            $table = '';
            $recordId = 0;
            $pageId = 0;

            if ($queryParameters && is_array($queryParameters['edit'] ?? null)) {
                $table = (string)key($queryParameters['edit']);
                $recordId = (int)key($queryParameters['edit'][$table]);
                $pageId = (int)(BackendUtility::getRecord($table, $recordId)['pid'] ?? 0);
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

            $moduleName = $this->getModuleNameFromRouteIdentifier($routeIdentifier);
            $id = (string)($queryParameters['id'] ?? '');
            if ($moduleName === 'file_FilelistList' && $id !== '') {
                try {
                    $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
                    $resource = $resourceFactory->getObjectFromCombinedIdentifier($queryParameters['id']);
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
        }

        // In case title is still empty try to set the modules short description label
        // @todo This is deprecated and can be removed in v12
        if ($title === '') {
            $moduleLabels = $this->moduleLoader->getLabelsForModule($this->getModuleNameFromRouteIdentifier($routeIdentifier));
            if (!empty($moduleLabels['shortdescription'])) {
                $title = $this->getLanguageService()->sL($moduleLabels['shortdescription']);
            }
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $affectedRows = $queryBuilder
            ->insert(self::TABLE_NAME)
            ->values([
                'userid' => $this->getBackendUser()->user['uid'],
                'route' => $routeIdentifier,
                'arguments' => $arguments,
                'description' => $title ?: 'Shortcut',
                'sorting' => $GLOBALS['EXEC_TIME'],
            ])
            ->executeStatement();

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
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->update(self::TABLE_NAME)
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

        $affectedRows = $queryBuilder->executeStatement();

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

        if ((int)$shortcut['raw']['userid'] === (int)$this->getBackendUser()->user['uid']) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
            $affectedRows = $queryBuilder->delete(self::TABLE_NAME)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                    )
                )
                ->executeStatement();

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
                    $label = (string)$label;
                    $shortcutGroups[$groupId] = strpos($label, 'LLL:') === 0 ? $languageService->sL($label) : $label;
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
        $lastGroup = 0;
        $shortcuts = [];

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $result = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
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
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $pageId = 0;
            $shortcut = ['raw' => $row];
            $routeIdentifier = $row['route'] ?? '';
            $arguments = json_decode($row['arguments'] ?? '', true) ?? [];

            if ($routeIdentifier === 'record_edit' && is_array($arguments['edit'])) {
                $shortcut['table'] = (string)(key($arguments['edit']) ?? '');
                $shortcut['recordid'] = key($arguments['edit'][$shortcut['table']]);

                if ($arguments['edit'][$shortcut['table']][$shortcut['recordid']] === 'edit') {
                    $shortcut['type'] = 'edit';
                } elseif ($arguments['edit'][$shortcut['table']][$shortcut['recordid']] === 'new') {
                    $shortcut['type'] = 'new';
                }

                if (str_ends_with((string)$shortcut['recordid'], ',')) {
                    $shortcut['recordid'] = substr((string)$shortcut['recordid'], 0, -1);
                }
            } else {
                $shortcut['type'] = 'other';
            }

            $moduleName = $this->getModuleNameFromRouteIdentifier($routeIdentifier);

            // Skip shortcut if module name can not be resolved
            if ($moduleName === '') {
                continue;
            }

            // Check if the user has access to this module
            // @todo Hack for EditDocumentController / FormEngine, see issues #91368 and #91210
            if ($routeIdentifier !== 'record_edit' && !is_array($this->moduleLoader->checkMod($moduleName))) {
                continue;
            }

            if ($moduleName === 'file_FilelistList') {
                $combinedIdentifier = (string)($arguments['id'] ?? '');
                if ($combinedIdentifier !== '') {
                    $storage = GeneralUtility::makeInstance(StorageRepository::class)->findByCombinedIdentifier($combinedIdentifier);
                    if ($storage === null || $storage->getUid() === 0) {
                        // Continue, if invalid storage or disallowed fallback storage
                        continue;
                    }
                    $folderIdentifier = substr($combinedIdentifier, strpos($combinedIdentifier, ':') + 1);
                    try {
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
                if ($moduleName === 'record_edit' && isset($shortcut['table'], $shortcut['recordid'])) {
                    // Check if user is allowed to modify the requested record
                    if (!$backendUser->check('tables_modify', $shortcut['table'])) {
                        continue;
                    }
                    if ($shortcut['type'] === 'edit'
                        || ($shortcut['type'] === 'new' && (int)$shortcut['recordid'] < 0)
                    ) {
                        $record = BackendUtility::getRecord($shortcut['table'], abs((int)$shortcut['recordid']));
                        // Check if requested record exists
                        if ($record === null || $record === []) {
                            continue;
                        }
                        // Store the page id of the record in question
                        $pageId = ($shortcut['table'] === 'pages' ? (int)($record['uid'] ?? 0) : (int)($record['pid'] ?? 0));
                    } elseif ($shortcut['type'] === 'new' && (int)$shortcut['recordid'] > 0) {
                        // If type is new and "recordid" is positive, it references the current page
                        $pageId = (int)$shortcut['recordid'];
                    }
                } else {
                    // In case this is no record edit shortcut, treat a possible "id" as page id
                    $pageId = (int)($arguments['id'] ?? 0);
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

            $shortcutGroup = (int)$row['sc_group'];
            if ($shortcutGroup && $lastGroup !== $shortcutGroup && $shortcutGroup !== self::SUPERGLOBAL_GROUP) {
                $shortcut['groupLabel'] = $this->getShortcutGroupLabel($shortcutGroup);
            }
            $lastGroup = $shortcutGroup;

            $description = $row['description'] ?? '';
            // Empty description should usually never happen since not defining such, is deprecated and a
            // fallback is in place, at least for v11. Only manual inserts could lead to an empty description.
            // @todo Can be removed in v12 since setting a display name is mandatory then
            if ($description === '') {
                $moduleLabel = (string)($this->moduleLoader->getLabelsForModule($moduleName)['shortdescription'] ?? '');
                if ($moduleLabel !== '') {
                    $description = $this->getLanguageService()->sL($moduleLabel);
                }
            }

            $shortcutUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute($routeIdentifier, $arguments);

            $shortcut['group'] = $shortcutGroup;
            $shortcut['icon'] = $this->getShortcutIcon($routeIdentifier, $moduleName, $shortcut);
            $shortcut['label'] = $description;
            $shortcut['href'] = $shortcutUrl;
            $shortcut['route'] = $routeIdentifier;
            $shortcut['module'] = $moduleName;
            $shortcut['pageId'] = $pageId;
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
     * @param string $routeIdentifier
     * @param string $moduleName
     * @param array $shortcut
     * @return string Shortcut icon as img tag
     */
    protected function getShortcutIcon(string $routeIdentifier, string $moduleName, array $shortcut): string
    {
        switch ($routeIdentifier) {
            case 'record_edit':
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

                if (str_contains($moduleName, '_')) {
                    [$mainModule, $subModule] = explode('_', $moduleName, 2);
                    $iconIdentifier = $this->moduleLoader->getModules()[$mainModule]['sub'][$subModule]['iconIdentifier'] ?? '';
                } elseif ($moduleName !== '') {
                    $iconIdentifier = $this->moduleLoader->getModules()[$moduleName]['iconIdentifier'] ?? '';
                }

                if (!$iconIdentifier) {
                    $iconIdentifier = 'empty-empty';
                }

                $icon = $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render();
        }

        return $icon;
    }

    /**
     * Get the module name from the resolved route or by static mapping for some special cases.
     *
     * @param string $routeIdentifier
     * @return string
     */
    protected function getModuleNameFromRouteIdentifier(string $routeIdentifier): string
    {
        if ($this->isSpecialRoute($routeIdentifier)) {
            return $routeIdentifier;
        }

        $route = $this->getRoute($routeIdentifier);
        return $route !== null ? (string)($route->getOption('moduleName') ?? '') : '';
    }

    /**
     * Get the route for a given route identifier
     *
     * @param string $routeIdentifier
     * @return Route|null
     */
    protected function getRoute(string $routeIdentifier): ?Route
    {
        return GeneralUtility::makeInstance(Router::class)->getRoutes()[$routeIdentifier] ?? null;
    }

    /**
     * Check if a route for the given identifier exists
     *
     * @param string $routeIdentifier
     * @return bool
     */
    protected function routeExists(string $routeIdentifier): bool
    {
        return $this->getRoute($routeIdentifier) !== null;
    }

    /**
     * Check if given route identifier is a special "no module" route
     *
     * @param string $routeIdentifier
     * @return bool
     */
    protected function isSpecialRoute(string $routeIdentifier): bool
    {
        return in_array($routeIdentifier, ['record_edit', 'file_edit', 'wizard_rte'], true);
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
