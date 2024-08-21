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

namespace TYPO3\CMS\Backend\Tests\Functional\ViewHelpers\Link;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class EditRecordViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected ServerRequest $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''));
    }

    #[Test]
    public function renderReturnsValidLinkInExplicitFormat(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.editRecord uid="42" table="a_table">edit record a_table:42</be:link.editRecord>');
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[a_table][42]=edit', $result);
    }

    #[Test]
    public function renderReturnsValidLinkInInlineFormat(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource("{be:link.editRecord(uid: 21, table: 'b_table')}");
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[b_table][21]=edit', $result);
    }

    #[Test]
    public function renderReturnsValidLinkWithReturnUrl(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.editRecord uid="43" table="c_table" returnUrl="foo/bar">edit record c_table:43</be:link.editRecord>');
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[c_table][43]=edit', $result);
        self::assertStringContainsString('returnUrl=foo/bar', $result);
    }

    #[Test]
    public function renderReturnsValidLinkWithField(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.editRecord uid="43" table="c_table" fields="canonical_url">edit record c_table:42</be:link.editRecord>');
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[c_table][43]=edit', $result);
        self::assertStringContainsString('columnsOnly[c_table][0]=canonical_url', $result);
    }

    #[Test]
    public function renderReturnsValidLinkWithFields(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.editRecord uid="43" table="c_table" fields="canonical_url,title">edit record c_table:42</be:link.editRecord>');
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[c_table][43]=edit', $result);
        self::assertStringContainsString('columnsOnly[c_table][0]=canonical_url', $result);
        self::assertStringContainsString('columnsOnly[c_table][1]=title', $result);
    }

    #[Test]
    public function renderThrowsExceptionForInvalidUidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526127158);

        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.editRecord uid="-42" table="c_table">edit record c_table:-42</be:link.editRecord>');
        (new TemplateView($context))->render();
    }
}
