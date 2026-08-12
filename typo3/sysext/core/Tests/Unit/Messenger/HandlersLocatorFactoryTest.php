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

namespace TYPO3\CMS\Core\Tests\Unit\Messenger;

use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use TYPO3\CMS\Core\Messenger\HandlersLocatorFactory;
use TYPO3\CMS\Core\Tests\Unit\Messenger\Fixtures\RoutedMessage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class HandlersLocatorFactoryTest extends UnitTestCase
{
    private function createFactory(): HandlersLocatorFactory
    {
        // The container is never touched while listing handlers - the descriptor
        // callable only resolves the service when the handler is actually invoked.
        $container = self::createStub(ContainerInterface::class);
        return new HandlersLocatorFactory($container);
    }

    /**
     * @param iterable<HandlerDescriptor> $handlers
     * @return list<string>
     */
    private function aliasesOf(iterable $handlers): array
    {
        $aliases = [];
        foreach ($handlers as $handler) {
            $aliases[] = $handler->getOption('alias');
        }
        return $aliases;
    }

    #[Test]
    public function handlerWithoutFromTransportRunsForEveryReceivingTransport(): void
    {
        $factory = $this->createFactory();
        $factory->addHandler(RoutedMessage::class, 'handler.all', '__invoke');
        $locator = $factory->createHandlersLocator();

        self::assertSame(
            ['handler.all::__invoke'],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage(), [new ReceivedStamp('transport_a')]))),
        );
        self::assertSame(
            ['handler.all::__invoke'],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage()))),
        );
    }

    #[Test]
    public function handlerBoundToTransportOnlyRunsForThatTransport(): void
    {
        $factory = $this->createFactory();
        $factory->addHandler(RoutedMessage::class, 'handler.a', '__invoke', 'transport_a');
        $factory->addHandler(RoutedMessage::class, 'handler.b', '__invoke', 'transport_b');
        $factory->addHandler(RoutedMessage::class, 'handler.all', '__invoke');
        $locator = $factory->createHandlersLocator();

        self::assertSame(
            ['handler.a::__invoke', 'handler.all::__invoke'],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage(), [new ReceivedStamp('transport_a')]))),
        );
        self::assertSame(
            ['handler.b::__invoke', 'handler.all::__invoke'],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage(), [new ReceivedStamp('transport_b')]))),
        );
    }

    #[Test]
    public function transportBoundHandlerIsIgnoredForUnknownTransport(): void
    {
        $factory = $this->createFactory();
        $factory->addHandler(RoutedMessage::class, 'handler.a', '__invoke', 'transport_a');
        $factory->addHandler(RoutedMessage::class, 'handler.all', '__invoke');
        $locator = $factory->createHandlersLocator();

        self::assertSame(
            ['handler.all::__invoke'],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage(), [new ReceivedStamp('other')]))),
        );
    }

    #[Test]
    public function handlerBoundToMultipleTransportsRunsForEachOfThem(): void
    {
        $factory = $this->createFactory();
        $factory->addHandler(RoutedMessage::class, 'handler.multi', '__invoke', ['transport_a', 'transport_b']);
        $locator = $factory->createHandlersLocator();

        self::assertSame(
            ['handler.multi::__invoke'],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage(), [new ReceivedStamp('transport_a')]))),
        );
        self::assertSame(
            ['handler.multi::__invoke'],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage(), [new ReceivedStamp('transport_b')]))),
        );
        self::assertSame(
            [],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage(), [new ReceivedStamp('transport_c')]))),
        );
    }

    #[Test]
    public function duplicateEmptyAndWhitespaceTransportEntriesAreNormalized(): void
    {
        $factory = $this->createFactory();
        $factory->addHandler(RoutedMessage::class, 'handler.multi', '__invoke', ['transport_a', ' transport_a ', '', 'transport_b']);
        $locator = $factory->createHandlersLocator();

        // 'transport_a' (incl. its whitespace duplicate) and 'transport_b' bind;
        // the empty entry is dropped. The handler still runs at most once per
        // received transport.
        self::assertSame(
            ['handler.multi::__invoke'],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage(), [new ReceivedStamp('transport_a')]))),
        );
        self::assertSame(
            ['handler.multi::__invoke'],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage(), [new ReceivedStamp('transport_b')]))),
        );
    }

    #[Test]
    public function emptyTransportListLeavesHandlerUnbound(): void
    {
        $factory = $this->createFactory();
        $factory->addHandler(RoutedMessage::class, 'handler.empty', '__invoke', []);
        $factory->addHandler(RoutedMessage::class, 'handler.blank', '__invoke', ['', '  ']);
        $locator = $factory->createHandlersLocator();

        self::assertSame(
            ['handler.empty::__invoke', 'handler.blank::__invoke'],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage(), [new ReceivedStamp('any')]))),
        );
    }

    #[Test]
    public function withoutReceivedStampTransportBoundHandlersAreNotFiltered(): void
    {
        // A dispatch that never went through a transport (no ReceivedStamp) must
        // run every matching handler - the transport binding only narrows down
        // messages received from a worker.
        $factory = $this->createFactory();
        $factory->addHandler(RoutedMessage::class, 'handler.a', '__invoke', 'transport_a');
        $factory->addHandler(RoutedMessage::class, 'handler.all', '__invoke');
        $locator = $factory->createHandlersLocator();

        self::assertSame(
            ['handler.a::__invoke', 'handler.all::__invoke'],
            $this->aliasesOf($locator->getHandlers(new Envelope(new RoutedMessage()))),
        );
    }
}
