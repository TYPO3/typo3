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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Link;

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

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
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * setUp function
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->uriBuilder = $this->createMock(UriBuilder::class);
        $this->uriBuilder->expects(self::any())->method('reset')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setArguments')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setSection')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setFormat')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setCreateAbsoluteUri')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setLanguage')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setAddQueryString')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setArgumentsToBeExcludedFromQueryString')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setLinkAccessRestrictedPages')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setTargetPageUid')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setTargetPageType')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setNoCache')->willReturn($this->uriBuilder);
        $this->uriBuilder->expects(self::any())->method('setAddQueryStringMethod')->willReturn($this->uriBuilder);

        // reset parent controller context and uri builder @todo: remove once fluid-cleanup is merged in testing framework
        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->expects(self::any())->method('getUriBuilder')->willReturn($this->uriBuilder);
        $this->controllerContext->expects(self::any())->method('getRequest')->willReturn($this->request->reveal());
        $this->arguments = [];
        $this->renderingContext = $this->getMockBuilder(RenderingContext::class)
            ->onlyMethods(['getControllerContext'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->renderingContext->expects(self::any())->method('getControllerContext')->willReturn($this->controllerContext);
        // until here

        $this->renderingContext->setControllerContext($this->controllerContext);

        $this->viewHelper = $this->getAccessibleMock(PageViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
        $this->tagBuilder = $this->createMock(TagBuilder::class);
        $this->viewHelper->_set('tag', $this->tagBuilder);
    }

    /**
     * @test
     */
    public function renderProvidesATagForValidLinkTarget()
    {
        $this->uriBuilder->expects(self::once())->method('build')->willReturn('index.php');
        $this->tagBuilder->expects(self::once())->method('render');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderWillNotProvideATagForNonValidLinkTarget()
    {
        $this->uriBuilder->expects(self::once())->method('build')->willReturn('');
        $this->tagBuilder->expects(self::never())->method('render');
        $this->viewHelper->render();
    }
}
