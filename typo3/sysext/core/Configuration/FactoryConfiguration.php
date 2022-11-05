<?php
/**
 * This is a boilerplate of %config-dir%/system/settings.php. It is
 * used as base file during installation and can be overloaded with
 * a package specific file %config-dir%/system/additional.php
 * from eg. the government or introduction package.
 */
return [
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
        'cacheHash' => [
            'enforceValidation' => true,
        ],
    ],
    'SYS' => [
        'sitename' => 'New TYPO3 site',
        'UTF8filesystem' => true,
        'features' => [
            'security.backend.enforceContentSecurityPolicy' => true,
            'security.usePasswordPolicyForFrontendUsers' => true,
        ],
    ],
];
