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

namespace TYPO3\CMS\Beuser\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Transform information of user and groups into better format
 * @internal
 */
class UserInformationService
{

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    public function __construct(IconFactory $iconFactory)
    {
        $this->iconFactory = $iconFactory;
    }

    /**
     * Get all relevant information for a backend usergroup
     * @param int $groupId
     * @return array
     */
    public function getGroupInformation(int $groupId): array
    {
        $usergroupRecord = BackendUtility::getRecord('be_groups', $groupId);
        if (!$usergroupRecord) {
            return [];
        }

        $user = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $user->enablecolumns = [
            'deleted' => true
        ];
        // Setup dummy user to allow fetching all group data
        // @see \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::fetchGroups
        $user->user = [
            'uid' => PHP_INT_MAX,
            'options' => 3,
            'workspace_id' => -99,
            $user->usergroup_column => $groupId
        ];
        $user->fetchGroupData();

        $data = $this->convert($user);
        $data['group'] = $usergroupRecord;

        return $data;
    }

    /**
     * Get all relevant information of the user
     *
     * @param int $userId
     * @return array
     */
    public function getUserInformation(int $userId): array
    {
        $user = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $user->enablecolumns = [
            'deleted' => true
        ];
        $user->setBeUserByUid($userId);
        if (!$user->user) {
            return [];
        }
        $user->fetchGroupData();

        return $this->convert($user);
    }

    /**
     * Convert hard readable user & group information into structured
     * data which can be rendered later
     *
     * @param BackendUserAuthentication $user
     * @return array
     */
    protected function convert(BackendUserAuthentication $user): array
    {
        // usergroups
        $data = [
            'user' => $user->user ?? [],
            'groups' => [
                'inherit' => GeneralUtility::trimExplode(',', $user->groupList, true),
                'direct' => GeneralUtility::trimExplode(',', $user->user['usergroup'], true),
            ],
        ];
        $data['groups']['diff'] = array_diff($data['groups']['inherit'], $data['groups']['direct']);
        foreach ($data['groups'] as $type => $groups) {
            foreach ($groups as $key => $id) {
                $record = BackendUtility::getRecord('be_groups', (int)$id);
                if ($record) {
                    $data['groups']['all'][$record['uid']]['row'] = $record;
                    $data['groups']['all'][$record['uid']][$type] = 1;
                }
            }
        }

        // languages
        $languages = GeneralUtility::trimExplode(',', $user->dataLists['allowed_languages'], true);
        asort($languages);
        foreach ($languages as $language) {
            $record = BackendUtility::getRecord('sys_language', (int)$language);
            if ($record) {
                $data['languages'][$language] = $record;
            }
        }

        // table permissions
        $data['tables']['tables_select'] = [];
        $data['tables']['tables_modify'] = [];
        foreach (['tables_select', 'tables_modify'] as $tableField) {
            $temp = GeneralUtility::trimExplode(',', $user->dataLists[$tableField], true);
            foreach ($temp as $tableName) {
                $data['tables'][$tableField][$tableName] = $GLOBALS['TCA'][$tableName]['ctrl']['title'];
            }
        }
        $data['tables']['all'] = array_replace($data['tables']['tables_select'] ?? [], $data['tables']['tables_modify'] ?? []);

        // DB mounts
        $dbMounts = GeneralUtility::trimExplode(',', $user->dataLists['webmount_list'], true);
        asort($dbMounts);
        foreach ($dbMounts as $mount) {
            $record = BackendUtility::getRecord('pages', (int)$mount);
            if ($record) {
                $data['dbMounts'][] = $record;
            }
        }

        // File mounts
        $fileMounts = GeneralUtility::trimExplode(',', $user->dataLists['filemount_list'], true);
        asort($fileMounts);
        foreach ($fileMounts as $mount) {
            $record = BackendUtility::getRecord('sys_filemounts', (int)$mount);
            if ($record) {
                $data['fileMounts'][] = $record;
            }
        }

        // Modules
        $modules = GeneralUtility::trimExplode(',', $user->dataLists['modList'], true);
        foreach ($modules as $module) {
            $data['modules'][$module] = $GLOBALS['TBE_MODULES']['_configuration'][$module];
        }
        $data['modules'] = array_filter((array)$data['modules']);

        // Categories
        $categories = $user->getCategoryMountPoints();
        foreach ($categories as $category) {
            $record = BackendUtility::getRecord('sys_category', $category);
            if ($record) {
                $data['categories'][$category] = $record;
            }
        }

        // workspaces
        if (ExtensionManagementUtility::isLoaded('workspaces')) {
            $data['workspaces'] = [
                'loaded' => true,
                'record' => $user->workspaceRec
            ];
        }

        // file & folder permissions
        if ($filePermissions = $user->dataLists['file_permissions']) {
            $items = GeneralUtility::trimExplode(',', $filePermissions, true);
            foreach ($GLOBALS['TCA']['be_groups']['columns']['file_permissions']['config']['items'] as $availableItem) {
                if (in_array($availableItem[1], $items, true)) {
                    $data['fileFolderPermissions'][] = $availableItem;
                }
            }
        }

        // tsconfig
        $data['tsconfig'] = $user->getTSConfig();

        // non_exclude_fields
        $fieldListTmp = GeneralUtility::trimExplode(',', $user->dataLists['non_exclude_fields'], true);
        $fieldList = [];
        foreach ($fieldListTmp as $item) {
            $split = explode(':', $item);
            $fieldList[$split[0]]['label'] = $GLOBALS['TCA'][$split[0]]['ctrl']['title'];
            $fieldList[$split[0]]['fields'][$split[1]] = $GLOBALS['TCA'][$split[0]]['columns'][$split[1]]['label'] ?? $split[1];
        }
        ksort($fieldList);
        foreach ($fieldList as &$fieldListItem) {
            ksort($fieldListItem['fields']);
        }
        $data['non_exclude_fields'] = $fieldList;

        // page types
        $specialItems = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];
        foreach ($specialItems as $specialItem) {
            $value = $specialItem[1];
            if (!GeneralUtility::inList($user->dataLists['pagetypes_select'], $value)) {
                continue;
            }
            $label = $specialItem[0];
            $icon = $this->iconFactory->mapRecordTypeToIconIdentifier('pages', ['doktype' => $specialItem[1]]);
            $data['pageTypes'][] = ['label' => $label, 'value' => $value, 'icon' => $icon];
        }

        return $data;
    }
}
