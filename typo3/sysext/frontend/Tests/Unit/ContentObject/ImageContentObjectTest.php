<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectOneSourceCollectionHookInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\ImageContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImageContentObjectTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var ImageContentObject
     */
    protected $subject;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $tsfe->reveal();
        $contentObjectRenderer = new ContentObjectRenderer($tsfe->reveal());
        $this->subject = $this->getAccessibleMock(ImageContentObject::class, ['dummy'], [$contentObjectRenderer]);
    }

    /**
     * @return array
     */
    public function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFoundDataProvider(): array
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
    public function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFound($key, $configuration): void
    {
        $defaultImgTagTemplate = '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ###BORDER######SELFCLOSINGTAGSLASH###>';
        $subject = $this->getAccessibleMock(ImageContentObject::class, ['dummy'], [], '', false);
        $result = $subject->_call('getImageTagTemplate', $key, $configuration);
        self::assertEquals($result, $defaultImgTagTemplate);
    }

    /**
     * @return array
     */
    public function getImageTagTemplateReturnTemplateElementIdentifiedByKeyDataProvider(): array
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
    public function getImageTagTemplateReturnTemplateElementIdentifiedByKey($key, $configuration, $expectation): void
    {
        $result = $this->subject->_call('getImageTagTemplate', $key, $configuration);
        self::assertEquals($result, $expectation);
    }

    /**
     * @return array
     */
    public function getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefinedDataProvider(): array
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
    public function getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefined(
        $layoutKey,
        $configuration,
        $file
    ): void {
        $result = $this->subject->_call('getImageSourceCollection', $layoutKey, $configuration, $file);
        self::assertSame($result, '');
    }

    /**
     * Make sure the generation of subimages calls the generation of the subimages and uses the layout -> source template
     *
     * @test
     */
    public function getImageSourceCollectionRendersDefinedSources(): void
    {
        /** @var $cObj \PHPUnit\Framework\MockObject\MockObject|ContentObjectRenderer */
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
            ->expects(self::any())
            ->method('stdWrap')
            ->willReturnArgument(0);

        // Avoid calling of imgResource
        $cObj
            ->expects(self::exactly(1))
            ->method('getImgResource')
            ->with(self::equalTo('testImageName'))
            ->willReturn([100, 100, null, 'bar']);

        $subject = $this->getAccessibleMock(ImageContentObject::class, ['dummy'], [$cObj]);
        $result = $subject->_call('getImageSourceCollection', $layoutKey, $configuration, $file);

        self::assertEquals('---bar---', $result);
    }

    /**
     * Data provider for the getImageSourceCollectionRendersDefinedLayoutKeyDefault test
     *
     * @return array multi-dimensional array with the second level like this:
     * @see getImageSourceCollectionRendersDefinedLayoutKeyDefault
     */
    public function getImageSourceCollectionRendersDefinedLayoutKeyDataDefaultProvider(): array
    {
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
    public function getImageSourceCollectionRendersDefinedLayoutKeyDefault($layoutKey, $configuration): void
    {
        /** @var $cObj \PHPUnit\Framework\MockObject\MockObject|ContentObjectRenderer */
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['stdWrap', 'getImgResource'])
            ->getMock();

        $cObj->start([], 'tt_content');

        $file = 'testImageName';

        // Avoid calling of stdWrap
        $cObj
            ->expects(self::any())
            ->method('stdWrap')
            ->willReturnArgument(0);

        $subject = $this->getAccessibleMock(ImageContentObject::class, ['dummy'], [$cObj]);
        $result = $subject->_call('getImageSourceCollection', $layoutKey, $configuration, $file);

        self::assertEmpty($result);
    }

    /**
     * Data provider for the getImageSourceCollectionRendersDefinedLayoutKeyData test
     *
     * @return array multi-dimensional array with the second level like this:
     * @see getImageSourceCollectionRendersDefinedLayoutKeyData
     */
    public function getImageSourceCollectionRendersDefinedLayoutKeyDataDataProvider(): array
    {
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
    public function getImageSourceCollectionRendersDefinedLayoutKeyData(
        $layoutKey,
        $configuration,
        $xhtmlDoctype,
        $expectedHtml
    ): void {
        /** @var $cObj \PHPUnit\Framework\MockObject\MockObject|ContentObjectRenderer */
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['stdWrap', 'getImgResource'])
            ->getMock();

        $cObj->start([], 'tt_content');

        $file = 'testImageName';

        $GLOBALS['TSFE']->xhtmlDoctype = $xhtmlDoctype;

        // Avoid calling of stdWrap
        $cObj
            ->expects(self::any())
            ->method('stdWrap')
            ->willReturnArgument(0);

        // Avoid calling of imgResource
        $cObj
            ->expects(self::exactly(2))
            ->method('getImgResource')
            ->with(self::equalTo('testImageName'))
            ->willReturn([100, 100, null, 'bar-file.jpg']);

        $subject = $this->getAccessibleMock(ImageContentObject::class, ['dummy'], [$cObj]);
        $result = $subject->_call('getImageSourceCollection', $layoutKey, $configuration, $file);

        self::assertEquals($expectedHtml, $result);
    }

    /**
     * Make sure the hook in get sourceCollection is called
     *
     * @test
     */
    public function getImageSourceCollectionHookCalled(): void
    {
        $cObj = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            ['getResourceFactory', 'stdWrap', 'getImgResource']
        );
        $cObj->start([], 'tt_content');

        // Avoid calling stdwrap and getImgResource
        $cObj->expects(self::any())
            ->method('stdWrap')
            ->willReturnArgument(0);

        $cObj->expects(self::any())
            ->method('getImgResource')
            ->willReturn([100, 100, null, 'bar-file.jpg']);

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $cObj->expects(self::any())->method('getResourceFactory')->willReturn($resourceFactory);

        $className = StringUtility::getUniqueId('tx_coretest_getImageSourceCollectionHookCalled');
        $getImageSourceCollectionHookMock = $this->getMockBuilder(
            ContentObjectOneSourceCollectionHookInterface::class
        )
            ->setMethods(['getOneSourceCollection'])
            ->setMockClassName($className)
            ->getMock();
        GeneralUtility::addInstance($className, $getImageSourceCollectionHookMock);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImageSourceCollection'][] = $className;

        $getImageSourceCollectionHookMock
            ->expects(self::exactly(1))
            ->method('getOneSourceCollection')
            ->willReturnCallback([$this, 'isGetOneSourceCollectionCalledCallback']);

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

        $subject = $this->getAccessibleMock(ImageContentObject::class, ['dummy'], [$cObj]);
        $result = $subject->_call('getImageSourceCollection', 'data', $configuration, StringUtility::getUniqueId('testImage-'));

        self::assertSame($result, 'isGetOneSourceCollectionCalledCallback');
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
    public function isGetOneSourceCollectionCalledCallback(
        array $sourceRenderConfiguration,
        array $sourceConfiguration,
        $oneSourceCollection,
        $parent
    ): string {
        self::assertTrue(is_array($sourceRenderConfiguration));
        self::assertTrue(is_array($sourceConfiguration));
        return 'isGetOneSourceCollectionCalledCallback';
    }

    /**
     * Data provider for linkWrap
     *
     * @return array [[$expected, $content, $wrap],]
     */
    public function linkWrapDataProvider(): array
    {
        $content = StringUtility::getUniqueId();
        return [
            'Handles a tag as wrap.' => [
                '<tag>' . $content . '</tag>',
                $content,
                '<tag>|</tag>'
            ],
            'Handles simple text as wrap.' => [
                'alpha' . $content . 'omega',
                $content,
                'alpha|omega'
            ],
            'Trims whitespace around tags.' => [
                '<tag>' . $content . '</tag>',
                $content,
                "\t <tag>\t |\t </tag>\t "
            ],
            'A wrap without pipe is placed before the content.' => [
                '<tag>' . $content,
                $content,
                '<tag>'
            ],
            'For an empty string as wrap the content is returned as is.' => [
                $content,
                $content,
                ''
            ],
            'For null as wrap the content is returned as is.' => [
                $content,
                $content,
                null
            ],
            'For a valid rootline level the uid will be inserted.' => [
                '<a href="?id=55">' . $content . '</a>',
                $content,
                '<a href="?id={3}"> | </a>'
            ],
            'For an invalid rootline level there is no replacement.' => [
                '<a href="?id={4}">' . $content . '</a>',
                $content,
                '<a href="?id={4}"> | </a>'
            ],
        ];
    }

    /**
     * Check if linkWrap works properly.
     *
     * @test
     * @dataProvider  linkWrapDataProvider
     * @param string $expected The expected output.
     * @param string $content The parameter $content.
     * @param string|null $wrap The parameter $wrap.
     */
    public function linkWrap(string $expected, string $content, $wrap): void
    {
        $GLOBALS['TSFE']->tmpl = new \stdClass();
        $GLOBALS['TSFE']->tmpl->rootLine = [3 => ['uid' => 55]];
        $actual = $this->subject->_call('linkWrap', $content, $wrap);
        self::assertEquals($expected, $actual);
    }
}
