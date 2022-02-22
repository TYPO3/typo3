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

namespace TYPO3\CMS\Core\Domain\Access;

use Psr\EventDispatcher\StoppableEventInterface;
use TYPO3\CMS\Core\Context\Context;

/**
 * Event to modify records to be checked against "enableFields".
 * Listeners are able to grant access or to modify the record itself to
 * continue to use the native access check functionality with a modified dataset.
 */
final class RecordAccessGrantedEvent implements StoppableEventInterface
{
    private ?bool $accessGranted = null;

    public function __construct(
        private readonly string $tableName,
        private array $record,
        private readonly Context $context
    ) {
    }

    public function isPropagationStopped(): bool
    {
        return $this->accessGranted !== null;
    }

    /**
     * @internal
     */
    public function accessGranted(): bool
    {
        if ($this->accessGranted === null) {
            throw new \RuntimeException('Access was not yet defined.', 1645506529);
        }

        return $this->accessGranted;
    }

    public function setAccessGranted(bool $accessGranted): void
    {
        $this->accessGranted = $accessGranted;
    }

    public function getTable(): string
    {
        return $this->tableName;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function updateRecord(array $record): void
    {
        $this->record = $record;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
