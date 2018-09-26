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
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_base_variant.base',
            'config' => [
                'type' => 'input',
                'eval' => 'required, trim',
                'placeholder' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.base.placeholder',
            ],
        ],
        'condition' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.condition',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_base_variant.condition',
            'config' => [
                'type' => 'input',
                'eval' => 'required, trim',
                'valuePicker' => [
                    'items' => [
                        [ 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.condition.applicationContext', 'applicationContext == "Production"'],
                        [ 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.condition.environmentVariable', 'getenv("mycontext") == "production"'],
                    ],
                ],
                'placeholder' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.condition.placeholder',
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'base,condition',
        ],
    ],
];
