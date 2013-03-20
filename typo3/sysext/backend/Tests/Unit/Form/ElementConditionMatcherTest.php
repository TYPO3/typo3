<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Sebastian Michaelsen <michaelsen@t3seo.de>
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
 * Test case
 */
class ElementConditionMatcherTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Backend\Form\ElementConditionMatcher
	 */
	protected $fixture;

	/**
	 * Sets up this test case.
	 */
	protected function setUp() {
		$this->fixture = new \TYPO3\CMS\Backend\Form\ElementConditionMatcher();
	}

	/**
	 * Tears down this test case.
	 */
	protected function tearDown() {
		unset($this->fixture);
	}

	/**
	 * Returns data sets for the test matchConditionStrings
	 * Each dataset is an array with the following elements:
	 * - the condition string
	 * - the current record
	 * - the current flexform value key
	 * - the expected result
	 *
	 * @return array
	 */
	public function conditionStringDataProvider() {
		return array(
			'Invalid condition string' => array(
				'xINVALIDx:', array(), NULL, FALSE,
			),
			'EXT (#1)' => array (
				'EXT:neverloadedext:LOADED:TRUE', array(), NULL, FALSE
			),
			'EXT (#2)' => array (
				'EXT:neverloadedext:LOADED:FALSE', array(), NULL, TRUE
			),
			'EXT (#3)' => array (
				'EXT:backend:LOADED:TRUE', array(), NULL, TRUE
			),
			'EXT (#4)' => array (
				'EXT:backend:LOADED:FALSE', array(), NULL, FALSE
			),
			'FIELD (#1)' => array(
				'FIELD:uid:>:0', array(), NULL, FALSE
			),
			'FIELD (#2)' => array(
				'FIELD:uid:=:0', array(), NULL, FALSE
			),
			'FIELD (#3)' => array(
				'FIELD:foo:=:bar', array('foo' => 'bar'), NULL, TRUE
			),
			'FIELD (#4)' => array(
				'FIELD:foo:REQ:FALSE', array('foo' => 'bar'), NULL, FALSE
			),
			'FIELD (#5)' => array(
				'FIELD:foo:!=:baz', array('foo' => 'bar'), NULL, TRUE
			),
			'FIELD (#6)' => array(
				'FIELD:uid:-:3-42', array('uid' => '23'), NULL, TRUE
			),
			'FIELD (#7)' => array(
				'FIELD:uid:>=:42', array('uid' => '23'), NULL, FALSE
			),
			'FIELD (#8)' => array(
				'FIELD:foo:=:bar', array('foo' => array('vDEF' => 'bar')), 'vDEF', TRUE
			),
			'FIELD (#9)' => array(
				'FIELD:parentRec.foo:=:bar', array('parentRec' => array('foo' => 'bar')), 'vDEF', TRUE
			),
			'HIDE_L10N_SIBLINGS (#1)' => array(
				'HIDE_L10N_SIBLINGS', array(), NULL, FALSE
			),
			'HIDE_L10N_SIBLINGS (#2)' => array(
				'HIDE_L10N_SIBLINGS', array(), 'vDEF', TRUE
			),
			'HIDE_L10N_SIBLINGS (#3)' => array(
				'HIDE_L10N_SIBLINGS', array(), 'vEN', FALSE
			),
			'REC (#1)' => array(
				'REC:NEW:TRUE', array('uid' => NULL), NULL, TRUE
			),
			'REC (#2)' => array(
				'REC:NEW:FALSE', array('uid' => NULL), NULL, FALSE
			),
			'REC (#3)' => array(
				'REC:NEW:TRUE', array('uid' => 42), NULL, FALSE
			),
			'REC (#4)' => array(
				'REC:NEW:FALSE', array('uid' => 42), NULL, TRUE
			),
			'VERSION (#1)' => array(
				'VERSION:IS:TRUE', array('uid' => 42, 'pid' => -1), NULL, TRUE
			),
			'VERSION (#2)' => array(
				'VERSION:IS:FALSE', array('uid' => 42, 'pid' => 1), NULL, TRUE
			),
			'VERSION (#3)' => array(
				'VERSION:IS:TRUE', array('uid' => NULL, 'pid' => NULL), NULL, FALSE
			),
		);
	}

	/**
	 * @param string $condition
	 * @param array $record
	 * @param string $flexformValueKey
	 * @param string $expectedResult
	 * @dataProvider conditionStringDataProvider
	 * @test
	 */
	public function matchConditionStrings($condition, array $record, $flexformValueKey, $expectedResult) {
		$this->assertEquals($expectedResult, $this->fixture->match($condition, $record, $flexformValueKey));
	}

	/**
	 * @test
	 */
	public function matchHideForNonAdminsReturnsTrueIfBackendUserIsAdmin() {
		/** @var $backendUserMock \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject */
		$backendUserMock = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUserMock
			->expects($this->once())
			->method('isAdmin')
			->will($this->returnValue(TRUE));
		$GLOBALS['BE_USER'] = $backendUserMock;
		$this->assertTrue($this->fixture->match('HIDE_FOR_NON_ADMINS'));
	}

	/**
	 * @test
	 */
	public function matchHideForNonAdminsReturnsFalseIfBackendUserIsNotAdmin() {
		/** @var $backendUserMock \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject */
		$backendUserMock = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUserMock
			->expects($this->once())
			->method('isAdmin')
			->will($this->returnValue(FALSE));
		$GLOBALS['BE_USER'] = $backendUserMock;
		$this->assertFalse($this->fixture->match('HIDE_FOR_NON_ADMINS'));
	}

	/**
	 * @test
	 */
	public function matchHideL10NSiblingsExceptAdminReturnsTrueIfBackendUserIsAdmin() {
		/** @var $backendUserMock \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject */
		$backendUserMock = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUserMock
			->expects($this->once())
			->method('isAdmin')
			->will($this->returnValue(TRUE));
		$GLOBALS['BE_USER'] = $backendUserMock;
		$this->assertTrue($this->fixture->match('HIDE_L10N_SIBLINGS:except_admin'), array(), 'vEN');
	}

	/**
	 * @test
	 */
	public function matchHideL10NSiblingsExceptAdminReturnsFalseIfBackendUserIsNotAdmin() {
		/** @var $backendUserMock \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject */
		$backendUserMock = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUserMock
			->expects($this->once())
			->method('isAdmin')
			->will($this->returnValue(FALSE));
		$GLOBALS['BE_USER'] = $backendUserMock;
		$this->assertFalse($this->fixture->match('HIDE_L10N_SIBLINGS:except_admin'), array(), 'vEN');
	}
}
?>