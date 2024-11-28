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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class EventDispatcherTest extends UnitTestCase
{
    public static function callables(): array
    {
        return [
            'Invokable' => [
                new class () {
                    public function __invoke(object $event): void
                    {
                        $event->invoked += 1;
                    }
                },
            ],
            'Class + method' => [
                [
                    new class () {
                        public function onEvent(object $event): void
                        {
                            $event->invoked += 1;
                        }
                    },
                    'onEvent',
                ],
            ],
            'Closure' => [
                static function (object $event): void {
                    $event->invoked += 1;
                },
            ],
        ];
    }

    #[DataProvider('callables')]
    #[Test]
    public function dispatchesEvent(callable $callable): void
    {
        $event = new \stdClass();
        $event->invoked = 0;

        $listenerProviderMock = $this->createMock(ListenerProviderInterface::class);
        $listenerProviderMock->method('getListenersForEvent')->with($event)->willReturnCallback(static function (object $event) use ($callable): iterable {
            yield $callable;
        });

        $subject = new EventDispatcher($listenerProviderMock);
        $ret = $subject->dispatch($event);
        self::assertSame($event, $ret);
        self::assertEquals(1, $event->invoked);
    }

    #[DataProvider('callables')]
    #[Test]
    public function doesNotDispatchStoppedEvent(callable $callable): void
    {
        $event = new class () implements StoppableEventInterface {
            public int $invoked = 0;

            public function isPropagationStopped(): bool
            {
                return true;
            }
        };

        $listenerProviderMock = $this->createMock(ListenerProviderInterface::class);
        $listenerProviderMock->method('getListenersForEvent')->with($event)->willReturnCallback(static function (object $event) use ($callable): iterable {
            yield $callable;
        });

        $subject = new EventDispatcher($listenerProviderMock);
        $ret = $subject->dispatch($event);
        self::assertSame($event, $ret);
        self::assertEquals(0, $event->invoked);
    }

    #[DataProvider('callables')]
    #[Test]
    public function dispatchesMultipleListeners(callable $callable): void
    {
        $event = new \stdClass();
        $event->invoked = 0;

        $listenerProviderMock = $this->createMock(ListenerProviderInterface::class);
        $listenerProviderMock->method('getListenersForEvent')->with($event)->willReturnCallback(static function (object $event) use ($callable): iterable {
            yield $callable;
            yield $callable;
        });

        $subject = new EventDispatcher($listenerProviderMock);
        $ret = $subject->dispatch($event);
        self::assertSame($event, $ret);
        self::assertEquals(2, $event->invoked);
    }

    #[DataProvider('callables')]
    #[Test]
    public function stopsOnStoppedEvent(callable $callable): void
    {
        $event = new class () implements StoppableEventInterface {
            public int $invoked = 0;
            public bool $stopped = false;

            public function isPropagationStopped(): bool
            {
                return $this->stopped;
            }
        };

        $listenerProviderMock = $this->createMock(ListenerProviderInterface::class);
        $listenerProviderMock->method('getListenersForEvent')->with($event)->willReturnCallback(static function (object $event) use ($callable): iterable {
            yield $callable;
            yield static function (object $event): void {
                $event->invoked += 1;
                $event->stopped = true;
            };
            yield $callable;
        });

        $subject = new EventDispatcher($listenerProviderMock);
        $ret = $subject->dispatch($event);
        self::assertSame($event, $ret);
        self::assertEquals(2, $event->invoked);
    }

    #[Test]
    public function listenerExceptionIsPropagated(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionCode(1563270337);

        $event = new \stdClass();

        $listenerProviderMock = $this->createMock(ListenerProviderInterface::class);
        $listenerProviderMock->method('getListenersForEvent')->with($event)->willReturnCallback(static function (object $event): iterable {
            yield static function (object $event): void {
                throw new \BadMethodCallException('some invalid state', 1563270337);
            };
        });

        $subject = new EventDispatcher($listenerProviderMock);
        $subject->dispatch($event);
    }
}
