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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Security;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class IfAuthenticatedViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function viewHelperRendersThenChildIfFeUserIsLoggedIn(): void
    {
        $user = new FrontendUserAuthentication();
        $user->user['uid'] = 13;
        $context = new Context();
        $context->setAspect('frontend.user', new UserAspect($user));
        GeneralUtility::setSingletonInstance(Context::class, $context);

        $renderingContext = $this->get(RenderingContextFactory::class)->create();
        $renderingContext->getTemplatePaths()->setTemplateSource('<f:security.ifAuthenticated><f:then>then child</f:then><f:else>else child</f:else></f:security.ifAuthenticated>');
        self::assertEquals('then child', (new TemplateView($renderingContext))->render());
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfFeUserIsNotLoggedIn(): void
    {
        $context = new Context();
        $context->setAspect('frontend.user', new UserAspect());
        GeneralUtility::setSingletonInstance(Context::class, $context);

        $renderingContext = $this->get(RenderingContextFactory::class)->create();
        $renderingContext->getTemplatePaths()->setTemplateSource('<f:security.ifAuthenticated><f:then>then child</f:then><f:else>else child</f:else></f:security.ifAuthenticated>');
        self::assertEquals('else child', (new TemplateView($renderingContext))->render());
    }
}
