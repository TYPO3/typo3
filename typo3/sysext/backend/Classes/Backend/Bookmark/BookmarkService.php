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

namespace TYPO3\CMS\Backend\Backend\Bookmark;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Backend\Bookmark\Security\BookmarkGroupVoter;
use TYPO3\CMS\Backend\Backend\Bookmark\Security\BookmarkVoter;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
class BookmarkService
{
    use Traits\RouteParserTrait;

    public const GROUP_DEFAULT = 0;
    public const GROUP_SUPERGLOBAL = -100;

    public function __construct(
        protected readonly BookmarkRepository $bookmarkRepository,
        protected readonly BookmarkVoter $bookmarkVoter,
        protected readonly BookmarkGroupVoter $bookmarkGroupVoter,
        protected readonly IconFactory $iconFactory,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly Router $router,
        protected readonly UriBuilder $uriBuilder,
    ) {}

    protected function getRouter(): Router
    {
        return $this->router;
    }

    public function isEnabled(): bool
    {
        return (bool)($this->getBackendUser()->getTSConfig()['options.']['enableBookmarks'] ?? false);
    }

    /**
     * @return Bookmark[]
     */
    public function getBookmarks(): array
    {
        $backendUser = $this->getBackendUser();
        $userId = (int)$backendUser->user['uid'];
        $rows = $this->bookmarkRepository->findByUser($userId);

        // Build list of valid group IDs from existing groups
        $groups = $this->getGroups();
        $validGroupIds = array_map(static fn(BookmarkGroup $g) => $g->id, $groups);

        $bookmarks = [];

        foreach ($rows as $row) {
            // Skip bookmarks the user cannot read (e.g., global bookmarks they can't navigate to)
            if (!$this->bookmarkVoter->vote(BookmarkVoter::READ, $row, $backendUser)) {
                continue;
            }
            $row = $this->migrateBookmarkGroup($row, $validGroupIds);
            $bookmark = $this->createBookmarkFromRow($row, $backendUser);
            if ($bookmark !== null) {
                $bookmarks[] = $bookmark;
            }
        }

        return $bookmarks;
    }

    /**
     * Ensures bookmark is assigned to a valid group for output.
     */
    private function migrateBookmarkGroup(array $row, array $validGroupIds): array
    {
        $groupUuid = $row['group_uuid'] ?? null;
        $scGroup = (int)($row['sc_group'] ?? 0);
        $groupId = $groupUuid !== null ? $groupUuid : $scGroup;

        if (in_array($groupId, $validGroupIds, true)) {
            return $row;
        }

        $row['group_uuid'] = null;
        $row['sc_group'] = $scGroup < 0 ? self::GROUP_SUPERGLOBAL : self::GROUP_DEFAULT;

        return $row;
    }

    /**
     * @return BookmarkGroup[]
     */
    public function getGroups(): array
    {
        $groups = [];
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();
        $globalPrefix = $languageService->sL('core.bookmarks:global');

        // User-created groups
        $userId = (int)$backendUser->user['uid'];
        $userGroupRows = $this->bookmarkRepository->findGroupsByUser($userId);
        foreach ($userGroupRows as $index => $row) {
            $groups[] = $this->createBookmarkGroup(
                $row['uuid'],
                $row['label'] ?? '',
                BookmarkGroupType::USER,
                (int)($row['sorting'] ?? $index),
                $backendUser,
                isset($row['userid']) ? (int)$row['userid'] : null,
            );
        }

        // TSconfig groups and their global counterparts
        $configGroups = $backendUser->getTSConfig()['options.']['bookmarkGroups.'] ?? [];
        $tsconfigIndex = 0;
        if (is_array($configGroups)) {
            foreach ($configGroups as $groupId => $configLabel) {
                $groupId = (int)$groupId;
                if ($groupId <= 0 || $groupId === 100 || $configLabel === '' || $configLabel === null) {
                    continue;
                }
                $label = $languageService->sL((string)$configLabel);
                $sorting = $tsconfigIndex++;

                $groups[] = $this->createBookmarkGroup(
                    $groupId,
                    $label,
                    BookmarkGroupType::SYSTEM,
                    $sorting,
                    $backendUser,
                );

                $groups[] = $this->createBookmarkGroup(
                    -$groupId,
                    $globalPrefix . ': ' . $label,
                    BookmarkGroupType::GLOBAL,
                    $sorting,
                    $backendUser,
                );
            }
        }

        // Superglobal group
        $groups[] = $this->createBookmarkGroup(
            self::GROUP_SUPERGLOBAL,
            $globalPrefix . ': ' . $languageService->sL('core.bookmarks:all'),
            BookmarkGroupType::GLOBAL,
            0,
            $backendUser,
        );

        // Default group
        $groups[] = $this->createBookmarkGroup(
            self::GROUP_DEFAULT,
            $languageService->sL('core.bookmarks:group_default'),
            BookmarkGroupType::SYSTEM,
            100,
            $backendUser,
        );

        // Sort by type priority, then by sorting value
        $priorityKeys = array_map(static fn(BookmarkGroup $g) => $g->type->getPriority(), $groups);
        $sortingKeys = array_map(static fn(BookmarkGroup $g) => $g->sorting, $groups);
        array_multisort($priorityKeys, SORT_ASC, SORT_NUMERIC, $sortingKeys, SORT_ASC, SORT_NUMERIC, $groups);

        return $groups;
    }

    public function getBookmark(int $id): ?Bookmark
    {
        $row = $this->bookmarkRepository->findById($id);
        if ($row === null) {
            return null;
        }
        $backendUser = $this->getBackendUser();
        if (!$this->bookmarkVoter->vote(BookmarkVoter::READ, $row, $backendUser)) {
            return null;
        }
        $groups = $this->getGroups();
        $validGroupIds = array_map(static fn(BookmarkGroup $g) => $g->id, $groups);
        $row = $this->migrateBookmarkGroup($row, $validGroupIds);
        return $this->createBookmarkFromRow($row, $backendUser);
    }

    public function hasBookmark(string $routeIdentifier, string $arguments): bool
    {
        $userId = (int)$this->getBackendUser()->user['uid'];
        return $this->bookmarkRepository->exists($userId, $routeIdentifier, $arguments);
    }

    /**
     * @return int|false The ID of the newly created bookmark, or false on failure
     */
    public function createBookmark(string $routeIdentifier, string $arguments = '', string $title = ''): int|false
    {
        $backendUser = $this->getBackendUser();

        if (!$this->bookmarkVoter->vote(BookmarkVoter::CREATE, [], $backendUser)) {
            return false;
        }

        if (!$this->router->hasRoute($routeIdentifier)) {
            return false;
        }

        if ($arguments !== '' && !json_validate($arguments)) {
            return false;
        }

        $userId = (int)$backendUser->user['uid'];
        $result = $this->bookmarkRepository->insert($userId, $routeIdentifier, $arguments, $title);

        return $result;
    }

    /**
     * @return array{success: bool, error?: string}
     */
    public function updateBookmark(int $id, string $title, int|string $groupId): array
    {
        $backendUser = $this->getBackendUser();

        // Check if bookmark exists
        $bookmark = $this->bookmarkRepository->findById($id);
        if ($bookmark === null) {
            return $this->errorResponse(
                'core.bookmarks:error.notFound.message'
            );
        }

        // Check edit permission
        if (!$this->bookmarkVoter->vote(BookmarkVoter::EDIT, $bookmark, $backendUser)) {
            return $this->errorResponse(
                'core.bookmarks:error.accessDenied.message'
            );
        }

        // Check if user can use global groups
        if (is_int($groupId) && $groupId < 0 && !$backendUser->isAdmin()) {
            return $this->errorResponse(
                'core.bookmarks:error.globalGroupNotAllowed.message'
            );
        }

        $userId = $backendUser->isAdmin() ? null : (int)$backendUser->user['uid'];
        $allowGlobalGroups = $backendUser->isAdmin();

        $this->bookmarkRepository->update(
            $id,
            $userId,
            $title,
            $groupId,
            $allowGlobalGroups
        );

        return ['success' => true];
    }

    /**
     * @return array{success: false, error: string}
     */
    private function errorResponse(string $label): array
    {
        return [
            'success' => false,
            'error' => $this->getLanguageService()->sL($label),
        ];
    }

    /**
     * @return array{success: bool, error?: string}
     */
    public function deleteBookmark(int $id): array
    {
        $backendUser = $this->getBackendUser();
        $bookmark = $this->bookmarkRepository->findById($id);
        if ($bookmark === null) {
            return $this->errorResponse(
                'core.bookmarks:error.notFound.message'
            );
        }

        if (!$this->bookmarkVoter->vote(BookmarkVoter::DELETE, $bookmark, $backendUser)) {
            return $this->errorResponse(
                'core.bookmarks:error.accessDenied.message'
            );
        }

        $affectedRows = $this->bookmarkRepository->delete($id);

        if ($affectedRows !== 1) {
            return $this->errorResponse(
                'core.bookmarks:error.deleteFailed.message'
            );
        }

        return ['success' => true];
    }

    /**
     * @param int[] $bookmarkIds
     */
    public function reorderBookmarks(array $bookmarkIds): bool
    {
        $backendUser = $this->getBackendUser();
        $userId = (int)$backendUser->user['uid'];

        // Fetch all bookmarks in one query
        $bookmarks = $this->bookmarkRepository->findByIds($bookmarkIds);

        $sorting = 0;
        foreach ($bookmarkIds as $bookmarkId) {
            $bookmark = $bookmarks[$bookmarkId] ?? null;
            if ($bookmark !== null && $this->bookmarkVoter->vote(BookmarkVoter::EDIT, $bookmark, $backendUser)) {
                $this->bookmarkRepository->updateSorting($bookmarkId, $userId, $sorting++);
            }
        }

        return true;
    }

    /**
     * @param int[] $bookmarkIds
     */
    public function deleteBookmarks(array $bookmarkIds): bool
    {
        $backendUser = $this->getBackendUser();
        $userId = (int)$backendUser->user['uid'];

        // Fetch all bookmarks in one query
        $bookmarks = $this->bookmarkRepository->findByIds($bookmarkIds);

        $manageableIds = [];
        foreach ($bookmarkIds as $bookmarkId) {
            $bookmark = $bookmarks[$bookmarkId] ?? null;
            if ($bookmark !== null && $this->bookmarkVoter->vote(BookmarkVoter::DELETE, $bookmark, $backendUser)) {
                $manageableIds[] = $bookmarkId;
            }
        }

        if ($manageableIds !== []) {
            $this->bookmarkRepository->deleteMultiple($manageableIds, $userId);
        }

        return true;
    }

    /**
     * @param int[] $bookmarkIds
     */
    public function moveBookmarks(array $bookmarkIds, int|string $groupId): bool
    {
        $backendUser = $this->getBackendUser();
        $userId = (int)$backendUser->user['uid'];
        $allowGlobalGroups = $backendUser->isAdmin();

        // Validate target group access for user-created groups
        if (is_string($groupId)) {
            $group = $this->bookmarkRepository->findGroupByUuid($groupId);
            if ($group === null || !$this->bookmarkGroupVoter->vote(BookmarkGroupVoter::READ, $group, $backendUser)) {
                return false;
            }
        }

        // Fetch all bookmarks in one query and filter to those user can edit
        $bookmarks = $this->bookmarkRepository->findByIds($bookmarkIds);

        $manageableIds = [];
        foreach ($bookmarkIds as $bookmarkId) {
            $bookmark = $bookmarks[$bookmarkId] ?? null;
            if ($bookmark !== null && $this->bookmarkVoter->vote(BookmarkVoter::EDIT, $bookmark, $backendUser)) {
                $manageableIds[] = $bookmarkId;
            }
        }

        if ($manageableIds === []) {
            return false;
        }

        $this->bookmarkRepository->moveToGroup(
            ids: $manageableIds,
            userId: $userId,
            groupId: $groupId,
            allowGlobalGroups: $allowGlobalGroups
        );

        return true;
    }

    public function createGroup(string $label): ?BookmarkGroup
    {
        $backendUser = $this->getBackendUser();

        if (!$this->bookmarkGroupVoter->vote(BookmarkGroupVoter::CREATE, [], $backendUser)) {
            return null;
        }

        $userId = (int)$backendUser->user['uid'];
        $uuid = $this->bookmarkRepository->createGroup($userId, $label);

        if ($uuid === null) {
            return null;
        }

        $row = $this->bookmarkRepository->findGroupByUuid($uuid);
        if ($row === null) {
            return null;
        }

        return $this->createBookmarkGroup(
            $row['uuid'],
            $row['label'] ?? '',
            BookmarkGroupType::USER,
            0,
            $backendUser,
            isset($row['userid']) ? (int)$row['userid'] : null,
        );
    }

    public function updateGroup(string $uuid, string $label): bool
    {
        $backendUser = $this->getBackendUser();
        $group = $this->bookmarkRepository->findGroupByUuid($uuid);
        if ($group === null || !$this->bookmarkGroupVoter->vote(BookmarkGroupVoter::EDIT, $group, $backendUser)) {
            return false;
        }

        $this->bookmarkRepository->updateGroup($uuid, $label);
        return true;
    }

    public function deleteGroup(string $uuid): bool
    {
        $backendUser = $this->getBackendUser();
        $group = $this->bookmarkRepository->findGroupByUuid($uuid);
        if ($group === null || !$this->bookmarkGroupVoter->vote(BookmarkGroupVoter::DELETE, $group, $backendUser)) {
            return false;
        }

        $userId = (int)$backendUser->user['uid'];
        $this->bookmarkRepository->moveBookmarksFromGroupToDefault($uuid, $userId);
        $affectedRows = $this->bookmarkRepository->deleteGroup($uuid);

        return $affectedRows > 0;
    }

    /**
     * @param string[] $uuids
     */
    public function reorderGroups(array $uuids): bool
    {
        $backendUser = $this->getBackendUser();
        $userId = (int)$backendUser->user['uid'];

        // Get all user's groups in one query
        $userGroups = $this->bookmarkRepository->findGroupsByUser($userId);
        $groupsByUuid = [];
        foreach ($userGroups as $group) {
            $groupsByUuid[$group['uuid']] = $group;
        }

        // Verify all provided UUIDs can be edited by the user
        foreach ($uuids as $uuid) {
            $group = $groupsByUuid[$uuid] ?? null;
            if ($group === null || !$this->bookmarkGroupVoter->vote(BookmarkGroupVoter::EDIT, $group, $backendUser)) {
                return false;
            }
        }

        $this->bookmarkRepository->reorderGroups($uuids, $userId);

        return true;
    }

    private function createBookmarkFromRow(array $row, BackendUserAuthentication $user): ?Bookmark
    {
        $routeIdentifier = $row['route'] ?? '';

        try {
            $arguments = json_decode($row['arguments'] ?? '', true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($arguments)) {
            return null;
        }

        $moduleName = $this->getModuleNameFromRouteIdentifier($routeIdentifier);
        if ($moduleName === '') {
            return null;
        }

        $accessible = $this->bookmarkVoter->vote(BookmarkVoter::NAVIGATE, $row, $user);
        $editable = $this->bookmarkVoter->vote(BookmarkVoter::EDIT, $row, $user);

        $groupUuid = $row['group_uuid'] ?? null;
        $groupId = $groupUuid !== null ? $groupUuid : (int)$row['sc_group'];
        $bookmarkData = $this->parseRecordEditData($routeIdentifier, $arguments);
        $iconData = $this->resolveBookmarkIcon($routeIdentifier, $moduleName, $bookmarkData);
        $href = $accessible ? (string)$this->uriBuilder->buildUriFromRoute($routeIdentifier, $arguments) : '';

        return new Bookmark(
            id: (int)$row['uid'],
            route: $routeIdentifier,
            arguments: $row['arguments'] ?? '',
            title: ($row['description'] ?? false) ?: 'Bookmark',
            groupId: $groupId,
            iconIdentifier: $iconData['identifier'],
            iconOverlayIdentifier: $iconData['overlay'],
            module: $moduleName,
            href: $href,
            editable: $editable,
            accessible: $accessible,
        );
    }

    /**
     * @return array{identifier: string, overlay: string}
     */
    private function resolveBookmarkIcon(string $routeIdentifier, string $moduleName, array $bookmarkData): array
    {
        $identifier = '';
        $overlay = '';

        switch ($routeIdentifier) {
            case 'record_edit':
                $table = $bookmarkData['table'] ?? '';
                $recordid = $bookmarkData['recordid'] ?? 0;

                $action = $bookmarkData['action'] ?? '';
                if ($action === 'edit') {
                    $row = BackendUtility::getRecordWSOL($table, (int)$recordid) ?? [];
                    $icon = $this->iconFactory->getIconForRecord($table, $row, IconSize::SMALL);
                } elseif ($action === 'new') {
                    $icon = $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL);
                } else {
                    $icon = $this->iconFactory->getIcon('empty-empty', IconSize::SMALL);
                }
                $identifier = $icon->getIdentifier();
                $overlay = $icon->getOverlayIcon()?->getIdentifier() ?? '';
                break;

            case 'file_edit':
                $identifier = 'mimetypes-text-html';
                break;

            default:
                $iconIdentifier = '';
                if ($module = $this->moduleProvider->getModule($moduleName, null, false)) {
                    $iconIdentifier = $module->getIconIdentifier();
                    if ($iconIdentifier === '' && $module->getParentModule()) {
                        $iconIdentifier = $module->getParentModule()->getIconIdentifier();
                    }
                }
                if ($iconIdentifier === '') {
                    $iconIdentifier = 'empty-empty';
                }
                $identifier = $iconIdentifier;
        }

        return [
            'identifier' => $identifier,
            'overlay' => $overlay,
        ];
    }

    private function createBookmarkGroup(
        int|string $id,
        string $label,
        BookmarkGroupType $type,
        int $sorting,
        BackendUserAuthentication $user,
        ?int $userid = null
    ): BookmarkGroup {
        $voterData = ['id' => $id, 'userid' => $userid];
        $editable = $this->bookmarkGroupVoter->vote(BookmarkGroupVoter::EDIT, $voterData, $user);
        $selectable = $this->bookmarkGroupVoter->vote(BookmarkGroupVoter::SELECT, $voterData, $user);

        return new BookmarkGroup(
            id: $id,
            label: $label,
            type: $type,
            sorting: $sorting,
            editable: $editable,
            selectable: $selectable,
        );
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
