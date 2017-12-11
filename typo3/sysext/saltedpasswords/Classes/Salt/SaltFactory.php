<?php
declare(strict_types=1);
namespace TYPO3\CMS\Saltedpasswords\Salt;

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
use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;

/**
 * Class that implements Blowfish salted hashing based on PHP's
 * crypt() function.
 */
class SaltFactory
{
    /**
     * An instance of the salted hashing method.
     * This member is set in the getSaltingInstance() function.
     *
     * @var SaltInterface
     */
    protected static $instance = null;

    /**
     * Returns list of all registered hashing methods. Used eg. in
     * extension configuration to select the default hashing method.
     *
     * @return array
     */
    public static function getRegisteredSaltedHashingMethods(): array
    {
        $saltMethods = static::getDefaultSaltMethods();
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods'])) {
            $configuredMethods = (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods'];
            if (!empty($configuredMethods)) {
                if (isset($configuredMethods[0])) {
                    // ensure the key of the array is not numeric, but a class name
                    foreach ($configuredMethods as $method) {
                        $saltMethods[$method] = $method;
                    }
                } else {
                    $saltMethods = array_merge($saltMethods, $configuredMethods);
                }
            }
        }
        return $saltMethods;
    }

    /**
     * Returns an array with default salt method class names.
     *
     * @return array
     */
    protected static function getDefaultSaltMethods(): array
    {
        return [
            Md5Salt::class => Md5Salt::class,
            BlowfishSalt::class => BlowfishSalt::class,
            PhpassSalt::class => PhpassSalt::class,
            Pbkdf2Salt::class => Pbkdf2Salt::class
        ];
    }

    /**
     * Obtains a salting hashing method instance.
     *
     * This function will return an instance of a class that implements
     * \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface
     *
     * Use parameter NULL to reset the factory!
     *
     * @param string|null $saltedHash Salted hashed password to determine the type of used method from or NULL to reset to the default type
     * @param string $mode The TYPO3 mode (FE or BE) saltedpasswords shall be used for
     * @return SaltInterface|null An instance of salting hash method class or null if given hash is not supported
     */
    public static function getSaltingInstance($saltedHash = '', $mode = TYPO3_MODE)
    {
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
                $availableClasses = static::getRegisteredSaltedHashingMethods();
                self::$instance = GeneralUtility::makeInstance($availableClasses[$classNameToUse]);
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
     */
    public static function determineSaltingHashingMethod(string $saltedHash, $mode = TYPO3_MODE): bool
    {
        $registeredMethods = static::getRegisteredSaltedHashingMethods();
        $defaultClassName = SaltedPasswordsUtility::getDefaultSaltingHashingMethod($mode);
        $defaultReference = $registeredMethods[$defaultClassName];
        unset($registeredMethods[$defaultClassName]);
        // place the default method first in the order
        $registeredMethods = [$defaultClassName => $defaultReference] + $registeredMethods;
        $methodFound = false;
        foreach ($registeredMethods as $method) {
            $objectInstance = GeneralUtility::makeInstance($method);
            if ($objectInstance instanceof SaltInterface) {
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
     * @param string $resource Object resource to use (e.g. \TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::class)
     * @return SaltInterface|null An instance of salting hashing method object or null
     */
    public static function setPreferredHashingMethod(string $resource)
    {
        self::$instance = null;
        $objectInstance = GeneralUtility::makeInstance($resource);
        if ($objectInstance instanceof SaltInterface) {
            self::$instance = $objectInstance;
        }
        return self::$instance;
    }
}
