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
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTML;
use TYPO3Fluid\Fluid\View\TemplateView;

final class TextViewHelperTest extends FunctionalTestCase
{
    #[Test]
    public function renderEscapesHtmlByDefault(): void
    {
        $record = $this->createRecord([
            'bodytext' => '<b>Content</b>',
        ]);

        $context = $this->get(RenderingContextFactory::class)->create([], new ServerRequest());
        $context->getTemplatePaths()->setTemplateSource('<f:render.text record="{record}" field="bodytext" />');

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $result = $view->render();
        self::assertInstanceOf(UnsafeHTML::class, $result);

        self::assertStringContainsString('&lt;b&gt;Content&lt;/b&gt;', (string)$result);
        self::assertStringNotContainsString('<b>Content</b>', (string)$result);
    }

    #[Test]
    public function renderEscapesHtmlInline(): void
    {
        $record = $this->createRecord([
            'bodytext' => '<i>Inline</i>',
        ]);

        $context = $this->get(RenderingContextFactory::class)->create([], new ServerRequest());
        $context->getTemplatePaths()->setTemplateSource('{record -> f:render.text(field: "bodytext")}');

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $result = $view->render();
        self::assertInstanceOf(UnsafeHTML::class, $result);

        self::assertStringContainsString('&lt;i&gt;Inline&lt;/i&gt;', (string)$result);
        self::assertStringNotContainsString('<i>Inline</i>', (string)$result);
    }

    #[Test]
    public function renderConvertsNewlinesWhenEnabled(): void
    {
        $record = $this->createRecord([
            'bodytext' => "Line 1\nLine 2",
        ]);

        $context = $this->get(RenderingContextFactory::class)->create([], new ServerRequest());
        $context->getTemplatePaths()->setTemplateSource('<f:render.text record="{record}" field="bodytext" allowNewlines="1" />');

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $result = $view->render();
        self::assertInstanceOf(UnsafeHTML::class, $result);

        self::assertStringContainsString('Line 1<br />', (string)$result);
        self::assertStringContainsString('Line 2', (string)$result);
    }

    #[Test]
    public function renderThrowsForNonStringField(): void
    {
        $record = $this->createRecord([
            'bodytext' => ['not-a-string'],
        ]);

        $context = $this->get(RenderingContextFactory::class)->create([], new ServerRequest());
        $context->getTemplatePaths()->setTemplateSource('<f:render.text record="{record}" field="bodytext" />');

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1770321858);

        $view->render();
    }

    #[Test]
    public function renderThrowsForInvalidRecordObject(): void
    {
        $record = new \stdClass();

        $context = $this->get(RenderingContextFactory::class)->create([], new ServerRequest());
        $context->getTemplatePaths()->setTemplateSource('<f:render.text record="{record}" field="bodytext" />');

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

        $context = $this->get(RenderingContextFactory::class)->create([], new ServerRequest());
        $context->getTemplatePaths()->setTemplateSource('{record -> f:render.text(field: "bodytext")}');

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The record argument must be an instance of');
        $this->expectExceptionCode(1770539910);

        $view->render();
    }

    private function createRecord(array $properties): RawRecord
    {
        return new RawRecord(
            uid: 1,
            pid: 1,
            properties: $properties,
            computedProperties: new ComputedProperties(),
            fullType: 'tt_content.text'
        );
    }
}
