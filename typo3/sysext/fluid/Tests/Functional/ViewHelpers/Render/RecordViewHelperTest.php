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
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Event\ModifyRenderedRecordEvent;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class RecordViewHelperTest extends FunctionalTestCase
{
    private array $dispatchedEvents = [];

    #[Test]
    public function renderRecordEventIsDispatched(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set('render-record-event-listener-fixture', fn(ModifyRenderedRecordEvent $event) => $this->dispatchedEvents[] = $event);

        $listenerProvider = $this->get(ListenerProvider::class);
        $listenerProvider->addListener(ModifyRenderedRecordEvent::class, 'render-record-event-listener-fixture');

        $record = new RawRecord(
            uid: 1,
            pid: 1,
            properties: [
                'CType' => 'text',
                'bodytext' => 'Test content',
            ],
            computedProperties: new ComputedProperties(),
            fullType: 'tt_content.text'
        );

        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:render.record record="{record}" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $result = $view->render();

        self::assertStringContainsString('Test content', $result);
        self::assertCount(1, $this->dispatchedEvents);
        self::assertInstanceOf(ModifyRenderedRecordEvent::class, $this->dispatchedEvents[0]);
    }

    #[Test]
    public function renderRecordInline(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set('render-record-event-listener-fixture', fn(ModifyRenderedRecordEvent $event) => $this->dispatchedEvents[] = $event);

        $listenerProvider = $this->get(ListenerProvider::class);
        $listenerProvider->addListener(ModifyRenderedRecordEvent::class, 'render-record-event-listener-fixture');

        $record = new RawRecord(
            uid: 1,
            pid: 1,
            properties: [
                'CType' => 'text',
                'bodytext' => 'Test content',
            ],
            computedProperties: new ComputedProperties(),
            fullType: 'tt_content.text'
        );

        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('{record -> f:render.record()}');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $result = $view->render();

        self::assertStringContainsString('Test content', $result);
        self::assertCount(1, $this->dispatchedEvents);
        self::assertInstanceOf(ModifyRenderedRecordEvent::class, $this->dispatchedEvents[0]);
    }

    #[Test]
    public function renderThrowsForInvalidRecordObject(): void
    {
        $record = new \stdClass();

        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:render.record record="{record}" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The argument "record" was registered with type');
        $this->expectExceptionCode(1256475113);

        $view->render();
    }

    #[Test]
    public function renderThrowsForInvalidRecordObjectInline(): void
    {
        $record = new \stdClass();

        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('{record -> f:render.record()}');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "record" argument must be an instance of');
        $this->expectExceptionCode(1770215699);

        $view->render();
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
}
