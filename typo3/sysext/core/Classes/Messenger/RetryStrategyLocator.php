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
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use TYPO3\CMS\Core\Messenger\Exception\RetryStrategyNotFoundException;

/**
 * Builds the retry strategy of a transport from its configuration in
 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports'][<name>]['retry_strategy'].
 *
 * Symfony's SendFailedMessageForRetryListener asks this container - keyed by
 * transport (receiver) name - for the strategy to use. A transport without a
 * "retry_strategy" configuration is not retryable, so a failed message is handed
 * straight to the failure transport (or discarded when none is configured).
 *
 * @internal
 */
final readonly class RetryStrategyLocator implements ContainerInterface
{
    public function has(string $id): bool
    {
        return $this->getConfiguration($id) !== null;
    }

    public function get(string $id): mixed
    {
        $configuration = $this->getConfiguration($id);
        if ($configuration === null) {
            throw new RetryStrategyNotFoundException(
                sprintf('No retry strategy is configured for the "%s" transport.', $id),
                1784501886,
            );
        }

        return new MultiplierRetryStrategy(
            (int)($configuration['max_retries'] ?? 3),
            (int)($configuration['delay'] ?? 1000),
            (float)($configuration['multiplier'] ?? 2),
            (int)($configuration['max_delay'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getConfiguration(string $transport): ?array
    {
        $configuration = $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports'][$transport]['retry_strategy'] ?? null;
        return is_array($configuration) && $configuration !== [] ? $configuration : null;
    }
}
