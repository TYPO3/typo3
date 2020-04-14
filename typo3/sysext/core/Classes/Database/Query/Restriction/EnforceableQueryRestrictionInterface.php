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

namespace TYPO3\CMS\Core\Database\Query\Restriction;

/**
 * Can be added to QueryRestrictionInterface implementations.
 * Restrictions implementing this interface will not be removed when removeAll()
 * is called on the container and isEnforced() returns true.
 * It can be removed though, when explicitly calling removeByType()
 */
interface EnforceableQueryRestrictionInterface
{
    /**
     * When returning false, restriction will be removed when removeAll()
     * is called on the container
     *
     * @return bool
     */
    public function isEnforced(): bool;
}
