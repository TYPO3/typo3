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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTML;
use TYPO3Fluid\Fluid\View\TemplateView;

final class TextViewHelperTest extends FunctionalTestCase
{
    #[Test]
    public function renderInputField(): void
    {
        $record = $this->createRecord([
            'header' => '<b>Content</b>',
        ]);

        $context = $this->get(RenderingContextFactory::class)->create([], $this->createRequest());
        $context->getTemplatePaths()->setTemplateSource('<f:render.text record="{record}" field="header" />');

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $result = $view->render();
        self::assertInstanceOf(UnsafeHTML::class, $result);

        self::assertStringContainsString('&lt;b&gt;Content&lt;/b&gt;', (string)$result);
        self::assertStringNotContainsString('<b>Content</b>', (string)$result);
    }

    #[Test]
    public function renderInputFieldInline(): void
    {
        $record = $this->createRecord([
            'header' => '<i>Inline</i>',
        ]);

        $context = $this->get(RenderingContextFactory::class)->create([], $this->createRequest());
        $context->getTemplatePaths()->setTemplateSource('{record -> f:render.text(field: "header")}');

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $result = $view->render();
        self::assertInstanceOf(UnsafeHTML::class, $result);

        self::assertStringContainsString('&lt;i&gt;Inline&lt;/i&gt;', (string)$result);
        self::assertStringNotContainsString('<i>Inline</i>', (string)$result);
    }

    #[Test]
    public function renderTextField(): void
    {
        $record = $this->createRecord([
            'rowDescription' => "Line 1\nLine 2",
        ]);

        $context = $this->get(RenderingContextFactory::class)->create([], $this->createRequest());
        $context->getTemplatePaths()->setTemplateSource('<f:render.text record="{record}" field="rowDescription" />');

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $result = $view->render();
        self::assertInstanceOf(UnsafeHTML::class, $result);

        self::assertStringContainsString('Line 1<br />', (string)$result);
        self::assertStringContainsString('Line 2', (string)$result);
    }

    #[Test]
    public function renderRichText(): void
    {
        $record = $this->createRecord([
            'bodytext' => '<p>
    Line <sup>1</sup><br>
    Line 2
</p><script>striped away</script>',
        ]);

        $context = $this->get(RenderingContextFactory::class)->create([], $this->createRequest());
        $context->getTemplatePaths()->setTemplateSource('<f:render.text record="{record}" field="bodytext" />');

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $result = $view->render();
        self::assertInstanceOf(UnsafeHTML::class, $result);

        self::assertStringContainsString('<p>
    Line <sup>1</sup><br>
    Line 2
</p>', (string)$result);
    }

    #[Test]
    public function renderInputFieldFromPageInformation(): void
    {
        $pageInformation = $this->getPageInformation([
            'title' => '<b>My Page</b>',
        ]);

        $context = $this->get(RenderingContextFactory::class)->create([], $this->createRequest());
        $context->getTemplatePaths()->setTemplateSource('<f:render.text record="{record}" field="title" />');

        $view = new TemplateView($context);
        $view->assign('record', $pageInformation);

        $result = $view->render();
        self::assertInstanceOf(UnsafeHTML::class, $result);

        self::assertStringContainsString('&lt;b&gt;My Page&lt;/b&gt;', (string)$result);
        self::assertStringNotContainsString('<b>My Page</b>', (string)$result);
    }

    #[Test]
    public function renderThrowsForNonStringField(): void
    {
        $record = $this->createRecord([
            'bodytext' => ['not-a-string'],
        ]);

        $context = $this->get(RenderingContextFactory::class)->create([], $this->createRequest());
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

        $context = $this->get(RenderingContextFactory::class)->create([], $this->createRequest());
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

        $context = $this->get(RenderingContextFactory::class)->create([], $this->createRequest());
        $context->getTemplatePaths()->setTemplateSource('{record -> f:render.text(field: "bodytext")}');

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The record argument must be an instance of');
        $this->expectExceptionCode(1770539910);

        $view->render();
    }

    private function createRequest(): ServerRequest
    {
        $typoScriptSetup = [
            'lib.' => [
                'parseFunc_RTE.' => [
                    'htmlSanitize' => '0',
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

    private function getPageInformation(array $fields): PageInformation
    {
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setPageRecord([
            'uid' => 1,
            'pid' => 0,
            'doktype' => 1,
            'sys_language_uid' => 0,
            'l10n_parent' => 0,
            't3ver_wsid' => 0,
            't3ver_oid' => 0,
            't3ver_state' => 0,
            't3ver_stage' => 0,
            'crdate' => 1770620184,
            'tstamp' => 1770620184,
            'starttime' => 0,
            'endtime' => 0,
            'deleted' => false,
            'hidden' => false,
            'editlock' => false,
            'rowDescription' => '',
            'sorting' => 0,
            'fe_group' => '',
            ...$fields,
        ]);
        return $pageInformation;
    }
}
