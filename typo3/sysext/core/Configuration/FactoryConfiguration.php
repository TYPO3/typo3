<?php
/**
 * This is a boilerplate of typo3conf/LocalConfiguration.php. It is
 * used as base file during installation and can be overloaded with
 * a package specific file typo3conf/AdditionalFactoryConfiguration.php
 * from eg. the government or introduction package.
 */
return [
    'BE' => [
        'explicitADmode' => 'explicitAllow',
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
        'disableNoCacheParameter' => true,
    ],
    'SYS' => [
        'sitename' => 'New TYPO3 site',
        'features' => [
            'unifiedPageTranslationHandling' => true,
            'yamlImportsFollowDeclarationOrder' => true,
        ],
    ],
];
