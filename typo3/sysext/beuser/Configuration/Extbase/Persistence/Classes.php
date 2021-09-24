<?php

declare(strict_types=1);

return [
    \TYPO3\CMS\Beuser\Domain\Model\BackendUser::class => [
        'tableName' => 'be_users',
        'properties' => [
            'userName' => [
                'fieldName' => 'username',
            ],
            'isAdministrator' => [
                'fieldName' => 'admin',
            ],
            'isDisabled' => [
                'fieldName' => 'disable',
            ],
            'realName' => [
                'fieldName' => 'realName',
            ],
            'startDateAndTime' => [
                'fieldName' => 'starttime',
            ],
            'endDateAndTime' => [
                'fieldName' => 'endtime',
            ],
            'lastLoginDateAndTime' => [
                'fieldName' => 'lastlogin',
            ],
            'allowedLanguages' => [
                'fieldName' => 'allowed_languages',
            ],
            'fileMountPoints' => [
                'fieldName' => 'file_mountpoints',
            ],
            'dbMountPoints' => [
                'fieldName' => 'db_mountpoints',
            ],
            'backendUserGroups' => [
                'fieldName' => 'usergroup',
            ],
        ],
    ],
    \TYPO3\CMS\Beuser\Domain\Model\BackendUserGroup::class => [
        'tableName' => 'be_groups',
        'properties' => [
            'subGroups' => [
                'fieldName' => 'subgroup',
            ],
        ],
    ],
];
