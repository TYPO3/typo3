<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

/**
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
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;

/**
 * Testcase for TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
 *
 * @author Oliver Hader <oliver@typo3.org>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ContentObjectRendererTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $subject = NULL;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $typoScriptFrontendControllerMock = NULL;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\TypoScript\TemplateService
	 */
	protected $templateServiceMock = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		$this->createMockedLoggerAndLogManager();

		$this->templateServiceMock = $this->getMock('TYPO3\\CMS\\Core\\TypoScript\\TemplateService', array('getFileName', 'linkData'));
		$pageRepositoryMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository', array('getRawRecord'));

		$this->typoScriptFrontendControllerMock = $this->getAccessibleMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array('dummy'), array(), '', FALSE);
		$this->typoScriptFrontendControllerMock->tmpl = $this->templateServiceMock;
		$this->typoScriptFrontendControllerMock->config = array();
		$this->typoScriptFrontendControllerMock->page = array();
		$this->typoScriptFrontendControllerMock->sys_page = $pageRepositoryMock;
		$this->typoScriptFrontendControllerMock->csConvObj = new CharsetConverter();
		$this->typoScriptFrontendControllerMock->renderCharset = 'utf-8';
		$GLOBALS['TSFE'] = $this->typoScriptFrontendControllerMock;

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array());
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['TYPO3\\CMS\\Core\\Charset\\CharsetConverter_utils'] = 'mbstring';

		$this->subject = $this->getAccessibleMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer',
			array('getResourceFactory', 'getEnvironmentVariable'),
			array($this->typoScriptFrontendControllerMock)
		);
		$this->subject->start(array(), 'tt_content');
	}

	public function tearDown() {
		GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	////////////////////////
	// Utitility functions
	////////////////////////

	/**
	 * Avoid logging to the file system (file writer is currently the only configured writer)
	 */
	protected function createMockedLoggerAndLogManager() {
		$logManagerMock = $this->getMock(LogManager::class);
		$loggerMock = $this->getMock(LoggerInterface::class);
		$logManagerMock->expects($this->any())
			->method('getLogger')
			->willReturn($loggerMock);
		GeneralUtility::setSingletonInstance(LogManager::class, $logManagerMock);
	}

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
	public function getImgResourceCallsGetImgResourcePostProcessHook() {
		$this->templateServiceMock
			->expects($this->atLeastOnce())
			->method('getFileName')
			->with('typo3/clear.gif')
			->will($this->returnValue('typo3/clear.gif'));

		$resourceFactory = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceFactory', array(), array(), '', FALSE);
		$this->subject->expects($this->any())->method('getResourceFactory')->will($this->returnValue($resourceFactory));

		$className = uniqid('tx_coretest');
		$getImgResourceHookMock = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectGetImageResourceHookInterface', array('getImgResourcePostProcess'), array(), $className);
		$getImgResourceHookMock
			->expects($this->once())
			->method('getImgResourcePostProcess')
			->will($this->returnCallback(array($this, 'isGetImgResourceHookCalledCallback')));
		$getImgResourceHookObjects = array($getImgResourceHookMock);
		$this->subject->_setRef('getImgResourceHookObjects', $getImgResourceHookObjects);
		$this->subject->getImgResource('typo3/clear.gif', array());
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
	public function getContentObjectCallsMakeInstanceForNewContentObjectInstance($name, $className) {
		$fullClassName = 'TYPO3\\CMS\\Frontend\\ContentObject\\' . $className . 'ContentObject';
		$contentObjectInstance = $this->getMock($fullClassName, array(), array(), '', FALSE);
		\TYPO3\CMS\Core\Utility\GeneralUtility::addInstance($fullClassName, $contentObjectInstance);
		$this->assertSame($contentObjectInstance, $this->subject->getContentObject($name));
	}

	//////////////////////////
	// Tests concerning FORM
	//////////////////////////
	/**
	 * @test
	 */
	public function formWithSecureFormMailEnabledDoesNotContainRecipientField() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail'] = TRUE;
		$this->assertNotContains('name="recipient', $this->subject->FORM(array('recipient' => 'foo@bar.com', 'recipient.' => array()), array()));
	}

	/**
	 * @test
	 */
	public function formWithSecureFormMailDisabledDoesNotContainRecipientField() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail'] = FALSE;
		$this->assertContains('name="recipient', $this->subject->FORM(array('recipient' => 'foo@bar.com', 'recipient.' => array()), array()));
	}

	/////////////////////////////////////////
	// Tests concerning getQueryArguments()
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function getQueryArgumentsExcludesParameters() {
		$this->subject->expects($this->any())->method('getEnvironmentVariable')->with($this->equalTo('QUERY_STRING'))->will(
			$this->returnValue('key1=value1&key2=value2&key3[key31]=value31&key3[key32][key321]=value321&key3[key32][key322]=value322')
		);
		$getQueryArgumentsConfiguration = array();
		$getQueryArgumentsConfiguration['exclude'] = array();
		$getQueryArgumentsConfiguration['exclude'][] = 'key1';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key31]';
		$getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key321]';
		$getQueryArgumentsConfiguration['exclude'] = implode(',', $getQueryArgumentsConfiguration['exclude']);
		$expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key2=value2&key3[key32][key322]=value322');
		$actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration);
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
		$actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getQueryArgumentsOverrulesSingleParameter() {
		$this->subject->expects($this->any())->method('getEnvironmentVariable')->with($this->equalTo('QUERY_STRING'))->will(
			$this->returnValue('key1=value1')
		);
		$getQueryArgumentsConfiguration = array();
		$overruleArguments = array(
			// Should be overridden
			'key1' => 'value1Overruled',
			// Shouldn't be set: Parameter doesn't exist in source array and is not forced
			'key2' => 'value2Overruled'
		);
		$expectedResult = '&key1=value1Overruled';
		$actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments);
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
		$actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getQueryArgumentsOverrulesMultiDimensionalForcedParameters() {
		$this->subject->expects($this->any())->method('getEnvironmentVariable')->with($this->equalTo('QUERY_STRING'))->will(
			$this->returnValue('key1=value1&key2=value2&key3[key31]=value31&key3[key32][key321]=value321&key3[key32][key322]=value322')
		);
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
		$actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments, TRUE);
		$this->assertEquals($expectedResult, $actualResult);
		$getQueryArgumentsConfiguration['method'] = 'POST';
		$actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments, TRUE);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getQueryArgumentsWithMethodPostGetMergesParameters() {
		$_POST = array(
			'key1' => 'POST1',
			'key2' => 'POST2',
			'key3' => array(
				'key31' => 'POST31',
				'key32' => 'POST32',
				'key33' => array(
					'key331' => 'POST331',
					'key332' => 'POST332',
				)
			)
		);
		$_GET = array(
			'key2' => 'GET2',
			'key3' => array(
				'key32' => 'GET32',
				'key33' => array(
					'key331' => 'GET331',
				)
			)
		);
		$getQueryArgumentsConfiguration = array();
		$getQueryArgumentsConfiguration['method'] = 'POST,GET';
		$expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key1=POST1&key2=GET2&key3[key31]=POST31&key3[key32]=GET32&key3[key33][key331]=GET331&key3[key33][key332]=POST332');
		$actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getQueryArgumentsWithMethodGetPostMergesParameters() {
		$_GET = array(
			'key1' => 'GET1',
			'key2' => 'GET2',
			'key3' => array(
				'key31' => 'GET31',
				'key32' => 'GET32',
				'key33' => array(
					'key331' => 'GET331',
					'key332' => 'GET332',
				)
			)
		);
		$_POST = array(
			'key2' => 'POST2',
			'key3' => array(
				'key32' => 'POST32',
				'key33' => array(
					'key331' => 'POST331',
				)
			)
		);
		$getQueryArgumentsConfiguration = array();
		$getQueryArgumentsConfiguration['method'] = 'GET,POST';
		$expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key1=GET1&key2=POST2&key3[key31]=GET31&key3[key32]=POST32&key3[key33][key331]=POST331&key3[key33][key332]=GET332');
		$actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration);
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
		$this->assertEquals('бла', $this->subject->crop('бла', '3|...'));
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
				),
				$charset . ' html elements with dashes in attributes' => array(
					'9',
					'<em data-foo="x">foobar</em>foobaz',
					'<em data-foo="x">foobar</em>foo',
					$charset
				),
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
		$this->assertEquals($expected, $this->subject->cropHTML($subject, $settings), 'cropHTML failed with settings: "' . $settings . '" and charset "' . $charset . '"');
	}

	/**
	 * Checks if stdWrap.cropHTML works with a complex content with many tags. Currently cropHTML
	 * counts multiple invisible characters not as one (as the browser will output the content).
	 *
	 * @test
	 */
	public function cropHtmlWorksWithComplexContent() {
		$GLOBALS['TSFE']->renderCharset = 'iso-8859-1';
		$input =
			'<h1>Blog Example</h1>' . LF .
			'<hr>' . LF .
			'<div class="csc-header csc-header-n1">' . LF .
			'	<h2 class="csc-firstHeader">Welcome to Blog #1</h2>' . LF .
			'</div>' . LF .
			'<p class="bodytext">' . LF .
			'	A blog about TYPO3 extension development. In order to start blogging, read the <a href="#">Help section</a>. If you have any further questions, feel free to contact the administrator John Doe (<a href="mailto:john.doe@example.com">john.doe@example.com)</a>.' . LF .
			'</p>' . LF .
			'<div class="tx-blogexample-list-container">' . LF .
			'	<p class="bodytext">' . LF .
			'		Below are the most recent posts:' . LF .
			'	</p>' . LF .
			'	<ul>' . LF .
			'		<li data-element="someId">' . LF .
			'			<h3>' . LF .
			'				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog]=&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=003b0131ed">The Post #1</a>' . LF .
			'			</h3>' . LF .
			'			<p class="bodytext">' . LF .
			'				Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut...' . LF .
			'			</p>' . LF .
			'			<p class="metadata">' . LF .
			'				Published on 26.08.2009 by Jochen Rau' . LF .
			'			</p>' . LF .
			'			<p>' . LF .
			'				Tags: [MVC]&nbsp;[Domain Driven Design]&nbsp;<br>' . LF .
			'				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=f982643bc3">read more &gt;&gt;</a><br>' . LF .
			'				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=edit&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=5b481bc8f0">Edit</a>&nbsp;<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=delete&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=4e52879656">Delete</a>' . LF .
			'			</p>' . LF .
			'		</li>' . LF .
			'	</ul>' . LF .
			'	<p>' . LF .
			'		<a href="index.php?id=99&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=new&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=2718a4b1a0">Create a new Post</a>' . LF .
			'	</p>' . LF .
			'</div>' . LF .
			'<hr>' . LF .
			'<p>' . LF .
			'	? TYPO3 Association' . LF .
			'</p>';

		$result = $this->subject->cropHTML($input, '300');

		$expected =
			'<h1>Blog Example</h1>' . LF .
			'<hr>' . LF .
			'<div class="csc-header csc-header-n1">' . LF .
			'	<h2 class="csc-firstHeader">Welcome to Blog #1</h2>' . LF .
			'</div>' . LF .
			'<p class="bodytext">' . LF .
			'	A blog about TYPO3 extension development. In order to start blogging, read the <a href="#">Help section</a>. If you have any further questions, feel free to contact the administrator John Doe (<a href="mailto:john.doe@example.com">john.doe@example.com)</a>.' . LF .
			'</p>' . LF .
			'<div class="tx-blogexample-list-container">' . LF .
			'	<p class="bodytext">' . LF .
			'		Below are the most recent posts:' . LF .
			'	</p>' . LF .
			'	<ul>' . LF .
			'		<li data-element="someId">' . LF .
			'			<h3>' . LF .
			'				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog]=&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=003b0131ed">The Post</a></h3></li></ul></div>';

		$this->assertEquals($expected, $result);

		$result = $this->subject->cropHTML($input, '-100');

		$expected =
			'<div class="tx-blogexample-list-container"><ul><li data-element="someId"><p> Design]&nbsp;<br>' . LF .
			'				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=f982643bc3">read more &gt;&gt;</a><br>' . LF .
			'				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=edit&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=5b481bc8f0">Edit</a>&nbsp;<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=delete&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=4e52879656">Delete</a>' . LF .
			'			</p>' . LF .
			'		</li>' . LF .
			'	</ul>' . LF .
			'	<p>' . LF .
			'		<a href="index.php?id=99&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=new&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=2718a4b1a0">Create a new Post</a>' . LF .
			'	</p>' . LF .
			'</div>' . LF .
			'<hr>' . LF .
			'<p>' . LF .
			'	? TYPO3 Association' . LF .
			'</p>';

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
		$result = $this->subject->cropHTML($subject, '121');
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
		$result = $this->subject->stdWrap_round($float, $conf);
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
			'pad string with padWith 0 and type both and length 10' => array(
				'Alien',
				array(
					'length' => '10',
					'padWith' => '0',
					'type' => 'both',
				),
				'00Alien000',
			),
			'pad string with padWith ___ and type both and length 6' => array(
				'Alien',
				array(
					'length' => '6',
					'padWith' => '___',
					'type' => 'both',
				),
				'Alien_',
			),
			'pad string with padWith _ and type both and length 12, using stdWrap for length' => array(
				'Alien',
				array(
					'length' => '1',
					'length.' => array(
						'wrap' => '|2',
					),
					'padWith' => '_',
					'type' => 'both',
				),
				'___Alien____',
			),
			'pad string with padWith _ and type both and length 12, using stdWrap for padWidth' => array(
				'Alien',
				array(
					'length' => '12',
					'padWith' => '_',
					'padWith.' => array(
						'wrap' => '-|=',
					),
					'type' => 'both',
				),
				'-_=Alien-_=-',
			),
			'pad string with padWith _ and type both and length 12, using stdWrap for type' => array(
				'Alien',
				array(
					'length' => '12',
					'padWith' => '_',
					'type' => 'both',
					// make type become "left"
					'type.' => array(
						'substring' => '2,1',
						'wrap' => 'lef|',
					),
				),
				'_______Alien',
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
		$result = $this->subject->stdWrap_strPad($content, $conf);
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
		$result = $this->subject->stdWrap_hash($text, $conf);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function recursiveStdWrapProperlyRendersBasicString() {
		$stdWrapConfiguration = array(
			'noTrimWrap' => '|| 123|',
			'stdWrap.' => array(
				'wrap' => '<b>|</b>'
			)
		);
		$this->assertSame(
			'<b>Test</b> 123',
			$this->subject->stdWrap('Test', $stdWrapConfiguration)
		);
	}

	/**
	 * @test
	 */
	public function recursiveStdWrapIsOnlyCalledOnce() {
		$stdWrapConfiguration = array(
			'append' => 'TEXT',
			'append.' => array(
				'data' => 'register:Counter'
			),
			'stdWrap.' => array(
				'append' => 'LOAD_REGISTER',
				'append.' => array(
					'Counter.' => array(
						'prioriCalc' => 'intval',
						'cObject' => 'TEXT',
						'cObject.' => array(
							'data' => 'register:Counter',
							'wrap' => '|+1',
						)
					)
				)
			)
		);
		$this->assertSame(
			'Counter:1',
			$this->subject->stdWrap('Counter:', $stdWrapConfiguration)
		);
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
			'testing decimals with input as string' => array(
				'0.8',
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
		$result = $this->subject->numberFormat($float, $formatConf);
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
			),
			'replacement with optionSplit, normal pattern' => array(
				'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
				array(
					'replacement.' => array(
						'10.' => array(
							'search' => '_',
							'replace' => '1 || 2 || 3',
							'useOptionSplitReplace' => '1'
						),
					)
				),
				'There1is2a3cat,3a3dog3and3a3tiger3in3da3hood!3Yeah!'
			),
			'replacement with optionSplit, using regex' => array(
				'There is a cat, a dog and a tiger in da hood! Yeah!',
				array(
					'replacement.' => array(
						'10.' => array(
							'search' => '#(a) (Cat|Dog|Tiger)#i',
							'replace' => '${1} tiny ${2} || ${1} midsized ${2} || ${1} big ${2}',
							'useOptionSplitReplace' => '1',
							'useRegExp' => '1'
						)
					)
				),
				'There is a tiny cat, a midsized dog and a big tiger in da hood! Yeah!'
			),
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
		$result = $this->subject->stdWrap_replacement($input, $conf);
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
		$GLOBALS['TCA'] = array(
			'pages' => array(
				'ctrl' => array(
					'enablecolumns' => array(
						'disabled' => 'hidden'
					)
				)
			),
			'tt_content' => array(
				'ctrl' => array(
					'enablecolumns' => array(
						'disabled' => 'hidden'
					),
					'versioningWS' => 2
				)
			),
		);
		$result = $this->subject->getQuery($table, $conf, TRUE);
		foreach ($expected as $field => $value) {
			$this->assertEquals($value, $result[$field]);
		}
	}

	/**
	 * @test
	 */
	public function getQueryCallsGetTreeListWithNegativeValuesIfRecursiveIsSet() {
		$GLOBALS['TCA'] = array(
			'pages' => array(
				'ctrl' => array(
					'enablecolumns' => array(
						'disabled' => 'hidden'
					)
				)
			),
			'tt_content' => array(
				'ctrl' => array(
					'enablecolumns' => array(
						'disabled' => 'hidden'
					)
				)
			),
		);
		$this->subject = $this->getAccessibleMock('\\TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer', array('getTreeList'));
		$this->subject->start(array(), 'tt_content');
		$conf = array(
			'recursive' => '15',
			'pidInList' => '16, -35'
		);
		$this->subject->expects($this->at(0))
			->method('getTreeList')
			->with(-16, 15)
			->will($this->returnValue('15,16'));
		$this->subject->expects($this->at(1))
			->method('getTreeList')
			->with(-35, 15)
			->will($this->returnValue('15,35'));
		$this->subject->getQuery('tt_content', $conf, TRUE);
	}

	/**
	 * @test
	 */
	public function getQueryCallsGetTreeListWithCurrentPageIfThisIsSet() {
		$GLOBALS['TCA'] = array(
			'pages' => array(
				'ctrl' => array(
					'enablecolumns' => array(
						'disabled' => 'hidden'
					)
				)
			),
			'tt_content' => array(
				'ctrl' => array(
					'enablecolumns' => array(
						'disabled' => 'hidden'
					)
				)
			),
		);
		$this->subject = $this->getAccessibleMock('\\TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer', array('getTreeList'));
		$GLOBALS['TSFE']->id = 27;
		$this->subject->start(array(), 'tt_content');
		$conf = array(
			'pidInList' => 'this',
			'recursive' => '4'
		);
		$this->subject->expects($this->once())
			->method('getTreeList')
			->with(-27)
			->will($this->returnValue('27'));
		$this->subject->getQuery('tt_content', $conf, TRUE);
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

		$result = $this->subject->stdWrap_strftime($content, $conf);

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
		$result = $this->subject->stdWrap_ifNull($content, $configuration);
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
		$result = $this->subject->stdWrap_noTrimWrap($content, $configuration);
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
		$this->subject->stdWrap_addPageCacheTags('', $configuration);
		$this->assertEquals($expectedTags, $this->typoScriptFrontendControllerMock->_get('pageCacheTags'));
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

	/**
	 * Data provider for stdWrap_encodeForJavaScriptValue test
	 *
	 * @return array multi-dimensional array with the second level like this:
	 * @see encodeForJavaScriptValue
	 */
	public function stdWrap_encodeForJavaScriptValueDataProvider() {
		return array(
			'double quote in string' => array(
				'double quote"',
				array(),
				'\'double\u0020quote\u0022\''
			),
			'backslash in string' => array(
				'backslash \\',
				array(),
				'\'backslash\u0020\u005C\''
			),
			'exclamation mark' => array(
				'exclamation!',
				array(),
				'\'exclamation\u0021\''
			),
			'whitespace tab, newline and carriage return' => array(
				"white\tspace\ns\r",
				array(),
				'\'white\u0009space\u000As\u000D\''
			),
			'single quote in string' => array(
				'single quote \'',
				array(),
				'\'single\u0020quote\u0020\u0027\''
			),
			'tag' => array(
				'<tag>',
				array(),
				'\'\u003Ctag\u003E\''
			),
			'ampersand in string' => array(
				'amper&sand',
				array(),
				'\'amper\u0026sand\''
			),
		);
	}

	/**
	 * Check if encodeForJavaScriptValue works properly
	 *
	 * @dataProvider stdWrap_encodeForJavaScriptValueDataProvider
	 * @test
	 */
	public function stdWrap_encodeForJavaScriptValue($input, $conf, $expected) {
		$result = $this->subject->stdWrap_encodeForJavaScriptValue($input, $conf);
		$this->assertEquals($expected, $result);
	}


	/////////////////////////////
	// Tests concerning getData()
	/////////////////////////////

	/**
	 * @return array
	 */
	public function getDataWithTypeGpDataProvider() {
		return array(
			'Value in get-data' => array('onlyInGet', 'GetValue'),
			'Value in post-data' => array('onlyInPost', 'PostValue'),
			'Value in post-data overriding get-data' => array('inGetAndPost', 'ValueInPost'),
		);
	}

	/**
	 * Checks if getData() works with type "gp"
	 *
	 * @test
	 * @dataProvider getDataWithTypeGpDataProvider
	 */
	public function getDataWithTypeGp($key, $expectedValue) {
		$_GET = array(
			'onlyInGet' => 'GetValue',
			'inGetAndPost' => 'ValueInGet',
		);
		$_POST = array(
			'onlyInPost' => 'PostValue',
			'inGetAndPost' => 'ValueInPost',
		);
		$this->assertEquals($expectedValue, $this->subject->getData('gp:' . $key));
	}

	/**
	 * Checks if getData() works with type "tsfe"
	 *
	 * @test
	 */
	public function getDataWithTypeTsfe() {
		$this->assertEquals($GLOBALS['TSFE']->renderCharset, $this->subject->getData('tsfe:renderCharset'));
	}

	/**
	 * Checks if getData() works with type "getenv"
	 *
	 * @test
	 */
	public function getDataWithTypeGetenv() {
		$envName = uniqid('frontendtest');
		$value = uniqid('someValue');
		putenv($envName . '=' . $value);
		$this->assertEquals($value, $this->subject->getData('getenv:' . $envName));
	}

	/**
	 * Checks if getData() works with type "getindpenv"
	 *
	 * @test
	 */
	public function getDataWithTypeGetindpenv() {
		$this->subject->expects($this->once())->method('getEnvironmentVariable')
			->with($this->equalTo('SCRIPT_FILENAME'))->will($this->returnValue('dummyPath'));
		$this->assertEquals('dummyPath', $this->subject->getData('getindpenv:SCRIPT_FILENAME'));
	}

	/**
	 * Checks if getData() works with type "getindpenv"
	 *
	 * @test
	 */
	public function getDataWithTypeField() {
		$key = 'someKey';
		$value = 'someValue';
		$field = array($key => $value);

		$this->assertEquals($value, $this->subject->getData('field:' . $key, $field));
	}

	/**
	 * Basic check if getData gets the uid of a file object
	 *
	 * @test
	 */
	public function getDataWithTypeFileReturnsUidOfFileObject() {
		$uid = uniqid();
		$file = $this->getMock('TYPO3\\CMS\\Core\\Resource\File', array(), array(), '', FALSE);
		$file->expects($this->once())->method('getUid')->will($this->returnValue($uid));
		$this->subject->setCurrentFile($file);
		$this->assertEquals($uid, $this->subject->getData('file:current:uid'));
	}

	/**
	 * Checks if getData() works with type "parameters"
	 *
	 * @test
	 */
	public function getDataWithTypeParameters() {
		$key = uniqid('someKey');
		$value = uniqid('someValue');
		$this->subject->parameters[$key] = $value;

		$this->assertEquals($value, $this->subject->getData('parameters:' . $key));
	}

	/**
	 * Checks if getData() works with type "register"
	 *
	 * @test
	 */
	public function getDataWithTypeRegister() {
		$key = uniqid('someKey');
		$value = uniqid('someValue');
		$GLOBALS['TSFE']->register[$key] = $value;

		$this->assertEquals($value, $this->subject->getData('register:' . $key));
	}

	/**
	 * Checks if getData() works with type "level"
	 *
	 * @test
	 */
	public function getDataWithTypeLevel() {
		$rootline = array(
			0 => array('uid' => 1, 'title' => 'title1'),
			1 => array('uid' => 2, 'title' => 'title2'),
			2 => array('uid' => 3, 'title' => 'title3'),
		);

		$GLOBALS['TSFE']->tmpl->rootLine = $rootline;
		$this->assertEquals(2, $this->subject->getData('level'));
	}

	/**
	 * Checks if getData() works with type "global"
	 *
	 * @test
	 */
	public function getDataWithTypeGlobal() {
		$this->assertEquals($GLOBALS['TSFE']->renderCharset, $this->subject->getData('global:TSFE|renderCharset'));
	}

	/**
	 * Checks if getData() works with type "leveltitle"
	 *
	 * @test
	 */
	public function getDataWithTypeLeveltitle() {
		$rootline = array(
			0 => array('uid' => 1, 'title' => 'title1'),
			1 => array('uid' => 2, 'title' => 'title2'),
			2 => array('uid' => 3, 'title' => ''),
		);

		$GLOBALS['TSFE']->tmpl->rootLine = $rootline;
		$this->assertEquals('', $this->subject->getData('leveltitle:-1'));
		// since "title3" is not set, it will slide to "title2"
		$this->assertEquals('title2', $this->subject->getData('leveltitle:-1,slide'));
	}

	/**
	 * Checks if getData() works with type "levelmedia"
	 *
	 * @test
	 */
	public function getDataWithTypeLevelmedia() {
		$rootline = array(
			0 => array('uid' => 1, 'title' => 'title1', 'media' => 'media1'),
			1 => array('uid' => 2, 'title' => 'title2', 'media' => 'media2'),
			2 => array('uid' => 3, 'title' => 'title3', 'media' => ''),
		);

		$GLOBALS['TSFE']->tmpl->rootLine = $rootline;
		$this->assertEquals('', $this->subject->getData('levelmedia:-1'));
		// since "title3" is not set, it will slide to "title2"
		$this->assertEquals('media2', $this->subject->getData('levelmedia:-1,slide'));
	}

	/**
	 * Checks if getData() works with type "leveluid"
	 *
	 * @test
	 */
	public function getDataWithTypeLeveluid() {
		$rootline = array(
			0 => array('uid' => 1, 'title' => 'title1'),
			1 => array('uid' => 2, 'title' => 'title2'),
			2 => array('uid' => 3, 'title' => 'title3'),
		);

		$GLOBALS['TSFE']->tmpl->rootLine = $rootline;
		$this->assertEquals(3, $this->subject->getData('leveluid:-1'));
		// every element will have a uid - so adding slide doesn't really make sense, just for completeness
		$this->assertEquals(3, $this->subject->getData('leveluid:-1,slide'));
	}

	/**
	 * Checks if getData() works with type "levelfield"
	 *
	 * @test
	 */
	public function getDataWithTypeLevelfield() {
		$rootline = array(
			0 => array('uid' => 1, 'title' => 'title1', 'testfield' => 'field1'),
			1 => array('uid' => 2, 'title' => 'title2', 'testfield' => 'field2'),
			2 => array('uid' => 3, 'title' => 'title3', 'testfield' => ''),
		);

		$GLOBALS['TSFE']->tmpl->rootLine = $rootline;
		$this->assertEquals('', $this->subject->getData('levelfield:-1,testfield'));
		$this->assertEquals('field2', $this->subject->getData('levelfield:-1,testfield,slide'));
	}

	/**
	 * Checks if getData() works with type "fullrootline"
	 *
	 * @test
	 */
	public function getDataWithTypeFullrootline() {
		$rootline1 = array(
			0 => array('uid' => 1, 'title' => 'title1', 'testfield' => 'field1'),
		);
		$rootline2 = array(
			0 => array('uid' => 1, 'title' => 'title1', 'testfield' => 'field1'),
			1 => array('uid' => 2, 'title' => 'title2', 'testfield' => 'field2'),
			2 => array('uid' => 3, 'title' => 'title3', 'testfield' => 'field3'),
		);

		$GLOBALS['TSFE']->tmpl->rootLine = $rootline1;
		$GLOBALS['TSFE']->rootLine = $rootline2;
		$this->assertEquals('field2', $this->subject->getData('fullrootline:-1,testfield'));
	}

	/**
	 * Checks if getData() works with type "date"
	 *
	 * @test
	 */
	public function getDataWithTypeDate() {
		$format = 'Y-M-D';
		$defaultFormat = 'd/m Y';

		$this->assertEquals(date($format, $GLOBALS['EXEC_TIME']), $this->subject->getData('date:' . $format));
		$this->assertEquals(date($defaultFormat, $GLOBALS['EXEC_TIME']), $this->subject->getData('date'));
	}

	/**
	 * Checks if getData() works with type "page"
	 *
	 * @test
	 */
	public function getDataWithTypePage() {
		$uid = rand();
		$GLOBALS['TSFE']->page['uid'] = $uid;
		$this->assertEquals($uid, $this->subject->getData('page:uid'));
	}

	/**
	 * Checks if getData() works with type "current"
	 *
	 * @test
	 */
	public function getDataWithTypeCurrent() {
		$key = uniqid('someKey');
		$value = uniqid('someValue');
		$this->subject->data[$key] = $value;
		$this->subject->currentValKey = $key;
		$this->assertEquals($value, $this->subject->getData('current'));
	}

	/**
	 * Checks if getData() works with type "db"
	 *
	 * @test
	 */
	public function getDataWithTypeDb() {
		$dummyRecord = array('uid' => 5, 'title' => 'someTitle');

		$GLOBALS['TSFE']->sys_page->expects($this->atLeastOnce())->method('getRawRecord')->with('tt_content', '106')->will($this->returnValue($dummyRecord));
		$this->assertEquals($dummyRecord['title'], $this->subject->getData('db:tt_content:106:title'));
	}

	/**
	 * Checks if getData() works with type "lll"
	 *
	 * @test
	 */
	public function getDataWithTypeLll() {
		$key = uniqid('someKey');
		$value = uniqid('someValue');
		$language = uniqid('someLanguage');
		$GLOBALS['TSFE']->LL_labels_cache[$language]['LLL:' . $key] = $value;
		$GLOBALS['TSFE']->lang = $language;

		$this->assertEquals($value, $this->subject->getData('lll:' . $key));
	}

	/**
	 * Checks if getData() works with type "path"
	 *
	 * @test
	 */
	public function getDataWithTypePath() {
		$filenameIn = uniqid('someValue');
		$filenameOut = uniqid('someValue');
		$this->templateServiceMock->expects($this->atLeastOnce())->method('getFileName')->with($filenameIn)->will($this->returnValue($filenameOut));
		$this->assertEquals($filenameOut, $this->subject->getData('path:' . $filenameIn));
	}

	/**
	 * Checks if getData() works with type "parentRecordNumber"
	 *
	 * @test
	 */
	public function getDataWithTypeParentRecordNumber() {
		$recordNumber = rand();
		$this->subject->parentRecordNumber = $recordNumber;
		$this->assertEquals($recordNumber, $this->subject->getData('cobj:parentRecordNumber'));
	}

	/**
	 * Checks if getData() works with type "debug:rootLine"
	 *
	 * @test
	 */
	public function getDataWithTypeDebugRootline() {
		$rootline = array(
			0 => array('uid' => 1, 'title' => 'title1'),
			1 => array('uid' => 2, 'title' => 'title2'),
			2 => array('uid' => 3, 'title' => ''),
		);
		$expectedResult = '0uid1titletitle11uid2titletitle22uid3title';
		$GLOBALS['TSFE']->tmpl->rootLine = $rootline;

		$result = $this->subject->getData('debug:rootLine');
		$cleanedResult = strip_tags($result);
		$cleanedResult = str_replace("\r", '', $cleanedResult);
		$cleanedResult = str_replace("\n", '', $cleanedResult);
		$cleanedResult = str_replace("\t", '', $cleanedResult);
		$cleanedResult = str_replace(' ', '', $cleanedResult);

		$this->assertEquals($expectedResult, $cleanedResult);
	}

	/**
	 * Checks if getData() works with type "debug:fullRootLine"
	 *
	 * @test
	 */
	public function getDataWithTypeDebugFullRootline() {
		$rootline = array(
			0 => array('uid' => 1, 'title' => 'title1'),
			1 => array('uid' => 2, 'title' => 'title2'),
			2 => array('uid' => 3, 'title' => ''),
		);
		$expectedResult = '0uid1titletitle11uid2titletitle22uid3title';
		$GLOBALS['TSFE']->rootLine = $rootline;

		$result = $this->subject->getData('debug:fullRootLine');
		$cleanedResult = strip_tags($result);
		$cleanedResult = str_replace("\r", '', $cleanedResult);
		$cleanedResult = str_replace("\n", '', $cleanedResult);
		$cleanedResult = str_replace("\t", '', $cleanedResult);
		$cleanedResult = str_replace(' ', '', $cleanedResult);

		$this->assertEquals($expectedResult, $cleanedResult);
	}

	/**
	 * Checks if getData() works with type "debug:data"
	 *
	 * @test
	 */
	public function getDataWithTypeDebugData() {
		$key = uniqid('someKey');
		$value = uniqid('someValue');
		$this->subject->data = array($key => $value);

		$expectedResult = $key . $value;

		$result = $this->subject->getData('debug:data');
		$cleanedResult = strip_tags($result);
		$cleanedResult = str_replace("\r", '', $cleanedResult);
		$cleanedResult = str_replace("\n", '', $cleanedResult);
		$cleanedResult = str_replace("\t", '', $cleanedResult);
		$cleanedResult = str_replace(' ', '', $cleanedResult);

		$this->assertEquals($expectedResult, $cleanedResult);
	}

	/**
	 * Checks if getData() works with type "debug:register"
	 *
	 * @test
	 */
	public function getDataWithTypeDebugRegister() {
		$key = uniqid('someKey');
		$value = uniqid('someValue');
		$GLOBALS['TSFE']->register = array($key => $value);

		$expectedResult = $key . $value;

		$result = $this->subject->getData('debug:register');
		$cleanedResult = strip_tags($result);
		$cleanedResult = str_replace("\r", '', $cleanedResult);
		$cleanedResult = str_replace("\n", '', $cleanedResult);
		$cleanedResult = str_replace("\t", '', $cleanedResult);
		$cleanedResult = str_replace(' ', '', $cleanedResult);

		$this->assertEquals($expectedResult, $cleanedResult);
	}

	/**
	 * Checks if getData() works with type "data:page"
	 *
	 * @test
	 */
	public function getDataWithTypeDebugPage() {
		$uid = rand();
		$GLOBALS['TSFE']->page = array('uid' => $uid);

		$expectedResult = 'uid' . $uid;

		$result = $this->subject->getData('debug:page');
		$cleanedResult = strip_tags($result);
		$cleanedResult = str_replace("\r", '', $cleanedResult);
		$cleanedResult = str_replace("\n", '', $cleanedResult);
		$cleanedResult = str_replace("\t", '', $cleanedResult);
		$cleanedResult = str_replace(' ', '', $cleanedResult);

		$this->assertEquals($expectedResult, $cleanedResult);
	}

	/**
	 * @test
	 */
	public function getTreeListReturnsChildPageUids() {
		$GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetSingleRow')->with('treelist')->will($this->returnValue(NULL));
		$GLOBALS['TSFE']->sys_page
			->expects($this->any())
			->method('getRawRecord')
			->will(
				$this->onConsecutiveCalls(
					array('uid' => 17),
					array('uid' => 321),
					array('uid' => 719),
					array('uid' => 42)
				)
			);

		$GLOBALS['TSFE']->sys_page->expects($this->any())->method('getMountPointInfo')->will($this->returnValue(NULL));
		$GLOBALS['TYPO3_DB']
			->expects($this->any())
			->method('exec_SELECTgetRows')
			->will(
				$this->onConsecutiveCalls(
					array(
						array('uid' => 321)
					),
					array(
						array('uid' => 719)
					),
					array(
						array('uid' => 42)
					)
				)
			);
		// 17 = pageId, 5 = recursionLevel, 0 = begin (entry to recursion, internal), TRUE = do not check enable fields
		// 17 is positive, we expect 17 NOT to be included in result
		$result = $this->subject->getTreeList(17, 5, 0, TRUE);
		$expectedResult = '42,719,321';
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @test
	 */
	public function getTreeListReturnsChildPageUidsAndOriginalPidForNegativeValue() {
		$GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetSingleRow')->with('treelist')->will($this->returnValue(NULL));
		$GLOBALS['TSFE']->sys_page
			->expects($this->any())
			->method('getRawRecord')
			->will(
				$this->onConsecutiveCalls(
					array('uid' => 17),
					array('uid' => 321),
					array('uid' => 719),
					array('uid' => 42)
				)
			);

		$GLOBALS['TSFE']->sys_page->expects($this->any())->method('getMountPointInfo')->will($this->returnValue(NULL));
		$GLOBALS['TYPO3_DB']
			->expects($this->any())
			->method('exec_SELECTgetRows')
			->will(
				$this->onConsecutiveCalls(
					array(
						array('uid' => 321)
					),
					array(
						array('uid' => 719)
					),
					array(
						array('uid' => 42)
					)
				)
			);
		// 17 = pageId, 5 = recursionLevel, 0 = begin (entry to recursion, internal), TRUE = do not check enable fields
		// 17 is negative, we expect 17 to be included in result
		$result = $this->subject->getTreeList(-17, 5, 0, TRUE);
		$expectedResult = '42,719,321,17';
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @test
	 */
	public function aTagParamsHasLeadingSpaceIfNotEmpty() {
		$aTagParams = $this->subject->getATagParams(array('ATagParams' => 'data-test="testdata"'));
		$this->assertEquals(' data-test="testdata"', $aTagParams );
	}

	/**
	 * @test
	 */
	public function aTagParamsHaveSpaceBetweenLocalAndGlobalParams() {
		$GLOBALS['TSFE']->ATagParams = 'data-global="dataglobal"';
		$aTagParams = $this->subject->getATagParams(array('ATagParams' => 'data-test="testdata"'));
		$this->assertEquals(' data-global="dataglobal" data-test="testdata"', $aTagParams );
	}

	/**
	 * @test
	 */
	public function aTagParamsHasNoLeadingSpaceIfEmpty() {
		// make sure global ATagParams are empty
		$GLOBALS['TSFE']->ATagParams = '';
		$aTagParams = $this->subject->getATagParams(array('ATagParams' => ''));
		$this->assertEquals('', $aTagParams);
	}

	/**
	 * @return array
	 */
	public function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFoundDataProvider() {
		return array(
			array(NULL, NULL),
			array('', NULL),
			array('', array()),
			array('fooo', array('foo' => 'bar'))
		);
	}

	/**
	 * Make sure that the rendering falls back to the classic <img style if nothing else is found
	 *
	 * @test
	 * @dataProvider getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFoundDataProvider
	 * @param string $key
	 * @param array $configuration
	 */
	public function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFound($key, $configuration) {
		$defaultImgTagTemplate = '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ###BORDER######SELFCLOSINGTAGSLASH###>';
		$result = $this->subject->getImageTagTemplate($key, $configuration);
		$this->assertEquals($result, $defaultImgTagTemplate);
	}

	/**
	 * @return array
	 */
	public function getImageTagTemplateReturnTemplateElementIdentifiedByKeyDataProvider() {
		return array(
			array(
				'foo',
				array(
					'layout.' => array(
						'foo.' => array(
							'element' => '<img src="###SRC###" srcset="###SOURCES###" ###PARAMS### ###ALTPARAMS### ###FOOBAR######SELFCLOSINGTAGSLASH###>'
						)
					)
				),
				'<img src="###SRC###" srcset="###SOURCES###" ###PARAMS### ###ALTPARAMS### ###FOOBAR######SELFCLOSINGTAGSLASH###>'
			)

		);
	}

	/**
	 * Assure if a layoutKey and layout is given the selected layout is returned
	 *
	 * @test
	 * @dataProvider getImageTagTemplateReturnTemplateElementIdentifiedByKeyDataProvider
	 * @param string $key
	 * @param array $configuration
	 * @param string $expectation
	 */
	public function getImageTagTemplateReturnTemplateElementIdentifiedByKey($key, $configuration, $expectation) {
		$result = $this->subject->getImageTagTemplate($key, $configuration);
		$this->assertEquals($result, $expectation);
	}

	/**
	 * @return array
	 */
	public function getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefinedDataProvider() {
		return array(
			array(NULL, NULL, NULL),
			array('foo', NULL, NULL),
			array('foo', array('sourceCollection.' => 1), 'bar')
		);
	}

	/**
	 * Make sure the source collection is empty if no valid configuration or source collection is defined
	 *
	 * @test
	 * @dataProvider getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefinedDataProvider
	 * @param string $layoutKey
	 * @param array $configuration
	 * @param string $file
	 */
	public function getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefined($layoutKey, $configuration, $file) {
		$result = $this->subject->getImageSourceCollection($layoutKey, $configuration, $file);
		$this->assertSame($result, '');
	}

	/**
	 * Make sure the generation of subimages calls the generation of the subimages and uses the layout -> source template
	 *
	 * @test
	 */
	public function getImageSourceCollectionRendersDefinedSources() {
		/** @var $cObj \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
		$cObj = $this->getMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer',
			array('stdWrap','getImgResource')
		);
		$cObj->start(array(), 'tt_content');

		$layoutKey = 'test';

		$configuration = array(
			'layoutKey' => 'test',
			'layout.' => array (
				'test.' => array(
					'element' => '<img ###SRC### ###SRCCOLLECTION### ###SELFCLOSINGTAGSLASH###>',
					'source' => '---###SRC###---'
				)
			),
			'sourceCollection.' => array(
				'1.' => array(
					'width' => '200'
				)
			)
		);

		$file = 'testImageName';

		// Avoid calling of stdWrap
		$cObj
			->expects($this->any())
			->method('stdWrap')
			->will($this->returnArgument(0));

		// Avoid calling of imgResource
		$cObj
			->expects($this->exactly(1))
			->method('getImgResource')
			->with($this->equalTo('testImageName'))
			->will($this->returnValue(array(100, 100, NULL, 'bar')));

		$result = $cObj->getImageSourceCollection($layoutKey, $configuration, $file);

		$this->assertEquals('---bar---', $result);
	}

	/**
	 * Data provider for the getImageSourceCollectionRendersDefinedLayoutKeyDefault test
	 *
	 * @return array multi-dimensional array with the second level like this:
	 * @see getImageSourceCollectionRendersDefinedLayoutKeyDefault
	 */
	public function getImageSourceCollectionRendersDefinedLayoutKeyDataDefaultProvider() {
		/**
		 * @see css_styled_content/static/setup.txt
		 */
		$sourceCollectionArray = array(
			'small.' => array(
				'width' => '200',
				'srcsetCandidate' => '600w',
				'mediaQuery' => '(max-device-width: 600px)',
				'dataKey' => 'small',
			),
			'smallRetina.' => array(
				'if.directReturn' => 0,
				'width' => '200',
				'pixelDensity' => '2',
				'srcsetCandidate' => '600w 2x',
				'mediaQuery' => '(max-device-width: 600px) AND (min-resolution: 192dpi)',
				'dataKey' => 'smallRetina',
			)
		);
		return array(
			array(
				'default',
				array(
					'layoutKey' => 'default',
					'layout.' => array (
						'default.' => array(
							'element' => '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ###BORDER######SELFCLOSINGTAGSLASH###>',
							'source' => ''
						)
					),
					'sourceCollection.' => $sourceCollectionArray
				)
			),
		);
	}

	/**
	 * Make sure the generation of subimages renders the expected HTML Code for the sourceset
	 *
	 * @test
	 * @dataProvider getImageSourceCollectionRendersDefinedLayoutKeyDataDefaultProvider
	 * @param string $layoutKey
	 * @param array $configuration
	 */
	public function getImageSourceCollectionRendersDefinedLayoutKeyDefault($layoutKey , $configuration) {
		/** @var $cObj \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
		$cObj = $this->getMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer',
			array('stdWrap','getImgResource')
		);
		$cObj->start(array(), 'tt_content');

		$file = 'testImageName';

		// Avoid calling of stdWrap
		$cObj
			->expects($this->any())
			->method('stdWrap')
			->will($this->returnArgument(0));

		$result = $cObj->getImageSourceCollection($layoutKey, $configuration, $file);

		$this->assertEmpty($result);
	}

	/**
	 * Data provider for the getImageSourceCollectionRendersDefinedLayoutKeyData test
	 *
	 * @return array multi-dimensional array with the second level like this:
	 * @see getImageSourceCollectionRendersDefinedLayoutKeyData
	 */
	public function getImageSourceCollectionRendersDefinedLayoutKeyDataDataProvider() {
		/**
		 * @see css_styled_content/static/setup.txt
		 */
		$sourceCollectionArray = array(
			'small.' => array(
				'width' => '200',
				'srcsetCandidate' => '600w',
				'mediaQuery' => '(max-device-width: 600px)',
				'dataKey' => 'small',
			),
			'smallRetina.' => array(
				'if.directReturn' => 1,
				'width' => '200',
				'pixelDensity' => '2',
				'srcsetCandidate' => '600w 2x',
				'mediaQuery' => '(max-device-width: 600px) AND (min-resolution: 192dpi)',
				'dataKey' => 'smallRetina',
			)
		);
		return array(
			array(
				'srcset',
				array(
					'layoutKey' => 'srcset',
					'layout.' => array (
						'srcset.' => array(
							'element' => '<img src="###SRC###" srcset="###SOURCECOLLECTION###" ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###>',
							'source' => '|*|###SRC### ###SRCSETCANDIDATE###,|*|###SRC### ###SRCSETCANDIDATE###'
						)
					),
					'sourceCollection.' => $sourceCollectionArray
				),
				'xhtml_strict',
				'bar-file.jpg 600w,bar-file.jpg 600w 2x',
			),
			array(
				'picture',
				array(
					'layoutKey' => 'picture',
					'layout.' => array (
						'picture.' => array(
							'element' => '<picture>###SOURCECOLLECTION###<img src="###SRC###" ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###></picture>',
							'source' => '<source src="###SRC###" media="###MEDIAQUERY###"###SELFCLOSINGTAGSLASH###>'
						)
					),
					'sourceCollection.' => $sourceCollectionArray,
				),
				'xhtml_strict',
				'<source src="bar-file.jpg" media="(max-device-width: 600px)" /><source src="bar-file.jpg" media="(max-device-width: 600px) AND (min-resolution: 192dpi)" />',
			),
			array(
				'picture',
				array(
					'layoutKey' => 'picture',
					'layout.' => array (
						'picture.' => array(
							'element' => '<picture>###SOURCECOLLECTION###<img src="###SRC###" ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###></picture>',
							'source' => '<source src="###SRC###" media="###MEDIAQUERY###"###SELFCLOSINGTAGSLASH###>'
						)
					),
					'sourceCollection.' => $sourceCollectionArray,
				),
				'',
				'<source src="bar-file.jpg" media="(max-device-width: 600px)"><source src="bar-file.jpg" media="(max-device-width: 600px) AND (min-resolution: 192dpi)">',
			),
			array(
				'data',
				array(
					'layoutKey' => 'data',
					'layout.' => array (
						'data.' => array(
							'element' => '<img src="###SRC###" ###SOURCECOLLECTION### ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###>',
							'source' => 'data-###DATAKEY###="###SRC###"'
						)
					),
					'sourceCollection.' => $sourceCollectionArray
				),
				'xhtml_strict',
				'data-small="bar-file.jpg"data-smallRetina="bar-file.jpg"',
			),
		);
	}

	/**
	 * Make sure the generation of subimages renders the expected HTML Code for the sourceset
	 *
	 * @test
	 * @dataProvider getImageSourceCollectionRendersDefinedLayoutKeyDataDataProvider
	 * @param string $layoutKey
	 * @param array $configuration
	 * @param string $xhtmlDoctype
	 * @param string $expectedHtml
	 */
	public function getImageSourceCollectionRendersDefinedLayoutKeyData($layoutKey , $configuration, $xhtmlDoctype, $expectedHtml) {
		/** @var $cObj \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
		$cObj = $this->getMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer',
			array('stdWrap','getImgResource')
		);
		$cObj->start(array(), 'tt_content');

		$file = 'testImageName';

		$GLOBALS['TSFE']->xhtmlDoctype = $xhtmlDoctype;

		// Avoid calling of stdWrap
		$cObj
			->expects($this->any())
			->method('stdWrap')
			->will($this->returnArgument(0));

		// Avoid calling of imgResource
		$cObj
			->expects($this->exactly(2))
			->method('getImgResource')
			->with($this->equalTo('testImageName'))
			->will($this->returnValue(array(100, 100, NULL, 'bar-file.jpg')));

		$result = $cObj->getImageSourceCollection($layoutKey, $configuration, $file);

		$this->assertEquals($expectedHtml, $result);
	}

	/**
	 * Make sure the hook in get sourceCollection is called
	 *
	 * @test
	 */
	public function getImageSourceCollectionHookCalled() {
		$this->subject = $this->getAccessibleMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer',
			array('getResourceFactory', 'stdWrap', 'getImgResource')
		);
		$this->subject->start(array(), 'tt_content');

		// Avoid calling stdwrap and getImgResource
		$this->subject->expects($this->any())
			->method('stdWrap')
			->will($this->returnArgument(0));

		$this->subject->expects($this->any())
			->method('getImgResource')
			->will($this->returnValue(array(100, 100, NULL, 'bar-file.jpg')));

		$resourceFactory = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceFactory', array(), array(), '', FALSE);
		$this->subject->expects($this->any())->method('getResourceFactory')->will($this->returnValue($resourceFactory));

		$className = uniqid('tx_coretest_getImageSourceCollectionHookCalled');
		$getImageSourceCollectionHookMock = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectOneSourceCollectionHookInterface', array('getOneSourceCollection'), array(), $className);
		$GLOBALS['T3_VAR']['getUserObj'][$className] = $getImageSourceCollectionHookMock;
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImageSourceCollection'][] = $className;

		$getImageSourceCollectionHookMock
			->expects($this->exactly(1))
			->method('getOneSourceCollection')
			->will($this->returnCallback(array($this, 'isGetOneSourceCollectionCalledCallback')));

		$configuration = array(
			'layoutKey' => 'data',
			'layout.' => array (
				'data.' => array(
					'element' => '<img src="###SRC###" ###SOURCECOLLECTION### ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###>',
					'source' => 'data-###DATAKEY###="###SRC###"'
				)
			),
			'sourceCollection.' => array(
				'small.' => array(
					'width' => '200',
					'srcsetCandidate' => '600w',
					'mediaQuery' => '(max-device-width: 600px)',
					'dataKey' => 'small',
				),
			),
		);

		$result = $this->subject->getImageSourceCollection('data', $configuration, uniqid('testImage-'));

		$this->assertSame($result, 'isGetOneSourceCollectionCalledCallback');
	}

	/**
	 * Handles the arguments that have been sent to the getImgResource hook.
	 *
	 * @return 	string
	 * @see getImageSourceCollectionHookCalled
	 */
	public function isGetOneSourceCollectionCalledCallback() {
		list($sourceRenderConfiguration, $sourceConfiguration, $oneSourceCollection, $parent) = func_get_args();
		$this->assertTrue(is_array($sourceRenderConfiguration));
		$this->assertTrue(is_array($sourceConfiguration));
		return 'isGetOneSourceCollectionCalledCallback';
	}

	/**
	 * @param string $expected The expected URL
	 * @param string $url The URL to parse and manipulate
	 * @param array $configuration The configuration array
	 * @test
	 * @dataProvider forceAbsoluteUrlReturnsCorrectAbsoluteUrlDataProvider
	 */
	public function forceAbsoluteUrlReturnsCorrectAbsoluteUrl($expected, $url, array $configuration) {
		// Force hostname
		$this->subject->expects($this->any())->method('getEnvironmentVariable')->will($this->returnValueMap(
			array(
				array('HTTP_HOST', 'localhost'),
				array('TYPO3_SITE_PATH', '/'),
			)
		));

		$this->assertEquals($expected, $this->subject->_call('forceAbsoluteUrl', $url, $configuration));
	}

	/**
	 * @return array The test data for forceAbsoluteUrlReturnsAbsoluteUrl
	 */
	public function forceAbsoluteUrlReturnsCorrectAbsoluteUrlDataProvider() {
		return array(
			'Missing forceAbsoluteUrl leaves URL untouched' => array(
				'foo',
				'foo',
				array()
			),
			'Absolute URL stays unchanged' => array(
				'http://example.org/',
				'http://example.org/',
				array(
					'forceAbsoluteUrl' => '1'
				)
			),
			'Absolute URL stays unchanged 2' => array(
				'http://example.org/resource.html',
				'http://example.org/resource.html',
				array(
					'forceAbsoluteUrl' => '1'
				)
			),
			'Scheme and host w/o ending slash stays unchanged' => array(
				'http://example.org',
				'http://example.org',
				array(
					'forceAbsoluteUrl' => '1'
				)
			),
			'Scheme can be forced' => array(
				'typo3://example.org',
				'http://example.org',
				array(
					'forceAbsoluteUrl' => '1',
					'forceAbsoluteUrl.' => array(
						'scheme' => 'typo3'
					)
				)
			),
			'Scheme can be forced with relative path' => array(
				'typo3://localhost/fileadmin/dummy.txt',
				'/fileadmin/dummy.txt', // this leading slash is weird, but we need it to really get an absolute link
				array(
					'forceAbsoluteUrl' => '1',
					'forceAbsoluteUrl.' => array(
						'scheme' => 'typo3'
					)
				)
			),
		);
	}

	/**
	 * @test
	 * @expectedException \LogicException
	 * @expectedExceptionCode 1414513947
	 */
	public function renderingContentObjectThrowsException() {
		$contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();
		$this->subject->render($contentObjectFixture, array());
	}

	/**
	 * @test
	 */
	public function exceptionHandlerIsEnabledByDefaultInProductionContext() {
		$backupApplicationContext = GeneralUtility::getApplicationContext();
		Fixtures\GeneralUtilityFixture::setApplicationContext(new ApplicationContext('Production'));

		$contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();
		$this->subject->render($contentObjectFixture, array());

		Fixtures\GeneralUtilityFixture::setApplicationContext($backupApplicationContext);
	}

	/**
	 * @test
	 */
	public function renderingContentObjectDoesNotThrowExceptionIfExceptionHandlerIsConfiguredLocally() {
		$contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

		$configuration = array(
			'exceptionHandler' => '1'
		);
		$this->subject->render($contentObjectFixture, $configuration);
	}

	/**
	 * @test
	 */
	public function renderingContentObjectDoesNotThrowExceptionIfExceptionHandlerIsConfiguredGlobally() {
		$contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

		$this->typoScriptFrontendControllerMock->config['config']['contentObjectExceptionHandler'] = '1';
		$this->subject->render($contentObjectFixture, array());
	}

	/**
	 * @test
	 * @expectedException \LogicException
	 * @expectedExceptionCode 1414513947
	 */
	public function globalExceptionHandlerConfigurationCanBeOverriddenByLocalConfiguration() {
		$contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

		$this->typoScriptFrontendControllerMock->config['config']['contentObjectExceptionHandler'] = '1';
		$configuration = array(
			'exceptionHandler' => '0'
		);
		$this->subject->render($contentObjectFixture, $configuration);
	}

	/**
	 * @test
	 */
	public function renderedErrorMessageCanBeCustomized() {
		$contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

		$configuration = array(
			'exceptionHandler' => '1',
			'exceptionHandler.' => array(
				'errorMessage' => 'New message for testing',
			)
		);

		$this->assertSame('New message for testing', $this->subject->render($contentObjectFixture, $configuration));
	}

	/**
	 * @test
	 */
	public function localConfigurationOverridesGlobalConfiguration() {
		$contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

		$this->typoScriptFrontendControllerMock
			->config['config']['contentObjectExceptionHandler.'] = array(
				'errorMessage' => 'Global message for testing',
			);
		$configuration = array(
			'exceptionHandler' => '1',
			'exceptionHandler.' => array(
				'errorMessage' => 'New message for testing',
			)
		);

		$this->assertSame('New message for testing', $this->subject->render($contentObjectFixture, $configuration));
	}

	/**
	 * @test
	 * @expectedException \LogicException
	 * @expectedExceptionCode 1414513947
	 */
	public function specificExceptionsCanBeIgnoredByExceptionHandler() {
		$contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

		$configuration = array(
			'exceptionHandler' => '1',
			'exceptionHandler.' => array(
				'ignoreCodes.' => array('10.' => '1414513947'),
			)
		);

		$this->subject->render($contentObjectFixture, $configuration);
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject | AbstractContentObject
	 */
	protected function createContentObjectThrowingExceptionFixture() {
		$contentObjectFixture = $this->getMock(AbstractContentObject::class, array(), array($this->subject));
		$contentObjectFixture->expects($this->once())
			->method('render')
			->willReturnCallback(function() {
				throw new \LogicException('Exception during rendering', 1414513947);
			});
		return $contentObjectFixture;
	}
}
