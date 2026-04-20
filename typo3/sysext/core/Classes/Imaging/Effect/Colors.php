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
 * Reduce the image palette to N colours (posterise).
 *
 * Out-of-range input is clamped to 2..255.
 *
 * @internal not part of TYPO3 Core API.
 */
final readonly class Colors
{
    public int $count;

    public function __construct(int $count = 16)
    {
        $this->count = max(2, min(255, $count));
    }
}
