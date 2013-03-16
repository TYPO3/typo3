<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validation;
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
 * Test case for class \TYPO3\CMS\Form\Validation\EqualsValidator.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 */
class EqualsValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {
	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\EqualsValidator
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->fixture = new \TYPO3\CMS\Form\Validation\EqualsValidator(array());
	}

	public function tearDown() {
		unset($this->helper, $this->fixture);
	}

	public function invalidPairProvider() {
		return array(
			'somethingElse !== something' => array(array('somethingElse', 'something')),
			'4 !== 3'                     => array(array(4, 3))
		);
	}

	/**
	 * @test
	 * @dataProvider invalidPairProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->fixture->setFieldName('myField');
		$this->fixture->setField($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myField' => $input[1],
			$input[0] => TRUE
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->fixture->isValid()
		);
	}
}
?>