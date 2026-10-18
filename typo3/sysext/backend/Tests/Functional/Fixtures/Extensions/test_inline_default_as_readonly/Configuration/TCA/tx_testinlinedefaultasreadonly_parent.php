<?php

return [
    'ctrl' => [
        'title' => 'Parent',
        'label' => 'name',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
    ],
    'types' => [
        '1' => ['showitem' => 'name, children, sys_language_uid, l10n_parent'],
    ],
    'columns' => [
        'name' => [
            'label' => 'Name',
            'config' => [
                'type' => 'input',
            ],
        ],
        'children' => [
            'label' => 'Children',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testinlinedefaultasreadonly_child',
                'foreign_field' => 'parent',
                'appearance' => [
                    'collapseAll' => false,
                ],
            ],
        ],
    ],
];
