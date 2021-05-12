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

namespace TYPO3\CMS\Backend\History\Event;

use TYPO3\CMS\Backend\History\RecordHistoryRollback;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * This event is fired before a history record rollback starts
 */
final class BeforeHistoryRollbackStartEvent
{
    /**
     * @var string
     */
    private $rollbackFields;

    /**
     * @var array
     */
    private $diff;

    /**
     * @var BackendUserAuthentication|null
     */
    private $backendUserAuthentication;

    /**
     * @var RecordHistoryRollback
     */
    private $recordHistory;

    /**
     * HistoryRollbackEvent constructor.
     *
     * @param string $rollbackFields
     * @param array $diff
     * @param RecordHistoryRollback $recordHistoryRollback
     * @param BackendUserAuthentication $backendUserAuthentication
     */
    public function __construct(string $rollbackFields, array $diff, RecordHistoryRollback $recordHistoryRollback, BackendUserAuthentication $backendUserAuthentication = null)
    {
        $this->rollbackFields = $rollbackFields;
        $this->diff = $diff;
        $this->recordHistory = $recordHistoryRollback;
        $this->backendUserAuthentication = $backendUserAuthentication;
    }

    public function getRecordHistoryRollback(): RecordHistoryRollback
    {
        return $this->recordHistory;
    }

    public function getRollbackFields(): string
    {
        return $this->rollbackFields;
    }

    public function getDiff(): array
    {
        return $this->diff;
    }

    public function getBackendUserAuthentication(): ?BackendUserAuthentication
    {
        return $this->backendUserAuthentication;
    }
}
