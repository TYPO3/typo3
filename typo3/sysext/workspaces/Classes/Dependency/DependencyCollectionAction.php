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
 * Defines the action context for workspace dependency collection.
 */
enum DependencyCollectionAction
{
    // Publishing workspace changes to live
    case Publish;
    // Changing the workflow stage of workspace records (e.g. "editing" → "ready to publish")
    case StageChange;
    // Discarding workspace changes, removing the workspace version
    case Discard;
    // Displaying dependency relationships in the workspace review module
    case Display;
}
