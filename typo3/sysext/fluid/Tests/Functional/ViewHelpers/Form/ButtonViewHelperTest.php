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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Form;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ButtonViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function renderCorrectlySetsTagNameAndDefaultAttributes(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.button type="submit">Button Content</f:form.button>');
        self::assertSame('<button type="submit" name="" value="">Button Content</button>', (new TemplateView($context))->render());
    }

    #[Test]
    public function closingTagIsEnforcedOnEmptyContent(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.button type="reset"></f:form.button>');
        self::assertSame('<button type="reset" name="" value=""></button>', (new TemplateView($context))->render());
    }

    #[Test]
    public function integerAsButtonRenderChildrenRendersTagContent(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:for each="{4711:\'4712\'}" as="i" iteration="iterator" key="k"><f:form.button type="{i}">{k}</f:form.button></f:for>');
        self::assertSame('<button type="4712" name="" value="">4711</button>', (new TemplateView($context))->render());
    }
}
