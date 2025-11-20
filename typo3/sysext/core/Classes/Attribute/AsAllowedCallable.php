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

namespace TYPO3\CMS\Core\Attribute;

/**
 * Defines a method that is allowed to be used as a user function.
 *
 * This is a non-functional back-port for a new behavior in TYPO3 v14.0,
 * see https://docs.typo3.org/permalink/changelog:breaking-108054-1762881326
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
final class AsAllowedCallable
{
    public const TAG_NAME = 'security.allowed-callable';
}
