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

namespace TYPO3\CMS\Backend\Backend\Bookmark\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Backend\Bookmark\Traits\RouteParserTrait;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
final readonly class BookmarkVoter
{
    use RouteParserTrait;

    public const READ = 'read';
    public const CREATE = 'create';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const NAVIGATE = 'navigate';

    public function __construct(
        protected ModuleProvider $moduleProvider,
        protected Router $router,
        protected LoggerInterface $logger,
        protected StorageRepository $storageRepository,
    ) {}

    protected function getRouter(): Router
    {
        return $this->router;
    }

    public function vote(string $attribute, array $bookmark, BackendUserAuthentication $user): bool
    {
        return match ($attribute) {
            self::READ => $this->canRead($bookmark, $user),
            self::CREATE => $this->canCreate($user),
            self::EDIT => $this->canEdit($bookmark, $user),
            self::DELETE => $this->canDelete($bookmark, $user),
            self::NAVIGATE => $this->canNavigate($bookmark, $user),
            default => false,
        };
    }

    private function canCreate(BackendUserAuthentication $user): bool
    {
        return isset($user->user['uid']) && (int)$user->user['uid'] > 0;
    }

    private function canRead(array $bookmark, BackendUserAuthentication $user): bool
    {
        $userId = (int)$user->user['uid'];

        // User's own bookmarks are always readable
        if ((int)$bookmark['userid'] === $userId) {
            return true;
        }

        // Global bookmarks are only readable if user can navigate to them
        if ((int)$bookmark['sc_group'] < 0) {
            return $this->canNavigate($bookmark, $user);
        }

        return false;
    }

    private function canEdit(array $bookmark, BackendUserAuthentication $user): bool
    {
        if (!$this->canRead($bookmark, $user)) {
            return false;
        }

        $userId = (int)$user->user['uid'];
        $isOwner = (int)$bookmark['userid'] === $userId;
        $groupId = isset($bookmark['group_uuid']) && $bookmark['group_uuid'] !== '' ? $bookmark['group_uuid'] : (int)$bookmark['sc_group'];
        $isGlobal = is_int($groupId) && $groupId < 0;

        if ($isOwner && !$isGlobal) {
            return true;
        }

        if ($user->isAdmin() && $isGlobal) {
            return true;
        }

        return false;
    }

    private function canDelete(array $bookmark, BackendUserAuthentication $user): bool
    {
        $userId = (int)$user->user['uid'];

        if ((int)$bookmark['userid'] === $userId) {
            return true;
        }

        if ($user->isAdmin() && (int)$bookmark['sc_group'] < 0) {
            return true;
        }

        return false;
    }

    private function canNavigate(array $bookmark, BackendUserAuthentication $user): bool
    {
        $routeIdentifier = $bookmark['route'] ?? '';

        try {
            $arguments = json_decode($bookmark['arguments'] ?? '', true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }

        if (!is_array($arguments)) {
            return false;
        }

        $moduleName = $this->getModuleNameFromRouteIdentifier($routeIdentifier);
        if ($moduleName === '') {
            return false;
        }

        if (!$this->canAccessModule($routeIdentifier, $moduleName, $user)) {
            return false;
        }

        if (!$this->canAccessFile($moduleName, $arguments)) {
            return false;
        }

        if (!$this->canAccessRecord($moduleName, $routeIdentifier, $arguments, $user)) {
            return false;
        }

        return true;
    }

    private function canAccessModule(string $routeIdentifier, string $moduleName, BackendUserAuthentication $user): bool
    {
        // record_edit has its own access checks via canAccessRecord
        if ($routeIdentifier === 'record_edit') {
            return true;
        }

        return $this->moduleProvider->accessGranted($moduleName, $user);
    }

    private function canAccessFile(string $moduleName, array $arguments): bool
    {
        if ($moduleName !== 'file_FilelistList' && $moduleName !== 'media_management') {
            return true;
        }

        $combinedIdentifier = (string)($arguments['id'] ?? '');
        if ($combinedIdentifier === '') {
            return true;
        }

        $storage = $this->storageRepository->findByCombinedIdentifier($combinedIdentifier);
        if ($storage === null || $storage->isFallbackStorage()) {
            return false;
        }

        $folderIdentifier = substr($combinedIdentifier, strpos($combinedIdentifier, ':') + 1);
        try {
            $storage->getFolder($folderIdentifier);
        } catch (InsufficientFolderAccessPermissionsException) {
            return false;
        } catch (FolderDoesNotExistException) {
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to resolve folder identifier "{folder}" in backend user bookmark: {message}', [
                'folder' => $folderIdentifier,
                'message' => $e->getMessage(),
            ]);
            return false;
        }

        return true;
    }

    private function canAccessRecord(
        string $moduleName,
        string $routeIdentifier,
        array $arguments,
        BackendUserAuthentication $user
    ): bool {
        if ($moduleName === 'file_FilelistList' || $moduleName === 'media_management') {
            return true;
        }

        $bookmarkData = $this->parseRecordEditData($routeIdentifier, $arguments);
        $pageId = 0;

        if ($moduleName === 'record_edit' && isset($bookmarkData['table'], $bookmarkData['recordid'])) {
            if (!$user->check('tables_modify', $bookmarkData['table'])) {
                return false;
            }

            $action = $bookmarkData['action'] ?? '';
            $recordId = (int)$bookmarkData['recordid'];

            if ($action === 'edit' || ($action === 'new' && $recordId < 0)) {
                $record = BackendUtility::getRecord($bookmarkData['table'], abs($recordId));
                if ($record === null || $record === []) {
                    return false;
                }
                $pageId = ($bookmarkData['table'] === 'pages' ? (int)($record['uid'] ?? 0) : (int)($record['pid'] ?? 0));
            } elseif ($action === 'new' && $recordId > 0) {
                $pageId = $recordId;
            }
        } else {
            $pageId = (int)($arguments['id'] ?? 0);
        }

        if ($pageId > 0 && !$user->isAdmin()) {
            if ($user->isInWebMount($pageId) === null) {
                return false;
            }
            $pageRow = BackendUtility::getRecord('pages', $pageId);
            if ($pageRow === null || !$user->doesUserHaveAccess($pageRow, Permission::PAGE_SHOW)) {
                return false;
            }
        }

        return true;
    }
}
