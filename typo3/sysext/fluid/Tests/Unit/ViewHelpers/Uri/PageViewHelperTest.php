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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Uri;

use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\ViewHelpers\Uri\PageViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test-case for Link\PageViewHelper
 */
class PageViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var PageViewHelper
     */
    protected $viewHelper;

    /**
     * setUp function
     */
    protected function setUp(): void
    {
        parent::setUp();
        $uriBuilder = $this->createMock(UriBuilder::class);
        $this->controllerContext->expects(self::any())->method('getUriBuilder')->willReturn($uriBuilder);
        $this->viewHelper = $this->getAccessibleMock(PageViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderProvidesUriForValidLinkTarget()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'index.php';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            []
        );
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderWillNotProvideUriForNonValidLinkTarget()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return null;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            []
        );
        $this->viewHelper->initializeArgumentsAndRender();
    }
}
