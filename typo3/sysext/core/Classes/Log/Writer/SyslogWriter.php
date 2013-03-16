<?php
namespace TYPO3\CMS\Core\Log\Writer;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Ingo Renner (ingo@typo3.org)
 * (c) 2011-2013 Steffen Müller (typo3@t3node.com)
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
 * Log writer that writes to syslog
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Steffen Müller <typo3@t3node.com>
 */
class SyslogWriter extends \TYPO3\CMS\Core\Log\Writer\AbstractWriter {

	/**
	 * List of valid syslog facility names.
	 * private as it's not supposed to be changed.
	 *
	 * @var array Facilities
	 */
	private $facilities = array(
		'auth' => LOG_AUTH,
		'authpriv' => LOG_AUTHPRIV,
		'cron' => LOG_CRON,
		'daemon' => LOG_DAEMON,
		'kern' => LOG_KERN,
		'lpr' => LOG_LPR,
		'mail' => LOG_MAIL,
		'news' => LOG_NEWS,
		'syslog' => LOG_SYSLOG,
		'user' => LOG_USER,
		'uucp' => LOG_UUCP
	);

	/**
	 * Type of program that is logging to syslog.
	 *
	 * @var integer
	 */
	protected $facility = LOG_USER;

	/**
	 * Constructor, adds facilities on *nix environments.
	 *
	 * @param array $options Configuration options
	 * @throws \RuntimeException if connection to syslog cannot be opened
	 * @see \TYPO3\CMS\Core\Log\Writer\AbstractWriter
	 */
	public function __construct(array $options = array()) {
		// additional facilities for *nix environments
		if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
			$this->facilities['local0'] = LOG_LOCAL0;
			$this->facilities['local1'] = LOG_LOCAL1;
			$this->facilities['local2'] = LOG_LOCAL2;
			$this->facilities['local3'] = LOG_LOCAL3;
			$this->facilities['local4'] = LOG_LOCAL4;
			$this->facilities['local5'] = LOG_LOCAL5;
			$this->facilities['local6'] = LOG_LOCAL6;
			$this->facilities['local7'] = LOG_LOCAL7;
		}
		parent::__construct($options);
		if (!openlog('TYPO3', (LOG_ODELAY | LOG_PID), $this->facility)) {
			$facilityName = array_search($this->facility, $this->facilities);
			throw new \RuntimeException('Could not open syslog for facility ' . $facilityName, 1321722682);
		}
	}

	/**
	 * Destructor, closes connection to syslog.
	 */
	public function __destruct() {
		closelog();
	}

	/**
	 * Sets the facility to use when logging to syslog.
	 *
	 * @param integer $facility Facility to use when logging.
	 * @return void
	 */
	public function setFacility($facility) {
		if (array_key_exists(strtolower($facility), $this->facilities)) {
			$this->facility = $this->facilities[strtolower($facility)];
		}
	}

	/**
	 * Returns the data of the record in syslog format
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $record
	 * @return string
	 */
	public function getMessageForSyslog(\TYPO3\CMS\Core\Log\LogRecord $record) {
		$data = $record->getData();
		$data = !empty($data) ? '- ' . json_encode($data) : '';
		$message = sprintf('[request="%s" component="%s"] %s %s', $record->getRequestId(), $record->getComponent(), $record->getMessage(), $data);
		return $message;
	}

	/**
	 * Writes the log record to syslog
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $record Log record
	 * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface
	 * @throws \RuntimeException
	 */
	public function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record) {
		if (FALSE === syslog($record->getLevel(), $this->getMessageForSyslog($record))) {
			throw new \RuntimeException('Could not write log record to syslog', 1345036337);
		}
		return $this;
	}

}


?>