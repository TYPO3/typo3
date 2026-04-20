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
 * Rotate clockwise around the centre, in degrees.
 *
 * Rotation is cyclic, so any angle is normalized to the same rotation
 * within one turn, 0 up to but excluding 360: -90 becomes 270 and 450
 * becomes 90. A non-finite angle does not rotate.
 *
 * @internal not part of TYPO3 Core API.
 */
final readonly class Rotate
{
    public float $degrees;

    public function __construct(float $degrees = 0.0)
    {
        if (!is_finite($degrees)) {
            $degrees = 0.0;
        }
        $degrees = fmod($degrees, 360.0);
        if ($degrees < 0.0) {
            $degrees += 360.0;
        }
        // fmod() keeps the sign, so a negative full turn would end up as -0.0
        $this->degrees = $degrees + 0.0;
    }
}
