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

namespace TYPO3Tests\BlogExample\Domain\Model;

/**
 * A robot - the machine with the record type "robot", the only one with a battery
 */
class Robot extends Machine
{
    protected string $battery = '';

    public function getBattery(): string
    {
        return $this->battery;
    }

    public function setBattery(string $battery): void
    {
        $this->battery = $battery;
    }
}
