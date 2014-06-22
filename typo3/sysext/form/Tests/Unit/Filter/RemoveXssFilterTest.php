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
class RemoveXssFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Filter\RemoveXssFilter
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Form\Filter\RemoveXssFilter();
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
