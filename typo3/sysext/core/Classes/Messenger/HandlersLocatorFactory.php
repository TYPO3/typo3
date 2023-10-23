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
     */
    public function addHandler(string $messageClass, string $handlerService, string $handlerMethod): void
    {
        $container = $this->container;
        $this->handlers[$messageClass][] = new HandlerDescriptor(
            function (...$args) use ($container, $handlerService, $handlerMethod) {
                if ($handlerMethod === '__invoke') {
                    return $container->get($handlerService)(...$args);
                }
                return $container->get($handlerService)->{$handlerMethod}(...$args);
            },
            [
                'alias' => $handlerService . '::' . $handlerMethod,
            ]
        );
    }
}
