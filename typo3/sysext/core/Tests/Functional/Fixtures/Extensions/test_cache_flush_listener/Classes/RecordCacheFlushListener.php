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

namespace TYPO3Tests\TestCacheFlushListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Event\AfterCacheFlushedEvent;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Records every cache flush in a file of the test instance, because the
 * install tool and console commands flush caches from a container or a
 * process of their own.
 */
final class RecordCacheFlushListener
{
    #[AsEventListener]
    public function __invoke(AfterCacheFlushedEvent $event): void
    {
        $result = $event->result;
        file_put_contents(
            self::getLogFile(),
            json_encode([
                'groups' => $result->groups,
                'skippedGroups' => array_keys($result->skippedGroups),
                'errors' => $result->errors,
            ], JSON_THROW_ON_ERROR) . "\n",
            FILE_APPEND
        );
    }

    /**
     * @return list<array{groups: list<string>, skippedGroups: list<string>, errors: list<string>}>
     */
    public static function readResults(): array
    {
        if (!is_file(self::getLogFile())) {
            return [];
        }
        $lines = file(self::getLogFile(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        return array_map(static fn(string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR), $lines);
    }

    public static function reset(): void
    {
        if (is_file(self::getLogFile())) {
            unlink(self::getLogFile());
        }
    }

    private static function getLogFile(): string
    {
        return Environment::getVarPath() . '/test-cache-flush-listener.log';
    }
}
