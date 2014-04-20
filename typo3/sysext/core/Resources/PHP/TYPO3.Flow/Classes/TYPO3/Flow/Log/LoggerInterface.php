<?php
namespace TYPO3\Flow\Log;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for a basic logger interface
 *
 * The severities are (according to RFC3164) the PHP constants:
 *   LOG_EMERG   # Emergency: system is unusable
 *   LOG_ALERT   # Alert: action must be taken immediately
 *   LOG_CRIT    # Critical: critical conditions
 *   LOG_ERR     # Error: error conditions
 *   LOG_WARNING # Warning: warning conditions
 *   LOG_NOTICE  # Notice: normal but significant condition
 *   LOG_INFO    # Informational: informational messages
 *   LOG_DEBUG   # Debug: debug-level messages
 *
 * @api
 */
interface LoggerInterface {

	/**
	 * Adds a backend to which the logger sends the logging data
	 *
	 * @param \TYPO3\Flow\Log\Backend\BackendInterface $backend A backend implementation
	 * @return void
	 * @api
	 */
	public function addBackend(\TYPO3\Flow\Log\Backend\BackendInterface $backend);

	/**
	 * Runs the close() method of a backend and removes the backend
	 * from the logger.
	 *
	 * @param \TYPO3\Flow\Log\Backend\BackendInterface $backend The backend to remove
	 * @return void
	 * @throws \TYPO3\Flow\Log\Exception\NoSuchBackendException if the given backend is unknown to this logger
	 * @api
	 */
	public function removeBackend(\TYPO3\Flow\Log\Backend\BackendInterface $backend);

	/**
	 * Writes the given message along with the additional information into the log.
	 *
	 * @param string $message The message to log
	 * @param integer $severity An integer value, one of the LOG_* constants
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
	 * @param string $className Name of the class triggering the log (determined automatically if not specified)
	 * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
	 * @return void
	 * @api
	 */
	public function log($message, $severity = LOG_INFO, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL);

	/**
	 * Writes information about the given exception into the log.
	 *
	 * @param \Exception $exception The exception to log
	 * @param array $additionalData Additional data to log
	 * @return void
	 * @api
	 */
	public function logException(\Exception $exception, array $additionalData = array());

}
