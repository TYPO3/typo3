<?php
namespace TYPO3\CMS\Install\Tests\Unit\ViewHelpers\Format;

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
