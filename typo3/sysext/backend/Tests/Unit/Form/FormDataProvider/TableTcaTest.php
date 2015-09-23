<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

/*
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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Backend\Form\FormDataProvider\TableTca;

/**
 * Test case
 */
class TableTcaTest extends UnitTestCase {

	/**
	 * @var TableTca
	 */
	protected $subject;

	protected function setUp() {
		$this->subject = new TableTca();
	}

	/**
	 * @test
	 */
	public function addDataSetsTableTcaFromGlobalsInResult() {
		$input = [
			'tableName' => 'aTable',
		];
		$expected = array('foo');
		$GLOBALS['TCA'][$input['tableName']] = $expected;
		$result = $this->subject->addData($input);
		$this->assertEquals($expected, $result['vanillaTableTca']);
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfGlobalTableTcaIsNotSet() {
		$input = [
			'tableName' => 'aTable',
		];

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1437914223);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfGlobalTableTcaIsNotAnArray() {
		$input = [
			'tableName' => 'aTable',
		];
		$GLOBALS['TCA'][$input['tableName']] = 'foo';
		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1437914223);

		$this->subject->addData($input);
	}

}
