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

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectOneSourceCollectionHookInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\ImageContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImageContentObjectTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected ImageContentObject|MockObject|AccessibleObjectInterface $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock();
        $GLOBALS['TSFE'] = $tsfe;
        $contentObjectRenderer = new ContentObjectRenderer($tsfe);
        $this->subject = $this->getAccessibleMock(ImageContentObject::class, null, [
            new MarkerBasedTemplateService(
                new NullFrontend('hash'),
                new NullFrontend('runtime'),
            ),
        ]);
        $this->subject->setContentObjectRenderer($contentObjectRenderer);
        $pageRenderer = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->addMethods(['dummy'])->getMock();
        $this->subject->_set('pageRenderer', $pageRenderer);
    }

    public function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFoundDataProvider(): array
    {
        return [
            [null, null],
            ['', null],
            ['', []],
            ['fooo', ['foo' => 'bar']],
        ];
    }

    /**
     * Make sure that the rendering falls back to the classic <img style if nothing else is found
     *
     * @test
     * @dataProvider getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFoundDataProvider
     */
    public function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFound(?string $key, ?array $configuration): void
    {
        $defaultImgTagTemplate = '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ###SELFCLOSINGTAGSLASH###>';
        $result = $this->subject->_call('getImageTagTemplate', $key, $configuration);
        self::assertEquals($result, $defaultImgTagTemplate);
    }

    public function getImageTagTemplateReturnTemplateElementIdentifiedByKeyDataProvider(): array
    {
        return [
            [
                'foo',
                [
                    'layout.' => [
                        'foo.' => [
                            'element' => '<img src="###SRC###" srcset="###SOURCES###" ###PARAMS### ###ALTPARAMS### ###FOOBAR######SELFCLOSINGTAGSLASH###>',
                        ],
                    ],
                ],
                '<img src="###SRC###" srcset="###SOURCES###" ###PARAMS### ###ALTPARAMS### ###FOOBAR######SELFCLOSINGTAGSLASH###>',
            ],

        ];
    }

    /**
     * Assure if a layoutKey and layout is given the selected layout is returned
     *
     * @test
     * @dataProvider getImageTagTemplateReturnTemplateElementIdentifiedByKeyDataProvider
     */
    public function getImageTagTemplateReturnTemplateElementIdentifiedByKey(string $key, array $configuration, string $expectation): void
    {
        $result = $this->subject->_call('getImageTagTemplate', $key, $configuration);
        self::assertEquals($result, $expectation);
    }

    public function getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefinedDataProvider(): array
    {
        return [
            [null, null, null],
            ['foo', null, null],
            ['foo', ['sourceCollection.' => 1], 'bar'],
        ];
    }

    /**
     * Make sure the source collection is empty if no valid configuration or source collection is defined
     *
     * @test
     * @dataProvider getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefinedDataProvider
     */
    public function getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefined(
        ?string $layoutKey,
        ?array $configuration,
        ?string $file
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
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['stdWrap', 'getImgResource'])
            ->getMock();

        $cObj->start([], 'tt_content');

        $layoutKey = 'test';

        $configuration = [
            'layoutKey' => 'test',
            'layout.' => [
                'test.' => [
                    'element' => '<img ###SRC### ###SRCCOLLECTION### ###SELFCLOSINGTAGSLASH###>',
                    'source' => '---###SRC###---',
                ],
            ],
            'sourceCollection.' => [
                '1.' => [
                    'width' => '200',
                ],
            ],
        ];

        $file = 'testImageName';

        // Avoid calling of stdWrap
        $cObj
            ->method('stdWrap')
            ->willReturnArgument(0);

        // Avoid calling of imgResource
        $cObj
            ->expects(self::once())
            ->method('getImgResource')
            ->with(self::equalTo('testImageName'))
            ->willReturn([100, 100, null, 'bar']);

        $this->subject->setContentObjectRenderer($cObj);
        $result = $this->subject->_call('getImageSourceCollection', $layoutKey, $configuration, $file);

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
            ],
        ];
        return [
            [
                'default',
                [
                    'layoutKey' => 'default',
                    'layout.' => [
                        'default.' => [
                            'element' => '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ##SELFCLOSINGTAGSLASH###>',
                            'source' => '',
                        ],
                    ],
                    'sourceCollection.' => $sourceCollectionArray,
                ],
            ],
        ];
    }

    /**
     * Make sure the generation of subimages renders the expected HTML Code for the sourceset
     *
     * @test
     * @dataProvider getImageSourceCollectionRendersDefinedLayoutKeyDataDefaultProvider
     */
    public function getImageSourceCollectionRendersDefinedLayoutKeyDefault(string $layoutKey, array $configuration): void
    {
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['stdWrap', 'getImgResource'])
            ->getMock();

        $cObj->start([], 'tt_content');

        $file = 'testImageName';

        // Avoid calling of stdWrap
        $cObj
            ->method('stdWrap')
            ->willReturnArgument(0);

        $this->subject->setContentObjectRenderer($cObj);
        $result = $this->subject->_call('getImageSourceCollection', $layoutKey, $configuration, $file);

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
            ],
        ];
        return [
            [
                'srcset',
                [
                    'layoutKey' => 'srcset',
                    'layout.' => [
                        'srcset.' => [
                            'element' => '<img src="###SRC###" srcset="###SOURCECOLLECTION###" ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###>',
                            'source' => '|*|###SRC### ###SRCSETCANDIDATE###,|*|###SRC### ###SRCSETCANDIDATE###',
                        ],
                    ],
                    'sourceCollection.' => $sourceCollectionArray,
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
                            'source' => '<source src="###SRC###" media="###MEDIAQUERY###"###SELFCLOSINGTAGSLASH###>',
                        ],
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
                            'source' => '<source src="###SRC###" media="###MEDIAQUERY###"###SELFCLOSINGTAGSLASH###>',
                        ],
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
                            'source' => 'data-###DATAKEY###="###SRC###"',
                        ],
                    ],
                    'sourceCollection.' => $sourceCollectionArray,
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
     */
    public function getImageSourceCollectionRendersDefinedLayoutKeyData(
        string $layoutKey,
        array $configuration,
        string $xhtmlDoctype,
        string $expectedHtml
    ): void {
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['stdWrap', 'getImgResource'])
            ->getMock();

        $cObj->start([], 'tt_content');

        $file = 'testImageName';
        $pageRenderer = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->addMethods(['dummy'])->getMock();
        $pageRenderer->setLanguage('en');
        $pageRenderer->setDocType(DocType::createFromConfigurationKey($xhtmlDoctype));
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer);

        // Avoid calling of stdWrap
        $cObj
            ->method('stdWrap')
            ->willReturnArgument(0);

        // Avoid calling of imgResource
        $cObj
            ->expects(self::exactly(2))
            ->method('getImgResource')
            ->with(self::equalTo('testImageName'))
            ->willReturn([100, 100, null, 'bar-file.jpg']);

        $subject = $this->getAccessibleMock(ImageContentObject::class, null, [
            new MarkerBasedTemplateService(
                new NullFrontend('hash'),
                new NullFrontend('runtime'),
            ),
        ]);
        $subject->_set('pageRenderer', $pageRenderer);
        $subject->setContentObjectRenderer($cObj);
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
        $cObj
            ->method('stdWrap')
            ->willReturnArgument(0);

        $cObj
            ->method('getImgResource')
            ->willReturn([100, 100, null, 'bar-file.jpg']);

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $cObj->method('getResourceFactory')->willReturn($resourceFactory);

        $className = StringUtility::getUniqueId('tx_coretest_getImageSourceCollectionHookCalled');
        $getImageSourceCollectionHookMock = $this->getMockBuilder(
            ContentObjectOneSourceCollectionHookInterface::class
        )
            ->onlyMethods(['getOneSourceCollection'])
            ->setMockClassName($className)
            ->getMock();
        GeneralUtility::addInstance($className, $getImageSourceCollectionHookMock);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImageSourceCollection'][] = $className;

        $getImageSourceCollectionHookMock
            ->expects(self::once())
            ->method('getOneSourceCollection')
            ->willReturnCallback([$this, 'isGetOneSourceCollectionCalledCallback']);

        $configuration = [
            'layoutKey' => 'data',
            'layout.' => [
                'data.' => [
                    'element' => '<img src="###SRC###" ###SOURCECOLLECTION### ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###>',
                    'source' => 'data-###DATAKEY###="###SRC###"',
                ],
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

        $this->subject->setContentObjectRenderer($cObj);
        $result = $this->subject->_call('getImageSourceCollection', 'data', $configuration, StringUtility::getUniqueId('testImage-'));

        self::assertSame($result, 'isGetOneSourceCollectionCalledCallback');
    }

    /**
     * Handles the arguments that have been sent to the getImgResource hook.
     *
     * @see getImageSourceCollectionHookCalled
     */
    public function isGetOneSourceCollectionCalledCallback(
        array $sourceRenderConfiguration,
        array $sourceConfiguration
    ): string {
        self::assertIsArray($sourceRenderConfiguration);
        self::assertIsArray($sourceConfiguration);
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
                '<tag>|</tag>',
            ],
            'Handles simple text as wrap.' => [
                'alpha' . $content . 'omega',
                $content,
                'alpha|omega',
            ],
            'Trims whitespace around tags.' => [
                '<tag>' . $content . '</tag>',
                $content,
                "\t <tag>\t |\t </tag>\t ",
            ],
            'A wrap without pipe is placed before the content.' => [
                '<tag>' . $content,
                $content,
                '<tag>',
            ],
            'For an empty string as wrap the content is returned as is.' => [
                $content,
                $content,
                '',
            ],
            'For a valid rootline level the uid will be inserted.' => [
                '<a href="?id=55">' . $content . '</a>',
                $content,
                '<a href="?id={3}"> | </a>',
            ],
            'For an invalid rootline level there is no replacement.' => [
                '<a href="?id={4}">' . $content . '</a>',
                $content,
                '<a href="?id={4}"> | </a>',
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
        $GLOBALS['TSFE']->config = ['rootLine' => [3 => ['uid' => 55]]];
        $actual = $this->subject->_call('linkWrap', $content, $wrap);
        self::assertEquals($expected, $actual);
    }
}
