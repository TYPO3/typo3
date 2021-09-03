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

namespace TYPO3\CMS\Core\Resource\Event;

/**
 * Event that is triggered after a file command has been processed. Can be used
 * to perform additional tasks for specific commands. For example, trigger a
 * custom indexer after a file has been uploaded.
 */
final class AfterFileCommandProcessedEvent
{
    private array $command;
    private $result;
    private string $conflictMode;

    public function __construct(array $command, $result, string $conflictMode)
    {
        $this->command = $command;
        $this->result = $result;
        $this->conflictMode = $conflictMode;
    }

    /**
     * A single command, e.g.:
     *
     * 'upload' => [
     *     'target' => '1:/some/folder/'
     *     'data' => '1'
     * ]
     *
     * @return array<string, array<string, mixed>>
     */
    public function getCommand(): array
    {
        return $this->command;
    }

    /**
     * @return mixed The result - Depending on the performed action,
     *               this could e.g. be a File or just a boolean.
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return string The current conflict mode
     * @see DuplicationBehavior
     */
    public function getConflictMode(): string
    {
        return $this->conflictMode;
    }
}
