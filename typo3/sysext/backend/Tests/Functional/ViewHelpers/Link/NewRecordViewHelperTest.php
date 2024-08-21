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

final class NewRecordViewHelperTest extends FunctionalTestCase
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
        $context->getTemplatePaths()->setTemplateSource('<be:link.newRecord table="a_table" pid="17">new record at a_table on page 17</be:link.newRecord>');
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[a_table][17]=new', $result);
    }

    #[Test]
    public function renderReturnsValidLinkForRoot(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.newRecord table="a_table">new record at a_table on root</be:link.newRecord>');
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[a_table][0]=new', $result);
    }

    #[Test]
    public function renderReturnsValidLinkInInlineFormat(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource("{be:link.newRecord(table: 'b_table', pid:17)}");
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[b_table][17]=new', $result);
    }

    #[Test]
    public function renderReturnsValidLinkWithReturnUrl(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.newRecord table="c_table" returnUrl="foo/bar" pid="17">new record at c_table</be:link.newRecord>');
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[c_table][17]=new', $result);
        self::assertStringContainsString('returnUrl=foo/bar', $result);
    }

    #[Test]
    public function renderReturnsValidLinkWithPosition(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.newRecord uid="-11" table="c_table">new record at c_table after record with uid 11</be:link.newRecord>');
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[c_table][-11]=new', $result);
    }

    #[Test]
    public function renderReturnsValidLinkWithDefaultValue(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.newRecord table="c_table" defaultValues="{c_table: {c_field: \'c_value\'}}" pid="17">new record at c_table</be:link.newRecord>');
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[c_table][17]=new', $result);
        self::assertStringContainsString('defVals[c_table][c_field]=c_value', $result);
    }

    #[Test]
    public function renderReturnsValidLinkWithDefaultValues(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.newRecord table="c_table" defaultValues="{c_table: {c_field: \'c_value\', c_field2: \'c_value2\'}}" pid="17">new record at c_table</be:link.newRecord>');
        $result = urldecode((new TemplateView($context))->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[c_table][17]=new', $result);
        self::assertStringContainsString('defVals[c_table][c_field]=c_value&amp;defVals[c_table][c_field2]=c_value2', $result);
    }

    #[Test]
    public function renderThrowsExceptionForInvalidUidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526134901);

        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.newRecord uid="42" table="c_table">if uid given, it must be negative</be:link.newRecord>');
        (new TemplateView($context))->render();
    }

    #[Test]
    public function renderThrowsExceptionForUidAndPid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526129969);

        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.newRecord uid="-42" pid="18" table="c_table">can\'t handle uid and pid together</be:link.newRecord>');
        (new TemplateView($context))->render();
    }
}
