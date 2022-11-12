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

namespace TYPO3\CMS\Core\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * An event dispatcher that does nothing. Useful for testing.
 *
 * Also used in scopes like the install tool where classes have dependencies
 * to EventDispatcherInterface but should not actively dispatch events.
 */
final class NoopEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): object
    {
        return $event;
    }
}
