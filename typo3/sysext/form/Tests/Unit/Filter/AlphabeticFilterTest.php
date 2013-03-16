<?php
namespace TYPO3\CMS\Form\Tests\Unit\Filter;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Andreas Lappe <a.lappe@kuehlhaus.com>, kuehlhaus AG
 *
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
	 * Tear down
	 */
	public function tearDown() {
		$this->fixture = NULL;
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


?>