<?php
namespace TYPO3\CMS\Core\Log;

/**
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

use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * Log record
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Steffen Müller (typo3@t3node.com)
 */
class LogRecord implements \ArrayAccess {

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
	 * @var integer
	 */
	protected $level = \TYPO3\CMS\Core\Log\LogLevel::INFO;

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
	protected $data = array();

	/**
	 * Gettable properties for ArrayAccess
	 *
	 * @var array
	 */
	private $gettableProperties = array(
		'requestId',
		'created',
		'component',
		'level',
		'message',
		'data'
	);

	/**
	 * Settable properties for ArrayAccess
	 *
	 * @var array
	 */
	private $settableProperties = array(
		'level',
		'message',
		'data'
	);

	/**
	 * Constructor.
	 *
	 * @param string $component Affected component
	 * @param integer $level Severity level (see \TYPO3\CMS\Core\Log\Level)
	 * @param string $message Log message
	 * @param array $data Additional data
	 */
	public function __construct($component = '', $level, $message, array $data = array()) {
		$this->setRequestId(Bootstrap::getInstance()->getRequestId())
			->setCreated(microtime(TRUE))
			->setComponent($component)
			->setLevel($level)
			->setMessage($message)
			->setData($data);
	}

	/**
	 * Sets the affected component
	 *
	 * @param string $component Component key
	 * @return \TYPO3\CMS\Core\Log\LogRecord
	 */
	public function setComponent($component) {
		$this->component = $component;
		return $this;
	}

	/**
	 * Returns the component
	 *
	 * @return string Component key
	 */
	public function getComponent() {
		return $this->component;
	}

	/**
	 * Sets the the creation time
	 *
	 * @param float $created Creation time as float
	 * @return \TYPO3\CMS\Core\Log\LogRecord
	 */
	public function setCreated($created) {
		$this->created = $created;
		return $this;
	}

	/**
	 * Returns the creation time
	 *
	 * @return float Creation time as float
	 */
	public function getCreated() {
		return $this->created;
	}

	/**
	 * Sets the severity level
	 *
	 * @param integer $level Severity level
	 * @return \TYPO3\CMS\Core\Log\LogRecord
	 * @throws \RangeException if the given log level is invalid
	 * @see \TYPO3\CMS\Core\Log\Level
	 */
	public function setLevel($level) {
		\TYPO3\CMS\Core\Log\LogLevel::validateLevel($level);
		$this->level = $level;
		return $this;
	}

	/**
	 * Returns the severity level
	 *
	 * @see \TYPO3\CMS\Core\Log\Level
	 * @return integer Severity level
	 */
	public function getLevel() {
		return $this->level;
	}

	/**
	 * Sets log data array
	 *
	 * @param array $data
	 * @return \TYPO3\CMS\Core\Log\LogRecord
	 */
	public function setData($data) {
		$this->data = $data;
		return $this;
	}

	/**
	 * Returns the log data
	 *
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Adds additional log data to already existing data
	 * and overwrites previously data using the same array keys.
	 *
	 * @param array $data
	 * @return \TYPO3\CMS\Core\Log\LogRecord
	 */
	public function addData(array $data) {
		$this->data = array_merge($this->data, $data);
		return $this;
	}

	/**
	 * Sets the log message
	 *
	 * @param string|object $message Log message. Usually a string, or an object that can be casted to string (implements __toString())
	 * @return \TYPO3\CMS\Core\Log\LogRecord
	 */
	public function setMessage($message) {
		$this->message = (string)$message;
		return $this;
	}

	/**
	 * Returns the log message
	 *
	 * @return string Log message
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Sets the request ID
	 *
	 * @param string $requestId
	 * @return \TYPO3\CMS\Core\Log\LogRecord
	 */
	public function setRequestId($requestId) {
		$this->requestId = $requestId;
		return $this;
	}

	/**
	 * Returns the request ID
	 *
	 * @return string
	 */
	public function getRequestId() {
		return $this->requestId;
	}

	/**
	 * Convert record to string for simple output, like echo().
	 * Contents of data array is appended as JSON-encoded string
	 *
	 * @return string
	 */
	public function __toString() {
		$timestamp = date('r', (int)$this->created);
		$levelName = \TYPO3\CMS\Core\Log\LogLevel::getName($this->level);
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
	public function toArray() {
		return array(
			'requestId' => $this->requestId,
			'created' => $this->created,
			'component' => $this->component,
			'level' => $this->level,
			'message' => $this->message,
			'data' => $this->data
		);
	}

	/**
	 * Checks whether an offset exists, required by ArrayAccess interface
	 *
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		$offsetExists = FALSE;
		if (in_array($offset, $this->gettableProperties, TRUE) && isset($this->{$offset})) {
			$offsetExists = TRUE;
		}
		return $offsetExists;
	}

	/**
	 * Offset to retrieve, required by ArrayAccess interface
	 *
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		if (!in_array($offset, $this->gettableProperties, TRUE)) {
			return NULL;
		}
		return $this->{$offset};
	}

	/**
	 * Offset to set, required by ArrayAccess interface
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		if (in_array($offset, $this->settableProperties, TRUE)) {
			$this->{$offset} = $offset;
		}
	}

	/**
	 * Offset to unset, required by ArrayAccess interface
	 *
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		if (in_array($offset, $this->settableProperties, TRUE)) {
			unset($this->{$offset});
		}
	}

}
