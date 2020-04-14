<?php

declare(strict_types=1);

return [
    \TYPO3\CMS\Extbase\Domain\Model\FileMount::class => [
        'tableName' => 'sys_filemounts',
        'properties' => [
            'title' => [
                'fieldName' => 'title'
            ],
            'path' => [
                'fieldName' => 'path'
            ],
            'isAbsolutePath' => [
                'fieldName' => 'base'
            ],
        ],
    ],
    \TYPO3\CMS\Extbase\Domain\Model\FileReference::class => [
        'tableName' => 'sys_file_reference',
    ],
    \TYPO3\CMS\Extbase\Domain\Model\File::class => [
        'tableName' => 'sys_file',
    ],
    \TYPO3\CMS\Extbase\Domain\Model\BackendUser::class => [
        'tableName' => 'be_users',
        'properties' => [
            'userName' => [
                'fieldName' => 'username'
            ],
            'isAdministrator' => [
                'fieldName' => 'admin'
            ],
            'isDisabled' => [
                'fieldName' => 'disable'
            ],
            'realName' => [
                'fieldName' => 'realName'
            ],
            'startDateAndTime' => [
                'fieldName' => 'starttime'
            ],
            'endDateAndTime' => [
                'fieldName' => 'endtime'
            ],
            'ipLockIsDisabled' => [
                'fieldName' => 'disableIPlock'
            ],
            'lastLoginDateAndTime' => [
                'fieldName' => 'lastlogin'
            ],
        ],
    ],
    \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup::class => [
        'tableName' => 'be_groups',
        'properties' => [
            'subGroups' => [
                'fieldName' => 'subgroup'
            ],
            'modules' => [
                'fieldName' => 'groupMods'
            ],
            'tablesListening' => [
                'fieldName' => 'tables_select'
            ],
            'tablesModify' => [
                'fieldName' => 'tables_modify'
            ],
            'pageTypes' => [
                'fieldName' => 'pagetypes_select'
            ],
            'allowedExcludeFields' => [
                'fieldName' => 'non_exclude_fields'
            ],
            'explicitlyAllowAndDeny' => [
                'fieldName' => 'explicit_allowdeny'
            ],
            'allowedLanguages' => [
                'fieldName' => 'allowed_languages'
            ],
            'workspacePermission' => [
                'fieldName' => 'workspace_perms'
            ],
            'databaseMounts' => [
                'fieldName' => 'db_mountpoints'
            ],
            'fileOperationPermissions' => [
                'fieldName' => 'file_permissions'
            ],
            'lockToDomain' => [
                'fieldName' => 'lockToDomain'
            ],
            'tsConfig' => [
                'fieldName' => 'TSconfig'
            ],
        ],
    ],
    \TYPO3\CMS\Extbase\Domain\Model\FrontendUser::class => [
        'tableName' => 'fe_users',
        'properties' => [
            'lockToDomain' => [
                'fieldName' => 'lockToDomain'
            ],
        ],
    ],
    \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup::class => [
        'tableName' => 'fe_groups',
        'properties' => [
            'lockToDomain' => [
                'fieldName' => 'lockToDomain'
            ],
        ],
    ],
    \TYPO3\CMS\Extbase\Domain\Model\Category::class => [
        'tableName' => 'sys_category',
    ],
];
