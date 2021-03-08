<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Crypto\PasswordHashing;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory class to find and return hash instances of given hashed passwords
 * and to find and return default hash instances to hash new passwords.
 */
class PasswordHashFactory
{
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
        return $saltMethods;
    }
}
