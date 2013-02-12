<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Oliver Hader <oliver@typo3.org>
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
 * Testcase for TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
 *
 * @author Oliver Hader <oliver@typo3.org>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ContentObjectRendererTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $cObj = NULL;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $tsfe = NULL;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\TypoScript\TemplateService
	 */
	protected $template = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->template = $this->getMock('TYPO3\\CMS\\Core\\TypoScript\\TemplateService', array('getFileName', 'linkData'));
		$this->tsfe = $this->getAccessibleMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array('dummy'), array(), '', FALSE);
		$this->tsfe->tmpl = $this->template;
		$this->tsfe->config = array();
		$this->tsfe->page = array();
		$sysPageMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$this->tsfe->sys_page = $sysPageMock;
		$GLOBALS['TSFE'] = $this->tsfe;
		$GLOBALS['TSFE']->csConvObj = new \TYPO3\CMS\Core\Charset\CharsetConverter();
		$GLOBALS['TSFE']->renderCharset = 'utf-8';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['TYPO3\\CMS\\Core\\Charset\\CharsetConverter_utils'] = 'mbstring';
		$this->cObj = $this->getAccessibleMock('\\TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer', array('dummy'));
		$this->cObj->start(array(), 'tt_content');
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
		$this->template->expects($this->atLeastOnce())->method('getFileName')->with('typo3/clear.gif')->will($this->returnValue('typo3/clear.gif'));
		$className = uniqid('tx_coretest');
		$getImgResourceHookMock = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectGetImageResourceHookInterface', array('getImgResourcePostProcess'), array(), $className);
		$getImgResourceHookMock->expects($this->once())->method('getImgResourcePostProcess')->will($this->returnCallback(array($this, 'isGetImgResourceHookCalledCallback')));
		$getImgResourceHookObjects = array($getImgResourceHookMock);
		$this->cObj->_setRef('getImgResourceHookObjects', $getImgResourceHookObjects);
		$this->cObj->IMAGE(array('file' => 'typo3/clear.gif'));
	}

	/**
	 * Handles the arguments that have been sent to the getImgResource hook.
	 *
	 * @return 	array
	 * @see getImgResourceHookGetsCalled
	 */
	public function isGetImgResourceHookCalledCallback() {
		list($file, $fileArray, $imageResource, $parent) = func_get_args();
		$this->assertEquals('typo3/clear.gif', $file);
		$this->assertEquals('typo3/clear.gif', $imageResource['origFile']);
		$this->assertTrue(is_array($fileArray));
		$this->assertTrue($parent instanceof \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer);
		return $imageResource;
	}


	/*************************
	 * Tests concerning getContentObject
	 ************************/
	public function getContentObjectValidContentObjectsDataProvider() {
		return array(
			array('TEXT', 'Text'),
			array('CASE', 'Case'),
			array('CLEARGIF', 'ClearGif'),
			array('COBJ_ARRAY', 'ContentObjectArray'),
			array('COA', 'ContentObjectArray'),
			array('COA_INT', 'ContentObjectArrayInternal'),
			array('USER', 'User'),
			array('USER_INT', 'UserInternal'),
			array('FILE', 'File'),
			array('FILES', 'Files'),
			array('IMAGE', 'Image'),
			array('IMG_RESOURCE', 'ImageResource'),
			array('IMGTEXT', 'ImageText'),
			array('CONTENT', 'Content'),
			array('RECORDS', 'Records'),
			array('HMENU', 'HierarchicalMenu'),
			array('CTABLE', 'ContentTable'),
			array('OTABLE', 'OffsetTable'),
			array('COLUMNS', 'Columns'),
			array('HRULER', 'HorizontalRuler'),
			array('CASEFUNC', 'Case'),
			array('LOAD_REGISTER', 'LoadRegister'),
			array('RESTORE_REGISTER', 'RestoreRegister'),
			array('FORM', 'Form'),
			array('SEARCHRESULT', 'SearchResult'),
			array('TEMPLATE', 'Template'),
			array('FLUIDTEMPLATE', 'FluidTemplate'),
			array('MULTIMEDIA', 'Multimedia'),
			array('MEDIA', 'Media'),
			array('SWFOBJECT', 'ShockwaveFlashObject'),
			array('FLOWPLAYER', 'FlowPlayer'),
			array('QTOBJECT', 'QuicktimeObject'),
			array('SVG', 'ScalableVectorGraphics'),
			array('EDITPANEL', 'EditPanel'),
		);
	}

	/**
	 * @test
	 * @dataProvider getContentObjectValidContentObjectsDataProvider
	 * @param string $name TypoScript name of content object
	 * @param string $className Expected class name
	 */
	public function getContentObjectUsesExistingInstanceOfRequestedObjectType($name, $className) {
		$fullClassName = 'TYPO3\\CMS\\Frontend\\ContentObject\\' . $className . 'ContentObject';
		$contentObjectInstance = $this->getMock($fullClassName, array(), array(), '', FALSE);
		$this->cObj->_set('contentObjects', array($className => $contentObjectInstance));
		$this->assertSame($contentObjectInstance, $this->cObj->getContentObject($name));
	}

	/**
	 * @test
	 * @dataProvider getContentObjectValidContentObjectsDataProvider
	 * @param string $name TypoScript name of content object
	 * @param string $className Expected class name
	 */
	public function getContentObjectCallsMakeInstanceForNewContentObjectInstance($name, $className) {
		$fullClassName = 'TYPO3\\CMS\\Frontend\\ContentObject\\' . $className . 'ContentObject';
		$contentObjectInstance = $this->getMock($fullClassName, array(), array(), '', FALSE);
		\TYPO3\CMS\Core\Utility\GeneralUtility::addInstance($fullClassName, $contentObjectInstance);
		$this->assertSame($contentObjectInstance, $this->cObj->getContentObject($name));
	}

	//////////////////////////
	// Tests concerning FORM
	//////////////////////////
	/**
	 * @test
	 */
	public function formWithSecureFormMailEnabledDoesNotContainRecipientField() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail'] = TRUE;
		$this->assertNotContains('name="recipient', $this->cObj->FORM(array('recipient' => 'foo@bar.com', 'recipient.' => array()), array()));
	}

	/**
	 * @test
	 */
	public function formWithSecureFormMailDisabledDoesNotContainRecipientField() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail'] = FALSE;
		$this->assertContains('name="recipient', $this->cObj->FORM(array('recipient' => 'foo@bar.com', 'recipient.' => array()), array()));
	}

	/////////////////////////////////////////
	// Tests concerning getQueryArguments()
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function getQueryArgumentsExcludesParameters() {
		$_SERVER['QUERY_STRING'] = 'key1=value1' . '&key2=value2' . '&key3[key31]=value31' . '&key3[key32][key321]=value321' . '&key3[key32][key322]=value322';
		$getQueryArgumentsConfiguration = array();
		$getQueryArgumentsConfiguration['exclude'] = array();
		$getQueryArgumentsConfiguration['exclude'][] = 'key1';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key31]';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key321]';
		$getQueryArgumentsConfiguration['exclude'] = implode(',', $getQueryArgumentsConfiguration['exclude']);
		$expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key2=value2&key3[key32][key322]=value322');
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
					'key322' => 'value322'
				)
			)
		);
		$getQueryArgumentsConfiguration = array();
		$getQueryArgumentsConfiguration['method'] = 'GET';
		$getQueryArgumentsConfiguration['exclude'] = array();
		$getQueryArgumentsConfiguration['exclude'][] = 'key1';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key31]';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key321]';
		$getQueryArgumentsConfiguration['exclude'] = implode(',', $getQueryArgumentsConfiguration['exclude']);
		$expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key2=value2&key3[key32][key322]=value322');
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
			'key2' => 'value2Overruled'
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
					'key322' => 'value322'
				)
			)
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
					'key323' => 'value323Overruled'
				)
			)
		);
		$expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key2=value2Overruled&key3[key32][key322]=value322Overruled');
		$actualResult = $this->cObj->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getQueryArgumentsOverrulesMultiDimensionalForcedParameters() {
		$_SERVER['QUERY_STRING'] = 'key1=value1' . '&key2=value2' . '&key3[key31]=value31' . '&key3[key32][key321]=value321' . '&key3[key32][key322]=value322';
		$_POST = array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => array(
				'key31' => 'value31',
				'key32' => array(
					'key321' => 'value321',
					'key322' => 'value322'
				)
			)
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
					'key323' => 'value323Overruled'
				)
			)
		);
		$expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key2=value2Overruled&key3[key32][key321]=value321Overruled&key3[key32][key323]=value323Overruled');
		$actualResult = $this->cObj->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments, TRUE);
		$this->assertEquals($expectedResult, $actualResult);
		$getQueryArgumentsConfiguration['method'] = 'POST';
		$actualResult = $this->cObj->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments, TRUE);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * Encodes square brackets in URL.
	 *
	 * @param string $string
	 * @return string
	 */
	private function rawUrlEncodeSquareBracketsInUrl($string) {
		return str_replace(array('[', ']'), array('%5B', '%5D'), $string);
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
	 * @see cropHtmlWithDataProvider
	 */
	public function cropHtmlDataProvider() {
		$plainText = 'Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j implemented the original version of the crop function.';
		$textWithMarkup = '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a>' . ' implemented</strong> the original version of the crop function.';
		$textWithEntities = 'Kasper Sk&aring;rh&oslash;j implemented the; original ' . 'version of the crop function.';
		$charsets = array('iso-8859-1', 'utf-8', 'ascii', 'big5');
		$data = array();
		foreach ($charsets as $charset) {
			$data = array_merge($data, array(
				$charset . ' plain text; 11|...' => array(
					'11|...',
					$plainText,
					'Kasper Sk' . chr(229) . 'r...',
					$charset
				),
				$charset . ' plain text; -58|...' => array(
					'-58|...',
					$plainText,
					'...h' . chr(248) . 'j implemented the original version of the crop function.',
					$charset
				),
				$charset . ' plain text; 4|...|1' => array(
					'4|...|1',
					$plainText,
					'Kasp...',
					$charset
				),
				$charset . ' plain text; 20|...|1' => array(
					'20|...|1',
					$plainText,
					'Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j...',
					$charset
				),
				$charset . ' plain text; -5|...|1' => array(
					'-5|...|1',
					$plainText,
					'...tion.',
					$charset
				),
				$charset . ' plain text; -49|...|1' => array(
					'-49|...|1',
					$plainText,
					'...the original version of the crop function.',
					$charset
				),
				$charset . ' text with markup; 11|...' => array(
					'11|...',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'r...</a></strong>',
					$charset
				),
				$charset . ' text with markup; 13|...' => array(
					'13|...',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . '...</a></strong>',
					$charset
				),
				$charset . ' text with markup; 14|...' => array(
					'14|...',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
					$charset
				),
				$charset . ' text with markup; 15|...' => array(
					'15|...',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a> ...</strong>',
					$charset
				),
				$charset . ' text with markup; 29|...' => array(
					'29|...',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> th...',
					$charset
				),
				$charset . ' text with markup; -58|...' => array(
					'-58|...',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">...h' . chr(248) . 'j</a> implemented</strong> the original version of the crop function.',
					$charset
				),
				$charset . ' text with markup 4|...|1' => array(
					'4|...|1',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">Kasp...</a></strong>',
					$charset
				),
				$charset . ' text with markup; 11|...|1' => array(
					'11|...|1',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">Kasper...</a></strong>',
					$charset
				),
				$charset . ' text with markup; 13|...|1' => array(
					'13|...|1',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">Kasper...</a></strong>',
					$charset
				),
				$charset . ' text with markup; 14|...|1' => array(
					'14|...|1',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
					$charset
				),
				$charset . ' text with markup; 15|...|1' => array(
					'15|...|1',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
					$charset
				),
				$charset . ' text with markup; 29|...|1' => array(
					'29|...|1',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong>...',
					$charset
				),
				$charset . ' text with markup; -66|...|1' => array(
					'-66|...|1',
					$textWithMarkup,
					'<strong><a href="mailto:kasper@typo3.org">...Sk' . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> the original version of the crop function.',
					$charset
				),
				$charset . ' text with entities 9|...' => array(
					'9|...',
					$textWithEntities,
					'Kasper Sk...',
					$charset
				),
				$charset . ' text with entities 10|...' => array(
					'10|...',
					$textWithEntities,
					'Kasper Sk&aring;...',
					$charset
				),
				$charset . ' text with entities 11|...' => array(
					'11|...',
					$textWithEntities,
					'Kasper Sk&aring;r...',
					$charset
				),
				$charset . ' text with entities 13|...' => array(
					'13|...',
					$textWithEntities,
					'Kasper Sk&aring;rh&oslash;...',
					$charset
				),
				$charset . ' text with entities 14|...' => array(
					'14|...',
					$textWithEntities,
					'Kasper Sk&aring;rh&oslash;j...',
					$charset
				),
				$charset . ' text with entities 15|...' => array(
					'15|...',
					$textWithEntities,
					'Kasper Sk&aring;rh&oslash;j ...',
					$charset
				),
				$charset . ' text with entities 16|...' => array(
					'16|...',
					$textWithEntities,
					'Kasper Sk&aring;rh&oslash;j i...',
					$charset
				),
				$charset . ' text with entities -57|...' => array(
					'-57|...',
					$textWithEntities,
					'...j implemented the; original version of the crop function.',
					$charset
				),
				$charset . ' text with entities -58|...' => array(
					'-58|...',
					$textWithEntities,
					'...&oslash;j implemented the; original version of the crop function.',
					$charset
				),
				$charset . ' text with entities -59|...' => array(
					'-59|...',
					$textWithEntities,
					'...h&oslash;j implemented the; original version of the crop function.',
					$charset
				),
				$charset . ' text with entities 4|...|1' => array(
					'4|...|1',
					$textWithEntities,
					'Kasp...',
					$charset
				),
				$charset . ' text with entities 9|...|1' => array(
					'9|...|1',
					$textWithEntities,
					'Kasper...',
					$charset
				),
				$charset . ' text with entities 10|...|1' => array(
					'10|...|1',
					$textWithEntities,
					'Kasper...',
					$charset
				),
				$charset . ' text with entities 11|...|1' => array(
					'11|...|1',
					$textWithEntities,
					'Kasper...',
					$charset
				),
				$charset . ' text with entities 13|...|1' => array(
					'13|...|1',
					$textWithEntities,
					'Kasper...',
					$charset
				),
				$charset . ' text with entities 14|...|1' => array(
					'14|...|1',
					$textWithEntities,
					'Kasper Sk&aring;rh&oslash;j...',
					$charset
				),
				$charset . ' text with entities 15|...|1' => array(
					'15|...|1',
					$textWithEntities,
					'Kasper Sk&aring;rh&oslash;j...',
					$charset
				),
				$charset . ' text with entities 16|...|1' => array(
					'16|...|1',
					$textWithEntities,
					'Kasper Sk&aring;rh&oslash;j...',
					$charset
				),
				$charset . ' text with entities -57|...|1' => array(
					'-57|...|1',
					$textWithEntities,
					'...implemented the; original version of the crop function.',
					$charset
				),
				$charset . ' text with entities -58|...|1' => array(
					'-58|...|1',
					$textWithEntities,
					'...implemented the; original version of the crop function.',
					$charset
				),
				$charset . ' text with entities -59|...|1' => array(
					'-59|...|1',
					$textWithEntities,
					'...implemented the; original version of the crop function.',
					$charset
				),
				$charset . ' text with dash in html-element 28|...|1' => array(
					'28|...|1',
					'Some text with a link to <link email.address@example.org - mail "Open email window">my email.address@example.org</link> and text after it',
					'Some text with a link to <link email.address@example.org - mail "Open email window">my...</link>',
					$charset
				)
			));
		}
		return $data;
	}

	/**
	 * Checks if stdWrap.cropHTML works with plain text cropping from left
	 *
	 * @test
	 * @dataProvider cropHtmlDataProvider
	 * @param string $settings
	 * @param string $subject the string to crop
	 * @param string $expected the expected cropped result
	 * @param string $charset the charset that will be set as renderCharset
	 */
	public function cropHtmlWithDataProvider($settings, $subject, $expected, $charset) {
		$this->handleCharset($charset, $subject, $expected);
		$this->assertEquals($expected, $this->cObj->cropHTML($subject, $settings), 'cropHTML failed with settings: "' . $settings . '" and charset "' . $charset . '"');
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
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function stdWrap_roundDataProvider() {
		return array(
			'rounding off without any configuration' => array(
				1.123456789,
				array(),
				1
			),
			'rounding up without any configuration' => array(
				1.523456789,
				array(),
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
	 * Checks if stdWrap.cropHTML handles linebreaks correctly (by ignoring them)
	 *
	 * @test
	 */
	public function cropHtmlWorksWithLinebreaks() {
		$subject = "Lorem ipsum dolor sit amet,\nconsetetur sadipscing elitr,\nsed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam";
		$expected = "Lorem ipsum dolor sit amet,\nconsetetur sadipscing elitr,\nsed diam nonumy eirmod tempor invidunt ut labore et dolore magna";
		$result = $this->cObj->cropHTML($subject, '121');
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test for the stdWrap function "round"
	 *
	 * @param float $float
	 * @param array $conf
	 * @param float $expected
	 * @return void
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
	 * @return array
	 */
	public function stdWrap_strPadDataProvider() {
		return array(
			'pad string with default settings and length 10' => array(
				'Alien',
				array(
					'length' => '10',
				),
				'Alien     ',
			),
			'pad string with padWith -= and type left and length 10' => array(
				'Alien',
				array(
					'length' => '10',
					'padWith' => '-=',
					'type' => 'left',
				),
				'-=-=-Alien',
			),
			'pad string with padWith _ and type both and length 10' => array(
				'Alien',
				array(
					'length' => '10',
					'padWith' => '_',
					'type' => 'both',
				),
				'__Alien___',
			),
			'pad string with padWith _ and type both and length 6' => array(
				'Alien',
				array(
					'length' => '6',
					'padWith' => '___',
					'type' => 'both',
				),
				'Alien_',
			),
		);
	}

	/**
	 * Test for the stdWrap function "strPad"
	 *
	 * @param string $content
	 * @param array $conf
	 * @param string $expected
	 *
	 * @dataProvider stdWrap_strPadDataProvider
	 * @test
	 */
	public function stdWrap_strPad($content, $conf, $expected) {
		$conf = array(
			'strPad.' => $conf
		);
		$result = $this->cObj->stdWrap_strPad($content, $conf);
		$this->assertEquals($expected, $result);
	}

	/**
	 * Data provider for the hash test
	 *
	 * @return array multi-dimensional array with the second level like this:
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
	 * @test
	 */
	public function numberFormat($float, $formatConf, $expected) {
		$result = $this->cObj->numberFormat($float, $formatConf);
		$this->assertEquals($expected, $result);
	}

	/**
	 * Data provider for the replacement test
	 *
	 * @return array multi-dimensional array with the second level like this:
	 * @see replacement
	 */
	public function replacementDataProvider() {
		$data = array(
			'multiple replacements, including regex' => array(
				'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
				array(
					'replacement.' => array(
						'120.' => array(
							'search' => 'in da hood',
							'replace' => 'around the block'
						),
						'20.' => array(
							'search' => '_',
							'replace.' => array('char' => '32')
						),
						'130.' => array(
							'search' => '#a (Cat|Dog|Tiger)#i',
							'replace' => 'an animal',
							'useRegExp' => '1'
						)
					)
				),
				'There is an animal, an animal and an animal around the block! Yeah!'
			)
		);
		return $data;
	}

	/**
	 * Check if stdWrap.replacement and all of its properties work properly
	 *
	 * @dataProvider replacementDataProvider
	 * @test
	 */
	public function replacement($input, $conf, $expected) {
		$result = $this->cObj->stdWrap_replacement($input, $conf);
		$this->assertEquals($expected, $result);
	}

	/**
	 * Data provider for the getQuery test
	 *
	 * @return array multi-dimensional array with the second level like this:
	 * @see getQuery
	 */
	public function getQueryDataProvider() {
		$data = array(
			'testing empty conf' => array(
				'tt_content',
				array(),
				array(
					'SELECT' => '*'
				)
			),
			'testing #17284: adding uid/pid for workspaces' => array(
				'tt_content',
				array(
					'selectFields' => 'header,bodytext'
				),
				array(
					'SELECT' => 'header,bodytext, tt_content.uid as uid, tt_content.pid as pid, tt_content.t3ver_state as t3ver_state'
				)
			),
			'testing #17284: no need to add' => array(
				'tt_content',
				array(
					'selectFields' => 'tt_content.*'
				),
				array(
					'SELECT' => 'tt_content.*'
				)
			),
			'testing #17284: no need to add #2' => array(
				'tt_content',
				array(
					'selectFields' => '*'
				),
				array(
					'SELECT' => '*'
				)
			),
			'testing #29783: joined tables, prefix tablename' => array(
				'tt_content',
				array(
					'selectFields' => 'tt_content.header,be_users.username',
					'join' => 'be_users ON tt_content.cruser_id = be_users.uid'
				),
				array(
					'SELECT' => 'tt_content.header,be_users.username, tt_content.uid as uid, tt_content.pid as pid, tt_content.t3ver_state as t3ver_state'
				)
			),
			'testing #34152: single count(*), add nothing' => array(
				'tt_content',
				array(
					'selectFields' => 'count(*)'
				),
				array(
					'SELECT' => 'count(*)'
				)
			),
			'testing #34152: single max(crdate), add nothing' => array(
				'tt_content',
				array(
					'selectFields' => 'max(crdate)'
				),
				array(
					'SELECT' => 'max(crdate)'
				)
			),
			'testing #34152: single min(crdate), add nothing' => array(
				'tt_content',
				array(
					'selectFields' => 'min(crdate)'
				),
				array(
					'SELECT' => 'min(crdate)'
				)
			),
			'testing #34152: single sum(is_siteroot), add nothing' => array(
				'tt_content',
				array(
					'selectFields' => 'sum(is_siteroot)'
				),
				array(
					'SELECT' => 'sum(is_siteroot)'
				)
			),
			'testing #34152: single avg(crdate), add nothing' => array(
				'tt_content',
				array(
					'selectFields' => 'avg(crdate)'
				),
				array(
					'SELECT' => 'avg(crdate)'
				)
			)
		);
		return $data;
	}

	/**
	 * Check if sanitizeSelectPart works as expected
	 *
	 * @dataProvider getQueryDataProvider
	 * @test
	 */
	public function getQuery($table, $conf, $expected) {
		$result = $this->cObj->getQuery($table, $conf, TRUE);
		foreach ($expected as $field => $value) {
			$this->assertEquals($value, $result[$field]);
		}
	}

	/**
	 * Data provider for the stdWrap_strftime test
	 *
	 * @return array multi-dimensional array with the second level like this:
	 * @see stdWrap_strftime
	 */
	public function stdWrap_strftimeReturnsFormattedStringDataProvider() {
		$data = array(
			'given timestamp' => array(
				1346500800, // This is 2012-09-01 12:00 in UTC/GMT
				array(
					'strftime' => '%d-%m-%Y',
				),
			),
			'empty string' => array(
				'',
				array(
					'strftime' => '%d-%m-%Y',
				),
			),
			'testing null' => array(
				NULL,
				array(
					'strftime' => '%d-%m-%Y',
				),
			),
		);
		return $data;
	}

	/**
	 * @test
	 * @dataProvider stdWrap_strftimeReturnsFormattedStringDataProvider
	 */
	public function stdWrap_strftimeReturnsFormattedString($content, $conf) {
			// Set exec_time to a hard timestamp
		$GLOBALS['EXEC_TIME'] = 1346500800;
			// Save current timezone and set to UTC to make the system under test behave
			// the same in all server timezone settings
		$timezoneBackup = date_default_timezone_get();
		date_default_timezone_set('UTC');

		$result = $this->cObj->stdWrap_strftime($content, $conf);

			// Reset timezone
		date_default_timezone_set($timezoneBackup);

		$this->assertEquals('01-09-2012', $result);
	}

	/**
	 * @param string|NULL $content
	 * @param array $configuration
	 * @param string $expected
	 * @dataProvider stdWrap_ifNullDeterminesNullValuesDataProvider
	 * @test
	 */
	public function stdWrap_ifNullDeterminesNullValues($content, array $configuration, $expected) {
		$result = $this->cObj->stdWrap_ifNull($content, $configuration);
		$this->assertEquals($expected, $result);
	}

	/**
	 * Data provider for stdWrap_ifNullDeterminesNullValues test
	 *
	 * @return array
	 */
	public function stdWrap_ifNullDeterminesNullValuesDataProvider() {
		return array(
			'null value' => array(
				NULL,
				array(
					'ifNull' => '1',
				),
				'1',
			),
			'zero value' => array(
				'0',
				array(
					'ifNull' => '1',
				),
				'0',
			),
		);
	}

	/**
	 * @param $content
	 * @param array $configuration
	 * @param $expected
	 * @dataProvider stdWrap_noTrimWrapAcceptsSplitCharDataProvider
	 * @test
	 */
	public function stdWrap_noTrimWrapAcceptsSplitChar($content, array $configuration, $expected) {
		$result = $this->cObj->stdWrap_noTrimWrap($content, $configuration);
		$this->assertEquals($expected, $result);
	}

	/**
	 * Data provider for stdWrap_noTrimWrapAcceptsSplitChar test
	 *
	 * @return array
	 */
	public function stdWrap_noTrimWrapAcceptsSplitCharDataProvider() {
		return array(
			'No char given' => array(
				'middle',
				array(
					'noTrimWrap' => '| left | right |',
				),
				' left middle right '
			),
			'Zero char given' => array(
				'middle',
				array(
					'noTrimWrap' => '0 left 0 right 0',
					'noTrimWrap.' => array('splitChar' => '0'),

				),
				' left middle right '
			),
			'Default char given' => array(
				'middle',
				array(
					'noTrimWrap' => '| left | right |',
					'noTrimWrap.' => array('splitChar' => '|'),
				),
				' left middle right '
			),
			'Split char is a' => array(
				'middle',
				array(
					'noTrimWrap' => 'a left a right a',
					'noTrimWrap.' => array('splitChar' => 'a'),
				),
				' left middle right '
			),
			'Split char is multi-char (ab)' => array(
				'middle',
				array(
					'noTrimWrap' => 'ab left ab right ab',
					'noTrimWrap.' => array('splitChar' => 'ab'),
				),
				' left middle right '
			),
			'Split char accepts stdWrap' => array(
				'middle',
				array(
					'noTrimWrap' => 'abc left abc right abc',
					'noTrimWrap.' => array(
						'splitChar' => 'b',
						'splitChar.' => array('wrap' => 'a|c'),
					),
				),
				' left middle right '
			),
		);
	}

	/**
	 * @param array $expectedTags
	 * @param array $configuration
	 * @test
	 * @dataProvider stdWrap_addPageCacheTagsAddsPageTagsDataProvider
	 */
	public function stdWrap_addPageCacheTagsAddsPageTags(array $expectedTags, array $configuration) {
		$this->cObj->stdWrap_addPageCacheTags('', $configuration);
		$this->assertEquals($expectedTags, $this->tsfe->_get('pageCacheTags'));
	}

	/**
	 * @return array
	 */
	public function stdWrap_addPageCacheTagsAddsPageTagsDataProvider() {
		return array(
			'No Tag' => array(
				array(),
				array('addPageCacheTags' => ''),
			),
			'Two expectedTags' => array(
				array('tag1', 'tag2'),
				array('addPageCacheTags' => 'tag1,tag2'),
			),
			'Two expectedTags plus one with stdWrap' => array(
				array('tag1', 'tag2', 'tag3'),
				array(
					'addPageCacheTags' => 'tag1,tag2',
					'addPageCacheTags.' => array('wrap' => '|,tag3')
				),
			),
		);
	}
}

?>
