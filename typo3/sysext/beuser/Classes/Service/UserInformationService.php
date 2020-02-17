<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Beuser\Service;

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

    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Get all relevant information of the user
     *
     * @param int $userId
     * @return array
     */
    public function get(int $userId): array
    {
        $user = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $user->enablecolumns = [];
        $user->setBeUserByUid($userId);
        $user->fetchGroupData();

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
                $data['groups'][$type][$key] = BackendUtility::getRecord('be_groups', $id);
            }
        }

        // languages
        $languages = GeneralUtility::trimExplode(',', $user->dataLists['allowed_languages'], true);
        asort($languages);
        foreach ($languages as $language) {
            $data['languages'][$language] = BackendUtility::getRecord('sys_language', $language);
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
            $record = BackendUtility::getRecord('pages', $mount, '*');
            if ($record) {
                $data['dbMounts'][] = $record;
            }
        }

        // File mounts
        $fileMounts = GeneralUtility::trimExplode(',', $user->dataLists['filemount_list'], true);
        asort($fileMounts);
        foreach ($fileMounts as $mount) {
            $data['fileMounts'][] = BackendUtility::getRecord('sys_filemounts', $mount, '*');
        }

        // Modules
        $modules = GeneralUtility::trimExplode(',', $user->dataLists['modList'], true);
        foreach ($modules as $module) {
            $data['modules'][$module] = $GLOBALS['TBE_MODULES']['_configuration'][$module];
        }

        // Categories
        $categories = GeneralUtility::trimExplode(',', $user->user['category_perms'], true);
        foreach ($categories as $category) {
            $data['categories'][$category] = BackendUtility::getRecord('sys_category', $category);
        }

        // workspaces
        if (ExtensionManagementUtility::isLoaded('workspaces')) {
            $data['workspaces'] = [
              'loaded' => true,
              'record' => $user->workspaceRec
            ];
        }

        // non_exclude_fields
        $fieldListTmp = GeneralUtility::trimExplode(',', $user->dataLists['non_exclude_fields'], true);
        $fieldList = [];
        foreach ($fieldListTmp as $item) {
            $split = explode(':', $item);
            $fieldList[$split[0]]['label'] = $GLOBALS['TCA'][$split[0]]['ctrl']['title'];
            $fieldList[$split[0]]['fields'][$split[1]] = $GLOBALS['TCA'][$split[0]]['columns'][$split[1]]['label'] ?? $split[1];
        }
        $data['non_exclude_fields'] = $fieldList;

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
