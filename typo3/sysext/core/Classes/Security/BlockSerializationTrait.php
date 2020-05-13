<?php

declare(strict_types = 1);

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

namespace TYPO3\CMS\Core\Security;

/**
 * Blocks object being using `unserialize()` invocations.
 *
 * Initially this trait blocked `serialize()` as well, which caused
 * a couple of side-effects in user-land code and is not problematic
 * from a security point of view.
 */
trait BlockSerializationTrait
{
    /**
     * Deny object deserialization.
     */
    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize ' . __CLASS__, 1588784142);
    }
}
