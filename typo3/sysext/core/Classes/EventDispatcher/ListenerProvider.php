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

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Provides Listeners configured with the symfony service
 * tag 'event.listener'.
 *
 * @internal
 */
class ListenerProvider implements ListenerProviderInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $listeners = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Not part of the public API, used in the generated service factor for this class,
     *
     * @param string $event
     * @param string $service
     * @param string|null $method
     * @internal
     */
    public function addListener(string $event, string $service, string $method = null): void
    {
        $this->listeners[$event][] = [
            'service' => $service,
            'method' => $method,
        ];
    }

    /**
     * Not part of the public API, only used for debugging purposes
     *
     * @internal
     */
    public function getAllListenerDefinitions(): array
    {
        return $this->listeners;
    }

    /**
     * @inheritdoc
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventClasses = [get_class($event)];
        $classParents = class_parents($event);
        $classInterfaces = class_implements($event);
        if (is_array($classParents) && !empty($classParents)) {
            array_push($eventClasses, ...array_values($classParents));
        }
        if (is_array($classInterfaces) && !empty($classInterfaces)) {
            array_push($eventClasses, ...array_values($classInterfaces));
        }
        foreach ($eventClasses as $className) {
            if (isset($this->listeners[$className])) {
                foreach ($this->listeners[$className] as $listener) {
                    yield $this->getCallable($listener['service'], $listener['method']);
                }
            }
        }
    }

    /**
     * @param string $service
     * @param string|null $method
     * @return callable
     * @throws \InvalidArgumentException
     */
    protected function getCallable(string $service, string $method = null): callable
    {
        $target = $this->container->get($service);
        if ($method !== null) {
            // Dispatch to configured method name instead of __invoke()
            $target = [ $target, $method ];
        }

        if (!is_callable($target)) {
            throw new \InvalidArgumentException(
                sprintf('Event listener "%s%s%s" is not callable"', $service, ($method !== null ? '::' : ''), $method),
                1549988537
            );
        }

        return $target;
    }
}
