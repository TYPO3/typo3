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
class HookFixture implements SingletonInterface
{
    /**
     * @var array[]
     */
    protected $invocations = [];

    /**
     * Purges the state of this singleton instance
     */
    public function purge()
    {
        $this->invocations = [];
    }

    /**
     * @param string $methodName
     * @return array|null
     */
    public function findInvocationsByMethodName(string $methodName)
    {
        return $this->invocations[$methodName] ?? null;
    }

    /**
     * @param DataHandler $dataHandler
     */
    public function processDatamap_beforeStart(DataHandler $dataHandler)
    {
        $this->invocations[__FUNCTION__][] = true;
    }

    /**
     * @param array $fieldArray
     * @param string $table
     * @param string|int $id
     * @param DataHandler $dataHandler
     */
    public function processDatamap_preProcessFieldArray(array $fieldArray, string $table, $id, DataHandler $dataHandler)
    {
        $this->invocations[__FUNCTION__][] = [
            'fieldArray' => $fieldArray,
            'table' => $table,
            'id' => $id,
        ];
    }

    /**
     * @param string $status
     * @param string $table
     * @param string|int $id
     * @param array $fieldArray
     * @param DataHandler $dataHandler
     */
    public function processDatamap_postProcessFieldArray(string $status, string $table, $id, array $fieldArray, DataHandler $dataHandler)
    {
        $this->invocations[__FUNCTION__][] = [
            'status' => $status,
            'table' => $table,
            'id' => $id,
            'fieldArray' => $fieldArray,
        ];
    }

    /**
     * @param string $status
     * @param string $table
     * @param string|int $id
     * @param array $fieldArray
     * @param DataHandler $dataHandler
     */
    public function processDatamap_afterDatabaseOperations(string $status, string $table, $id, array $fieldArray, DataHandler $dataHandler)
    {
        $this->invocations[__FUNCTION__][] = [
            'status' => $status,
            'table' => $table,
            'id' => $id,
            'fieldArray' => $fieldArray,
        ];
    }

    /**
     * @param DataHandler $dataHandler
     */
    public function processDatamap_afterAllOperations(DataHandler $dataHandler)
    {
        $this->invocations[__FUNCTION__][] = true;
    }

    /**
     * @param DataHandler $dataHandler
     */
    public function processCmdmap_beforeStart(DataHandler $dataHandler)
    {
        $this->invocations[__FUNCTION__][] = true;
    }

    /**
     * @param string $command
     * @param string $table
     * @param string|int $id
     * @param mixed $value
     * @param DataHandler $dataHandler
     * @param bool|string $pasteUpdate
     */
    public function processCmdmap_preProcess(string $command, string $table, $id, $value, DataHandler $dataHandler, $pasteUpdate)
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
     * @param string $command
     * @param string $table
     * @param string|int $id
     * @param mixed $value
     * @param bool $commandIsProcessed
     * @param DataHandler $dataHandler
     * @param bool|string $pasteUpdate
     */
    public function processCmdmap(string $command, string $table, $id, $value, bool $commandIsProcessed, DataHandler $dataHandler, $pasteUpdate)
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
     * @param string $command
     * @param string $table
     * @param string|int $id
     * @param mixed $value
     * @param DataHandler $dataHandler
     * @param bool|string $pasteUpdate
     * @param bool|string $pasteDatamap
     */
    public function processCmdmap_postProcess(string $command, string $table, $id, $value, DataHandler $dataHandler, $pasteUpdate, $pasteDatamap)
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

    /**
     * @param DataHandler $dataHandler
     */
    public function processCmdmap_afterFinish(DataHandler $dataHandler)
    {
        $this->invocations[__FUNCTION__][] = true;
    }
}
