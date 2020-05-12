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

namespace TYPO3\CMS\Core\Security;

/**
 * Blocks object being using in `serialize()` and `unserialize()` invocations.
 */
trait BlockSerializationTrait
{
    /**
     * Deny object serialization.
     */
    public function __sleep()
    {
        throw new \BadMethodCallException('Cannot serialize ' . __CLASS__, 1588784141);
    }

    /**
     * Deny object deserialization.
     */
    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize ' . __CLASS__, 1588784142);
    }
}
