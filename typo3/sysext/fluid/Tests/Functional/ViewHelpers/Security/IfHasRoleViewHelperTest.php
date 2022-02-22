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

class IfHasRoleViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        $context = new Context();
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $user = new FrontendUserAuthentication();
        $user->user['uid'] = 13;
        $user->userGroups = [
            1 => ['uid' => 1, 'title' => 'Editor'],
            2 => ['uid' => 2, 'title' => 'OtherRole'],
        ];
        $context->setAspect('frontend.user', new UserAspect($user, [1, 2]));
    }

    public function renderDataProvider(): array
    {
        return [
            'viewHelperRendersThenChildIfFeUserWithSpecifiedRoleIsLoggedIn' => [
                '<f:security.ifHasRole role="Editor"><f:then>then child</f:then><f:else>else child</f:else></f:security.ifHasRole>',
                'then child',
            ],
            'viewHelperRendersThenChildIfFeUserWithSpecifiedRoleIdIsLoggedIn' => [
                '<f:security.ifHasRole role="1"><f:then>then child</f:then><f:else>else child</f:else></f:security.ifHasRole>',
                'then child',
            ],
            'viewHelperRendersElseChildIfFeUserWithSpecifiedRoleIsNotLoggedIn' => [
                '<f:security.ifHasRole role="NonExistingRole"><f:then>then child</f:then><f:else>else child</f:else></f:security.ifHasRole>',
                'else child',
            ],
            'viewHelperRendersElseChildIfFeUserWithSpecifiedRoleIdIsNotLoggedIn' => [
                '<f:security.ifHasRole role="123"><f:then>then child</f:then><f:else>else child</f:else></f:security.ifHasRole>',
                'else child',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(string $template, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertEquals($expected, (new TemplateView($context))->render());
    }
}
