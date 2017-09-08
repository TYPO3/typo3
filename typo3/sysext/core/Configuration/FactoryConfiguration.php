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
        'loginSecurityLevel' => 'rsa',
    ],
    'DB' => [
        'Connections' => [
            'Default' => [
                'charset' => 'utf8',
                'driver' => 'mysqli',
            ],
        ],
    ],
    'EXTENSIONS' => [
        'backend' => [
            'backendFavicon' => '',
            'backendLogo' => '',
            'loginBackgroundImage' => '',
            'loginFootnote' => '',
            'loginHighlightColor' => '',
            'loginLogo' => '',
        ],
        'extensionmanager' => [
            'automaticInstallation' => 1,
            'offlineMode' => 0,
        ],
        'rsaauth' => [
            'temporaryDirectory' => '',
        ],
        'saltedpasswords' => [
            'BE' => [
                'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::class,
                'forceSalted' => 0,
                'onlyAuthService' => 0,
                'updatePasswd' => 1,
            ],
            'FE' => [
                'enabled' => 1,
                'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::class,
                'forceSalted' => 0,
                'onlyAuthService' => 0,
                'updatePasswd' => 1,
            ],
            'checkConfigurationBE' => '0',
            'checkConfigurationBE2' => '0',
            'checkConfigurationFE' => '0',
            'checkConfigurationFE2' => '0',
        ],
        'scheduler' => [
            'enableBELog' => 1,
            'maxLifetime' => 1440,
            'showSampleTasks' => 1,
        ],
    ],
    'FE' => [
        'loginSecurityLevel' => 'rsa',
    ],
    'SYS' => [
        'sitename' => 'New TYPO3 site',
    ],
];
