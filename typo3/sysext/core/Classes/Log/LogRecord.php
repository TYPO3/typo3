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

namespace TYPO3\CMS\Core\Log;

/**
 * Log record
 */
final class LogRecord implements \ArrayAccess
{
    /**
     * Unique ID of the request
     */
    private string $requestId = '';

    /**
     * Creation timestamp with microseconds
     */
    private float $created = 0.0;

    /**
     * The component where the record was created
     */
    private string $component = '';

    /**
     * Severity level
     */
    private string $level = \Psr\Log\LogLevel::INFO;

    /**
     * Log message one-liner
     */
    private string $message = '';

    /**
     * Additional log data
     */
    private array $data = [];

    /**
     * Gettable properties for ArrayAccess
     */
    private array $gettableProperties = [
        'requestId',
        'created',
        'component',
        'level',
        'message',
        'data',
    ];

    /**
     * Settable properties for ArrayAccess
     */
    private array $settableProperties = [
        'level',
        'message',
        'data',
    ];

    /**
     * @param string $component Affected component
     * @param string $level Severity level (see \TYPO3\CMS\Core\Log\LogLevel)
     * @param string|\Stringable $message Log message
     * @param array $data Additional data
     * @param string $requestId Unique ID of the request
     */
    public function __construct(string $component, string $level, string|\Stringable $message, array $data = [], string $requestId = '')
    {
        $this->setRequestId($requestId)
            ->setCreated(microtime(true))
            ->setComponent($component)
            ->setLevel($level)
            ->setMessage($message)
            ->setData($data);
    }

    public function setComponent(string $component): self
    {
        $this->component = $component;
        return $this;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function setCreated(float $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): float
    {
        return $this->created;
    }

    /**
     * @see \TYPO3\CMS\Core\Log\LogLevel
     */
    public function setLevel(string $level): self
    {
        LogLevel::validateLevel(LogLevel::normalizeLevel($level));
        $this->level = $level;
        return $this;
    }

    /**
     * @see \TYPO3\CMS\Core\Log\LogLevel
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Adds additional log data to already existing data
     * and overwrites previously data using the same array keys.
     */
    public function addData(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function setMessage(string|\Stringable $message): self
    {
        $this->message = (string)$message;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setRequestId(string $requestId): self
    {
        $this->requestId = $requestId;
        return $this;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * Convert record to string for simple output, like echo().
     * Contents of data array is appended as JSON-encoded string
     */
    public function __toString(): string
    {
        $timestamp = date('r', (int)$this->created);
        $levelName = strtoupper($this->level);
        $data = '';
        if (!empty($this->data)) {
            // According to PSR3 the exception-key may hold an \Exception
            // Since json_encode() does not encode an exception, we run the _toString() here
            if (isset($this->data['exception']) && $this->data['exception'] instanceof \Exception) {
                $this->data['exception'] = (string)$this->data['exception'];
            }
            $data = '- ' . json_encode($this->data);
        }
        $logRecordString = sprintf(
            '%s [%s] request="%s" component="%s": %s %s',
            $timestamp,
            $levelName,
            $this->requestId,
            $this->component,
            $this->message,
            $data
        );
        return $logRecordString;
    }

    public function toArray(): array
    {
        return [
            'requestId' => $this->requestId,
            'created' => $this->created,
            'component' => $this->component,
            'level' => $this->level,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }

    /**
     * Checks whether an offset exists, required by ArrayAccess interface
     */
    public function offsetExists(mixed $offset): bool
    {
        $offsetExists = false;
        if (in_array($offset, $this->gettableProperties, true) && isset($this->{$offset})) {
            $offsetExists = true;
        }
        return $offsetExists;
    }

    /**
     * Offset to retrieve, required by ArrayAccess interface
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!in_array($offset, $this->gettableProperties, true)) {
            return null;
        }
        return $this->{$offset};
    }

    /**
     * Offset to set, required by ArrayAccess interface
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (in_array($offset, $this->settableProperties, true)) {
            $this->{$offset} = $offset;
        }
    }

    /**
     * Offset to unset, required by ArrayAccess interface
     */
    public function offsetUnset(mixed $offset): void
    {
        if (in_array($offset, $this->settableProperties, true)) {
            unset($this->{$offset});
        }
    }
}
