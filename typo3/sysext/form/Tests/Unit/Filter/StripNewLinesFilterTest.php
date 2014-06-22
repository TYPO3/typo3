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
class StripNewLinesFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Filter\StripNewLinesFilter
	 */
	protected $fixture = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Form\Filter\StripNewLinesFilter();
	}

	public function dataProviderWithNewlines() {
		return array(
			'some\ntext' => array("some\ntext", 'some text'),
			'somechr(10)text' => array('some' . chr(10) . 'text', 'some text'),
			'some^Mtext' => array('some
text', 'some text'),
			'trailing newline^M' => array('trailing newline
', 'trailing newline '),
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderWithNewlines
	 */
	public function filterForStringWithNewlineReturnsStringWithoutNewline($input, $expected) {
		$this->assertSame(
			$expected,
			$this->fixture->filter($input)
		);
	}
}
