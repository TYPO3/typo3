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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface as CacheFrontendInterface;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\CaseContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayInternalContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectOneSourceCollectionHookInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectStdWrapHookInterface;
use TYPO3\CMS\Frontend\ContentObject\EditPanelContentObject;
use TYPO3\CMS\Frontend\ContentObject\FileContentObject;
use TYPO3\CMS\Frontend\ContentObject\FilesContentObject;
use TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject;
use TYPO3\CMS\Frontend\ContentObject\HierarchicalMenuContentObject;
use TYPO3\CMS\Frontend\ContentObject\ImageContentObject;
use TYPO3\CMS\Frontend\ContentObject\ImageResourceContentObject;
use TYPO3\CMS\Frontend\ContentObject\LoadRegisterContentObject;
use TYPO3\CMS\Frontend\ContentObject\RecordsContentObject;
use TYPO3\CMS\Frontend\ContentObject\RestoreRegisterContentObject;
use TYPO3\CMS\Frontend\ContentObject\ScalableVectorGraphicsContentObject;
use TYPO3\CMS\Frontend\ContentObject\TemplateContentObject;
use TYPO3\CMS\Frontend\ContentObject\TextContentObject;
use TYPO3\CMS\Frontend\ContentObject\UserContentObject;
use TYPO3\CMS\Frontend\ContentObject\UserInternalContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Fixtures\PageRepositoryFixture;

/**
 * Testcase for TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
 */
class ContentObjectRendererTest extends UnitTestCase
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
    protected $frontendControllerMock = null;

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
        'TEXT'             => TextContentObject::class,
        'CASE'             => CaseContentObject::class,
        'COBJ_ARRAY'       => ContentObjectArrayContentObject::class,
        'COA'              => ContentObjectArrayContentObject::class,
        'COA_INT'          => ContentObjectArrayInternalContentObject::class,
        'USER'             => UserContentObject::class,
        'USER_INT'         => UserInternalContentObject::class,
        'FILE'             => FileContentObject::class,
        'FILES'            => FilesContentObject::class,
        'IMAGE'            => ImageContentObject::class,
        'IMG_RESOURCE'     => ImageResourceContentObject::class,
        'CONTENT'          => ContentContentObject::class,
        'RECORDS'          => RecordsContentObject::class,
        'HMENU'            => HierarchicalMenuContentObject::class,
        'CASEFUNC'         => CaseContentObject::class,
        'LOAD_REGISTER'    => LoadRegisterContentObject::class,
        'RESTORE_REGISTER' => RestoreRegisterContentObject::class,
        'TEMPLATE'         => TemplateContentObject::class,
        'FLUIDTEMPLATE'    => FluidTemplateContentObject::class,
        'SVG'              => ScalableVectorGraphicsContentObject::class,
        'EDITPANEL'        => EditPanelContentObject::class
    ];

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->currentLocale = setlocale(LC_NUMERIC, 0);
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->createMockedLoggerAndLogManager();

        $this->templateServiceMock =
            $this->getMockBuilder(TemplateService::class)
            ->setMethods(['getFileName', 'linkData'])->getMock();
        $pageRepositoryMock =
            $this->getMockBuilder(PageRepositoryFixture::class)
            ->setMethods(['getRawRecord', 'getMountPointInfo'])->getMock();
        $this->frontendControllerMock =
            $this->getAccessibleMock(TypoScriptFrontendController::class,
            ['dummy'], [], '', false);
        $this->frontendControllerMock->tmpl = $this->templateServiceMock;
        $this->frontendControllerMock->config = [];
        $this->frontendControllerMock->page =  [];
        $this->frontendControllerMock->sys_page = $pageRepositoryMock;
        $GLOBALS['TSFE'] = $this->frontendControllerMock;

        $this->subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            ['getResourceFactory', 'getEnvironmentVariable'],
            [$this->frontendControllerMock]
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
     * @return TypoScriptFrontendController
     */
    protected function getFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Avoid logging to the file system (file writer is currently the only configured writer)
     */
    protected function createMockedLoggerAndLogManager()
    {
        $logManagerMock = $this->getMockBuilder(LogManager::class)->getMock();
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logManagerMock->expects($this->any())
            ->method('getLogger')
            ->willReturn($loggerMock);
        GeneralUtility::setSingletonInstance(LogManager::class, $logManagerMock);
    }

    /**
     * Converts the subject and the expected result into utf-8.
     *
     * @param string $subject the subject, will be modified
     * @param string $expected the expected result, will be modified
     */
    protected function handleCharset(&$subject, &$expected)
    {
        $charsetConverter = new CharsetConverter();
        $subject = $charsetConverter->conv($subject, 'iso-8859-1', 'utf-8');
        $expected = $charsetConverter->conv($expected, 'iso-8859-1', 'utf-8');
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

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $this->subject->expects($this->any())->method('getResourceFactory')->will($this->returnValue($resourceFactory));

        $className = $this->getUniqueId('tx_coretest');
        $getImgResourceHookMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\ContentObject\ContentObjectGetImageResourceHookInterface::class)
            ->setMethods(['getImgResourcePostProcess'])
            ->setMockClassName($className)
            ->getMock();
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
     * @param string $file
     * @param array $fileArray
     * @param $imageResource
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parent
     * @return array
     * @see getImgResourceHookGetsCalled
     */
    public function isGetImgResourceHookCalledCallback($file, $fileArray, $imageResource, $parent)
    {
        $this->assertEquals('typo3/clear.gif', $file);
        $this->assertEquals('typo3/clear.gif', $imageResource['origFile']);
        $this->assertTrue(is_array($fileArray));
        $this->assertTrue($parent instanceof ContentObjectRenderer);
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
        $contentObjectInstance = $this->createMock($fullClassName);
        GeneralUtility::addInstance($fullClassName, $contentObjectInstance);
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

    //////////////////////////////
    // Tests concerning cropHTML
    //////////////////////////////

    /**
     * Data provider for cropHTML.
     *
     * Provides combinations of text type and configuration.
     *
     * @return array [$expect, $conf, $content]
     */
    public function cropHTMLDataProvider()
    {
        $plainText = 'Kasper Sk' . chr(229) . 'rh' . chr(248)
            . 'j implemented the original version of the crop function.';
        $textWithMarkup = '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
            . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> the '
            . 'original version of the crop function.';
        $textWithEntities = 'Kasper Sk&aring;rh&oslash;j implemented the; '
            . 'original ' . 'version of the crop function.';
        $textWithLinebreaks = "Lorem ipsum dolor sit amet,\n"
            . "consetetur sadipscing elitr,\n"
            . 'sed diam nonumy eirmod tempor invidunt ut labore e'
            . 't dolore magna aliquyam';

        return [
            'plain text; 11|...' => [
                'Kasper Sk' . chr(229) . 'r...',
                $plainText, '11|...',
            ],
            'plain text; -58|...' => [
                '...h' . chr(248) . 'j implemented the original version of '
                . 'the crop function.',
                $plainText, '-58|...',
            ],
            'plain text; 4|...|1' => [
                'Kasp...',
                $plainText, '4|...|1',
            ],
            'plain text; 20|...|1' => [
                'Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j...',
                $plainText, '20|...|1',
            ],
            'plain text; -5|...|1' => [
                '...tion.',
                $plainText, '-5|...|1',
            ],
            'plain text; -49|...|1' => [
                '...the original version of the crop function.',
                $plainText, '-49|...|1',
            ],
            'text with markup; 11|...' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'r...</a></strong>',
                    $textWithMarkup, '11|...',
            ],
            'text with markup; 13|...' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . '...</a></strong>',
                    $textWithMarkup, '13|...',
            ],
            'text with markup; 14|...' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
                    $textWithMarkup, '14|...',
            ],
            'text with markup; 15|...' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a> ...</strong>',
                    $textWithMarkup, '15|...',
            ],
            'text with markup; 29|...' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> '
                . 'th...',
                $textWithMarkup, '29|...',
            ],
            'text with markup; -58|...' => [
                '<strong><a href="mailto:kasper@typo3.org">...h' . chr(248)
                . 'j</a> implemented</strong> the original version of the crop '
                . 'function.',
                $textWithMarkup, '-58|...',
            ],
            'text with markup 4|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasp...</a>'
                . '</strong>',
                $textWithMarkup, '4|...|1',
            ],
            'text with markup; 11|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper...</a>'
                . '</strong>',
                $textWithMarkup, '11|...|1',
            ],
            'text with markup; 13|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper...</a>'
                . '</strong>',
                $textWithMarkup, '13|...|1',
            ],
            'text with markup; 14|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
                 $textWithMarkup, '14|...|1',
            ],
            'text with markup; 15|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
                $textWithMarkup, '15|...|1',
            ],
            'text with markup; 29|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong>...',
                $textWithMarkup, '29|...|1',
            ],
            'text with markup; -66|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">...Sk' . chr(229)
                . 'rh' . chr(248) . 'j</a> implemented</strong> the original v'
                . 'ersion of the crop function.',
                $textWithMarkup, '-66|...|1',
            ],
            'text with entities 9|...' => [
                'Kasper Sk...',
                $textWithEntities, '9|...',
            ],
            'text with entities 10|...' => [
                'Kasper Sk&aring;...',
                $textWithEntities, '10|...',
            ],
            'text with entities 11|...' => [
                'Kasper Sk&aring;r...',
                $textWithEntities, '11|...',
            ],
            'text with entities 13|...' => [
                'Kasper Sk&aring;rh&oslash;...',
                $textWithEntities, '13|...',
            ],
            'text with entities 14|...' => [
                'Kasper Sk&aring;rh&oslash;j...',
                $textWithEntities, '14|...',
            ],
            'text with entities 15|...' => [
                'Kasper Sk&aring;rh&oslash;j ...',
                $textWithEntities, '15|...',
            ],
            'text with entities 16|...' => [
                'Kasper Sk&aring;rh&oslash;j i...',
                $textWithEntities, '16|...',
            ],
            'text with entities -57|...' => [
                '...j implemented the; original version of the crop function.',
                $textWithEntities, '-57|...',
            ],
            'text with entities -58|...' => [
                '...&oslash;j implemented the; original version of the crop '
                . 'function.',
                $textWithEntities, '-58|...',
            ],
            'text with entities -59|...' => [
                '...h&oslash;j implemented the; original version of the crop '
                . 'function.',
                $textWithEntities, '-59|...',
            ],
            'text with entities 4|...|1' => [
                'Kasp...',
                $textWithEntities, '4|...|1',
            ],
            'text with entities 9|...|1' => [
                'Kasper...',
                $textWithEntities, '9|...|1',
            ],
            'text with entities 10|...|1' => [
                'Kasper...',
                $textWithEntities, '10|...|1',
            ],
            'text with entities 11|...|1' => [
                'Kasper...',
                $textWithEntities, '11|...|1',
            ],
            'text with entities 13|...|1' => [
                'Kasper...',
                $textWithEntities, '13|...|1',
            ],
            'text with entities 14|...|1' => [
                'Kasper Sk&aring;rh&oslash;j...',
                $textWithEntities, '14|...|1',
            ],
            'text with entities 15|...|1' => [
                'Kasper Sk&aring;rh&oslash;j...',
                $textWithEntities, '15|...|1',
            ],
            'text with entities 16|...|1' => [
                'Kasper Sk&aring;rh&oslash;j...',
                $textWithEntities, '16|...|1',
            ],
            'text with entities -57|...|1' => [
                '...implemented the; original version of the crop function.',
                $textWithEntities, '-57|...|1',
            ],
            'text with entities -58|...|1' => [
                '...implemented the; original version of the crop function.',
                $textWithEntities, '-58|...|1',
            ],
            'text with entities -59|...|1' => [
                '...implemented the; original version of the crop function.',
                $textWithEntities, '-59|...|1',
            ],
            'text with dash in html-element 28|...|1' => [
                'Some text with a link to <link email.address@example.org - '
                . 'mail "Open email window">my...</link>',
                'Some text with a link to <link email.address@example.org - m'
                . 'ail "Open email window">my email.address@example.org<'
                . '/link> and text after it',
                '28|...|1',
            ],
            'html elements with dashes in attributes' => [
                '<em data-foo="x">foobar</em>foo',
                '<em data-foo="x">foobar</em>foobaz',
                '9',
            ],
            'html elements with iframe embedded 24|...|1' => [
                'Text with iframe <iframe src="//what.ever/"></iframe> and...',
                'Text with iframe <iframe src="//what.ever/">'
                . '</iframe> and text after it',
                '24|...|1',
            ],
            'html elements with script tag embedded 24|...|1' => [
                'Text with script <script>alert(\'foo\');</script> and...',
                'Text with script <script>alert(\'foo\');</script> '
                . 'and text after it',
                '24|...|1',
            ],
            'text with linebreaks' => [
                "Lorem ipsum dolor sit amet,\nconsetetur sadipscing elitr,\ns"
                . 'ed diam nonumy eirmod tempor invidunt ut labore e'
                . 't dolore magna',
                $textWithLinebreaks, '121',
            ],
        ];
    }

    /**
     * Check if cropHTML works properly.
     *
     * @test
     * @dataProvider cropHTMLDataProvider
     * @param string $expect The expected cropped output.
     * @param string $content The given input.
     * @param string $conf The given configuration.
     * @return void
     */
    public function cropHTML($expect, $content, $conf)
    {
        $this->handleCharset($content, $expect);
        $this->assertSame($expect,
            $this->subject->cropHTML($content, $conf));
    }

    /**
     * Data provider for round
     *
     * @return array [$expect, $contet, $conf]
     */
    public function roundDataProvider()
    {
        return [
            // floats
            'down' => [1.0, 1.11, []],
            'up' => [2.0, 1.51, []],
            'rounds up from x.50' => [2.0, 1.50, []],
            'down with decimals' => [0.12, 0.1231, ['decimals' => 2]],
            'up with decimals' => [0.13, 0.1251, ['decimals' => 2]],
            'ceil' => [1.0, 0.11, ['roundType' => 'ceil']],
            'ceil does not accept decimals' => [
                1.0, 0.111, [
                    'roundType' => 'ceil',
                    'decimals' => 2,
                ],
            ],
            'floor' => [2.0, 2.99, ['roundType' => 'floor']],
            'floor does not accept decimals' => [
                2.0, 2.999, [
                    'roundType' => 'floor',
                    'decimals' => 2,
                ],
            ],
            'round, down' => [1.0, 1.11, ['roundType' => 'round']],
            'round, up' => [2.0, 1.55, ['roundType' => 'round']],
            'round does accept decimals' => [
                5.56, 5.5555, [
                    'roundType' => 'round',
                    'decimals' => 2,
                ],
            ],
            // strings
            'emtpy string' => [0.0, '', []],
            'word string' => [0.0, 'word', []],
            'float string' => [1.0, '1.123456789', []],
            // other types
            'null' => [0.0, null, []],
            'false' => [0.0, false, []],
            'true' => [1.0, true, []]
        ];
    }

    /**
     * Check if round works properly
     *
     * Show:
     *
     *  - Different types of input are casted to float.
     *  - Configuration ceil rounds like ceil().
     *  - Configuration floor rounds like floor().
     *  - Otherwise rounds like round() and decimals can be applied.
     *  - Always returns float.
     *
     * @param float $expected The expected output.
     * @param mixed $content The given content.
     * @param array $conf The given configuration of 'round.'.
     * @return void
     * @dataProvider roundDataProvider
     * @test
     */
    public function round($expect, $content, $conf)
    {
        $this->assertSame($expect,
            $this->subject->_call('round', $content, $conf));
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
     * Data provider for numberFormat.
     *
     * @return array [$expect, $content, $conf]
     */
    public function numberFormatDataProvider()
    {
        return [
            'testing decimals' => [
                '0.80', 0.8,
                ['decimals' => 2]
            ],
            'testing decimals with input as string' => [
                '0.80', '0.8',
                ['decimals' => 2]
            ],
            'testing dec_point' => [
                '0,8', 0.8,
                ['decimals' => 1, 'dec_point' => ',']
            ],
            'testing thousands_sep' => [
                '1.000', 999.99,
                [
                    'decimals' => 0,
                    'thousands_sep.' => ['char' => 46]
                ]
            ],
            'testing mixture' => [
                '1.281.731,5', 1281731.45,
                [
                    'decimals' => 1,
                    'dec_point.' => ['char' => 44],
                    'thousands_sep.' => ['char' => 46]
                ]
            ]
        ];
    }

    /**
     * Check if numberFormat works properly.
     *
     * @dataProvider numberFormatDataProvider
     * @test
     */
    public function numberFormat($expects, $content, $conf)
    {
        $this->assertSame($expects,
            $this->subject->numberFormat($content, $conf));
    }

    /**
     * Data provider replacement
     *
     * @return array [$expect, $content, $conf]
     */
    public function replacementDataProvider()
    {
        return [
            'multiple replacements, including regex' => [
                'There is an animal, an animal and an animal around the block! Yeah!',
                'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
                [
                    '20.' => [
                        'search' => '_',
                        'replace.' => ['char' => '32']
                    ],
                    '120.' => [
                        'search' => 'in da hood',
                        'replace' => 'around the block'
                    ],
                    '130.' => [
                        'search' => '#a (Cat|Dog|Tiger)#i',
                        'replace' => 'an animal',
                        'useRegExp' => '1'
                    ]
                ]
            ],
            'replacement with optionSplit, normal pattern' => [
                'There1is2a3cat,3a3dog3and3a3tiger3in3da3hood!3Yeah!',
                'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
                [
                    '10.' => [
                        'search' => '_',
                        'replace' => '1 || 2 || 3',
                        'useOptionSplitReplace' => '1'
                    ]
                ]
            ],
            'replacement with optionSplit, using regex' => [
                'There is a tiny cat, a midsized dog and a big tiger in da hood! Yeah!',
                'There is a cat, a dog and a tiger in da hood! Yeah!',
                [
                    '10.' => [
                        'search' => '#(a) (Cat|Dog|Tiger)#i',
                        'replace' => '${1} tiny ${2} || ${1} midsized ${2} || ${1} big ${2}',
                        'useOptionSplitReplace' => '1',
                        'useRegExp' => '1'
                    ]
                ]
            ]
        ];
    }

    /**
     * Check if stdWrap.replacement and all of its properties work properly
     *
     * @test
     * @dataProvider replacementDataProvider
     * @param string $content The given input.
     * @param string $expects The expected result.
     * @param array $conf The given configuration.
     * @return void
     */
    public function replacement($expects, $content, $conf)
    {
        $this->assertSame($expects,
            $this->subject->_call('replacement', $content, $conf));
    }

    /**
     * Data provider for calcAge.
     *
     * @return array [$expect, $timestamp, $labels]
     */
    public function calcAgeDataProvider()
    {
        return [
            'minutes' => [
                '2 min', 120, ' min| hrs| days| yrs',
            ],
            'hours' => [
                '2 hrs', 7200, ' min| hrs| days| yrs',
            ],
            'days' => [
                '7 days', 604800, ' min| hrs| days| yrs',
            ],
            'day with provided singular labels' => [
                '1 day', 86400, ' min| hrs| days| yrs| min| hour| day| year',
            ],
            'years' => [
                '45 yrs', 1417997800, ' min| hrs| days| yrs',
            ],
            'different labels' => [
                '2 Minutes', 120, ' Minutes| Hrs| Days| Yrs',
            ],
            'negative values' => [
                '-7 days', -604800, ' min| hrs| days| yrs',
            ],
            'default label values for wrong label input' => [
                '2 min', 121, 10,
            ],
            'default singular label values for wrong label input' => [
                '1 year', 31536000, 10,
            ]
        ];
    }

    /**
     * Check if calcAge works properly.
     *
     * @test
     * @dataProvider calcAgeDataProvider
     * @param int $expect
     * @param int $timestamp
     * @param string $labels
     * @return void
     */
    public function calcAge($expect, $timestamp, $labels)
    {
        $this->assertSame($expect,
            $this->subject->calcAge($timestamp, $labels));
    }

    /**
     * @return array
     */
    public function stdWrapReturnsExpectationDataProvider()
    {
        return [
            'Prevent silent bool conversion' => [
                '1+1',
                [
                    'prioriCalc.' => [
                        'wrap' => '|',
                    ],
                ],
                '1+1',
            ],
        ];
    }

    /**
     * @param string $content
     * @param array $configuration
     * @param string $expectation
     * @dataProvider stdWrapReturnsExpectationDataProvider
     * @test
     */
    public function stdWrapReturnsExpectation($content, array $configuration, $expectation)
    {
        $this->assertSame($expectation, $this->subject->stdWrap($content, $configuration));
    }

    /**
     * Data provider for substring
     *
     * @return array [$expect, $content, $conf]
     */
    public function substringDataProvider()
    {
        return [
            'sub -1'    => ['g', 'substring', '-1'],
            'sub -1,0'  => ['g', 'substring', '-1,0'],
            'sub -1,-1' => ['', 'substring', '-1,-1'],
            'sub -1,1'  => ['g', 'substring', '-1,1'],
            'sub 0'     => ['substring', 'substring', '0'],
            'sub 0,0'   => ['substring', 'substring', '0,0'],
            'sub 0,-1'  => ['substrin', 'substring', '0,-1'],
            'sub 0,1'   => ['s', 'substring', '0,1'],
            'sub 1'     => ['ubstring', 'substring', '1'],
            'sub 1,0'   => ['ubstring', 'substring', '1,0'],
            'sub 1,-1'  => ['ubstrin', 'substring', '1,-1'],
            'sub 1,1'   => ['u', 'substring', '1,1'],
            'sub'       => ['substring', 'substring', ''],
        ];
    }

    /**
     * Check if substring works properly.
     *
     * @test
     * @dataProvider substringDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param array $conf The given configutation.
     * @return void
     */
    public function substring($expect, $content, $conf)
    {
        $this->assertSame($expect, $this->subject->substring($content, $conf));
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
        $this->assertEquals($GLOBALS['TSFE']->metaCharset, $this->subject->getData('tsfe:metaCharset'));
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
        $file = $this->createMock(File::class);
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
        $this->assertEquals($GLOBALS['TSFE']->metaCharset, $this->subject->getData('global:TSFE|metaCharset'));
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
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['stdWrap', 'getImgResource'])
            ->getMock();

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
                'width' => 200,
                'srcsetCandidate' => '600w',
                'mediaQuery' => '(max-device-width: 600px)',
                'dataKey' => 'small',
            ],
            'smallRetina.' => [
                'if.directReturn' => 0,
                'width' => 200,
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
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['stdWrap', 'getImgResource'])
            ->getMock();

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
                'width' => 200,
                'srcsetCandidate' => '600w',
                'mediaQuery' => '(max-device-width: 600px)',
                'dataKey' => 'small',
            ],
            'smallRetina.' => [
                'if.directReturn' => 1,
                'width' => 200,
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
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['stdWrap', 'getImgResource'])
            ->getMock();

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
        $this->subject = $this->getAccessibleMock(ContentObjectRenderer::class,
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

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $this->subject->expects($this->any())->method('getResourceFactory')->will($this->returnValue($resourceFactory));

        $className = $this->getUniqueId('tx_coretest_getImageSourceCollectionHookCalled');
        $getImageSourceCollectionHookMock = $this->getMockBuilder(
            ContentObjectOneSourceCollectionHookInterface::class)
            ->setMethods(['getOneSourceCollection'])
            ->setMockClassName($className)
            ->getMock();
        GeneralUtility::addInstance($className, $getImageSourceCollectionHookMock);
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
                    'width' => 200,
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
     * @param array $sourceRenderConfiguration
     * @param array $sourceConfiguration
     * @param $oneSourceCollection
     * @param $parent
     * @return string
     * @see getImageSourceCollectionHookCalled
     */
    public function isGetOneSourceCollectionCalledCallback($sourceRenderConfiguration, $sourceConfiguration, $oneSourceCollection, $parent)
    {
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
     */
    public function renderingContentObjectThrowsException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
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

        $this->frontendControllerMock->config['config']['contentObjectExceptionHandler'] = '1';
        $this->subject->render($contentObjectFixture, []);
    }

    /**
     * @test
     */
    public function globalExceptionHandlerConfigurationCanBeOverriddenByLocalConfiguration()
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
        $this->frontendControllerMock->config['config']['contentObjectExceptionHandler'] = '1';
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

        $this->frontendControllerMock
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
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
        $this->subject->render($contentObjectFixture, $configuration);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | AbstractContentObject
     */
    protected function createContentObjectThrowingExceptionFixture()
    {
        $contentObjectFixture = $this->getMockBuilder(AbstractContentObject::class)
            ->setConstructorArgs([$this->subject])
            ->getMock();
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
        $templateServiceObjectMock = $this->getMockBuilder(TemplateService::class)
            ->setMethods(['dummy'])
            ->getMock();
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $typoScriptFrontendControllerMockObject = $this->createMock(TypoScriptFrontendController::class);
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
        $templateServiceObjectMock = $this->getMockBuilder(TemplateService::class)
            ->setMethods(['dummy'])
            ->getMock();
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $typoScriptFrontendControllerMockObject = $this->createMock(TypoScriptFrontendController::class);
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
        $templateServiceObjectMock = $this->getMockBuilder(TemplateService::class)
            ->setMethods(['dummy'])
            ->getMock();
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $typoScriptFrontendControllerMockObject = $this->createMock(TypoScriptFrontendController::class);
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

    ////////////////////////////////////
    // Test concerning link generation
    ////////////////////////////////////

    /**
     * @test
     */
    public function filelinkCreatesCorrectUrlForFileWithUrlEncodedSpecialChars()
    {
        $fileNameAndPath = PATH_site . 'typo3temp/var/tests/phpunitJumpUrlTestFile with spaces & amps.txt';
        file_put_contents($fileNameAndPath, 'Some test data');
        $relativeFileNameAndPath = substr($fileNameAndPath, strlen(PATH_site));
        $fileName = substr($fileNameAndPath, strlen(PATH_site . 'typo3temp/var/tests/'));

        $expectedLink = str_replace('%2F', '/', rawurlencode($relativeFileNameAndPath));
        $result = $this->subject->filelink($fileName, ['path' => 'typo3temp/var/tests/']);
        $this->assertEquals('<a href="' . $expectedLink . '">' . $fileName . '</a>', $result);

        GeneralUtility::unlink_tempfile($fileNameAndPath);
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
        $pageRepo = $this->frontendControllerMock->sys_page;
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
        $pageRepo = $this->frontendControllerMock->sys_page;
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
        $pageRepo = $this->frontendControllerMock->sys_page;
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
        $pageRepo = $this->frontendControllerMock->sys_page;
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
     * Check if calculateCacheKey works properly.
     *
     * @return array Order: expect, conf, times, with, withWrap, will
     */
    public function calculateCacheKeyDataProvider()
    {
        $value = $this->getUniqueId('value');
        $wrap = [$this->getUniqueId('wrap')];
        $valueConf = ['key' => $value];
        $wrapConf = ['key.' => $wrap];
        $conf = array_merge($valueConf, $wrapConf);
        $will = $this->getUniqueId('stdWrap');

        return [
            'no conf' => [
                '',
                [],
                0,
                null,
                null,
                null
            ],
            'value conf only' => [
                $value,
                $valueConf,
                0,
                null,
                null,
                null
            ],
            'wrap conf only' => [
                $will,
                $wrapConf,
                1,
                '',
                $wrap,
                $will
            ],
            'full conf' => [
                $will,
                $conf,
                1,
                $value,
                $wrap,
                $will
            ],
        ];
    }

    /**
     * Check if calculateCacheKey works properly.
     *
     * - takes key from $conf['key']
     * - processes key with stdWrap based on $conf['key.']
     *
     * @test
     * @dataProvider calculateCacheKeyDataProvider
     * @param string $expect Expected result.
     * @param array $conf Properties 'key', 'key.'
     * @param int $times Times called mocked method.
     * @param array $with Parameter passed to mocked method.
     * @param string $will Return value of mocked method.
     * @return void
     */
    public function calculateCacheKey($expect, $conf, $times, $with, $withWrap, $will)
    {
        $subject = $this->getAccessibleMock(ContentObjectRenderer::class, ['stdWrap']);
        $subject->expects($this->exactly($times))
            ->method('stdWrap')
            ->with($with, $withWrap)
            ->willReturn($will);

        $result = $subject->_call('calculateCacheKey', $conf);
        $this->assertSame($expect, $result);
    }

    /**
     * Data provider for getFromCache
     *
     * @return array Order: expect, conf, cacheKey, times, cached.
     */
    public function getFromCacheDtataProvider()
    {
        $conf = [$this->getUniqueId('conf')];
        return [
            'empty cache key' => [
                false, $conf, '', 0, null,
            ],
            'non-empty cache key' => [
                'value', $conf, 'non-empty-key', 1, 'value',
            ],
        ];
    }

    /**
     * Check if getFromCache works properly.
     *
     * - CalculateCacheKey is called to calc the cache key.
     * - $conf is passed on as parameter
     * - CacheFrontend is created and called if $cacheKey is not empty.
     * - Else false is returned.
     *
     * @test
     * @dataProvider getFromCacheDtataProvider
     * @param string $expect Expected result.
     * @param array $conf Configuration to pass to calculateCacheKey mock.
     * @param string $cacheKey Return from calculateCacheKey mock.
     * @param int $times Times the cache is expected to be called (0 or 1).
     * @param string $cached Return from cacheFrontend mock.
     * @return void
     */
    public function getFromCache($expect, $conf, $cacheKey, $times, $cached)
    {
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class, ['calculateCacheKey']);
        $subject
            ->expects($this->exactly(1))
            ->method('calculateCacheKey')
            ->with($conf)
            ->willReturn($cacheKey);
        $cacheFrontend = $this->createMock(CacheFrontendInterface::class);
        $cacheFrontend
            ->expects($this->exactly($times))
            ->method('get')
            ->with($cacheKey)
            ->willReturn($cached);
        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager
            ->method('getCache')
            ->willReturn($cacheFrontend);
        GeneralUtility::setSingletonInstance(
            CacheManager::class, $cacheManager);
        $this->assertSame($expect, $subject->_call('getFromCache', $conf));
    }

    /**
     * Data provider for getFieldVal
     *
     * @return array [$expect, $fields]
     */
    public function getFieldValDataProvider()
    {
        return [
            'invalid single key' => [null, 'invalid'],
            'single key of null' => [null, 'null'],
            'single key of empty string' => ['', 'empty'],
            'single key of non-empty string' => ['string 1', 'string1'],
            'single key of boolean false' => [false, 'false'],
            'single key of boolean true' => [true, 'true'],
            'single key of integer 0' => [0, 'zero'],
            'single key of integer 1' => [1, 'one'],
            'single key to be trimmed' => ['string 1', ' string1 '],

            'split nothing' => ['', '//'],
            'split one before' => ['string 1', 'string1//'],
            'split one after' => ['string 1', '//string1'],
            'split two ' => ['string 1', 'string1//string2'],
            'split three ' => ['string 1', 'string1//string2//string3'],
            'split to be trimmed' => ['string 1', ' string1 // string2 '],
            '0 is not empty' => [0, '// zero'],
            '1 is not empty' => [1, '// one'],
            'true is not empty' => [true, '// true'],
            'false is empty' => ['', '// false'],
            'null is empty' => ['', '// null'],
            'empty string is empty' => ['', '// empty'],
            'string is not empty' => ['string 1', '// string1'],
            'first non-empty winns' => [0, 'false//empty//null//zero//one'],
            'empty string is fallback' => ['', 'false // empty // null'],
        ];
    }

    /**
     * Check that getFieldVal works properly.
     *
     * Show:
     *
     * - Returns the field from $this->data.
     * - The keys are trimmed.
     *
     * - For a single key (no //) returns the field as is:
     *
     *   - '' => ''
     *   - null => null
     *   - false => false
     *   - true => true
     *   -  0 => 0
     *   -  1 => 1
     *   - 'string' => 'string'
     *
     * - If '//' is present, explodes key candidates.
     * - Returns the first field, that is not "empty".
     * - "Empty" is checked after type cast to string by comparing to ''.
     * - The winning non-empty value is returned as is.
     * - The fallback, if all evals to empty, is the empty string ''.
     * - '//' with single elements and empty string fallback results in:
     *
     *   - '' => ''
     *   - null => ''
     *   - false => ''
     *   - true => true
     *   -  0 => 0
     *   -  1 => 1
     *   - 'string' => 'string'
     *
     * @test
     * @dataProvider getFieldValDataProvider
     * @param string $expect The expected string.
     * @param string $fields Field names divides by //.
     * @return void
     */
    public function getFieldVal($expect, $fields)
    {
        $data = [
            'string1' => 'string 1',
            'string2' => 'string 2',
            'string3' => 'string 3',
            'empty' => '',
            'null' => null,
            'false' => false,
            'true' => true,
            'zero' => 0,
            'one' => 1,
        ];
        $this->subject->_set('data', $data);
        $this->assertSame($expect, $this->subject->getFieldVal($fields));
    }

    /**
     * Data provider for caseshift.
     *
     * @return array [$expect, $content, $case]
     */
    public function caseshiftDataProvider()
    {
        return [
             'lower' => ['x y', 'X Y', 'lower'],
             'upper' => ['X Y', 'x y', 'upper'],
             'capitalize' => ['One Two', 'one two', 'capitalize'],
             'ucfirst' => ['One two', 'one two', 'ucfirst'],
             'lcfirst' => ['oNE TWO', 'ONE TWO', 'lcfirst'],
             'uppercamelcase' => ['CamelCase', 'camel_case', 'uppercamelcase'],
             'lowercamelcase' => ['camelCase', 'camel_case', 'lowercamelcase'],
         ];
    }

     /**
      * Check if caseshift works properly.
      *
      * @test
      * @dataProvider caseshiftDataProvider
      * @param string $expect The expected output.
      * @param string $content The given input.
      * @param string $case The given type of conversion.
      */
     public function caseshift($expect, $content, $case)
     {
         $this->assertSame($expect,
             $this->subject->caseshift($content, $case));
     }

    /**
     * Data provider for HTMLcaseshift.
     *
     * @return array [$expect, $content, $case, $with, $will]
     */
    public function HTMLcaseshiftDataProvider()
    {
        $case = $this->getUniqueId('case');
        return [
            'simple text' => [
                'TEXT', 'text', $case,
                [['text', $case]],
                ['TEXT']
            ],
            'simple tag' => [
                '<i>TEXT</i>', '<i>text</i>', $case,
                [['', $case], ['text', $case]],
                ['', 'TEXT']
            ],
            'multiple nested tags with classes' => [
                NL . '<div class="typo3">' .
                NL . '<p>A <b>BOLD<\b> WORD.</p>' .
                NL . '<p>AN <i>ITALIC<\i> WORD.</p>' .
                NL . '</div>',
                NL . '<div class="typo3">' .
                NL . '<p>A <b>bold<\b> word.</p>' .
                NL . '<p>An <i>italic<\i> word.</p>' .
                NL . '</div>',
                $case,
                [
                    [NL, $case],
                    [NL, $case],
                    ['A ', $case],
                    ['bold', $case],
                    [' word.', $case],
                    [NL, $case],
                    ['An ', $case],
                    ['italic', $case],
                    [' word.', $case],
                    [NL, $case],
                ],
                [NL, NL, 'A ', 'BOLD', ' WORD.',
                NL, 'AN ', 'ITALIC', ' WORD.', NL]
            ],
        ];
    }

     /**
      * Check if HTMLcaseshift works properly.
      *
      * Show:
      *
      * - Only shifts the case of characters not part of tags.
      * - Delegates to the method caseshift.
      *
      * @test
      * @dataProvider HTMLcaseshiftDataProvider
      * @param string $expect The expected output.
      * @param string $content The given input.
      * @param string $case The given type of conversion.
      * @param array $with Consecutive args expected by caseshift.
      * @param array $will Consecutive return values of caseshfit.
      * @return void
      */
    public function HTMLcaseshift($expect, $content, $case, $with, $will)
    {
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['caseshift'])->getMock();
        $subject
            ->expects($this->exactly(count($with)))
            ->method('caseshift')
            ->withConsecutive(...$with)
            ->will($this->onConsecutiveCalls(...$will));
        $this->assertSame($expect,
            $subject->HTMLcaseshift($content, $case));
    }

    /***************************************************************************
    * General tests for stdWrap_
    ***************************************************************************/

    /**
     * Check that all registered stdWrap processors are callable.
     *
     * Show:
     *
     * - The given invalidProcessor is counted as not callable.
     * - All stdWrap processors are counted as callable.
     * - Their amount is 91.
     *
     * @test
     * @return void
     */
    public function allStdWrapProcessorsAreCallable()
    {
        $callable = 0;
        $notCallable = 0;
        $processors = ['invalidProcessor'];
        foreach (array_keys($this->subject->_get('stdWrapOrder')) as $key) {
            $processors[] = strtr($key, ['.' => '']);
        }
        foreach (array_unique($processors) as $processor) {
            $method = [$this->subject, 'stdWrap_' . $processor];
            if (is_callable($method)) {
                $callable += 1;
            } else {
                $notCallable += 1;
            }
        }
        $this->assertSame(1, $notCallable);
        $this->assertSame(91, $callable);
    }

    /**
     * Check which stdWrap functions are callable with empty parameters.
     *
     * Show:
     *
     * - Almost all stdWrap_[type] are callable if called with 2 parameters:
     *   - string $content Empty string.
     *   - array $conf ['type' => '', 'type.' => []].
     * - Exeptions: stdWrap_numRows, stdWrap_split
     * - The overall count is 91.
     *
     *  Note:
     *
     *  The two exceptions break, if the configuration is empty. This test just
     *  tracks the different behaviour to gain information. It doesn't mean
     *  that it is an issue.
     *
     * @test
     * @return void
     */
    public function notAllStdWrapProcessorsAreCallableWithEmptyConfiguration()
    {
        $expectExceptions = ['numRows', 'split'];
        if (!version_compare(PHP_VERSION, '7.1', '<')) {
            // PHP >= 7.1 throws "A non-numeric value encountered" in GeneralUtility::formatSize()
            // @todo: If that is sanitized in a better way in formatSize(), this call needs adaption
            $expectExceptions[] = 'bytes';
        }
        $count = 0;
        $processors = [];
        $exceptions = [];
        foreach (array_keys($this->subject->_get('stdWrapOrder')) as $key) {
            $processors[] = strtr($key, ['.' => '']);
        }
        foreach (array_unique($processors) as $processor) {
            $count += 1;
            try {
                $conf = [$processor => '', $processor . '.' => ['table' => 'tt_content']];
                $method = 'stdWrap_' . $processor;
                $this->subject->$method('', $conf);
            } catch (\Exception $e) {
                $exceptions[] = $processor;
            }
        }
        $this->assertSame($expectExceptions, $exceptions);
        $this->assertSame(91, $count);
    }

    /***************************************************************************
    * End general tests for stdWrap_
    ***************************************************************************/

    /***************************************************************************
    * Tests for stdWrap_ in alphabetical order (all uppercase before lowercase)
    ***************************************************************************/

    /**
     * Data provider for fourTypesOfStdWrapHookObjectProcessors
     *
     * @return array Order: stdWrap, hookObjectCall
     */
    public function fourTypesOfStdWrapHookObjectProcessorsDataProvider()
    {
        return [
            'preProcess' => [
                'stdWrap_stdWrapPreProcess', 'stdWrapPreProcess'
            ],
            'override' => [
                'stdWrap_stdWrapOverride', 'stdWrapOverride'
            ],
            'process' => [
                'stdWrap_stdWrapProcess', 'stdWrapProcess'
            ],
            'postProcess' => [
                'stdWrap_stdWrapPostProcess', 'stdWrapPostProcess'
            ],
        ];
    }

    /**
     * Check if stdWrapHookObject processors work properly.
     *
     * Checks:
     *
     * - stdWrap_stdWrapPreProcess
     * - stdWrap_stdWrapOverride
     * - stdWrap_stdWrapProcess
     * - stdWrap_stdWrapPostProcess
     *
     * @test
     * @dataProvider fourTypesOfStdWrapHookObjectProcessorsDataProvider
     * @param string $stdWrapMethod: The method to cover.
     * @param string $hookObjectCall: The expected hook object call.
     * @return void
     */
    public function fourTypesOfStdWrapHookObjectProcessors(
        $stdWrapMethod, $hookObjectCall)
    {
        $conf = [$this->getUniqueId('conf')];
        $content = $this->getUniqueId('content');
        $processed1 = $this->getUniqueId('processed1');
        $processed2 = $this->getUniqueId('processed2');
        $hookObject1 = $this->createMock(
            ContentObjectStdWrapHookInterface::class);
        $hookObject1->expects($this->once())
            ->method($hookObjectCall)
            ->with($content, $conf)
            ->willReturn($processed1);
        $hookObject2 = $this->createMock(
            ContentObjectStdWrapHookInterface::class);
        $hookObject2->expects($this->once())
            ->method($hookObjectCall)
            ->with($processed1, $conf)
            ->willReturn($processed2);
        $this->subject->_set('stdWrapHookObjects',
            [$hookObject1, $hookObject2]);
        $result = $this->subject->$stdWrapMethod($content, $conf);
        $this->assertSame($processed2, $result);
    }

    /**
     * Data provider for stdWrap_HTMLparser
     *
     * @return array [$expect, $content, $conf, $times, $will].
     */
    public function stdWrap_HTMLparserDataProvider()
    {
        $content = $this->getUniqueId('content');
        $parsed = $this->getUniqueId('parsed');
        return [
            'no config' => [
                $content, $content, [], 0, $parsed
            ],
            'no array' => [
                $content, $content, ['HTMLparser.' => 1], 0, $parsed
            ],
            'empty array' => [
                $parsed, $content, ['HTMLparser.' => []], 1, $parsed
            ],
            'non-empty array' => [
                $parsed, $content, ['HTMLparser.' => [true]], 1, $parsed
            ],
        ];
    }

    /**
     * Check if stdWrap_HTMLparser works properly
     *
     * Show:
     *
     * - Checks if $conf['HTMLparser.'] is an array.
     * - No:
     *   - Returns $content as is.
     * - Yes:
     *   - Delegates to method HTMLparser_TSbridge.
     *   - Parameter 1 is $content.
     *   - Parameter 2 is $conf['HTMLparser'].
     *   - Returns the return value.
     *
     * @test
     * @dataProvider stdWrap_HTMLparserDataProvider
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param array $conf The given configuration.
     * @param int $times Times HTMLparser_TSbridge is called (0 or 1).
     * @param string $will Return of HTMLparser_TSbridge.
     * @return void.
     */
    public function stdWrap_HTMLparser(
        $expect, $content, $conf, $times, $will)
    {
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['HTMLparser_TSbridge'])->getMock();
        $subject
            ->expects($this->exactly($times))
            ->method('HTMLparser_TSbridge')
            ->with($content, $conf['HTMLparser.'])
            ->willReturn($will);
        $this->assertSame($expect,
            $subject->stdWrap_HTMLparser($content, $conf));
    }

    /**
     * Data provider ofr stdWrap_TCAselectItem.
     *
     * @return array [$expect, $content, $conf, $times, $will]
     */
    public function stdWrap_TCAselectItemDataProvider()
    {
        $content = $this->getUniqueId('content');
        $array = [$this->getUniqueId('TCAselectItem.')];
        $will = $this->getUniqueId('will');
        return [
            'empty conf' => [
                $content, $content, [], 0, $will
            ],
            'no array' => [
                $content, $content, ['TCAselectItem.' => true], 0, $will
            ],
            'empty array' => [
                $will, $content, ['TCAselectItem.' => []], 1, $will
            ],
            'array' => [
                $will, $content, ['TCAselectItem.' => $array], 1, $will
            ]
        ];
    }

    /**
     * Check that stdWrap_TCAselectItem works properly.
     *
     * Show:
     *
     * - Checks if $conf['TCAselectItem'] is an array.
     * - If NO:
     *   - Returns $content as is.
     * - If YES:
     *   - Delegates to method TCAlookup.
     *   - Parameter 1 is $content.
     *   - Parameter 2 is $conf['TCAselectItem.'].
     *   - Returns the return value.
     *
     *  @test
     *  @dataProvider stdWrap_TCAselectItemDataProvider
     *  @param mixed $expect The expected output.
     *  @param mixed $content The the given input.
     *  @param mixed $conf The the given configuration.
     *  @param int $times Times TCAlookup is called.
     *  @param string $will Return value of TCAlookup.
     *  @return void.
     */
    public function stdWrap_TCAselectItem(
        $expect, $content, $conf, $times, $will)
    {
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['TCAlookup'])->getMock();
        $subject
            ->expects($this->exactly($times))
            ->method('TCAlookup')
            ->with($content, $conf['TCAselectItem.'])
            ->willReturn($will);
        $this->assertSame($expect,
            $subject->stdWrap_TCAselectItem($content, $conf));
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
     * @param array $expectedTags
     * @param array $configuration
     * @test
     * @dataProvider stdWrap_addPageCacheTagsAddsPageTagsDataProvider
     */
    public function stdWrap_addPageCacheTagsAddsPageTags(array $expectedTags, array $configuration)
    {
        $this->subject->stdWrap_addPageCacheTags('', $configuration);
        $this->assertEquals($expectedTags, $this->frontendControllerMock->_get('pageCacheTags'));
    }

    /**
     * Check that stdWrap_addParams works properly.
     *
     * Show:
     *
     *  - Delegates to method addParams.
     *  - Parameter 1 is $content.
     *  - Parameter 2 is $conf['addParams.'].
     *  - Returns the return value.
     *
     *  @test
     *  @return void.
     */
    public function stdWrap_addParams()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'addParams' => $this->getUniqueId('not used'),
            'addParams.' => [$this->getUniqueId('addParams.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['addParams'])->getMock();
        $subject
            ->expects($this->once())
            ->method('addParams')
            ->with($content, $conf['addParams.'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_addParams($content, $conf));
    }

    /**
     * Check if stdWrap_age works properly.
     *
     * Show:
     *
     * - Delegates to calcAge.
     * - Parameter 1 is the difference between $content and EXEC_TIME.
     * - Parameter 2 is $conf['age'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_age()
    {
        $now = 10;
        $content = '9';
        $conf = ['age' => $this->getUniqueId('age')];
        $return = $this->getUniqueId('return');
        $difference = $now - (int)$content;
        $GLOBALS['EXEC_TIME'] = $now;
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['calcAge'])->getMock();
        $subject
            ->expects($this->once())
            ->method('calcAge')
            ->with($difference, $conf['age'])
            ->willReturn($return);
        $this->assertSame($return, $subject->stdWrap_age($content, $conf));
    }

    /**
     * Check if stdWrap_append works properly.
     *
     * Show:
     *
     * - Delegates to the method cObjGetSingle().
     * - First parameter is $conf['append'].
     * - Second parameter is $conf['append.'].
     * - Third parameter is '/stdWrap/.append'.
     * - Returns the return value appended to $content.
     *
     * @test
     * @return void
     */
    public function stdWrap_append()
    {
        $debugKey =  '/stdWrap/.append';
        $content = $this->getUniqueId('content');
        $conf = [
            'append' => $this->getUniqueId('append'),
            'append.' => [$this->getUniqueId('append.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['cObjGetSingle'])->getMock();
        $subject
            ->expects($this->once())
            ->method('cObjGetSingle')
            ->with($conf['append'], $conf['append.'], $debugKey)
            ->willReturn($return);
        $this->assertSame($content . $return,
            $subject->stdWrap_append($content, $conf));
    }

    /**
     * Data provider for stdWrap_br
     *
     * @return string[][] Order expected, given, xhtmlDoctype
     */
    public function stdWrapBrDataProvider()
    {
        return [
            'no xhtml with LF in between' => [
                'one<br>' . LF . 'two',
                'one' . LF . 'two',
                null
            ],
            'no xhtml with LF in between and around' => [
                '<br>' . LF . 'one<br>' . LF . 'two<br>' . LF,
                LF . 'one' . LF . 'two' . LF,
                null
            ],
            'xhtml with LF in between' => [
                'one<br />' . LF . 'two',
                'one' . LF . 'two',
                'xhtml_strict'
            ],
            'xhtml with LF in between and around' => [
                '<br />' . LF . 'one<br />' . LF . 'two<br />' . LF,
                LF . 'one' . LF . 'two' . LF,
                'xhtml_strict'
            ],
        ];
    }

    /**
     * Test that stdWrap_br works as expected.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param string $xhtmlDoctype Xhtml document type.
     * @return void
     * @test
     * @dataProvider stdWrapBrDataProvider
     */
    public function stdWrap_br($expected, $input, $xhtmlDoctype)
    {
        $GLOBALS['TSFE']->xhtmlDoctype = $xhtmlDoctype;
        $this->assertSame($expected, $this->subject->stdWrap_br($input));
    }

    /**
     * Data provider for stdWrap_brTag
     *
     * @return array
     */
    public function stdWrapBrTagDataProvider()
    {
        $noConfig = [];
        $config1 = ['brTag' => '<br/>'];
        $config2 = ['brTag' => '<br>'];
        return [
            'no config: one break at the beginning' => [LF . 'one' . LF . 'two', 'onetwo', $noConfig],
            'no config: multiple breaks at the beginning' => [LF . LF . 'one' . LF . 'two', 'onetwo', $noConfig],
            'no config: one break at the end' => ['one' . LF . 'two' . LF, 'onetwo', $noConfig],
            'no config: multiple breaks at the end' => ['one' . LF . 'two' . LF . LF, 'onetwo', $noConfig],

            'config1: one break at the beginning' => [LF . 'one' . LF . 'two', '<br/>one<br/>two', $config1],
            'config1: multiple breaks at the beginning' => [LF . LF . 'one' . LF . 'two', '<br/><br/>one<br/>two', $config1],
            'config1: one break at the end' => ['one' . LF . 'two' . LF, 'one<br/>two<br/>', $config1],
            'config1: multiple breaks at the end' => ['one' . LF . 'two' . LF . LF, 'one<br/>two<br/><br/>', $config1],

            'config2: one break at the beginning' => [LF . 'one' . LF . 'two', '<br>one<br>two', $config2],
            'config2: multiple breaks at the beginning' => [LF . LF . 'one' . LF . 'two', '<br><br>one<br>two', $config2],
            'config2: one break at the end' => ['one' . LF . 'two' . LF, 'one<br>two<br>', $config2],
            'config2: multiple breaks at the end' => ['one' . LF . 'two' . LF . LF, 'one<br>two<br><br>', $config2],
        ];
    }

    /**
     * Check if brTag works properly
     *
     * @test
     * @dataProvider stdWrapBrTagDataProvider
     */
    public function stdWrap_brTag($input, $expected, $config)
    {
        $this->assertEquals($expected, $this->subject->stdWrap_brTag($input, $config));
    }

    /**
     * Data provider for stdWrap_bytes.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_bytesDataProvider()
    {
        return [
            'value 1234 default' => [
                '1.21 Ki', '1234',
                ['labels' => '', 'base' => 0],
            ],
            'value 1234 si' => [
                '1.23 k', '1234',
                ['labels' => 'si', 'base' => 0],
            ],
            'value 1234 iec' => [
                '1.21 Ki', '1234',
                ['labels' => 'iec', 'base' => 0],
            ],
            'value 1234 a-i' => [
                '1.23b', '1234',
                ['labels' => 'a|b|c|d|e|f|g|h|i', 'base' => 1000],
            ],
            'value 1234 a-i invalid base' => [
                '1.21b', '1234',
                ['labels' => 'a|b|c|d|e|f|g|h|i', 'base' => 54],
            ],
            'value 1234567890 default' => [
                '1.15 Gi', '1234567890',
                ['labels' => '', 'base' => 0],
            ]
        ];
    }

    /**
     * Check if stdWrap_bytes works properly.
     *
     * Show:
     *
     * - Delegates to GeneralUtility::formatSize
     * - Parameter 1 is $conf['bytes.'][labels'].
     * - Parameter 2 is $conf['bytes.'][base'].
     * - Returns the return value.
     *
     * Note: As PHPUnit can't mock static methods, the call to
     *       GeneralUtility::formatSize can't be easily intercepted. The test
     *       is done by testing input/output pairs instead. To not duplicate
     *       the testing of formatSize just a few smoke tests are done here.
     *
     * @test
     * @dataProvider stdWrap_bytesDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param array $conf The given configuration for 'bytes.'.
     * @return void
     */
    public function stdWrap_bytes($expect, $content, $conf)
    {
        $locale = 'en_US.UTF-8';
        if (!setlocale(LC_NUMERIC, $locale)) {
            $this->markTestSkipped('Locale ' . $locale . ' is not available.');
        }
        $conf = ['bytes.' => $conf];
        $this->assertSame($expect,
            $this->subject->stdWrap_bytes($content, $conf));
    }

    /**
     * Check if stdWrap_cObject works properly.
     *
     * Show:
     *
     * - Delegates to the method cObjGetSingle().
     * - Parameter 1 is $conf['cObject'].
     * - Parameter 2 is $conf['cObject.'].
     * - Parameter 3 is '/stdWrap/.cObject'.
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_cObject()
    {
        $debugKey =  '/stdWrap/.cObject';
        $content = $this->getUniqueId('content');
        $conf = [
            'cObject' => $this->getUniqueId('cObject'),
            'cObject.' => [$this->getUniqueId('cObject.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['cObjGetSingle'])->getMock();
        $subject
            ->expects($this->once())
            ->method('cObjGetSingle')
            ->with($conf['cObject'], $conf['cObject.'], $debugKey)
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_cObject($content, $conf));
    }

    /**
     * Data provider for stdWrap_orderedStdWrap.
     *
     * @return array [$firstConf, $secondConf, $conf]
     */
    public function stdWrap_orderedStdWrapDataProvider()
    {
        $confA = [$this->getUniqueId('conf A')];
        $confB = [$this->getUniqueId('conf B')];
        return [
            'standard case: order 1, 2' => [
                $confA, $confB, ['1.' => $confA, '2.' => $confB]
            ],
            'inverted: order 2, 1' => [
                $confB, $confA, ['2.' => $confA, '1.' => $confB]
            ],
            '0 as integer: order 0, 2' => [
                $confA, $confB, ['0.' => $confA, '2.' => $confB]
            ],
            'negative integers: order 2, -2' => [
                $confB, $confA, ['2.' => $confA, '-2.' => $confB]
            ],
            'chars are casted to key 0, that is not in the array' => [
                null, $confB, ['2.' => $confB, 'xxx.' => $confA]
            ],
        ];
    }

    /**
     * Check if stdWrap_orderedStdWrap works properly.
     *
     * Show:
     *
     * - For each entry of $conf['orderedStdWrap.'] stdWrap is applied
     *   to $content.
     * - The order is defined by the keys, after they have been casted
     *   to integers.
     * - Returns the processed $content after all entries have been applied.
     *
     * Each test calls stdWrap two times. First $content is processed to
     * $between, second $between is processed to $expect, the final return
     * value. It is checked, if the expected parameters are given in the right
     * consecutive order to stdWrap.
     *
     * @test
     * @dataProvider stdWrap_orderedStdWrapDataProvider
     * @param array $firstConf Parameter 2 expected by first call to stdWrap.
     * @param array $secondConf Parameter 2 expected by second call to stdWrap.
     * @param array $conf The given configuration.
     * @return void
     */
    public function stdWrap_orderedStdWrap($firstConf, $secondConf, $conf)
    {
        $content = $this->getUniqueId('content');
        $between = $this->getUniqueId('between');
        $expect = $this->getUniqueId('expect');
        $conf['orderedStdWrap.'] = $conf;
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['stdWrap'])->getMock();
        $subject
            ->expects($this->exactly(2))
            ->method('stdWrap')
            ->withConsecutive([$content, $firstConf], [$between, $secondConf])
            ->will($this->onConsecutiveCalls($between, $expect));
        $this->assertSame($expect,
            $subject->stdWrap_orderedStdWrap($content, $conf));
    }

    /**
     * Data provider for stdWrap_cacheRead
     *
     * @return array Order: expect, input, conf, times, with, will
     */
    public function stdWrap_cacheReadDataProvider()
    {
        $cacheConf = [$this->getUniqueId('cache.')];
        $conf = ['cache.' => $cacheConf];
        return [
            'no conf' => [
                'content', 'content', [],
                0, null, null,
            ],
            'no cache. conf' => [
                'content', 'content', ['otherConf' => 1],
                0, null, null,
            ],
            'non-cached simulation' => [
                'content', 'content', $conf,
                1, $cacheConf, false,
            ],
            'cached simulation' => [
                'cachedContent', 'content', $conf,
                1, $cacheConf, 'cachedContent',
            ],
        ];
    }

    /**
     * Check if stdWrap_cacheRead works properly.
     *
     * - the method branches correctly
     * - getFromCache is called to fetch from cache
     * - $conf['cache.'] is passed on as parameter
     *
     * @test
     * @dataProvider stdWrap_cacheReadDataProvider
     * @param string $expect Expected result.
     * @param string $input Given input string.
     * @param array $conf Property 'cache.'
     * @param int $times Times called mocked method.
     * @param array $with Parameter passed to mocked method.
     * @param string|false $will Return value of mocked method.
     * @return void
     */
    public function stdWrap_cacheRead(
        $expect, $input, $conf, $times, $with, $will)
    {
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class, ['getFromCache']);
        $subject
            ->expects($this->exactly($times))
            ->method('getFromCache')
            ->with($with)
            ->willReturn($will);
        $this->assertSame($expect,
            $subject->stdWrap_cacheRead($input, $conf));
    }

    /**
     * Data provider for stdWrap_cacheStore.
     *
     * @return array [$confCache, $timesCCK, $key, $times]
     */
    public function stdWrap_cacheStoreDataProvider()
    {
        $confCache = [$this->getUniqueId('cache.')];
        $key = [$this->getUniqueId('key')];
        return [
            'Return immediate with no conf' => [
                null, 0, null, 0,
            ],
            'Return immediate with empty key' => [
                $confCache, 1, '0', 0,
            ],
            'Call all methods' => [
                $confCache, 1, $key, 1,
            ],
        ];
    }

    /**
     * Check if stdWrap_cacheStore works properly.
     *
     * Show:
     *
     * - Returns $content as is.
     * - Returns immediate if $conf['cache.'] is not set.
     * - Returns immediate if calculateCacheKey returns an empty value.
     * - Calls calculateCacheKey with $conf['cache.'].
     * - Calls calculateCacheTags with $conf['cache.'].
     * - Calls calculateCacheLifetime with $conf['cache.'].
     * - Calls all configured user functions with $params, $this.
     * - Calls set on the cache frontent with $key, $content, $tags, $lifetime.
     *
     * @test
     * @dataProvider stdWrap_cacheStoreDataProvider
     * @param array $confCache Configuration of 'cache.'
     * @param int $timesCCK Times calculateCacheKey is called.
     * @param string  $key The return value of calculateCacheKey.
     * @param int $times Times the other methods are called.
     * @return void
     */
    public function stdWrap_cacheStore(
        $confCache, $timesCCK, $key, $times)
    {
        $content = $this->getUniqueId('content');
        $conf['cache.'] = $confCache;
        $tags = [$this->getUniqueId('tags')];
        $lifetime = $this->getUniqueId('lifetime');
        $params = ['key' => $key, 'content' => $content,
            'lifetime' => $lifetime, 'tags' => $tags];
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class, ['calculateCacheKey',
            'calculateCacheTags', 'calculateCacheLifetime']);
        $subject
            ->expects($this->exactly($timesCCK))
            ->method('calculateCacheKey')
            ->with($confCache)
            ->willReturn($key);
        $subject
            ->expects($this->exactly($times))
            ->method('calculateCacheTags')
            ->with($confCache)
            ->willReturn($tags);
        $subject
            ->expects($this->exactly($times))
            ->method('calculateCacheLifetime')
            ->with($confCache)
            ->willReturn($lifetime);
        $cacheFrontend = $this->createMock(CacheFrontendInterface::class);
        $cacheFrontend
            ->expects($this->exactly($times))
            ->method('set')
            ->with($key, $content, $tags, $lifetime)
            ->willReturn($cached);
        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager
            ->method('getCache')
            ->willReturn($cacheFrontend);
        GeneralUtility::setSingletonInstance(
            CacheManager::class, $cacheManager);
        list($countCalls, $test) = [0, $this];
        $closure = function ($par1, $par2) use (
            $test, $subject, $params, &$countCalls) {
            $test->assertSame($params, $par1);
            $test->assertSame($subject, $par2);
            $countCalls++;
        };
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap_cacheStore'] = [$closure, $closure, $closure];
        $this->assertSame($content,
            $subject->stdWrap_cacheStore($content, $conf));
        $this->assertSame($times * 3, $countCalls);
    }

    /**
     * Check if stdWrap_case works properly.
     *
     * Show:
     *
     * - Delegates to method HTMLcaseshift.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['case'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_case()
    {
        $content = $this->getUniqueId();
        $conf = [
            'case' => $this->getUniqueId('used'),
            'case.' => [$this->getUniqueId('discarded')],
        ];
        $return = $this->getUniqueId();
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['HTMLcaseshift'])->getMock();
        $subject
            ->expects($this->once())
            ->method('HTMLcaseshift')
            ->with($content, $conf['case'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_case($content, $conf));
    }

    /**
     * Check if stdWrap_char works properly.
     *
     * @test
     * @return void
     */
    public function stdWrap_char()
    {
        $input = 'discarded';
        $expected = 'C';
        $this->assertEquals($expected, $this->subject->stdWrap_char($input, ['char' => '67']));
    }

    /**
     * Check if stdWrap_crop works properly.
     *
     * Show:
     *
     * - Delegates to method listNum.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['crop'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_crop()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'crop' => $this->getUniqueId('crop'),
            'crop.' => $this->getUniqueId('not used'),
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['crop'])->getMock();
        $subject
            ->expects($this->once())
            ->method('crop')
            ->with($content, $conf['crop'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_crop($content, $conf));
    }

    /**
     * Check if stdWrap_cropHTML works properly.
     *
     * Show:
     *
     * - Delegates to method cropHTML.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['cropHTML'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_cropHTML()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'cropHTML' => $this->getUniqueId('cropHTML'),
            'cropHTML.' => $this->getUniqueId('not used'),
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['cropHTML'])->getMock();
        $subject
            ->expects($this->once())
            ->method('cropHTML')
            ->with($content, $conf['cropHTML'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_cropHTML($content, $conf));
    }

    /**
     * Data provider for stdWrap_csConv
     *
     * @return array Order expected, input, conf
     */
    public function stdWrap_csConvDataProvider()
    {
        return [
            'empty string from ISO-8859-15' => [
                '',
                iconv('UTF-8', 'ISO-8859-15', ''),
                ['csConv' => 'ISO-8859-15']
            ],
            'empty string from BIG-5' => [
                '',
                mb_convert_encoding('', 'BIG-5'),
                ['csConv' => 'BIG-5']
            ],
            '"0" from ISO-8859-15' => [
                '0',
                iconv('UTF-8', 'ISO-8859-15', '0'),
                ['csConv' => 'ISO-8859-15']
            ],
            '"0" from BIG-5' => [
                '0',
                mb_convert_encoding('0', 'BIG-5'),
                ['csConv' => 'BIG-5']
            ],
            'euro symbol from ISO-88859-15' => [
                '€',
                iconv('UTF-8', 'ISO-8859-15', '€'),
                ['csConv' => 'ISO-8859-15']
            ],
            'good morning from BIG-5' => [
                '早安',
                mb_convert_encoding('早安', 'BIG-5'),
                ['csConv' => 'BIG-5']
            ],
        ];
    }

    /**
     * Check if stdWrap_csConv works properly.
     *
     * @test
     * @dataProvider stdWrap_csConvDataProvider
     * @param string $expected The expected value.
     * @param string $value The input value.
     * @param array $conf Property: csConv
     * @return void
     */
    public function stdWrap_csConv($expected, $input, $conf)
    {
        $this->assertSame($expected,
            $this->subject->stdWrap_csConv($input, $conf));
    }

    /**
     * Check if stdWrap_current works properly.
     *
     * Show:
     *
     * - current is returned from $this->data
     * - the key is stored in $this->currentValKey
     * - the key defaults to 'currentValue_kidjls9dksoje'
     *
     * @test
     * @return void
     */
    public function stdWrap_current()
    {
        $data = [
            'currentValue_kidjls9dksoje' => 'default',
            'currentValue_new' => 'new',
        ];
        $this->subject->_set('data', $data);
        $this->assertSame('currentValue_kidjls9dksoje',
            $this->subject->_get('currentValKey'));
        $this->assertSame('default',
            $this->subject->stdWrap_current('discarded', ['discarded']));
        $this->subject->_set('currentValKey', 'currentValue_new');
        $this->assertSame('new',
            $this->subject->stdWrap_current('discarded', ['discarded']));
    }

    /**
     * Data provider for stdWrap_data.
     *
     * @return array [$expect, $data, $alt]
     */
    public function stdWrap_dataDataProvider()
    {
        $data = [$this->getUniqueId('data')];
        $alt = [$this->getUniqueId('alternativeData')];
        return [
            'default' => [$data, $data, ''],
            'alt is array' => [$alt, $data, $alt],
            'alt is empty array' => [[], $data, []],
            'alt null' => [$data, $data, null],
            'alt string' => [$data, $data, 'xxx'],
            'alt int' => [$data, $data, 1],
            'alt bool' => [$data, $data, true],
        ];
    }

    /**
     * Checks that stdWrap_data works properly.
     *
     * Show:
     *
     * - Delegates to method getData.
     * - Parameter 1 is $conf['data'].
     * - Parameter 2 is property data by default.
     * - Parameter 2 is property alternativeData, if set as array.
     * - Property alternativeData is always unset to ''.
     * - Returns the return value.
     *
     * @test
     * @dataProvider stdWrap_dataDataProvider
     * @param mixed $expect Expect either $data or $alternativeData.
     * @param array $data The data.
     * @param mixed $alt The alternativeData.
     * @return void
     */
    public function stdWrap_data($expect, $data, $alt)
    {
        $conf = ['data' => $this->getUniqueId('conf.data')];
        $return = $this->getUniqueId('return');
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class, ['getData']);
        $subject->_set('data', $data);
        $subject->_set('alternativeData', $alt);
        $subject
            ->expects($this->once())
            ->method('getData')
            ->with($conf['data'], $expect)
            ->willReturn($return);
        $this->assertSame($return, $subject->stdWrap_data('discard', $conf));
        $this->assertSame('', $subject->_get('alternativeData'));
    }

    /**
     * Check that stdWrap_dataWrap works properly.
     *
     * Show:
     *
     *  - Delegates to method dataWrap.
     *  - Parameter 1 is $content.
     *  - Parameter 2 is $conf['dataWrap'].
     *  - Returns the return value.
     *
     *  @test
     *  @return void.
     */
    public function stdWrap_dataWrap()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'dataWrap' => $this->getUniqueId('dataWrap'),
            'dataWrap.' => [$this->getUniqueId('not used')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['dataWrap'])->getMock();
        $subject
            ->expects($this->once())
            ->method('dataWrap')
            ->with($content, $conf['dataWrap'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_dataWrap($content, $conf));
    }

    /**
     * Data provider for the stdWrap_date test
     *
     * @return array [$expect, $content, $conf, $now]
     */
    public function stdWrap_dateDataProvider()
    {
        // Fictive execution time: 2015-10-02 12:00
        $now =  1443780000;
        return [
            'given timestamp' => [
                '02.10.2015',
                $now,
                ['date' => 'd.m.Y'],
                $now
            ],
            'empty string' => [
                '02.10.2015',
                '',
                ['date' => 'd.m.Y'],
                $now
            ],
            'testing null' => [
                '02.10.2015',
                null,
                ['date' => 'd.m.Y'],
                $now
            ],
            'given timestamp return GMT' => [
                '02.10.2015 10:00:00',
                $now,
                [
                    'date' => 'd.m.Y H:i:s',
                    'date.' => ['GMT' => true],
                ],
                $now
            ]
        ];
    }

    /**
     * Check if stdWrap_date works properly.
     *
     * @test
     * @dataProvider stdWrap_dateDataProvider
     * @param string $expected The expected output.
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     * @param int $now Fictive execution time.
     * @return void
     */
    public function stdWrap_date($expected, $content, $conf, $now)
    {
        $GLOBALS['EXEC_TIME'] = $now;
        $this->assertEquals($expected,
            $this->subject->stdWrap_date($content, $conf));
    }

    /**
     * Check if stdWrap_debug works properly.
     *
     * @test
     * @return void
     */
    public function stdWrap_debug()
    {
        $expect = '<pre>&lt;p class=&quot;class&quot;&gt;&lt;br/&gt;'
            . '&lt;/p&gt;</pre>';
        $content = '<p class="class"><br/></p>';
        $this->assertSame($expect, $this->subject->stdWrap_debug($content));
    }

    /**
     * Check if stdWrap_debug works properly.
     *
     * Show:
     *
     * - Calls the function debug.
     * - Parameter 1 is $this->data.
     * - Parameter 2 is the string '$cObj->data:'.
     * - If $this->alternativeData is an array the same is repeated with:
     * - Parameter 1 is $this->alternativeData.
     * - Parameter 2 is the string '$cObj->alternativeData:'.
     * - Returns $content as is.
     *
     * Note 1:
     *
     *   As PHPUnit can't mock PHP function calls, the call to debug can't be
     *   easily intercepted. The test is done indirectly by catching the
     *   frontend output of debug.
     *
     * Note 2:
     *
     *   The second parameter to the debug function isn't used by the current
     *   implementation at all. It can't even indirectly be tested.
     *
     * @test
     * @return void
     */
    public function stdWrap_debugData()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $content = $this->getUniqueId('content');
        $key = $this->getUniqueId('key');
        $value = $this->getUniqueId('value');
        $altValue = $this->getUniqueId('value alt');
        $this->subject->data = [$key => $value];
        // Without alternative data only data is returned.
        ob_start();
        $result = $this->subject->stdWrap_debugData($content);
        $out = ob_get_clean();
        $this->assertSame($result, $content);
        $this->assertNotContains('$cObj->data', $out);
        $this->assertContains($value, $out);
        $this->assertNotContains($altValue, $out);
        // By adding alternative data both are returned together.
        $this->subject->alternativeData = [$key => $altValue];
        ob_start();
        $this->subject->stdWrap_debugData($content);
        $out = ob_get_clean();
        $this->assertNotContains('$cObj->alternativeData', $out);
        $this->assertContains($value, $out);
        $this->assertContains($altValue, $out);
    }

    /*
     * Data provider for stdWrap_debugFunc.
     *
     * @return array [$expectArray, $confDebugFunc]
     */
    public function stdWrap_debugFuncDataProvider()
    {
        return [
            'expect array by string' => [ true, '2' ],
            'expect array by integer' => [ true, 2 ],
            'do not expect array' => [ false, '' ],
        ];
    }

    /**
     * Check if stdWrap_debugFunc works properly.
     *
     * Show:
     *
     * - Calls the function debug with one parameter.
     * - The parameter is the given $content string.
     * - The string is casted to array before, if (int)$conf['debugFunc'] is 2.
     * - Returns $content as is.
     *
     * Note 1:
     *
     *   As PHPUnit can't mock PHP function calls, the call to debug can't be
     *   easily intercepted. The test is done indirectly by catching the
     *   frontend output of debug.
     *
     * @test
     * @dataProvider stdWrap_debugFuncDataProvider
     * @param bool $expectArray If cast to array is expected.
     * @param mixed $confDebugFunc The configuration for $conf['debugFunc'].
     * @return void
     */
    public function stdWrap_debugFunc($expectArray, $confDebugFunc)
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $content = $this->getUniqueId('content');
        $conf = ['debugFunc' => $confDebugFunc];
        ob_start();
        $result = $this->subject->stdWrap_debugFunc($content, $conf);
        $out = ob_get_clean();
        $this->assertSame($result, $content);
        $this->assertContains($content, $out);
        if ($expectArray) {
            $this->assertContains('=>', $out);
        } else {
            $this->assertNotContains('=>', $out);
        }
    }

    /**
     * Data provider for stdWrap_doubleBrTag
     *
     * @return array Order expected, input, config
     */
    public function stdWrapDoubleBrTagDataProvider()
    {
        return [
            'no config: void input' => [
                '',
                '',
                [],
            ],
            'no config: single break' => [
                'one' . LF . 'two',
                'one' . LF . 'two',
                [],
            ],
            'no config: double break' => [
                'onetwo',
                'one' . LF . LF . 'two',
                [],
            ],
            'no config: double break with whitespace' => [
                'onetwo',
                'one' . LF . TAB . ' ' . TAB . ' ' . LF . 'two',
                [],
            ],
            'no config: single break around' => [
                LF . 'one' . LF,
                LF . 'one' . LF,
                [],
            ],
            'no config: double break around' => [
                'one',
                LF . LF . 'one' . LF . LF,
                [],
            ],
            'empty string: double break around' => [
                'one',
                LF . LF . 'one' . LF . LF,
                ['doubleBrTag' => ''],
            ],
            'br tag: double break' => [
                'one<br/>two',
                'one' . LF . LF . 'two',
                ['doubleBrTag' => '<br/>'],
            ],
            'br tag: double break around' => [
                '<br/>one<br/>',
                LF . LF . 'one' . LF . LF,
                ['doubleBrTag' => '<br/>'],
            ],
            'double br tag: double break around' => [
                '<br/><br/>one<br/><br/>',
                LF . LF . 'one' . LF . LF,
                ['doubleBrTag' => '<br/><br/>'],
            ],
        ];
    }

    /**
     * Check if doubleBrTag works properly
     *
     * @test
     * @dataProvider stdWrapDoubleBrTagDataProvider
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $config The property 'doubleBrTag'.
     * @return void
     */
    public function stdWrap_doubleBrTag($expected, $input, $config)
    {
        $this->assertEquals($expected, $this->subject->stdWrap_doubleBrTag($input, $config));
    }

    /**
     * Data provider for stdWrap_editIcons.
     *
     * @return [$expect, $content, $conf, $login, $times, $param3, $will]
     */
    public function stdWrap_editIconsDataProvider()
    {
        $content = $this->getUniqueId('content');
        $editIcons = $this->getUniqueId('editIcons');
        $editIconsArray = [$this->getUniqueId('editIcons.')];
        $will = $this->getUniqueId('will');
        return [
            'standard case calls edit icons' => [
                $will, $content,
                ['editIcons' => $editIcons, 'editIcons.' => $editIconsArray],
                true, 1, $editIconsArray, $will
            ],
            'null in editIcons. repalaced by []' => [
                $will, $content,
                ['editIcons' => $editIcons, 'editIcons.' => null],
                true, 1, [], $will
            ],
            'missing editIcons. replaced by []' => [
                $will, $content,
                ['editIcons' => $editIcons],
                true, 1, [], $will
            ],
            'no user login disables call' => [
                $content, $content,
                ['editIcons' => $editIcons, 'editIcons.' => $editIconsArray],
                false, 0, $editIconsArray, $will
            ],
            'empty string in editIcons disables call' => [
                $content, $content,
                ['editIcons' => '', 'editIcons.' => $editIconsArray],
                true, 0, $editIconsArray, $will
            ],
            'zero string in editIcons disables call' => [
                $content, $content,
                ['editIcons' => '0', 'editIcons.' => $editIconsArray],
                true, 0, $editIconsArray, $will
            ],
        ];
    }

    /**
     * Check if stdWrap_editIcons works properly.
     *
     * Show:
     *
     * - Returns $content as is if:
     *   - beUserLogin is not set
     *   - (bool)$conf['editIcons'] is false
     * - Otherwise:
     *   - Delegates to method editIcons.
     *   - Parameter 1 is $content.
     *   - Parameter 2 is $conf['editIcons'].
     *   - Parameter 3 is $conf['editIcons.'].
     *   - If $conf['editIcons.'] is no array at all, the empty array is used.
     *   - Returns the return value.
     *
     * @test
     * @dataProvider stdWrap_editIconsDataProvider
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param array $conf The given configuration.
     * @param bool $login Simulate backend user login.
     * @param int $times Times editIcons is called (0 or 1).
     * @param array $param3 The expected third parameter.
     * @param string $will Return value of editIcons.
     * @return void
     */
    public function stdWrap_editIcons(
        $expect, $content, $conf, $login, $times, $param3, $will)
    {
        $GLOBALS['TSFE']->beUserLogin = $login;
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['editIcons'])->getMock();
        $subject
            ->expects($this->exactly($times))
            ->method('editIcons')
            ->with($content, $conf['editIcons'], $param3)
            ->willReturn($will);
        $this->assertSame($expect,
            $subject->stdWrap_editIcons($content, $conf));
    }

    /**
     * Check if stdWrap_encapsLines works properly.
     *
     * Show:
     *
     * - Delegates to method encaps_lineSplit.
     * - Parameter 1 is $content.
     * - Prameter 2 is $conf['encapsLines'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
     public function stdWrap_encapsLines()
     {
         $content = $this->getUniqueId('content');
         $conf = [
             'encapsLines' => [$this->getUniqueId('not used')],
             'encapsLines.' => [$this->getUniqueId('encapsLines.')],
         ];
         $return = $this->getUniqueId('return');
         $subject = $this->getMockBuilder(ContentObjectRenderer::class)
             ->setMethods(['encaps_lineSplit'])->getMock();
         $subject
             ->expects($this->once())
             ->method('encaps_lineSplit')
             ->with($content, $conf['encapsLines.'])
             ->willReturn($return);
         $this->assertSame($return,
             $subject->stdWrap_encapsLines($content, $conf));
     }

    /**
     * Data provider for stdWrap_editPanel.
     *
     * @return [$expect, $content, $login, $times, $will]
     */
    public function stdWrap_editPanelDataProvider()
    {
        $content = $this->getUniqueId('content');
        $will = $this->getUniqueId('will');
        return [
            'standard case calls edit icons' => [
                $will, $content, true, 1, $will
            ],
            'no user login disables call' => [
                $content, $content, false, 0, $will
            ],
        ];
    }

    /**
     * Check if stdWrap_editPanel works properly.
     *
     * Show:
     *
     * - Returns $content as is if:
     *   - beUserLogin is not set
     * - Otherwise:
     *   - Delegates to method editPanel.
     *   - Parameter 1 is $content.
     *   - Parameter 2 is $conf['editPanel'].
     *   - Returns the return value.
     *
     * @test
     * @dataProvider stdWrap_editPanelDataProvider
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param bool $login Simulate backend user login.
     * @param int $times Times editPanel is called (0 or 1).
     * @param string $will Return value of editPanel.
     * @return void
     */
    public function stdWrap_editPanel(
        $expect, $content, $login, $times, $will)
    {
        $GLOBALS['TSFE']->beUserLogin = $login;
        $conf = ['editPanel.' => [$this->getUniqueId('editPanel.')]];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['editPanel'])->getMock();
        $subject
            ->expects($this->exactly($times))
            ->method('editPanel')
            ->with($content, $conf['editPanel.'])
            ->willReturn($will);
        $this->assertSame($expect,
            $subject->stdWrap_editPanel($content, $conf));
    }

    /**
     * Data provider for stdWrap_encodeForJavaScriptValue.
     *
     * @return array []
     */
    public function stdWrap_encodeForJavaScriptValueDataProvider()
    {
        return [
            'double quote in string' => [
                '\'double\u0020quote\u0022\'', 'double quote"'
            ],
            'backslash in string' => [
                '\'backslash\u0020\u005C\'', 'backslash \\'
            ],
            'exclamation mark' => [
                '\'exclamation\u0021\'', 'exclamation!'
            ],
            'whitespace tab, newline and carriage return' => [
                '\'white\u0009space\u000As\u000D\'', "white\tspace\ns\r"
            ],
            'single quote in string' => [
                '\'single\u0020quote\u0020\u0027\'', 'single quote \''
            ],
            'tag' => [
                '\'\u003Ctag\u003E\'', '<tag>'
            ],
            'ampersand in string' => [
                '\'amper\u0026sand\'', 'amper&sand'
            ]
        ];
    }

    /**
     * Check if encodeForJavaScriptValue works properly.
     *
     * @test
     * @dataProvider stdWrap_encodeForJavaScriptValueDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @return void
     */
    public function stdWrap_encodeForJavaScriptValue($expect, $content)
    {
        $this->assertSame($expect,
            $this->subject->stdWrap_encodeForJavaScriptValue($content));
    }

    /**
     * Data provider for expandList
     *
     * @return array [$expect, $content]
     */
    public function stdWrap_expandListDataProvider()
    {
        return [
            'numbers' => ['1,2,3', '1,2,3'],
            'range' => ['3,4,5', '3-5'],
            'numbers and range' => ['1,3,4,5,7', '1,3-5,7']
        ];
    }

    /**
     * Test for the stdWrap function "expandList"
     *
     * The method simply delegates to GeneralUtility::expandList. There is no
     * need to repeat the full set of tests of this method here. As PHPUnit
     * can't mock static methods, to prove they are called, all we do here
     * is to provide a few smoke tests.
     *
     * @test
     * @dataProvider stdWrap_expandListDataProvider
     * @param string $expected The expeced output.
     * @param string $content The given content.
     * @return void
     */
    public function stdWrap_expandList($expected, $content)
    {
        $this->assertEquals($expected,
            $this->subject->stdWrap_expandList($content));
    }

    /**
     * Check if stdWrap_field works properly.
     *
     * Show:
     *
     * - calls getFieldVal
     * - passes conf['field'] as parameter
     *
     * @test
     * @return void
     */
    public function stdWrap_field()
    {
        $expect = $this->getUniqueId('expect');
        $conf = ['field' => $this->getUniqueId('field')];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['getFieldVal'])->getMock();
        $subject
            ->expects($this->once())
            ->method('getFieldVal')
            ->with($conf['field'])
            ->willReturn($expect);
        $this->assertSame($expect,
            $subject->stdWrap_field('discarded', $conf));
    }

    /**
     * Data provider for stdWrap_fieldRequired.
     *
     * @return array [$expect, $stop, $content, $conf]
     */
    public function stdWrap_fieldRequiredDataProvider()
    {
        $content = $this->getUniqueId('content');
        return [
            // resulting in boolean false
            'false is false' => [
                '', true, $content, ['fieldRequired' => 'false']
            ],
            'null is false' => [
                '', true, $content, ['fieldRequired' => 'null']
            ],
            'empty string is false' => [
                '', true, $content, ['fieldRequired' => 'empty']
            ],
            'whitespace is false' => [
                '', true, $content, ['fieldRequired' => 'whitespace']
            ],
            'string zero is false' => [
                '', true, $content, ['fieldRequired' => 'stringZero']
            ],
            'string zero with whitespace is false' => [
                '', true, $content,
                ['fieldRequired' => 'stringZeroWithWhiteSpace']
            ],
            'zero is false' => [
                '', true, $content, ['fieldRequired' => 'zero']
            ],
            // resulting in boolean true
            'true is true' => [
                $content, false, $content, ['fieldRequired' => 'true']
            ],
            'string is true' => [
                $content, false, $content, ['fieldRequired' => 'string']
            ],
            'one is true' => [
                $content, false, $content, ['fieldRequired' => 'one']
            ]
        ];
    }

    /**
     * Check if stdWrap_fieldRequired works properly.
     *
     * Show:
     *
     *  - The value is taken from property array data.
     *  - The key is taken from $conf['fieldRequired'].
     *  - The value is casted to string by trim() and trimmed.
     *  - It is further casted to boolean by if().
     *  - False triggers a stop of further rendering.
     *  - False returns '', true the given content as is.
     *
     * @test
     * @dataProvider stdWrap_fieldRequiredDataProvider
     * @param mixed $expect The expected output.
     * @param bool $stop Expect stop further rendering.
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     * @return void
     */
    public function stdWrap_fieldRequired($expect, $stop, $content, $conf)
    {
        $data = [
            'null' => null,
            'false' => false,
            'empty' => '',
            'whitespace' => TAB . ' ',
            'stringZero' => '0',
            'stringZeroWithWhiteSpace' => TAB . ' 0 ' . TAB,
            'zero' => 0,
            'string' => 'string',
            'true' => true,
            'one' => 1
        ];
        $subject = $this->subject;
        $subject->_set('data', $data);
        $subject->_set('stdWrapRecursionLevel', 1);
        $subject->_set('stopRendering', [1 => false]);
        $this->assertSame($expect,
            $subject->stdWrap_fieldRequired($content, $conf));
        $this->assertSame($stop, $subject->_get('stopRendering')[1]);
    }

    /**
     * Check if stdWrap_filelink works properly.
     *
     * Show:
     *
     * - Delegates to method filelink.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['filelink.'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_filelink()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'filelink' => $this->getUniqueId('not used'),
            'filelink.' => [$this->getUniqueId('filelink.')],
        ];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['filelink'])->getMock();
        $subject->expects($this->once())->method('filelink')
            ->with($content, $conf['filelink.'])->willReturn('return');
        $this->assertSame('return',
            $subject->stdWrap_filelink($content, $conf));
    }

    /**
     * Check if stdWrap_filelist works properly.
     *
     * Show:
     *
     * - Delegates to method filelist.
     * - Parameter is $conf['filelist'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_filelist()
    {
        $conf = [
            'filelist' => $this->getUniqueId('filelist'),
            'filelist.' => [$this->getUniqueId('not used')],
        ];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['filelist'])->getMock();
        $subject->expects($this->once())->method('filelist')
            ->with($conf['filelist'])->willReturn('return');
        $this->assertSame('return',
            $subject->stdWrap_filelist('discard', $conf));
    }

    /**
     * Data provider for the hash test
     *
     * @return array [$expect, $content, $conf]
     */
    public function hashDataProvider()
    {
        return [
            'md5' => [
                'bacb98acf97e0b6112b1d1b650b84971',
                'joh316',
                ['hash' => 'md5']
            ],
            'sha1' => [
                '063b3d108bed9f88fa618c6046de0dccadcf3158',
                'joh316',
                ['hash' => 'sha1']
            ],
            'stdWrap capability' => [
                'bacb98acf97e0b6112b1d1b650b84971',
                'joh316',
                [
                    'hash' => '5',
                    'hash.' => ['wrap' => 'md|']
                ]
            ],
            'non-existing hashing algorithm' => [
                '',
                'joh316',
                ['hash' => 'non-existing']
            ]
        ];
    }

    /**
     * Check if stdWrap_hash works properly.
     *
     * Show:
     *
     *  - Algorithms: sha1, md5
     *  - Returns '' for invalid algorithm.
     *  - Value can be processed by stdWrap.
     *
     * @test
     * @dataProvider hashDataProvider
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param array $conf The given configuration.
     * @return void
     */
    public function stdWrap_hash($expect, $content, $conf)
    {
        $this->assertSame($expect,
            $this->subject->stdWrap_hash($content, $conf));
    }

    /**
     * Data provider for stdWrap_htmlSpecialChars
     *
     * @return array Order: expected, input, conf
     */
    public function stdWrap_htmlSpecialCharsDataProvider()
    {
        return [
            'void conf' => [
                '&lt;span&gt;1 &amp;lt; 2&lt;/span&gt;',
                '<span>1 &lt; 2</span>',
                [],
            ],
            'void preserveEntities' => [
                '&lt;span&gt;1 &amp;lt; 2&lt;/span&gt;',
                '<span>1 &lt; 2</span>',
                ['htmlSpecialChars.' => []],
            ],
            'false preserveEntities' => [
                '&lt;span&gt;1 &amp;lt; 2&lt;/span&gt;',
                '<span>1 &lt; 2</span>',
                ['htmlSpecialChars.' => ['preserveEntities' => 0]],
            ],
            'true preserveEntities' => [
                '&lt;span&gt;1 &lt; 2&lt;/span&gt;',
                '<span>1 &lt; 2</span>',
                ['htmlSpecialChars.' => ['preserveEntities' => 1]],
            ],
        ];
    }

    /**
     * Check if stdWrap_htmlSpecialChars works properly
     *
     * @test
     * @dataProvider stdWrap_htmlSpecialCharsDataProvider
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf htmlSpecialChars.preserveEntities
     * @return void
     */
    public function stdWrap_htmlSpecialChars($expected, $input, $conf)
    {
        $this->assertSame($expected,
            $this->subject->stdWrap_htmlSpecialChars($input, $conf));
    }

    /**
     * Data provider for stdWrap_if.
     *
     * @return array [$expect, $stop, $content, $conf, $times, $will]
     */
    public function stdWrap_ifDataProvider()
    {
        $content = $this->getUniqueId('content');
        $conf = ['if.' => [$this->getUniqueId('if.')]];
        return [
            // evals to true
            'empty config' => [
                $content, false, $content, [], 0, null
            ],
            'if. is empty array' => [
                $content, false, $content, ['if.' => []], 0, null
            ],
            'if. is null' => [
                $content, false, $content, ['if.' => null], 0, null
            ],
            'if. is false' => [
                $content, false, $content, ['if.' => false], 0, null
            ],
            'if. is 0' => [
                $content, false, $content, ['if.' => false], 0, null
            ],
            'if. is "0"' => [
                $content, false, $content, ['if.' => '0'], 0, null
            ],
            'checkIf returning true' => [
                $content, false, $content, $conf, 1, true
            ],
            // evals to false
            'checkIf returning false' => [
                '', true, $content, $conf, 1, false
            ],
        ];
    }

    /**
     * Check if stdWrap_if works properly.
     *
     * Show:
     *
     *  - Delegates to the method checkIf to check for 'true'.
     *  - The parameter to checkIf is $conf['if.'].
     *  - Is also 'true' if $conf['if.'] is empty (PHP method empty).
     *  - 'False' triggers a stop of further rendering.
     *  - Returns the content as is or '' if false.
     *
     * @test
     * @dataProvider stdWrap_ifDataProvider
     * @param mixed $expect The expected output.
     * @param bool $stop Expect stop further rendering.
     * @param mixed $content The given content.
     * @param mixed $config The given configuration.
     * @param int $times Times checkIf is called (0 or 1).
     * @param bool|null $will Return of checkIf (null if not called).
     * @return void
     */
    public function stdWrap_if($expect, $stop, $content, $conf, $times, $will)
    {
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class, ['checkIf']);
        $subject->_set('stdWrapRecursionLevel', 1);
        $subject->_set('stopRendering', [1 => false]);
        $subject
            ->expects($this->exactly($times))
            ->method('checkIf')
            ->with($conf['if.'])
            ->willReturn($will);
        $this->assertSame($expect, $subject->stdWrap_if($content, $conf));
        $this->assertSame($stop, $subject->_get('stopRendering')[1]);
    }

    /**
     * Data provider for stdWrap_ifBlank.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_ifBlankDataProvider()
    {
        $alt = $this->getUniqueId('alternative content');
        $conf = ['ifBlank' => $alt];
        return [
            // blank cases
            'null is blank' => [$alt, null, $conf],
            'false is blank' => [$alt, false, $conf],
            'empty string is blank' => [$alt, '', $conf],
            'whitespace is blank' => [$alt, TAB . '', $conf],
            // non-blank cases
            'string is not blank' => ['string', 'string', $conf],
            'zero is not blank' => [0, 0, $conf],
            'zero string is not blank' => ['0', '0', $conf],
            'zero float is not blank' => [0.0, 0.0, $conf],
            'true is not blank' => [true, true, $conf],
        ];
    }

    /**
     * Check that stdWrap_ifBlank works properly.
     *
     * Show:
     *
     * - The content is returned if not blank.
     * - Otherwise $conf['ifBlank'] is returned.
     * - The check for blank is done by comparing the trimmed content
     *   with the empty string for equality.
     *
     * @test
     * @dataProvider stdWrap_ifBlankDataProvider
     * @param mixed $expected The expected output.
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     * @return void
     */
    public function stdWrap_ifBlank($expect, $content, $conf)
    {
        $result = $this->subject->stdWrap_ifBlank($content, $conf);
        $this->assertSame($expect, $result);
    }

    /**
     * Data provider for stdWrap_ifEmpty.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_ifEmptyDataProvider()
    {
        $alt = $this->getUniqueId('alternative content');
        $conf = ['ifEmpty' => $alt];
        return [
            // empty cases
            'null is empty' => [$alt, null, $conf ],
            'false is empty' => [$alt, false, $conf ],
            'zero is empty' => [$alt, 0, $conf ],
            'float zero is empty' => [$alt, 0.0, $conf ],
            'whitespace is empty' => [$alt, TAB . ' ', $conf ],
            'empty string is empty' => [$alt, '', $conf ],
            'zero string is empty' => [$alt, '0', $conf ],
            'zero string is empty with whitespace' => [
                $alt, TAB . ' 0 ' . TAB, $conf
            ],
            // non-empty cases
            'string is not empty' => ['string', 'string', $conf ],
            '1 is not empty' => [1, 1, $conf ],
            '-1 is not empty' => [-1, -1, $conf ],
            '0.1 is not empty' => [0.1, 0.1, $conf ],
            '-0.1 is not empty' => [-0.1, -0.1, $conf ],
            'true is not empty' => [true, true, $conf ],
        ];
    }

    /**
     * Check that stdWrap_ifEmpty works properly.
     *
     * Show:
     *
     * - Returns the content, if not empty.
     * - Otherwise returns $conf['ifEmpty'].
     * - Empty is checked by cast to boolean after trimming.
     *
     * @test
     * @dataProvider stdWrap_ifEmptyDataProvider
     * @param mixed $expect The expected output.
     * @param mixed $content The given content.
     * @param array $conf The given configuration.
     * @return void
     */
    public function stdWrap_ifEmpty($expect, $content, $conf)
    {
        $result = $this->subject->stdWrap_ifEmpty($content, $conf);
        $this->assertSame($expect, $result);
    }

    /**
     * Data provider for stdWrap_ifNull.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_ifNullDataProvider()
    {
        $alt = $this->getUniqueId('alternative content');
        $conf = ['ifNull' => $alt];
        return [
            'only null is null' => [$alt, null, $conf],
            'zero is not null' => [0, 0, $conf],
            'float zero is not null' => [0.0, 0.0, $conf],
            'false is not null' => [false, false, $conf],
            'zero is not null' => [0, 0, $conf],
            'zero string is not null' => ['0', '0', $conf],
            'empty string is not null' => ['', '', $conf],
            'whitespace is not null' => [TAB . '', TAB . '', $conf],
        ];
    }

    /**
     * Check that stdWrap_ifNull works properly.
     *
     * Show:
     *
     * - Returns the content, if not null.
     * - Otherwise returns $conf['ifNull'].
     * - Null is strictly checked by identiy with null.
     *
     * @test
     * @dataProvider stdWrap_ifNullDataProvider
     * @param mixed $expected The expected output.
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     * @return void
     */
    public function stdWrap_ifNull($expect, $content, $conf)
    {
        $result = $this->subject->stdWrap_ifNull($content, $conf);
        $this->assertSame($expect, $result);
    }

    /**
     * Data provider for stdWrap_innerWrap
     *
     * @return array Order expected, input, conf
     */
    public function stdWrap_innerWrapDataProvider()
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['innerWrap' => '<wrap>|</wrap>'],
            ],
            'missing pipe puts wrap before' => [
                '<pre>XXX',
                'XXX',
                ['innerWrap' => '<pre>'],
            ],
            'trims whitespace' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['innerWrap' => '<wrap>' . TAB . ' | ' . TAB . '</wrap>'],
            ],
            'split char change is not possible' => [
                '<wrap> # </wrap>XXX',
                'XXX',
                [
                    'innerWrap' => '<wrap> # </wrap>',
                    'innerWrap.' => ['splitChar' => '#'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_innerWrap works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Property: innerWrap
     * @return void
     * @test
     * @dataProvider stdWrap_innerWrapDataProvider
     */
    public function stdWrap_innerWrap($expected, $input, $conf)
    {
        $this->assertSame($expected,
            $this->subject->stdWrap_innerWrap($input, $conf));
    }

    /**
     * Data provider for stdWrap_innerWrap2
     *
     * @return array Order expected, input, conf
     */
    public function stdWrap_innerWrap2DataProvider()
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['innerWrap2' => '<wrap>|</wrap>'],
            ],
            'missing pipe puts wrap before' => [
                '<pre>XXX',
                'XXX',
                ['innerWrap2' => '<pre>'],
            ],
            'trims whitespace' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['innerWrap2' => '<wrap>' . TAB . ' | ' . TAB . '</wrap>'],
            ],
            'split char change is not possible' => [
                '<wrap> # </wrap>XXX',
                'XXX',
                [
                    'innerWrap2' => '<wrap> # </wrap>',
                    'innerWrap2.' => ['splitChar' => '#'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_innerWrap2 works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Property: innerWrap2
     * @return void
     * @test
     * @dataProvider stdWrap_innerWrap2DataProvider
     */
    public function stdWrap_innerWrap2($expected, $input, $conf)
    {
        $this->assertSame($expected,
            $this->subject->stdWrap_innerWrap2($input, $conf));
    }

    /**
     * Check if stdWrap_insertData works properly.
     *
     * Show:
     *
     *  - Delegates to method insertData.
     *  - Parameter 1 is $content.
     *  - Returns the return value.
     *
     *  @test
     *  @return void
     */
    public function stdWrap_insertData()
    {
        $content = $this->getUniqueId('content');
        $conf = [$this->getUniqueId('conf not used')];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['insertData'])->getMock();
        $subject->expects($this->once())->method('insertData')
            ->with($content)->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_insertData($content, $conf));
    }

    /**
     * Data provider for stdWrap_intval
     *
     * @return array [$expect, $content]
     */
    public function stdWrap_intvalDataProvider()
    {
        return [
            // numbers
            'int' => [123, 123],
            'float' => [123, 123.45],
            'float does not round up' => [123, 123.55],
            // negative numbers
            'negative int' => [-123, -123],
            'negative float' => [-123, -123.45],
            'negative float does not round down' => [-123, -123.55],
            // strings
            'word string' => [0, 'string'],
            'empty string' => [0, ''],
            'zero string' => [0, '0'],
            'int string' => [123, '123'],
            'float string' => [123, '123.55'],
            'negative float string' => [-123, '-123.55'],
            // other types
            'null' => [0, null],
            'true' => [1, true],
            'false' => [0, false]
        ];
    }

    /**
     * Check that stdWrap_intval works properly.
     *
     * Show:
     *
     * - It does not round up.
     * - All types of input is casted to int:
     *   - null: 0
     *   - false: 0
     *   - true: 1
     *   -
     *
     *
     *
     * @test
     * @dataProvider stdWrap_intvalDataProvider
     * @param int $expect The expected output.
     * @param string $content The given input.
     * @return void
     */
    public function stdWrap_intval($expect, $content)
    {
        $this->assertSame($expect, $this->subject->stdWrap_intval($content));
    }

    /**
     * Data provider for stdWrap_keywords
     *
     * @return string[][] Order expected, input
     */
    public function stdWrapKeywordsDataProvider()
    {
        return [
            'empty string' => ['', ''],
            'blank' => ['', ' '],
            'tab' => ['', "\t"],
            'single semicolon' => [',', ' ; '],
            'single comma' => [',', ' , '],
            'single nl' => [',', ' ' . PHP_EOL . ' '],
            'double semicolon' => [',,', ' ; ; '],
            'double comma' => [',,', ' , , '],
            'double nl' => [',,', ' ' . PHP_EOL . ' ' . PHP_EOL . ' '],
            'simple word' => ['one', ' one '],
            'simple word trimmed' => ['one', 'one'],
            ', separated' => ['one,two', ' one , two '],
            '; separated' => ['one,two', ' one ; two '],
            'nl separated' => ['one,two', ' one ' . PHP_EOL . ' two '],
            ', typical' => ['one,two,three', 'one, two, three'],
            '; typical' => ['one,two,three', ' one; two; three'],
            'nl typical' => [
                'one,two,three',
                'one' . PHP_EOL . 'two' . PHP_EOL . 'three'
            ],
            ', sourounded' => [',one,two,', ' , one , two , '],
            '; sourounded' => [',one,two,', ' ; one ; two ; '],
            'nl sourounded' => [
                ',one,two,',
                ' ' . PHP_EOL . ' one ' . PHP_EOL . ' two ' . PHP_EOL . ' '
            ],
            'mixed' => [
                'one,two,three,four',
                ' one, two; three' . PHP_EOL . 'four'
            ],
            'keywods with blanks in words' => [
                'one plus,two minus',
                ' one plus , two minus ',
            ]
        ];
    }

    /**
     * Check if stdWrap_keywords works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @return void
     * @test
     * @dataProvider stdWrapKeywordsDataProvider
     */
    public function stdWrap_keywords($expected, $input)
    {
        $this->assertSame($expected, $this->subject->stdWrap_keywords($input));
    }

    /**
     * Data provider for stdWrap_lang
     *
     * @return array Order expected, input, conf, language
     */
    public function stdWrap_langDataProvider()
    {
        return [
            'empty conf' => [
                'original',
                'original',
                [],
                'de',
            ],
            'translation de' => [
                'Übersetzung',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ]
                ],
                'de',
            ],
            'translation it' => [
                'traduzione',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ]
                ],
                'it',
            ],
            'no translation' => [
                'original',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ]
                ],
                '',
            ],
            'missing label' => [
                'original',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ]
                ],
                'fr',
            ],
        ];
    }

    /**
     * Check if stdWrap_lang works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Properties: lang.xy.
     * @param string $language For $TSFE->config[config][language].
     * @return void
     * @test
     * @dataProvider stdWrap_langDataProvider
     */
    public function stdWrap_lang($expected, $input, $conf, $language)
    {
        if ($language) {
            $this->frontendControllerMock
                ->config['config']['language'] = $language;
        }
        $this->assertSame($expected,
            $this->subject->stdWrap_lang($input, $conf));
    }

    /**
     * Check if stdWrap_listNum works properly.
     *
     * Show:
     *
     * - Delegates to method listNum.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['listNum'].
     * - Parameter 3 is $conf['listNum.']['splitChar'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_listNum()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'listNum' => $this->getUniqueId('listNum'),
            'listNum.' => [
                'splitChar' => $this->getUniqueId('splitChar')
            ],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['listNum'])->getMock();
        $subject
            ->expects($this->once())
            ->method('listNum')
            ->with(
                $content,
                $conf['listNum'],
                $conf['listNum.']['splitChar']
            )
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_listNum($content, $conf));
    }

    /**
     * Data provider for stdWrap_noTrimWrap.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_noTrimWrapDataProvider()
    {
        return [
            'Standard case' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => '| left | right |',
                ],
            ],
            'Tabs as whitespace' => [
                TAB . 'left' . TAB . 'middle' . TAB . 'right' . TAB,
                'middle',
                [
                    'noTrimWrap' =>
                    '|' . TAB . 'left' . TAB . '|' . TAB . 'right' . TAB . '|',
                ],
            ],
            'Split char is 0' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => '0 left 0 right 0',
                    'noTrimWrap.' => ['splitChar' => '0'],
                ],
            ],
            'Split char is pipe (default)' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => '| left | right |',
                    'noTrimWrap.' => ['splitChar' => '|'],
                ],
            ],
            'Split char is a' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => 'a left a right a',
                    'noTrimWrap.' => ['splitChar' => 'a'],
                ],
            ],
            'Split char is a word (ab)' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => 'ab left ab right ab',
                    'noTrimWrap.' => ['splitChar' => 'ab'],
                ],
            ],
            'Split char accepts stdWrap' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => 'abc left abc right abc',
                    'noTrimWrap.' => [
                        'splitChar' => 'b',
                        'splitChar.' => ['wrap' => 'a|c'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_noTrimWrap works properly.
     *
     * @test
     * @dataProvider stdWrap_noTrimWrapDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param array $conf The given configuration.
     * @return void
     */
    public function stdWrap_noTrimWrap($expect, $content, $conf)
    {
        $this->assertSame($expect,
            $this->subject->stdWrap_noTrimWrap($content, $conf));
    }

    /**
     * Check if stdWrap_numRows works properly.
     *
     * Show:
     *
     * - Delegates to method numRows.
     * - Parameter is $conf['numRows.'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_numRows()
    {
        $conf = [
            'numRows' => $this->getUniqueId('numRows'),
            'numRows.' => [$this->getUniqueId('numRows')],
        ];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['numRows'])->getMock();
        $subject->expects($this->once())->method('numRows')
            ->with($conf['numRows.'])->willReturn('return');
        $this->assertSame('return',
            $subject->stdWrap_numRows('discard', $conf));
    }

    /**
     * Check if stdWrap_numberFormat works properly.
     *
     * Show:
     *
     * - Delegates to the method numberFormat.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['numberFormat.'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_numberFormat()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'numberFormat' => $this->getUniqueId('not used'),
            'numberFormat.' => [$this->getUniqueId('numberFormat.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['numberFormat'])->getMock();
        $subject
            ->expects($this->once())
            ->method('numberFormat')
            ->with($content, $conf['numberFormat.'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_numberFormat($content, $conf));
    }

    /**
     * Data provider for stdWrap_outerWrap
     *
     * @return array Order expected, input, conf
     */
    public function stdWrap_outerWrapDataProvider()
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['outerWrap' => '<wrap>|</wrap>'],
            ],
            'missing pipe puts wrap before' => [
                '<pre>XXX',
                'XXX',
                ['outerWrap' => '<pre>'],
            ],
            'trims whitespace' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['outerWrap' => '<wrap>' . TAB . ' | ' . TAB . '</wrap>'],
            ],
            'split char change is not possible' => [
                '<wrap> # </wrap>XXX',
                'XXX',
                [
                    'outerWrap' => '<wrap> # </wrap>',
                    'outerWrap.' => ['splitChar' => '#'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_outerWrap works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Property: outerWrap
     * @return void
     * @test
     * @dataProvider stdWrap_outerWrapDataProvider
     */
    public function stdWrap_outerWrap($expected, $input, $conf)
    {
        $this->assertSame($expected,
            $this->subject->stdWrap_outerWrap($input, $conf));
    }

    /**
     * Data provider for stdWrap_csConv
     *
     * @return array Order expected, input, conf
     */
    public function stdWrap_overrideDataProvider()
    {
        return [
            'standard case' => [
                'override', 'content', ['override' => 'override']
            ],
            'empty conf does not override' => [
                'content', 'content', []
            ],
            'empty string does not override' => [
                'content', 'content', ['override' => '']
            ],
            'whitespace does not override' => [
                'content', 'content', ['override' => ' ' . TAB]
            ],
            'zero does not override' => [
                'content', 'content', ['override' => 0]
            ],
            'false does not override' => [
                'content', 'content', ['override' => false]
            ],
            'null does not override' => [
                'content', 'content', ['override' => null]
            ],
            'one does override' => [
                1, 'content', ['override' => 1]
            ],
            'minus one does override' => [
                -1, 'content', ['override' => -1]
            ],
            'float does override' => [
                -0.1, 'content', ['override' => -0.1]
            ],
            'true does override' => [
                true, 'content', ['override' => true]
            ],
            'the value is not trimmed' => [
                TAB . 'override', 'content', ['override' => TAB . 'override']
            ],
        ];
    }

    /**
     * Check if stdWrap_override works properly.
     *
     * @test
     * @dataProvider stdWrap_overrideDataProvider
     * @param string $input The input value.
     * @param array $conf Property: setCurrent
     * @return void
     */
    public function stdWrap_override($expect, $content, $conf)
    {
        $this->assertSame($expect,
            $this->subject->stdWrap_override($content, $conf));
    }

    /**
     * Check if stdWrap_parseFunc works properly.
     *
     * Show:
     *
     * - Delegates to method parseFunc.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['parseFunc.'].
     * - Parameter 3 is $conf['parseFunc'].
     * - Returns the return.
     *
     * @test
     * @return void
     */
    public function stdWrap_parseFunc()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'parseFunc' => $this->getUniqueId('parseFunc'),
            'parseFunc.' => [$this->getUniqueId('parseFunc.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['parseFunc'])->getMock();
        $subject
            ->expects($this->once())
            ->method('parseFunc')
            ->with($content, $conf['parseFunc.'], $conf['parseFunc'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_parseFunc($content, $conf));
    }

    /**
     * Check if stdWrap_postCObject works properly.
     *
     * Show:
     *
     * - Delegates to the method cObjGetSingle().
     * - Parameter 1 is $conf['postCObject'].
     * - Parameter 2 is $conf['postCObject.'].
     * - Parameter 3 is '/stdWrap/.postCObject'.
     * - Returns the return value appended by $content.
     *
     * @test
     * @return void
     */
    public function stdWrap_postCObject()
    {
        $debugKey =  '/stdWrap/.postCObject';
        $content = $this->getUniqueId('content');
        $conf = [
            'postCObject' => $this->getUniqueId('postCObject'),
            'postCObject.' => [$this->getUniqueId('postCObject.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['cObjGetSingle'])->getMock();
        $subject
            ->expects($this->once())
            ->method('cObjGetSingle')
            ->with($conf['postCObject'], $conf['postCObject.'], $debugKey)
            ->willReturn($return);
        $this->assertSame($content . $return,
            $subject->stdWrap_postCObject($content, $conf));
    }

    /**
     * Check that stdWrap_postUserFunc works properly.
     *
     * Show:
     *  - Delegates to method callUserFunction.
     *  - Parameter 1 is $conf['postUserFunc'].
     *  - Parameter 2 is $conf['postUserFunc.'].
     *  - Returns the return value.
     *
     *  @test
     *  @return void.
     */
    public function stdWrap_postUserFunc()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'postUserFunc' => $this->getUniqueId('postUserFunc'),
            'postUserFunc.' => [$this->getUniqueId('postUserFunc.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['callUserFunction'])->getMock();
        $subject
            ->expects($this->once())
            ->method('callUserFunction')
            ->with($conf['postUserFunc'], $conf['postUserFunc.'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_postUserFunc($content, $conf));
    }

    /**
     * Check if stdWrap_postUserFuncInt works properly.
     *
     * Show:
     *
     * - Calls frontend controller method uniqueHash.
     * - Concatenates "INT_SCRIPT." and the returned hash to $substKey.
     * - Configures the frontend controller for 'INTincScript.$substKey'.
     * - The configuration array contains:
     *   - content: $content
     *   - postUserFunc: $conf['postUserFuncInt']
     *   - conf: $conf['postUserFuncInt.']
     *   - type: 'POSTUSERFUNC'
     *   - cObj: serialized content renderer object
     * - Returns "<!-- $substKey -->".
     *
     * @test
     * @return void
     */
    public function stdWrap_postUserFuncInt()
    {
        $uniqueHash = $this->getUniqueId('uniqueHash');
        $substKey = 'INT_SCRIPT.' . $uniqueHash;
        $content = $this->getUniqueId('content');
        $conf = [
            'postUserFuncInt' => $this->getUniqueId('function'),
            'postUserFuncInt.' => [$this->getUniqueId('function array')],
        ];
        $expect = '<!--' . $substKey . '-->';
        $frontend = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()->setMethods(['uniqueHash'])
            ->getMock();
        $frontend->expects($this->once())->method('uniqueHash')
            ->with()->willReturn($uniqueHash);
        $frontend->config = [];
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class, null, [$frontend]);
        $this->assertSame($expect,
            $subject->stdWrap_postUserFuncInt($content, $conf));
        $array = [
            'content' => $content,
            'postUserFunc' => $conf['postUserFuncInt'],
            'conf' => $conf['postUserFuncInt.'],
            'type' => 'POSTUSERFUNC',
            'cObj' => serialize($subject)
        ];
        $this->assertSame($array,
            $frontend->config['INTincScript'][$substKey]);
    }

    /**
     * Check if stdWrap_preCObject works properly.
     *
     * Show:
     *
     * - Delegates to the method cObjGetSingle().
     * - Parameter 1 is $conf['preCObject'].
     * - Parameter 2 is $conf['preCObject.'].
     * - Parameter 3 is '/stdWrap/.preCObject'.
     * - Returns the return value appended by $content.
     *
     * @test
     * @return void
     */
    public function stdWrap_preCObject()
    {
        $debugKey =  '/stdWrap/.preCObject';
        $content = $this->getUniqueId('content');
        $conf = [
            'preCObject' => $this->getUniqueId('preCObject'),
            'preCObject.' => [$this->getUniqueId('preCObject.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['cObjGetSingle'])->getMock();
        $subject
            ->expects($this->once())
            ->method('cObjGetSingle')
            ->with($conf['preCObject'], $conf['preCObject.'], $debugKey)
            ->willReturn($return);
        $this->assertSame($return . $content,
            $subject->stdWrap_preCObject($content, $conf));
    }

    /**
     * Check if stdWrap_preIfEmptyListNum works properly.
     *
     * Show:
     *
     * - Delegates to method listNum.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['preIfEmptyListNum'].
     * - Parameter 3 is $conf['preIfEmptyListNum.']['splitChar'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_preIfEmptyListNum()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'preIfEmptyListNum' => $this->getUniqueId('preIfEmptyListNum'),
            'preIfEmptyListNum.' => [
                'splitChar' => $this->getUniqueId('splitChar')
            ],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['listNum'])->getMock();
        $subject
            ->expects($this->once())
            ->method('listNum')
            ->with(
                $content,
                $conf['preIfEmptyListNum'],
                $conf['preIfEmptyListNum.']['splitChar']
            )
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_preIfEmptyListNum($content, $conf));
    }

    /**
     * Data provider for stdWrap_prefixComment.
     *
     * @retunr array [$expect, $content, $conf, $disable, $times, $will]
     */
    public function stdWrap_prefixCommentDataProvider()
    {
        $content = $this->getUniqueId('content');
        $will = $this->getUniqueId('will');
        $conf['prefixComment'] = $this->getUniqueId('prefixComment');
        $emptyConf1 = [];
        $emptyConf2['prefixComment'] = '';
        return [
            'standard case' => [$will, $content, $conf, false, 1, $will],
            'emptyConf1' => [$content, $content, $emptyConf1, false, 0, $will],
            'emptyConf2' => [$content, $content, $emptyConf2, false, 0, $will],
            'disabled by bool' => [$content, $content, $conf, true, 0, $will],
            'disabled by int' => [$content, $content, $conf, 1, 0, $will],
        ];
    }

    /**
     * Check that stdWrap_prefixComment works properly.
     *
     * Show:
     *
     *  - Delegates to method prefixComment.
     *  - Parameter 1 is $conf['prefixComment'].
     *  - Parameter 2 is [].
     *  - Parameter 3 is $content.
     *  - Returns the return value.
     *  - Returns $content as is,
     *    - if $conf['prefixComment'] is empty.
     *    - if 'config.disablePrefixComment' is configured by the frontend.
     *
     *  @test
     *  @dataProvider stdWrap_prefixCommentDataProvider
     *  @return void
     */
    public function stdWrap_prefixComment(
        $expect, $content, $conf, $disable, $times, $will)
    {
        $this->frontendControllerMock
            ->config['config']['disablePrefixComment'] = $disable;
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['prefixComment'])->getMock();
        $subject
            ->expects($this->exactly($times))
            ->method('prefixComment')
            ->with($conf['prefixComment'], [], $content)
            ->willReturn($will);
        $this->assertSame($expect,
            $subject->stdWrap_prefixComment($content, $conf));
    }

    /**
     * Check if stdWrap_prepend works properly.
     *
     * Show:
     *
     * - Delegates to the method cObjGetSingle().
     * - First parameter is $conf['prepend'].
     * - Second parameter is $conf['prepend.'].
     * - Third parameter is '/stdWrap/.prepend'.
     * - Returns the return value prepended to $content.
     *
     * @test
     * @return void
     */
    public function stdWrap_prepend()
    {
        $debugKey =  '/stdWrap/.prepend';
        $content = $this->getUniqueId('content');
        $conf = [
            'prepend' => $this->getUniqueId('prepend'),
            'prepend.' => [$this->getUniqueId('prepend.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['cObjGetSingle'])->getMock();
        $subject
            ->expects($this->once())
            ->method('cObjGetSingle')
            ->with($conf['prepend'], $conf['prepend.'], $debugKey)
            ->willReturn($return);
        $this->assertSame($return . $content,
            $subject->stdWrap_prepend($content, $conf));
    }

    /**
     * Data provider for stdWrap_prioriCalc
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_prioriCalcDataProvider()
    {
        return [
            'priority of *' => ['7', '1 + 2 * 3', []],
            'priority of parentheses' => ['9', '(1 + 2) * 3', []],
            'float' => ['1.5', '3/2', []],
            'intval casts to int' => [1, '3/2', ['prioriCalc' => 'intval']],
            'intval does not round' => [2, '2.7', ['prioriCalc' => 'intval']],
        ];
    }

    /**
     * Check if stdWrap_prioriCalc works properly.
     *
     * Show:
     *
     * - If $conf['prioriCalc'] is 'intval' the return is casted to int.
     * - Delegates to MathUtility::calculateWithParentheses.
     *
     * Note: As PHPUnit can't mock static methods, the call to
     *       MathUtility::calculateWithParentheses can't be easily intercepted.
     *       The test is done by testing input/output pairs instead. To not
     *       duplicate the testing of calculateWithParentheses just a few
     *       smoke tests are done here.
     *
     * @test
     * @dataProvider stdWrap_prioriCalcDataProvider
     * @param mixed $expect The expected output.
     * @param string $content The given content.
     * @param array $conf The given configuration.
     * @return void
     */
    public function stdWrap_prioriCalc($expect, $content, $conf)
    {
        $result = $this->subject->stdWrap_prioriCalc($content, $conf);
        $this->assertSame($expect, $result);
    }

    /**
     * Check if stdWrap_preUserFunc works properly.
     *
     * Show:
     *
     * - Delegates to method callUserFunction.
     * - Parameter 1 is $conf['preUserFunc'].
     * - Parameter 2 is $conf['preUserFunc.'].
     * - Parameter 3 is $content.
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_preUserFunc()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'preUserFunc' => $this->getUniqueId('preUserFunc'),
            'preUserFunc.' => [$this->getUniqueId('preUserFunc.')],
        ];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['callUserFunction'])->getMock();
        $subject->expects($this->once())->method('callUserFunction')
            ->with($conf['preUserFunc'], $conf['preUserFunc.'], $content)
            ->willReturn('return');
        $this->assertSame('return',
            $subject->stdWrap_preUserFunc($content, $conf));
    }

    /**
     * Data provider for stdWrap_rawUrlEncode
     *
     * @return array [$expect, $content].
     */
    public function stdWrap_rawUrlEncodeDataProvider()
    {
        return [
            'https://typo3.org?id=10' => [
                'https%3A%2F%2Ftypo3.org%3Fid%3D10',
                'https://typo3.org?id=10',
            ],
            'https://typo3.org?id=10&foo=bar' => [
                'https%3A%2F%2Ftypo3.org%3Fid%3D10%26foo%3Dbar',
                'https://typo3.org?id=10&foo=bar',
            ],
        ];
    }

    /**
     * Check if rawUrlEncode works properly.
     *
     * @test
     * @dataProvider stdWrap_rawUrlEncodeDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @return void
     */
    public function stdWrap_rawUrlEncode($expect, $content)
    {
        $this->assertSame($expect,
            $this->subject->stdWrap_rawUrlEncode($content));
    }

    /**
     * Check if stdWrap_replacement works properly.
     *
     * Show:
     *
     * - Delegates to method replacement.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['replacement.'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_replacement()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'replacement' => $this->getUniqueId('not used'),
            'replacement.' => [$this->getUniqueId('replacement.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['replacement'])->getMock();
        $subject
            ->expects($this->once())
            ->method('replacement')
            ->with($content, $conf['replacement.'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_replacement($content, $conf));
    }

    /**
     * Data provider for stdWrap_required.
     *
     * @return array [$expect, $stop, $content]
     */
    public function stdWrap_requiredDataProvider()
    {
        return [
            // empty content
            'empty string is empty' => ['', true, ''],
            'null is empty' => ['', true, null],
            'false is empty' => ['', true, false],

            // non-empty content
            'blank is not empty' => [' ', false, ' '],
            'tab is not empty' => [TAB, false, TAB],
            'linebreak is not empty' => [PHP_EOL, false, PHP_EOL],
            '"0" is not empty' => ['0', false, '0'],
            '0 is not empty' => [0, false, 0],
            '1 is not empty' => [1, false, 1],
            'true is not empty' => [true, false, true],
        ];
    }

    /**
     * Check if stdWrap_required works properly.
     *
     * Show:
     *
     *  - Content is empty if it equals '' after cast to string.
     *  - Empty content triggers a stop of further rendering.
     *  - Returns the content as is or '' for empty content.
     *
     * @test
     * @dataProvider stdWrap_requiredDataProvider
     * @param mixed $expect The expected output.
     * @param bool $stop Expect stop further rendering.
     * @param mixed $content The given input.
     * @return void
     */
    public function stdWrap_required($expect, $stop, $content)
    {
        $subject = $this->subject;
        $subject->_set('stdWrapRecursionLevel', 1);
        $subject->_set('stopRendering', [1 => false]);
        $this->assertSame($expect, $subject->stdWrap_required($content));
        $this->assertSame($stop, $subject->_get('stopRendering')[1]);
    }

    /**
     * Check if stdWrap_round works properly
     *
     * Show:
     *
     * - Delegates to method round.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['round.'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_round()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'round' => $this->getUniqueId('not used'),
            'round.' => [$this->getUniqueId('round.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['round'])->getMock();
        $subject
            ->expects($this->once())
            ->method('round')
            ->with($content, $conf['round.'])
            ->willReturn($return);
        $this->assertSame($return, $subject->stdWrap_round($content, $conf));
    }

    /**
     * Check if stdWrap_setContentToCurrent works properly.
     *
     * @test
     * @return void
     */
    public function stdWrap_setContentToCurrent()
    {
        $content = $this->getUniqueId('content');
        $this->assertNotSame($content, $this->subject->getData('current'));
        $this->assertSame($content,
            $this->subject->stdWrap_setContentToCurrent($content));
        $this->assertSame($content, $this->subject->getData('current'));
    }

    /**
     * Data provider for stdWrap_setCurrent
     *
     * @return array Order input, conf
     */
    public function stdWrap_setCurrentDataProvider()
    {
        return [
            'no conf' => [
                'content',
                [],
            ],
            'empty string' => [
                'content',
                ['setCurrent' => ''],
            ],
            'non-empty string' => [
                'content',
                ['setCurrent' => 'xxx'],
            ],
            'integer null' => [
                'content',
                ['setCurrent' => 0],
            ],
            'integer not null' => [
                'content',
                ['setCurrent' => 1],
            ],
            'boolean true' => [
                'content',
                ['setCurrent' => true],
            ],
            'boolean false' => [
                'content',
                ['setCurrent' => false],
            ],
        ];
    }

    /**
     * Check if stdWrap_setCurrent works properly.
     *
     * @test
     * @dataProvider stdWrap_setCurrentDataProvider
     * @param string $input The input value.
     * @param array $conf Property: setCurrent
     * @return void
     */
    public function stdWrap_setCurrent($input, $conf)
    {
        if (isset($conf['setCurrent'])) {
            $this->assertNotSame($conf['setCurrent'], $this->subject->getData('current'));
        }
        $this->assertSame($input, $this->subject->stdWrap_setCurrent($input, $conf));
        if (isset($conf['setCurrent'])) {
            $this->assertSame($conf['setCurrent'], $this->subject->getData('current'));
        }
    }

    /**
     * Check if stdWrap_space works properly.
     *
     * Show:
     *
     *  - Delegates to method wrapSpace.
     *  - Parameter 1 is $content.
     *  - Parameter 2 is $conf['space'],
     *  - trimmed.
     *  - Parameter 3 is $conf['space.'].
     *  - Returns the return value.
     *
     *  @test
     *  @return void.
     */
    public function stdWrap_space()
    {
        $content = $this->getUniqueId('content');
        $trimmed = $this->getUniqueId('space trimmed');
        $conf = [
            'space' => TAB . ' ' . $trimmed . ' ' . TAB,
            'space.' => [$this->getUniqueId('space.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['wrapSpace'])->getMock();
        $subject
            ->expects($this->once())
            ->method('wrapSpace')
            ->with($content, $trimmed, $conf['space.'])
            ->willReturn($return);
        $this->assertSame($return, $subject->stdWrap_space($content, $conf));
    }

    /**
     * Check if stdWrap_spaceAfter works properly.
     *
     * Show:
     *
     *  - Delegates to method wrapSpace.
     *  - Parameter 1 is $content.
     *  - Parameter 2 is $conf['spaceAfter'],
     *  - trimmed,
     *  - prepended with '|'.
     *  - Parameter 3 is $conf['space.'] !!!
     *  - Returns the return value.
     *
     *  @test
     *  @return void.
     */
    public function stdWrap_spaceAfter()
    {
        $content = $this->getUniqueId('content');
        $trimmed = $this->getUniqueId('spaceAfter trimmed');
        $conf = [
            'spaceAfter' => TAB . ' ' . $trimmed . ' ' . TAB,
            'space.' => [$this->getUniqueId('space.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['wrapSpace'])->getMock();
        $subject
            ->expects($this->once())
            ->method('wrapSpace')
            ->with($content, '|' . $trimmed, $conf['space.'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_spaceAfter($content, $conf));
    }

    /**
     * Check if stdWrap_spaceBefore works properly.
     *
     * Show:
     *
     *  - Delegates to method wrapSpace.
     *  - Parameter 1 is $content.
     *  - Parameter 2 is $conf['spaceBefore'],
     *  - trimmed,
     *  - appended with '|'.
     *  - Parameter 3 is $conf['space.'] !!!
     *  - Returns the return value.
     *
     *  @test
     *  @return void.
     */
    public function stdWrap_spaceBefore()
    {
        $content = $this->getUniqueId('content');
        $trimmed = $this->getUniqueId('spaceBefore trimmed');
        $conf = [
            'spaceBefore' => TAB . ' ' . $trimmed . ' ' . TAB,
            'space.' => [$this->getUniqueId('space.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['wrapSpace'])->getMock();
        $subject
            ->expects($this->once())
            ->method('wrapSpace')
            ->with($content, $trimmed . '|', $conf['space.'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_spaceBefore($content, $conf));
    }

    /**
     * Check if stdWrap_split works properly.
     *
     * Show:
     *
     * - Delegates to method splitObj.
     * - Parameter 1 is $content.
     * - Prameter 2 is $conf['split.'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
     public function stdWrap_split()
     {
         $content = $this->getUniqueId('content');
         $conf = [
             'split' => $this->getUniqueId('not used'),
             'split.' => [$this->getUniqueId('split.')],
         ];
         $return = $this->getUniqueId('return');
         $subject = $this->getMockBuilder(ContentObjectRenderer::class)
             ->setMethods(['splitObj'])->getMock();
         $subject
             ->expects($this->once())
             ->method('splitObj')
             ->with($content, $conf['split.'])
             ->willReturn($return);
         $this->assertSame($return,
             $subject->stdWrap_split($content, $conf));
     }

    /**
     * Check that stdWrap_stdWrap works properly.
     *
     * Show:
     *  - Delegates to method stdWrap.
     *  - Parameter 1 is $content.
     *  - Parameter 2 is $conf['stdWrap.'].
     *  - Returns the return value.
     *
     *  @test
     *  @return void.
     */
    public function stdWrap_stdWrap()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'stdWrap' => $this->getUniqueId('not used'),
            'stdWrap.' => [$this->getUniqueId('stdWrap.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['stdWrap'])->getMock();
        $subject
            ->expects($this->once())
            ->method('stdWrap')
            ->with($content, $conf['stdWrap.'])
            ->willReturn($return);
        $this->assertSame($return, $subject->stdWrap_stdWrap($content, $conf));
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
     * Data provider for stdWrap_strPad.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_strPadDataProvider()
    {
        return [
            'pad string with default settings and length 10' => [
                'Alien     ',
                'Alien',
                [
                    'length' => '10',
                ],
            ],
            'pad string with padWith -= and type left and length 10' => [
                '-=-=-Alien',
                'Alien',
                [
                    'length' => '10',
                    'padWith' => '-=',
                    'type' => 'left',
                ],
            ],
            'pad string with padWith _ and type both and length 10' => [
                '__Alien___',
                'Alien',
                [
                    'length' => '10',
                    'padWith' => '_',
                    'type' => 'both',
                ],
            ],
            'pad string with padWith 0 and type both and length 10' => [
                '00Alien000',
                'Alien',
                [
                    'length' => '10',
                    'padWith' => '0',
                    'type' => 'both',
                ],
            ],
            'pad string with padWith ___ and type both and length 6' => [
                'Alien_',
                'Alien',
                [
                    'length' => '6',
                    'padWith' => '___',
                    'type' => 'both',
                ],
            ],
            'pad string with padWith _ and type both and length 12, using stdWrap for length' => [
                '___Alien____',
                'Alien',
                [
                    'length' => '1',
                    'length.' => [
                        'wrap' => '|2',
                    ],
                    'padWith' => '_',
                    'type' => 'both',
                ],
            ],
            'pad string with padWith _ and type both and length 12, using stdWrap for padWidth' => [
                '-_=Alien-_=-',
                'Alien',
                [
                    'length' => '12',
                    'padWith' => '_',
                    'padWith.' => [
                        'wrap' => '-|=',
                    ],
                    'type' => 'both',
                ],
            ],
            'pad string with padWith _ and type both and length 12, using stdWrap for type' => [
                '_______Alien',
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
            ],
        ];
    }

    /**
     * Check if stdWrap_strPad works properly.
     *
     * @test
     * @dataProvider stdWrap_strPadDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param array $conf The configuration of 'strPad.'.
     * @return void
     */
    public function stdWrap_strPad($expect, $content, $conf)
    {
        $conf = ['strPad.' => $conf];
        $result = $this->subject->stdWrap_strPad($content, $conf);
        $this->assertSame($expect, $result);
    }

    /**
     * Data provider for stdWrap_strftime
     *
     * @return array [$expect, $content, $conf, $now]
     */
    public function stdWrap_strftimeDataProvider()
    {
        // Fictive execution time is 2012-09-01 12:00 in UTC/GMT.
        $now = 1346500800;
        return [
            'given timestamp' => [
                '01-09-2012',
                $now,
                ['strftime' => '%d-%m-%Y'],
                $now
            ],
            'empty string' => [
                '01-09-2012',
                '',
                ['strftime' => '%d-%m-%Y'],
                $now
            ],
            'testing null' => [
                '01-09-2012',
                null,
                ['strftime' => '%d-%m-%Y'],
                $now
            ]
        ];
    }

    /**
     * Check if stdWrap_strftime works properly.
     *
     * @test
     * @dataProvider stdWrap_strftimeDataProvider
     * @param string $expect The expected output.
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     * @param int $now Fictive execution time.
     * @return void
     */
    public function stdWrap_strftime($expect, $content, $conf, $now)
    {
        // Save current timezone and set to UTC to make the system under test
        // behave the same in all server timezone settings
        $timezoneBackup = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $GLOBALS['EXEC_TIME'] = $now;
        $result = $this->subject->stdWrap_strftime($content, $conf);

        // Reset timezone
        date_default_timezone_set($timezoneBackup);

        $this->assertSame($expect, $result);
    }

    /**
     * Test for the stdWrap_stripHtml
     *
     * @test
     */
    public function stdWrap_stripHtml()
    {
        $content = '<html><p>Hello <span class="inline">inline tag<span>!</p><p>Hello!</p></html>';
        $expected = 'Hello inline tag!Hello!';
        $this->assertSame($expected, $this->subject->stdWrap_stripHtml($content));
    }

    /**
     * Data provider for the stdWrap_strtotime test
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_strtotimeDataProvider()
    {
        return [
            'date from content' => [
                1417651200, '2014-12-04',
                ['strtotime' => '1']
            ],
            'manipulation of date from content' => [
                1417996800, '2014-12-04',
                ['strtotime' => '+ 2 weekdays']
            ],
            'date from configuration' => [
                1417651200, '',
                ['strtotime' => '2014-12-04']
            ],
            'manipulation of date from configuration' => [
                1417996800, '',
                ['strtotime' => '2014-12-04 + 2 weekdays']
            ],
            'empty input' => [
                false, '',
                ['strtotime' => '1']
            ],
            'date from content and configuration' => [
                false, '2014-12-04',
                ['strtotime' => '2014-12-05']
            ]
        ];
    }

    /**
     * Check if stdWrap_strtotime works properly.
     *
     * @test
     * @dataProvider stdWrap_strtotimeDataProvider
     * @param int $expect The expected output.
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     * @return void
     */
    public function stdWrap_strtotime($expect, $content, $conf)
    {
        // Set exec_time to a hard timestamp
        $GLOBALS['EXEC_TIME'] = 1417392000;
        // Save current timezone and set to UTC to make the system under test
        // behave the same in all server timezone settings
        $timezoneBackup = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $result = $this->subject->stdWrap_strtotime($content, $conf);

        // Reset timezone
        date_default_timezone_set($timezoneBackup);

        $this->assertEquals($expect, $result);
    }

    /**
     * Check if stdWrap_substring works properly.
     *
     * Show:
     *
     * - Delegates to method substring.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['substring'].
     * - Returns the return value.
     *
     * @test
     * @return void
     */
    public function stdWrap_substring()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'substring' => $this->getUniqueId('substring'),
            'substring.' => $this->getUniqueId('not used'),
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['substring'])->getMock();
        $subject
            ->expects($this->once())
            ->method('substring')
            ->with($content, $conf['substring'])
            ->willReturn($return);
        $this->assertSame($return,
            $subject->stdWrap_substring($content, $conf));
    }

    /**
     * Data provider for stdWrap_trim.
     *
     * @return array [$expect, $content]
     */
    public function stdWrap_trimDataProvider()
    {
        return [
            // string not trimmed
            'empty string' => ['', ''],
            'string without whitespace' => ['xxx', 'xxx'],
            'string with whitespace inside' => [
                'xx ' . TAB . ' xx',
                'xx ' . TAB . ' xx',
            ],
            'string with newlines inside' => [
                'xx ' . PHP_EOL . ' xx',
                'xx ' . PHP_EOL . ' xx',
            ],
            // string trimmed
            'blanks around' => ['xxx', '  xxx  '],
            'tabs around' => ['xxx', TAB . 'xxx' . TAB],
            'newlines around' => ['xxx', PHP_EOL . 'xxx' . PHP_EOL],
            'mixed case' => ['xxx', TAB . ' xxx ' . PHP_EOL],
            // non strings
            'null' => ['', null],
            'false' => ['', false],
            'true' => ['1', true],
            'zero' => ['0', 0],
            'one' => ['1', 1],
            '-1' => ['-1', -1],
            '0.0' => ['0', 0.0],
            '1.0' => ['1', 1.0],
            '-1.0' => ['-1', -1.0],
            '1.1' => ['1.1', 1.1],
        ];
    }

    /**
     * Check that stdWrap_trim works properly.
     *
     * Show:
     *
     *  - the given string is trimmed like PHP trim
     *  - non-strings are casted to strings:
     *    - null => 'null'
     *    - false => ''
     *    - true => '1'
     *    - 0 => '0'
     *    - -1 => '-1'
     *    - 1.0 => '1'
     *    - 1.1 => '1.1'
     *
     * @test
     * @dataProvider stdWrap_trimDataProvider
     * @param string $expected The expected output.
     * @param mixed $content The given content.
     * @return void
     */
    public function stdWrap_trim($expect, $content)
    {
        $result = $this->subject->stdWrap_trim($content);
        $this->assertSame($expect, $result);
    }

    /**
     * Check that stdWrap_typolink works properly.
     *
     * Show:
     *  - Delegates to method typolink.
     *  - Parameter 1 is $content.
     *  - Parameter 2 is $conf['typolink.'].
     *  - Returns the return value.
     *
     *  @test
     *  @return void.
     */
    public function stdWrap_typolink()
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'typolink' => $this->getUniqueId('not used'),
            'typolink.' => [$this->getUniqueId('typolink.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['typolink'])->getMock();
        $subject
            ->expects($this->once())
            ->method('typolink')
            ->with($content, $conf['typolink.'])
            ->willReturn($return);
        $this->assertSame($return, $subject->stdWrap_typolink($content, $conf));
    }

    /**
     * Data provider for stdWrap_wrap
     *
     * @return array Order expected, input, conf
     */
    public function stdWrap_wrapDataProvider()
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap' => '<wrapper>|</wrapper>'],
            ],
            'trims whitespace' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap' => '<wrapper>' . TAB . ' | ' . TAB . '</wrapper>'],
            ],
            'missing pipe puts wrap before' => [
                '<pre>XXX',
                'XXX',
                [
                    'wrap' => '<pre>',
                ],
            ],
            'split char change' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap' => '<wrapper> # </wrapper>',
                    'wrap.' => ['splitChar' => '#'],
                ],
            ],
            'split by pattern' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap' => '<wrapper> ###splitter### </wrapper>',
                    'wrap.' => ['splitChar' => '###splitter###'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_wrap works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Properties: wrap, wrap.splitChar
     * @return void
     * @test
     * @dataProvider stdWrap_wrapDataProvider
     */
    public function stdWrap_wrap($expected, $input, $conf)
    {
        $this->assertSame($expected,
            $this->subject->stdWrap_wrap($input, $conf));
    }

    /**
     * Data provider for stdWrap_wrap2
     *
     * @return array Order expected, input, conf
     */
    public function stdWrap_wrap2DataProvider()
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap2' => '<wrapper>|</wrapper>'],
            ],
            'trims whitespace' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap2' => '<wrapper>' . TAB . ' | ' . TAB . '</wrapper>'],
            ],
            'missing pipe puts wrap2 before' => [
                '<pre>XXX',
                'XXX',
                [
                    'wrap2' => '<pre>',
                ],
            ],
            'split char change' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap2' => '<wrapper> # </wrapper>',
                    'wrap2.' => ['splitChar' => '#'],
                ],
            ],
            'split by pattern' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap2' => '<wrapper> ###splitter### </wrapper>',
                    'wrap2.' => ['splitChar' => '###splitter###'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_wrap2 works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Properties: wrap2, wrap2.splitChar
     * @return void
     * @test
     * @dataProvider stdWrap_wrap2DataProvider
     */
    public function stdWrap_wrap2($expected, $input, $conf)
    {
        $this->assertSame($expected, $this->subject->stdWrap_wrap2($input, $conf));
    }

    /**
     * Data provider for stdWrap_wrap3
     *
     * @return array Order expected, input, conf
     */
    public function stdWrap_wrap3DataProvider()
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap3' => '<wrapper>|</wrapper>'],
            ],
            'trims whitespace' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap3' => '<wrapper>' . TAB . ' | ' . TAB . '</wrapper>'],
            ],
            'missing pipe puts wrap3 before' => [
                '<pre>XXX',
                'XXX',
                [
                    'wrap3' => '<pre>',
                ],
            ],
            'split char change' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap3' => '<wrapper> # </wrapper>',
                    'wrap3.' => ['splitChar' => '#'],
                ],
            ],
            'split by pattern' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap3' => '<wrapper> ###splitter### </wrapper>',
                    'wrap3.' => ['splitChar' => '###splitter###'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_wrap3 works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Properties: wrap3, wrap3.splitChar
     * @return void
     * @test
     * @dataProvider stdWrap_wrap3DataProvider
     */
    public function stdWrap_wrap3($expected, $input, $conf)
    {
        $this->assertSame($expected, $this->subject->stdWrap_wrap3($input, $conf));
    }

    /**
     * Data provider for stdWrap_wrapAlign.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_wrapAlignDataProvider()
    {
        $format = '<div style="text-align:%s;">%s</div>';
        $content = $this->getUniqueId('content');
        $wrapAlign = $this->getUniqueId('wrapAlign');
        $expect = sprintf($format, $wrapAlign, $content);
        return [
            'standard case' => [$expect, $content, $wrapAlign],
            'empty conf' => [$content, $content, null],
            'empty string' => [$content, $content, ''],
            'whitespaced zero string' => [$content, $content, ' 0 '],
        ];
    }

    /**
     * Check if stdWrap_wrapAlign works properly.
     *
     * Show:
     *
     * - Wraps $content with div and style attribute.
     * - The style attribute is taken from $conf['wrapAlign'].
     * - Returns the content as is,
     * - if $conf['wrapAlign'] evals to false after being trimmed.
     *
     * @test
     * @dataProvider stdWrap_wrapAlignDataProvider
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param mixed $wrapAlignConf The given input.
     * @return void
     */
    public function stdWrap_wrapAlign($expect, $content, $wrapAlignConf)
    {
        $conf = [];
        if ($wrapAlignConf !== null) {
            $conf['wrapAlign'] = $wrapAlignConf;
        }
        $this->assertSame($expect,
            $this->subject->stdWrap_wrapAlign($content, $conf));
    }

    /***************************************************************************
    * End of tests of stdWrap in alphabetical order
    ***************************************************************************/
}
