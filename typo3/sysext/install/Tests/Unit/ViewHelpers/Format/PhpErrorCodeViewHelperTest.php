<?php
namespace TYPO3\CMS\Install\Tests\Unit\ViewHelpers\Format;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Ernesto Baschny <ernst@cron-it.de>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
 */
class PhpErrorCodeViewHelperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Format\NumberViewHelper
	 */
	protected $viewHelper;

	/**
	 * Setup the test case scenario
	 */
	public function setUp() {
		$this->viewHelper = $this->getMock('TYPO3\CMS\Install\ViewHelpers\Format\PhpErrorCodeViewHelper', array('dummy'));
	}

	/**
	 * @return array
	 */
	public function errorCodesDataProvider() {
		return array(
			array(
				'errorCode' => E_ERROR,
				'expectedString' => 'E_ERROR',
			),
			array(
				'errorCode' => E_ALL,
				'expectedString' => 'E_ALL',
			),
			array(
				'errorCode' => E_ERROR ^ E_WARNING ^ E_PARSE,
				'expectedString' => 'E_ERROR | E_WARNING | E_PARSE',
			),
			array(
				'errorCode' => E_RECOVERABLE_ERROR ^ E_USER_DEPRECATED,
				'expectedString' => 'E_RECOVERABLE_ERROR | E_USER_DEPRECATED',
			)
		);
	}

	/**
	 * @param $errorCode
	 * @param $expectedString
	 * @test
	 * @dataProvider errorCodesDataProvider
	 */
	public function renderPhpCodesCorrectly($errorCode, $expectedString) {
		$actualString = $this->viewHelper->render($errorCode);
		$this->assertEquals($expectedString, $actualString);
	}

}
