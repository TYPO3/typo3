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

namespace TYPO3\CMS\Core\Utility;

use cogpowered\FineDiff\Diff;
use cogpowered\FineDiff\Granularity\Character;
use cogpowered\FineDiff\Granularity\Word;

/**
 * Helper service to create a diff HTML of two strings.
 * It is currently a facade for lolli42/finediff.
 */
readonly class DiffUtility
{
    public function diff(string $from, string $to, DiffGranularity $granularity = DiffGranularity::WORD): string
    {
        return (new Diff($granularity === DiffGranularity::WORD ? new Word() : new Character()))->render($from, $to);
    }
}
