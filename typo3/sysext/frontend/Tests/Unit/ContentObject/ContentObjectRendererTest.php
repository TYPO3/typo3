<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

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

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\TimeTracker\NullTimeTracker;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Fixtures\PageRepositoryFixture;

/**
 * Testcase for TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
 */
class ContentObjectRendererTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var string
     */
    protected $currentLocale;

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected $subject = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TypoScriptFrontendController|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $typoScriptFrontendControllerMock = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TemplateService
     */
    protected $templateServiceMock = null;

    /**
     * Default content object name -> class name map, shipped with TYPO3 CMS
     *
     * @var array
     */
    protected $contentObjectMap = [
        'TEXT'             => \TYPO3\CMS\Frontend\ContentObject\TextContentObject::class,
        'CASE'             => \TYPO3\CMS\Frontend\ContentObject\CaseContentObject::class,
        'COBJ_ARRAY'       => \TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayContentObject::class,
        'COA'              => \TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayContentObject::class,
        'COA_INT'          => \TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayInternalContentObject::class,
        'USER'             => \TYPO3\CMS\Frontend\ContentObject\UserContentObject::class,
        'USER_INT'         => \TYPO3\CMS\Frontend\ContentObject\UserInternalContentObject::class,
        'FILE'             => \TYPO3\CMS\Frontend\ContentObject\FileContentObject::class,
        'FILES'            => \TYPO3\CMS\Frontend\ContentObject\FilesContentObject::class,
        'IMAGE'            => \TYPO3\CMS\Frontend\ContentObject\ImageContentObject::class,
        'IMG_RESOURCE'     => \TYPO3\CMS\Frontend\ContentObject\ImageResourceContentObject::class,
        'CONTENT'          => \TYPO3\CMS\Frontend\ContentObject\ContentContentObject::class,
        'RECORDS'          => \TYPO3\CMS\Frontend\ContentObject\RecordsContentObject::class,
        'HMENU'            => \TYPO3\CMS\Frontend\ContentObject\HierarchicalMenuContentObject::class,
        'CASEFUNC'         => \TYPO3\CMS\Frontend\ContentObject\CaseContentObject::class,
        'LOAD_REGISTER'    => \TYPO3\CMS\Frontend\ContentObject\LoadRegisterContentObject::class,
        'RESTORE_REGISTER' => \TYPO3\CMS\Frontend\ContentObject\RestoreRegisterContentObject::class,
        'TEMPLATE'         => \TYPO3\CMS\Frontend\ContentObject\TemplateContentObject::class,
        'FLUIDTEMPLATE'    => \TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject::class,
        'SVG'              => \TYPO3\CMS\Frontend\ContentObject\ScalableVectorGraphicsContentObject::class,
        'EDITPANEL'        => \TYPO3\CMS\Frontend\ContentObject\EditPanelContentObject::class
    ];

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->currentLocale = setlocale(LC_NUMERIC, 0);

        $this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
        $this->createMockedLoggerAndLogManager();

        $this->templateServiceMock = $this->getMock(TemplateService::class, ['getFileName', 'linkData']);
        $pageRepositoryMock = $this->getMock(PageRepositoryFixture::class, ['getRawRecord']);

        $this->typoScriptFrontendControllerMock = $this->getAccessibleMock(TypoScriptFrontendController::class, ['dummy'], [], '', false);
        $this->typoScriptFrontendControllerMock->tmpl = $this->templateServiceMock;
        $this->typoScriptFrontendControllerMock->config = [];
        $this->typoScriptFrontendControllerMock->page = [];
        $this->typoScriptFrontendControllerMock->sys_page = $pageRepositoryMock;
        $this->typoScriptFrontendControllerMock->csConvObj = new CharsetConverter();
        $this->typoScriptFrontendControllerMock->renderCharset = 'utf-8';
        $GLOBALS['TSFE'] = $this->typoScriptFrontendControllerMock;
        $GLOBALS['TT'] = new NullTimeTracker();
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, []);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['t3lib_cs_utils'] = 'mbstring';

        $this->subject = $this->getAccessibleMock(
            \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class,
            ['getResourceFactory', 'getEnvironmentVariable'],
            [$this->typoScriptFrontendControllerMock]
        );
        $this->subject->setContentObjectClassMap($this->contentObjectMap);
        $this->subject->start([], 'tt_content');
    }

    protected function tearDown()
    {
        setlocale(LC_NUMERIC, $this->currentLocale);
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    //////////////////////
    // Utility functions
    //////////////////////

    /**
     * Avoid logging to the file system (file writer is currently the only configured writer)
     */
    protected function createMockedLoggerAndLogManager()
    {
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
    protected function handleCharset($charset, &$subject, &$expected)
    {
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
    public function getImgResourceCallsGetImgResourcePostProcessHook()
    {
        $this->templateServiceMock
            ->expects($this->atLeastOnce())
            ->method('getFileName')
            ->with('typo3/clear.gif')
            ->will($this->returnValue('typo3/clear.gif'));

        $resourceFactory = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceFactory::class, [], [], '', false);
        $this->subject->expects($this->any())->method('getResourceFactory')->will($this->returnValue($resourceFactory));

        $className = $this->getUniqueId('tx_coretest');
        $getImgResourceHookMock = $this->getMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectGetImageResourceHookInterface::class, ['getImgResourcePostProcess'], [], $className);
        $getImgResourceHookMock
            ->expects($this->once())
            ->method('getImgResourcePostProcess')
            ->will($this->returnCallback([$this, 'isGetImgResourceHookCalledCallback']));
        $getImgResourceHookObjects = [$getImgResourceHookMock];
        $this->subject->_setRef('getImgResourceHookObjects', $getImgResourceHookObjects);
        $this->subject->getImgResource('typo3/clear.gif', []);
    }

    /**
     * Handles the arguments that have been sent to the getImgResource hook.
     *
     * @return 	array
     * @see getImgResourceHookGetsCalled
     */
    public function isGetImgResourceHookCalledCallback()
    {
        list($file, $fileArray, $imageResource, $parent) = func_get_args();
        $this->assertEquals('typo3/clear.gif', $file);
        $this->assertEquals('typo3/clear.gif', $imageResource['origFile']);
        $this->assertTrue(is_array($fileArray));
        $this->assertTrue($parent instanceof \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer);
        return $imageResource;
    }

    //////////////////////////////////////
    // Tests concerning getContentObject
    //////////////////////////////////////

    public function getContentObjectValidContentObjectsDataProvider()
    {
        $dataProvider = [];
        foreach ($this->contentObjectMap as $name => $className) {
            $dataProvider[] = [$name, $className];
        }
        return $dataProvider;
    }

    /**
     * @test
     * @dataProvider getContentObjectValidContentObjectsDataProvider
     * @param string $name TypoScript name of content object
     * @param string $fullClassName Expected class name
     */
    public function getContentObjectCallsMakeInstanceForNewContentObjectInstance($name, $fullClassName)
    {
        $contentObjectInstance = $this->getMock($fullClassName, [], [], '', false);
        \TYPO3\CMS\Core\Utility\GeneralUtility::addInstance($fullClassName, $contentObjectInstance);
        $this->assertSame($contentObjectInstance, $this->subject->getContentObject($name));
    }

    /////////////////////////////////////////
    // Tests concerning getQueryArguments()
    /////////////////////////////////////////
    /**
     * @test
     */
    public function getQueryArgumentsExcludesParameters()
    {
        $this->subject->expects($this->any())->method('getEnvironmentVariable')->with($this->equalTo('QUERY_STRING'))->will(
            $this->returnValue('key1=value1&key2=value2&key3[key31]=value31&key3[key32][key321]=value321&key3[key32][key322]=value322')
        );
        $getQueryArgumentsConfiguration = [];
        $getQueryArgumentsConfiguration['exclude'] = [];
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
    public function getQueryArgumentsExcludesGetParameters()
    {
        $_GET = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'key31' => 'value31',
                'key32' => [
                    'key321' => 'value321',
                    'key322' => 'value322'
                ]
            ]
        ];
        $getQueryArgumentsConfiguration = [];
        $getQueryArgumentsConfiguration['method'] = 'GET';
        $getQueryArgumentsConfiguration['exclude'] = [];
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
    public function getQueryArgumentsOverrulesSingleParameter()
    {
        $this->subject->expects($this->any())->method('getEnvironmentVariable')->with($this->equalTo('QUERY_STRING'))->will(
            $this->returnValue('key1=value1')
        );
        $getQueryArgumentsConfiguration = [];
        $overruleArguments = [
            // Should be overridden
            'key1' => 'value1Overruled',
            // Shouldn't be set: Parameter doesn't exist in source array and is not forced
            'key2' => 'value2Overruled'
        ];
        $expectedResult = '&key1=value1Overruled';
        $actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getQueryArgumentsOverrulesMultiDimensionalParameters()
    {
        $_POST = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'key31' => 'value31',
                'key32' => [
                    'key321' => 'value321',
                    'key322' => 'value322'
                ]
            ]
        ];
        $getQueryArgumentsConfiguration = [];
        $getQueryArgumentsConfiguration['method'] = 'POST';
        $getQueryArgumentsConfiguration['exclude'] = [];
        $getQueryArgumentsConfiguration['exclude'][] = 'key1';
        $getQueryArgumentsConfiguration['exclude'][] = 'key3[key31]';
        $getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key321]';
        $getQueryArgumentsConfiguration['exclude'] = implode(',', $getQueryArgumentsConfiguration['exclude']);
        $overruleArguments = [
            // Should be overriden
            'key2' => 'value2Overruled',
            'key3' => [
                'key32' => [
                    // Shouldn't be set: Parameter is excluded and not forced
                    'key321' => 'value321Overruled',
                    // Should be overriden: Parameter is not excluded
                    'key322' => 'value322Overruled',
                    // Shouldn't be set: Parameter doesn't exist in source array and is not forced
                    'key323' => 'value323Overruled'
                ]
            ]
        ];
        $expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key2=value2Overruled&key3[key32][key322]=value322Overruled');
        $actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getQueryArgumentsOverrulesMultiDimensionalForcedParameters()
    {
        $this->subject->expects($this->any())->method('getEnvironmentVariable')->with($this->equalTo('QUERY_STRING'))->will(
            $this->returnValue('key1=value1&key2=value2&key3[key31]=value31&key3[key32][key321]=value321&key3[key32][key322]=value322')
        );
        $_POST = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'key31' => 'value31',
                'key32' => [
                    'key321' => 'value321',
                    'key322' => 'value322'
                ]
            ]
        ];
        $getQueryArgumentsConfiguration = [];
        $getQueryArgumentsConfiguration['exclude'] = [];
        $getQueryArgumentsConfiguration['exclude'][] = 'key1';
        $getQueryArgumentsConfiguration['exclude'][] = 'key3[key31]';
        $getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key321]';
        $getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key322]';
        $getQueryArgumentsConfiguration['exclude'] = implode(',', $getQueryArgumentsConfiguration['exclude']);
        $overruleArguments = [
            // Should be overriden
            'key2' => 'value2Overruled',
            'key3' => [
                'key32' => [
                    // Should be set: Parameter is excluded but forced
                    'key321' => 'value321Overruled',
                    // Should be set: Parameter doesn't exist in source array but is forced
                    'key323' => 'value323Overruled'
                ]
            ]
        ];
        $expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key2=value2Overruled&key3[key32][key321]=value321Overruled&key3[key32][key323]=value323Overruled');
        $actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments, true);
        $this->assertEquals($expectedResult, $actualResult);
        $getQueryArgumentsConfiguration['method'] = 'POST';
        $actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration, $overruleArguments, true);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getQueryArgumentsWithMethodPostGetMergesParameters()
    {
        $_POST = [
            'key1' => 'POST1',
            'key2' => 'POST2',
            'key3' => [
                'key31' => 'POST31',
                'key32' => 'POST32',
                'key33' => [
                    'key331' => 'POST331',
                    'key332' => 'POST332',
                ]
            ]
        ];
        $_GET = [
            'key2' => 'GET2',
            'key3' => [
                'key32' => 'GET32',
                'key33' => [
                    'key331' => 'GET331',
                ]
            ]
        ];
        $getQueryArgumentsConfiguration = [];
        $getQueryArgumentsConfiguration['method'] = 'POST,GET';
        $expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key1=POST1&key2=GET2&key3[key31]=POST31&key3[key32]=GET32&key3[key33][key331]=GET331&key3[key33][key332]=POST332');
        $actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getQueryArgumentsWithMethodGetPostMergesParameters()
    {
        $_GET = [
            'key1' => 'GET1',
            'key2' => 'GET2',
            'key3' => [
                'key31' => 'GET31',
                'key32' => 'GET32',
                'key33' => [
                    'key331' => 'GET331',
                    'key332' => 'GET332',
                ]
            ]
        ];
        $_POST = [
            'key2' => 'POST2',
            'key3' => [
                'key32' => 'POST32',
                'key33' => [
                    'key331' => 'POST331',
                ]
            ]
        ];
        $getQueryArgumentsConfiguration = [];
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
    private function rawUrlEncodeSquareBracketsInUrl($string)
    {
        return str_replace(['[', ']'], ['%5B', '%5D'], $string);
    }

    //////////////////////////
    // Tests concerning crop
    //////////////////////////
    /**
     * @test
     */
    public function cropIsMultibyteSafe()
    {
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
    public function cropHtmlDataProvider()
    {
        $plainText = 'Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j implemented the original version of the crop function.';
        $textWithMarkup = '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a>' . ' implemented</strong> the original version of the crop function.';
        $textWithEntities = 'Kasper Sk&aring;rh&oslash;j implemented the; original ' . 'version of the crop function.';
        $charsets = ['iso-8859-1', 'utf-8', 'ascii', 'big5'];
        $data = [];
        foreach ($charsets as $charset) {
            $data = array_merge($data, [
                $charset . ' plain text; 11|...' => [
                    '11|...',
                    $plainText,
                    'Kasper Sk' . chr(229) . 'r...',
                    $charset
                ],
                $charset . ' plain text; -58|...' => [
                    '-58|...',
                    $plainText,
                    '...h' . chr(248) . 'j implemented the original version of the crop function.',
                    $charset
                ],
                $charset . ' plain text; 4|...|1' => [
                    '4|...|1',
                    $plainText,
                    'Kasp...',
                    $charset
                ],
                $charset . ' plain text; 20|...|1' => [
                    '20|...|1',
                    $plainText,
                    'Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j...',
                    $charset
                ],
                $charset . ' plain text; -5|...|1' => [
                    '-5|...|1',
                    $plainText,
                    '...tion.',
                    $charset
                ],
                $charset . ' plain text; -49|...|1' => [
                    '-49|...|1',
                    $plainText,
                    '...the original version of the crop function.',
                    $charset
                ],
                $charset . ' text with markup; 11|...' => [
                    '11|...',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'r...</a></strong>',
                    $charset
                ],
                $charset . ' text with markup; 13|...' => [
                    '13|...',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . '...</a></strong>',
                    $charset
                ],
                $charset . ' text with markup; 14|...' => [
                    '14|...',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
                    $charset
                ],
                $charset . ' text with markup; 15|...' => [
                    '15|...',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a> ...</strong>',
                    $charset
                ],
                $charset . ' text with markup; 29|...' => [
                    '29|...',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> th...',
                    $charset
                ],
                $charset . ' text with markup; -58|...' => [
                    '-58|...',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">...h' . chr(248) . 'j</a> implemented</strong> the original version of the crop function.',
                    $charset
                ],
                $charset . ' text with markup 4|...|1' => [
                    '4|...|1',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">Kasp...</a></strong>',
                    $charset
                ],
                $charset . ' text with markup; 11|...|1' => [
                    '11|...|1',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">Kasper...</a></strong>',
                    $charset
                ],
                $charset . ' text with markup; 13|...|1' => [
                    '13|...|1',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">Kasper...</a></strong>',
                    $charset
                ],
                $charset . ' text with markup; 14|...|1' => [
                    '14|...|1',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
                    $charset
                ],
                $charset . ' text with markup; 15|...|1' => [
                    '15|...|1',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
                    $charset
                ],
                $charset . ' text with markup; 29|...|1' => [
                    '29|...|1',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong>...',
                    $charset
                ],
                $charset . ' text with markup; -66|...|1' => [
                    '-66|...|1',
                    $textWithMarkup,
                    '<strong><a href="mailto:kasper@typo3.org">...Sk' . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> the original version of the crop function.',
                    $charset
                ],
                $charset . ' text with entities 9|...' => [
                    '9|...',
                    $textWithEntities,
                    'Kasper Sk...',
                    $charset
                ],
                $charset . ' text with entities 10|...' => [
                    '10|...',
                    $textWithEntities,
                    'Kasper Sk&aring;...',
                    $charset
                ],
                $charset . ' text with entities 11|...' => [
                    '11|...',
                    $textWithEntities,
                    'Kasper Sk&aring;r...',
                    $charset
                ],
                $charset . ' text with entities 13|...' => [
                    '13|...',
                    $textWithEntities,
                    'Kasper Sk&aring;rh&oslash;...',
                    $charset
                ],
                $charset . ' text with entities 14|...' => [
                    '14|...',
                    $textWithEntities,
                    'Kasper Sk&aring;rh&oslash;j...',
                    $charset
                ],
                $charset . ' text with entities 15|...' => [
                    '15|...',
                    $textWithEntities,
                    'Kasper Sk&aring;rh&oslash;j ...',
                    $charset
                ],
                $charset . ' text with entities 16|...' => [
                    '16|...',
                    $textWithEntities,
                    'Kasper Sk&aring;rh&oslash;j i...',
                    $charset
                ],
                $charset . ' text with entities -57|...' => [
                    '-57|...',
                    $textWithEntities,
                    '...j implemented the; original version of the crop function.',
                    $charset
                ],
                $charset . ' text with entities -58|...' => [
                    '-58|...',
                    $textWithEntities,
                    '...&oslash;j implemented the; original version of the crop function.',
                    $charset
                ],
                $charset . ' text with entities -59|...' => [
                    '-59|...',
                    $textWithEntities,
                    '...h&oslash;j implemented the; original version of the crop function.',
                    $charset
                ],
                $charset . ' text with entities 4|...|1' => [
                    '4|...|1',
                    $textWithEntities,
                    'Kasp...',
                    $charset
                ],
                $charset . ' text with entities 9|...|1' => [
                    '9|...|1',
                    $textWithEntities,
                    'Kasper...',
                    $charset
                ],
                $charset . ' text with entities 10|...|1' => [
                    '10|...|1',
                    $textWithEntities,
                    'Kasper...',
                    $charset
                ],
                $charset . ' text with entities 11|...|1' => [
                    '11|...|1',
                    $textWithEntities,
                    'Kasper...',
                    $charset
                ],
                $charset . ' text with entities 13|...|1' => [
                    '13|...|1',
                    $textWithEntities,
                    'Kasper...',
                    $charset
                ],
                $charset . ' text with entities 14|...|1' => [
                    '14|...|1',
                    $textWithEntities,
                    'Kasper Sk&aring;rh&oslash;j...',
                    $charset
                ],
                $charset . ' text with entities 15|...|1' => [
                    '15|...|1',
                    $textWithEntities,
                    'Kasper Sk&aring;rh&oslash;j...',
                    $charset
                ],
                $charset . ' text with entities 16|...|1' => [
                    '16|...|1',
                    $textWithEntities,
                    'Kasper Sk&aring;rh&oslash;j...',
                    $charset
                ],
                $charset . ' text with entities -57|...|1' => [
                    '-57|...|1',
                    $textWithEntities,
                    '...implemented the; original version of the crop function.',
                    $charset
                ],
                $charset . ' text with entities -58|...|1' => [
                    '-58|...|1',
                    $textWithEntities,
                    '...implemented the; original version of the crop function.',
                    $charset
                ],
                $charset . ' text with entities -59|...|1' => [
                    '-59|...|1',
                    $textWithEntities,
                    '...implemented the; original version of the crop function.',
                    $charset
                ],
                $charset . ' text with dash in html-element 28|...|1' => [
                    '28|...|1',
                    'Some text with a link to <link email.address@example.org - mail "Open email window">my email.address@example.org</link> and text after it',
                    'Some text with a link to <link email.address@example.org - mail "Open email window">my...</link>',
                    $charset
                ],
                $charset . ' html elements with dashes in attributes' => [
                    '9',
                    '<em data-foo="x">foobar</em>foobaz',
                    '<em data-foo="x">foobar</em>foo',
                    $charset
                ],
                $charset . ' html elements with iframe embedded 24|...|1' => [
                    '24|...|1',
                    'Text with iframe <iframe src="//what.ever/"></iframe> and text after it',
                    'Text with iframe <iframe src="//what.ever/"></iframe> and...',
                    $charset
                ],
                $charset . ' html elements with script tag embedded 24|...|1' => [
                    '24|...|1',
                    'Text with script <script>alert(\'foo\');</script> and text after it',
                    'Text with script <script>alert(\'foo\');</script> and...',
                    $charset
                ],
            ]);
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
    public function cropHtmlWithDataProvider($settings, $subject, $expected, $charset)
    {
        $this->handleCharset($charset, $subject, $expected);
        $this->assertEquals($expected, $this->subject->cropHTML($subject, $settings), 'cropHTML failed with settings: "' . $settings . '" and charset "' . $charset . '"');
    }

    /**
     * Checks if stdWrap.cropHTML works with a complex content with many tags. Currently cropHTML
     * counts multiple invisible characters not as one (as the browser will output the content).
     *
     * @test
     */
    public function cropHtmlWorksWithComplexContent()
    {
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
     * Checks if stdWrap.cropHTML handles linebreaks correctly (by ignoring them)
     *
     * @test
     */
    public function cropHtmlWorksWithLinebreaks()
    {
        $subject = "Lorem ipsum dolor sit amet,\nconsetetur sadipscing elitr,\nsed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam";
        $expected = "Lorem ipsum dolor sit amet,\nconsetetur sadipscing elitr,\nsed diam nonumy eirmod tempor invidunt ut labore et dolore magna";
        $result = $this->subject->cropHTML($subject, '121');
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function stdWrap_roundDataProvider()
    {
        return [
            'rounding off without any configuration' => [
                1.123456789,
                [],
                1
            ],
            'rounding up without any configuration' => [
                1.523456789,
                [],
                2
            ],
            'regular rounding (off) to two decimals' => [
                0.123456789,
                [
                    'decimals' => 2
                ],
                0.12
            ],
            'regular rounding (up) to two decimals' => [
                0.1256789,
                [
                    'decimals' => 2
                ],
                0.13
            ],
            'rounding up to integer with type ceil' => [
                0.123456789,
                [
                    'roundType' => 'ceil'
                ],
                1
            ],
            'rounding down to integer with type floor' => [
                2.3481,
                [
                    'roundType' => 'floor'
                ],
                2
            ]
        ];
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
    public function stdWrap_round($float, $conf, $expected)
    {
        $conf = [
            'round.' => $conf
        ];
        $result = $this->subject->stdWrap_round($float, $conf);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function stdWrap_numberFormatDataProvider()
    {
        return [
            'testing decimals' => [
                0.8,
                [
                    'numberFormat.' => [
                        'decimals' => 2
                    ],
                ],
                '0.80'
            ],
            'testing decimals with input as string' => [
                '0.8',
                [
                    'numberFormat.' => [
                        'decimals' => 2
                    ],
                ],
                '0.80'
            ],
            'testing dec_point' => [
                0.8,
                [
                    'numberFormat.' => [
                        'decimals' => 1,
                        'dec_point' => ','
                    ],
                ],
                '0,8'
            ],
            'testing thousands_sep' => [
                999.99,
                [
                    'numberFormat.' => [
                        'decimals' => 0,
                        'thousands_sep.' => [
                            'char' => 46
                        ],
                    ],
                ],
                '1.000'
            ],
            'testing mixture' => [
                1281731.45,
                [
                    'numberFormat.' => [
                        'decimals' => 1,
                        'dec_point.' => [
                            'char' => 44
                        ],
                        'thousands_sep.' => [
                            'char' => 46
                        ],
                    ],
                ],
                '1.281.731,5'
            ]
        ];
    }

    /**
     * Test for the stdWrap function "round"
     *
     * @param float $float
     * @param array $conf
     * @param string $expected
     * @return void
     * @dataProvider stdWrap_numberFormatDataProvider
     * @test
     */
    public function stdWrap_numberFormat($float, $conf, $expected)
    {
        $result = $this->subject->stdWrap_numberFormat($float, $conf);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function stdWrap_expandListDataProvider()
    {
        return [
            'numbers' => [
                '1,2,3',
                '1,2,3',
            ],
            'range' => [
                '3-5',
                '3,4,5',
            ],
            'numbers and range' => [
                '1,3-5,7',
                '1,3,4,5,7',
            ],
        ];
    }

    /**
     * Test for the stdWrap function "expandList"
     *
     * @param string $content
     * @param string $expected
     *
     * @dataProvider stdWrap_expandListDataProvider
     * @test
     */
    public function stdWrap_expandList($content, $expected)
    {
        $result = $this->subject->stdWrap_expandList($content);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function stdWrap_trimDataProvider()
    {
        return [
            'trimstring' => [
                'trimstring',
                'trimstring',
            ],
            'trim string with space inside' => [
                'trim string',
                'trim string',
            ],
            'trim string with space at the begin and end' => [
                ' trim string ',
                'trim string',
            ],
        ];
    }

    /**
     * Test for the stdWrap function "trim"
     *
     * @param string $content
     * @param string $expected
     *
     * @dataProvider stdWrap_trimDataProvider
     * @test
     */
    public function stdWrap_trim($content, $expected)
    {
        $result = $this->subject->stdWrap_trim($content);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function stdWrap_intvalDataProvider()
    {
        return [
            'number' => [
                '123',
                123,
            ],
            'float' => [
                '123.45',
                123,
            ],
            'string' => [
                'string',
                0,
            ],
            'zero' => [
                '0',
                0,
            ],
            'empty' => [
                '',
                0,
            ],
            'NULL' => [
                null,
                0,
            ],
            'bool TRUE' => [
                true,
                1,
            ],
            'bool FALSE' => [
                false,
                0,
            ],
        ];
    }

    /**
     * Test for the stdWrap function "intval"
     *
     * @param string $content
     * @param int $expected
     *
     * @dataProvider stdWrap_intvalDataProvider
     * @test
     */
    public function stdWrap_intval($content, $expected)
    {
        $result = $this->subject->stdWrap_intval($content);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function stdWrap_strPadDataProvider()
    {
        return [
            'pad string with default settings and length 10' => [
                'Alien',
                [
                    'length' => '10',
                ],
                'Alien     ',
            ],
            'pad string with padWith -= and type left and length 10' => [
                'Alien',
                [
                    'length' => '10',
                    'padWith' => '-=',
                    'type' => 'left',
                ],
                '-=-=-Alien',
            ],
            'pad string with padWith _ and type both and length 10' => [
                'Alien',
                [
                    'length' => '10',
                    'padWith' => '_',
                    'type' => 'both',
                ],
                '__Alien___',
            ],
            'pad string with padWith 0 and type both and length 10' => [
                'Alien',
                [
                    'length' => '10',
                    'padWith' => '0',
                    'type' => 'both',
                ],
                '00Alien000',
            ],
            'pad string with padWith ___ and type both and length 6' => [
                'Alien',
                [
                    'length' => '6',
                    'padWith' => '___',
                    'type' => 'both',
                ],
                'Alien_',
            ],
            'pad string with padWith _ and type both and length 12, using stdWrap for length' => [
                'Alien',
                [
                    'length' => '1',
                    'length.' => [
                        'wrap' => '|2',
                    ],
                    'padWith' => '_',
                    'type' => 'both',
                ],
                '___Alien____',
            ],
            'pad string with padWith _ and type both and length 12, using stdWrap for padWidth' => [
                'Alien',
                [
                    'length' => '12',
                    'padWith' => '_',
                    'padWith.' => [
                        'wrap' => '-|=',
                    ],
                    'type' => 'both',
                ],
                '-_=Alien-_=-',
            ],
            'pad string with padWith _ and type both and length 12, using stdWrap for type' => [
                'Alien',
                [
                    'length' => '12',
                    'padWith' => '_',
                    'type' => 'both',
                    // make type become "left"
                    'type.' => [
                        'substring' => '2,1',
                        'wrap' => 'lef|',
                    ],
                ],
                '_______Alien',
            ],
        ];
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
    public function stdWrap_strPad($content, $conf, $expected)
    {
        $conf = [
            'strPad.' => $conf
        ];
        $result = $this->subject->stdWrap_strPad($content, $conf);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for the hash test
     *
     * @return array multi-dimensional array with the second level like this:
     * @see hash
     */
    public function hashDataProvider()
    {
        $data = [
            'testing md5' => [
                'joh316',
                [
                    'hash' => 'md5'
                ],
                'bacb98acf97e0b6112b1d1b650b84971'
            ],
            'testing sha1' => [
                'joh316',
                [
                    'hash' => 'sha1'
                ],
                '063b3d108bed9f88fa618c6046de0dccadcf3158'
            ],
            'testing non-existing hashing algorithm' => [
                'joh316',
                [
                    'hash' => 'non-existing'
                ],
                ''
            ],
            'testing stdWrap capability' => [
                'joh316',
                [
                    'hash.' => [
                        'cObject' => 'TEXT',
                        'cObject.' => [
                            'value' => 'md5'
                        ]
                    ]
                ],
                'bacb98acf97e0b6112b1d1b650b84971'
            ]
        ];
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
    public function stdWrap_hash($text, array $conf, $expected)
    {
        $result = $this->subject->stdWrap_hash($text, $conf);
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function recursiveStdWrapProperlyRendersBasicString()
    {
        $stdWrapConfiguration = [
            'noTrimWrap' => '|| 123|',
            'stdWrap.' => [
                'wrap' => '<b>|</b>'
            ]
        ];
        $this->assertSame(
            '<b>Test</b> 123',
            $this->subject->stdWrap('Test', $stdWrapConfiguration)
        );
    }

    /**
     * @test
     */
    public function recursiveStdWrapIsOnlyCalledOnce()
    {
        $stdWrapConfiguration = [
            'append' => 'TEXT',
            'append.' => [
                'data' => 'register:Counter'
            ],
            'stdWrap.' => [
                'append' => 'LOAD_REGISTER',
                'append.' => [
                    'Counter.' => [
                        'prioriCalc' => 'intval',
                        'cObject' => 'TEXT',
                        'cObject.' => [
                            'data' => 'register:Counter',
                            'wrap' => '|+1',
                        ]
                    ]
                ]
            ]
        ];
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
    public function numberFormatDataProvider()
    {
        $data = [
            'testing decimals' => [
                0.8,
                [
                    'decimals' => 2
                ],
                '0.80'
            ],
            'testing decimals with input as string' => [
                '0.8',
                [
                    'decimals' => 2
                ],
                '0.80'
            ],
            'testing dec_point' => [
                0.8,
                [
                    'decimals' => 1,
                    'dec_point' => ','
                ],
                '0,8'
            ],
            'testing thousands_sep' => [
                999.99,
                [
                    'decimals' => 0,
                    'thousands_sep.' => [
                        'char' => 46
                    ]
                ],
                '1.000'
            ],
            'testing mixture' => [
                1281731.45,
                [
                    'decimals' => 1,
                    'dec_point.' => [
                        'char' => 44
                    ],
                    'thousands_sep.' => [
                        'char' => 46
                    ]
                ],
                '1.281.731,5'
            ]
        ];
        return $data;
    }

    /**
     * Check if stdWrap.numberFormat and all of its properties work properly
     *
     * @dataProvider numberFormatDataProvider
     * @test
     */
    public function numberFormat($float, $formatConf, $expected)
    {
        $result = $this->subject->numberFormat($float, $formatConf);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for the replacement test
     *
     * @return array multi-dimensional array with the second level like this:
     * @see replacement
     */
    public function replacementDataProvider()
    {
        $data = [
            'multiple replacements, including regex' => [
                'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
                [
                    'replacement.' => [
                        '120.' => [
                            'search' => 'in da hood',
                            'replace' => 'around the block'
                        ],
                        '20.' => [
                            'search' => '_',
                            'replace.' => ['char' => '32']
                        ],
                        '130.' => [
                            'search' => '#a (Cat|Dog|Tiger)#i',
                            'replace' => 'an animal',
                            'useRegExp' => '1'
                        ]
                    ]
                ],
                'There is an animal, an animal and an animal around the block! Yeah!'
            ],
            'replacement with optionSplit, normal pattern' => [
                'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
                [
                    'replacement.' => [
                        '10.' => [
                            'search' => '_',
                            'replace' => '1 || 2 || 3',
                            'useOptionSplitReplace' => '1'
                        ],
                    ]
                ],
                'There1is2a3cat,3a3dog3and3a3tiger3in3da3hood!3Yeah!'
            ],
            'replacement with optionSplit, using regex' => [
                'There is a cat, a dog and a tiger in da hood! Yeah!',
                [
                    'replacement.' => [
                        '10.' => [
                            'search' => '#(a) (Cat|Dog|Tiger)#i',
                            'replace' => '${1} tiny ${2} || ${1} midsized ${2} || ${1} big ${2}',
                            'useOptionSplitReplace' => '1',
                            'useRegExp' => '1'
                        ]
                    ]
                ],
                'There is a tiny cat, a midsized dog and a big tiger in da hood! Yeah!'
            ],
        ];
        return $data;
    }

    /**
     * Check if stdWrap.replacement and all of its properties work properly
     *
     * @dataProvider replacementDataProvider
     * @test
     */
    public function replacement($input, $conf, $expected)
    {
        $result = $this->subject->stdWrap_replacement($input, $conf);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for the getQuery test
     *
     * @return array multi-dimensional array with the second level like this:
     * @see getQuery
     */
    public function getQueryDataProvider()
    {
        $data = [
            'testing empty conf' => [
                'tt_content',
                [],
                [
                    'SELECT' => '*'
                ]
            ],
            'testing #17284: adding uid/pid for workspaces' => [
                'tt_content',
                [
                    'selectFields' => 'header,bodytext'
                ],
                [
                    'SELECT' => 'header,bodytext, tt_content.uid as uid, tt_content.pid as pid, tt_content.t3ver_state as t3ver_state'
                ]
            ],
            'testing #17284: no need to add' => [
                'tt_content',
                [
                    'selectFields' => 'tt_content.*'
                ],
                [
                    'SELECT' => 'tt_content.*'
                ]
            ],
            'testing #17284: no need to add #2' => [
                'tt_content',
                [
                    'selectFields' => '*'
                ],
                [
                    'SELECT' => '*'
                ]
            ],
            'testing #29783: joined tables, prefix tablename' => [
                'tt_content',
                [
                    'selectFields' => 'tt_content.header,be_users.username',
                    'join' => 'be_users ON tt_content.cruser_id = be_users.uid'
                ],
                [
                    'SELECT' => 'tt_content.header,be_users.username, tt_content.uid as uid, tt_content.pid as pid, tt_content.t3ver_state as t3ver_state'
                ]
            ],
            'testing #34152: single count(*), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'count(*)'
                ],
                [
                    'SELECT' => 'count(*)'
                ]
            ],
            'testing #34152: single max(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'max(crdate)'
                ],
                [
                    'SELECT' => 'max(crdate)'
                ]
            ],
            'testing #34152: single min(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'min(crdate)'
                ],
                [
                    'SELECT' => 'min(crdate)'
                ]
            ],
            'testing #34152: single sum(is_siteroot), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'sum(is_siteroot)'
                ],
                [
                    'SELECT' => 'sum(is_siteroot)'
                ]
            ],
            'testing #34152: single avg(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'avg(crdate)'
                ],
                [
                    'SELECT' => 'avg(crdate)'
                ]
            ]
        ];
        return $data;
    }

    /**
     * Check if sanitizeSelectPart works as expected
     *
     * @dataProvider getQueryDataProvider
     * @test
     */
    public function getQuery($table, $conf, $expected)
    {
        $GLOBALS['TCA'] = [
            'pages' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden'
                    ]
                ]
            ],
            'tt_content' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden'
                    ],
                    'versioningWS' => true
                ]
            ],
        ];
        $result = $this->subject->getQuery($table, $conf, true);
        foreach ($expected as $field => $value) {
            $this->assertEquals($value, $result[$field]);
        }
    }

    /**
     * @test
     */
    public function getQueryCallsGetTreeListWithNegativeValuesIfRecursiveIsSet()
    {
        $GLOBALS['TCA'] = [
            'pages' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden'
                    ]
                ]
            ],
            'tt_content' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden'
                    ]
                ]
            ],
        ];
        $this->subject = $this->getAccessibleMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class, ['getTreeList']);
        $this->subject->start([], 'tt_content');
        $conf = [
            'recursive' => '15',
            'pidInList' => '16, -35'
        ];
        $this->subject->expects($this->at(0))
            ->method('getTreeList')
            ->with(-16, 15)
            ->will($this->returnValue('15,16'));
        $this->subject->expects($this->at(1))
            ->method('getTreeList')
            ->with(-35, 15)
            ->will($this->returnValue('15,35'));
        $this->subject->getQuery('tt_content', $conf, true);
    }

    /**
     * @test
     */
    public function getQueryCallsGetTreeListWithCurrentPageIfThisIsSet()
    {
        $GLOBALS['TCA'] = [
            'pages' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden'
                    ]
                ]
            ],
            'tt_content' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden'
                    ]
                ]
            ],
        ];
        $this->subject = $this->getAccessibleMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class, ['getTreeList']);
        $GLOBALS['TSFE']->id = 27;
        $this->subject->start([], 'tt_content');
        $conf = [
            'pidInList' => 'this',
            'recursive' => '4'
        ];
        $this->subject->expects($this->once())
            ->method('getTreeList')
            ->with(-27)
            ->will($this->returnValue('27'));
        $this->subject->getQuery('tt_content', $conf, true);
    }

    /**
     * Data provider for the stdWrap_date test
     *
     * @return array multi-dimensional array with the second level like this:
     * @see stdWrap_date
     */
    public function stdWrap_dateDataProvider()
    {
        return [
            'given timestamp' => [
                1443780000, // This is 2015-10-02 12:00
                [
                    'date' => 'd.m.Y',
                ],
                '02.10.2015',
            ],
            'empty string' => [
                '',
                [
                    'date' => 'd.m.Y',
                ],
                '02.10.2015',
            ],
            'testing null' => [
                null,
                [
                    'date' => 'd.m.Y',
                ],
                '02.10.2015',
            ],
            'given timestamp return GMT' => [
                1443780000, // This is 2015-10-02 12:00
                [
                    'date' => 'd.m.Y H:i:s',
                    'date.' => [
                        'GMT' => true,
                    ]
                ],
                '02.10.2015 10:00:00',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider stdWrap_dateDataProvider
     * @param string|int|NULL $content
     * @param array $conf
     * @param string $expected
     */
    public function stdWrap_date($content, $conf, $expected)
    {
        // Set exec_time to a hard timestamp
        $GLOBALS['EXEC_TIME'] = 1443780000;

        $result = $this->subject->stdWrap_date($content, $conf);

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for the stdWrap_strftime test
     *
     * @return array multi-dimensional array with the second level like this:
     * @see stdWrap_strftime
     */
    public function stdWrap_strftimeReturnsFormattedStringDataProvider()
    {
        $data = [
            'given timestamp' => [
                1346500800, // This is 2012-09-01 12:00 in UTC/GMT
                [
                    'strftime' => '%d-%m-%Y',
                ],
            ],
            'empty string' => [
                '',
                [
                    'strftime' => '%d-%m-%Y',
                ],
            ],
            'testing null' => [
                null,
                [
                    'strftime' => '%d-%m-%Y',
                ],
            ],
        ];
        return $data;
    }

    /**
     * @test
     * @dataProvider stdWrap_strftimeReturnsFormattedStringDataProvider
     */
    public function stdWrap_strftimeReturnsFormattedString($content, $conf)
    {
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
     * Data provider for the stdWrap_strtotime test
     *
     * @return array
     * @see stdWrap_strtotime
     */
    public function stdWrap_strtotimeReturnsTimestampDataProvider()
    {
        return [
            'date from content' => [
                '2014-12-04',
                [
                    'strtotime' => '1',
                ],
                1417651200,
            ],
            'manipulation of date from content' => [
                '2014-12-04',
                [
                    'strtotime' => '+ 2 weekdays',
                ],
                1417996800,
            ],
            'date from configuration' => [
                '',
                [
                    'strtotime' => '2014-12-04',
                ],
                1417651200,
            ],
            'manipulation of date from configuration' => [
                '',
                [
                    'strtotime' => '2014-12-04 + 2 weekdays',
                ],
                1417996800,
            ],
            'empty input' => [
                '',
                [
                    'strtotime' => '1',
                ],
                false,
            ],
            'date from content and configuration' => [
                '2014-12-04',
                [
                    'strtotime' => '2014-12-05',
                ],
                false,
            ],
        ];
    }

    /**
     * @param string|NULL $content
     * @param array $configuration
     * @param int $expected
     * @dataProvider stdWrap_strtotimeReturnsTimestampDataProvider
     * @test
     */
    public function stdWrap_strtotimeReturnsTimestamp($content, $configuration, $expected)
    {
        // Set exec_time to a hard timestamp
        $GLOBALS['EXEC_TIME'] = 1417392000;
        // Save current timezone and set to UTC to make the system under test behave
        // the same in all server timezone settings
        $timezoneBackup = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $result = $this->subject->stdWrap_strtotime($content, $configuration);

        // Reset timezone
        date_default_timezone_set($timezoneBackup);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function stdWrap_ageCallsCalcAgeWithSubtractedTimestampAndSubPartOfArray()
    {
        $subject = $this->getMock(
            \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class,
            ['calcAge']
        );
        // Set exec_time to a hard timestamp
        $GLOBALS['EXEC_TIME'] = 10;
        $subject->expects($this->once())->method('calcAge')->with(1, 'Min| Hrs| Days| Yrs');
        $subject->stdWrap_age(9, ['age' => 'Min| Hrs| Days| Yrs']);
    }

    /**
     * Data provider for calcAgeCalculatesAgeOfTimestamp
     *
     * @return array
     * @see calcAge
     */
    public function calcAgeCalculatesAgeOfTimestampDataProvider()
    {
        return [
            'minutes' => [
                120,
                ' min| hrs| days| yrs',
                '2 min',
            ],
            'hours' => [
                7200,
                ' min| hrs| days| yrs',
                '2 hrs',
            ],
            'days' => [
                604800,
                ' min| hrs| days| yrs',
                '7 days',
            ],
            'day with provided singular labels' => [
                86400,
                ' min| hrs| days| yrs| min| hour| day| year',
                '1 day',
            ],
            'years' => [
                1417997800,
                ' min| hrs| days| yrs',
                '45 yrs',
            ],
            'different labels' => [
                120,
                ' Minutes| Hrs| Days| Yrs',
                '2 Minutes',
            ],
            'negative values' => [
                -604800,
                ' min| hrs| days| yrs',
                '-7 days',
            ],
            'default label values for wrong label input' => [
                121,
                10,
                '2 min',
            ],
            'default singular label values for wrong label input' => [
                31536000,
                10,
                '1 year',
            ]
        ];
    }

    /**
     * @param int $timestamp
     * @param string $labels
     * @param int $expectation
     * @dataProvider calcAgeCalculatesAgeOfTimestampDataProvider
     * @test
     */
    public function calcAgeCalculatesAgeOfTimestamp($timestamp, $labels, $expectation)
    {
        $result = $this->subject->calcAge($timestamp, $labels);
        $this->assertEquals($result, $expectation);
    }

    /**
     * Data provider for stdWrap_case test
     *
     * @return array
     */
    public function stdWrap_caseDataProvider()
    {
        return [
            'lower case text to upper' => [
                '<span>text</span>',
                [
                    'case' => 'upper',
                ],
                '<span>TEXT</span>',
            ],
            'upper case text to lower' => [
                '<span>TEXT</span>',
                [
                    'case' => 'lower',
                ],
                '<span>text</span>',
            ],
            'capitalize text' => [
                '<span>this is a text</span>',
                [
                    'case' => 'capitalize',
                ],
                '<span>This Is A Text</span>',
            ],
            'ucfirst text' => [
                '<span>this is a text</span>',
                [
                    'case' => 'ucfirst',
                ],
                '<span>This is a text</span>',
            ],
            'lcfirst text' => [
                '<span>This is a Text</span>',
                [
                    'case' => 'lcfirst',
                ],
                '<span>this is a Text</span>',
            ],
            'uppercamelcase text' => [
                '<span>this_is_a_text</span>',
                [
                    'case' => 'uppercamelcase',
                ],
                '<span>ThisIsAText</span>',
            ],
            'lowercamelcase text' => [
                '<span>this_is_a_text</span>',
                [
                    'case' => 'lowercamelcase',
                ],
                '<span>thisIsAText</span>',
            ],
        ];
    }

    /**
     * @param string|NULL $content
     * @param array $configuration
     * @param string $expected
     * @dataProvider stdWrap_caseDataProvider
     * @test
     */
    public function stdWrap_case($content, array $configuration, $expected)
    {
        $result = $this->subject->stdWrap_case($content, $configuration);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for stdWrap_bytes test
     *
     * @return array
     */
    public function stdWrap_bytesDataProvider()
    {
        return [
            'value 1234 default' => [
                '1234',
                [
                    'bytes.' => [
                        'labels' => '',
                        'base' => 0,
                    ],
                ],
                '1.21 Ki',
                'en_US.UTF-8'
            ],
            'value 1234 si' => [
                '1234',
                [
                    'bytes.' => [
                        'labels' => 'si',
                        'base' => 0,
                    ],
                ],
                '1.23 k',
                'en_US.UTF-8'
            ],
            'value 1234 iec' => [
                '1234',
                [
                    'bytes.' => [
                        'labels' => 'iec',
                        'base' => 0,
                    ],
                ],
                '1.21 Ki',
                'en_US.UTF-8'
            ],
            'value 1234 a-i' => [
                '1234',
                [
                    'bytes.' => [
                        'labels' => 'a|b|c|d|e|f|g|h|i',
                        'base' => 1000,
                    ],
                ],
                '1.23b',
                'en_US.UTF-8'
            ],
            'value 1234 a-i invalid base' => [
                '1234',
                [
                    'bytes.' => [
                        'labels' => 'a|b|c|d|e|f|g|h|i',
                        'base' => 54,
                    ],
                ],
                '1.21b',
                'en_US.UTF-8'
            ],
            'value 1234567890 default' => [
                '1234567890',
                [
                    'bytes.' => [
                        'labels' => '',
                        'base' => 0,
                    ],
                ],
                '1.15 Gi',
                'en_US.UTF-8'
            ],
        ];
    }

    /**
     * @param string|NULL $content
     * @param array $configuration
     * @param string $expected
     * @dataProvider stdWrap_bytesDataProvider
     * @test
     */
    public function stdWrap_bytes($content, array $configuration, $expected, $locale)
    {
        if (!setlocale(LC_NUMERIC, $locale)) {
            $this->markTestSkipped('Locale ' . $locale . ' is not available.');
        }
        $result = $this->subject->stdWrap_bytes($content, $configuration);
        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for stdWrap_substring test
     *
     * @return array
     */
    public function stdWrap_substringDataProvider()
    {
        return [
            'sub -1' => [
                'substring',
                [
                    'substring' => '-1',
                ],
                'g',
            ],
            'sub -1,0' => [
                'substring',
                [
                    'substring' => '-1,0',
                ],
                'g',
            ],
            'sub -1,-1' => [
                'substring',
                [
                    'substring' => '-1,-1',
                ],
                '',
            ],
            'sub -1,1' => [
                'substring',
                [
                    'substring' => '-1,1',
                ],
                'g',
            ],
            'sub 0' => [
                'substring',
                [
                    'substring' => '0',
                ],
                'substring',
            ],
            'sub 0,0' => [
                'substring',
                [
                    'substring' => '0,0',
                ],
                'substring',
            ],
            'sub 0,-1' => [
                'substring',
                [
                    'substring' => '0,-1',
                ],
                'substrin',
            ],
            'sub 0,1' => [
                'substring',
                [
                    'substring' => '0,1',
                ],
                's',
            ],
            'sub 1' => [
                'substring',
                [
                    'substring' => '1',
                ],
                'ubstring',
            ],
            'sub 1,0' => [
                'substring',
                [
                    'substring' => '1,0',
                ],
                'ubstring',
            ],
            'sub 1,-1' => [
                'substring',
                [
                    'substring' => '1,-1',
                ],
                'ubstrin',
            ],
            'sub 1,1' => [
                'substring',
                [
                    'substring' => '1,1',
                ],
                'u',
            ],
            'sub' => [
                'substring',
                [
                    'substring' => '',
                ],
                'substring',
            ],
        ];
    }

    /**
     * @param string $content
     * @param array $configuration
     * @param string $expected
     * @dataProvider stdWrap_substringDataProvider
     * @test
     */
    public function stdWrap_substring($content, array $configuration, $expected)
    {
        $result = $this->subject->stdWrap_substring($content, $configuration);
        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for stdWrap_stdWrapValue test
     *
     * @return array
     */
    public function stdWrap_stdWrapValueDataProvider()
    {
        return [
            'only key returns value' => [
                'ifNull',
                [
                    'ifNull' => '1',
                ],
                '',
                '1',
            ],
            'array without key returns empty string' => [
                'ifNull',
                [
                    'ifNull.' => '1',
                ],
                '',
                '',
            ],
            'array without key returns default' => [
                'ifNull',
                [
                    'ifNull.' => '1',
                ],
                'default',
                'default',
            ],
            'non existing key returns default' => [
                'ifNull',
                [
                    'noTrimWrap' => 'test',
                    'noTrimWrap.' => '1',
                ],
                'default',
                'default',
            ],
            'existing key and array returns stdWrap' => [
                'test',
                [
                    'test' => 'value',
                    'test.' => ['case' => 'upper'],
                ],
                'default',
                'VALUE'
            ],
        ];
    }

    /**
     * @param string $key
     * @param array $configuration
     * @param string $defaultValue
     * @param string $expected
     * @dataProvider stdWrap_stdWrapValueDataProvider
     * @test
     */
    public function stdWrap_stdWrapValue($key, array $configuration, $defaultValue, $expected)
    {
        $result = $this->subject->stdWrapValue($key, $configuration, $defaultValue);
        $this->assertEquals($expected, $result);
    }

    /**
     * @param string|NULL $content
     * @param array $configuration
     * @param string $expected
     * @dataProvider stdWrap_ifNullDeterminesNullValuesDataProvider
     * @test
     */
    public function stdWrap_ifNullDeterminesNullValues($content, array $configuration, $expected)
    {
        $result = $this->subject->stdWrap_ifNull($content, $configuration);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for stdWrap_ifNullDeterminesNullValues test
     *
     * @return array
     */
    public function stdWrap_ifNullDeterminesNullValuesDataProvider()
    {
        return [
            'null value' => [
                null,
                [
                    'ifNull' => '1',
                ],
                '1',
            ],
            'zero value' => [
                '0',
                [
                    'ifNull' => '1',
                ],
                '0',
            ],
        ];
    }

    /**
     * Data provider for stdWrap_ifEmptyDeterminesEmptyValues test
     *
     * @return array
     */
    public function stdWrap_ifEmptyDeterminesEmptyValuesDataProvider()
    {
        return [
            'null value' => [
                null,
                [
                    'ifEmpty' => '1',
                ],
                '1',
            ],
            'empty value' => [
                '',
                [
                    'ifEmpty' => '1',
                ],
                '1',
            ],
            'string value' => [
                'string',
                [
                    'ifEmpty' => '1',
                ],
                'string',
            ],
            'empty string value' => [
                '        ',
                [
                    'ifEmpty' => '1',
                ],
                '1',
            ],
        ];
    }

    /**
     * @param string|NULL $content
     * @param array $configuration
     * @param string $expected
     * @dataProvider stdWrap_ifEmptyDeterminesEmptyValuesDataProvider
     * @test
     */
    public function stdWrap_ifEmptyDeterminesEmptyValues($content, array $configuration, $expected)
    {
        $result = $this->subject->stdWrap_ifEmpty($content, $configuration);
        $this->assertEquals($expected, $result);
    }

    /**
     * @param $content
     * @param array $configuration
     * @param $expected
     * @dataProvider stdWrap_noTrimWrapAcceptsSplitCharDataProvider
     * @test
     */
    public function stdWrap_noTrimWrapAcceptsSplitChar($content, array $configuration, $expected)
    {
        $result = $this->subject->stdWrap_noTrimWrap($content, $configuration);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for stdWrap_noTrimWrapAcceptsSplitChar test
     *
     * @return array
     */
    public function stdWrap_noTrimWrapAcceptsSplitCharDataProvider()
    {
        return [
            'No char given' => [
                'middle',
                [
                    'noTrimWrap' => '| left | right |',
                ],
                ' left middle right '
            ],
            'Zero char given' => [
                'middle',
                [
                    'noTrimWrap' => '0 left 0 right 0',
                    'noTrimWrap.' => ['splitChar' => '0'],

                ],
                ' left middle right '
            ],
            'Default char given' => [
                'middle',
                [
                    'noTrimWrap' => '| left | right |',
                    'noTrimWrap.' => ['splitChar' => '|'],
                ],
                ' left middle right '
            ],
            'Split char is a' => [
                'middle',
                [
                    'noTrimWrap' => 'a left a right a',
                    'noTrimWrap.' => ['splitChar' => 'a'],
                ],
                ' left middle right '
            ],
            'Split char is multi-char (ab)' => [
                'middle',
                [
                    'noTrimWrap' => 'ab left ab right ab',
                    'noTrimWrap.' => ['splitChar' => 'ab'],
                ],
                ' left middle right '
            ],
            'Split char accepts stdWrap' => [
                'middle',
                [
                    'noTrimWrap' => 'abc left abc right abc',
                    'noTrimWrap.' => [
                        'splitChar' => 'b',
                        'splitChar.' => ['wrap' => 'a|c'],
                    ],
                ],
                ' left middle right '
            ],
        ];
    }

    /**
     * @param array $expectedTags
     * @param array $configuration
     * @test
     * @dataProvider stdWrap_addPageCacheTagsAddsPageTagsDataProvider
     */
    public function stdWrap_addPageCacheTagsAddsPageTags(array $expectedTags, array $configuration)
    {
        $this->subject->stdWrap_addPageCacheTags('', $configuration);
        $this->assertEquals($expectedTags, $this->typoScriptFrontendControllerMock->_get('pageCacheTags'));
    }

    /**
     * @return array
     */
    public function stdWrap_addPageCacheTagsAddsPageTagsDataProvider()
    {
        return [
            'No Tag' => [
                [],
                ['addPageCacheTags' => ''],
            ],
            'Two expectedTags' => [
                ['tag1', 'tag2'],
                ['addPageCacheTags' => 'tag1,tag2'],
            ],
            'Two expectedTags plus one with stdWrap' => [
                ['tag1', 'tag2', 'tag3'],
                [
                    'addPageCacheTags' => 'tag1,tag2',
                    'addPageCacheTags.' => ['wrap' => '|,tag3']
                ],
            ],
        ];
    }

    /**
     * Data provider for stdWrap_encodeForJavaScriptValue test
     *
     * @return array multi-dimensional array with the second level like this:
     * @see encodeForJavaScriptValue
     */
    public function stdWrap_encodeForJavaScriptValueDataProvider()
    {
        return [
            'double quote in string' => [
                'double quote"',
                [],
                '\'double\u0020quote\u0022\''
            ],
            'backslash in string' => [
                'backslash \\',
                [],
                '\'backslash\u0020\u005C\''
            ],
            'exclamation mark' => [
                'exclamation!',
                [],
                '\'exclamation\u0021\''
            ],
            'whitespace tab, newline and carriage return' => [
                "white\tspace\ns\r",
                [],
                '\'white\u0009space\u000As\u000D\''
            ],
            'single quote in string' => [
                'single quote \'',
                [],
                '\'single\u0020quote\u0020\u0027\''
            ],
            'tag' => [
                '<tag>',
                [],
                '\'\u003Ctag\u003E\''
            ],
            'ampersand in string' => [
                'amper&sand',
                [],
                '\'amper\u0026sand\''
            ],
        ];
    }

    /**
     * Check if encodeForJavaScriptValue works properly
     *
     * @dataProvider stdWrap_encodeForJavaScriptValueDataProvider
     * @test
     */
    public function stdWrap_encodeForJavaScriptValue($input, $conf, $expected)
    {
        $result = $this->subject->stdWrap_encodeForJavaScriptValue($input, $conf);
        $this->assertEquals($expected, $result);
    }

    ///////////////////////////////
    // Tests concerning getData()
    ///////////////////////////////

    /**
     * @return array
     */
    public function getDataWithTypeGpDataProvider()
    {
        return [
            'Value in get-data' => ['onlyInGet', 'GetValue'],
            'Value in post-data' => ['onlyInPost', 'PostValue'],
            'Value in post-data overriding get-data' => ['inGetAndPost', 'ValueInPost'],
        ];
    }

    /**
     * Checks if getData() works with type "gp"
     *
     * @test
     * @dataProvider getDataWithTypeGpDataProvider
     */
    public function getDataWithTypeGp($key, $expectedValue)
    {
        $_GET = [
            'onlyInGet' => 'GetValue',
            'inGetAndPost' => 'ValueInGet',
        ];
        $_POST = [
            'onlyInPost' => 'PostValue',
            'inGetAndPost' => 'ValueInPost',
        ];
        $this->assertEquals($expectedValue, $this->subject->getData('gp:' . $key));
    }

    /**
     * Checks if getData() works with type "tsfe"
     *
     * @test
     */
    public function getDataWithTypeTsfe()
    {
        $this->assertEquals($GLOBALS['TSFE']->renderCharset, $this->subject->getData('tsfe:renderCharset'));
    }

    /**
     * Checks if getData() works with type "getenv"
     *
     * @test
     */
    public function getDataWithTypeGetenv()
    {
        $envName = $this->getUniqueId('frontendtest');
        $value = $this->getUniqueId('someValue');
        putenv($envName . '=' . $value);
        $this->assertEquals($value, $this->subject->getData('getenv:' . $envName));
    }

    /**
     * Checks if getData() works with type "getindpenv"
     *
     * @test
     */
    public function getDataWithTypeGetindpenv()
    {
        $this->subject->expects($this->once())->method('getEnvironmentVariable')
            ->with($this->equalTo('SCRIPT_FILENAME'))->will($this->returnValue('dummyPath'));
        $this->assertEquals('dummyPath', $this->subject->getData('getindpenv:SCRIPT_FILENAME'));
    }

    /**
     * Checks if getData() works with type "field"
     *
     * @test
     */
    public function getDataWithTypeField()
    {
        $key = 'someKey';
        $value = 'someValue';
        $field = [$key => $value];

        $this->assertEquals($value, $this->subject->getData('field:' . $key, $field));
    }

    /**
     * Checks if getData() works with type "field" of the field content
     * is multi-dimensional (e.g. an array)
     *
     * @test
     */
    public function getDataWithTypeFieldAndFieldIsMultiDimensional()
    {
        $key = 'somekey|level1|level2';
        $value = 'somevalue';
        $field = ['somekey' => ['level1' => ['level2' => 'somevalue']]];

        $this->assertEquals($value, $this->subject->getData('field:' . $key, $field));
    }

    /**
     * Basic check if getData gets the uid of a file object
     *
     * @test
     */
    public function getDataWithTypeFileReturnsUidOfFileObject()
    {
        $uid = $this->getUniqueId();
        $file = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);
        $file->expects($this->once())->method('getUid')->will($this->returnValue($uid));
        $this->subject->setCurrentFile($file);
        $this->assertEquals($uid, $this->subject->getData('file:current:uid'));
    }

    /**
     * Checks if getData() works with type "parameters"
     *
     * @test
     */
    public function getDataWithTypeParameters()
    {
        $key = $this->getUniqueId('someKey');
        $value = $this->getUniqueId('someValue');
        $this->subject->parameters[$key] = $value;

        $this->assertEquals($value, $this->subject->getData('parameters:' . $key));
    }

    /**
     * Checks if getData() works with type "register"
     *
     * @test
     */
    public function getDataWithTypeRegister()
    {
        $key = $this->getUniqueId('someKey');
        $value = $this->getUniqueId('someValue');
        $GLOBALS['TSFE']->register[$key] = $value;

        $this->assertEquals($value, $this->subject->getData('register:' . $key));
    }

    /**
     * Checks if getData() works with type "level"
     *
     * @test
     */
    public function getDataWithTypeLevel()
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => 'title3'],
        ];

        $GLOBALS['TSFE']->tmpl->rootLine = $rootline;
        $this->assertEquals(2, $this->subject->getData('level'));
    }

    /**
     * Checks if getData() works with type "global"
     *
     * @test
     */
    public function getDataWithTypeGlobal()
    {
        $this->assertEquals($GLOBALS['TSFE']->renderCharset, $this->subject->getData('global:TSFE|renderCharset'));
    }

    /**
     * Checks if getData() works with type "leveltitle"
     *
     * @test
     */
    public function getDataWithTypeLeveltitle()
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => ''],
        ];

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
    public function getDataWithTypeLevelmedia()
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1', 'media' => 'media1'],
            1 => ['uid' => 2, 'title' => 'title2', 'media' => 'media2'],
            2 => ['uid' => 3, 'title' => 'title3', 'media' => ''],
        ];

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
    public function getDataWithTypeLeveluid()
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => 'title3'],
        ];

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
    public function getDataWithTypeLevelfield()
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1', 'testfield' => 'field1'],
            1 => ['uid' => 2, 'title' => 'title2', 'testfield' => 'field2'],
            2 => ['uid' => 3, 'title' => 'title3', 'testfield' => ''],
        ];

        $GLOBALS['TSFE']->tmpl->rootLine = $rootline;
        $this->assertEquals('', $this->subject->getData('levelfield:-1,testfield'));
        $this->assertEquals('field2', $this->subject->getData('levelfield:-1,testfield,slide'));
    }

    /**
     * Checks if getData() works with type "fullrootline"
     *
     * @test
     */
    public function getDataWithTypeFullrootline()
    {
        $rootline1 = [
            0 => ['uid' => 1, 'title' => 'title1', 'testfield' => 'field1'],
        ];
        $rootline2 = [
            0 => ['uid' => 1, 'title' => 'title1', 'testfield' => 'field1'],
            1 => ['uid' => 2, 'title' => 'title2', 'testfield' => 'field2'],
            2 => ['uid' => 3, 'title' => 'title3', 'testfield' => 'field3'],
        ];

        $GLOBALS['TSFE']->tmpl->rootLine = $rootline1;
        $GLOBALS['TSFE']->rootLine = $rootline2;
        $this->assertEquals('field2', $this->subject->getData('fullrootline:-1,testfield'));
    }

    /**
     * Checks if getData() works with type "date"
     *
     * @test
     */
    public function getDataWithTypeDate()
    {
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
    public function getDataWithTypePage()
    {
        $uid = rand();
        $GLOBALS['TSFE']->page['uid'] = $uid;
        $this->assertEquals($uid, $this->subject->getData('page:uid'));
    }

    /**
     * Checks if getData() works with type "current"
     *
     * @test
     */
    public function getDataWithTypeCurrent()
    {
        $key = $this->getUniqueId('someKey');
        $value = $this->getUniqueId('someValue');
        $this->subject->data[$key] = $value;
        $this->subject->currentValKey = $key;
        $this->assertEquals($value, $this->subject->getData('current'));
    }

    /**
     * Checks if getData() works with type "db"
     *
     * @test
     */
    public function getDataWithTypeDb()
    {
        $dummyRecord = ['uid' => 5, 'title' => 'someTitle'];

        $GLOBALS['TSFE']->sys_page->expects($this->atLeastOnce())->method('getRawRecord')->with('tt_content', '106')->will($this->returnValue($dummyRecord));
        $this->assertEquals($dummyRecord['title'], $this->subject->getData('db:tt_content:106:title'));
    }

    /**
     * Checks if getData() works with type "lll"
     *
     * @test
     */
    public function getDataWithTypeLll()
    {
        $key = $this->getUniqueId('someKey');
        $value = $this->getUniqueId('someValue');
        $language = $this->getUniqueId('someLanguage');
        $GLOBALS['TSFE']->LL_labels_cache[$language]['LLL:' . $key] = $value;
        $GLOBALS['TSFE']->lang = $language;

        $this->assertEquals($value, $this->subject->getData('lll:' . $key));
    }

    /**
     * Checks if getData() works with type "path"
     *
     * @test
     */
    public function getDataWithTypePath()
    {
        $filenameIn = $this->getUniqueId('someValue');
        $filenameOut = $this->getUniqueId('someValue');
        $this->templateServiceMock->expects($this->atLeastOnce())->method('getFileName')->with($filenameIn)->will($this->returnValue($filenameOut));
        $this->assertEquals($filenameOut, $this->subject->getData('path:' . $filenameIn));
    }

    /**
     * Checks if getData() works with type "parentRecordNumber"
     *
     * @test
     */
    public function getDataWithTypeParentRecordNumber()
    {
        $recordNumber = rand();
        $this->subject->parentRecordNumber = $recordNumber;
        $this->assertEquals($recordNumber, $this->subject->getData('cobj:parentRecordNumber'));
    }

    /**
     * Checks if getData() works with type "debug:rootLine"
     *
     * @test
     */
    public function getDataWithTypeDebugRootline()
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => ''],
        ];
        $expectedResult = 'array(3items)0=>array(2items)uid=>1(integer)title=>"title1"(6chars)1=>array(2items)uid=>2(integer)title=>"title2"(6chars)2=>array(2items)uid=>3(integer)title=>""(0chars)';
        $GLOBALS['TSFE']->tmpl->rootLine = $rootline;

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:rootLine');
        $cleanedResult = str_replace("\r", '', $result);
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
    public function getDataWithTypeDebugFullRootline()
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => ''],
        ];
        $expectedResult = 'array(3items)0=>array(2items)uid=>1(integer)title=>"title1"(6chars)1=>array(2items)uid=>2(integer)title=>"title2"(6chars)2=>array(2items)uid=>3(integer)title=>""(0chars)';
        $GLOBALS['TSFE']->rootLine = $rootline;

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:fullRootLine');
        $cleanedResult = str_replace("\r", '', $result);
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
    public function getDataWithTypeDebugData()
    {
        $key = $this->getUniqueId('someKey');
        $value = $this->getUniqueId('someValue');
        $this->subject->data = [$key => $value];

        $expectedResult = 'array(1item)' . $key . '=>"' . $value . '"(' . strlen($value) . 'chars)';

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:data');
        $cleanedResult = str_replace("\r", '', $result);
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
    public function getDataWithTypeDebugRegister()
    {
        $key = $this->getUniqueId('someKey');
        $value = $this->getUniqueId('someValue');
        $GLOBALS['TSFE']->register = [$key => $value];

        $expectedResult = 'array(1item)' . $key . '=>"' . $value . '"(' . strlen($value) . 'chars)';

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:register');
        $cleanedResult = str_replace("\r", '', $result);
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
    public function getDataWithTypeDebugPage()
    {
        $uid = rand();
        $GLOBALS['TSFE']->page = ['uid' => $uid];

        $expectedResult = 'array(1item)uid=>' . $uid . '(integer)';

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:page');
        $cleanedResult = str_replace("\r", '', $result);
        $cleanedResult = str_replace("\n", '', $cleanedResult);
        $cleanedResult = str_replace("\t", '', $cleanedResult);
        $cleanedResult = str_replace(' ', '', $cleanedResult);

        $this->assertEquals($expectedResult, $cleanedResult);
    }

    /**
     * @test
     */
    public function getTreeListReturnsChildPageUids()
    {
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetSingleRow')->with('treelist')->will($this->returnValue(null));
        $GLOBALS['TSFE']->sys_page
            ->expects($this->any())
            ->method('getRawRecord')
            ->will(
                $this->onConsecutiveCalls(
                    ['uid' => 17],
                    ['uid' => 321],
                    ['uid' => 719],
                    ['uid' => 42]
                )
            );

        $GLOBALS['TSFE']->sys_page->expects($this->any())->method('getMountPointInfo')->will($this->returnValue(null));
        $GLOBALS['TYPO3_DB']
            ->expects($this->any())
            ->method('exec_SELECTgetRows')
            ->will(
                $this->onConsecutiveCalls(
                    [
                        ['uid' => 321]
                    ],
                    [
                        ['uid' => 719]
                    ],
                    [
                        ['uid' => 42]
                    ]
                )
            );
        // 17 = pageId, 5 = recursionLevel, 0 = begin (entry to recursion, internal), TRUE = do not check enable fields
        // 17 is positive, we expect 17 NOT to be included in result
        $result = $this->subject->getTreeList(17, 5, 0, true);
        $expectedResult = '42,719,321';
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function getTreeListReturnsChildPageUidsAndOriginalPidForNegativeValue()
    {
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetSingleRow')->with('treelist')->will($this->returnValue(null));
        $GLOBALS['TSFE']->sys_page
            ->expects($this->any())
            ->method('getRawRecord')
            ->will(
                $this->onConsecutiveCalls(
                    ['uid' => 17],
                    ['uid' => 321],
                    ['uid' => 719],
                    ['uid' => 42]
                )
            );

        $GLOBALS['TSFE']->sys_page->expects($this->any())->method('getMountPointInfo')->will($this->returnValue(null));
        $GLOBALS['TYPO3_DB']
            ->expects($this->any())
            ->method('exec_SELECTgetRows')
            ->will(
                $this->onConsecutiveCalls(
                    [
                        ['uid' => 321]
                    ],
                    [
                        ['uid' => 719]
                    ],
                    [
                        ['uid' => 42]
                    ]
                )
            );
        // 17 = pageId, 5 = recursionLevel, 0 = begin (entry to recursion, internal), TRUE = do not check enable fields
        // 17 is negative, we expect 17 to be included in result
        $result = $this->subject->getTreeList(-17, 5, 0, true);
        $expectedResult = '42,719,321,17';
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function aTagParamsHasLeadingSpaceIfNotEmpty()
    {
        $aTagParams = $this->subject->getATagParams(['ATagParams' => 'data-test="testdata"']);
        $this->assertEquals(' data-test="testdata"', $aTagParams);
    }

    /**
     * @test
     */
    public function aTagParamsHaveSpaceBetweenLocalAndGlobalParams()
    {
        $GLOBALS['TSFE']->ATagParams = 'data-global="dataglobal"';
        $aTagParams = $this->subject->getATagParams(['ATagParams' => 'data-test="testdata"']);
        $this->assertEquals(' data-global="dataglobal" data-test="testdata"', $aTagParams);
    }

    /**
     * @test
     */
    public function aTagParamsHasNoLeadingSpaceIfEmpty()
    {
        // make sure global ATagParams are empty
        $GLOBALS['TSFE']->ATagParams = '';
        $aTagParams = $this->subject->getATagParams(['ATagParams' => '']);
        $this->assertEquals('', $aTagParams);
    }

    /**
     * @return array
     */
    public function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFoundDataProvider()
    {
        return [
            [null, null],
            ['', null],
            ['', []],
            ['fooo', ['foo' => 'bar']]
        ];
    }

    /**
     * Make sure that the rendering falls back to the classic <img style if nothing else is found
     *
     * @test
     * @dataProvider getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFoundDataProvider
     * @param string $key
     * @param array $configuration
     */
    public function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFound($key, $configuration)
    {
        $defaultImgTagTemplate = '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ###BORDER######SELFCLOSINGTAGSLASH###>';
        $result = $this->subject->getImageTagTemplate($key, $configuration);
        $this->assertEquals($result, $defaultImgTagTemplate);
    }

    /**
     * @return array
     */
    public function getImageTagTemplateReturnTemplateElementIdentifiedByKeyDataProvider()
    {
        return [
            [
                'foo',
                [
                    'layout.' => [
                        'foo.' => [
                            'element' => '<img src="###SRC###" srcset="###SOURCES###" ###PARAMS### ###ALTPARAMS### ###FOOBAR######SELFCLOSINGTAGSLASH###>'
                        ]
                    ]
                ],
                '<img src="###SRC###" srcset="###SOURCES###" ###PARAMS### ###ALTPARAMS### ###FOOBAR######SELFCLOSINGTAGSLASH###>'
            ]

        ];
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
    public function getImageTagTemplateReturnTemplateElementIdentifiedByKey($key, $configuration, $expectation)
    {
        $result = $this->subject->getImageTagTemplate($key, $configuration);
        $this->assertEquals($result, $expectation);
    }

    /**
     * @return array
     */
    public function getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefinedDataProvider()
    {
        return [
            [null, null, null],
            ['foo', null, null],
            ['foo', ['sourceCollection.' => 1], 'bar']
        ];
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
    public function getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefined($layoutKey, $configuration, $file)
    {
        $result = $this->subject->getImageSourceCollection($layoutKey, $configuration, $file);
        $this->assertSame($result, '');
    }

    /**
     * Make sure the generation of subimages calls the generation of the subimages and uses the layout -> source template
     *
     * @test
     */
    public function getImageSourceCollectionRendersDefinedSources()
    {
        /** @var $cObj \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
        $cObj = $this->getMock(
            \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class,
            ['stdWrap', 'getImgResource']
        );
        $cObj->start([], 'tt_content');

        $layoutKey = 'test';

        $configuration = [
            'layoutKey' => 'test',
            'layout.' => [
                'test.' => [
                    'element' => '<img ###SRC### ###SRCCOLLECTION### ###SELFCLOSINGTAGSLASH###>',
                    'source' => '---###SRC###---'
                ]
            ],
            'sourceCollection.' => [
                '1.' => [
                    'width' => '200'
                ]
            ]
        ];

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
            ->will($this->returnValue([100, 100, null, 'bar']));

        $result = $cObj->getImageSourceCollection($layoutKey, $configuration, $file);

        $this->assertEquals('---bar---', $result);
    }

    /**
     * Data provider for the getImageSourceCollectionRendersDefinedLayoutKeyDefault test
     *
     * @return array multi-dimensional array with the second level like this:
     * @see getImageSourceCollectionRendersDefinedLayoutKeyDefault
     */
    public function getImageSourceCollectionRendersDefinedLayoutKeyDataDefaultProvider()
    {
        /**
         * @see css_styled_content/static/setup.txt
         */
        $sourceCollectionArray = [
            'small.' => [
                'width' => '200',
                'srcsetCandidate' => '600w',
                'mediaQuery' => '(max-device-width: 600px)',
                'dataKey' => 'small',
            ],
            'smallRetina.' => [
                'if.directReturn' => 0,
                'width' => '200',
                'pixelDensity' => '2',
                'srcsetCandidate' => '600w 2x',
                'mediaQuery' => '(max-device-width: 600px) AND (min-resolution: 192dpi)',
                'dataKey' => 'smallRetina',
            ]
        ];
        return [
            [
                'default',
                [
                    'layoutKey' => 'default',
                    'layout.' => [
                        'default.' => [
                            'element' => '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ###BORDER######SELFCLOSINGTAGSLASH###>',
                            'source' => ''
                        ]
                    ],
                    'sourceCollection.' => $sourceCollectionArray
                ]
            ],
        ];
    }

    /**
     * Make sure the generation of subimages renders the expected HTML Code for the sourceset
     *
     * @test
     * @dataProvider getImageSourceCollectionRendersDefinedLayoutKeyDataDefaultProvider
     * @param string $layoutKey
     * @param array $configuration
     */
    public function getImageSourceCollectionRendersDefinedLayoutKeyDefault($layoutKey, $configuration)
    {
        /** @var $cObj \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
        $cObj = $this->getMock(
            \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class,
            ['stdWrap', 'getImgResource']
        );
        $cObj->start([], 'tt_content');

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
    public function getImageSourceCollectionRendersDefinedLayoutKeyDataDataProvider()
    {
        /**
         * @see css_styled_content/static/setup.txt
         */
        $sourceCollectionArray = [
            'small.' => [
                'width' => '200',
                'srcsetCandidate' => '600w',
                'mediaQuery' => '(max-device-width: 600px)',
                'dataKey' => 'small',
            ],
            'smallRetina.' => [
                'if.directReturn' => 1,
                'width' => '200',
                'pixelDensity' => '2',
                'srcsetCandidate' => '600w 2x',
                'mediaQuery' => '(max-device-width: 600px) AND (min-resolution: 192dpi)',
                'dataKey' => 'smallRetina',
            ]
        ];
        return [
            [
                'srcset',
                [
                    'layoutKey' => 'srcset',
                    'layout.' => [
                        'srcset.' => [
                            'element' => '<img src="###SRC###" srcset="###SOURCECOLLECTION###" ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###>',
                            'source' => '|*|###SRC### ###SRCSETCANDIDATE###,|*|###SRC### ###SRCSETCANDIDATE###'
                        ]
                    ],
                    'sourceCollection.' => $sourceCollectionArray
                ],
                'xhtml_strict',
                'bar-file.jpg 600w,bar-file.jpg 600w 2x',
            ],
            [
                'picture',
                [
                    'layoutKey' => 'picture',
                    'layout.' => [
                        'picture.' => [
                            'element' => '<picture>###SOURCECOLLECTION###<img src="###SRC###" ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###></picture>',
                            'source' => '<source src="###SRC###" media="###MEDIAQUERY###"###SELFCLOSINGTAGSLASH###>'
                        ]
                    ],
                    'sourceCollection.' => $sourceCollectionArray,
                ],
                'xhtml_strict',
                '<source src="bar-file.jpg" media="(max-device-width: 600px)" /><source src="bar-file.jpg" media="(max-device-width: 600px) AND (min-resolution: 192dpi)" />',
            ],
            [
                'picture',
                [
                    'layoutKey' => 'picture',
                    'layout.' => [
                        'picture.' => [
                            'element' => '<picture>###SOURCECOLLECTION###<img src="###SRC###" ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###></picture>',
                            'source' => '<source src="###SRC###" media="###MEDIAQUERY###"###SELFCLOSINGTAGSLASH###>'
                        ]
                    ],
                    'sourceCollection.' => $sourceCollectionArray,
                ],
                '',
                '<source src="bar-file.jpg" media="(max-device-width: 600px)"><source src="bar-file.jpg" media="(max-device-width: 600px) AND (min-resolution: 192dpi)">',
            ],
            [
                'data',
                [
                    'layoutKey' => 'data',
                    'layout.' => [
                        'data.' => [
                            'element' => '<img src="###SRC###" ###SOURCECOLLECTION### ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###>',
                            'source' => 'data-###DATAKEY###="###SRC###"'
                        ]
                    ],
                    'sourceCollection.' => $sourceCollectionArray
                ],
                'xhtml_strict',
                'data-small="bar-file.jpg"data-smallRetina="bar-file.jpg"',
            ],
        ];
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
    public function getImageSourceCollectionRendersDefinedLayoutKeyData($layoutKey, $configuration, $xhtmlDoctype, $expectedHtml)
    {
        /** @var $cObj \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
        $cObj = $this->getMock(
            \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class,
            ['stdWrap', 'getImgResource']
        );
        $cObj->start([], 'tt_content');

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
            ->will($this->returnValue([100, 100, null, 'bar-file.jpg']));

        $result = $cObj->getImageSourceCollection($layoutKey, $configuration, $file);

        $this->assertEquals($expectedHtml, $result);
    }

    /**
     * Make sure the hook in get sourceCollection is called
     *
     * @test
     */
    public function getImageSourceCollectionHookCalled()
    {
        $this->subject = $this->getAccessibleMock(
            \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class,
            ['getResourceFactory', 'stdWrap', 'getImgResource']
        );
        $this->subject->start([], 'tt_content');

        // Avoid calling stdwrap and getImgResource
        $this->subject->expects($this->any())
            ->method('stdWrap')
            ->will($this->returnArgument(0));

        $this->subject->expects($this->any())
            ->method('getImgResource')
            ->will($this->returnValue([100, 100, null, 'bar-file.jpg']));

        $resourceFactory = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceFactory::class, [], [], '', false);
        $this->subject->expects($this->any())->method('getResourceFactory')->will($this->returnValue($resourceFactory));

        $className = $this->getUniqueId('tx_coretest_getImageSourceCollectionHookCalled');
        $getImageSourceCollectionHookMock = $this->getMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectOneSourceCollectionHookInterface::class, ['getOneSourceCollection'], [], $className);
        $GLOBALS['T3_VAR']['getUserObj'][$className] = $getImageSourceCollectionHookMock;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImageSourceCollection'][] = $className;

        $getImageSourceCollectionHookMock
            ->expects($this->exactly(1))
            ->method('getOneSourceCollection')
            ->will($this->returnCallback([$this, 'isGetOneSourceCollectionCalledCallback']));

        $configuration = [
            'layoutKey' => 'data',
            'layout.' => [
                'data.' => [
                    'element' => '<img src="###SRC###" ###SOURCECOLLECTION### ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###>',
                    'source' => 'data-###DATAKEY###="###SRC###"'
                ]
            ],
            'sourceCollection.' => [
                'small.' => [
                    'width' => '200',
                    'srcsetCandidate' => '600w',
                    'mediaQuery' => '(max-device-width: 600px)',
                    'dataKey' => 'small',
                ],
            ],
        ];

        $result = $this->subject->getImageSourceCollection('data', $configuration, $this->getUniqueId('testImage-'));

        $this->assertSame($result, 'isGetOneSourceCollectionCalledCallback');
    }

    /**
     * Handles the arguments that have been sent to the getImgResource hook.
     *
     * @return 	string
     * @see getImageSourceCollectionHookCalled
     */
    public function isGetOneSourceCollectionCalledCallback()
    {
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
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrl($expected, $url, array $configuration)
    {
        // Force hostname
        $this->subject->expects($this->any())->method('getEnvironmentVariable')->will($this->returnValueMap(
            [
                ['HTTP_HOST', 'localhost'],
                ['TYPO3_SITE_PATH', '/'],
            ]
        ));
        $GLOBALS['TSFE']->absRefPrefix = '';

        $this->assertEquals($expected, $this->subject->_call('forceAbsoluteUrl', $url, $configuration));
    }

    /**
     * @return array The test data for forceAbsoluteUrlReturnsAbsoluteUrl
     */
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrlDataProvider()
    {
        return [
            'Missing forceAbsoluteUrl leaves URL untouched' => [
                'foo',
                'foo',
                []
            ],
            'Absolute URL stays unchanged' => [
                'http://example.org/',
                'http://example.org/',
                [
                    'forceAbsoluteUrl' => '1'
                ]
            ],
            'Absolute URL stays unchanged 2' => [
                'http://example.org/resource.html',
                'http://example.org/resource.html',
                [
                    'forceAbsoluteUrl' => '1'
                ]
            ],
            'Scheme and host w/o ending slash stays unchanged' => [
                'http://example.org',
                'http://example.org',
                [
                    'forceAbsoluteUrl' => '1'
                ]
            ],
            'Scheme can be forced' => [
                'typo3://example.org',
                'http://example.org',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3'
                    ]
                ]
            ],
            'Relative path old-style' => [
                'http://localhost/fileadmin/dummy.txt',
                '/fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                ]
            ],
            'Relative path' => [
                'http://localhost/fileadmin/dummy.txt',
                'fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                ]
            ],
            'Scheme can be forced with pseudo-relative path' => [
                'typo3://localhost/fileadmin/dummy.txt',
                '/fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3'
                    ]
                ]
            ],
            'Hostname only is not treated as valid absolute URL' => [
                'http://localhost/example.org',
                'example.org',
                [
                    'forceAbsoluteUrl' => '1'
                ]
            ],
            'Scheme and host is added to local file path' => [
                'typo3://localhost/fileadmin/my.pdf',
                'fileadmin/my.pdf',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3'
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionCode 1414513947
     */
    public function renderingContentObjectThrowsException()
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();
        $this->subject->render($contentObjectFixture, []);
    }

    /**
     * @test
     */
    public function exceptionHandlerIsEnabledByDefaultInProductionContext()
    {
        $backupApplicationContext = GeneralUtility::getApplicationContext();
        Fixtures\GeneralUtilityFixture::setApplicationContext(new ApplicationContext('Production'));

        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();
        $this->subject->render($contentObjectFixture, []);

        Fixtures\GeneralUtilityFixture::setApplicationContext($backupApplicationContext);
    }

    /**
     * @test
     */
    public function renderingContentObjectDoesNotThrowExceptionIfExceptionHandlerIsConfiguredLocally()
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

        $configuration = [
            'exceptionHandler' => '1'
        ];
        $this->subject->render($contentObjectFixture, $configuration);
    }

    /**
     * @test
     */
    public function renderingContentObjectDoesNotThrowExceptionIfExceptionHandlerIsConfiguredGlobally()
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

        $this->typoScriptFrontendControllerMock->config['config']['contentObjectExceptionHandler'] = '1';
        $this->subject->render($contentObjectFixture, []);
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionCode 1414513947
     */
    public function globalExceptionHandlerConfigurationCanBeOverriddenByLocalConfiguration()
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

        $this->typoScriptFrontendControllerMock->config['config']['contentObjectExceptionHandler'] = '1';
        $configuration = [
            'exceptionHandler' => '0'
        ];
        $this->subject->render($contentObjectFixture, $configuration);
    }

    /**
     * @test
     */
    public function renderedErrorMessageCanBeCustomized()
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'errorMessage' => 'New message for testing',
            ]
        ];

        $this->assertSame('New message for testing', $this->subject->render($contentObjectFixture, $configuration));
    }

    /**
     * @test
     */
    public function localConfigurationOverridesGlobalConfiguration()
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

        $this->typoScriptFrontendControllerMock
            ->config['config']['contentObjectExceptionHandler.'] = [
                'errorMessage' => 'Global message for testing',
            ];
        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'errorMessage' => 'New message for testing',
            ]
        ];

        $this->assertSame('New message for testing', $this->subject->render($contentObjectFixture, $configuration));
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionCode 1414513947
     */
    public function specificExceptionsCanBeIgnoredByExceptionHandler()
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'ignoreCodes.' => ['10.' => '1414513947'],
            ]
        ];

        $this->subject->render($contentObjectFixture, $configuration);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | AbstractContentObject
     */
    protected function createContentObjectThrowingExceptionFixture()
    {
        $contentObjectFixture = $this->getMock(AbstractContentObject::class, [], [$this->subject]);
        $contentObjectFixture->expects($this->once())
            ->method('render')
            ->willReturnCallback(function () {
                throw new \LogicException('Exception during rendering', 1414513947);
            });
        return $contentObjectFixture;
    }

    /**
     * @test
     */
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrlWithSubfolder()
    {
        // Force hostname and subfolder
        $this->subject->expects($this->any())->method('getEnvironmentVariable')->will($this->returnValueMap(
            [
                ['HTTP_HOST', 'localhost'],
                ['TYPO3_SITE_PATH', '/subfolder/'],
            ]
        ));

        $expected = 'http://localhost/subfolder/fileadmin/my.pdf';
        $url = 'fileadmin/my.pdf';
        $configuration = [
            'forceAbsoluteUrl' => '1'
        ];

        $this->assertEquals($expected, $this->subject->_call('forceAbsoluteUrl', $url, $configuration));
    }

    /**
     * @return array
     */
    protected function getLibParseTarget()
    {
        return [
            'override' => '',
            'override.' => [
                'if.' => [
                    'isTrue.' => [
                        'data' => 'TSFE:dtdAllowsFrames',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getLibParseFunc()
    {
        return [
            'makelinks' => '1',
            'makelinks.' => [
                'http.' => [
                    'keep' => '{$styles.content.links.keep}',
                    'extTarget' => '',
                    'extTarget.' => $this->getLibParseTarget(),
                    'mailto.' => [
                        'keep' => 'path',
                    ],
                ],
            ],
            'tags' => [
                'link' => 'TEXT',
                'link.' => [
                    'current' => '1',
                    'typolink.' => [
                        'parameter.' => [
                            'data' => 'parameters : allParams',
                        ],
                        'extTarget.' => $this->getLibParseTarget(),
                        'target.' => $this->getLibParseTarget(),
                    ],
                    'parseFunc.' => [
                        'constants' => '1',
                    ],
                ],
            ],

            'allowTags' => 'a, abbr, acronym, address, article, aside, b, bdo, big, blockquote, br, caption, center, cite, code, col, colgroup, dd, del, dfn, dl, div, dt, em, font, footer, header, h1, h2, h3, h4, h5, h6, hr, i, img, ins, kbd, label, li, link, meta, nav, ol, p, pre, q, samp, sdfield, section, small, span, strike, strong, style, sub, sup, table, thead, tbody, tfoot, td, th, tr, title, tt, u, ul, var',
            'denyTags' => '*',
            'sword' => '<span class="csc-sword">|</span>',
            'constants' => '1',
            'nonTypoTagStdWrap.' => [
                'HTMLparser' => '1',
                'HTMLparser.' => [
                    'keepNonMatchedTags' => '1',
                    'htmlSpecialChars' => '2',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getLibParseFunc_RTE()
    {
        return [
            'parseFunc' => '',
            'parseFunc.' => [
                'allowTags' => 'a, abbr, acronym, address, article, aside, b, bdo, big, blockquote, br, caption, center, cite, code, col, colgroup, dd, del, dfn, dl, div, dt, em, font, footer, header, h1, h2, h3, h4, h5, h6, hr, i, img, ins, kbd, label, li, link, meta, nav, ol, p, pre, q, samp, sdfield, section, small, span, strike, strong, style, sub, sup, table, thead, tbody, tfoot, td, th, tr, title, tt, u, ul, var',
                'constants' => '1',
                'denyTags' => '*',
                'externalBlocks' => 'article, aside, blockquote, div, dd, dl, footer, header, nav, ol, section, table, ul, pre',
                'externalBlocks.' => [
                    'article.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'aside.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'blockquote.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'dd.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'div.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'dl.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'footer.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'header.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'nav.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'ol.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'section.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'table.' => [
                        'HTMLtableCells' => '1',
                        'HTMLtableCells.' => [
                            'addChr10BetweenParagraphs' => '1',
                            'default.' => [
                                'stdWrap.' => [
                                    'parseFunc' => '=< lib.parseFunc_RTE',
                                    'parseFunc.' => [
                                        'nonTypoTagStdWrap.' => [
                                            'encapsLines.' => [
                                                'nonWrappedTag' => '',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'stdWrap.' => [
                            'HTMLparser' => '1',
                            'HTMLparser.' => [
                                'keepNonMatchedTags' => '1',
                                'tags.' => [
                                    'table.' => [
                                        'fixAttrib.' => [
                                            'class.' => [
                                                'always' => '1',
                                                'default' => 'contenttable',
                                                'list' => 'contenttable',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'stripNL' => '1',
                    ],
                    'ul.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                ],
                'makelinks' => '1',
                'makelinks.' => [
                    'http.' => [
                        'extTarget.' =>  [
                            'override' => '_blank',
                            'override.' => [
                                'if.' => [
                                    'isTrue.' => [
                                        'data' => 'TSFE:dtdAllowsFrames',
                                    ],
                                ],
                            ],
                        ],
                        'keep' => 'path',
                    ],
                ],
                'nonTypoTagStdWrap.' => [
                    'encapsLines.' => [
                        'addAttributes.' => [
                            'P.' => [
                                'class' => 'bodytext',
                                'class.' => [
                                    'setOnly' => 'blank',
                                ],
                            ],
                        ],
                        'encapsTagList' => 'p,pre,h1,h2,h3,h4,h5,h6,hr,dt,li',
                        'innerStdWrap_all.' => [
                            'ifBlank' => '&nbsp;',
                        ],
                        'nonWrappedTag' => 'P',
                        'remapTag.' => [
                            'DIV' => 'P',
                        ],
                    ],
                    'HTMLparser' => '1',
                    'HTMLparser.' => [
                        'htmlSpecialChars' => '2',
                        'keepNonMatchedTags' => '1',
                    ],
                ],
                'sword' => '<span class="csc-sword">|</span>',
                'tags.' => [
                    'link' => 'TEXT',
                    'link.' => [
                        'current' => '1',
                        'parseFunc.' => [
                            'constants' => '1',
                        ],
                        'typolink.' => [
                            'extTarget.' =>  [
                                'override' => '',
                                'override.' => [
                                    'if.' => [
                                        'isTrue.' => [
                                            'data' => 'TSFE:dtdAllowsFrames',
                                        ],
                                    ],
                                ],
                            ],
                            'parameter.' => [
                                'data' => 'parameters : allParams',
                            ],
                            'target.' =>  [
                                'override' => '',
                                'override.' => [
                                    'if.' => [
                                        'isTrue.' => [
                                            'data' => 'TSFE:dtdAllowsFrames',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function _parseFuncReturnsCorrectHtmlDataProvider()
    {
        return [
            'Text without tag is wrapped with <p> tag' => [
                'Text without tag',
                $this->getLibParseFunc_RTE(),
                '<p class="bodytext">Text without tag</p>',
            ],
            'Text wrapped with <p> tag remains the same' => [
                '<p class="myclass">Text with &lt;p&gt; tag</p>',
                $this->getLibParseFunc_RTE(),
                '<p class="myclass">Text with &lt;p&gt; tag</p>',
            ],
            'Text with absolute external link' => [
                'Text with <link http://example.com/foo/>external link</link>',
                $this->getLibParseFunc_RTE(),
                '<p class="bodytext">Text with <a href="http://example.com/foo/">external link</a></p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider _parseFuncReturnsCorrectHtmlDataProvider
     * @param string $value
     * @param array $configuration
     * @param string $expectedResult
     */
    public function stdWrap_parseFuncReturnsParsedHtml($value, $configuration, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->subject->stdWrap_parseFunc($value, $configuration));
    }

    /**
     * @return array
     */
    public function detectLinkTypeFromLinkParameterDataProvider()
    {
        return [
            'Domain only' => [
                'example.com',
                'url'
            ],
            'URL without a file' => [
                'http://example.com',
                'url'
            ],
            'URL with schema and a file' => [
                'http://example.com/index.php',
                'url'
            ],
            'URL with a file but without a schema' => [
                'example.com/index.php',
                'url'
            ],
            'file' => [
                '/index.php',
                'file'
            ],
        ];
    }

    /**
     * @test
     * @param string $linkParameter
     * @param string $expectedResult
     * @dataProvider detectLinkTypeFromLinkParameterDataProvider
     */
    public function detectLinkTypeFromLinkParameter($linkParameter, $expectedResult)
    {
        /** @var TemplateService|\PHPUnit_Framework_MockObject_MockObject $templateServiceObjectMock */
        $templateServiceObjectMock = $this->getMock(TemplateService::class, ['dummy']);
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        /** @var TypoScriptFrontendController|\PHPUnit_Framework_MockObject_MockObject $typoScriptFrontendControllerMockObject */
        $typoScriptFrontendControllerMockObject = $this->getMock(TypoScriptFrontendController::class, [], [], '', false);
        $typoScriptFrontendControllerMockObject->config = [
            'config' => [],
            'mainScript' => 'index.php',
        ];
        $typoScriptFrontendControllerMockObject->tmpl = $templateServiceObjectMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMockObject;
        $this->subject->_set('typoScriptFrontendController', $typoScriptFrontendControllerMockObject);

        $this->assertEquals($expectedResult, $this->subject->_call('detectLinkTypeFromLinkParameter', $linkParameter));
    }

    /**
     * @return array
     */
    public function typolinkReturnsCorrectLinksForEmailsAndUrlsDataProvider()
    {
        return [
            'Link to url' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org',
                ],
                '<a href="http://typo3.org">TYPO3</a>',
            ],
            'Link to url without schema' => [
                'TYPO3',
                [
                    'parameter' => 'typo3.org',
                ],
                '<a href="http://typo3.org">TYPO3</a>',
            ],
            'Link to url without link text' => [
                '',
                [
                    'parameter' => 'http://typo3.org',
                ],
                '<a href="http://typo3.org">http://typo3.org</a>',
            ],
            'Link to url with attributes' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org',
                    'ATagParams' => 'class="url-class"',
                    'extTarget' => '_blank',
                    'title' => 'Open new window',
                ],
                '<a href="http://typo3.org" title="Open new window" target="_blank" class="url-class">TYPO3</a>',
            ],
            'Link to url with attributes in parameter' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org _blank url-class "Open new window"',
                ],
                '<a href="http://typo3.org" title="Open new window" target="_blank" class="url-class">TYPO3</a>',
            ],
            'Link to url with script tag' => [
                '',
                [
                    'parameter' => 'http://typo3.org<script>alert(123)</script>',
                ],
                '<a href="http://typo3.org&lt;script&gt;alert(123)&lt;/script&gt;">http://typo3.org&lt;script&gt;alert(123)&lt;/script&gt;</a>',
            ],
            'Link to email address' => [
                'Email address',
                [
                    'parameter' => 'foo@bar.org',
                ],
                '<a href="mailto:foo@bar.org">Email address</a>',
            ],
            'Link to email address without link text' => [
                '',
                [
                    'parameter' => 'foo@bar.org',
                ],
                '<a href="mailto:foo@bar.org">foo@bar.org</a>',
            ],
            'Link to email with attributes' => [
                'Email address',
                [
                    'parameter' => 'foo@bar.org',
                    'ATagParams' => 'class="email-class"',
                    'title' => 'Write an email',
                ],
                '<a href="mailto:foo@bar.org" title="Write an email" class="email-class">Email address</a>',
            ],
            'Link to email with attributes in parameter' => [
                'Email address',
                [
                    'parameter' => 'foo@bar.org - email-class "Write an email"',
                ],
                '<a href="mailto:foo@bar.org" title="Write an email" class="email-class">Email address</a>',
            ],
        ];
    }

    /**
     * @test
     * @param string $linkText
     * @param array $configuration
     * @param string $expectedResult
     * @dataProvider typolinkReturnsCorrectLinksForEmailsAndUrlsDataProvider
     */
    public function typolinkReturnsCorrectLinksForEmailsAndUrls($linkText, $configuration, $expectedResult)
    {
        $templateServiceObjectMock = $this->getMock(TemplateService::class, ['dummy']);
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $typoScriptFrontendControllerMockObject = $this->getMock(TypoScriptFrontendController::class, [], [], '', false);
        $typoScriptFrontendControllerMockObject->config = [
            'config' => [],
            'mainScript' => 'index.php',
        ];
        $typoScriptFrontendControllerMockObject->tmpl = $templateServiceObjectMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMockObject;
        $this->subject->_set('typoScriptFrontendController', $typoScriptFrontendControllerMockObject);

        $this->assertEquals($expectedResult, $this->subject->typoLink($linkText, $configuration));
    }

    /**
     * @return array
     */
    public function typolinkReturnsCorrectLinksForPagesDataProvider()
    {
        return [
            'Link to page' => [
                'My page',
                [
                    'parameter' => 42,
                ],
                [
                    'uid' => 42,
                    'title' => 'Page title',
                ],
                '<a href="index.php?id=42">My page</a>',
            ],
            'Link to page without link text' => [
                '',
                [
                    'parameter' => 42,
                ],
                [
                    'uid' => 42,
                    'title' => 'Page title',
                ],
                '<a href="index.php?id=42">Page title</a>',
            ],
            'Link to page with attributes' => [
                'My page',
                [
                    'parameter' => '42',
                    'ATagParams' => 'class="page-class"',
                    'target' => '_self',
                    'title' => 'Link to internal page',
                ],
                [
                    'uid' => 42,
                    'title' => 'Page title',
                ],
                '<a href="index.php?id=42" title="Link to internal page" target="_self" class="page-class">My page</a>',
            ],
            'Link to page with attributes in parameter' => [
                'My page',
                [
                    'parameter' => '42 _self page-class "Link to internal page"',
                ],
                [
                    'uid' => 42,
                    'title' => 'Page title',
                ],
                '<a href="index.php?id=42" title="Link to internal page" target="_self" class="page-class">My page</a>',
            ],
            'Link to page with bold tag in title' => [
                '',
                [
                    'parameter' => 42,
                ],
                [
                    'uid' => 42,
                    'title' => 'Page <b>title</b>',
                ],
                '<a href="index.php?id=42">Page <b>title</b></a>',
            ],
            'Link to page with script tag in title' => [
                '',
                [
                    'parameter' => 42,
                ],
                [
                    'uid' => 42,
                    'title' => '<script>alert(123)</script>Page title',
                ],
                '<a href="index.php?id=42">&lt;script&gt;alert(123)&lt;/script&gt;Page title</a>',
            ],
        ];
    }

    /**
     * @param array $settings
     * @param string $linkText
     * @param string $mailAddress
     * @param string $expected
     * @dataProvider typoLinkEncodesMailAddressForSpamProtectionDataProvider
     * @test
     */
    public function typoLinkEncodesMailAddressForSpamProtection(array $settings, $linkText, $mailAddress, $expected)
    {
        $this->getFrontendController()->spamProtectEmailAddresses = $settings['spamProtectEmailAddresses'];
        $this->getFrontendController()->config['config'] = $settings;
        $typoScript = ['parameter' => $mailAddress];

        $this->assertEquals($expected, $this->subject->typoLink($linkText, $typoScript));
    }

    /**
     * @return array
     */
    public function typoLinkEncodesMailAddressForSpamProtectionDataProvider()
    {
        return [
            'plain mail without mailto scheme' => [
                [
                    'spamProtectEmailAddresses' => '',
                    'spamProtectEmailAddresses_atSubst' => '',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'some.body@test.typo3.org',
                '<a href="mailto:some.body@test.typo3.org">some.body@test.typo3.org</a>',
            ],
            'plain mail with mailto scheme' => [
                [
                    'spamProtectEmailAddresses' => '',
                    'spamProtectEmailAddresses_atSubst' => '',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="mailto:some.body@test.typo3.org">some.body@test.typo3.org</a>',
            ],
            'plain with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => '0',
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="mailto:some.body@test.typo3.org">some.body@test.typo3.org</a>',
            ],
            'mono-alphabetic substitution offset +1' => [
                [
                    'spamProtectEmailAddresses' => '1',
                    'spamProtectEmailAddresses_atSubst' => '',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="javascript:linkTo_UnCryptMailto(\'nbjmup+tpnf\/cpezAuftu\/uzqp4\/psh\');">some.body(at)test.typo3.org</a>',
            ],
            'mono-alphabetic substitution offset +1 with at substitution' => [
                [
                    'spamProtectEmailAddresses' => '1',
                    'spamProtectEmailAddresses_atSubst' => '@',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="javascript:linkTo_UnCryptMailto(\'nbjmup+tpnf\/cpezAuftu\/uzqp4\/psh\');">some.body@test.typo3.org</a>',
            ],
            'mono-alphabetic substitution offset +1 with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => '1',
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="javascript:linkTo_UnCryptMailto(\'nbjmup+tpnf\/cpezAuftu\/uzqp4\/psh\');">some.body(at)test.typo3(dot)org</a>',
            ],
            'mono-alphabetic substitution offset -1 with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => '-1',
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="javascript:linkTo_UnCryptMailto(\'lzhksn9rnld-ancxZsdrs-sxon2-nqf\');">some.body(at)test.typo3(dot)org</a>',
            ],
            'entity substitution with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => 'ascii',
                    'spamProtectEmailAddresses_atSubst' => '',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#115;&#111;&#109;&#101;&#46;&#98;&#111;&#100;&#121;&#64;&#116;&#101;&#115;&#116;&#46;&#116;&#121;&#112;&#111;&#51;&#46;&#111;&#114;&#103;">some.body(at)test.typo3.org</a>',
            ],
            'entity substitution with at and dot substitution with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => 'ascii',
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#115;&#111;&#109;&#101;&#46;&#98;&#111;&#100;&#121;&#64;&#116;&#101;&#115;&#116;&#46;&#116;&#121;&#112;&#111;&#51;&#46;&#111;&#114;&#103;">some.body(at)test.typo3(dot)org</a>',
            ],
        ];
    }

    /**
     * @test
     * @param string $linkText
     * @param array $configuration
     * @param array $pageArray
     * @param string $expectedResult
     * @dataProvider typolinkReturnsCorrectLinksForPagesDataProvider
     */
    public function typolinkReturnsCorrectLinksForPages($linkText, $configuration, $pageArray, $expectedResult)
    {
        $pageRepositoryMockObject = $this->getMock(\TYPO3\CMS\Frontend\Page\PageRepository::class, ['getPage']);
        $pageRepositoryMockObject->expects($this->any())->method('getPage')->willReturn($pageArray);
        $templateServiceObjectMock = $this->getMock(TemplateService::class, ['dummy']);
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $typoScriptFrontendControllerMockObject = $this->getMock(TypoScriptFrontendController::class, [], [], '', false);
        $typoScriptFrontendControllerMockObject->config = [
            'config' => [],
            'mainScript' => 'index.php',
        ];
        $typoScriptFrontendControllerMockObject->sys_page = $pageRepositoryMockObject;
        $typoScriptFrontendControllerMockObject->tmpl = $templateServiceObjectMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMockObject;
        $this->subject->_set('typoScriptFrontendController', $typoScriptFrontendControllerMockObject);

        $this->assertEquals($expectedResult, $this->subject->typoLink($linkText, $configuration));
    }

    /**
     * @return array
     */
    public function typolinkReturnsCorrectLinksFilesDataProvider()
    {
        return [
            'Link to file' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                ],
                '<a href="fileadmin/foo.bar">My file</a>',
            ],
            'Link to file without link text' => [
                '',
                [
                    'parameter' => 'fileadmin/foo.bar',
                ],
                '<a href="fileadmin/foo.bar">fileadmin/foo.bar</a>',
            ],
            'Link to file with attributes' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to file with attributes in parameter' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar _blank file-class "Title of the file"',
                ],
                '<a href="fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to file with script tag in name' => [
                '',
                [
                    'parameter' => 'fileadmin/<script>alert(123)</script>',
                ],
                '<a href="fileadmin/&lt;script&gt;alert(123)&lt;/script&gt;">fileadmin/&lt;script&gt;alert(123)&lt;/script&gt;</a>',
            ],
        ];
    }

    /**
     * @test
     * @param string $linkText
     * @param array $configuration
     * @param string $expectedResult
     * @dataProvider typolinkReturnsCorrectLinksFilesDataProvider
     */
    public function typolinkReturnsCorrectLinksFiles($linkText, $configuration, $expectedResult)
    {
        $templateServiceObjectMock = $this->getMock(TemplateService::class, ['dummy']);
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $typoScriptFrontendControllerMockObject = $this->getMock(TypoScriptFrontendController::class, [], [], '', false);
        $typoScriptFrontendControllerMockObject->config = [
            'config' => [],
            'mainScript' => 'index.php',
        ];
        $typoScriptFrontendControllerMockObject->tmpl = $templateServiceObjectMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMockObject;
        $this->subject->_set('typoScriptFrontendController', $typoScriptFrontendControllerMockObject);

        $this->assertEquals($expectedResult, $this->subject->typoLink($linkText, $configuration));
    }

    /**
     * @return array
     */
    public function typolinkReturnsCorrectLinksForFilesWithAbsRefPrefixDataProvider()
    {
        return [
            'Link to file' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                ],
                '/',
                '<a href="/fileadmin/foo.bar">My file</a>',
            ],
            'Link to file with longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                ],
                '/sub/',
                '<a href="/sub/fileadmin/foo.bar">My file</a>',
            ],
            'Link to absolute file' => [
                'My file',
                [
                    'parameter' => '/images/foo.bar',
                ],
                '/',
                '<a href="/images/foo.bar">My file</a>',
            ],
            'Link to absolute file with longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/images/foo.bar',
                ],
                '/sub/',
                '<a href="/images/foo.bar">My file</a>',
            ],
            'Link to absolute file with identical longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/sub/fileadmin/foo.bar',
                ],
                '/sub/',
                '<a href="/sub/fileadmin/foo.bar">My file</a>',
            ],
            'Link to file with empty absRefPrefix' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                ],
                '',
                '<a href="fileadmin/foo.bar">My file</a>',
            ],
            'Link to absolute file with empty absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/fileadmin/foo.bar',
                ],
                '',
                '<a href="/fileadmin/foo.bar">My file</a>',
            ],
            'Link to file with attributes with absRefPrefix' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '/',
                '<a href="/fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to file with attributes with longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '/sub/',
                '<a href="/sub/fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to absolute file with attributes with absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/images/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '/',
                '<a href="/images/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to absolute file with attributes with longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/images/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '/sub/',
                '<a href="/images/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to absolute file with attributes with identical longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/sub/fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '/sub/',
                '<a href="/sub/fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
        ];
    }

    /**
     * @test
     * @param string $linkText
     * @param array $configuration
     * @param string $absRefPrefix
     * @param string $expectedResult
     * @dataProvider typolinkReturnsCorrectLinksForFilesWithAbsRefPrefixDataProvider
     */
    public function typolinkReturnsCorrectLinksForFilesWithAbsRefPrefix($linkText, $configuration, $absRefPrefix, $expectedResult)
    {
        $templateServiceObjectMock = $this->getMock(TemplateService::class, ['dummy']);
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $typoScriptFrontendControllerMockObject = $this->getMock(TypoScriptFrontendController::class, [], [], '', false);
        $typoScriptFrontendControllerMockObject->config = [
            'config' => [],
            'mainScript' => 'index.php',
        ];
        $typoScriptFrontendControllerMockObject->tmpl = $templateServiceObjectMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMockObject;
        $GLOBALS['TSFE']->absRefPrefix = $absRefPrefix;
        $this->subject->_set('typoScriptFrontendController', $typoScriptFrontendControllerMockObject);

        $this->assertEquals($expectedResult, $this->subject->typoLink($linkText, $configuration));
    }

    /**
     * @test
     */
    public function stdWrap_splitObjReturnsCount()
    {
        $conf = [
            'token' => ',',
            'returnCount' => 1
        ];
        $expectedResult = 5;
        $amountOfEntries = $this->subject->splitObj('1, 2, 3, 4, 5', $conf);
        $this->assertSame(
            $expectedResult,
            $amountOfEntries
        );
    }

    /**
     * @return array
     */
    public function getWhereReturnCorrectQueryDataProvider()
    {
        return [
            [
                [
                    'tt_content' => [
                        'ctrl' => [
                        ],
                        'columns' => [
                        ]
                    ],
                ],
                'tt_content',
                [
                    'uidInList' => '42',
                    'pidInList' => 43,
                    'where' => 'tt_content.cruser_id=5',
                    'andWhere' => 'tt_content.crdate>0',
                    'groupBy' => 'tt_content.title',
                    'orderBy' => 'tt_content.sorting',
                ],
                'WHERE tt_content.uid=42 AND tt_content.pid IN (43) AND tt_content.cruser_id=5 AND tt_content.crdate>0 GROUP BY tt_content.title ORDER BY tt_content.sorting',
            ],
            [
                [
                    'tt_content' => [
                        'ctrl' => [
                            'delete' => 'deleted',
                            'enablecolumns' => [
                                'disabled' => 'hidden',
                                'starttime' => 'startdate',
                                'endtime' => 'enddate',
                            ],
                            'languageField' => 'sys_language_uid',
                            'transOrigPointerField' => 'l18n_parent',
                        ],
                        'columns' => [
                        ]
                    ],
                ],
                'tt_content',
                [
                    'uidInList' => 42,
                    'pidInList' => 43,
                    'where' => 'tt_content.cruser_id=5',
                    'andWhere' => 'tt_content.crdate>0',
                    'groupBy' => 'tt_content.title',
                    'orderBy' => 'tt_content.sorting',
                ],
                'WHERE tt_content.uid=42 AND tt_content.pid IN (43) AND tt_content.cruser_id=5 AND (tt_content.sys_language_uid = 13) AND tt_content.crdate>0 AND tt_content.deleted=0 AND tt_content.hidden=0 AND tt_content.startdate<=4242 AND (tt_content.enddate=0 OR tt_content.enddate>4242) GROUP BY tt_content.title ORDER BY tt_content.sorting',
            ],
            [
                [
                    'tt_content' => [
                        'ctrl' => [
                            'languageField' => 'sys_language_uid',
                            'transOrigPointerField' => 'l18n_parent',
                        ],
                        'columns' => [
                        ]
                    ],
                ],
                'tt_content',
                [
                    'uidInList' => 42,
                    'pidInList' => 43,
                    'where' => 'tt_content.cruser_id=5',
                    'languageField' => 0,
                ],
                'WHERE tt_content.uid=42 AND tt_content.pid IN (43) AND tt_content.cruser_id=5',
            ],
        ];
    }

    /**
     * @test
     * @param array $tca
     * @param string $table
     * @param array $configuration
     * @param string $expectedResult
     * @dataProvider getWhereReturnCorrectQueryDataProvider
     */
    public function getWhereReturnCorrectQuery($tca, $table, $configuration, $expectedResult)
    {
        $GLOBALS['TCA'] = $tca;
        $GLOBALS['SIM_ACCESS_TIME'] = '4242';
        $GLOBALS['TSFE']->sys_language_content = 13;
        /** @var \PHPUnit_Framework_MockObject_MockObject|ContentObjectRenderer $contentObjectRenderer */
        $contentObjectRenderer = $this->getMock(ContentObjectRenderer::class, ['checkPidArray']);
        $contentObjectRenderer->expects($this->any())->method('checkPidArray')->willReturn(explode(',', $configuration['pidInList']));
        $this->assertEquals($expectedResult, $contentObjectRenderer->getWhere($table, $configuration));
    }

    ////////////////////////////////////
    // Test concerning link generation
    ////////////////////////////////////

    /**
     * @test
     */
    public function filelinkCreatesCorrectUrlForFileWithUrlEncodedSpecialChars()
    {
        $fileNameAndPath = PATH_site . 'typo3temp/phpunitJumpUrlTestFile with spaces & amps.txt';
        file_put_contents($fileNameAndPath, 'Some test data');
        $relativeFileNameAndPath = substr($fileNameAndPath, strlen(PATH_site));
        $fileName = substr($fileNameAndPath, strlen(PATH_site . 'typo3temp/'));

        $expectedLink = str_replace('%2F', '/', rawurlencode($relativeFileNameAndPath));
        $result = $this->subject->filelink($fileName, ['path' => 'typo3temp/']);
        $this->assertEquals('<a href="' . $expectedLink . '">' . $fileName . '</a>', $result);

        \TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($fileNameAndPath);
    }

    /**
     * @return array
     */
    public function substituteMarkerArrayCachedReturnsExpectedContentDataProvider()
    {
        return [
            'no markers defined' => [
                'dummy content with ###UNREPLACED### marker',
                [],
                [],
                [],
                'dummy content with ###UNREPLACED### marker',
                false,
                false
            ],
            'no markers used' => [
                'dummy content with no marker',
                [
                    '###REPLACED###' => '_replaced_'
                ],
                [],
                [],
                'dummy content with no marker',
                true,
                false
            ],
            'one marker' => [
                'dummy content with ###REPLACED### marker',
                [
                    '###REPLACED###' => '_replaced_'
                ],
                [],
                [],
                'dummy content with _replaced_ marker'
            ],
            'one marker with lots of chars' => [
                'dummy content with ###RE.:##-=_()LACED### marker',
                [
                    '###RE.:##-=_()LACED###' => '_replaced_'
                ],
                [],
                [],
                'dummy content with _replaced_ marker'
            ],
            'markers which are special' => [
                'dummy ###aa##.#######A### ######',
                [
                    '###aa##.###' => 'content ',
                    '###A###' => 'is',
                    '######' => '-is not considered-'
                ],
                [],
                [],
                'dummy content #is ######'
            ],
            'two markers in content, but more defined' => [
                'dummy ###CONTENT### with ###REPLACED### marker',
                [
                    '###REPLACED###' => '_replaced_',
                    '###CONTENT###' => 'content',
                    '###NEVERUSED###' => 'bar'
                ],
                [],
                [],
                'dummy content with _replaced_ marker'
            ],
            'one subpart' => [
                'dummy content with ###ASUBPART### around some text###ASUBPART###.',
                [],
                [
                    '###ASUBPART###' => 'some other text'
                ],
                [],
                'dummy content with some other text.'
            ],
            'one wrapped subpart' => [
                'dummy content with ###AWRAPPEDSUBPART### around some text###AWRAPPEDSUBPART###.',
                [],
                [],
                [
                    '###AWRAPPEDSUBPART###' => [
                        'more content',
                        'content'
                    ]
                ],
                'dummy content with more content around some textcontent.'
            ],
            'one subpart with markers, not replaced recursively' => [
                'dummy ###CONTENT### with ###ASUBPART### around ###SOME### text###ASUBPART###.',
                [
                    '###CONTENT###' => 'content',
                    '###SOME###' => '-this should never make it into output-',
                    '###OTHER_NOT_REPLACED###' => '-this should never make it into output-'
                ],
                [
                    '###ASUBPART###' => 'some ###OTHER_NOT_REPLACED### text'
                ],
                [],
                'dummy content with some ###OTHER_NOT_REPLACED### text.'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider substituteMarkerArrayCachedReturnsExpectedContentDataProvider
     *
     * @param string $content
     * @param array $markContentArray
     * @param array $subpartContentArray
     * @param array $wrappedSubpartContentArray
     * @param string $expectedContent
     * @param bool $shouldQueryCache
     * @param bool $shouldStoreCache
     */
    public function substituteMarkerArrayCachedReturnsExpectedContent($content, array $markContentArray, array $subpartContentArray, array $wrappedSubpartContentArray, $expectedContent, $shouldQueryCache = true, $shouldStoreCache = true)
    {
        /** @var PageRepositoryFixture|\PHPUnit_Framework_MockObject_MockObject $pageRepo */
        $pageRepo = $this->typoScriptFrontendControllerMock->sys_page;
        $pageRepo->resetCallCount();

        $resultContent = $this->subject->substituteMarkerArrayCached($content, $markContentArray, $subpartContentArray, $wrappedSubpartContentArray);

        $this->assertSame((int)$shouldQueryCache, $pageRepo::$getHashCallCount, 'getHash call count mismatch');
        $this->assertSame((int)$shouldStoreCache, $pageRepo::$storeHashCallCount, 'storeHash call count mismatch');
        $this->assertSame($expectedContent, $resultContent);
    }

    /**
     * @test
     */
    public function substituteMarkerArrayCachedRetrievesCachedValueFromRuntimeCache()
    {
        /** @var PageRepositoryFixture|\PHPUnit_Framework_MockObject_MockObject $pageRepo */
        $pageRepo = $this->typoScriptFrontendControllerMock->sys_page;
        $pageRepo->resetCallCount();

        $content = 'Please tell me this ###FOO###.';
        $markContentArray = [
            '###FOO###' => 'foo',
            '###NOTUSED###' => 'blub'
        ];
        $storeKey = md5('substituteMarkerArrayCached_storeKey:' . serialize([$content, array_keys($markContentArray)]));
        $this->subject->substMarkerCache[$storeKey] = [
            'c' => [
                'Please tell me this ',
                '.'
            ],
            'k' => [
                '###FOO###'
            ],
        ];
        $resultContent = $this->subject->substituteMarkerArrayCached($content, $markContentArray);
        $this->assertSame(0, $pageRepo::$getHashCallCount);
        $this->assertSame('Please tell me this foo.', $resultContent);
    }

    /**
     * @test
     */
    public function substituteMarkerArrayCachedRetrievesCachedValueFromDbCache()
    {
        /** @var PageRepositoryFixture|\PHPUnit_Framework_MockObject_MockObject $pageRepo */
        $pageRepo = $this->typoScriptFrontendControllerMock->sys_page;
        $pageRepo->resetCallCount();

        $content = 'Please tell me this ###FOO###.';
        $markContentArray = [
            '###FOO###' => 'foo',
            '###NOTUSED###' => 'blub'
        ];
        $pageRepo::$dbCacheContent = [
            'c' => [
                'Please tell me this ',
                '.'
            ],
            'k' => [
                '###FOO###'
            ],
        ];
        $resultContent = $this->subject->substituteMarkerArrayCached($content, $markContentArray);
        $this->assertSame(1, $pageRepo::$getHashCallCount, 'getHash call count mismatch');
        $this->assertSame(0, $pageRepo::$storeHashCallCount, 'storeHash call count mismatch');
        $this->assertSame('Please tell me this foo.', $resultContent);
    }

    /**
     * @test
     */
    public function substituteMarkerArrayCachedStoresResultInCaches()
    {
        /** @var PageRepositoryFixture|\PHPUnit_Framework_MockObject_MockObject $pageRepo */
        $pageRepo = $this->typoScriptFrontendControllerMock->sys_page;
        $pageRepo->resetCallCount();

        $content = 'Please tell me this ###FOO###.';
        $markContentArray = [
            '###FOO###' => 'foo',
            '###NOTUSED###' => 'blub'
        ];
        $resultContent = $this->subject->substituteMarkerArrayCached($content, $markContentArray);

        $storeKey = md5('substituteMarkerArrayCached_storeKey:' . serialize([$content, array_keys($markContentArray)]));
        $storeArr = [
            'c' => [
                'Please tell me this ',
                '.'
            ],
            'k' => [
                '###FOO###'
            ],
        ];
        $this->assertSame(1, $pageRepo::$getHashCallCount);
        $this->assertSame('Please tell me this foo.', $resultContent);
        $this->assertSame($storeArr, $this->subject->substMarkerCache[$storeKey]);
        $this->assertSame(1, $pageRepo::$storeHashCallCount);
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
