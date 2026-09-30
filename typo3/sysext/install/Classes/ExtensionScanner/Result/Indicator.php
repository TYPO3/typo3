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

namespace TYPO3\CMS\Install\ExtensionScanner\Result;

/**
 * How reliable a single extension scanner match is.
 *
 * A strong match is a reliable one: a false positive is unlikely. A weak match is a probable
 * one, typically a bare method or property name that could not be resolved to a class, and it
 * may well be a false positive. Consumers reporting numbers should keep the two apart.
 *
 * The backed values are part of the JSON API of the install tool and must not change.
 *
 * @internal This enum is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
enum Indicator: string
{
    case Strong = 'strong';
    case Weak = 'weak';
}
