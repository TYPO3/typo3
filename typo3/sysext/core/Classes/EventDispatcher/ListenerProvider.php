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
use TYPO3\CMS\Core\Localization\Event\ModifyLanguagePackRemoteBaseUrlEvent;
use TYPO3\CMS\Core\Localization\Event\ModifyLanguagePacksEvent;

/**
 * Provides Listeners configured with the symfony service
 * tag 'event.listener'.
 *
 * @internal
 */
class ListenerProvider implements ListenerProviderInterface
{
    /**
     * Maps new event class names to their deprecated predecessors.
     * Listeners registered for a deprecated alias will still be called
     * when the new event is dispatched, with a deprecation notice triggered.
     *
     * @todo Remove entries in TYPO3 v15.
     * @var array<class-string, list<string>>
     */
    private const DEPRECATED_EVENT_ALIASES = [
        ModifyLanguagePacksEvent::class => [
            'TYPO3\\CMS\\Install\\Service\\Event\\ModifyLanguagePacksEvent',
        ],
        ModifyLanguagePackRemoteBaseUrlEvent::class => [
            'TYPO3\\CMS\\Install\\Service\\Event\\ModifyLanguagePackRemoteBaseUrlEvent',
        ],
    ];

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
     * @internal
     */
    public function addListener(string $event, string $service, ?string $method = null, ?string $identifier = null): void
    {
        $this->listeners[$event][$identifier ?? $service] = [
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

    public function getListenersForEvent(object $event): iterable
    {
        $eventClasses = [get_class($event)];
        $classParents = class_parents($event);
        $classInterfaces = class_implements($event);
        if (!empty($classParents)) {
            array_push($eventClasses, ...array_values($classParents));
        }
        if (!empty($classInterfaces)) {
            array_push($eventClasses, ...array_values($classInterfaces));
        }
        // @todo Remove deprecated alias handling in TYPO3 v15.
        $deprecatedAliases = self::DEPRECATED_EVENT_ALIASES[get_class($event)] ?? [];
        foreach ($deprecatedAliases as $oldClassName) {
            if (!in_array($oldClassName, $eventClasses, true)) {
                $eventClasses[] = $oldClassName;
            }
        }
        foreach ($eventClasses as $className) {
            if (isset($this->listeners[$className])) {
                // @todo Remove deprecated alias trigger_error in TYPO3 v15.
                if (in_array($className, $deprecatedAliases, true)) {
                    trigger_error(
                        sprintf(
                            'Listening to "%s" has been deprecated, use "%s" instead.',
                            $className,
                            get_class($event),
                        ),
                        E_USER_DEPRECATED,
                    );
                }
                foreach ($this->listeners[$className] as $listener) {
                    yield $this->getCallable($listener['service'], $listener['method']);
                }
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function getCallable(string $service, ?string $method = null): callable
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
