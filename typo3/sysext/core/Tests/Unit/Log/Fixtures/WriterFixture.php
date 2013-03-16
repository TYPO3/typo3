<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Fixtures;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Ingo Renner (ingo@typo3.org)
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
 * A writer dedicated for testing
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class WriterFixture extends \TYPO3\CMS\Core\Log\Writer\AbstractWriter {

	/**
	 * @var array
	 */
	protected $records = array();

	public function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record) {
		$this->records[] = $record;
	}

}

?>