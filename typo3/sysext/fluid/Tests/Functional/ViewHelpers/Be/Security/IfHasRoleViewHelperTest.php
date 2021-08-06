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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Be\Security;

use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class IfHasRoleViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

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
    }

    /**
     * @test
     */
    public function viewHelperRendersThenChildIfBeUserWithSpecifiedRoleIsLoggedIn(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:be.security.ifHasRole role="1"><f:then>then child</f:then><f:else>else child</f:else></f:be.security.ifHasRole>');
        self::assertEquals('then child', $view->render());
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfBeUserWithSpecifiedRoleIsNotLoggedIn(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:be.security.ifHasRole role="NonExistingRole"><f:then>then child</f:then><f:else>else child</f:else></f:be.security.ifHasRole>');
        self::assertEquals('else child', $view->render());
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfBeUserWithSpecifiedRoleIdIsNotLoggedIn(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:be.security.ifHasRole role="123"><f:then>then child</f:then><f:else>else child</f:else></f:be.security.ifHasRole>');
        self::assertEquals('else child', $view->render());
    }
}
