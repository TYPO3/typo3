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
class DigitFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Filter\DigitFilter
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Form\Filter\DigitFilter();
	}

	public function validDataProvider() {
		return array(
			'1,00 -> 100' => array('1,00', '100'),
			'1E+49 -> 149' => array('1E+49', '149'),
			'100 -> 100' => array('100', '100'),
			'00000 -> 00000' => array('00000', '00000'),
			'ABCD -> ""' => array('ABCD', ''),
		);
	}

	/**
	 * @test
	 * @dataProvider validDataProvider
	 */
	public function filterForStringsReturnsStringsFilteredToOnlyContainDigits($input, $expected) {
		$this->assertSame(
			$expected,
			$this->fixture->filter($input)
		);
	}
}
