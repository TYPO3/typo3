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

namespace TYPO3\CMS\Extbase\Reflection\DocBlock\Tags;

use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\StaticMethod;
use phpDocumentor\Reflection\DocBlock\Tags\Formatter;

/**
 * Class TYPO3\CMS\Extbase\Reflection\DocBlock\Tags\Null_
 */
class Null_ implements Tag, StaticMethod
{
    public function getName(): string
    {
        return 'Null';
    }

    public static function create($body): void
    {
    }

    public function render(?Formatter $formatter = null): string
    {
        return '';
    }

    public function __toString(): string
    {
        return '';
    }
}
