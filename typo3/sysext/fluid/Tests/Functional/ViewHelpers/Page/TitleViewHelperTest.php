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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Page;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\PageTitle\RecordTitleProvider;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class TitleViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function titleIsSetViadPageTitleProvider(): void
    {
        $template = '
            <f:page.title>My Custom Page Title</f:page.title>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();

        $pageTitleProvider = $this->get(RecordTitleProvider::class);
        self::assertSame('My Custom Page Title', $pageTitleProvider->getTitle());
    }

    #[Test]
    public function titleIsSetWithVariableContent(): void
    {
        $template = '
            {namespace f=TYPO3\CMS\Fluid\ViewHelpers}
            <f:page.title>{title}</f:page.title>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $context->getVariableProvider()->add('title', 'Dynamic Title from Variable');
        $view = new TemplateView($context);
        $view->render();

        $pageTitleProvider = $this->get(RecordTitleProvider::class);
        self::assertSame('Dynamic Title from Variable', $pageTitleProvider->getTitle());
    }

    #[Test]
    public function emptyTitleDoesNotSetProvider(): void
    {
        $template = '
            <f:page.title></f:page.title>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();

        $pageTitleProvider = $this->get(RecordTitleProvider::class);
        self::assertSame('', $pageTitleProvider->getTitle());
    }

    #[Test]
    public function titleWithWhitespaceIsTrimmed(): void
    {
        $template = '
            <f:page.title>
                Title with Whitespace
            </f:page.title>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();

        $pageTitleProvider = $this->get(RecordTitleProvider::class);
        self::assertSame('Title with Whitespace', trim($pageTitleProvider->getTitle()));
    }
}
