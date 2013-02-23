<?php
namespace TYPO3\CMS\Core\Locking\Locker;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Daniel Hürtgen <huertgen@rheinschafe.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Locker interface
 *
 * @author Daniel Hürtgen <huertgen@rheinschafe.de>
 */
abstract class AbstractLocker implements LockerInterface {

	/**
	 * Locking state.
	 *
	 * @var boolean
	 */
	protected $isAcquired = FALSE;

	/**
	 * Unique identifier.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Unique hash identifier.
	 *
	 * @var string
	 */
	protected $idHash;

	/**
	 * Context/prefix to generate lock for.
	 *
	 * @var string
	 */
	protected $context;

	/**
	 * Array contains configuration options.
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Constructs locker.
	 *
	 * @param string $context String sets a scope/context for current locking.
	 * @param string $id      String sets an unique lock identifier.
	 * @param array  $options Array with locker options.
	 * @return \TYPO3\CMS\Core\Locking\Locker\AbstractLocker
	 */
	public function __construct($context, $id, array $options = array()) {
		$this->context = (string) $context;
		$this->id = (string) $id;

		$this->options = array(
			'logging' => TRUE,
			'retries' => 150,
			'retryInterval' => 200,
			'respectExecutionTime' => TRUE,
			'autoReleaseOnPHPShutdown' => TRUE,
			'maxLockAge' => 120,
		);
		$this->setOptions($options);

		if ($this->getOption('autoReleaseOnPHPShutdown')) {
			register_shutdown_function(array($this, 'shutdown'));
		}
	}

	/**
	 * Sets all options.
	 *
	 * @param array $options
	 * @return void
	 */
	public function setOptions(array $options = array()) {
		foreach ($options as $name => $value) {
			$this->setOption($name, $value);
		}
	}

	/**
	 * Gets all options.
	 *
	 * @return array
	 */
	public function getOptions() {
		$options = array();
		foreach (array_keys($this->options) as $option) {
			$options[$option] = $this->getOption($option);
		}
		return $options;
	}

	/**
	 * Sets an option key.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return void
	 * @throws \TYPO3\CMS\Core\Locking\Exception\InvalidOptionException
	 */
	public function setOption($name, $value) {
		$methodName = 'set' . ucfirst($name);
		if (method_exists($this, $methodName)) {
			$this->$methodName($value);
		} else if (array_key_exists($name, $this->options)) {
			$this->options[$name] = $value;
		} else {
			throw new \TYPO3\CMS\Core\Locking\Exception\InvalidOptionException(
				sprintf('Invalid option "%s". Valid options for locker "%s" are: "%s"', $name, $this->getType(), var_export(array_keys($this->options), TRUE)), 1361694936
			);
		}
	}

	/**
	 * Gets an option key.
	 *
	 * @param string $name
	 * @return mixed
	 * @throws \TYPO3\CMS\Core\Locking\Exception\InvalidOptionException
	 */
	public function getOption($name) {
		$methodName = 'get' . ucfirst($name);
		if (method_exists($this, $methodName)) {
			return $this->$methodName();
		} else if (array_key_exists($name, $this->options)) {
			return $this->options[$name];
		} else {
			throw new \TYPO3\CMS\Core\Locking\Exception\InvalidOptionException(
				sprintf('Invalid option "%s". Valid options for locker "%s" are: "%s"', $name, $this->getType(), var_export(array_keys($this->options), TRUE)), 1361694936
			);
		}
	}

	/**
	 * Checks wheater an options exists.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasOption($name) {
		return array_key_exists($name, $this->options);
	}

	/**
	 * Get acquire retries setting.
	 *
	 * @return integer
	 */
	public function getRetries() {
		return $this->options['retries'];
	}

	/**
	 * Set acquire fail retries setting.
	 *
	 * @param integer $retries
	 * @return void
	 * @api
	 */
	public function setRetries($retries) {
		$this->options['retries'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($retries, 0, 1000, 0);
	}

	/**
	 * Get acquire retry interval setting.
	 *
	 * @return integer
	 * @api
	 */
	public function getRetryInterval() {
		return $this->options['retryInterval'];
	}

	/**
	 * Set acquire retry interval setting.
	 *
	 * @param integer $retryInterval
	 * @return void
	 * @api
	 */
	public function setRetryInterval($retryInterval) {
		$this->options['retryInterval'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($retryInterval, 1, 99999, 1);
	}

	/**
	 * Get max lock age setting (seconds).
	 *
	 * @return integer
	 */
	public function getMaxLockAge() {
		return $this->options['maxLockAge'];
	}

	/**
	 * Set max lock age setting (in seconds).
	 *
	 * @param integer $maxLockAge
	 * @return integer
	 */
	public function setMaxLockAge($maxLockAge) {
		$this->options['maxLockAge'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($maxLockAge, 1, 2000000000, 1);
	}

	/**
	 * Waits milliseconds (interval) for next retry.
	 *
	 * @return void
	 */
	protected function waitForRetry() {
		usleep($this->getRetryInterval() * 1000);
	}

	/**
	 * Return php max_execution_time ini value.
	 *
	 * @return integer
	 */
	protected function getMaxExecutionTime() {
		return (int) ini_get('max_execution_time');
	}

	/**
	 * Return typo3 global exec time.
	 *
	 * @return integer
	 */
	protected function getGlobalExecTime() {
		return (int) $GLOBALS['EXEC_TIME'];
	}

	/**
	 * Return current timestamp.
	 *
	 * @return integer
	 */
	protected function getCurrentTime() {
		return (int) time();
	}

	/**
	 * Calulcate the max loops to acquire locks before running into a
	 * php timeout (max_execution_timeout).
	 *
	 * @return integer
	 */
	protected function calculateMaxRetriesForAcquireLoop() {
		$maxExecutionTime = $this->getMaxExecutionTime();
		if ($maxExecutionTime === 0 || !$this->getOption('respectExecutionTime')) {
			return $this->getRetries();
		}

		$globalExecTime = $this->getGlobalExecTime();
		$currentTime = $this->getCurrentTime();
		// already consumed execed time upton here
		$alreadyConsumedExecTime = $currentTime - $globalExecTime;
		// cut 5 percent from max_execution_time setting to left space
		// for error handling before running out in php timeout
		$maxUsableLoopTime = (int) (floor(($maxExecutionTime - $alreadyConsumedExecTime) * 0.95) * 1000);

		$usedTime = 0;
		for ($loops = 0; $loops < $this->getRetries(); $loops++) {
			$usedTime += $this->getRetryInterval();
			if ($usedTime > $maxUsableLoopTime) {
				break;
			}
		}

		return $loops;
	}

	/**
	 * Is lock aquired?
	 *
	 * @return boolean TRUE if lock was acquired, otherwise FALSE.
	 * @api
	 */
	public function isAcquired() {
		return $this->isAcquired;
	}

	/**
	 * Returns unique lock identifier.
	 *
	 * @return string
	 * @api
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Return unique id hash.
	 * 40 chars long string sha1().
	 *
	 * @return string
	 * @api
	 */
	public function getIdHash() {
		if ($this->idHash === NULL) {
			$this->idHash = sha1($this->context . ':' . $this->id);
		}
		return $this->idHash;
	}

	/**
	 * Get context/prefix for hash.
	 *
	 * @return string
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Sets logging enabled|disabled.
	 *
	 * @param boolean $enabled
	 * @return void
	 */
	public function setLogging($enabled) {
		$this->options['logging'] = (boolean) $enabled;
	}

	/**
	 * Get logging state.
	 *
	 * @return boolean
	 */
	public function getLogging() {
		return $this->options['logging'];
	}

	/**
	 * Checks wheater logging is enabled.
	 *
	 * @return boolean
	 */
	public function isLoggingEnabled() {
		return $this->getLogging();
	}

	/**
	 * Wrapper method for sending a message to log.
	 *  If logging is enabled (default).
	 *
	 * @param string  $message  String message to log.
	 * @param integer $severity Severity - 0 is info (default), 1 is notice, 2 is warning, 3 is error, 4 is fatal error
	 * @return void
	 */
	protected function log($message, $severity = 0) {
		if ($this->isLoggingEnabled()) {
			$message = $this->prefixLogMessage($message);
			$this->doLog($message, $severity);
		}
	}

	/**
	 * Sends a message to log.
	 *
	 * @param string  $message  String with a message to log.
	 * @param integer $severity Severtiy - 0 is info (default), 1 is notice, 2 is warning, 3 is error, 4 is fatal error
	 * @return void
	 */
	protected function doLog($message, $severity = 0) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', $severity);
	}

	/**
	 * Wrapper method for sending a message to devlog.
	 *  If logging is enabled (default).
	 *
	 * @param string   $message  String message to log.
	 * @param integer  $severity Severity - 0 is info (default), 1 is notice, 2 is warning, 3 is fatal error, -1 is ok message
	 * @param mixed    $payload  Additional data to log.
	 * @return void
	 */
	protected function devLog($message, $severity = 0, $payload = '') {
		if ($this->isLoggingEnabled()) {
			$message = $this->prefixLogMessage($message);
			$payload = array(
				'id' => $this->getId(),
				'context' => $this->getContext(),
				'type' => $this->getType(),
				'options' => $this->getOptions(),
				'payload' => $payload,
			);
			$this->doDevLog($message, $severity, $payload);
		}
	}

	/**
	 * Sends a message to devlog.
	 *
	 * @param string   $message  String message to log.
	 * @param integer  $severity Severity - 0 is info (default), 1 is notice, 2 is warning, 3 is fatal error, -1 is ok message
	 * @param mixed    $payload  Additional data to log.
	 * @return void
	 */
	protected function doDevLog($message, $severity = 0, $payload = FALSE) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($message, 'cms', $severity, $payload);
	}

	/**
	 * Prefix log message with
	 *  - locker type [DummyLocker]
	 *  - locker payload [C: DummyContext|ID: 1234567]
	 *
	 * Composite string will be like this:
	 *  [DummyLocker][C:DummyContext|ID:1234567] Dummy Log Message
	 *
	 * @param string $message
	 * @return string
	 */
	protected function prefixLogMessage($message) {
		return sprintf('[%sLocker][C:%s|ID:%s] %s', ucfirst($this->getType()), $this->getContext(), $this->getId(), $message);

	}

	/**
	 * Acquire lock.
	 *
	 * @return boolean TRUE if lock could be acquired without beeing blocked. Otherwise throws exceptions.
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockBlockedException Thrown for internal usage only.
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockDelayedException Thrown if lock was acquired successfully, but has been blocked by other locks during acquiring.
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredWithinProposedRetriesException Thrown if lock can't be acquired within configured retries.
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredOnTimeException Thrown if lock can't be acquired before max_execution_time will be reached.
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredException Thrown if something wen't wrong during acquiring. Also base class of 'LockCouldNotBeAcquiredOnTimeException' && 'LockCouldNotBeAcquiredWithinProposedRetriesException'.
	 * @api
	 */
	public function acquire() {
		// lock was already acquired
		if ($this->isAcquired) {
			$this->log('Lock was already acquired by myself.', 1);
			return TRUE;
		}

		// run cleanup task
		if ($this->doCleanStaleLock()) {
			// run garbage collection
			$this->log('Garbage Collection was triggered.');
			$this->doGarbageCollection();
		}

		// acquire lock
		for ($i = 1; $i <= $this->calculateMaxRetriesForAcquireLoop(); $i++) {
			try {
				// we expect either doAcquire() will return TRUE on success
				// or throw a lock-blocked-exception on blocking
				// or throw an exception if something went wrong at all
				// if nothing returned or couldn't proved to be true
				// we will throw such a lock-blocked exception to
				// trigger retry looping and stop proceeding for now
				if (!$this->doAcquire($i)) {
					$this->devLog(
						'Your lockers doAcquire() methods return value couldn\'t proved to be true, so we throw a lock-block-exception for you.
						You should fix this to get full control over things like this.',
						1, array('retry' => $i)
					);
					throw new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException($i);
				}
				// succeed acquire
				$this->isAcquired = TRUE;
				// acquiring lock needs more than one retry
				// throw a lock-delayed-exception
				if ($i > 1) {
					throw new \TYPO3\CMS\Core\Locking\Exception\LockDelayedException($i);
				}
				$this->log('Lock successfully aquired.');
				break;
			} catch (\TYPO3\CMS\Core\Locking\Exception\LockBlockedException $e) {
				$this->waitForRetry();
			} catch (\TYPO3\CMS\Core\Locking\Exception\LockDelayedException $e) {
				$this->isAcquired = TRUE;
				$this->log(sprintf('Lock successfully acquired after %d tries.', $e->getRetries()));
				throw $e; //rethrow exception
			} catch (\Exception $e) {
				$this->log('Lock could not be acquired.', 3);
				// rethrow exception in wrapped lock-could-not-be-acquired exception
				// so your able to decide what to do, maybe locking was optional or
				// you're not interested in why, locking could not be acquired
				// you will be always able to catch the same exception, even if previous
				// exception was not a lock exception
				// previous exception is accessable via LockCouldNotBeAcquiredException::getPrevious()
				throw new \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredException($this->getRetries(), $i, $e);
			}
		}

		// acquire fails
		if (!$this->isAcquired) {
			$retries = $this->getRetries();
			// proposed retries greated than actual looped
			if ($this->getOption('respectExecutionTime') && $retries > $i) {
				$this->log(sprintf('Lock could not be acquired before "max_execution_time" will be reached. Proposed retries: "%d", Actual retries: %d.', $retries, $i), 2);
				throw new \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredOnTimeException($retries, $i);
			}
			// could not acquire lock within proposed retries
			$this->log(sprintf('Lock could not be acquired withing proposed retries "%d".', $retries), 3);
			throw new \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredWithinProposedRetriesException($retries, $i);
		}

		return $this->isAcquired;
	}

	/**
	 * Runs garbage collection tasks for current lock.
	 *
	 * @return boolean TRUE, if found & cleaned up an stale lock, otherwise FALSE. Returning TRUE will trigger the overall garbage collection.
	 */
	abstract protected function doCleanStaleLock();

	/**
	 * Runs overall garbage collection.
	 *  Will be triggered, if doCLeanStaleLock() method returned TRUE due lock acquiring.
	 *
	 * @return void
	 */
	protected function doGarbageCollection() {
	}

	/**
	 * Do real lock acquiring magic.
	 *  Will be called several times from API method acquire() until lock was acquired successfully or maxRetries was reached.
	 *
	 * @param integer $try Current try counter
	 * @return boolean Return boolean TRUE on success, otherwise throw exceptions or at least you should return FALSE.
	 */
	abstract protected function doAcquire($try);

	/**
	 * Release lock.
	 *
	 * @return boolean TRUE if lock was release, otherwise throw lock-could-not-be-released exception.
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockHasBeenAlreadyReleasedException
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeReleasedException
	 * @api
	 */
	public function release() {
		// is there no lock that is releaseable?
		if (!$this->isAcquired) {
			$this->log('There is no lock that could be released.', 1);
			return TRUE;
		}

		// release lock
		try {
			// we expect either doRelease() will return TRUE on success
			// or throw a lock-could-not-be-release-exception if fails
			// or throw an other exception if something went wrong at all
			// if nothing returned or couldn't proved to be true
			// we will throw such a lock-could-not-be-released-exception to
			// stop continuing
			if (!$this->doRelease()) {
				throw new \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeReleasedException();
			}
			// success release
			$this->isAcquired = FALSE;
			$this->log('Lock has been successfully released.');
		} catch (\TYPO3\CMS\Core\Locking\Exception\LockHasBeenAlreadyReleasedException $e) {
			// if lock has been already released, release seems
			// to be successfully, but maybe something went wrong
			// because WE should release our own lock. but for now
			// it is okay, we set acquired to false, but we send a
			// warning to the log file
			$this->isAcquired = FALSE;
			$this->log('Lock has been already released.', 2);
		} catch (\Exception $e) {
			$this->log('Lock could not be released.', 3);
			if ($e instanceof \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeReleasedException) {
				throw $e; // rethrow exception
			}
			// rethrow all other exceptions in wrapped lock-could-not-be-released-exception
			// so your similar to acquire() method able to decide what to do
			// previous exception is accessable via LockCouldNotBeReleasedException::getPrevious()
			throw new \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeReleasedException('', 0, $e);
		}

		return TRUE;
	}

	/**
	 * Do real lock releasing magic.
	 *  Will be called from API method release() and maybe from shutdown method to release posibile acquired locks.
	 *
	 * @return boolean TRUE if lock was released, otherwise throw exception.
	 */
	abstract protected function doRelease();

	/**
	 * PHP shutdown function.
	 *  Force to release lock, to avoid dead/stale locks.
	 *
	 * @return void
	 */
	public function shutdown() {
		try {
			if ($this->getOption('autoReleaseOnPHPShutdown') && $this->isAcquired) {
				$this->preShutdown();
				$this->doRelease();
				$this->postShutdown();
			}
		} catch (\Exception $e) {}
	}

	/**
	 * Pre shutdown function.
	 *  Use this method to evaluate that all requirements are available
	 *  to release a lock.
	 *  E.g. validate if database-object is still present because we're
	 *  processing shutdown tasks, it it possible that our database-object
	 *  already bringed down. Here you can start it ups again, for example.
	 *
	 * @return void
	 */
	protected function preShutdown() {
	}

	/**
	 * Post shutdown function.
	 *  Oppsite preShutdown() method. Use this method to manually clean up
	 *  created objects, resources. You must manually clean up objects you
	 *  created before otherwise php won't shutdown normally.
	 *
	 * @return void
	 */
	protected function postShutdown() {
	}

}
