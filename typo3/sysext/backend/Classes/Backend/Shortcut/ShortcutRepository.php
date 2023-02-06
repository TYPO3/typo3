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

use TYPO3\CMS\Backend\Module\ModuleProvider;
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

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly IconFactory $iconFactory,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly Router $router,
        protected readonly UriBuilder $uriBuilder,
    ) {
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
                    $queryBuilder->createNamedParameter($this->getBackendUser()->user['uid'], Connection::PARAM_INT)
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
     * @throws \RuntimeException if the given URL is invalid
     */
    public function addShortcut(string $routeIdentifier, string $arguments = '', string $title = ''): bool
    {
        // Do not add shortcuts for routes which do not exist
        if (!$this->router->hasRoute($routeIdentifier)) {
            return false;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $affectedRows = $queryBuilder
            ->insert(self::TABLE_NAME)
            ->values([
                'userid' => $this->getBackendUser()->user['uid'],
                'route' => $routeIdentifier,
                'arguments' => $arguments,
                'description' => $title ?: 'Shortcut',  // Fall back to "Shortcut", see: initShortcuts()
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
     */
    public function updateShortcut(int $id, string $title, int $groupId): bool
    {
        $backendUser = $this->getBackendUser();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->update(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
                )
            )
            ->set('description', $title)
            ->set('sc_group', $groupId);

        if (!$backendUser->isAdmin()) {
            // Users can only modify their own shortcuts
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($backendUser->user['uid'], Connection::PARAM_INT)
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
                        $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
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
     * Gets the available shortcut groups from default groups, user TSconfig, and global groups
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
                    $shortcutGroups[$groupId] = $languageService->sL($label);
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
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'userid',
                        $queryBuilder->createNamedParameter($backendUser->user['uid'], Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gte(
                        'sc_group',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
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
            if ($routeIdentifier !== 'record_edit' && !$this->moduleProvider->accessGranted($moduleName, $backendUser)) {
                continue;
            }

            if ($moduleName === 'file_FilelistList' || $moduleName === 'media_management') {
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

            $shortcut['group'] = $shortcutGroup;
            $shortcut['icon'] = $this->getShortcutIcon($routeIdentifier, $moduleName, $shortcut);
            $shortcut['label'] = ($row['description'] ?? false) ?: 'Shortcut'; // Fall back to "Shortcut", see: addShortcut()
            $shortcut['href'] = (string)$this->uriBuilder->buildUriFromRoute($routeIdentifier, $arguments);
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
                if ($module = $this->moduleProvider->getModule($moduleName, null, false)) {
                    $iconIdentifier = $module->getIconIdentifier();
                }
                if ($iconIdentifier === '') {
                    $iconIdentifier = 'empty-empty';
                }
                $icon = $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render();
        }

        return $icon;
    }

    /**
     * Get the module name from the resolved route or by static mapping for some special cases.
     */
    protected function getModuleNameFromRouteIdentifier(string $routeIdentifier): string
    {
        if ($this->isSpecialRoute($routeIdentifier)) {
            return $routeIdentifier;
        }

        return (string)($this->router->getRoute($routeIdentifier)?->getOption('module')?->getIdentifier() ?? '');
    }

    /**
     * Check if given route identifier is a special "no module" route
     */
    protected function isSpecialRoute(string $routeIdentifier): bool
    {
        return in_array($routeIdentifier, ['record_edit', 'file_edit', 'wizard_rte'], true);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
