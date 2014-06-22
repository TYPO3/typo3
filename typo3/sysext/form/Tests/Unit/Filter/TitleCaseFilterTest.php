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
class TitleCaseFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Filter\TitleCaseFilter
	 */
	protected $fixture = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Form\Filter\TitleCaseFilter();
	}

	public function stringProvider() {
		return array(
			'some text' => array('some text', 'Some Text'),
			'some Text' => array('some Text', 'Some Text'),
			'Ein Maß' => array('Ein Maß', 'Ein Maß'),
			'¿por que?' => array('¿por que?', '¿por Que?'),
		);
	}

	/**
	 * @test
	 * @dataProvider stringProvider
	 */
	public function filterForStringReturnsStringWithUppercasedWords($input, $expected) {
		$this->assertSame(
			$expected,
			$this->fixture->filter($input)
		);
	}
}
