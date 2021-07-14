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

class IfAuthenticatedViewHelperTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function viewHelperRendersThenChildIfBeUserIsLoggedIn(): void
    {
        $GLOBALS['BE_USER'] = new \stdClass();
        $GLOBALS['BE_USER']->user = ['uid' => 1];
        $view = new StandaloneView();
        $view->setTemplateSource('<f:be.security.ifAuthenticated><f:then>then child</f:then><f:else>else child</f:else></f:be.security.ifAuthenticated>');
        self::assertEquals('then child', $view->render());
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfBeUserIsNotLoggedIn(): void
    {
        $GLOBALS['BE_USER'] = new \stdClass();
        $GLOBALS['BE_USER']->user = ['uid' => 0];
        $view = new StandaloneView();
        $view->setTemplateSource('<f:be.security.ifAuthenticated><f:then>then child</f:then><f:else>else child</f:else></f:be.security.ifAuthenticated>');
        self::assertEquals('else child', $view->render());
    }
}
