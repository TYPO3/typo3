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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Be\Security;

use TYPO3\CMS\Fluid\ViewHelpers\Be\Security\IfAuthenticatedViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Testcase for be.security.ifAuthenticated view helper
 */
class IfAuthenticatedViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var IfAuthenticatedViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new \stdClass();
        $this->viewHelper = new IfAuthenticatedViewHelper();
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
    public function viewHelperRendersThenChildIfBeUserIsLoggedIn()
    {
        $GLOBALS['BE_USER']->user = ['uid' => 1];

        $actualResult = $this->viewHelper->renderStatic(
            ['then' => 'then child', 'else' => 'else child'],
            function () {
            },
            $this->prophesize(RenderingContextInterface::class)->reveal()
        );

        self::assertEquals('then child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfBeUserIsNotLoggedIn()
    {
        $GLOBALS['BE_USER']->user = ['uid' => 0];

        $actualResult = $this->viewHelper->renderStatic(
            ['then' => 'then child', 'else' => 'else child'],
            function () {
            },
            $this->prophesize(RenderingContextInterface::class)->reveal()
        );

        self::assertEquals('else child', $actualResult);
    }
}
