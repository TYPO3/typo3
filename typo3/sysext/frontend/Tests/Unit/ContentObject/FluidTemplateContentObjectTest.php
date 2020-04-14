<?php

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * Testcase
 */
class FluidTemplateContentObjectTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var FluidTemplateContentObject|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var ContentObjectRenderer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contentObjectRenderer;

    /**
     * @var StandaloneView|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $standaloneView;

    /**
     * @var Request|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $request;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->contentObjectRenderer = $this->getMockBuilder(
            ContentObjectRenderer::class
        )->getMock();
        $this->subject = $this->getAccessibleMock(
            FluidTemplateContentObject::class,
            ['initializeStandaloneViewInstance'],
            [$this->contentObjectRenderer]
        );
        /** @var $tsfe TypoScriptFrontendController */
        $tsfe = $this->createMock(TypoScriptFrontendController::class);
        $tsfe->tmpl = $this->getMockBuilder(TemplateService::class)
            ->disableOriginalConstructor(true)
            ->getMock();
        $GLOBALS['TSFE'] = $tsfe;
    }

    /**
     * Add a mock standalone view to subject
     */
    protected function addMockViewToSubject(): void
    {
        $this->standaloneView = $this->createMock(StandaloneView::class);
        $this->request = $this->getMockBuilder(Request::class)->getMock();
        $this->standaloneView
            ->expects(self::any())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->subject->_set('view', $this->standaloneView);
    }

    /**
     * @test
     */
    public function constructSetsContentObjectRenderer(): void
    {
        self::assertSame($this->contentObjectRenderer, $this->subject->getContentObjectRenderer());
    }

    /**
     * @test
     */
    public function renderCallsInitializeStandaloneViewInstance(): void
    {
        $this->addMockViewToSubject();
        $this->subject
            ->expects(self::once())
            ->method('initializeStandaloneViewInstance');
        $this->subject->render([]);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForGivenTemplateFileWithStandardWrap(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects(self::any())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $this->subject->render(['file' => 'foo', 'file.' => ['bar' => 'baz']]);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForGivenTemplateRootPathsWithStandardWrap(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects(self::at(0))
            ->method('stdWrap')
            ->with('dummyPath', ['wrap' => '|5/']);
        $this->contentObjectRenderer
            ->expects(self::at(1))
            ->method('stdWrap')
            ->with('', ['field' => 'someField']);
        $this->subject->render(
            [
                'templateName' => 'foobar',
                'templateRootPaths.' => [
                    10 => 'dummyPath',
                    '10.' => [
                        'wrap' => '|5/',
                    ],
                    15 => 'dummyPath6/',
                    '25.' => [
                        'field' => 'someField',
                    ],
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileInView(): void
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects(self::any())
            ->method('setTemplatePathAndFilename')
            ->with(Environment::getFrameworkBasePath() . '/core/bar.html');
        $this->subject->render(['file' => 'EXT:core/bar.html']);
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileByTemplateInView(): void
    {
        $this->addMockViewToSubject();

        $this->contentObjectRenderer
            ->expects(self::any())
            ->method('cObjGetSingle')
            ->with('FILE', ['file' => Environment::getPublicPath() . '/foo/bar.html'])
            ->willReturn('baz');

        $this->standaloneView
            ->expects(self::any())
            ->method('setTemplateSource')
            ->with('baz');

        $this->subject->render([
            'template' => 'FILE',
            'template.' => [
                'file' => Environment::getPublicPath() . '/foo/bar.html'
            ]
        ]);
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileByTemplateNameInView(): void
    {
        $this->addMockViewToSubject();

        $this->standaloneView
            ->expects(self::any())
            ->method('getFormat')
            ->willReturn('html');
        $this->standaloneView
            ->expects(self::once())
            ->method('setTemplate')
            ->with('foo');

        $this->subject->render(
            [
                'templateName' => 'foo',
                'templateRootPaths.' => [
                    0 => 'dummyPath1/',
                    1 => 'dummyPath2/'
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileByTemplateNameStdWrapInView(): void
    {
        $this->addMockViewToSubject();

        $this->contentObjectRenderer
            ->expects(self::once())
            ->method('stdWrap')
            ->with('TEXT', ['value' => 'bar'])
            ->willReturn('bar');
        $this->standaloneView
            ->expects(self::any())
            ->method('getFormat')
            ->willReturn('html');
        $this->standaloneView
            ->expects(self::once())
            ->method('setTemplate')
            ->with('bar');

        $this->subject->render(
            [
                'templateName' => 'TEXT',
                'templateName.' => ['value' => 'bar'],
                'templateRootPaths.' => [
                    0 => 'dummyPath1/',
                    1 => 'dummyPath2/'
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function renderSetsLayoutRootPathInView(): void
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects(self::once())
            ->method('setLayoutRootPaths')
            ->with([Environment::getPublicPath() . '/foo/bar.html']);
        $this->subject->render(['layoutRootPath' => 'foo/bar.html']);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForLayoutRootPath(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects(self::once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $this->subject->render(['layoutRootPath' => 'foo', 'layoutRootPath.' => ['bar' => 'baz']]);
    }

    /**
     * @test
     */
    public function layoutRootPathsHasStdWrapSupport(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects(self::at(0))
            ->method('stdWrap')
            ->with('FILE', ['file' => 'foo/bar.html']);
        $this->subject->render(
            [
                'layoutRootPaths.' => [
                    10 => 'FILE',
                    '10.' => [
                        'file' => 'foo/bar.html',
                    ],
                    20 => 'foo/bar2.html',
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function fallbacksForLayoutRootPathAreSet(): void
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects(self::once())
            ->method('setLayoutRootPaths')
            ->with([
                10 => Environment::getPublicPath() . '/foo/bar.html',
                20 => Environment::getPublicPath() . '/foo/bar2.html'
            ]);
        $this->subject->render(['layoutRootPaths.' => [10 => 'foo/bar.html', 20 => 'foo/bar2.html']]);
    }

    /**
     * @test
     */
    public function fallbacksForLayoutRootPathAreAppendedToLayoutRootPath(): void
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects(self::once())
            ->method('setLayoutRootPaths')
            ->with([
                0 => Environment::getPublicPath() . '/foo/main.html',
                10 => Environment::getPublicPath() . '/foo/bar.html',
                20 => Environment::getPublicPath() . '/foo/bar2.html'
            ]);
        $this->subject->render([
            'layoutRootPath' => 'foo/main.html',
            'layoutRootPaths.' => [10 => 'foo/bar.html', 20 => 'foo/bar2.html']
        ]);
    }

    /**
     * @test
     */
    public function renderSetsPartialRootPathInView(): void
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects(self::once())
            ->method('setPartialRootPaths')
            ->with([Environment::getPublicPath() . '/foo/bar.html']);
        $this->subject->render(['partialRootPath' => 'foo/bar.html']);
    }

    /**
     * @test
     */
    public function partialRootPathsHasStdWrapSupport(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects(self::at(0))
            ->method('stdWrap')
            ->with('FILE', ['file' => 'foo/bar.html']);
        $this->subject->render(
            [
                'partialRootPaths.' => [
                    10 => 'FILE',
                    '10.' => [
                        'file' => 'foo/bar.html',
                    ],
                    20 => 'foo/bar2.html',
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForPartialRootPath(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects(self::once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $this->subject->render(['partialRootPath' => 'foo', 'partialRootPath.' => ['bar' => 'baz']]);
    }

    /**
     * @test
     */
    public function fallbacksForPartialRootPathAreSet(): void
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects(self::once())
            ->method('setPartialRootPaths')
            ->with([10 => Environment::getPublicPath() . '/foo', 20 => Environment::getPublicPath() . '/bar']);
        $this->subject->render(['partialRootPaths.' => [10 => 'foo', 20 => 'bar']]);
    }

    /**
     * @test
     */
    public function fallbacksForPartialRootPathAreAppendedToPartialRootPath(): void
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects(self::once())
            ->method('setPartialRootPaths')
            ->with([
                0 => Environment::getPublicPath() . '/main',
                10 => Environment::getPublicPath() . '/foo',
                20 => Environment::getPublicPath() . '/bar'
            ]);
        $this->subject->render(['partialRootPath' => 'main', 'partialRootPaths.' => [10 => 'foo', 20 => 'bar']]);
    }

    /**
     * @test
     */
    public function renderSetsFormatInView(): void
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects(self::once())
            ->method('setFormat')
            ->with('xml');
        $this->subject->render(['format' => 'xml']);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForFormat(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects(self::once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $this->subject->render(['format' => 'foo', 'format.' => ['bar' => 'baz']]);
    }

    /**
     * @test
     */
    public function renderSetsExtbasePluginNameInRequest(): void
    {
        $this->addMockViewToSubject();
        $this->request
            ->expects(self::once())
            ->method('setPluginName')
            ->with('foo');
        $configuration = [
            'extbase.' => [
                'pluginName' => 'foo',
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForExtbasePluginName(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects(self::once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $configuration = [
            'extbase.' => [
                'pluginName' => 'foo',
                'pluginName.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderSetsExtbaseControllerExtensionNameInRequest(): void
    {
        $this->addMockViewToSubject();
        $this->request
            ->expects(self::once())
            ->method('setControllerExtensionName')
            ->with('foo');
        $configuration = [
            'extbase.' => [
                'controllerExtensionName' => 'foo',
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForExtbaseControllerExtensionName(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects(self::once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $configuration = [
            'extbase.' => [
                'controllerExtensionName' => 'foo',
                'controllerExtensionName.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderSetsExtbaseControllerNameInRequest(): void
    {
        $this->addMockViewToSubject();
        $this->request
            ->expects(self::once())
            ->method('setControllerName')
            ->with('foo');
        $configuration = [
            'extbase.' => [
                'controllerName' => 'foo',
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForExtbaseControllerName(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects(self::once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $configuration = [
            'extbase.' => [
                'controllerName' => 'foo',
                'controllerName.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderSetsExtbaseControllerActionNameInRequest(): void
    {
        $this->addMockViewToSubject();
        $this->request
            ->expects(self::once())
            ->method('setControllerActionName')
            ->with('foo');
        $configuration = [
            'extbase.' => [
                'controllerActionName' => 'foo',
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForExtbaseControllerActionName(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects(self::once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $configuration = [
            'extbase.' => [
                'controllerActionName' => 'foo',
                'controllerActionName.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderAssignsSettingsArrayToView(): void
    {
        $this->addMockViewToSubject();

        $configuration = [
            'settings.' => [
                'foo' => 'value',
                'bar.' => [
                    'baz' => 'value2',
                ],
            ],
        ];

        $expectedSettingsToBeSet = [
            'foo' => 'value',
            'bar' => [
                'baz' => 'value2',
            ],
        ];

        /** @var TypoScriptService|\PHPUnit\Framework\MockObject\MockObject $typoScriptServiceMock */
        $typoScriptServiceMock = $this->getMockBuilder(TypoScriptService::class)->getMock();
        $typoScriptServiceMock
            ->expects(self::once())
            ->method('convertTypoScriptArrayToPlainArray')
            ->with($configuration['settings.'])
            ->willReturn($expectedSettingsToBeSet);
        GeneralUtility::addInstance(TypoScriptService::class, $typoScriptServiceMock);

        $this->standaloneView
            ->expects(self::at(1))
            ->method('assign')
            ->with('settings', $expectedSettingsToBeSet);

        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionForNotAllowedVariableData(): void
    {
        $this->addMockViewToSubject();
        $configuration = [
            'variables.' => [
                'data' => 'foo',
                'data.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288095720);
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionForNotAllowedVariableCurrent(): void
    {
        $this->addMockViewToSubject();
        $configuration = [
            'variables.' => [
                'current' => 'foo',
                'current.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288095720);
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderCallsCObjGetSingleForAllowedVariable(): void
    {
        $this->addMockViewToSubject();
        $configuration = [
            'variables.' => [
                'aVar' => 'TEXT',
                'aVar.' => [
                    'value' => 'foo',
                ],
            ],
        ];
        $this->contentObjectRenderer
            ->expects(self::once())
            ->method('cObjGetSingle')
            ->with('TEXT', ['value' => 'foo']);
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderAssignsRenderedContentObjectVariableToView(): void
    {
        $this->addMockViewToSubject();
        $configuration = [
            'variables.' => [
                'aVar' => 'TEXT',
                'aVar.' => [
                    'value' => 'foo',
                ],
            ],
        ];
        $this->contentObjectRenderer
            ->expects(self::once())
            ->method('cObjGetSingle')
            ->willReturn('foo');
        $this->standaloneView
            ->expects(self::once())
            ->method('assignMultiple')
            ->with(['aVar' => 'foo', 'data' => [], 'current' => null]);
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderAssignsContentObjectRendererDataToView(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer->data = ['foo'];
        $this->standaloneView
            ->expects(self::once())
            ->method('assignMultiple')
            ->with(['data' => ['foo'], 'current' => null]);
        $this->subject->render([]);
    }

    /**
     * @test
     */
    public function renderAssignsContentObjectRendererCurrentValueToView(): void
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer->data = ['currentKey' => 'currentValue'];
        $this->contentObjectRenderer->currentValKey = 'currentKey';
        $this->standaloneView
            ->expects(self::once())
            ->method('assignMultiple')
            ->with(['data' => ['currentKey' => 'currentValue'], 'current' => 'currentValue']);
        $this->subject->render([]);
    }

    /**
     * @test
     */
    public function renderCallsRenderOnStandaloneView(): void
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects(self::once())
            ->method('render');
        $this->subject->render([]);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapOnResultStringIfGivenInConfiguration(): void
    {
        $this->addMockViewToSubject();
        $configuration = [
            'stdWrap.' => [
                'foo' => 'bar',
            ],
        ];
        $this->standaloneView
            ->expects(self::any())
            ->method('render')
            ->willReturn('baz');
        $this->contentObjectRenderer
            ->expects(self::once())
            ->method('stdWrap')
            ->with('baz', ['foo' => 'bar']);
        $this->subject->render($configuration);
    }

    /**
     * @param TemplateView $viewMock
     * @param string|null $expectedHeader
     * @param string|null $expectedFooter
     * @test
     * @dataProvider headerAssetDataProvider
     */
    public function renderFluidTemplateAssetsIntoPageRendererRendersAndAttachesAssets(
        $viewMock,
        $expectedHeader,
        $expectedFooter
    ): void {
        $pageRendererMock = $this->getMockBuilder(PageRenderer::class)->setMethods([
            'addHeaderData',
            'addFooterData'
        ])->getMock();
        if (!empty(trim($expectedHeader))) {
            $pageRendererMock->expects(self::once())->method('addHeaderData')->with($expectedHeader);
        } else {
            $pageRendererMock->expects(self::never())->method('addHeaderData');
        }
        if (!empty(trim($expectedFooter))) {
            $pageRendererMock->expects(self::once())->method('addFooterData')->with($expectedFooter);
        } else {
            $pageRendererMock->expects(self::never())->method('addFooterData');
        }
        $subject = $this->getMockBuilder(FluidTemplateContentObject::class)->setMethods(['getPageRenderer'])->disableOriginalConstructor()->getMock();
        $subject->expects(self::once())->method('getPageRenderer')->willReturn($pageRendererMock);
        $viewProperty = new \ReflectionProperty($subject, 'view');
        $viewProperty->setAccessible(true);
        $viewProperty->setValue($subject, $viewMock);

        $method = new \ReflectionMethod($subject, 'renderFluidTemplateAssetsIntoPageRenderer');
        $method->setAccessible(true);
        $method->invoke($subject);
    }

    /**
     * @return array
     */
    public function headerAssetDataProvider(): array
    {
        $viewWithHeaderData = $this->getMockBuilder(TemplateView::class)->setMethods(['renderSection'])->disableOriginalConstructor()->getMock();
        $viewWithHeaderData->expects(self::at(0))->method('renderSection')->with(
            'HeaderAssets',
            self::anything(),
            true
        )->willReturn('custom-header-data');
        $viewWithHeaderData->expects(self::at(1))->method('renderSection')->with(
            'FooterAssets',
            self::anything(),
            true
        )->willReturn(null);
        $viewWithFooterData = $this->getMockBuilder(TemplateView::class)->setMethods(['renderSection'])->disableOriginalConstructor()->getMock();
        $viewWithFooterData->expects(self::at(0))->method('renderSection')->with(
            'HeaderAssets',
            self::anything(),
            true
        )->willReturn(null);
        $viewWithFooterData->expects(self::at(1))->method('renderSection')->with(
            'FooterAssets',
            self::anything(),
            true
        )->willReturn('custom-footer-data');
        $viewWithBothData = $this->getMockBuilder(TemplateView::class)->setMethods(['renderSection'])->disableOriginalConstructor()->getMock();
        $viewWithBothData->expects(self::at(0))->method('renderSection')->with(
            'HeaderAssets',
            self::anything(),
            true
        )->willReturn('custom-header-data');
        $viewWithBothData->expects(self::at(1))->method('renderSection')->with(
            'FooterAssets',
            self::anything(),
            true
        )->willReturn('custom-footer-data');
        return [
            [$viewWithHeaderData, 'custom-header-data', null],
            [$viewWithFooterData, null, 'custom-footer-data'],
            [$viewWithBothData, 'custom-header-data', 'custom-footer-data']
        ];
    }
}
