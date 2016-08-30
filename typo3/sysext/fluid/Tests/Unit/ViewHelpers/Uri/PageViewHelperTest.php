<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Uri;

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

use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\CMS\Fluid\ViewHelpers\Uri\PageViewHelper;

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
    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(PageViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderProvidesUriForValidLinkTarget()
    {
        $this->uriBuilder->expects($this->once())->method('build')->will($this->returnValue('index.php'));
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderWillNotProvideUriForNonValidLinkTarget()
    {
        $this->uriBuilder->expects($this->once())->method('build')->will($this->returnValue(null));
        $this->viewHelper->render();
    }
}
