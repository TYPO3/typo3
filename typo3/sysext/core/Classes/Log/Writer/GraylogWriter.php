<?php
namespace TYPO3\CMS\Core\Log\Writer;

	/***************************************************************
	 * Copyright notice
	 *
	 * (c) 2013 Georg Ringer (georg.ringer@cyberhouse.at)
	 * All rights reserved
	 *
	 * This script is part of the TYPO3 project. The TYPO3 project is
	 * free software; you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation; either version 2 of the License, or
	 * (at your option) any later version.
	 *
	 * The GNU General Public License can be found at
	 * http://www.gnu.org/copyleft/gpl.html.
	 *
	 * This script is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * This copyright notice MUST APPEAR in all copies of the script!
	 ***************************************************************/
/**
 * Log writer that writes the log records into Graylog
 *
 * @author Georg Ringer <georg.ringer@cyberhouse.at>
 */
class GraylogWriter extends \TYPO3\CMS\Core\Log\Writer\AbstractWriter {

	/** @var string */
	const GRAYLOG2_GELF_VERSION = '1.0';

	/** @var \GELFMessagePublisher */
	protected $publisher;

	/* @var string */
	protected $host = '';

	/** @var string */
	protected $facility = '';

	/** @var integer */
	protected $port = NULL;

	/** @var integer */
	protected $chunkSize = NULL;

	/**
	 * Constructor
	 *
	 * @param array $options
	 * @return \TYPO3\CMS\Core\Log\Writer\GraylogWriter
	 */
	public function __construct(array $options = array()) {
		require_once(PATH_typo3 . 'contrib/graylog2-php/GELFMessagePublisher.php');
		require_once(PATH_typo3 . 'contrib/graylog2-php/GELFMessage.php');

		parent::__construct($options);
	}

	/**
	 * Push the log to GrayLog2
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $record Log record
	 * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
	 * @throws \RuntimeException
	 */
	public function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record) {
		$message = new \GELFMessage();
		$message->setVersion(self::GRAYLOG2_GELF_VERSION);
		$message->setHost(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST'));
		$message->setTimestamp($GLOBALS['EXEC_TIME']);
		$message->setFacility($this->facility);
		$message->setLevel($record->getLevel());
		$message->setShortMessage($record->getMessage());
		$message->setFullMessage($record->getMessage());
		$message->setAdditional('LoggerName', $record->getComponent());
		$message->setAdditional('Thread', $record->getRequestId());

		$backTrace = $this->getLogLocation();
		$message->setAdditional('ClassName', $backTrace['class']);
		$message->setFile($backTrace['file']);
		$message->setLine($backTrace['line']);

		$publisher = new \GELFMessagePublisher($this->host, $this->port, $this->chunkSize);
		$publisher->publish($message);

		return $this;
	}

	/**
	 * Get additional information
	 *
	 * @return array
	 */
	protected function getLogLocation() {
		$locationInfo = array(
			'line' => NULL,
			'file' => NULL,
			'class' => NULL,
			'function' => NULL,
		);

		if (function_exists('debug_backtrace')) {
			$trace = debug_backtrace();
			$prevHop = NULL;

			// Make a downsearch to identify the caller
			$hop = array_pop($trace);
			while ($hop !== NULL) {
				if (isset($hop['class'])) {
					// We are sometimes in functions = no class available: avoid php warning here
					if (!empty($hop['class']) and ($hop['class'] === 'TYPO3\\CMS\\Core\\Log\\Logger' ||
							(get_parent_class($hop['class'])) === 'TYPO3\\CMS\\Core\\Log\\Logger')
					) {
						$locationInfo['line'] = $hop['line'];
						$locationInfo['file'] = $hop['file'];
						break;
					}
				}
				$prevHop = $hop;
				$hop = array_pop($trace);
			}
			$locationInfo['class'] = isset($prevHop['class']) ? $prevHop['class'] : 'main';
			if (isset($prevHop['function']) and
				$prevHop['function'] !== 'include' and
				$prevHop['function'] !== 'include_once' and
				$prevHop['function'] !== 'require' and
				$prevHop['function'] !== 'require_once'
			) {
				$locationInfo['function'] = $prevHop['function'];
			} else {
				$locationInfo['function'] = 'main';
			}
		}
		return $locationInfo;
	}

	/**
	 * Sets host
	 *
	 * @param string $host
	 * @return void
	 */
	public function setHost($host) {
		$this->host = $host;
	}

	/**
	 * Sets port
	 *
	 * @param integer $port
	 * @return void
	 */
	public function setPort($port) {
		$this->port = $port;
	}

	/**
	 * Sets chunkSize
	 *
	 * @param integer $chunkSize
	 * @return void
	 */
	public function setChunkSize($chunkSize) {
		$this->chunkSize = $chunkSize;
	}

	/**
	 * Set the facility
	 *
	 * @param string $facility
	 * @return void
	 */
	public function setFacility($facility) {
		$this->facility = $facility;
	}
}

?>