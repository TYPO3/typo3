<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Link;

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
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper::class, array('renderChildren'));
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
