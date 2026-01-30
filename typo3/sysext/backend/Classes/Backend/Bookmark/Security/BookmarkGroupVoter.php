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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
final readonly class BookmarkGroupVoter
{
    public const READ = 'read';
    public const CREATE = 'create';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const SELECT = 'select';

    public function vote(string $attribute, array $group, BackendUserAuthentication $user): bool
    {
        return match ($attribute) {
            self::READ => $this->canRead($group, $user),
            self::CREATE => $this->canCreate($user),
            self::EDIT => $this->canEdit($group, $user),
            self::DELETE => $this->canDelete($group, $user),
            self::SELECT => $this->canSelect($group, $user),
            default => false,
        };
    }

    private function canCreate(BackendUserAuthentication $user): bool
    {
        return isset($user->user['uid']) && (int)$user->user['uid'] > 0;
    }

    private function canRead(array $group, BackendUserAuthentication $user): bool
    {
        $userId = (int)$user->user['uid'];
        return (int)$group['userid'] === $userId;
    }

    private function canEdit(array $group, BackendUserAuthentication $user): bool
    {
        $groupId = $group['id'] ?? null;

        // System/global groups (integer IDs) are not editable
        if (is_int($groupId) || is_numeric($groupId)) {
            return false;
        }

        // User-created groups (UUID strings) are editable only if user owns them
        if (!isset($group['userid'])) {
            return false;
        }

        $userId = (int)$user->user['uid'];
        return (int)$group['userid'] === $userId;
    }

    private function canDelete(array $group, BackendUserAuthentication $user): bool
    {
        // Delete permission mirrors edit permission
        return $this->canEdit($group, $user);
    }

    private function canSelect(array $group, BackendUserAuthentication $user): bool
    {
        $groupId = $group['id'] ?? 0;

        // Global groups (negative IDs) are only selectable by admins
        if (is_int($groupId) && $groupId < 0) {
            return $user->isAdmin();
        }

        return true;
    }
}
