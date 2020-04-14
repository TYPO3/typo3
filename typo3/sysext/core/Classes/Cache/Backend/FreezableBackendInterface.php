<?php

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

namespace TYPO3\CMS\Core\Cache\Backend;

/**
 * A contract for a cache backend which can be frozen.
 */
interface FreezableBackendInterface extends BackendInterface
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
     */
    public function freeze();

    /**
     * Tells if this backend is frozen.
     *
     * @return bool
     */
    public function isFrozen();
}
