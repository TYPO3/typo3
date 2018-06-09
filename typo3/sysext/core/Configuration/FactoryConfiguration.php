<?php
/**
 * This is a boilerplate of LocalConfiguration.php. It is
 * used as base file during installation and can be overloaded with
 * a package specific file AdditionalFactoryConfiguration.php
 * from eg. the government or introduction package.
 */
return [
    'BE' => [
        'explicitADmode' => 'explicitAllow',
        'loginSecurityLevel' => 'normal',
    ],
    'DB' => [
        'Connections' => [
            'Default' => [
                'charset' => 'utf8',
                'driver' => 'mysqli',
            ],
        ],
    ],
    'FE' => [
        'loginSecurityLevel' => 'normal',
    ],
    'SYS' => [
        'sitename' => 'New TYPO3 site',
        'features' => [
            'unifiedPageTranslationHandling' => true
        ],
    ],
];
