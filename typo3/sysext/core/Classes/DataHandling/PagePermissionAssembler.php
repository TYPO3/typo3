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
 * - PageTSconfig va TCEMAIN.permissions
 */
class PagePermissionAssembler
{

    /**
     * Can be overridden from $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPermissions']
     *
     * @var array
     */
    protected $defaultPermissions = [
        'user' => 'show,edit,delete,new,editcontent',
        'group' => 'show,edit,new,editcontent',
        'everybody' => '',
    ];

    public function __construct(array $defaultPermissions = null)
    {
        // Initializing default permissions for pages
        if (isset($defaultPermissions['user'])) {
            $this->defaultPermissions['user'] = $defaultPermissions['user'];
        }
        if (isset($defaultPermissions['group'])) {
            $this->defaultPermissions['group'] = $defaultPermissions['group'];
        }
        if (isset($defaultPermissions['everybody'])) {
            $this->defaultPermissions['everybody'] = $defaultPermissions['everybody'];
        }
    }

    /**
     * Set default permissions of a new page, and override via pageTSconfig.
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
        $fieldArray['perms_user'] = $this->assemblePermissions($this->defaultPermissions['user']);
        $fieldArray['perms_group'] = $this->assemblePermissions($this->defaultPermissions['group']);
        $fieldArray['perms_everybody'] = $this->assemblePermissions($this->defaultPermissions['everybody']);
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
        if ((string)($tsconfig['userid'] ?? '') !== '') {
            $fieldArray['perms_userid'] = (int)$tsconfig['userid'];
        }
        if ((string)($tsconfig['groupid'] ?? '') !== '') {
            $fieldArray['perms_groupid'] = (int)$tsconfig['groupid'];
        }
        if ((string)($tsconfig['user'] ?? '') !== '') {
            $fieldArray['perms_user'] = $this->assemblePermissions($tsconfig['user']);
        }
        if ((string)($tsconfig['group'] ?? '') !== '') {
            $fieldArray['perms_group'] = $this->assemblePermissions($tsconfig['group']);
        }
        if ((string)($tsconfig['everybody'] ?? '') !== '') {
            $fieldArray['perms_everybody'] = $this->assemblePermissions($tsconfig['everybody']);
        }
        return $fieldArray;
    }

    /**
     * Calculates the bitvalue of the permissions given in a string, comma-separated
     *
     * @param string $listOfPermissions a comma-separated list like "show,delete", usually from pageTSconfig
     * @return int Integer mask
     */
    protected function assemblePermissions($listOfPermissions): int
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
