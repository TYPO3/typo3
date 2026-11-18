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

namespace TYPO3\CMS\Core\Security\Authorization\Policy;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The permission lists of a backend user, as accumulated by
 * BackendUserAuthentication::fetchGroupData(). Mounts, file and workspace
 * permissions are not part of it. Each list is parsed on its first use, the
 * policy decision point keeps one instance per principal.
 *
 * @internal
 */
final class LegacyGroupData
{
    /**
     * @var array<string, array<string, true>>
     */
    private array $lists = [];

    /**
     * @param array<string, mixed> $groupData
     */
    private function __construct(
        private readonly array $groupData,
    ) {}

    /**
     * From the group data registered for a principal, see
     * LegacyBackendUserRegistry::getGroupData(). Changes made to the backend
     * user object after the principal was registered are not seen.
     *
     * @param array<string, mixed> $groupData
     */
    public static function fromGroupData(array $groupData): self
    {
        return new self($groupData);
    }

    public function contains(string $list, string $value): bool
    {
        return $value !== '' && isset($this->getList($list)[$value]);
    }

    public function isEmpty(string $list): bool
    {
        return $this->getList($list) === [];
    }

    /**
     * @return array<string, true>
     */
    private function getList(string $list): array
    {
        if (!isset($this->lists[$list])) {
            $value = (string)($this->groupData[$list] ?? '');
            $this->lists[$list] = array_fill_keys(GeneralUtility::trimExplode(',', $value, true), true);
        }
        return $this->lists[$list];
    }
}
