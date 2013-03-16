<?php
namespace TYPO3\CMS\Form\Tests\Unit\Filter;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Andreas Lappe <nd@kaeufli.ch>, kaeufli.ch
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
 * @author Andreas Lappe <nd@kaeufli.ch>
 */
class TrimFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Filter\TrimFilter
	 */
	protected $fixture = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Form\Filter\TrimFilter();
	}

	/**
	 * Tear down
	 */
	public function tearDown() {
		$this->fixture = NULL;
	}

	public function stringProvider() {
		return array(
			'\tsome text ' => array("\tsome text ", 'some text'),
			'some text   ' => array('some text   ', 'some text'),
			'some text^M' => array('some text
', 'some text'),
		);
	}

	public function stringProviderForCharacterList() {
		return array(
			'$some text;' => array('$some text;', 'some text', '$;'),
			'$some text ' => array('$some text ', 'some text', '$ '),
			'^Msome text ' => array('
some text ', 'some text', '
 '),
		);
	}

	/**
	 * @test
	 * @dataProvider stringProvider
	 */
	public function filterForStringWithWhitespaceInFrontAndEndReturnsStringWithoutThisWhitespace($input, $expected) {
		$this->assertSame(
			$expected,
			$this->fixture->filter($input)
		);
	}

	/**
	 * @test
	 * @dataProvider stringProviderForCharacterList
	 */
	public function filterForStringWithCharactersInCharacterListReturnsStringWithoutTheseCharacters($input, $expected, $characterList) {
		$this->fixture->setCharacterList($characterList);

		$this->assertSame(
			$expected,
			$this->fixture->filter($input)
		);
	}
}
?>