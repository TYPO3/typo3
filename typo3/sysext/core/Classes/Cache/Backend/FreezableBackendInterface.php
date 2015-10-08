<?php
namespace TYPO3\CMS\Core\Cache\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * A contract for a cache backend which can be frozen.
 *
 * @api
 */
interface FreezableBackendInterface extends \TYPO3\CMS\Core\Cache\Backend\BackendInterface
{
    /**
     * Freezes this cache backend.
     *
     * All data in a frozen backend remains unchanged and methods which try to add
     * or modify data result in an exception thrown. Possible expiry times of
     * individual cache entries are ignored.
     *
     * On the positive side, a frozen cache backend is much faster on read access.
     * A frozen backend can only be thawn by calling the flush() method.
     *
     * @return void
     */
    public function freeze();

    /**
     * Tells if this backend is frozen.
     *
     * @return bool
     */
    public function isFrozen();
}
