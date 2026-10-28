<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Group',
        'label' => 'title',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'translationSource' => 'l10n_source',
        'delete' => 'deleted',
        'versioningWS' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
            ],
        ],
        'members' => [
            'label' => 'Members',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testinlinetwoparents_member',
                'foreign_field' => 'group_uid',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title, members, sys_language_uid, l10n_parent'],
    ],
];
