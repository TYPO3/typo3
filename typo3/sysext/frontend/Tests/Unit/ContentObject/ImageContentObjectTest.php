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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\ImageResource;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Event\ModifyImageSourceCollectionEvent;
use TYPO3\CMS\Frontend\ContentObject\ImageContentObject;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ImageContentObjectTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected ImageContentObject&MockObject&AccessibleObjectInterface $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $contentObjectRenderer = new ContentObjectRenderer();

        $container = new Container();
        $container->set(EventDispatcherInterface::class, new NoopEventDispatcher());
        GeneralUtility::setContainer($container);
        $this->subject = $this->getAccessibleMock(ImageContentObject::class, null, [
            new MarkerBasedTemplateService(
                new NullFrontend('hash'),
                new NullFrontend('runtime'),
            ),
        ]);
        $this->subject->setRequest(new ServerRequest());
        $this->subject->setContentObjectRenderer($contentObjectRenderer);
        $pageRenderer = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $this->subject->_set('pageRenderer', $pageRenderer);
    }

    public static function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFoundDataProvider(): array
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
     */
    #[DataProvider('getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFoundDataProvider')]
    #[Test]
    public function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFound(?string $key, ?array $configuration): void
    {
        $defaultImgTagTemplate = '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ###SELFCLOSINGTAGSLASH###>';
        $result = $this->subject->_call('getImageTagTemplate', $key, $configuration);
        self::assertEquals($result, $defaultImgTagTemplate);
    }

    public static function getImageTagTemplateReturnTemplateElementIdentifiedByKeyDataProvider(): array
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
     */
    #[DataProvider('getImageTagTemplateReturnTemplateElementIdentifiedByKeyDataProvider')]
    #[Test]
    public function getImageTagTemplateReturnTemplateElementIdentifiedByKey(string $key, array $configuration, string $expectation): void
    {
        $result = $this->subject->_call('getImageTagTemplate', $key, $configuration);
        self::assertEquals($result, $expectation);
    }

    public static function getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefinedDataProvider(): array
    {
        return [
            ['foo', [], null],
            ['foo', ['sourceCollection.' => 1], 'bar'],
        ];
    }

    /**
     * Make sure the source collection is empty if no valid configuration or source collection is defined
     */
    #[DataProvider('getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefinedDataProvider')]
    #[Test]
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
     */
    #[Test]
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
            ->expects($this->once())
            ->method('getImgResource')
            ->with(self::equalTo('testImageName'))
            ->willReturn(new ImageResource(100, 100, '', 'bar', 'bar'));

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
    public static function getImageSourceCollectionRendersDefinedLayoutKeyDataDefaultProvider(): array
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
     */
    #[DataProvider('getImageSourceCollectionRendersDefinedLayoutKeyDataDefaultProvider')]
    #[Test]
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
    public static function getImageSourceCollectionRendersDefinedLayoutKeyDataDataProvider(): array
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
     */
    #[DataProvider('getImageSourceCollectionRendersDefinedLayoutKeyDataDataProvider')]
    #[Test]
    public function getImageSourceCollectionRendersDefinedLayoutKeyData(
        string $layoutKey,
        array $configuration,
        string $doctype,
        string $expectedHtml
    ): void {
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['stdWrap', 'getImgResource'])
            ->getMock();

        $cObj->start([], 'tt_content');

        $file = 'testImageName';
        $pageRenderer = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $pageRenderer->setLanguage(new Locale());
        $pageRenderer->setDocType(DocType::createFromConfigurationKey($doctype));
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer);

        // Avoid calling of stdWrap
        $cObj
            ->method('stdWrap')
            ->willReturnArgument(0);

        // Avoid calling of imgResource
        $cObj
            ->expects($this->exactly(2))
            ->method('getImgResource')
            ->with(self::equalTo('testImageName'))
            ->willReturn(new ImageResource(100, 100, '', 'bar-file.jpg', 'bar-file.jpg'));

        $subject = $this->getAccessibleMock(ImageContentObject::class, null, [
            new MarkerBasedTemplateService(
                new NullFrontend('hash'),
                new NullFrontend('runtime'),
            ),
        ]);
        $subject->_set('pageRenderer', $pageRenderer);
        $subject->setRequest(new ServerRequest());
        $subject->setContentObjectRenderer($cObj);
        $result = $subject->_call('getImageSourceCollection', $layoutKey, $configuration, $file);

        self::assertEquals($expectedHtml, $result);
    }

    /**
     * Make sure the PSR-14 Event in get sourceCollection is called
     */
    #[Test]
    public function modifyImageSourceCollectionEventIsCalled(): void
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
            ->willReturn(new ImageResource(100, 100, '', 'bar-file.jpg', 'bar-file.jpg'));

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $cObj->method('getResourceFactory')->willReturn($resourceFactory);

        $modifyImageSourceCollectionEvent = null;

        /** @var Container $container */
        $container = GeneralUtility::getContainer();

        $container->set(
            'modify-image-source-collection-listener',
            static function (ModifyImageSourceCollectionEvent $event) use (&$modifyImageSourceCollectionEvent) {
                $modifyImageSourceCollectionEvent = $event;
                $event->setSourceCollection('---modified-source-collection---');
            }
        );

        $listenerProvider = new ListenerProvider($container);
        $listenerProvider->addListener(ModifyImageSourceCollectionEvent::class, 'modify-image-source-collection-listener');
        $container->set(ListenerProvider::class, $listenerProvider);
        $container->set(EventDispatcherInterface::class, new EventDispatcher($listenerProvider));

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

        self::assertEquals('---modified-source-collection---', $result);

        self::assertInstanceOf(ModifyImageSourceCollectionEvent::class, $modifyImageSourceCollectionEvent);
        self::assertEquals('---modified-source-collection---', $modifyImageSourceCollectionEvent->getSourceCollection());
        self::assertEquals('', $modifyImageSourceCollectionEvent->getFullSourceCollection());
        self::assertEquals('(max-device-width: 600px)', $modifyImageSourceCollectionEvent->getSourceConfiguration()['mediaQuery']);
        self::assertStringStartsWith('testImage-', $modifyImageSourceCollectionEvent->getSourceRenderConfiguration()['file']);
        self::assertEquals($cObj, $modifyImageSourceCollectionEvent->getContentObjectRenderer());
    }

    /**
     * Data provider for linkWrap
     *
     * @return array [[$expected, $content, $wrap],]
     */
    public static function linkWrapDataProvider(): array
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
     * @param string $expected The expected output.
     * @param string $content The parameter $content.
     * @param string|null $wrap The parameter $wrap.
     */
    #[DataProvider('linkWrapDataProvider')]
    #[Test]
    public function linkWrap(string $expected, string $content, $wrap): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setLocalRootLine([3 => ['uid' => 55]]);
        $request = (new ServerRequest())->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $actual = $this->subject->_call('linkWrap', $content, $wrap);
        self::assertEquals($expected, $actual);
    }
}
