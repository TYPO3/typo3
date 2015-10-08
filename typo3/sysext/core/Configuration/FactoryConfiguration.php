<?php
/**
 * This is a boilerplate of typo3conf/LocalConfiguration.php. It is
 * used as base file during installation and can be overloaded with
 * a package specific file typo3conf/AdditionalFactoryConfiguration.php
 * from eg. the government or introduction package.
 */
return array(
    'BE' => array(
        'explicitADmode' => 'explicitAllow',
        'loginSecurityLevel' => 'rsa',
    ),
    'EXT' => array(
        'extConf' => array(
            'rsaauth' => 'a:1:{s:18:"temporaryDirectory";s:0:"";}',
            'saltedpasswords' => serialize(array(
                'BE.' => array(
                    'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class,
                    'forceSalted' => 0,
                    'onlyAuthService' => 0,
                    'updatePasswd' => 1,
                ),
                'FE.' => array(
                    'enabled' => 1,
                    'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class,
                    'forceSalted' => 0,
                    'onlyAuthService' => 0,
                    'updatePasswd' => 1,
                ),
            )),
        ),
    ),
    'FE' => array(
        'loginSecurityLevel' => 'rsa',
    ),
    'GFX' => array(
        'jpg_quality' => '80',
    ),
    'SYS' => array(
        'isInitialInstallationInProgress' => true,
        'isInitialDatabaseImportDone' => false,
        'sitename' => 'New TYPO3 site',
    ),
);
