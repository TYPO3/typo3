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
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocator;

/**
 * @internal not part of TYPO3 Core API
 */
final class HandlersLocatorFactory
{
    private array $handlers = [];

    public function __construct(private readonly ContainerInterface $container) {}

    public function createHandlersLocator(): HandlersLocator
    {
        return new HandlersLocator(
            $this->handlers
        );
    }

    /**
     * internally called by the messageHandlerPass
     *
     * $fromTransport binds the handler to one or more receiving transports: the
     * handler is then only executed for messages received from one of those
     * transports (matched against the envelope's ReceivedStamp by Symfony's
     * HandlersLocator). A null value (the default) - or an empty list - keeps the
     * handler bound to all transports.
     *
     * Symfony's HandlersLocator only compares a scalar from_transport option
     * against the received transport, so a list is registered as one handler
     * descriptor per transport. Only the descriptor matching the received
     * transport passes, and Symfony's per-name de-duplication ensures the handler
     * still runs at most once per message.
     *
     * @param string|list<string>|null $fromTransport
     */
    public function addHandler(string $messageClass, string $handlerService, string $handlerMethod, string|array|null $fromTransport = null): void
    {
        $container = $this->container;
        $handler = function (...$args) use ($container, $handlerService, $handlerMethod) {
            if ($handlerMethod === '__invoke') {
                return $container->get($handlerService)(...$args);
            }
            return $container->get($handlerService)->{$handlerMethod}(...$args);
        };
        $alias = $handlerService . '::' . $handlerMethod;

        $fromTransports = self::normalizeTransports($fromTransport);
        if ($fromTransports === []) {
            // Unbound: from_transport MUST stay null (not ''): Symfony's
            // HandlersLocator treats a non-null from_transport as a transport
            // name to match, so an empty string would exclude the handler
            // everywhere.
            $this->handlers[$messageClass][] = new HandlerDescriptor($handler, ['alias' => $alias, 'from_transport' => null]);
            return;
        }

        foreach ($fromTransports as $fromTransport) {
            $this->handlers[$messageClass][] = new HandlerDescriptor($handler, ['alias' => $alias, 'from_transport' => $fromTransport]);
        }
    }

    /**
     * Normalizes a from_transport definition (null, a single transport name or a
     * list of transport names) to a de-duplicated list of non-empty transport
     * names. Shared with MessageHandlerPass so the compile-time handler identifier
     * and the runtime handler descriptors agree on the resolved transports.
     *
     * @param string|list<string>|null $fromTransport
     * @return list<string>
     */
    public static function normalizeTransports(string|array|null $fromTransport): array
    {
        if ($fromTransport === null) {
            return [];
        }
        $normalized = [];
        foreach (is_string($fromTransport) ? [$fromTransport] : $fromTransport as $transport) {
            if (is_string($transport) && ($transport = trim($transport)) !== '' && !in_array($transport, $normalized, true)) {
                $normalized[] = $transport;
            }
        }
        return $normalized;
    }
}
