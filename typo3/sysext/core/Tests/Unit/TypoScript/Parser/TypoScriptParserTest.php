<?php
namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\Parser;

/***********************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Kai Vogel <kai.vogel@speedprogs.de>, Speedprogs.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 **********************************************************************/

class TypoScriptParserTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $fixture;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->fixture = $this->getAccessibleMock('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser', array('dummy'), array(), '', FALSE);
	}

	/**
	 * @test
	 */
	public function setValHandlesDoubleQoutesInTheMiddleOfTypoScriptSetupKeys() {
		$keys = 'foo.bar."settings.key".value';
		$value = array('foobar');

		$expected = array(
			'foo.' => array(
				'bar.' => array(
					'settings.key.' => array(
						'value' => 'foobar',
					),
				),
			),
		);

		$setup = array();
		$this->fixture->setVal($keys, $setup, $value);
		$this->assertEquals($setup, $expected);
	}

	/**
	 * @test
	 */
	public function setValHandlesDoubleQoutesAtTheEndOfTypoScriptSetupKeys() {
		$keys = 'foo.bar."settings.key"';
		$value = array('foobar');

		$expected = array(
			'foo.' => array(
				'bar.' => array(
					'settings.key' => 'foobar',
				),
			),
		);

		$setup = array();
		$this->fixture->setVal($keys, $setup, $value);
		$this->assertEquals($setup, $expected);
	}

}
?>