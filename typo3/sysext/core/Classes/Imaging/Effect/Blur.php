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
 * Soften the image.
 *
 * Out-of-range input is clamped to 1..99; the value object cannot
 * represent an ineffective or runaway blur.
 *
 * @internal not part of TYPO3 Core API.
 */
final readonly class Blur
{
    public int $strength;

    public function __construct(int $strength = 10)
    {
        $this->strength = max(1, min(99, $strength));
    }
}
