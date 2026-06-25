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

namespace TYPO3\CMS\Core\Type;

/**
 * Classifies records that don't have an actual persisted counter-part and only
 * exist virtually for semantic reasons. New cases may be added in the future to
 * represent entities that are not persisted as a database row at all.
 *
 * @internal
 */
enum VirtualRecord
{
    /**
     * A pages record that is itself at the root of the page tree (pid = 0).
     * The record acts as its own page context.
     */
    case RootPage;
}
