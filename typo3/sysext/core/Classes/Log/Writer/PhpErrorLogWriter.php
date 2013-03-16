<?php
namespace TYPO3\CMS\Core\Log\Writer;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Steffen Gebert (steffen.gebert@typo3.org)
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
 * Log writer that writes the log records into PHP error log.
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 * @author Steffen Müller <typo3@t3node.com>
 */
class PhpErrorLogWriter extends \TYPO3\CMS\Core\Log\Writer\AbstractWriter {

	/**
	 * Writes the log record
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $record Log record
	 * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
	 * @throws \RuntimeException
	 */
	public function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record) {
		$levelName = \TYPO3\CMS\Core\Log\LogLevel::getName($record->getLevel());
		$data = $record->getData();
		$data = !empty($data) ? '- ' . json_encode($data) : '';
		$message = sprintf('TYPO3 [%s] request="%s" component="%s": %s %s', $levelName, $record->getRequestId(), $record->getComponent(), $record->getMessage(), $data);
		if (FALSE === error_log($message)) {
			throw new \RuntimeException('Could not write log record to PHP error log', 1345036336);
		}
		return $this;
	}

}


?>