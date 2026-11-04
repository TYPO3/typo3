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

namespace TYPO3\CMS\Workspaces\Dependency;

/**
 * Defines in which direction a reference is followed during workspace dependency collection.
 */
enum DependencyLookupDirection
{
    // From the record owning the field to the referenced record, which becomes a child
    case Children;
    // From the referenced record to the record owning the field, which becomes a parent
    case Parents;
}
