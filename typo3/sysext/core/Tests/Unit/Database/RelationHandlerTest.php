<?php
namespace TYPO3\CMS\Core\Tests\Unit\Database;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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