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

use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class PasswordViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function renderCorrectlySetsTagName(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.password />');
        $context->setRequest(new Request());
        self::assertSame('<input type="password" name="" value="" />', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTypeNameAndValueAttributes(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.password name="NameOfTextbox" value="Current value" />');
        $context->setRequest(new Request());
        self::assertSame('<input type="password" name="NameOfTextbox" value="Current value" />', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderCorrectlySetsAutocompleteTagAttribute(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.password name="myNewPassword" value="" autocomplete="new-password" />');
        $context->setRequest(new Request());
        self::assertSame('<input autocomplete="new-password" type="password" name="myNewPassword" value="" />', (new TemplateView($context))->render());
    }
}
