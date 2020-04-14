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

namespace TYPO3\CMS\Core\Package\Event;

/**
 * Event that is triggered before a number of packages should become active
 */
final class BeforePackageActivationEvent
{
    /**
     * @var array
     */
    private $packageKeys;

    public function __construct(array $packageKeys)
    {
        $this->packageKeys = $packageKeys;
    }

    public function getPackageKeys(): array
    {
        return $this->packageKeys;
    }
}
