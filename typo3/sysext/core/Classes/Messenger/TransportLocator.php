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

namespace TYPO3\CMS\Core\Messenger;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\RuntimeException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;

/**
 * A locator that returns the senders (transports) a message should be routed to.
 *
 * Routing is configured via
 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'], a map of message
 * type to one or more transport identifiers. A routing value may be a single
 * transport identifier (string) or an ordered list of transport identifiers
 * (array) to route a single message to multiple transports.
 *
 * The routing keys (the "types") are matched against the type chain of the
 * dispatched message, which is derived from the message's PHP type - not from
 * the order of the routing configuration. The chain, as produced by
 * HandlersLocator::listTypes(), is, from most to least specific: the message
 * class, its parent classes, its implemented interfaces, the namespace
 * wildcards for every namespace level (My\Sub\Message\*, My\Sub\*, My\*) and
 * finally the global wildcard '*'.
 *
 * Resolution rules:
 *  1. An explicit TransportNamesStamp on the envelope always wins.
 *  2. Otherwise senders are collected across every matching type in that type
 *     chain order, deduplicated (first occurrence wins).
 *  3. A wildcard route - a namespace wildcard (My\Message\*) or the global '*' -
 *     acts as a fallback: it is skipped as soon as a more specific type already
 *     matched, and only applies when no specific route matched. This mirrors the
 *     upstream Symfony SendersLocator and removes the need to unset a wildcard
 *     route to keep a message on its specific transport only.
 *
 * @internal
 */
readonly class TransportLocator implements SendersLocatorInterface
{
    private const WILDCARD = '*';

    public function __construct(
        #[AutowireLocator('messenger.sender', indexAttribute: 'identifier')]
        private ContainerInterface $sendersLocator,
    ) {}

    public function getSenders(Envelope $envelope): iterable
    {
        // An explicit runtime override always wins.
        if ($envelope->all(TransportNamesStamp::class)) {
            foreach ($envelope->last(TransportNamesStamp::class)->getTransportNames() as $senderAlias) {
                yield from $this->getSenderFromConfiguration($senderAlias);
            }

            return;
        }

        foreach ($this->resolveSenderNames(HandlersLocator::listTypes($envelope)) as $senderAlias) {
            yield from $this->getSenderFromConfiguration($senderAlias);
        }
    }

    /**
     * Resolves the configured transport (sender) names for a message class,
     * applying the exact same routing resolution as getSenders() - including the
     * wildcard fallback and value normalization - but without requiring a
     * message instance/envelope and without instantiating the senders. Only the
     * sender identifiers are returned; unregistered identifiers are not
     * validated here.
     *
     * @param class-string $messageClass
     * @return list<string>
     */
    public function getSenderNamesForMessage(string $messageClass): array
    {
        return $this->resolveSenderNames($this->listTypesForMessageClass($messageClass));
    }

    /**
     * Applies the routing resolution to a list of message types (as produced by
     * HandlersLocator::listTypes()) and returns the ordered, deduplicated list of
     * sender identifiers.
     *
     * @param array<string, string> $types
     * @return list<string>
     */
    private function resolveSenderNames(array $types): array
    {
        $sendersMap = $this->getRoutingConfiguration();
        $seen = [];

        foreach ($types as $type) {
            // A wildcard route - a namespace wildcard (My\Message\*) or the
            // global '*' - only acts as a fallback: the senders bound to a
            // wildcard are skipped as soon as a more specific type already
            // matched, so a message stays on its specific transport(s).
            if ($seen !== [] && str_ends_with($type, self::WILDCARD)) {
                continue;
            }

            foreach ($sendersMap[$type] ?? [] as $senderAlias) {
                if (!in_array($senderAlias, $seen, true)) {
                    $seen[] = $senderAlias;
                }
            }
        }

        return $seen;
    }

    /**
     * Mirrors HandlersLocator::listTypes() for a message class name (message
     * class, parent classes, implemented interfaces, namespace wildcards and the
     * global wildcard), so routing can be resolved without a message instance.
     *
     * @param class-string $messageClass
     * @return array<string, string>
     */
    private function listTypesForMessageClass(string $messageClass): array
    {
        if (!class_exists($messageClass)) {
            // Not a concrete message class - only the global wildcard can apply.
            return [self::WILDCARD => self::WILDCARD];
        }

        return [$messageClass => $messageClass]
            + class_parents($messageClass)
            + class_implements($messageClass)
            + $this->listWildcards($messageClass)
            + [self::WILDCARD => self::WILDCARD];
    }

    /**
     * @return array<string, string>
     */
    private function listWildcards(string $type): array
    {
        $type .= '\*';
        $wildcards = [];
        while ($i = strrpos($type, '\\', -3)) {
            $type = substr_replace($type, '\*', $i);
            $wildcards[$type] = $type;
        }

        return $wildcards;
    }

    /**
     * Returns the routing map with every value normalized to a list of non-empty
     * transport identifier strings. Both the single-string and the array form are
     * accepted for backwards compatibility.
     *
     * @return array<string, list<string>>
     */
    private function getRoutingConfiguration(): array
    {
        $routing = $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'] ?? [];
        $normalized = [];
        foreach ($routing as $type => $senderAliases) {
            if (is_string($senderAliases)) {
                $senderAliases = [$senderAliases];
            }
            $normalized[$type] = array_values(array_filter(
                (array)$senderAliases,
                static fn(mixed $senderAlias): bool => is_string($senderAlias) && $senderAlias !== '',
            ));
        }

        return $normalized;
    }

    private function getSenderFromConfiguration(string $senderAlias): iterable
    {
        if (!$this->sendersLocator->has($senderAlias)) {
            throw new RuntimeException(sprintf('Invalid senders configuration: sender "%s" is not registered via messenger.sender tag.', $senderAlias), 1605192311);
        }

        yield $senderAlias => $this->sendersLocator->get($senderAlias);
    }
}
