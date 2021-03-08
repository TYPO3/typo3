<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory class to find and return hash instances of given hashed passwords
 * and to find and return default hash instances to hash new passwords.
 */
class PasswordHashFactory
{
    /**
     * An instance of the salted hashing method.
     * This member is set in the getSaltingInstance() function.
     *
     * @var PasswordHashInterface
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected static $instance;

    /**
     * Find a hash class that handles given hash and return an instance of it.
     *
     * @param string $hash Given hash to find instance for
     * @param string $mode 'FE' for frontend users, 'BE' for backend users
     * @return PasswordHashInterface Object that can handle given hash
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws InvalidPasswordHashException If no class was found that handles given hash
     */
    public function get(string $hash, string $mode): PasswordHashInterface
    {
        if ($mode !== 'FE' && $mode !== 'BE') {
            throw new \InvalidArgumentException('Mode must be either \'FE\' or \'BE\', ' . $mode . ' given.', 1533948312);
        }

        $registeredHashClasses = static::getRegisteredSaltedHashingMethods();

        if (empty($GLOBALS['TYPO3_CONF_VARS'][$mode]['passwordHashing']['className'])
            || !isset($GLOBALS['TYPO3_CONF_VARS'][$mode]['passwordHashing']['options'])
            || !is_array($GLOBALS['TYPO3_CONF_VARS'][$mode]['passwordHashing']['options'])
        ) {
            throw new \LogicException(
                'passwordHashing configuration of ' . $mode . ' broken',
                1533949053
            );
        }
        $defaultHashClassName = $GLOBALS['TYPO3_CONF_VARS'][$mode]['passwordHashing']['className'];
        $defaultHashOptions = (array)$GLOBALS['TYPO3_CONF_VARS'][$mode]['passwordHashing']['options'];

        foreach ($registeredHashClasses as $className) {
            if ($className === $defaultHashClassName) {
                $hashInstance = GeneralUtility::makeInstance($className, $defaultHashOptions);
            } else {
                $hashInstance = GeneralUtility::makeInstance($className);
            }
            if (!$hashInstance instanceof PasswordHashInterface) {
                throw new \LogicException('Class ' . $className . ' does not implement PasswordHashInterface', 1533818569);
            }
            if ($hashInstance->isAvailable() && $hashInstance->isValidSaltedPW($hash)) {
                return $hashInstance;
            }
        }
        // Do not add the hash to the exception to prevent information disclosure
        throw new InvalidPasswordHashException(
            'No implementation found to handle given hash. This happens if the stored hash uses a'
            . ' mechanism not supported by current server. Follow the documentation link to fix this issue.',
            1533818591
        );
    }

    /**
     * Determine configured default hash method and return an instance of the class representing it.
     *
     * @param string $mode 'FE' for frontend users, 'BE' for backend users
     * @return PasswordHashInterface Class instance that is configured as default hash method
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws InvalidPasswordHashException If configuration is broken
     */
    public function getDefaultHashInstance(string $mode): PasswordHashInterface
    {
        if ($mode !== 'FE' && $mode !== 'BE') {
            throw new \InvalidArgumentException('Mode must be either \'FE\' or \'BE\', ' . $mode . ' given.', 1533820041);
        }

        if (empty($GLOBALS['TYPO3_CONF_VARS'][$mode]['passwordHashing']['className'])
            || !isset($GLOBALS['TYPO3_CONF_VARS'][$mode]['passwordHashing']['options'])
            || !is_array($GLOBALS['TYPO3_CONF_VARS'][$mode]['passwordHashing']['options'])
        ) {
            throw new \LogicException(
                'passwordHashing configuration of ' . $mode . ' broken',
                1533950622
            );
        }

        $defaultHashClassName = $GLOBALS['TYPO3_CONF_VARS'][$mode]['passwordHashing']['className'];
        $defaultHashOptions = $GLOBALS['TYPO3_CONF_VARS'][$mode]['passwordHashing']['options'];
        $availableHashClasses = static::getRegisteredSaltedHashingMethods();

        if (!in_array($defaultHashClassName, $availableHashClasses, true)) {
            throw new InvalidPasswordHashException(
                'Configured default hash method ' . $defaultHashClassName . ' is not registered',
                1533820194
            );
        }
        $hashInstance =  GeneralUtility::makeInstance($defaultHashClassName, $defaultHashOptions);
        if (!$hashInstance instanceof PasswordHashInterface) {
            throw new \LogicException(
                'Configured default hash method ' . $defaultHashClassName . ' is not an instance of PasswordHashInterface',
                1533820281
            );
        }
        if (!$hashInstance->isAvailable()) {
            throw new InvalidPasswordHashException(
                'Configured default hash method ' . $defaultHashClassName . ' is not available. If'
                . ' the instance has just been upgraded, please log in to the standalone install tool'
                . ' at typo3/install.php to fix this. Follow the documentation link for more details.',
                1533822084
            );
        }
        return $hashInstance;
    }

    /**
     * Returns list of all registered hashing methods. Used eg. in
     * extension configuration to select the default hashing method.
     *
     * @return array
     * @throws \RuntimeException
     */
    public static function getRegisteredSaltedHashingMethods(): array
    {
        $saltMethods = $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'];
        if (!is_array($saltMethods) || empty($saltMethods)) {
            throw new \RuntimeException('No password hash methods configured', 1533948733);
        }
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods'])) {
            trigger_error(
                'Registering additional hash algorithms in $GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/saltedpasswords\'][\'saltMethods\']'
                . ' has been deprecated. Extend $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'availablePasswordHashAlgorithms\'] instead',
                E_USER_DEPRECATED
            );
            $configuredMethods = (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods'];
            if (!empty($configuredMethods)) {
                $saltMethods = array_merge($saltMethods, $configuredMethods);
            }
        }
        return $saltMethods;
    }

    /**
     * Obtains a salting hashing method instance.
     *
     * This function will return an instance of a class that implements
     * \TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashInterface
     *
     * Use parameter NULL to reset the factory!
     *
     * @param string|null $saltedHash Salted hashed password to determine the type of used method from or NULL to reset to the default type
     * @param string $mode The TYPO3 mode (FE or BE) saltedpasswords shall be used for
     * @return PasswordHashInterface|null An instance of salting hash method class or null if given hash is not supported
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public static function getSaltingInstance($saltedHash = '', $mode = TYPO3_MODE)
    {
        trigger_error(
            'This method is obsolete and will be removed in TYPO3 v10.0. Use get() and getDefaultHashInstance() instead.',
            E_USER_DEPRECATED
        );
        // Creating new instance when
        // * no instance existing
        // * a salted hash given to determine salted hashing method from
        // * a NULL parameter given to reset instance back to default method
        if (!is_object(self::$instance) || !empty($saltedHash) || $saltedHash === null) {
            // Determine method by checking the given hash
            if (!empty($saltedHash)) {
                $result = self::determineSaltingHashingMethod($saltedHash, $mode);
                if (!$result) {
                    self::$instance = null;
                }
            } else {
                $classNameToUse = SaltedPasswordsUtility::getDefaultSaltingHashingMethod($mode);
                self::$instance = GeneralUtility::makeInstance($classNameToUse);
            }
        }
        return self::$instance;
    }

    /**
     * Method tries to determine the salting hashing method used for given salt.
     *
     * Method implicitly sets the instance of the found method object in the class property when found.
     *
     * @param string $saltedHash
     * @param string $mode (optional) The TYPO3 mode (FE or BE) saltedpasswords shall be used for
     * @return bool TRUE, if salting hashing method has been found, otherwise FALSE
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public static function determineSaltingHashingMethod(string $saltedHash, $mode = TYPO3_MODE): bool
    {
        trigger_error(
            'This method is obsolete and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );
        $registeredMethods = static::getRegisteredSaltedHashingMethods();
        $defaultClassName = SaltedPasswordsUtility::getDefaultSaltingHashingMethod($mode);
        unset($registeredMethods[$defaultClassName]);
        // place the default method first in the order
        $registeredMethods = [$defaultClassName => $defaultClassName] + $registeredMethods;
        $methodFound = false;
        foreach ($registeredMethods as $method) {
            $objectInstance = GeneralUtility::makeInstance($method);
            if ($objectInstance instanceof PasswordHashInterface && $objectInstance->isAvailable()) {
                $methodFound = $objectInstance->isValidSaltedPW($saltedHash);
                if ($methodFound) {
                    self::$instance = $objectInstance;
                    break;
                }
            }
        }
        return $methodFound;
    }

    /**
     * Method sets a custom salting hashing method class.
     *
     * @param string $resource Object resource to use (e.g. \TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash::class)
     * @return PasswordHashInterface|null An instance of salting hashing method object or null
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public static function setPreferredHashingMethod(string $resource)
    {
        trigger_error('This method is obsolete and will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        self::$instance = null;
        $objectInstance = GeneralUtility::makeInstance($resource);
        if ($objectInstance instanceof PasswordHashInterface) {
            self::$instance = $objectInstance;
        }
        return self::$instance;
    }
}
