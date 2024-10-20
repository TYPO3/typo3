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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Be;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class InfoboxViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function renderCorrectlySetsTagNameAndDefaultAttributes(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:be.infobox title="Message">Content</f:be.infobox>');
        self::assertStringContainsString('<div class="callout-title">Message</div><div class="callout-body">Content</div>', (new TemplateView($context))->render());
    }

    #[Test]
    public function integerAsButtonRenderChildrenRendersTagContent(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:for each="{4711:\'4712\'}" as="i" iteration="iterator" key="k"><f:be.infobox title="{i}">{k}</f:be.infobox></f:for>');
        self::assertStringContainsString('<div class="callout-title">4712</div><div class="callout-body">4711</div>', (new TemplateView($context))->render());
    }
}
