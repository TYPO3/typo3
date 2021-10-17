<?php

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
class LogRecord implements \ArrayAccess
{
    /**
     * Unique ID of the request
     *
     * @var string
     */
    protected $requestId = '';

    /**
     * Creation timestamp with microseconds
     *
     * @var float
     */
    protected $created = 0.0;

    /**
     * The component where the record was created
     *
     * @var string
     */
    protected $component = '';

    /**
     * Severity level
     *
     * @var string
     */
    protected $level = LogLevel::INFO;

    /**
     * Log message one-liner
     *
     * @var string
     */
    protected $message = '';

    /**
     * Additional log data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Gettable properties for ArrayAccess
     *
     * @var array
     */
    private $gettableProperties = [
        'requestId',
        'created',
        'component',
        'level',
        'message',
        'data',
    ];

    /**
     * Settable properties for ArrayAccess
     *
     * @var array
     */
    private $settableProperties = [
        'level',
        'message',
        'data',
    ];

    /**
     * Constructor.
     *
     * @param string $component Affected component
     * @param string $level Severity level (see \TYPO3\CMS\Core\Log\Level)
     * @param string $message Log message
     * @param array $data Additional data
     * @param string $requestId Unique ID of the request
     */
    public function __construct(string $component, string $level, string $message, array $data = [], string $requestId = '')
    {
        $this->setRequestId($requestId)
            ->setCreated(microtime(true))
            ->setComponent($component)
            ->setLevel($level)
            ->setMessage($message)
            ->setData($data);
    }

    /**
     * Sets the affected component
     *
     * @param string $component Component key
     * @return LogRecord
     */
    public function setComponent($component)
    {
        $this->component = $component;
        return $this;
    }

    /**
     * Returns the component
     *
     * @return string Component key
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * Sets the the creation time
     *
     * @param float $created Creation time as float
     * @return LogRecord
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * Returns the creation time
     *
     * @return float Creation time as float
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Sets the severity level
     *
     * @param string $level Severity level
     * @return LogRecord
     * @see \TYPO3\CMS\Core\Log\Level
     */
    public function setLevel(string $level)
    {
        LogLevel::validateLevel(LogLevel::normalizeLevel($level));
        $this->level = $level;
        return $this;
    }

    /**
     * Returns the severity level
     *
     * @see \TYPO3\CMS\Core\Log\Level
     * @return string Severity level
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * Sets log data array
     *
     * @param array $data
     * @return LogRecord
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Returns the log data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Adds additional log data to already existing data
     * and overwrites previously data using the same array keys.
     *
     * @param array $data
     * @return LogRecord
     */
    public function addData(array $data)
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Sets the log message
     *
     * @param string|object $message Log message. Usually a string, or an object that can be casted to string (implements __toString())
     * @return LogRecord
     */
    public function setMessage($message)
    {
        $this->message = (string)$message;
        return $this;
    }

    /**
     * Returns the log message
     *
     * @return string Log message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets the request ID
     *
     * @param string $requestId
     * @return LogRecord
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
        return $this;
    }

    /**
     * Returns the request ID
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Convert record to string for simple output, like echo().
     * Contents of data array is appended as JSON-encoded string
     *
     * @return string
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

    /**
     * Convert record to array
     *
     * @return array
     */
    public function toArray()
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
     *
     * @param mixed $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        $offsetExists = false;
        if (in_array($offset, $this->gettableProperties, true) && isset($this->{$offset})) {
            $offsetExists = true;
        }
        return $offsetExists;
    }

    /**
     * Offset to retrieve, required by ArrayAccess interface
     *
     * @param mixed $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!in_array($offset, $this->gettableProperties, true)) {
            return null;
        }
        return $this->{$offset};
    }

    /**
     * Offset to set, required by ArrayAccess interface
     *
     * @param mixed $offset
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (in_array($offset, $this->settableProperties, true)) {
            $this->{$offset} = $offset;
        }
    }

    /**
     * Offset to unset, required by ArrayAccess interface
     *
     * @param mixed $offset
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        if (in_array($offset, $this->settableProperties, true)) {
            unset($this->{$offset});
        }
    }
}
