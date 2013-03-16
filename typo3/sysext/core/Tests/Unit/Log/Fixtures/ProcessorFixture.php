<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Fixtures;

/***************************************************************
 * Copyright notice
 *
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
 * A processor dedicated for testing
 *
 * @author Steffen Müller <typo3@t3node.com>
 */
class ProcessorFixture extends \TYPO3\CMS\Core\Log\Processor\AbstractProcessor {

	/**
	 * Processing the record
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $record
	 * @return \TYPO3\CMS\Core\Log\LogRecord
	 */
	public function processLogRecord(\TYPO3\CMS\Core\Log\LogRecord $record) {
		return $record;
	}

}

?>