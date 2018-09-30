<?php
namespace TYPO3\CMS\Core\Crypto\PasswordHashing;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * General library class.
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
 */
class SaltedPasswordsUtility
{
    /**
     * Keeps this extension's key.
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    const EXTKEY = 'saltedpasswords';

    /**
     * Calculates number of backend users, who have no saltedpasswords protection.
     *
     * @return int
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public static function getNumberOfBackendUsersWithInsecurePassword()
    {
        trigger_error('SaltedPasswordsUtility::getNumberOfBackendUsersWithInsecurePassword() is obsolete and will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();

        $userCount = $queryBuilder
            ->count('*')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->neq('password', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)),
                $queryBuilder->expr()->notLike('password', $queryBuilder->createNamedParameter('$%', \PDO::PARAM_STR)),
                $queryBuilder->expr()->notLike('password', $queryBuilder->createNamedParameter('M$%', \PDO::PARAM_STR))
            )
            ->execute()
            ->fetchColumn();

        return $userCount;
    }

    /**
     * Returns extension configuration data from $TYPO3_CONF_VARS (configurable in Extension Manager)
     *
     * @param string $mode TYPO3_MODE, whether Configuration for Frontend or Backend should be delivered
     * @return array Extension configuration data
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public static function returnExtConf($mode = TYPO3_MODE)
    {
        trigger_error('SaltedPasswordsUtility::returnExtConf() is obsolete and will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        $currentConfiguration = self::returnExtConfDefaults();
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords'])) {
            $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('saltedpasswords');
            // Merge default configuration with modified configuration:
            if (isset($extensionConfiguration[$mode])) {
                $currentConfiguration = array_merge($currentConfiguration, $extensionConfiguration[$mode]);
            }
        }
        return $currentConfiguration;
    }

    /**
     * Returns default configuration of this extension.
     *
     * @return array Default extension configuration data for localconf.php
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public static function returnExtConfDefaults()
    {
        trigger_error('SaltedPasswordsUtility::returnExtConfDefaults() is obsolete and will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return [
            'saltedPWHashingMethod' => \TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash::class,
        ];
    }

    /**
     * Function determines the default(=configured) type of
     * salted hashing method to be used.
     *
     * @param string $mode (optional) The TYPO3 mode (FE or BE) saltedpasswords shall be used for
     * @return string Classname of object to be used
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public static function getDefaultSaltingHashingMethod($mode = TYPO3_MODE)
    {
        trigger_error('SaltedPasswordsUtility::getDefaultSaltingHashingMethod() is obsolete and will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        $extConf = self::returnExtConf($mode);
        $classNameToUse = \TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash::class;
        if (in_array(
            $extConf['saltedPWHashingMethod'],
            \TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::getRegisteredSaltedHashingMethods(),
            true
        )) {
            $classNameToUse = $extConf['saltedPWHashingMethod'];
        }
        return $classNameToUse;
    }

    /**
     * Returns information if salted password hashes are
     * indeed used in the TYPO3_MODE.
     *
     * @return bool TRUE, if salted password hashes are used in the TYPO3_MODE, otherwise FALSE
     * @deprecated in TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public static function isUsageEnabled()
    {
        trigger_error(
            'SaltedPasswordsUtility::isUsageEnabled() now always returns true and will be removed with TYPO3 v10.0.',
            E_USER_DEPRECATED
        );
        return true;
    }
}
