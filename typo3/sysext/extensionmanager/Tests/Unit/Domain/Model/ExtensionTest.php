<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Model;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Extension test
 */
class ExtensionTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * Data provider for getCategoryIndexFromStringOrNumberReturnsIndex
	 *
	 * @return array
	 */
	public function getCategoryIndexFromStringOrNumberReturnsIndexDataProvider() {
		return array(
			'empty string' => array(
				'',
				4
			),
			'existing category string' => array(
				'plugin',
				3
			),
			'not existing category string' => array(
				'foo',
				4
			),
			'string number 3' => array(
				'3',
				3
			),
			'integer 3' => array(
				3,
				3
			),
			'string number not in range -1' => array(
				'-1',
				4
			),
			'integer not in range -1' => array(
				-1,
				4
			),
			'string number not in range 11' => array(
				'11',
				4
			),
			'integer not in range 11' => array(
				11,
				4
			),
			'object' => array(
				new \stdClass(),
				4
			),
			'array' => array(
				array(),
				4
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getCategoryIndexFromStringOrNumberReturnsIndexDataProvider
	 * @param string|integer $input Given input
	 * @param integer $expected Expected result
	 * @return void
	 */
	public function getCategoryIndexFromStringOrNumberReturnsIndex($input, $expected) {
		$extension = new \TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
		$this->assertEquals($expected, $extension->getCategoryIndexFromStringOrNumber($input));
	}
}

?>