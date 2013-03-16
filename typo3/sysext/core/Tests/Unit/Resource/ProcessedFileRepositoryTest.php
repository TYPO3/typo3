<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Helmut Hummel <helmut.hummel@typo3.org>
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
 * Processed file repository test
 */
class ProcessedFileRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function cleanUnavailableColumnsWorks() {
		$fixture = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository', array('dummy'), array(), '', FALSE);
		$databaseMock = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('admin_get_fields'));
		$databaseMock->expects($this->once())->method('admin_get_fields')->will($this->returnValue(array('storage' => '', 'checksum' => '')));
		$fixture->_set('databaseConnection', $databaseMock);

		$actual = $fixture->_call('cleanUnavailableColumns', array('storage' => 'a', 'checksum' => 'b', 'key3' => 'c'));

		$this->assertSame(array('storage' => 'a', 'checksum' => 'b'), $actual);
	}

}
?>