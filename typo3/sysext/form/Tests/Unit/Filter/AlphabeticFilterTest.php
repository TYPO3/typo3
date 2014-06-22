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
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 */
class AlphabeticFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Filter\AlphabeticFilter
	 */
	protected $fixture = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Form\Filter\AlphabeticFilter();
	}

	/**
	 * @test
	 */
	public function filterForStringWithUnicodeCharactersAndSpacesReturnsInputString() {
		$input = 'My name contains äøüößØœ';
		// This is default, but let's be explicit:
		$this->fixture->setAllowWhiteSpace(TRUE);
		$this->assertSame($input, $this->fixture->filter($input));
	}

	/**
	 * @test
	 */
	public function filterForStringWithUnicodeCharactersAndSpacesWithAllowWhitespaceSetToFalseReturnsInputStringWithoutSpaces() {
		$input = 'My name contains äøüößØœ';
		$expected = 'MynamecontainsäøüößØœ';
		$this->fixture->setAllowWhiteSpace(FALSE);
		$this->assertSame($expected, $this->fixture->filter($input));
	}

}
