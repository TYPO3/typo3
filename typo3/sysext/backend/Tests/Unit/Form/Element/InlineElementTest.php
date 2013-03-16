<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
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
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class InlineElementTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {
	/**
	 * @var \TYPO3\CMS\Backend\Form\Element\InlineElement
	 */
	protected $fixture;

	/**
	 * Sets up this test case.
	 */
	protected function setUp() {
		// @todo Use $this->buildAccessibleProxy() if properties are protected
		$this->fixture = new \TYPO3\CMS\Backend\Form\Element\InlineElement();
		$this->fixture->fObj = new \TYPO3\CMS\Backend\Form\FormEngine();
	}

	/**
	 * Tears down this test case.
	 */
	protected function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @param array $arguments
	 * @param array $expectedInlineStructure
	 * @param array $expectedInlineNames
	 * @dataProvider pushStructureFillsInlineStructureDataProvider
	 * @test
	 */
	public function pushStructureFillsInlineStructure(array $arguments, array $expectedInlineStructure, array $expectedInlineNames) {
		$this->fixture->inlineFirstPid = 'pageId';

		call_user_func_array(array($this->fixture, 'pushStructure'), $arguments);

		$this->assertEquals($expectedInlineStructure, $this->fixture->inlineStructure);
		$this->assertEquals($expectedInlineNames, $this->fixture->inlineNames);
	}

	public function pushStructureFillsInlineStructureDataProvider() {
		return array(
			'regular field' => array(
				array(
					'parentTable',
					'parentUid',
					'parentField'
				),
				array(
					'stable' => array(
						array(
							'table' => 'parentTable',
							'uid' => 'parentUid',
							'field' => 'parentField',
							'config' => array(),
							'localizationMode' => FALSE,
						),
					),
				),
				array(
					'form' => '[parentTable][parentUid][parentField]',
					'object' => 'data-pageId-parentTable-parentUid-parentField',
				)
			),
			'flexform field' => array(
				array(
					'parentTable',
					'parentUid',
					'parentField',
					array(),
					array(
						'itemFormElName' => 'data[parentTable][parentUid][parentField][data][sDEF][lDEF][grandParentFlexForm][vDEF]'
					)
				),
				array(
					'stable' => array(
						array(
							'table' => 'parentTable',
							'uid' => 'parentUid',
							'field' => 'parentField',
							'config' => array(),
							'localizationMode' => FALSE,
							'flexform' => array(
								'data', 'sDEF', 'lDEF', 'grandParentFlexForm', 'vDEF',
							),
						),
					),
				),
				array(
					'form' => '[parentTable][parentUid][parentField][data][sDEF][lDEF][grandParentFlexForm][vDEF]',
					'object' => 'data-pageId-parentTable-parentUid-parentField---data---sDEF---lDEF---grandParentFlexForm---vDEF',
				)
			),
		);
	}

	/**
	 * @param string $string
	 * @param array $expectedInlineStructure
	 * @param array $expectedInlineNames
	 * @dataProvider structureStringIsParsedDataProvider
	 * @test
	 */
	public function structureStringIsParsed($string, array $expectedInlineStructure, array $expectedInlineNames) {
		$this->fixture->parseStructureString($string, FALSE);

		$this->assertEquals('pageId', $this->fixture->inlineFirstPid);
		$this->assertEquals($expectedInlineStructure, $this->fixture->inlineStructure);
		$this->assertEquals($expectedInlineNames, $this->fixture->inlineNames);
	}

	/**
	 * @return array
	 */
	public function structureStringIsParsedDataProvider() {
		return array(
			'simple 1-level table structure' => array(
				'data-pageId-childTable',
				array(
					'unstable' => array(
						'table' => 'childTable',
					),
				),
				array()
			),
			'simple 1-level table-uid structure' => array(
				'data-pageId-childTable-childUid',
				array(
					'unstable' => array(
						'table' => 'childTable',
						'uid' => 'childUid',
					),
				),
				array()
			),
			'simple 1-level table-uid-field structure' => array(
				'data-pageId-childTable-childUid-childField',
				array(
					'unstable' => array(
						'table' => 'childTable',
						'uid' => 'childUid',
						'field' => 'childField',
					),
				),
				array(),
			),
			'simple 2-level table structure' => array(
				'data-pageId-parentTable-parentUid-parentField-childTable',
				array(
					'stable' => array(
						array(
							'table' => 'parentTable',
							'uid' => 'parentUid',
							'field' => 'parentField',
						),
					),
					'unstable' => array(
						'table' => 'childTable',
					),
				),
				array(
					'form' => '[parentTable][parentUid][parentField]',
					'object' => 'data-pageId-parentTable-parentUid-parentField',
				),
			),
			'simple 2-level table-uid structure' => array(
				'data-pageId-parentTable-parentUid-parentField-childTable-childUid',
				array(
					'stable' => array(
						array(
							'table' => 'parentTable',
							'uid' => 'parentUid',
							'field' => 'parentField',
						),
					),
					'unstable' => array(
						'table' => 'childTable',
						'uid' => 'childUid',
					),
				),
				array(
					'form' => '[parentTable][parentUid][parentField]',
					'object' => 'data-pageId-parentTable-parentUid-parentField',
				),
			),
			'simple 2-level table-uid-field structure' => array(
				'data-pageId-parentTable-parentUid-parentField-childTable-childUid-childField',
				array(
					'stable' => array(
						array(
							'table' => 'parentTable',
							'uid' => 'parentUid',
							'field' => 'parentField',
						),
					),
					'unstable' => array(
						'table' => 'childTable',
						'uid' => 'childUid',
						'field' => 'childField',
					),
				),
				array(
					'form' => '[parentTable][parentUid][parentField]',
					'object' => 'data-pageId-parentTable-parentUid-parentField',
				),
			),
			'simple 3-level table structure' => array(
				'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField-childTable',
				array(
					'stable' => array(
						array(
							'table' => 'grandParentTable',
							'uid' => 'grandParentUid',
							'field' => 'grandParentField',
						),
						array(
							'table' => 'parentTable',
							'uid' => 'parentUid',
							'field' => 'parentField',
						),
					),
					'unstable' => array(
						'table' => 'childTable',
					),
				),
				array(
					'form' => '[parentTable][parentUid][parentField]',
					'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField',
				),
			),
			'simple 3-level table-uid structure' => array(
				'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField-childTable-childUid',
				array(
					'stable' => array(
						array(
							'table' => 'grandParentTable',
							'uid' => 'grandParentUid',
							'field' => 'grandParentField',
						),
						array(
							'table' => 'parentTable',
							'uid' => 'parentUid',
							'field' => 'parentField',
						),
					),
					'unstable' => array(
						'table' => 'childTable',
						'uid' => 'childUid',
					),
				),
				array(
					'form' => '[parentTable][parentUid][parentField]',
					'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField',
				),
			),
			'simple 3-level table-uid-field structure' => array(
				'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField-childTable-childUid-childField',
				array(
					'stable' => array(
						array(
							'table' => 'grandParentTable',
							'uid' => 'grandParentUid',
							'field' => 'grandParentField',
						),
						array(
							'table' => 'parentTable',
							'uid' => 'parentUid',
							'field' => 'parentField',
						),
					),
					'unstable' => array(
						'table' => 'childTable',
						'uid' => 'childUid',
						'field' => 'childField',
					),
				),
				array(
					'form' => '[parentTable][parentUid][parentField]',
					'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField',
				),
			),
			'flexform 3-level table-uid structure' => array(
				'data-pageId-grandParentTable-grandParentUid-grandParentField---data---sDEF---lDEF---grandParentFlexForm---vDEF-parentTable-parentUid-parentField-childTable-childUid',
				array(
					'stable' => array(
						array(
							'table' => 'grandParentTable',
							'uid' => 'grandParentUid',
							'field' => 'grandParentField',
							'flexform' => array(
								'data', 'sDEF', 'lDEF', 'grandParentFlexForm', 'vDEF',
							),
						),
						array(
							'table' => 'parentTable',
							'uid' => 'parentUid',
							'field' => 'parentField',
						),
					),
					'unstable' => array(
						'table' => 'childTable',
						'uid' => 'childUid',
					),
				),
				array(
					'form' => '[parentTable][parentUid][parentField]',
					'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField---data---sDEF---lDEF---grandParentFlexForm---vDEF-parentTable-parentUid-parentField',
				),
			),
		);
	}
}
?>