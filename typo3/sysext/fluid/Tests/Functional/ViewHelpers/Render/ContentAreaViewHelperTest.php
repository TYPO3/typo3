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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Render;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\ContentArea;
use TYPO3\CMS\Core\Page\ContentSlideMode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Event\ModifyRenderedContentAreaEvent;
use TYPO3\CMS\Fluid\Event\ModifyRenderedRecordEvent;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ContentAreaViewHelperTest extends FunctionalTestCase
{
    private array $dispatchedEvents = [];

    #[Test]
    public function renderContentAreaEventIsDispatched(): void
    {
        $this->registerEventListeners();

        $contentArea = $this->createContentArea();

        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:render.contentArea contentArea="{contentArea}" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);

        $view = new TemplateView($context);
        $view->assign('contentArea', $contentArea);

        $result = $view->render();

        // Both record contents should be rendered
        self::assertStringContainsString('First content', $result);
        self::assertStringContainsString('Second content', $result);

        // RenderRecordEvent should be dispatched once per record, RenderContentAreaEvent once
        self::assertCount(3, $this->dispatchedEvents);
        self::assertInstanceOf(ModifyRenderedRecordEvent::class, $this->dispatchedEvents[0]);
        self::assertInstanceOf(ModifyRenderedRecordEvent::class, $this->dispatchedEvents[1]);
        self::assertInstanceOf(ModifyRenderedContentAreaEvent::class, $this->dispatchedEvents[2]);
    }

    #[Test]
    public function renderContentAreaInline(): void
    {
        $this->registerEventListeners();

        $contentArea = $this->createContentArea();

        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('{contentArea -> f:render.contentArea()}');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);

        $view = new TemplateView($context);
        $view->assign('contentArea', $contentArea);

        $result = $view->render();

        // Both record contents should be rendered
        self::assertStringContainsString('First content', $result);
        self::assertStringContainsString('Second content', $result);

        // RenderRecordEvent should be dispatched once per record, RenderContentAreaEvent once
        self::assertCount(3, $this->dispatchedEvents);
        self::assertInstanceOf(ModifyRenderedRecordEvent::class, $this->dispatchedEvents[0]);
        self::assertInstanceOf(ModifyRenderedRecordEvent::class, $this->dispatchedEvents[1]);
        self::assertInstanceOf(ModifyRenderedContentAreaEvent::class, $this->dispatchedEvents[2]);
    }

    #[Test]
    public function renderContentAreaWithEmptyRecordsDispatchesEvent(): void
    {
        $this->registerEventListeners();

        $contentArea = new ContentArea(
            identifier: 'main',
            name: 'Main',
            colPos: 0,
            slideMode: ContentSlideMode::None,
            allowedContentTypes: [],
            disallowedContentTypes: [],
            configuration: [],
            records: [],
        );

        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:render.contentArea contentArea="{contentArea}" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);

        $view = new TemplateView($context);
        $view->assign('contentArea', $contentArea);

        $view->render();

        // RenderContentAreaEvent should still be dispatched even with empty records
        self::assertCount(1, $this->dispatchedEvents);
        self::assertInstanceOf(ModifyRenderedContentAreaEvent::class, $this->dispatchedEvents[0]);
    }

    private function registerEventListeners(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set('render-record-event-listener-fixture', fn(ModifyRenderedRecordEvent $event) => $this->dispatchedEvents[] = $event);
        $container->set('render-content-area-event-listener-fixture', fn(ModifyRenderedContentAreaEvent $event) => $this->dispatchedEvents[] = $event);

        $listenerProvider = $this->get(ListenerProvider::class);
        $listenerProvider->addListener(ModifyRenderedRecordEvent::class, 'render-record-event-listener-fixture');
        $listenerProvider->addListener(ModifyRenderedContentAreaEvent::class, 'render-content-area-event-listener-fixture');
    }

    private function createRequest(): ServerRequest
    {
        $typoScriptSetup = [
            'tt_content' => 'CASE',
            'tt_content.' => [
                'key.' => [
                    'field' => 'CType',
                ],
                'default' => 'TEXT',
                'default.' => [
                    'field' => 'bodytext',
                ],
            ],
        ];

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupTree(new RootNode());
        $frontendTypoScript->setSetupArray($typoScriptSetup);
        $frontendTypoScript->setConfigArray([]);

        $contentObject = $this->get(ContentObjectRenderer::class);

        return (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('currentContentObject', $contentObject);
    }

    private function createContentArea(): ContentArea
    {
        $record1 = new RawRecord(
            uid: 1,
            pid: 1,
            properties: [
                'CType' => 'text',
                'bodytext' => 'First content',
            ],
            computedProperties: new ComputedProperties(),
            fullType: 'tt_content.text'
        );

        $record2 = new RawRecord(
            uid: 2,
            pid: 1,
            properties: [
                'CType' => 'text',
                'bodytext' => 'Second content',
            ],
            computedProperties: new ComputedProperties(),
            fullType: 'tt_content.text'
        );

        $contentArea = new ContentArea(
            identifier: 'main',
            name: 'Main',
            colPos: 0,
            slideMode: ContentSlideMode::None,
            allowedContentTypes: [],
            disallowedContentTypes: [],
            configuration: [],
            records: [$record1, $record2],
        );
        return $contentArea;
    }
}
