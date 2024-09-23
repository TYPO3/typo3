<?php

return [
    // Change in argument value.
    // 'argumentMatches' is an array with the array keys:
    // - 'argumentIndex' corresponding to argument number
    // - 'argumentValue' containing the scalar value that is to be matched
    // Multiple entries in 'argumentMatches' are AND-combined and all need to match to be reported.
    // IMPORTANT: Only fixed string values are matched, static analysis cannot resolve run-time dynamic argument values
    // Note that "->" is automatically matched as "->" (weak) AND "::" (strong) for convenience.
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility->addPlugin' => [
        'argumentMatches' => [
            [
                'argumentIndex' => 2,
                'argumentValue' => 'list_type',
            ],
        ],
        'restFiles' => [
            'Deprecation-105076-PluginContentElementAndPluginSubTypes.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Utility\ExtensionUtility->configurePlugin' => [
        'argumentMatches' => [
            [
                'argumentIndex' => 5,
                'argumentValue' => 'list_type',
            ],
        ],
        'restFiles' => [
            'Deprecation-105076-PluginContentElementAndPluginSubTypes.rst',
        ],
    ],
];
