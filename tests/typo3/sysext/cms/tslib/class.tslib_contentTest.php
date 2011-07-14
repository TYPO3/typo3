<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Oliver Hader <oliver@typo3.org>
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
 * Testcase for the "tslib_cObj" class in the TYPO3 Core.
 *
 * @package TYPO3
 * @subpackage tslib
 *
 * @author Oliver Hader <oliver@typo3.org>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tslib_contentTest extends tx_phpunit_testcase {
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
			'TYPO3_CONF_VARS' => $GLOBALS['TYPO3_CONF_VARS'],
		);

		$this->template = $this->getMock(
			't3lib_TStemplate', array('getFileName', 'linkData')
		);
		$this->tsfe = $this->getMock('tslib_fe', array(), array(), '', FALSE);
		$this->tsfe->tmpl = $this->template;
		$this->tsfe->config = array();
		$GLOBALS['TSFE'] = $this->tsfe;
		$GLOBALS['TSFE']->csConvObj = new t3lib_cs();
		$GLOBALS['TSFE']->renderCharset = 'utf-8';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['t3lib_cs_utils'] = 'mbstring';

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

		$GLOBALS['TSFE'] = NULL;

		unset($this->cObj, $this->tsfe, $this->template, $this->typoScriptImage);
	}


	////////////////////////
	// Utitility functions
	////////////////////////

	/**
	 * Converts the subject and the expected result into the target charset.
	 *
	 * @param string $charset the target charset
	 * @param string $subject the subject, will be modified
	 * @param string $expected the expected result, will be modified
	 */
	protected function handleCharset($charset, &$subject, &$expected) {
		$GLOBALS['TSFE']->renderCharset = $charset;
		$subject = $GLOBALS['TSFE']->csConvObj->conv($subject, 'iso-8859-1', $charset);
		$expected = $GLOBALS['TSFE']->csConvObj->conv($expected, 'iso-8859-1', $charset);
	}


	/////////////////////////////////////////////
	// Tests concerning the getImgResource hook
	/////////////////////////////////////////////

	/**
	 * @test
	 */
	public function getImgResourceHookGetsCalled() {
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
	 *
	 * @see getImgResourceHookGetsCalled
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


	/////////////////////////////////////////
	// Tests concerning getQueryArguments()
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function getQueryArgumentsExcludesParameters() {
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
	public function getQueryArgumentsExcludesGetParameters() {
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
	public function getQueryArgumentsOverrulesSingleParameter() {
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
	public function getQueryArgumentsOverrulesMultiDimensionalParameters() {
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
	public function getQueryArgumentsOverrulesMultiDimensionalForcedParameters() {
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


	//////////////////////////////
	// Tests concerning crop
	//////////////////////////////

	/**
	 * @test
	 */
	public function cropIsMultibyteSafe() {
		$this->assertEquals('бла', $this->cObj->crop('бла', '3|...'));
	}

	//////////////////////////////
	// Tests concerning cropHTML
	//////////////////////////////

	/**
	 * This is the data provider for the tests of crop and cropHTML below. It provides all combinations
	 * of charset, text type, and configuration options to be tested.
	 *
	 * @return array two-dimensional array with the second level like this:
	 *               0 => the settings for the crop function, for example "-58|..."
	 *               1 => the string to crop
	 *               2 => the expected cropped result
	 *               3 => the charset that will be set as renderCharset
	 *
	 * @see cropHtmlWithDataProvider
	 */
	public function cropHtmlDataProvider() {
		$plainText = 'Kasper Sk' . chr(229) . 'rh' . chr(248) .
			'j implemented the original version of the crop function.';
	 	$textWithMarkup = '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' .
	 		chr(229) . 'rh' . chr(248) . 'j</a>' .
	 		' implemented</strong> the original version of the crop function.';
		$textWithEntities = 'Kasper Sk&aring;rh&oslash;j implemented the; original ' .
			'version of the crop function.';

		$charsets = array('iso-8859-1', 'utf-8', 'ascii', 'big5');

		$data = array();
		foreach ($charsets as $charset) {
			$data = array_merge($data, array(
				$charset . ' plain text; 11|...' => array(
					'11|...', $plainText, 'Kasper Sk' . chr(229) . 'r...', $charset
				),
				$charset . ' plain text; -58|...' => array(
					'-58|...', $plainText, '...h' . chr(248) . 'j implemented the original version of the crop function.', $charset
				),
				$charset . ' plain text; 4|...|1' => array(
					'4|...|1', $plainText, 'Kasp...', $charset
				),
				$charset . ' plain text; 20|...|1' => array(
					'20|...|1', $plainText, 'Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j...', $charset
				),
				$charset . ' plain text; -5|...|1' => array(
					'-5|...|1', $plainText, '...tion.', $charset
				),
				$charset . ' plain text; -49|...|1' => array(
					'-49|...|1', $plainText, '...the original version of the crop function.', $charset
				),
				$charset . ' text with markup; 11|...' => array(
					'11|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'r...</a></strong>', $charset
				),
				$charset . ' text with markup; 13|...' => array(
					'13|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . '...</a></strong>', $charset
				),
				$charset . ' text with markup; 14|...' => array(
					'14|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>', $charset
				),
				$charset . ' text with markup; 15|...' => array(
					'15|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a> ...</strong>', $charset
				),
				$charset . ' text with markup; 29|...' => array(
					'29|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> th...', $charset
				),
				$charset . ' text with markup; -58|...' => array(
					'-58|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">...h' . chr(248) . 'j</a> implemented</strong> the original version of the crop function.', $charset
				),
				$charset . ' text with markup 4|...|1' => array(
					'4|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasp...</a></strong>', $charset
				),
				$charset . ' text with markup; 11|...|1' => array(
					'11|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper...</a></strong>', $charset
				),
				$charset . ' text with markup; 13|...|1' => array(
					'13|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper...</a></strong>', $charset
				),
				$charset . ' text with markup; 14|...|1' => array(
					'14|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>', $charset
				),
				$charset . ' text with markup; 15|...|1' => array(
					'15|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>', $charset
				),
				$charset . ' text with markup; 29|...|1' => array(
					'29|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong>...', $charset
				),
				$charset . ' text with markup; -66|...|1' => array(
					'-66|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">...Sk' . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> the original version of the crop function.', $charset
				),
				$charset . ' text with entities 9|...' => array(
					'9|...', $textWithEntities, 'Kasper Sk...', $charset
				),
				$charset . ' text with entities 10|...' => array(
					'10|...', $textWithEntities, 'Kasper Sk&aring;...', $charset
				),
				$charset . ' text with entities 11|...' => array(
					'11|...', $textWithEntities, 'Kasper Sk&aring;r...', $charset
				),
				$charset . ' text with entities 13|...' => array(
					'13|...', $textWithEntities, 'Kasper Sk&aring;rh&oslash;...', $charset
				),
				$charset . ' text with entities 14|...' => array(
					'14|...', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j...', $charset
				),
				$charset . ' text with entities 15|...' => array(
					'15|...', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j ...', $charset
				),
				$charset . ' text with entities 16|...' => array(
					'16|...', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j i...', $charset
				),
				$charset . ' text with entities -57|...' => array(
					'-57|...', $textWithEntities, '...j implemented the; original version of the crop function.', $charset
				),
				$charset . ' text with entities -58|...' => array(
					'-58|...', $textWithEntities, '...&oslash;j implemented the; original version of the crop function.', $charset
				),
				$charset . ' text with entities -59|...' => array(
					'-59|...', $textWithEntities, '...h&oslash;j implemented the; original version of the crop function.', $charset
				),
				$charset . ' text with entities 4|...|1' => array(
					'4|...|1', $textWithEntities, 'Kasp...', $charset
				),
				$charset . ' text with entities 9|...|1' => array(
					'9|...|1', $textWithEntities, 'Kasper...', $charset
				),
				$charset . ' text with entities 10|...|1' => array(
					'10|...|1', $textWithEntities, 'Kasper...', $charset
				),
				$charset . ' text with entities 11|...|1' => array(
					'11|...|1', $textWithEntities, 'Kasper...', $charset
				),
				$charset . ' text with entities 13|...|1' => array(
					'13|...|1', $textWithEntities, 'Kasper...', $charset
				),
				$charset . ' text with entities 14|...|1' => array(
					'14|...|1', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j...', $charset
				),
				$charset . ' text with entities 15|...|1' => array(
					'15|...|1', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j...', $charset
				),
				$charset . ' text with entities 16|...|1' => array(
					'16|...|1', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j...', $charset
				),
				$charset . ' text with entities -57|...|1' => array(
					'-57|...|1', $textWithEntities, '...implemented the; original version of the crop function.', $charset
				),
				$charset . ' text with entities -58|...|1' => array(
					'-58|...|1', $textWithEntities, '...implemented the; original version of the crop function.', $charset
				),
				$charset . ' text with entities -59|...|1' => array(
					'-59|...|1', $textWithEntities, '...implemented the; original version of the crop function.', $charset
				),
			));
		}
		return $data;
	}

	/**
	 * Checks if stdWrap.cropHTML works with plain text cropping from left
	 *
	 * @test
	 *
	 * @dataProvider cropHtmlDataProvider
	 *
	 * @param string $settings
	 *        the settings for the crop function, for example "-58|..."
	 * @param string $subject the string to crop
	 * @param string $expected the expected cropped result
	 * @param string $charset the charset that will be set as renderCharset
	 */
	public function cropHtmlWithDataProvider($settings, $subject, $expected, $charset) {
		$this->handleCharset($charset, $subject, $expected);

		$this->assertEquals(
			$expected,
			$this->cObj->cropHTML($subject, $settings),
			'cropHTML failed with settings: "' . $settings . '" and charset "' . $charset . '"'
		);
	}

	/**
	 * Checks if stdWrap.cropHTML works with a complex content with many tags. Currently cropHTML
	 * counts multiple invisible characters not as one (as the browser will output the content).
	 *
	 * @test
	 */
	public function cropHtmlWorksWithComplexContent() {
		$GLOBALS['TSFE']->renderCharset = 'iso-8859-1';
		$subject = '
<h1>Blog Example</h1>
<hr>
<div class="csc-header csc-header-n1">
	<h2 class="csc-firstHeader">Welcome to Blog #1</h2>
</div>
<p class="bodytext">
	A blog about TYPO3 extension development. In order to start blogging, read the <a href="#">Help section</a>. If you have any further questions, feel free to contact the administrator John Doe (<a href="mailto:john.doe@example.com">john.doe@example.com)</a>.
</p>
<div class="tx-blogexample-list-container">
	<p class="bodytext">
		Below are the most recent posts:
	</p>
	<ul>
		<li>
			<h3>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog]=&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=003b0131ed">The Post #1</a>
			</h3>
			<p class="bodytext">
				Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut...
			</p>
			<p class="metadata">
				Published on 26.08.2009 by Jochen Rau
			</p>
			<p>
				Tags: [MVC]&nbsp;[Domain Driven Design]&nbsp;<br>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=f982643bc3">read more &gt;&gt;</a><br>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=edit&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=5b481bc8f0">Edit</a>&nbsp;<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=delete&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=4e52879656">Delete</a>
			</p>
		</li>
	</ul>
	<p>
		<a href="index.php?id=99&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=new&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=2718a4b1a0">Create a new Post</a>
	</p>
</div>
<hr>
<p>
	? TYPO3 Association
</p>
';

		$result = $this->cObj->cropHTML($subject, '300');
		$expected = '
<h1>Blog Example</h1>
<hr>
<div class="csc-header csc-header-n1">
	<h2 class="csc-firstHeader">Welcome to Blog #1</h2>
</div>
<p class="bodytext">
	A blog about TYPO3 extension development. In order to start blogging, read the <a href="#">Help section</a>. If you have any further questions, feel free to contact the administrator John Doe (<a href="mailto:john.doe@example.com">john.doe@example.com)</a>.
</p>
<div class="tx-blogexample-list-container">
	<p class="bodytext">
		Below are the most recent posts:
	</p>
	<ul>
		<li>
			<h3>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog]=&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=003b0131ed">The Pos</a></h3></li></ul></div>';
		$this->assertEquals($expected, $result);

		$result = $this->cObj->cropHTML($subject, '-100');
		$expected = '<div class="tx-blogexample-list-container"><ul><li><p>Design]&nbsp;<br>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=f982643bc3">read more &gt;&gt;</a><br>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=edit&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=5b481bc8f0">Edit</a>&nbsp;<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=delete&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=4e52879656">Delete</a>
			</p>
		</li>
	</ul>
	<p>
		<a href="index.php?id=99&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=new&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=2718a4b1a0">Create a new Post</a>
	</p>
</div>
<hr>
<p>
	? TYPO3 Association
</p>
';
		$this->assertEquals(
			$expected,
			$result
		);
	}

	/**
	 * @return array
	 */
	public function stdWrap_roundDataProvider() {
		return array(
			'rounding off without any configuration' => array(
				1.123456789,
				array(
				),
				1
			),
			'rounding up without any configuration' => array(
				1.523456789,
				array(
				),
				2
			),
			'regular rounding (off) to two decimals' => array(
				0.123456789,
				array(
					'decimals' => 2
				),
				0.12
			),
			'regular rounding (up) to two decimals' => array(
				0.1256789,
				array(
					'decimals' => 2
				),
				0.13
			),
			'rounding up to integer with type ceil' => array(
				0.123456789,
				array(
					'roundType' => 'ceil'
				),
				1
			),
			'rounding down to integer with type floor' => array(
				2.3481,
				array(
					'roundType' => 'floor'
				),
				2
			)
		);
	}

	/**
	 * Test for the stdWrap function "round"
	 *
	 * @param float $float
	 * @param array $conf
	 * @param float $expected
	 * @return void
	 *
	 * @dataProvider stdWrap_roundDataProvider
	 * @test
	 */
	public function stdWrap_round($float, $conf, $expected) {
		$conf = array(
			'round.' => $conf
		);
		$result = $this->cObj->stdWrap_round($float, $conf);
		$this->assertEquals($expected, $result);
	}

	/**
	 * Data provider for the hash test
	 *
	 * @return array multi-dimensional array with the second level like this:
	 *               0 => the plain text
	 *               1 => the conf array for the hash stdWrap function
	 *               2 => the expected result
	 *
	 * @see hash
	 */
	public function hashDataProvider() {
		$data = array(
			'testing md5' => array(
				'joh316',
				array(
					'hash' => 'md5'
				),
				'bacb98acf97e0b6112b1d1b650b84971'
			),
			'testing sha1' => array(
				'joh316',
				array(
					'hash' => 'sha1'
				),
				'063b3d108bed9f88fa618c6046de0dccadcf3158'
			),
			'testing non-existing hashing algorithm' => array(
				'joh316',
				array(
					'hash' => 'non-existing'
				),
				''
			),
			'testing stdWrap capability' => array(
				'joh316',
				array(
					'hash.' => array(
						'cObject' => 'TEXT',
						'cObject.' => array(
							'value' => 'md5'
						)
					)
				),
				'bacb98acf97e0b6112b1d1b650b84971'
			)
		);
		return $data;
	}

	/**
	 * Test for the stdWrap function "hash"
	 *
	 * @param string $text
	 * @param array $conf
	 * @param string $expected
	 * @return void
	 *
	 * @dataProvider hashDataProvider
	 * @test
	 */
	public function stdWrap_hash($text, array $conf, $expected) {
		$result = $this->cObj->stdWrap_hash($text, $conf);
		$this->assertEquals($expected, $result);
	}

	/**
	 * Data provider for the numberFormat test
	 *
	 * @return array multi-dimensional array with the second level like this:
	 *               0 => the input float number
	 *               1 => the conf array for the numberFormat stdWrap function
	 *               2 => the expected result
	 *
	 * @see numberFormat
	 */
	public function numberFormatDataProvider() {
		$data = array(
			'testing decimals' => array(
				0.8,
				array(
					'decimals' => 2
				),
				'0.80'
			),
			'testing dec_point' => array(
				0.8,
				array(
					'decimals' => 1,
					'dec_point' => ','
				),
				'0,8'
			),
			'testing thousands_sep' => array(
				999.99,
				array(
					'decimals' => 0,
					'thousands_sep.' => array(
						'char' => 46
					)
				),
				'1.000'
			),
			'testing mixture' => array(
				1281731.45,
				array(
					'decimals' => 1,
					'dec_point.' => array(
						'char' => 44
					),
					'thousands_sep.' => array(
						'char' => 46
					)
				),
				'1.281.731,5'
			)
		);
		return $data;
	}

	/**
	 * Check if stdWrap.numberFormat and all of its properties work properly
	 *
	 * @dataProvider numberFormatDataProvider
	 *
	 * @test
	 */
	public function numberFormat($float, $formatConf, $expected) {
		$result = $this->cObj->numberFormat($float, $formatConf);
		$this->assertEquals($expected, $result);
	}
}
?>