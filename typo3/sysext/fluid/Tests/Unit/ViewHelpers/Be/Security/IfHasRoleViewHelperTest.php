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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Be\Security;

use TYPO3\CMS\Fluid\ViewHelpers\Be\Security\IfHasRoleViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Testcase for be.security.ifHasRole view helper
 */
class IfHasRoleViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Be\Security\IfAuthenticatedViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new \stdClass();
        $GLOBALS['BE_USER']->userGroups = [
            [
                'uid' => 1,
                'title' => 'Editor'
            ],
            [
                'uid' => 2,
                'title' => 'OtherRole'
            ]
        ];
        $this->viewHelper = new IfHasRoleViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function viewHelperRendersThenChildIfBeUserWithSpecifiedRoleIsLoggedIn()
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
    public function viewHelperRendersThenChildIfBeUserWithSpecifiedRoleIdIsLoggedIn()
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
    public function viewHelperRendersElseChildIfBeUserWithSpecifiedRoleIsNotLoggedIn()
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
    public function viewHelperRendersElseChildIfBeUserWithSpecifiedRoleIdIsNotLoggedIn()
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
