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
use TYPO3\CMS\Core\Messenger\Exception\FailureTransportNotFoundException;

/**
 * Provides the failure sender of a receiver to Symfony's
 * SendFailedMessageToFailureTransportListener. A receiver has none if no
 * failure transport applies to it, or if it is that failure transport itself.
 *
 * @internal
 */
final readonly class FailureTransportLocator implements ContainerInterface
{
    public function __construct(
        #[AutowireLocator('messenger.sender', indexAttribute: 'identifier')]
        private ContainerInterface $senders,
        private FailureTransport $failureTransport,
    ) {}

    public function has(string $id): bool
    {
        return $this->resolve($id) !== null;
    }

    public function get(string $id): mixed
    {
        $failureTransport = $this->resolve($id);
        if ($failureTransport === null) {
            throw new FailureTransportNotFoundException(
                sprintf('No failure transport is configured for the "%s" transport.', $id),
                1784501885,
            );
        }
        return $this->senders->get($failureTransport);
    }

    private function resolve(string $receiver): ?string
    {
        $failureTransport = $this->failureTransport->forReceiver($receiver);
        if ($failureTransport === null || $failureTransport === $receiver || !$this->senders->has($failureTransport)) {
            return null;
        }
        return $failureTransport;
    }
}
