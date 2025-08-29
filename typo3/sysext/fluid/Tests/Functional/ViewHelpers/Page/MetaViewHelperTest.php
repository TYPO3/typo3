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
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class MetaViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function basicMetaTagIsSet(): void
    {
        $template = '
            <f:page.meta property="description">My custom page description</f:page.meta>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();

        $metaTagManagerRegistry = $this->get(MetaTagManagerRegistry::class);
        $metaTagManager = $metaTagManagerRegistry->getManagerForProperty('description');
        $properties = $metaTagManager->getProperty('description', 'name');

        self::assertCount(1, $properties);
        self::assertSame('My custom page description', $properties[0]['content']);
    }

    #[Test]
    public function openGraphMetaTagIsSet(): void
    {
        $template = '
            <f:page.meta property="og:title">My Article Title</f:page.meta>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();

        $metaTagManagerRegistry = $this->get(MetaTagManagerRegistry::class);
        $metaTagManager = $metaTagManagerRegistry->getManagerForProperty('og:title');
        $properties = $metaTagManager->getProperty('og:title', 'name');

        self::assertCount(1, $properties);
        self::assertSame('My Article Title', $properties[0]['content']);
    }

    #[Test]
    public function metaTagWithVariableContent(): void
    {
        $template = '
            {namespace f=TYPO3\CMS\Fluid\ViewHelpers}
            <f:page.meta property="description">{pageDescription}</f:page.meta>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $context->getVariableProvider()->add('pageDescription', 'Dynamic description from variable');
        $view = new TemplateView($context);
        $view->render();

        $metaTagManagerRegistry = $this->get(MetaTagManagerRegistry::class);
        $metaTagManager = $metaTagManagerRegistry->getManagerForProperty('description');
        $properties = $metaTagManager->getProperty('description', 'name');

        self::assertCount(1, $properties);
        self::assertSame('Dynamic description from variable', $properties[0]['content']);
    }

    #[Test]
    public function metaTagWithSubProperties(): void
    {
        $template = '
            <f:page.meta property="og:image" subProperties="{width: 1200, height: 630, alt: \'Article image\'}">/path/to/image.jpg</f:page.meta>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();

        $metaTagManagerRegistry = $this->get(MetaTagManagerRegistry::class);
        $metaTagManager = $metaTagManagerRegistry->getManagerForProperty('og:image');
        $properties = $metaTagManager->getProperty('og:image', 'name');

        self::assertCount(1, $properties);
        self::assertSame('/path/to/image.jpg', $properties[0]['content']);
        self::assertSame(['width' => 1200, 'height' => 630, 'alt' => 'Article image'], $properties[0]['subProperties']);
    }

    #[Test]
    public function metaTagWithExplicitType(): void
    {
        $template = '
            <f:page.meta property="author" type="name">John Doe</f:page.meta>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();

        $metaTagManagerRegistry = $this->get(MetaTagManagerRegistry::class);
        $metaTagManager = $metaTagManagerRegistry->getManagerForProperty('author');
        $properties = $metaTagManager->getProperty('author', 'name');

        self::assertCount(1, $properties);
        self::assertSame('John Doe', $properties[0]['content']);
    }

    #[Test]
    public function emptyContentDoesNotSetMetaTag(): void
    {
        $template = '
            <f:page.meta property="description"></f:page.meta>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();

        $metaTagManagerRegistry = $this->get(MetaTagManagerRegistry::class);
        $metaTagManager = $metaTagManagerRegistry->getManagerForProperty('description');
        $properties = $metaTagManager->getProperty('description', 'name');

        self::assertCount(0, $properties);
    }

    #[Test]
    public function replaceExistingMetaTag(): void
    {
        $metaTagManagerRegistry = $this->get(MetaTagManagerRegistry::class);
        $metaTagManager = $metaTagManagerRegistry->getManagerForProperty('description');
        $metaTagManager->addProperty('description', 'Original description');

        $template = '
            <f:page.meta property="description" replace="1">Replaced description</f:page.meta>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();

        $properties = $metaTagManager->getProperty('description', 'name');

        self::assertCount(1, $properties);
        self::assertSame('Replaced description', $properties[0]['content']);
    }

    #[Test]
    public function multipleMetaTagsWithSameProperty(): void
    {
        $template = '
            <f:page.meta property="og:image">keyword1</f:page.meta>
            <f:page.meta property="og:image">keyword2</f:page.meta>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();

        $metaTagManagerRegistry = $this->get(MetaTagManagerRegistry::class);
        $metaTagManager = $metaTagManagerRegistry->getManagerForProperty('og:image');
        $properties = $metaTagManager->getProperty('og:image', 'name');

        self::assertCount(2, $properties);
        self::assertSame('keyword1', $properties[0]['content']);
        self::assertSame('keyword2', $properties[1]['content']);
    }

    #[Test]
    public function twitterCardMetaTag(): void
    {
        $template = '
            <f:page.meta property="twitter:card">summary_large_image</f:page.meta>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();

        $metaTagManagerRegistry = $this->get(MetaTagManagerRegistry::class);
        $metaTagManager = $metaTagManagerRegistry->getManagerForProperty('twitter:card');
        $properties = $metaTagManager->getProperty('twitter:card', 'name');

        self::assertCount(1, $properties);
        self::assertSame('summary_large_image', $properties[0]['content']);
    }
}
