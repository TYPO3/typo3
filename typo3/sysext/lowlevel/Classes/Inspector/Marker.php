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

namespace TYPO3\CMS\Lowlevel\Inspector;

/**
 * A substitute for a value that cannot be shown as data, e.g. "INF", "*RECURSION*",
 * "Resource id #12" or the name of a callable. Shown unquoted in tree and flat output,
 * as a string in JSON.
 *
 * @internal
 */
final readonly class Marker
{
    public function __construct(
        public string $text,
    ) {}
}
