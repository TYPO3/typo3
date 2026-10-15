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

namespace TYPO3\CMS\Core\Resource\Driver;

use League\Flysystem\PathNormalizer;
use League\Flysystem\PathTraversalDetected;

/**
 * Passes the paths of canonicalized identifiers to Flysystem as they are.
 *
 * The default normalizer of Flysystem rejects every path with a character of
 * the unicode category "other" and turns backslashes into slashes. File names
 * with a zero-width joiner, as in emoji sequences, or with a zero-width space
 * exist in storages and have to stay accessible.
 *
 * @internal
 */
final readonly class FlysystemPathNormalizer implements PathNormalizer
{
    public function normalizePath(string $path): string
    {
        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '..') {
                throw PathTraversalDetected::forPath($path);
            }
            if ($part !== '' && $part !== '.') {
                $parts[] = $part;
            }
        }
        return implode('/', $parts);
    }
}
