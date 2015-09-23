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
class CurrencyFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Filter\CurrencyFilter
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Form\Filter\CurrencyFilter();
	}

	/**
	 * Data provider for filterForVariousIntegerInputsReturnsFormattedCurrencyNotation
	 * Each data set is an array with the following elements:
	 *  - input value
	 *  - thousand separator character
	 *  - decimal point character
	 *  - expected result
	 *
	 * @returns array
	 */
	public function validDataProvider() {
		return array(
			'1200 => 1.200,00' => array(
				'1200',
				'.',
				',',
				'1.200,00'
			),
			'0 => 0,00' => array(
				'0',
				NULL,
				',',
				'0,00'
			),
			'3333.33 => 3,333.33' => array(
				'3333.33',
				',',
				'.',
				'3,333.33'
			),
			'1099.33 => 1 099,33' => array(
				'1099.33',
				' ',
				',',
				'1 099,33'
			),
			'1200,00 => 1.200,00' => array(
					'1200,00',
					'.',
					',',
					'1.200,00'
			),
			'1.200,00 => 1.200,00' => array(
					'1.200,00',
					'.',
					',',
					'1.200,00'
			),
			'1.200 => 1.200,00' => array(
					'1.200',
					'.',
					',',
					'1.200,00'
			),
			'-1 => -1,00' => array(
					'-1',
					'.',
					',',
					'-1,00'
			),
			'1.200 => 1.200,00' => array(
					'1.200',
					'.',
					',',
					'1.200,00'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider validDataProvider
	 */
	public function filterForVariousIntegerInputsReturnsFormattedCurrencyNotation($input, $thousandSeparator, $decimalPoint, $expected) {
		$this->fixture->setThousandSeparator($thousandSeparator);
		$this->fixture->setDecimalsPoint($decimalPoint);
		$this->assertSame($expected, $this->fixture->filter($input));
	}
}
