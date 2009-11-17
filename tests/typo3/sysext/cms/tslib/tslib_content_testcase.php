<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Oliver Hader <oliver@typo3.org>
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
 * Testcase for the "tslib_cObj" in the TYPO3 Core.
 *
 * @package TYPO3
 * @subpackage tslib
 *
 * @author Oliver Hader <oliver@typo3.org>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 *
 */
class tslib_content_testcase extends tx_phpunit_testcase {
	/**
	 * @var	boolean
	 */
	protected $backupGlobals = true;

	/**
	 * @var	tslib_cObj
	 */
	private $cObj;

	/**
	 * @var	tslib_fe
	 */
	private $tsfe;

	/**
	 * @var	t3lib_TStemplate
	 */
	private $template;

	/**
	 * @var	array
	 */
	private $typoScriptImage;

	public function setUp() {
		$this->template = $this->getMock(
			't3lib_TStemplate', array('getFileName', 'linkData')
		);
		$this->tsfe = $this->getMock('tslib_fe', array(), array(), '', false);
		$this->tsfe->tmpl = $this->template;
		$this->tsfe->config = array();
		$GLOBALS['TSFE'] = $this->tsfe;

		$className = 'tslib_cObj_' . uniqid('test');
		eval('
			class ' . $className . ' extends tslib_cObj {
				public $stdWrapHookObjects = array();
				public $getImgResourceHookObjects;
			}
		');

		$this->cObj = new $className();
		$this->cObj->start(array(), 'tt_content');

		$this->typoScriptImage = array(
			'file' => 'typo3/clear.gif',
		);
	}

	public function tearDown() {
		$GLOBALS['TSFE'] = null;

		unset($this->cObj, $this->tsfe, $this->template,$this->typoScriptImage);
	}

	/**
	 * Tests whether the getImgResource hook is called correctly.
	 *
	 * @test
	 */
	public function isGetImgResourceHookCalled() {
		$this->template->expects($this->atLeastOnce())->method('getFileName')
			->with('typo3/clear.gif')->will($this->returnValue('typo3/clear.gif'));

		$className = uniqid('tx_coretest');
		$getImgResourceHookMock = $this->getMock(
			'tslib_cObj_getImgResourceHook',
			array('getImgResourcePostProcess'),
			array(),
			$className
		);

		$getImgResourceHookMock->expects($this->once())->method('getImgResourcePostProcess')
			->will($this->returnCallback(array($this, 'isGetImgResourceHookCalledCallback')));
		$this->cObj->getImgResourceHookObjects = array($getImgResourceHookMock);

		$this->cObj->IMAGE($this->typoScriptImage);
	}

	/**
	 * Handles the arguments that have been sent to the getImgResource hook.
	 *
	 * @return	array
	 */
	public function isGetImgResourceHookCalledCallback() {
		list($file, $fileArray, $imageResource, $parent) = func_get_args();

		$this->assertEquals('typo3/clear.gif', $file);
		$this->assertEquals('typo3/clear.gif', $imageResource['origFile']);
		$this->assertTrue(is_array($fileArray));
		$this->assertTrue($parent instanceof tslib_cObj);

		return $imageResource;
	}


	//////////////////////////
	// Tests concerning FORM
	//////////////////////////

	/**
	 * @test
	 */
	public function formWithSecureFormMailEnabledDoesNotContainRecipientField() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail'] = TRUE;

		$this->assertNotContains(
			'name="recipient',
			$this->cObj->FORM(
				array('recipient' => 'foo@bar.com', 'recipient.' => array()),
				array()
			)
		);
	}

	/**
	 * @test
	 */
	public function formWithSecureFormMailDisabledDoesNotContainRecipientField() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail'] = FALSE;

		$this->assertContains(
			'name="recipient',
			$this->cObj->FORM(
				array('recipient' => 'foo@bar.com', 'recipient.' => array()),
				array()
			)
		);
	}
}
?>