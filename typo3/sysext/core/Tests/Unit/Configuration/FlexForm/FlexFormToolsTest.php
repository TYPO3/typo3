<?php
namespace TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm;

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
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;

/**
 * Test case
 */
class FlexFormToolsTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function traverseFlexFormXmlDataRecurseDoesNotFailOnNotExistingField() {
		$dataStruct = array(
			'dummy_field' => array(
				'TCEforms' => array(
					'config' => array(),
				),
			),
		);
		$pA = array(
			'vKeys' => array('ES'),
			'callBackMethod_value' => 'dummy',
		);
		$editData = '';
		/** @var \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools', array('executeCallBackMethod'));
		$subject->expects($this->never())->method('executeCallBackMethod');
		$subject->traverseFlexFormXMLData_recurse($dataStruct, $editData, $pA);
	}

	/**
	 * @test
	 */
	public function traverseFlexFormXmlDataRecurseDoesNotFailOnNotExistingArrayField() {
		$dataStruct = array(
			'dummy_field' => array(
				'type' => 'array',
				'el' => 'field_not_in_data',
			),
		);
		$pA = array(
			'vKeys' => array('ES'),
			'callBackMethod_value' => 'dummy',
		);
		$editData = array(
			'field' => array(
				'el' => 'dummy',
			),
		);
		$editData2 = '';
		/** @var \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
		$this->assertEquals(
			$subject->traverseFlexFormXMLData_recurse($dataStruct, $editData, $pA),
			$subject->traverseFlexFormXMLData_recurse($dataStruct, $editData2, $pA)
		);
	}

}
