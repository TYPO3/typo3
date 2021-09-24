<?php

return [
    'ctrl' => [
        'label' => 'errorHandler',
        'label_userFunc' => \TYPO3\CMS\Backend\Configuration\TCA\UserFunctions::class . '->getErrorHandlingTitle',
        'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.ctrl.title',
        'type' => 'errorHandler',
        'typeicon_column' => 'errorHandler',
        'typeicon_classes' => [
            'default' => 'default-not-found',
            'Fluid' => 'mimetypes-text-html',
            'Page' => 'apps-pagetree-page-content-from-page',
            'PHP' => 'mimetypes-text-php',
        ],
    ],
    'columns' => [
        'errorCode' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_errorhandling.errorCode',
            'config' => [
                'type' => 'input',
                'eval' => 'required, trim, int',
                'range' => [
                    'lower' => 0,
                    'upper' => 599,
                ],
                'size' => 8,
                'default' => 404,
                'valuePicker' => [
                    'mode' => '',
                    'items' => [
                        ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode.404', '404'],
                        ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode.403', '403'],
                        ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode.500', '500'],
                        ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode.503', '503'],
                        ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode.0', '0'],
                    ],
                ],
            ],
        ],
        'errorHandler' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorHandler',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'required' => true,
                'items' => [
                    ['', ''],
                    ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorHandler.fluid', 'Fluid'],
                    ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorHandler.page', 'Page'],
                    ['LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorHandler.php', 'PHP'],
                ],
            ],
        ],
        'errorFluidTemplate' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorFluidTemplate',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_errorhandling.errorFluidTemplate',
            'config' => [
                'type' => 'input',
                'eval' => 'required, trim',
                'placeholder' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorFluidTemplate.placeholder',
            ],
        ],
        'errorFluidTemplatesRootPath' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorFluidTemplatesRootPath',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'errorFluidLayoutsRootPath' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorFluidLayoutsRootPath',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'errorFluidPartialsRootPath' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorFluidPartialsRootPath',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'errorContentSource' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorContentSource',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'eval' => 'required',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'file,telephone,mail,spec,folder',
                        ],
                    ],
                ],
            ],
        ],
        'errorPhpClassFQCN' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorPhpClassFQCN',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_errorhandling.errorPhpClassFQCN',
            'config' => [
                'type' => 'input',
                'eval' => 'required, trim',
                'placeholder' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorPhpClassFQCN.placeholder',
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '--palette--;;general',
        ],
        'Fluid' => [
            'showitem' => '--palette--;;general, errorFluidTemplate,
                           --div--;LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.tab.rootpaths,
                           errorFluidTemplatesRootPath, errorFluidLayoutsRootPath, errorFluidPartialsRootPath',
        ],
        'Page' => [
            'showitem' => '--palette--;;general, errorContentSource',
        ],
        'PHP' => [
            'showitem' => '--palette--;;general, errorPhpClassFQCN',
        ],
    ],
    'palettes' => [
        'general' => [
            'showitem' => 'errorCode, errorHandler',
        ],
    ],
];
