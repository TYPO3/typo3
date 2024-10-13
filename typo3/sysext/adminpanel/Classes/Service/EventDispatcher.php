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

namespace TYPO3\CMS\Adminpanel\Service;

/**
 * The admin panel event dispatcher records all dispatched events
 */
class EventDispatcher extends \TYPO3\CMS\Core\EventDispatcher\EventDispatcher
{
    private array $dispatchedEvents = [];

    public function getDispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }

    public function dispatch(object $event): object
    {
        $eventClass = get_class($event);
        if (isset($this->dispatchedEvents[$eventClass])) {
            $this->dispatchedEvents[$eventClass]++;
        } else {
            $this->dispatchedEvents[$eventClass] = 1;
        }
        return parent::dispatch($event);
    }
}
