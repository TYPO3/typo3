<?php
namespace TYPO3\CMS\Core\Cache\Backend;

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
 * A contract for a cache backend which is capable of storing, retrieving and
 * including PHP source code.
 *
 * @api
 */
interface PhpCapableBackendInterface extends \TYPO3\CMS\Core\Cache\Backend\BackendInterface
{
    /**
     * Loads PHP code from the cache and require_onces it right away.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed Potential return value from the include operation
     * @api
     */
    public function requireOnce($entryIdentifier);
}
