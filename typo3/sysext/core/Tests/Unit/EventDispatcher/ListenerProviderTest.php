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

namespace TYPO3\CMS\Core\Tests\Unit\EventDispatcher;

use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ListenerProviderTest extends UnitTestCase
{
    /**
     * @var ContainerInterface|ObjectProphecy
     */
    protected $containerProphecy;

    /**
     * @var ListenerProvider
     */
    protected $listenerProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->containerProphecy = $this->prophesize();
        $this->containerProphecy->willImplement(ContainerInterface::class);

        $this->listenerProvider = new ListenerProvider(
            $this->containerProphecy->reveal()
        );
    }

    /**
     * @test
     */
    public function implementsPsrInterface()
    {
        self::assertInstanceOf(ListenerProviderInterface::class, $this->listenerProvider);
    }

    /**
     * @test
     */
    public function addedListenersAreReturnedByGetAllListenerDefinitions()
    {
        $this->listenerProvider->addListener('Event\\Name', 'listener1');
        $this->listenerProvider->addListener('Event\\Name', 'listener2', 'methodName');

        self::assertEquals($this->listenerProvider->getAllListenerDefinitions(), [
            'Event\\Name' => [
                [ 'service' => 'listener1', 'method' => null ],
                [ 'service' => 'listener2', 'method' => 'methodName' ],
            ]
        ]);
    }

    /**
     * @test
     * @dataProvider listeners
     */
    public function dispatchesEvent($listener, string $method = null)
    {
        $event = new \stdClass();
        $event->invoked = 0;

        $this->containerProphecy->get('listener')->willReturn($listener);
        $this->listenerProvider->addListener(\stdClass::class, 'listener', $method);

        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals(1, $event->invoked);
    }

    /**
     * @test
     * @dataProvider listeners
     */
    public function associatesToEventParentClass($listener, string $method = null)
    {
        $extendedEvent = new class() extends \stdClass {
            public $invoked = 0;
        };

        $this->containerProphecy->get('listener')->willReturn($listener);
        $this->listenerProvider->addListener(\stdClass::class, 'listener', $method);
        foreach ($this->listenerProvider->getListenersForEvent($extendedEvent) as $listener) {
            $listener($extendedEvent);
        }

        self::assertEquals(1, $extendedEvent->invoked);
    }

    /**
     * @test
     * @dataProvider listeners
     */
    public function associatesToImplementedInterfaces($listener, string $method = null)
    {
        $eventImplementation = new class() implements \IteratorAggregate {
            public $invoked = 0;

            public function getIterator(): \Traversable
            {
                throw new \BadMethodCallException('Test', 1586942436);
            }
        };

        $this->containerProphecy->get('listener')->willReturn($listener);
        $this->listenerProvider->addListener(\IteratorAggregate::class, 'listener', $method);
        foreach ($this->listenerProvider->getListenersForEvent($eventImplementation) as $listener) {
            $listener($eventImplementation);
        }

        self::assertEquals(1, $eventImplementation->invoked);
    }

    /**
     * @test
     */
    public function addListenerPreservesOrder()
    {
        $this->listenerProvider->addListener(\stdClass::class, 'listener1');
        $this->listenerProvider->addListener(\stdClass::class, 'listener2');

        $event = new \stdClass();
        $event->sequence = '';
        $this->containerProphecy->get('listener1')->willReturn(function (object $event): void {
            $event->sequence .= 'a';
        });
        $this->containerProphecy->get('listener2')->willReturn(function (object $event): void {
            $event->sequence .= 'b';
        });
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('ab', $event->sequence);
    }

    /**
     * @test
     */
    public function throwsExceptionForInvalidCallable()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1549988537);

        $event = new \stdClass();
        $this->containerProphecy->get('listener')->willReturn(new \stdClass());
        $this->listenerProvider->addListener(\stdClass::class, 'listener');
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }
    }

    /**
     * Provider for event listeners.
     * Either an invokable, class/method combination or a closure.
     */
    public function listeners(): array
    {
        return [
            [
                // Invokable
                'listener' => new class() {
                    public function __invoke(object $event): void
                    {
                        $event->invoked = 1;
                    }
                },
                'method' => null,
            ],
            [
                // Class + method
                'listener' => new class() {
                    public function onEvent(object $event): void
                    {
                        $event->invoked = 1;
                    }
                },
                'method' => 'onEvent',
            ],
            [
                // Closure
                'listener' => function (object $event): void {
                    $event->invoked = 1;
                },
                'method' => null,
            ]
        ];
    }
}
