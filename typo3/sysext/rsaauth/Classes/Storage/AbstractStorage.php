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
 * This class contains the abstract storage for the RSA private keys
 */
abstract class AbstractStorage
{
    /**
     * Retrieves the key from the storage
     *
     * @return string The key or NULL
     */
    abstract public function get();

    /**
     * Stores the key in the storage
     *
     * @param string $key The key
     */
    abstract public function put($key);
}
