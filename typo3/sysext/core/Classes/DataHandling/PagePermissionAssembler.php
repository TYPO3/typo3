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

namespace TYPO3\CMS\Core\DataHandling;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class to determine and set proper permissions to a new (or copied) page.
 *
 * The following order applies:
 * - defaultPermissions as defined in this class.
 * - TYPO3_CONF_VARS[BE][defaultPermissions]
 * - Page TSconfig va TCEMAIN.permissions
 *
 * @internal Implements a DataHandler detail. Should only be used by the TYPO3 Core.
 */
class PagePermissionAssembler
{
    /**
     * Set default permissions of a new page, considering defaults and pageTsConfig overrides.
     *
     * @param array $fieldArray the field array to be used
     * @param int $pid the parent page ID
     * @param int $backendUserId the owner of the page to be set
     * @param int $backendUserGroupId the owner group of the page to be set
     * @return array the enriched field array
     */
    public function applyDefaults(array $fieldArray, int $pid, int $backendUserId, int $backendUserGroupId): array
    {
        $fieldArray['perms_userid'] = $backendUserId;
        $fieldArray['perms_groupid'] = $backendUserGroupId;
        $fieldArray['perms_user'] = $this->assemblePermissions($GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPermissions']['user'] ?? 'show,edit,delete,new,editcontent');
        $fieldArray['perms_group'] = $this->assemblePermissions($GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPermissions']['group'] ?? 'show,edit,new,editcontent');
        $fieldArray['perms_everybody'] = $this->assemblePermissions($GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPermissions']['everybody'] ?? '');
        // @todo: It's kinda ugly pageTS is fetched here on demand. Together with the 'fetch parent page' code in
        //        setTSconfigPermissions(), we should think about changing the API to have these things hand
        //        over instead.
        $TSConfig = BackendUtility::getPagesTSconfig($pid)['TCEMAIN.'] ?? [];
        if (isset($TSConfig['permissions.']) && is_array($TSConfig['permissions.'])) {
            return $this->setTSconfigPermissions($fieldArray, $TSConfig['permissions.']);
        }
        return $fieldArray;
    }

    /**
     * Setting up perms_* fields in $fieldArray based on TSconfig input
     * Used for new pages and pages that are copied.
     *
     * @param array $fieldArray Field Array, returned with modifications
     * @param array $tsconfig TSconfig properties
     * @return array Modified Field Array
     */
    protected function setTSconfigPermissions(array $fieldArray, array $tsconfig): array
    {
        $parentPermissions = [];
        if (in_array('copyFromParent', $tsconfig, true)) {
            // @todo: Dislocated! The API should be changed to have a potential parent record hand over.
            $parentPermissions = BackendUtility::getRecordWSOL('pages', $fieldArray['pid'], 'uid,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody') ?? [];
        }
        if ((string)($tsconfig['userid'] ?? '') !== '' && ($tsconfig['userid'] !== 'copyFromParent' || isset($parentPermissions['perms_userid']))) {
            $fieldArray['perms_userid'] = $tsconfig['userid'] === 'copyFromParent' ? (int)$parentPermissions['perms_userid'] : (int)$tsconfig['userid'];
        }
        if ((string)($tsconfig['groupid'] ?? '') !== '' && ($tsconfig['groupid'] !== 'copyFromParent' || isset($parentPermissions['perms_groupid']))) {
            $fieldArray['perms_groupid'] = $tsconfig['groupid'] === 'copyFromParent' ? (int)$parentPermissions['perms_groupid'] : (int)$tsconfig['groupid'];
        }
        if ((string)($tsconfig['user'] ?? '') !== '' && ($tsconfig['user'] !== 'copyFromParent' || isset($parentPermissions['perms_user']))) {
            $fieldArray['perms_user'] = $tsconfig['user'] === 'copyFromParent' ? (int)$parentPermissions['perms_user'] : $this->assemblePermissions($tsconfig['user']);
        }
        if ((string)($tsconfig['group'] ?? '') !== '' && ($tsconfig['group'] !== 'copyFromParent' || isset($parentPermissions['perms_group']))) {
            $fieldArray['perms_group'] = $tsconfig['group'] === 'copyFromParent' ? (int)$parentPermissions['perms_group'] : $this->assemblePermissions($tsconfig['group']);
        }
        if ((string)($tsconfig['everybody'] ?? '') !== '' && ($tsconfig['everybody'] !== 'copyFromParent' || isset($parentPermissions['perms_everybody']))) {
            $fieldArray['perms_everybody'] = $tsconfig['everybody'] === 'copyFromParent' ? (int)$parentPermissions['perms_everybody'] : $this->assemblePermissions($tsconfig['everybody']);
        }
        return $fieldArray;
    }

    /**
     * Calculates the bit value of the permissions given in a string, comma-separated.
     *
     * Even though not documented, it seems to be possible having int values in
     * $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPermissions']['...'] as bit mask
     * already. To not break anything, this is kept for now.
     */
    protected function assemblePermissions(int|string $listOfPermissions): int
    {
        // Already set as integer, so this one is used.
        if (MathUtility::canBeInterpretedAsInteger($listOfPermissions)) {
            return (int)$listOfPermissions;
        }
        $keyArr = GeneralUtility::trimExplode(',', $listOfPermissions, true);
        $value = 0;
        $permissionMap = Permission::getMap();
        foreach ($keyArr as $key) {
            if ($key && isset($permissionMap[$key])) {
                $value |= $permissionMap[$key];
            }
        }
        return $value;
    }
}
