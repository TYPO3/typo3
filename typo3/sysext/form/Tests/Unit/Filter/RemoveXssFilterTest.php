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
class RemoveXssFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Filter\RemoveXssFilter
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Form\Filter\RemoveXssFilter();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	public function maliciousStringProvider() {
		return array(
			'<IMG SRC="javascript:alert(\'XSS\');">' => array('<IMG SRC="javascript:alert(\'XSS\');">'),
			'<SCRIPT SRC=http://ha.ckers.org/xss.js></SCRIPT>' => array('<SCRIPT SRC=http://ha.ckers.org/xss.js></SCRIPT>'),
			'<IMG SRC=JaVaScRiPt:alert(\'XSS\')>' => array('<IMG SRC=JaVaScRiPt:alert(\'XSS\')>'),
			'<IMG SRC=javascript:alert(&quot;XSS&quot;)>' => array('<IMG SRC=javascript:alert(&quot;XSS&quot;)>'),
			'<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>' => array('<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>'),
		);
	}

	/**
	 * @test
	 * @dataProvider maliciousStringProvider
	 */
	public function filterForMaliciousStringReturnsInputFilteredOfXssCode($input) {
		$this->assertNotSame(
			$input,
			$this->fixture->filter($input)
		);
	}
}
?>