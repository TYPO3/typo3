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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\RuntimeException;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use TYPO3\CMS\Core\Messenger\TransportLocator;
use TYPO3\CMS\Core\Tests\Unit\Messenger\Fixtures\AuditableInterface;
use TYPO3\CMS\Core\Tests\Unit\Messenger\Fixtures\AuditableMessage;
use TYPO3\CMS\Core\Tests\Unit\Messenger\Fixtures\RoutedMessage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TransportLocatorTest extends UnitTestCase
{
    /**
     * Transport identifiers the (mocked) senders locator knows about.
     */
    private const REGISTERED_SENDERS = ['doctrine', 'default', 'audit', 'nswild', 'iface', 'x'];

    /**
     * The most specific namespace wildcard produced by
     * HandlersLocator::listTypes() for the fixture messages.
     */
    private static function namespaceWildcard(): string
    {
        return substr(RoutedMessage::class, 0, (int)strrpos(RoutedMessage::class, '\\')) . '\\*';
    }

    /**
     * @param array<string, string|list<string>> $routing
     * @param list<StampInterface> $stamps
     * @param list<string> $expectedSenderAliases
     */
    #[Test]
    #[DataProvider('routingResolutionDataProvider')]
    public function getSendersResolvesConfiguredRouting(
        array $routing,
        object $message,
        array $stamps,
        array $expectedSenderAliases,
    ): void {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'] = $routing;

        $subject = new TransportLocator($this->createSendersLocator(self::REGISTERED_SENDERS));
        $senders = iterator_to_array($subject->getSenders(new Envelope($message, $stamps)), true);

        self::assertSame($expectedSenderAliases, array_keys($senders));
    }

    public static function routingResolutionDataProvider(): \Generator
    {
        yield 'a specific route suppresses the global wildcard' => [
            'routing' => [RoutedMessage::class => 'doctrine', '*' => 'default'],
            'message' => new RoutedMessage(),
            'stamps' => [],
            'expectedSenderAliases' => ['doctrine'],
        ];
        yield 'the global wildcard is used when no specific route matches' => [
            'routing' => ['*' => 'default'],
            'message' => new RoutedMessage(),
            'stamps' => [],
            'expectedSenderAliases' => ['default'],
        ];
        yield 'array route keeps the configured order and suppresses the wildcard' => [
            'routing' => [AuditableInterface::class => ['audit', 'doctrine'], '*' => 'default'],
            'message' => new AuditableMessage(),
            'stamps' => [],
            'expectedSenderAliases' => ['audit', 'doctrine'],
        ];
        yield 'transport names stamp overrides the configured routing' => [
            'routing' => [RoutedMessage::class => 'doctrine', '*' => 'default'],
            'message' => new RoutedMessage(),
            'stamps' => [new TransportNamesStamp(['x'])],
            'expectedSenderAliases' => ['x'],
        ];
        yield 'duplicate senders are only yielded once' => [
            'routing' => [RoutedMessage::class => 'default', '*' => 'default'],
            'message' => new RoutedMessage(),
            'stamps' => [],
            'expectedSenderAliases' => ['default'],
        ];
        yield 'a namespace wildcard is a fallback suppressed by an interface match' => [
            'routing' => [
                AuditableInterface::class => 'iface',
                self::namespaceWildcard() => 'nswild',
                '*' => 'default',
            ],
            'message' => new AuditableMessage(),
            'stamps' => [],
            'expectedSenderAliases' => ['iface'],
        ];
        yield 'a namespace wildcard is used as fallback and suppresses the global wildcard' => [
            'routing' => [
                self::namespaceWildcard() => 'nswild',
                '*' => 'default',
            ],
            'message' => new RoutedMessage(),
            'stamps' => [],
            'expectedSenderAliases' => ['nswild'],
        ];
    }

    /**
     * The legacy mapping syntax "SomeClass::class => 'transport'" (a plain
     * string value) must keep working and behave exactly like the single-element
     * array form "SomeClass::class => ['transport']".
     *
     * @param string|list<string> $routingValue
     * @param list<string> $expectedSenderAliases
     */
    #[Test]
    #[DataProvider('legacyStringMappingDataProvider')]
    public function getSendersNormalizesLegacyStringMappingToArray(
        string|array $routingValue,
        bool $withWildcard,
        array $expectedSenderAliases,
    ): void {
        $routing = [RoutedMessage::class => $routingValue];
        if ($withWildcard) {
            $routing['*'] = 'default';
        }
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'] = $routing;

        $subject = new TransportLocator($this->createSendersLocator(self::REGISTERED_SENDERS));
        $senders = iterator_to_array($subject->getSenders(new Envelope(new RoutedMessage())), true);

        self::assertSame($expectedSenderAliases, array_keys($senders));
    }

    public static function legacyStringMappingDataProvider(): \Generator
    {
        yield 'string value, no wildcard' => [
            'routingValue' => 'doctrine',
            'withWildcard' => false,
            'expectedSenderAliases' => ['doctrine'],
        ];
        yield 'single-element array value, no wildcard' => [
            'routingValue' => ['doctrine'],
            'withWildcard' => false,
            'expectedSenderAliases' => ['doctrine'],
        ];
        yield 'string value with wildcard is suppressed by the specific route' => [
            'routingValue' => 'doctrine',
            'withWildcard' => true,
            'expectedSenderAliases' => ['doctrine'],
        ];
    }

    /**
     * @param array<string, string|list<string>> $routing
     * @param class-string $messageClass
     * @param list<string> $expectedSenderNames
     */
    #[Test]
    #[DataProvider('senderNamesForMessageDataProvider')]
    public function getSenderNamesForMessageResolvesRoutingByClass(
        array $routing,
        string $messageClass,
        array $expectedSenderNames,
    ): void {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'] = $routing;

        // Note: no senders need to be registered - names are resolved from
        // configuration only.
        $subject = new TransportLocator($this->createSendersLocator([]));

        self::assertSame($expectedSenderNames, $subject->getSenderNamesForMessage($messageClass));
    }

    public static function senderNamesForMessageDataProvider(): \Generator
    {
        yield 'concrete class resolves to the specific transport' => [
            'routing' => [RoutedMessage::class => 'doctrine', '*' => 'default'],
            'messageClass' => RoutedMessage::class,
            'expectedSenderNames' => ['doctrine'],
        ];
        yield 'route by implemented interface' => [
            'routing' => [AuditableInterface::class => ['audit', 'doctrine'], '*' => 'default'],
            'messageClass' => AuditableMessage::class,
            'expectedSenderNames' => ['audit', 'doctrine'],
        ];
        yield 'route by namespace wildcard' => [
            'routing' => [self::namespaceWildcard() => 'nswild', '*' => 'default'],
            'messageClass' => RoutedMessage::class,
            'expectedSenderNames' => ['nswild'],
        ];
        yield 'unregistered sender name is returned without validation' => [
            'routing' => [RoutedMessage::class => 'missing'],
            'messageClass' => RoutedMessage::class,
            'expectedSenderNames' => ['missing'],
        ];
        yield 'unknown message class falls back to the global wildcard' => [
            'routing' => [RoutedMessage::class => 'doctrine', '*' => 'default'],
            'messageClass' => 'TYPO3\\CMS\\Core\\Tests\\Unit\\Messenger\\Fixtures\\DoesNotExist',
            'expectedSenderNames' => ['default'],
        ];
    }

    #[Test]
    public function getSendersThrowsExceptionForUnregisteredSender(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'] = [RoutedMessage::class => 'missing'];

        $subject = new TransportLocator($this->createSendersLocator(self::REGISTERED_SENDERS));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1605192311);

        iterator_to_array($subject->getSenders(new Envelope(new RoutedMessage())), true);
    }

    /**
     * @param list<string> $registeredSenders
     */
    private function createSendersLocator(array $registeredSenders): ContainerInterface
    {
        $sendersLocator = $this->createMock(ContainerInterface::class);
        $sendersLocator->method('has')->willReturnCallback(
            static fn(string $id): bool => in_array($id, $registeredSenders, true)
        );
        $sendersLocator->method('get')->willReturnCallback(
            fn(string $id): SenderInterface => $this->createMock(SenderInterface::class)
        );

        return $sendersLocator;
    }
}
