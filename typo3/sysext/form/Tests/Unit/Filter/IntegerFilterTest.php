<?php
namespace TYPO3\CMS\Form\Tests\Unit\Filter;

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
 * Test case
 *
 * @author Andreas Lappe <nd@kaeufli.ch>
 */
class IntegerFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Filter\IntegerFilter
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Form\Filter\IntegerFilter();
	}

	public function dataProvider() {
		return array(
			'"1" -> 1' => array('1', 1),
			'1 -> 1' => array(1, 1),
			'1.1 -> 1' => array(1.1, 1),
			'1+E42 -> 1' => array(1+E42, 1),
			'a -> 0' => array(a, 0),
			'a42 -> 0' => array('a42', 0),
			'-100.00 -> -100' => array(-100.00, -100),
		);
	}

	/**
	 * @test
	 * @dataProvider dataProvider
	 */
	public function filterForVariousInputReturnsInputCastedToInteger($input, $expected) {
		$this->assertSame(
			$expected,
			$this->fixture->filter($input)
		);
	}
}
