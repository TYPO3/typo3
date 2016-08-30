<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Link;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

use TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper;

/**
 * Test-case for Link\PageViewHelper
 */
class PageViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var PageViewHelper
     */
    protected $viewHelper;

    /**
     * setUp function
     */
    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderProvidesATagForValidLinkTarget()
    {
        $this->uriBuilder->expects($this->once())->method('build')->will($this->returnValue('index.php'));
        $this->tagBuilder->expects($this->once())->method('render');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderWillNotProvideATagForNonValidLinkTarget()
    {
        $this->uriBuilder->expects($this->once())->method('build')->will($this->returnValue(null));
        $this->tagBuilder->expects($this->never())->method('render');
        $this->viewHelper->render();
    }
}
