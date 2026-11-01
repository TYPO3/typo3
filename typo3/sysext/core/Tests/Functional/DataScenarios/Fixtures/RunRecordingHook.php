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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\Fixtures;

use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Records the run-level hook calls of all DataHandler instances, and whether
 * the calling instance is a nested one.
 */
final class RunRecordingHook
{
    /** @var list<array{hook: string, nested: bool}> */
    private static array $calls = [];

    public static function reset(): void
    {
        self::$calls = [];
    }

    /**
     * process_datamap() and process_cmdmap() calls, nested ones included.
     */
    public static function countRuns(): int
    {
        return self::countCalls('processDatamap_beforeStart') + self::countCalls('processCmdmap_beforeStart');
    }

    /**
     * process_datamap() and process_cmdmap() calls of nested instances.
     */
    public static function countNestedRuns(): int
    {
        return count(array_filter(
            self::$calls,
            static fn(array $call): bool => $call['nested'] && str_ends_with($call['hook'], '_beforeStart')
        ));
    }

    /**
     * @return array<string, int> hook method name => number of calls
     */
    public static function countCallsPerHook(): array
    {
        $counts = [
            'processDatamap_beforeStart' => 0,
            'processDatamap_afterAllOperations' => 0,
            'processCmdmap_beforeStart' => 0,
            'processCmdmap_afterFinish' => 0,
        ];
        foreach (self::$calls as $call) {
            $counts[$call['hook']]++;
        }
        return $counts;
    }

    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        self::record('processDatamap_beforeStart', $dataHandler);
    }

    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        self::record('processDatamap_afterAllOperations', $dataHandler);
    }

    public function processCmdmap_beforeStart(DataHandler $dataHandler): void
    {
        self::record('processCmdmap_beforeStart', $dataHandler);
    }

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        self::record('processCmdmap_afterFinish', $dataHandler);
    }

    private static function countCalls(string $hook): int
    {
        return count(array_filter(self::$calls, static fn(array $call): bool => $call['hook'] === $hook));
    }

    private static function record(string $hook, DataHandler $dataHandler): void
    {
        self::$calls[] = ['hook' => $hook, 'nested' => !$dataHandler->isOuterMostInstance()];
    }
}
