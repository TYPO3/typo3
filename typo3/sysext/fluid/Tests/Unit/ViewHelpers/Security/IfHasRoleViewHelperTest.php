<?php

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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Security;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Security\IfHasRoleViewHelper;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Testcase for security.ifHasRole view helper
 */
class IfHasRoleViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Security\IfHasRoleViewHelper
     */
    protected $viewHelper;

    /**
     * @var Context
     */
    protected $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = GeneralUtility::makeInstance(Context::class);
        $user = new FrontendUserAuthentication();
        $user->user['uid'] = 13;
        $user->groupData = [
            'uid' => [1, 2],
            'title' => ['Editor', 'OtherRole']
        ];
        $this->context->setAspect('frontend.user', new UserAspect($user, [1, 2]));
        $this->viewHelper = new IfHasRoleViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    protected function tearDown(): void
    {
        GeneralUtility::removeSingletonInstance(Context::class, $this->context);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function viewHelperRendersThenChildIfFeUserWithSpecifiedRoleIsLoggedIn()
    {
        $actualResult = $this->viewHelper->renderStatic(
            ['role' => 'Editor', 'then' => 'then child', 'else' => 'else child'],
            function () {
            },
            $this->prophesize(RenderingContextInterface::class)->reveal()
        );

        self::assertEquals('then child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersThenChildIfFeUserWithSpecifiedRoleIdIsLoggedIn()
    {
        $actualResult = $this->viewHelper->renderStatic(
            ['role' => 1, 'then' => 'then child', 'else' => 'else child'],
            function () {
            },
            $this->prophesize(RenderingContextInterface::class)->reveal()
        );

        self::assertEquals('then child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfFeUserWithSpecifiedRoleIsNotLoggedIn()
    {
        $actualResult = $this->viewHelper->renderStatic(
            ['role' => 'NonExistingRole', 'then' => 'then child', 'else' => 'else child'],
            function () {
            },
            $this->prophesize(RenderingContextInterface::class)->reveal()
        );

        self::assertEquals('else child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfFeUserWithSpecifiedRoleIdIsNotLoggedIn()
    {
        $actualResult = $this->viewHelper->renderStatic(
            ['role' => 123, 'then' => 'then child', 'else' => 'else child'],
            function () {
            },
            $this->prophesize(RenderingContextInterface::class)->reveal()
        );

        self::assertEquals('else child', $actualResult);
    }
}
