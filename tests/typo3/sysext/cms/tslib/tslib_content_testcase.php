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
	 * @var	array
	 */
	private $backupGlobalVariables;

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
		$this->backupGlobalVariables = array(
			'_GET' => $_GET,
			'_POST' => $_POST,
			'_SERVER' => $_SERVER,
		);

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
		foreach ($this->backupGlobalVariables as $key => $data) {
			$GLOBALS[$key] = $data;
		}

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

	//////////////////////////
	// Tests concerning getQueryArguments()
	//////////////////////////

	/**
	 * @test
	 */
	public function doesGetQueryArgumentsCorrectlyExcludeParameters() {
		$_SERVER['QUERY_STRING'] =
			'key1=value1' .
			'&key2=value2' .
			'&key3[key31]=value31' .
			'&key3[key32][key321]=value321' .
			'&key3[key32][key322]=value322';

		$getQueryArgumentsConfiguration = array();
		$getQueryArgumentsConfiguration['exclude'] = array();
		$getQueryArgumentsConfiguration['exclude'][] = 'key1';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key31]';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key321]';
		$getQueryArgumentsConfiguration['exclude'] = implode(',', $getQueryArgumentsConfiguration['exclude']);

		$expectedResult = '&key2=value2&key3[key32][key322]=value322';
		$actualResult = $this->cObj->getQueryArguments($getQueryArgumentsConfiguration);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function doesGetQueryArgumentsCorrectlyExcludeGETParameters() {
		$_GET = array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => array(
				'key31' => 'value31',
				'key32' => array(
					'key321' => 'value321',
					'key322' => 'value322',
				),
			),
		);

		$getQueryArgumentsConfiguration = array();
		$getQueryArgumentsConfiguration['method'] = 'GET';
		$getQueryArgumentsConfiguration['exclude'] = array();
		$getQueryArgumentsConfiguration['exclude'][] = 'key1';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key31]';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key321]';
		$getQueryArgumentsConfiguration['exclude'] = implode(',', $getQueryArgumentsConfiguration['exclude']);

		$expectedResult = '&key2=value2&key3[key32][key322]=value322';
		$actualResult = $this->cObj->getQueryArguments($getQueryArgumentsConfiguration);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function doesGetQueryArgumentsCorrectlyOverruleSingleParameter() {
		$_SERVER['QUERY_STRING'] = 'key1=value1';

		$getQueryArgumentsConfiguration = array();

		$overruleArguments = array(
				// Should be overriden
			'key1' => 'value1Overruled',
				// Shouldn't be set: Parameter doesn't exist in source array and is not forced
			'key2' => 'value2Overruled',
		);

		$expectedResult = '&key1=value1Overruled';
		$actualResult = $this->cObj->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function doesGetQueryArgumentsCorrectlyOverruleMultiDimensionalParameters() {
		$_POST = array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => array(
				'key31' => 'value31',
				'key32' => array(
					'key321' => 'value321',
					'key322' => 'value322',
				),
			),
		);

		$getQueryArgumentsConfiguration = array();
		$getQueryArgumentsConfiguration['method'] = 'POST';
		$getQueryArgumentsConfiguration['exclude'] = array();
		$getQueryArgumentsConfiguration['exclude'][] = 'key1';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key31]';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key321]';
		$getQueryArgumentsConfiguration['exclude'] = implode(',', $getQueryArgumentsConfiguration['exclude']);

		$overruleArguments = array(
				// Should be overriden
			'key2' => 'value2Overruled',
			'key3' => array(
				'key32' => array(
						// Shouldn't be set: Parameter is excluded and not forced
					'key321' => 'value321Overruled',
						// Should be overriden: Parameter is not excluded
					'key322' => 'value322Overruled',
						// Shouldn't be set: Parameter doesn't exist in source array and is not forced
					'key323' => 'value323Overruled',
				),
			),
		);

		$expectedResult = '&key2=value2Overruled&key3[key32][key322]=value322Overruled';
		$actualResult = $this->cObj->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function doesGetQueryArgumentsCorrectlyOverruleMultiDimensionalForcedParameters() {
		$_SERVER['QUERY_STRING'] =
			'key1=value1' .
			'&key2=value2' .
			'&key3[key31]=value31' .
			'&key3[key32][key321]=value321' .
			'&key3[key32][key322]=value322';

		$_POST = array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => array(
				'key31' => 'value31',
				'key32' => array(
					'key321' => 'value321',
					'key322' => 'value322',
				),
			),
		);

		$getQueryArgumentsConfiguration = array();
		$getQueryArgumentsConfiguration['exclude'] = array();
		$getQueryArgumentsConfiguration['exclude'][] = 'key1';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key31]';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key321]';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key322]';
		$getQueryArgumentsConfiguration['exclude'] = implode(',', $getQueryArgumentsConfiguration['exclude']);

		$overruleArguments = array(
				// Should be overriden
			'key2' => 'value2Overruled',
			'key3' => array(
				'key32' => array(
						// Should be set: Parameter is excluded but forced
					'key321' => 'value321Overruled',
						// Should be set: Parameter doesn't exist in source array but is forced
					'key323' => 'value323Overruled',
				),
			),
		);

		$expectedResult = '&key2=value2Overruled&key3[key32][key321]=value321Overruled&key3[key32][key323]=value323Overruled';
		$actualResult = $this->cObj->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments, TRUE);
		$this->assertEquals($expectedResult, $actualResult);

		$getQueryArgumentsConfiguration['method'] = 'POST';
		$actualResult = $this->cObj->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments, TRUE);
		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>