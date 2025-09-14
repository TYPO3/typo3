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

namespace TYPO3\CMS\Redirects\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This class is a workaround to temporarily mutate user permissions to create and delete redirects,
 * even if the current user has no access to the table.
 */
final class TemporaryPermissionMutationService
{
    public function addTableSelect(): bool
    {
        if (!$this->containsSysRedirectPermission('tables_select')) {
            $GLOBALS['BE_USER']->groupData['tables_select'] = $this->addSysRedirectPermission('tables_select');
            return true;
        }

        return false;
    }

    public function addTableModify(): bool
    {
        if (!$this->containsSysRedirectPermission('tables_modify')) {
            $GLOBALS['BE_USER']->groupData['tables_modify'] = $this->addSysRedirectPermission('tables_modify');
            return true;
        }

        return false;
    }

    public function removeTableSelect(): void
    {
        if ($this->containsSysRedirectPermission('tables_select')) {
            $GLOBALS['BE_USER']->groupData['tables_select'] = $this->removeSysRedirectPermission('tables_select');
        }
    }

    public function removeTableModify(): void
    {
        if ($this->containsSysRedirectPermission('tables_modify')) {
            $GLOBALS['BE_USER']->groupData['tables_modify'] = $this->removeSysRedirectPermission('tables_modify');
        }
    }

    private function addSysRedirectPermission(string $groupData): string
    {
        $permissions = GeneralUtility::trimExplode(',', $GLOBALS['BE_USER']->groupData[$groupData], true);
        $permissions[] = 'sys_redirect';
        return implode(',', array_unique($permissions));
    }

    private function removeSysRedirectPermission(string $permissionString): string
    {
        $permissions = GeneralUtility::trimExplode(',', $permissionString, true);
        $permissions = array_diff($permissions, ['sys_redirect']);
        return implode(',', array_unique($permissions));
    }

    private function containsSysRedirectPermission(string $groupData): bool
    {
        return GeneralUtility::inList($GLOBALS['BE_USER']->groupData[$groupData], 'sys_redirect');
    }
}
