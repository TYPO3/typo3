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

namespace TYPO3\CMS\Core\Imaging\Effect;

/**
 * Midtone brightness curve — 1.0 is neutral, <1 darker, >1 brighter.
 *
 * Out-of-range input is clamped to 0.1..10.0.
 *
 * @internal not part of TYPO3 Core API.
 */
final readonly class Gamma
{
    public float $value;

    public function __construct(float $value = 1.0)
    {
        $this->value = max(0.1, min(10.0, $value));
    }
}
