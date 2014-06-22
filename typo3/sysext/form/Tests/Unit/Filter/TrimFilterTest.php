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
