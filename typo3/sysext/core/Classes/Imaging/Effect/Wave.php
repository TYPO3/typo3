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
 * Sinusoidal ripple distortion. Wavelength of 0 would collapse the
 * distortion to nothing, so the bound starts at 1. Out-of-range input
 * is clamped to amplitude 0..99 and wavelength 1..99.
 *
 * @internal not part of TYPO3 Core API.
 */
final readonly class Wave
{
    public int $amplitude;
    public int $wavelength;

    public function __construct(int $amplitude = 10, int $wavelength = 30)
    {
        $this->amplitude = max(0, min(99, $amplitude));
        $this->wavelength = max(1, min(99, $wavelength));
    }
}
