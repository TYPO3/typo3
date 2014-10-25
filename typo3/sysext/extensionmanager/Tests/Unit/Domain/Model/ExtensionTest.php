<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Model;

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
 * Extension test
 */
class ExtensionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

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
	 * @param int $expected Expected result
	 * @return void
	 */
	public function getCategoryIndexFromStringOrNumberReturnsIndex($input, $expected) {
		$extension = new \TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
		$this->assertEquals($expected, $extension->getCategoryIndexFromStringOrNumber($input));
	}
}
