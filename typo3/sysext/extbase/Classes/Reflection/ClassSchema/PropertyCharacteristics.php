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

namespace TYPO3\CMS\Extbase\Reflection\ClassSchema;

use TYPO3\CMS\Core\Type\BitSet;

/**
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final class PropertyCharacteristics extends BitSet
{
    public const VISIBILITY_PRIVATE = 1 << 0;
    public const VISIBILITY_PROTECTED = 1 << 1;
    public const VISIBILITY_PUBLIC = 1 << 2;
    public const IS_STATIC = 1 << 3;
    public const ANNOTATED_LAZY = 1 << 4;
    public const ANNOTATED_TRANSIENT = 1 << 5;
    public const ANNOTATED_INJECT = 1 << 6;
}
