<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

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
use TYPO3\CMS\Fluid\Core\Variables\CmsVariableProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;

/**
 * Base test class for testing view helpers
 */
abstract class ViewHelperBaseTestcase extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer
     */
    protected $viewHelperVariableContainer;

    /**
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer
     */
    protected $templateVariableContainer;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder
     */
    protected $tagBuilder;

    /**
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\Arguments
     */
    protected $arguments;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Request
     */
    protected $request;

    /**
     * @var \TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContext
     */
    protected $renderingContext;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService
     */
    protected $mvcPropertyMapperConfigurationService;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->viewHelperVariableContainer = $this->createMock(\TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer::class);
        $this->templateVariableContainer = $this->createMock(CmsVariableProvider::class);
        $this->uriBuilder = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
        $this->uriBuilder->expects($this->any())->method('reset')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setArguments')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setSection')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setFormat')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setCreateAbsoluteUri')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setAddQueryString')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setArgumentsToBeExcludedFromQueryString')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setLinkAccessRestrictedPages')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setTargetPageUid')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setTargetPageType')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setNoCache')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setUseCacheHash')->will($this->returnValue($this->uriBuilder));
        $this->uriBuilder->expects($this->any())->method('setAddQueryStringMethod')->will($this->returnValue($this->uriBuilder));
        $this->request = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Web\Request::class);
        $this->controllerContext = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class);
        $this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->tagBuilder = $this->createMock(\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder::class);
        $this->arguments = array();
        $this->renderingContext = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture::class, array('getControllerContext'));
        $this->renderingContext->expects($this->any())->method('getControllerContext')->willReturn($this->controllerContext);
        $this->renderingContext->setVariableProvider($this->templateVariableContainer);
        $this->renderingContext->_set('viewHelperVariableContainer', $this->viewHelperVariableContainer);
        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->mvcPropertyMapperConfigurationService = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService::class, array('dummy'));
    }

    /**
     * @param ViewHelperInterface $viewHelper
     * @return void
     */
    protected function injectDependenciesIntoViewHelper(ViewHelperInterface $viewHelper)
    {
        $viewHelper->setRenderingContext($this->renderingContext);
        $viewHelper->setArguments($this->arguments);
        if ($viewHelper instanceof \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper || $viewHelper instanceof AbstractTagBasedViewHelper) {
            $viewHelper->_set('tag', $this->tagBuilder);
        }
    }
}
