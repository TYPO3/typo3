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

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class HiddenViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.hidden name="foo" value="bar" />');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context->setRequest(new Request($serverRequest));
        self::assertSame('<input type="hidden" name="foo" value="bar" />', (new TemplateView($context))->render());
    }
}
