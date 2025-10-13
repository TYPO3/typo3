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
            'LoginRedirect' => 'content-elements-login',
        ],
    ],
    'columns' => [
        'errorCode' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_errorhandling.errorCode',
            'config' => [
                'type' => 'number',
                'required' => true,
                'range' => [
                    'lower' => 0,
                    'upper' => 599,
                ],
                'size' => 8,
                'default' => 404,
                'valuePicker' => [
                    'items' => [
                        ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode.404', 'value' => '404'],
                        ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode.403', 'value' => '403'],
                        ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode.500', 'value' => '500'],
                        ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode.503', 'value' => '503'],
                        ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorCode.0', 'value' => '0'],
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
                    ['label' => '', 'value' => ''],
                    ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorHandler.fluid', 'value' => 'Fluid'],
                    ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorHandler.page', 'value' => 'Page'],
                    ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorHandler.php', 'value' => 'PHP'],
                    ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorHandler.loginRedirect', 'value' => 'LoginRedirect'],
                ],
            ],
        ],
        'errorFluidTemplate' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorFluidTemplate',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_errorhandling.errorFluidTemplate',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
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
                'type' => 'link',
                'required' => true,
                'allowedTypes' => ['page', 'url', 'record'],
            ],
        ],
        'errorPhpClassFQCN' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorPhpClassFQCN',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_errorhandling.errorPhpClassFQCN',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'placeholder' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.errorPhpClassFQCN.placeholder',
            ],
        ],
        'loginRedirectTarget' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.loginRedirectTarget',
            'config' => [
                'type' => 'link',
                'required' => true,
                'allowedTypes' => ['page'],
            ],
        ],
        'loginRedirectParameter' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.loginRedirectParameter',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'required' => true,
                'items' => [
                    ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.loginRedirectParameter.return_url', 'value' => 'return_url'],
                    ['label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_errorhandling.loginRedirectParameter.redirect_url', 'value' => 'redirect_url'],
                ],
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
        'LoginRedirect' => [
            'showitem' => '--palette--;;general, loginRedirectTarget, loginRedirectParameter',
        ],
    ],
    'palettes' => [
        'general' => [
            'showitem' => 'errorCode, errorHandler',
        ],
    ],
];
