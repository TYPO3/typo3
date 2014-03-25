<?php
namespace TYPO3\CMS\Core\Tests\Unit\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Klaas Johan Kooistra <k.kooistra@drecomm.nl>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * Test case for \TYPO3\CMS\Core\Database\RelationHandler
 */
class RelationHandlerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Database\RelationHandler
	 */
	protected $fixture;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Database\\RelationHandler', array('purgeVersionedIds', 'purgeLiveVersionedIds'));
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function purgeItemArrayReturnsFalseIfVersioningForTableIsDisabled() {
		$GLOBALS['TCA']['sys_category']['ctrl']['versioningWS'] = FALSE;

		$this->fixture->tableArray = array(
			'sys_category' => array(1, 2, 3),
		);

		$this->assertFalse($this->fixture->purgeItemArray(0));
	}

	/**
	 * @test
	 */
	public function purgeItemArrayReturnsTrueIfItemsHaveBeenPurged() {
		$GLOBALS['TCA']['sys_category']['ctrl']['versioningWS'] = 2;

		$this->fixture->tableArray = array(
			'sys_category' => array(1, 2, 3),
		);

		$this->fixture->expects($this->once())
			->method('purgeVersionedIds')
			->with('sys_category', array(1, 2, 3))
			->will($this->returnValue(array(2)));

		$this->assertTrue($this->fixture->purgeItemArray(0));
	}
}