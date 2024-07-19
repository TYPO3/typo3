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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Form\Select;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class OptgroupViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function emptyTagIsRendered(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select.optgroup />');
        self::assertSame('<optgroup />', (new TemplateView($context))->render());
    }

    #[Test]
    public function disabledAttributeIsSet(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select.optgroup disabled="true" />');
        self::assertSame('<optgroup disabled="disabled" />', (new TemplateView($context))->render());
    }

    #[Test]
    public function disabledAttributeIsNotSet(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select.optgroup disabled="false" />');
        self::assertSame('<optgroup />', (new TemplateView($context))->render());
    }
}
