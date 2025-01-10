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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler\Fixtures;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class for testing execution of DataHandler hook invocations.
 */
final class HookFixture implements SingletonInterface
{
    private array $invocations = [];

    public function findInvocationsByMethodName(string $methodName): ?array
    {
        return $this->invocations[$methodName] ?? null;
    }

    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        $this->invocations[__FUNCTION__][] = true;
    }

    /**
     * @param string|int $id
     */
    public function processDatamap_preProcessFieldArray(array $fieldArray, string $table, $id, DataHandler $dataHandler): void
    {
        $this->invocations[__FUNCTION__][] = [
            'fieldArray' => $fieldArray,
            'table' => $table,
            'id' => $id,
        ];
    }

    public function processDatamap_postProcessFieldArray(string $status, string $table, string|int $id, array $fieldArray, DataHandler $dataHandler): void
    {
        $this->invocations[__FUNCTION__][] = [
            'status' => $status,
            'table' => $table,
            'id' => $id,
            'fieldArray' => $fieldArray,
        ];
    }

    /**
     * @param string|int $id
     */
    public function processDatamap_afterDatabaseOperations(string $status, string $table, $id, array $fieldArray, DataHandler $dataHandler): void
    {
        $this->invocations[__FUNCTION__][] = [
            'status' => $status,
            'table' => $table,
            'id' => $id,
            'fieldArray' => $fieldArray,
        ];
    }

    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        $this->invocations[__FUNCTION__][] = true;
    }

    public function processCmdmap_beforeStart(DataHandler $dataHandler): void
    {
        $this->invocations[__FUNCTION__][] = true;
    }

    /**
     * @param string|int $id
     * @param mixed $value
     * @param bool|string $pasteUpdate
     */
    public function processCmdmap_preProcess(string $command, string $table, $id, $value, DataHandler $dataHandler, $pasteUpdate): void
    {
        $this->invocations[__FUNCTION__][] = [
            'command' => $command,
            'table' => $table,
            'id' => $id,
            'value' => $value,
            'pasteUpdate' => $pasteUpdate,
        ];
    }

    /**
     * @param string|int $id
     * @param mixed $value
     * @param bool|string $pasteUpdate
     */
    public function processCmdmap(string $command, string $table, $id, $value, bool $commandIsProcessed, DataHandler $dataHandler, $pasteUpdate): void
    {
        $this->invocations[__FUNCTION__][] = [
            'command' => $command,
            'table' => $table,
            'id' => $id,
            'value' => $value,
            'commandIsProcessed' => $commandIsProcessed,
            'pasteUpdate' => $pasteUpdate,
        ];
    }

    /**
     * @param string|int $id
     * @param mixed $value
     * @param bool|string $pasteUpdate
     */
    public function processCmdmap_postProcess(string $command, string $table, $id, $value, DataHandler $dataHandler, $pasteUpdate, array $pasteDatamap): void
    {
        $this->invocations[__FUNCTION__][] = [
            'command' => $command,
            'table' => $table,
            'id' => $id,
            'value' => $value,
            'pasteUpdate' => $pasteUpdate,
            'pasteDatamap' => $pasteDatamap,
        ];
    }

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        $this->invocations[__FUNCTION__][] = true;
    }
}
