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
use Prophecy\Argument;
use Prophecy\Doubler\Generator\Node\ReturnTypeNode;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\ImportMap;
use TYPO3\CMS\Core\Page\ImportMapFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Tests\Unit\Page\PageRendererFactoryTrait;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\View\AbstractTemplateView;

/**
 * @todo: Review these tests. Many of them test a lot of internal details like "see if stdWrap is called"
 *        and similar and thus heavily rely on mocking. When turned into functional tests with a proper
 *        fixture setup, the tests would shrink significantly, would be far better readable and could
 *        avoid dealing with internal class knowledge.
 */
class FluidTemplateContentObjectTest extends UnitTestCase
{
    use ProphecyTrait;
    use PageRendererFactoryTrait;

    protected bool $resetSingletonInstances = true;

    /**
     * @var FluidTemplateContentObject|MockObject|AccessibleObjectInterface
     */
    protected MockObject $subject;

    /** @var ObjectProphecy<ContentObjectRenderer> */
    protected ObjectProphecy $contentObjectRendererProphecy;

    protected ContentObjectRenderer $contentObjectRenderer;

    /**
     * @var ContentDataProcessor
     */
    protected ContentDataProcessor $contentDataProcessor;

    /**
     * @var StandaloneView|MockObject
     */
    protected MockObject $standaloneView;

    /**
     * @var Request|MockObject
     */
    protected MockObject $request;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $importMapProphecy = $this->prophesize(ImportMap::class);
        $importMapProphecy->render(Argument::type('string'), Argument::type('string'))->willReturn('');
        $importMapFactoryProphecy = $this->prophesize(ImportMapFactory::class);
        $importMapFactoryProphecy->create()->willReturn($importMapProphecy->reveal());
        GeneralUtility::setSingletonInstance(ImportMapFactory::class, $importMapFactoryProphecy->reveal());
        $this->contentDataProcessor = new ContentDataProcessor($this->prophesize(ContainerInterface::class)->reveal());
        $this->contentObjectRendererProphecy = $this->prophesize(ContentObjectRenderer::class);
        $this->contentObjectRenderer = $this->contentObjectRendererProphecy->reveal();
        $this->subject = $this->getAccessibleMock(
            FluidTemplateContentObject::class,
            ['initializeStandaloneViewInstance'],
            [$this->contentDataProcessor]
        );
        $this->subject->setContentObjectRenderer($this->contentObjectRenderer);
        $tsfe = $this->createMock(TypoScriptFrontendController::class);
        $tsfe->tmpl = $this->getMockBuilder(TemplateService::class)
            ->disableOriginalConstructor()
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
        $this->standaloneView->method('getRequest')->willReturn($this->request);
        $this->standaloneView->method('renderSection')->willReturn('');
        $this->subject->_set('view', $this->standaloneView);
    }

    /**
     * @test
     */
    public function constructSetsContentObjectRenderer(): void
    {
        $contentObjectRenderer = new ContentObjectRenderer();
        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer);
        self::assertEquals($contentObjectRenderer, $subject->getContentObjectRenderer());
    }

    /**
     * @test
     */
    public function renderCallsInitializeStandaloneViewInstance(): void
    {
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $this->addMockViewToSubject();
        $this->subject->expects(self::once())->method('initializeStandaloneViewInstance');
        $this->subject->render([]);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForGivenTemplateRootPathsWithStandardWrap(): void
    {
        $configuration = [
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
            ],
        ];
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplateRootPaths(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->setTemplate(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());
        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $subject->render($configuration);

        $contentObjectRenderer->stdWrap('dummyPath', ['wrap' => '|5/'])->shouldHaveBeenCalled();
        $contentObjectRenderer->stdWrap('', ['field' => 'someField'])->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileInView(): void
    {
        $configuration = ['file' => 'EXT:core/bar.html'];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('file', $configuration)->willReturn('EXT:core/bar.html');
        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $standAloneView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:core/bar.html'))->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileByTemplateInView(): void
    {
        $configuration = [
            'template' => 'FILE',
            'template.' => [
                'file' => Environment::getPublicPath() . '/foo/bar.html',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(7);
        $contentObjectRenderer->cObjGetSingle('FILE', ['file' => Environment::getPublicPath() . '/foo/bar.html'], 'template')->willReturn('baz');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $standAloneView->setTemplateSource('baz')->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileByTemplateNameInView(): void
    {
        $configuration = [
            'templateName' => 'foo',
            'templateRootPaths.' => [
                0 => 'dummyPath1/',
                1 => 'dummyPath2/',
            ],
        ];
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('templateName', $configuration)->willReturn('foo');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplateRootPaths(Argument::cetera())->shouldBeCalled();
        $standAloneView->assignMultiple(Argument::cetera())->shouldBeCalled();
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        $standAloneView->render()->willReturn('');
        $standAloneView->getFormat()->willReturn('html');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $standAloneView->setTemplate('foo')->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileByTemplateNameStdWrapInView(): void
    {
        $configuration = [
            'templateName' => 'TEXT',
            'templateName.' => ['value' => 'bar'],
            'templateRootPaths.' => [
                0 => 'dummyPath1/',
                1 => 'dummyPath2/',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('templateName', $configuration)->willReturn('bar');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplateRootPaths(Argument::cetera())->shouldBeCalled();
        $standAloneView->assignMultiple(Argument::cetera())->shouldBeCalled();
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        $standAloneView->render()->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $standAloneView->setTemplate('bar')->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderSetsLayoutRootPathInView(): void
    {
        $configuration = ['layoutRootPath' => 'foo/bar.html'];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('layoutRootPath', $configuration)->willReturn('foo/bar.html');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $standAloneView->setLayoutRootPaths([Environment::getPublicPath() . '/foo/bar.html'])->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapValueForLayoutRootPath(): void
    {
        $configuration = [
            'layoutRootPath' => 'foo',
            'layoutRootPath.' => [
                'bar' => 'baz',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $contentObjectRenderer->stdWrapValue('layoutRootPath', $configuration)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function layoutRootPathsHasStdWrapSupport(): void
    {
        $configuration = [
            'layoutRootPaths.' => [
                10 => 'FILE',
                '10.' => [
                    'file' => 'foo/bar.html',
                ],
                20 => 'foo/bar2.html',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrap('FILE', ['file' => 'foo/bar.html'])->shouldBeCalled();

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setLayoutRootPaths(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $contentObjectRenderer->stdWrap('FILE', ['file' => 'foo/bar.html'])->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function fallbacksForLayoutRootPathAreSet(): void
    {
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects(self::once())
            ->method('setLayoutRootPaths')
            ->with([
                10 => Environment::getPublicPath() . '/foo/bar.html',
                20 => Environment::getPublicPath() . '/foo/bar2.html',
            ]);
        $this->subject->render(['layoutRootPaths.' => [10 => 'foo/bar.html', 20 => 'foo/bar2.html']]);
    }

    /**
     * @test
     */
    public function fallbacksForLayoutRootPathAreAppendedToLayoutRootPath(): void
    {
        $configuration = [
            'layoutRootPath' => 'foo/main.html',
            'layoutRootPaths.' => [10 => 'foo/bar.html', 20 => 'foo/bar2.html'],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('layoutRootPath', $configuration)->willReturn('foo/main.html');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $standAloneView->setLayoutRootPaths([
            0 => Environment::getPublicPath() . '/foo/main.html',
            10 => Environment::getPublicPath() . '/foo/bar.html',
            20 => Environment::getPublicPath() . '/foo/bar2.html',
        ])->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderSetsPartialRootPathInView(): void
    {
        $configuration = ['partialRootPath' => 'foo/bar.html'];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('partialRootPath', $configuration)->willReturn('foo/bar.html');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $standAloneView->setPartialRootPaths([Environment::getPublicPath() . '/foo/bar.html'])->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function partialRootPathsHasStdWrapSupport(): void
    {
        $configuration = [
            'partialRootPaths.' => [
                10 => 'FILE',
                '10.' => [
                    'file' => 'foo/bar.html',
                ],
                20 => 'foo/bar2.html',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setPartialRootPaths(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());
        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $subject->render($configuration);

        $contentObjectRenderer->stdWrap('FILE', ['file' => 'foo/bar.html'])->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapValueForPartialRootPath(): void
    {
        $configuration = [
            'partialRootPath' => 'foo',
            'partialRootPath.' => ['bar' => 'baz'],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setPartialRootPaths(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $contentObjectRenderer->stdWrapValue('partialRootPath', $configuration)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function fallbacksForPartialRootPathAreSet(): void
    {
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
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
        $configuration = [
            'partialRootPath' => 'main',
            'partialRootPaths.' => [10 => 'foo', 20 => 'bar'],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('partialRootPath', $configuration)->willReturn(Environment::getPublicPath() . '/main');

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());
        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $subject->render($configuration);

        $standAloneView->setPartialRootPaths([
                0 => Environment::getPublicPath() . '/main',
                10 => Environment::getPublicPath() . '/foo',
                20 => Environment::getPublicPath() . '/bar',
            ])
            ->willReturn(new ReturnTypeNode('void'))
            ->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderSetsFormatInView(): void
    {
        $configuration = [
            'format' => 'xml',
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('format', $configuration)->willReturn('xml');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $standAloneView->setFormat('xml')->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapValueForFormat(): void
    {
        $configuration = [
            'format' => 'foo',
            'format.' => ['bar' => 'baz'],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $contentObjectRenderer->stdWrapValue('format', $configuration)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderSetsExtbasePluginNameInRequest(): void
    {
        $configuration = [
            'extbase.' => [
                'pluginName' => 'foo',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('pluginName', ['pluginName' => 'foo'])->willReturn('foo');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename('')->shouldBeCalled();
        $standAloneView->assignMultiple(['data' => [], 'current' => null])->shouldBeCalled();
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        $standAloneView->render(Argument::cetera())->shouldBeCalled();

        $request = $this->prophesize(Request::class);
        $standAloneView->getRequest()->willReturn($request->reveal());
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $request->setPluginName('foo')->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapValueForExtbasePluginName(): void
    {
        $configuration = [
            'pluginName' => 'foo',
            'pluginName.' => [
                'bar' => 'baz',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render(['extbase.' => $configuration]);

        $contentObjectRenderer->stdWrapValue('pluginName', $configuration)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderSetsExtbaseControllerExtensionNameInRequest(): void
    {
        $configuration = [
            'extbase.' => [
                'controllerExtensionName' => 'foo',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('controllerExtensionName', ['controllerExtensionName' => 'foo'])->willReturn('foo');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename('')->shouldBeCalled();
        $standAloneView->assignMultiple(['data' => [], 'current' => null])->shouldBeCalled();
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        $standAloneView->render(Argument::cetera())->shouldBeCalled();

        $request = $this->prophesize(Request::class);
        $standAloneView->getRequest()->willReturn($request->reveal());
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $request->setControllerExtensionName('foo')->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapValueForExtbaseControllerExtensionName(): void
    {
        $configuration = [
            'controllerExtensionName' => 'foo',
            'controllerExtensionName.' => [
                'bar' => 'baz',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());
        $subject->render(['extbase.' => $configuration]);

        $contentObjectRenderer->stdWrapValue('controllerExtensionName', $configuration)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderSetsExtbaseControllerNameInRequest(): void
    {
        $configuration = [
            'extbase.' => [
                'controllerName' => 'foo',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('controllerName', ['controllerName' => 'foo'])->willReturn('foo');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename('')->shouldBeCalled();
        $standAloneView->assignMultiple(['data' => [], 'current' => null])->shouldBeCalled();
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        $standAloneView->render(Argument::cetera())->shouldBeCalled();

        $request = $this->prophesize(Request::class);
        $standAloneView->getRequest()->willReturn($request->reveal());
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $request->setControllerName('foo')->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapValueForExtbaseControllerName(): void
    {
        $configuration = [
            'controllerName' => 'foo',
            'controllerName.' => [
                'bar' => 'baz',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render(['extbase.' => $configuration]);

        $contentObjectRenderer->stdWrapValue('controllerName', $configuration)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderSetsExtbaseControllerActionNameInRequest(): void
    {
        $configuration = [
            'extbase.' => [
                'controllerActionName' => 'foo',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->stdWrapValue('controllerActionName', ['controllerActionName' => 'foo'])->willReturn('foo');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename('')->shouldBeCalled();
        $standAloneView->assignMultiple(['data' => [], 'current' => null])->shouldBeCalled();
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        $standAloneView->render(Argument::cetera())->shouldBeCalled();

        $request = $this->prophesize(Request::class);
        $standAloneView->getRequest()->willReturn($request->reveal());
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $request->setControllerActionName('foo')->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForExtbaseControllerActionName(): void
    {
        $configuration = [
            'controllerActionName' => 'foo',
            'controllerActionName.' => [
                'bar' => 'baz',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );

        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render(['extbase.' => $configuration]);

        $contentObjectRenderer->stdWrapValue('controllerActionName', $configuration)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderAssignsSettingsArrayToView(): void
    {
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

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );

        /** @var TypoScriptService|MockObject $typoScriptServiceMock */
        $typoScriptServiceMock = $this->getMockBuilder(TypoScriptService::class)->getMock();
        $typoScriptServiceMock
            ->expects(self::once())
            ->method('convertTypoScriptArrayToPlainArray')
            ->with($configuration['settings.'])
            ->willReturn($expectedSettingsToBeSet);
        GeneralUtility::addInstance(TypoScriptService::class, $typoScriptServiceMock);

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());
        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($this->prophesize(ContentObjectRenderer::class)->reveal());

        $subject->render($configuration);

        $standAloneView->assign('settings', $expectedSettingsToBeSet)->shouldHaveBeenCalled();
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
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );

        $configuration = [
            'variables.' => [
                'aVar' => 'TEXT',
                'aVar.' => [
                    'value' => 'foo',
                ],
            ],
        ];

        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());
        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->assignMultiple(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $contentObjectRenderer->cObjGetSingle('TEXT', ['value' => 'foo'], Argument::any())->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderAssignsRenderedContentObjectVariableToView(): void
    {
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );

        $configuration = [
            'variables.' => [
                'aVar' => 'TEXT',
                'aVar.' => [
                    'value' => 'foo',
                ],
            ],
        ];

        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRenderer->stdWrapValue(Argument::cetera())->shouldBeCalledTimes(8);
        $contentObjectRenderer->cObjGetSingle(Argument::cetera())->willReturn('foo');

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename(Argument::cetera())->willReturn(new ReturnTypeNode('void'));
        $standAloneView->render(Argument::cetera())->willReturn('');
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $standAloneView->assignMultiple(['aVar' => 'foo', 'data' => [], 'current' => null])
            ->willReturn(new ReturnTypeNode('void'))
            ->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renderAssignsContentObjectRendererDataToView(): void
    {
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );

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
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );

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
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );

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
        $configuration = [
            'stdWrap.' => [
                'foo' => 'bar',
            ],
        ];

        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );

        $contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);

        $subject = new FluidTemplateContentObject($this->contentDataProcessor);
        $subject->setContentObjectRenderer($contentObjectRenderer->reveal());

        $standAloneView = $this->prophesize(StandaloneView::class);
        $standAloneView->setTemplatePathAndFilename('')->shouldBeCalled();
        $standAloneView->assignMultiple(['data' => [], 'current' => null])->shouldbeCalled();
        $standAloneView->renderSection(Argument::cetera())->willReturn('');
        $standAloneView->render()->willReturn('baz');
        GeneralUtility::addInstance(StandaloneView::class, $standAloneView->reveal());

        $subject->render($configuration);

        $contentObjectRenderer->stdWrap('baz', ['foo' => 'bar'])->shouldHaveBeenCalled();
    }

    /**
     * @param AbstractTemplateView $viewMock
     * @param string|null $expectedHeader
     * @param string|null $expectedFooter
     * @test
     * @dataProvider headerAssetDataProvider
     */
    public function renderFluidTemplateAssetsIntoPageRendererRendersAndAttachesAssets(
        AbstractTemplateView $viewMock,
        ?string $expectedHeader,
        ?string $expectedFooter
    ): void {
        $pageRendererMock = $this->getMockBuilder(PageRenderer::class)
            ->setConstructorArgs($this->getPageRendererConstructorArgs())
            ->onlyMethods([
                'addHeaderData',
                'addFooterData',
            ])
            ->getMock();
        if ($expectedHeader !== null && !empty(trim($expectedHeader))) {
            $pageRendererMock->expects(self::once())->method('addHeaderData')->with($expectedHeader);
        } else {
            $pageRendererMock->expects(self::never())->method('addHeaderData');
        }
        if ($expectedFooter !== null && !empty(trim($expectedFooter))) {
            $pageRendererMock->expects(self::once())->method('addFooterData')->with($expectedFooter);
        } else {
            $pageRendererMock->expects(self::never())->method('addFooterData');
        }

        $subject = $this->getMockBuilder(FluidTemplateContentObject::class)->onlyMethods(['getPageRenderer'])->disableOriginalConstructor()->getMock();
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
        $viewWithHeaderData = $this->getMockBuilder(AbstractTemplateView::class)->onlyMethods(['renderSection'])->disableOriginalConstructor()->getMock();
        $viewWithHeaderData->expects(self::exactly(2))->method('renderSection')
            ->withConsecutive(
                [
                    'HeaderAssets',
                    self::anything(),
                    true,
                ],
                [
                    'FooterAssets',
                    self::anything(),
                    true,
                ]
            )->willReturnOnConsecutiveCalls('custom-header-data', '');
        $viewWithFooterData = $this->getMockBuilder(AbstractTemplateView::class)->onlyMethods(['renderSection'])->disableOriginalConstructor()->getMock();
        $viewWithFooterData->expects(self::exactly(2))->method('renderSection')
            ->withConsecutive(
                [
                    'HeaderAssets',
                    self::anything(),
                    true,
                ],
                [
                    'FooterAssets',
                    self::anything(),
                    true,
                ]
            )->willReturn('', 'custom-footer-data');
        $viewWithBothData = $this->getMockBuilder(AbstractTemplateView::class)->onlyMethods(['renderSection'])->disableOriginalConstructor()->getMock();
        $viewWithBothData->expects(self::exactly(2))->method('renderSection')
            ->withConsecutive(
                [
                    'HeaderAssets',
                    self::anything(),
                    true,
                ],
                [
                    'FooterAssets',
                    self::anything(),
                    true,
                ]
            )->willReturnOnConsecutiveCalls('custom-header-data', 'custom-footer-data');
        return [
            [$viewWithHeaderData, 'custom-header-data', ''],
            [$viewWithFooterData, '', 'custom-footer-data'],
            [$viewWithBothData, 'custom-header-data', 'custom-footer-data'],
        ];
    }
}
