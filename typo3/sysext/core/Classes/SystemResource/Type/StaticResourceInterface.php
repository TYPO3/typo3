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

namespace TYPO3\CMS\Core\SystemResource\Type;

/**
 * All static resources in TYPO3 have one thing in common
 * and that is, that they can be identified with a unique string.
 * This means they can be constructed from a string by SystemResourceFactory,
 * and they can be cast to a unique string representation.
 *
 * Currently absolute and relative URLs (UriResource), files within an extension directory (PackageResource),
 * and file abstraction layer (FAL) files serve as static resources
 * to be referenced e.g. as public URL (see PublicResourceInterface)
 *
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
interface StaticResourceInterface extends \Stringable
{
    public function getResourceIdentifier(): string;
}
