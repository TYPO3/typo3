<?php

declare(strict_types=1);

return [
    \TYPO3\CMS\Belog\Domain\Model\LogEntry::class => [
        'tableName' => 'sys_log',
        'properties' => [
            'backendUserUid' => [
                'fieldName' => 'userid',
            ],
            'recordUid' => [
                'fieldName' => 'recuid',
            ],
            'tableName' => [
                'fieldName' => 'tablename',
            ],
            'recordPid' => [
                'fieldName' => 'recpid',
            ],
            'detailsNumber' => [
                'fieldName' => 'details_nr',
            ],
            'ip' => [
                'fieldName' => 'IP',
            ],
            'workspaceUid' => [
                'fieldName' => 'workspace',
            ],
            'newId' => [
                'fieldName' => 'NEWid',
            ],
            'channel' => [
                'fieldName' => 'channel',
            ],
            'level' => [
                'fieldName' => 'level',
            ],
        ],
    ],
    \TYPO3\CMS\Belog\Domain\Model\Workspace::class => [
        'tableName' => 'sys_workspace',
    ],
];
