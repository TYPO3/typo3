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
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class FooterDataViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function footerDataIsInsertedAsIsToPageBody(): void
    {
        $template = '
            <f:page.footerData>
                <script>
                    var _paq = window._paq = window._paq || [];
                </script>
            </f:page.footerData>
        ';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->render();
        $pageRenderer = $this->get(PageRenderer::class);
        $pageRenderer->addBodyContent(LF . '<body>');
        $renderedHtml = $pageRenderer->renderResponse()->getBody()->__toString();
        $matches = [];
        preg_match('/<body>(.*?)<\/body>/s', $renderedHtml, $matches);
        $headerPart = $matches[1] ?? '';
        self::assertStringContainsString('var _paq = window._paq = window._paq || [];', $headerPart);
    }
}
