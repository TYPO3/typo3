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
    'EXT' => [
        'extConf' => [
            'rsaauth' => 'a:1:{s:18:"temporaryDirectory";s:0:"";}',
            'saltedpasswords' => serialize([
                'BE.' => [
                    'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class,
                    'forceSalted' => 0,
                    'onlyAuthService' => 0,
                    'updatePasswd' => 1,
                ],
                'FE.' => [
                    'enabled' => 1,
                    'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class,
                    'forceSalted' => 0,
                    'onlyAuthService' => 0,
                    'updatePasswd' => 1,
                ],
            ]),
        ],
    ],
    'FE' => [
        'loginSecurityLevel' => 'rsa',
        'cHashIncludePageId' => true,
    ],
    'GFX' => [
        'jpg_quality' => '80',
    ],
    'SYS' => [
        'isInitialInstallationInProgress' => true,
        'isInitialDatabaseImportDone' => false,
        'sitename' => 'New TYPO3 site',
    ],
];
