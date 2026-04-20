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
 * Charcoal drawing effect — dark pencil lines on a light background.
 *
 * Out-of-range input is clamped to 1..5.
 *
 * @internal not part of TYPO3 Core API.
 */
final readonly class Charcoal
{
    public int $radius;

    public function __construct(int $radius = 1)
    {
        $this->radius = max(1, min(5, $radius));
    }
}
