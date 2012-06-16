<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Oliver Hader <oliver.hader@typo3.org>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
 * Test suite for filtering files by their extensions.
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_file_Utility_FileExtensionFilterTest extends Tx_Phpunit_TestCase {
	/**
	 * @var t3lib_file_Utility_FileExtensionFilter
	 */
	protected $filter;

	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @var t3lib_TCEmain|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $tceMainMock;

	/**
	 * Sets up this test suite.
	 */
	protected function setUp() {
		$this->filter = new t3lib_file_Utility_FileExtensionFilter();
		$this->tceMainMock = $this->getMock('t3lib_TCEmain', array('deleteAction'), array());
	}

	/**
	 * Cleans up this test suite.
	 */
	protected function tearDown() {
		unset($this->tceMainMock);
		unset($this->parameters);
		unset($this->filter);
	}

	/**
	 * @param array|string $allowed
	 * @param array|string $disallowed
	 * @param array|string $values
	 *
	 * @test
	 * @dataProvider invalidInlineChildrenFilterParametersDataProvider
	 */
	public function areInlineChildrenFilteredWithInvalidParameters($allowed, $disallowed, $values) {
		$this->parameters = array(
			'allowedFileExtensions' => $allowed,
			'disallowedFileExtensions' => $disallowed,
			'values' => $values,
		);

		$this->tceMainMock->expects($this->never())->method('deleteAction');
		$this->filter->filterInlineChildren($this->parameters, $this->tceMainMock);
	}

	/**
	 * @return array
	 */
	public function invalidInlineChildrenFilterParametersDataProvider() {
		return array(
			array('', '', array(0, 1, 3, 4)),
			array(NULL, NULL, NULL),
			array(NULL, NULL, array('', NULL, FALSE)),
		);
	}
}

?>