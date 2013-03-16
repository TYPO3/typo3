<?php
namespace TYPO3\CMS\Core\Log\Processor;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2012 Ingo Renner (ingo@typo3.org)
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
 * Log processor interface
 *
 * Processors provide additional data in an automatic way, without having to
 * collect that data yourself.
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
interface ProcessorInterface {
	/**
	 * Processes a log record and adds additional data.
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $logRecord The log record to process
	 * @return \TYPO3\CMS\Core\Log\LogRecord The processed log record with additional data
	 */
	public function processLogRecord(\TYPO3\CMS\Core\Log\LogRecord $logRecord);

}

?>