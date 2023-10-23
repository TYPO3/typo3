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

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\RuntimeException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;

/**
 * A locator that returns the senders for a given message.
 *
 * @internal
 */
class TransportLocator implements SendersLocatorInterface
{
    public function __construct(private readonly ServiceLocator $sendersLocator) {}

    public function getSenders(Envelope $envelope): iterable
    {
        $sendersMap = $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'];
        if ($envelope->all(TransportNamesStamp::class)) {
            foreach ($envelope->last(TransportNamesStamp::class)->getTransportNames() as $senderAlias) {
                yield from $this->getSenderFromConfiguration($senderAlias);
            }

            return;
        }

        $seen = [];

        foreach (HandlersLocator::listTypes($envelope) as $type) {
            $transportForMessage = $sendersMap[$type] ?? [];
            if (is_string($transportForMessage)) {
                $transportForMessage = [$transportForMessage];
            }
            foreach ($transportForMessage as $senderAlias) {
                if (!\in_array($senderAlias, $seen, true)) {
                    $seen[] = $senderAlias;

                    yield from $this->getSenderFromConfiguration($senderAlias);
                }
            }
        }
    }

    private function getSenderFromConfiguration(string $senderAlias): iterable
    {
        if (!$this->sendersLocator->has($senderAlias)) {
            throw new RuntimeException(sprintf('Invalid senders configuration: sender "%s" is not registered via messenger.sender tag.', $senderAlias), 1605192311);
        }

        yield $senderAlias => $this->sendersLocator->get($senderAlias);
    }
}
