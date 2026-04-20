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
 * Twirl pixels around the centre — closer pixels rotate more.
 *
 * Out-of-range input is clamped to -720..720.
 *
 * @internal not part of TYPO3 Core API.
 */
final readonly class Swirl
{
    public int $degrees;

    public function __construct(int $degrees = 180)
    {
        $this->degrees = max(-720, min(720, $degrees));
    }
}
