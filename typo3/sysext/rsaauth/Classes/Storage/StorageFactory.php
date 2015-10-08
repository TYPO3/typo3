<?php
namespace TYPO3\CMS\Rsaauth\Storage;

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

/**
 * This class contains a factory for the RSA backends.
 */
class StorageFactory
{
    /**
     * A list of all available storages. Currently this list cannot be extended.
     * This is for security reasons to avoid inserting some dummy storage to
     * the list.
     *
     * @var string
     */
    protected static $preferredStorage = \TYPO3\CMS\Rsaauth\Storage\SplitStorage::class;

    /**
     * An instance of the storage. This member is set in the getStorage() function.
     * It will not be an abstract storage as shown below but a real class, which is
     * derived from the \TYPO3\CMS\Rsaauth\Storage\AbstractStorage.
     *
     * <!-- Please, keep the variable type! It helps IDEs to provide autocomplete! -->
     *
     * @var \TYPO3\CMS\Rsaauth\Storage\AbstractStorage
     */
    protected static $storageInstance = null;

    /**
     * Obtains a storage. This function will return a non-abstract class, which
     * is derived from \TYPO3\CMS\Rsaauth\Storage\AbstractStorage. Applications should
     * not use any methods that are not declared in the \TYPO3\CMS\Rsaauth\Storage\AbstractStorage.
     *
     * @return \TYPO3\CMS\Rsaauth\Storage\AbstractStorage A storage
     */
    public static function getStorage()
    {
        if (self::$storageInstance === null) {
            self::$storageInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj(self::$preferredStorage);
        }
        return self::$storageInstance;
    }

    /**
     * Sets the preferred storage to the factory. This method can be called from
     * another extension or ext_localconf.php
     *
     * @param string $preferredStorage Preferred storage
     * @return void
     */
    public static function setPreferredStorage($preferredStorage)
    {
        self::$preferredStorage = $preferredStorage;
    }
}
