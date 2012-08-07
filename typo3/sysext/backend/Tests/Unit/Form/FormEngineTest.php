<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Dmitry Dulepov <dmitry.dulepov@gmail.com>
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
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Tests for Inline Relational Record Editing form rendering.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class FormEngineTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/** @var \TYPO3\CMS\Backend\Form\FormEngine */
	protected $fixture;

	protected function setUp() {
		$this->fixture = new \TYPO3\CMS\Backend\Form\FormEngine();
	}

	protected function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function testSingleCondition() {
		$row = array(
			'testField' => 10
		);
		$this->assertTrue($this->fixture->isDisplaySingleCondition('FIELD:testField:=:10', $row), 'isDisplaySingleCondition fails to test for equal values');
		$this->assertTrue($this->fixture->isDisplaySingleCondition('FIELD:testField:>:9', $row), 'isDisplaySingleCondition fails to test for greater values');
		$this->assertTrue($this->fixture->isDisplaySingleCondition('FIELD:testField:<:11', $row), 'isDisplaySingleCondition fails to test for lesser values');
	}

	/**
	 * @test
	 */
	public function testMultipleAndConditions() {
		$row = array(
			'testField' => 10
		);
		$conditions = array(
			'AND' => array(
				'FIELD:testField:>:9',
				'FIELD:testField:<:11',
			)
		);
		$this->assertTrue($this->fixture->isDisplayCondition($conditions, $row), 'isDisplayCondition fails on AND conditions');
	}

	/**
	 * @test
	 */
	public function testMultipleOrConditions() {
		$row = array(
			'testField' => 10
		);
		$conditions = array(
			'OR' => array(
				'FIELD:testField:<:9',
				'FIELD:testField:<:11',
			)
		);
		$this->assertTrue($this->fixture->isDisplayCondition($conditions, $row), 'isDisplayCondition fails on OR conditions');
	}

	/**
	 * @test
	 */
	public function testMultipleConditionsWithoutOperator() {
		$row = array(
			'testField' => 10
		);
		$conditions = array(
			'' => array(
				'FIELD:testField:<:9',
				'FIELD:testField:>:11',
			)
		);
		$this->assertTrue($this->fixture->isDisplayCondition($conditions, $row), 'isDisplayCondition fails on conditions without operators');
	}

	/**
	 * @test
	 */
	public function testNestedConditions() {
		$row = array(
			'testField' => 10
		);
		$conditions = array(
			'AND' => array(
				'FIELD:testField:>:9',
				'OR' => array(
					'FIELD:testField:<:100',
					'FIELD:testField:>:-100',
				)
			)
		);
		$this->assertTrue($this->fixture->isDisplayCondition($conditions, $row), 'isDisplayCondition fails on nested conditions');
	}
}
