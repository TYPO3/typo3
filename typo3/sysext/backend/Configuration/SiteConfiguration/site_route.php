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
                'eval' => 'required',
            ],
        ],
        'type' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'required' => true,
                'items' => [
                    ['', ''],
                    ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.staticText', 'staticText'],
                    ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.source', 'uri']
                ],
            ],
        ],
        'content' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.staticText',
            'config' => [
                'type' => 'text',
                'eval' => 'required',
            ],
        ],
        'source' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_route.source',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'eval' => 'required',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'mail,spec,folder',
                        ]
                    ]
                ],
            ],
        ]
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
        ]
    ],
];
