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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Type\BitSet;

class Capabilities extends BitSet
{
    /**
     * Capability for being browsable by (backend) users
     */
    public const CAPABILITY_BROWSABLE = 1;
    /**
     * Capability for publicly accessible storages (= accessible from the web)
     */
    public const CAPABILITY_PUBLIC = 2;
    /**
     * Capability for writable storages. This only signifies writability in
     * general - this might also be further limited by configuration.
     */
    public const CAPABILITY_WRITABLE = 4;
    /**
     * Whether identifiers contain hierarchy information (folder structure).
     */
    public const CAPABILITY_HIERARCHICAL_IDENTIFIERS = 8;

    /**
     * @param self::CAPABILITY_* $capability
     * @return $this
     */
    public function removeCapability(int $capability): self
    {
        $this->unset($capability);
        return $this;
    }

    /**
     * @param self::CAPABILITY_* ...$capabilities
     * @return $this
     */
    public function addCapabilities(int ...$capabilities): self
    {
        foreach ($capabilities as $capability) {
            $this->set($capability);
        }

        return $this;
    }

    /**
     * @param self::CAPABILITY_* $capability
     */
    public function hasCapability(int $capability): bool
    {
        return $this->get($capability);
    }
}
