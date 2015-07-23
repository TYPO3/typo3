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
use TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca;

/**
 * Test case
 */
class InitializeProcessedTcaTest extends UnitTestCase {

	/**
	 * @var InitializeProcessedTca
	 */
	protected $subject;

	public function setUp() {
		$this->subject = new InitializeProcessedTca();
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfVanillaTableTcaIsNotSet() {
		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438505113);
		$this->subject->addData([]);
	}

	/**
	 * @test
	 */
	public function addDataSetsProcessedTcaToVanillaTableTca() {
		$input = [
			'recordTypeValue' => 'aType',
			'vanillaTableTca' => [
				'columns' => [
					'aField' => [
						'type' => 'aType',
					],
				],
				'types' => [
					'aType' => [
						'showitem' => '',
					],
				],
			],
		];
		$expected = $input;
		$expected['processedTca'] = $input['vanillaTableTca'];
		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfColumnsIsMissing() {
		$input = [
			'vanillaTableTca' => [],
		];
		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438594406);
		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfTypesHasNoShowitem() {
		$input = [
			'recordTypeValue' => 'aType',
			'vanillaTableTca' => [
				'columns' => [
					'aField' => [
						'type' => 'aType',
					],
				],
				'types' => [
					'aType' => [],
				],
			],
		];
		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438614542);
		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfTcaColumnsHasNoTypeSet() {
		$this->markTestIncomplete('skipped for now, this is not save');
		$input = [
			'vanillaTableTca' => [
				'columns' => [
					'aField' => [],
				],
			],
		];
		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438594044);
		$this->subject->addData($input);
	}

}
