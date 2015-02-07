<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\Element;

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
use TYPO3\CMS\Backend\Form\Element\InlineElement;
use TYPO3\CMS\Backend\Form\FormEngine;

/**
 * Test case
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class InlineElementTest extends UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Backend\Form\Element\InlineElement
	 */
	protected $subject;

	/**
	 * Sets up this test case.
	 */
	protected function setUp() {
		/** @var InlineElement|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface subject */
		$this->subject = $this->getAccessibleMock(InlineElement::class, array('dummy'));
		$this->subject->_set('formEngine', $this->getMock(FormEngine::class, array(), array(), '', FALSE));
	}

	/**
	 * @param array $arguments
	 * @param array $expectedInlineStructure
	 * @param array $expectedInlineNames
	 * @dataProvider pushStructureFillsInlineStructureDataProvider
	 * @test
	 */
	public function pushStructureFillsInlineStructure(array $arguments, array $expectedInlineStructure, array $expectedInlineNames) {
		$this->subject->inlineFirstPid = 'pageId';

		call_user_func_array(array($this->subject, 'pushStructure'), $arguments);

		$this->assertEquals($expectedInlineStructure, $this->subject->inlineStructure);
		$this->assertEquals($expectedInlineNames, $this->subject->inlineNames);
	}

	/**
	 * Provide structure for DataProvider tests
	 *
	 * @return array
	 */
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
		$this->subject->parseStructureString($string, FALSE);

		$this->assertEquals('pageId', $this->subject->inlineFirstPid);
		$this->assertEquals($expectedInlineStructure, $this->subject->inlineStructure);
		$this->assertEquals($expectedInlineNames, $this->subject->inlineNames);
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

	/**
	 * Checks if the given filetype may be uploaded without *ANY* limit to
	 * filetypes being given
	 *
	 * @test
	 */
	public function checkFileTypeAccessForFieldForFieldNoFiletypesReturnsTrue(){
		$selectorData = array();
		$fileData['extension'] = 'png';
		$mockObject = $this->getAccessibleMock(InlineElement::class, array('dummy'));
		$mayUploadFile = $mockObject->_call('checkFileTypeAccessForField', $selectorData, $fileData);
		$this->assertTrue($mayUploadFile);
	}

	/**
	 * Checks if the given filetype may be uploaded and the given filetype is *NOT*
	 * in the list of allowed files
	 * @test
	 */
	public function checkFileTypeAccessForFieldFiletypesSetRecordTypeNotInListReturnsFalse(){
		$selectorData['PA']['fieldConf']['config']['appearance']['elementBrowserAllowed'] = 'doc, png, jpg, tiff';
		$fileData['extension'] = 'php';
		$mockObject = $this->getAccessibleMock(InlineElement::class, array('dummy'));
		$mayUploadFile = $mockObject->_call('checkFileTypeAccessForField', $selectorData, $fileData);
		$this->assertFalse($mayUploadFile);
	}

	/**
	 * Checks if the given filetype may be uploaded and the given filetype *is*
	 * in the list of allowed files
	 * @test
	 */
	public function checkFileTypeAccessForFieldFiletypesSetRecordTypeInListReturnsTrue(){
		$selectorData['PA']['fieldConf']['config']['appearance']['elementBrowserAllowed'] = 'doc, png, jpg, tiff';
		$fileData['extension'] = 'png';
		$mockObject = $this->getAccessibleMock(InlineElement::class, array('dummy'));
		$mayUploadFile = $mockObject->_call('checkFileTypeAccessForField', $selectorData, $fileData);
		$this->assertTrue($mayUploadFile);
	}
}
