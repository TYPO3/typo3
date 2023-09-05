<?php

return [
    'ctrl' => [
        'label' => 'route',
        'label_userFunc' => \TYPO3\CMS\Backend\Configuration\TCA\UserFunctions::class . '->getRouteTitle',
        'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.ctrl.title',
        'type' => 'type',
        'typeicon_column' => 'type',
        'typeicon_classes' => [
            'staticText' => 'mimetypes-text-html',
            'uri' => 'apps-pagetree-page-content-from-page',
        ],
    ],
    'columns' => [
        'route' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.route',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'placeholder' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.route.placeholder',
                'valuePicker' => [
                    'items' => [
                        ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.route.example1', 'robots.txt'],
                    ],
                ],
            ],
        ],
        'type' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'required' => true,
                'items' => [
                    ['label' => '', 'value' => ''],
                    ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.staticText', 'value' => 'staticText'],
                    ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.source', 'value' => 'uri'],
                ],
            ],
        ],
        'content' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.staticText',
            'config' => [
                'type' => 'text',
                'required' => true,
                'valuePicker' => [
                    'items' => [
                        ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.staticText.example1', 'User-agent: *
Disallow: /typo3/
Disallow: /typo3_src/
Allow: /typo3/sysext/frontend/Resources/Public/*
'],
                    ],
                ],
            ],
        ],
        'source' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.source',
            'config' => [
                'type' => 'link',
                'required' => true,
                'allowedTypes' => ['page', 'url', 'record', 'file'],
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'route, type',
        ],
        'staticText' => [
            'showitem' => 'route, type, content',
        ],
        'uri' => [
            'showitem' => 'route, type, source',
        ],
    ],
];
