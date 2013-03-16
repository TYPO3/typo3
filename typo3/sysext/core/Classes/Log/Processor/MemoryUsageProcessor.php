<?php
namespace TYPO3\CMS\Core\Log\Processor;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Ingo Renner (ingo@typo3.org)
 * (c) 2012-2013 Steffen Müller (typo3@t3node.com)
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
 * Memory usage processor methods.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Steffen Müller <typo3@t3node.com>
 */
class MemoryUsageProcessor extends \TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor {

	/**
	 * Processes a log record and adds memory usage information.
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $logRecord The log record to process
	 * @return \TYPO3\CMS\Core\Log\LogRecord The processed log record with additional data
	 * @see memory_get_usage()
	 */
	public function processLogRecord(\TYPO3\CMS\Core\Log\LogRecord $logRecord) {
		$bytes = memory_get_usage($this->getRealMemoryUsage());
		if ($this->formatSize) {
			$size = \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($bytes);
		} else {
			$size = $bytes;
		}
		$logRecord->addData(array(
			'memoryUsage' => $size
		));
		return $logRecord;
	}

}


?>