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

namespace TYPO3\CMS\Core\Tests\Unit\Fixtures\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * A mock event dispatcher that does nothing but records what events are passed.
 *
 * Use in tests for classes that depend in an event dispatcher
 * but interaction with the dispatcher is not what's being tested,
 * or where "event X is triggered" is the behavior being tested.
 */
class MockEventDispatcher implements EventDispatcherInterface
{
    /** @var object[] */
    public array $events = [];
    public function dispatch(object $event): object
    {
        $this->events[] = $event;
        return $event;
    }
}
