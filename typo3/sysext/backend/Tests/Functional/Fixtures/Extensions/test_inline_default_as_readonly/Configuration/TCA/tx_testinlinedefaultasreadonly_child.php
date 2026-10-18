<?php

return [
    'ctrl' => [
        'title' => 'Child',
        'label' => 'value',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
    ],
    'types' => [
        '1' => ['showitem' => 'value'],
    ],
    'columns' => [
        'value' => [
            'label' => 'Value',
            'config' => [
                'type' => 'input',
            ],
        ],
        'parent' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
