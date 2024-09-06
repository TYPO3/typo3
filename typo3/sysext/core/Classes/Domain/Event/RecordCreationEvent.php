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

namespace TYPO3\CMS\Core\Domain\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\SystemProperties;
use TYPO3\CMS\Core\Domain\RecordInterface;

/**
 * Event which allows to manipulate the properties to be used for a new Record.
 * With this event, it's even possible to create a new Record manually.
 */
final class RecordCreationEvent implements StoppableEventInterface
{
    public function __construct(
        private array $properties,
        private readonly RawRecord $rawRecord,
        private readonly SystemProperties $systemProperties,
        private readonly Context $context,
        private ?RecordInterface $record = null,
    ) {}

    public function setRecord(RecordInterface $record): void
    {
        $this->record = $record;
    }

    public function isPropagationStopped(): bool
    {
        return $this->record !== null;
    }

    public function hasProperty(string $name): bool
    {
        return array_key_exists($name, $this->properties);
    }

    public function setProperty(string $name, mixed $propertyValue): void
    {
        $this->properties[$name] = $propertyValue;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function unsetProperty(string $name): bool
    {
        if (!$this->hasProperty($name)) {
            return false;
        }
        unset($this->properties[$name]);
        return true;
    }

    public function getProperty(string $name): mixed
    {
        return $this->properties[$name] ?? null;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getRawRecord(): RawRecord
    {
        return $this->rawRecord;
    }

    public function getSystemProperties(): SystemProperties
    {
        return $this->systemProperties;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @internal
     */
    public function getRecord(): ?RecordInterface
    {
        return $this->record;
    }
}
