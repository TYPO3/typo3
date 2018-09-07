<?php

return [
    'ctrl' => [
        'label' => 'base',
        'label_alt' => 'condition',
        'label_alt_force' => true,
        'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.ctrl.title',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-domain',
        ],
    ],
    'columns' => [
        'base' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.base',
            'config' => [
                'type' => 'input',
                'eval' => 'required',
            ],
        ],
        'condition' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.condition',
            'config' => [
                'type' => 'input',
                'eval' => 'required',
                'valuePicker' => [
                    'items' => [
                        [ 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.condition.applicationContext', 'applicationContext == "Production"'],
                        [ 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.condition.environmentVariable', 'getenv("mycontext") == "production"'],
                    ],
                ],
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'base,condition',
        ],
    ],
];
