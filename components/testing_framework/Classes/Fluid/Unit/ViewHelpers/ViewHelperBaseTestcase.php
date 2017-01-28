<?php
namespace TYPO3\Components\TestingFramework\Fluid\Unit\ViewHelpers;

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
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Fluid\Core\Variables\CmsVariableProvider;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Base test class for testing view helpers
 */
abstract class ViewHelperBaseTestcase extends \TYPO3\Components\TestingFramework\Core\UnitTestCase
{
    /**
     * @var ViewHelperVariableContainer|ObjectProphecy
     */
    protected $viewHelperVariableContainer;

    /**
     * @var CmsVariableProvider
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
     * @var TagBuilder
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
        $this->viewHelperVariableContainer = $this->prophesize(ViewHelperVariableContainer::class);
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
        $this->request = $this->prophesize(\TYPO3\CMS\Extbase\Mvc\Web\Request::class);
        $this->controllerContext = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class);
        $this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request->reveal()));
        $this->arguments = [];
        $this->renderingContext = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture::class, ['getControllerContext']);
        $this->renderingContext->expects($this->any())->method('getControllerContext')->willReturn($this->controllerContext);
        $this->renderingContext->setVariableProvider($this->templateVariableContainer);
        $this->renderingContext->_set('viewHelperVariableContainer', $this->viewHelperVariableContainer->reveal());
        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->mvcPropertyMapperConfigurationService = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService::class, ['dummy']);
    }

    /**
     * @param ViewHelperInterface $viewHelper
     * @return void
     */
    protected function injectDependenciesIntoViewHelper(ViewHelperInterface $viewHelper)
    {
        $viewHelper->setRenderingContext($this->renderingContext);
        $viewHelper->setArguments($this->arguments);
        // this condition is needed, because the (Be)/Security\*ViewHelper don't extend the
        // AbstractViewHelper and contain no method injectReflectionService()
        if ($viewHelper instanceof AbstractViewHelper) {
            $reflectionServiceProphecy = $this->prophesize(ReflectionService::class);
            $viewHelper->injectReflectionService($reflectionServiceProphecy->reveal());
        }
    }

    /**
     * Helper function to merge arguments with default arguments according to their registration
     * This usually happens in ViewHelperInvoker before the view helper methods are called
     *
     * @param ViewHelperInterface $viewHelper
     * @param array $arguments
     */
    protected function setArgumentsUnderTest(ViewHelperInterface $viewHelper, array $arguments = [])
    {
        $argumentDefinitions = $viewHelper->prepareArguments();
        foreach ($argumentDefinitions as $argumentName => $argumentDefinition) {
            if (!isset($arguments[$argumentName])) {
                $arguments[$argumentName] = $argumentDefinition->getDefaultValue();
            }
        }
        $viewHelper->setArguments($arguments);
    }

    /**
     * Helper function for a valid mapping result
     */
    protected function stubRequestWithoutMappingErrors()
    {
        $this->request->getOriginalRequest()->willReturn(null);
        $this->request->getArguments()->willReturn([]);
        $result = $this->prophesize(Result::class);
        $result->forProperty('objectName')->willReturn($result->reveal());
        $result->forProperty('someProperty')->willReturn($result->reveal());
        $result->hasErrors()->willReturn(false);
        $this->request->getOriginalRequestMappingResults()->willReturn($result->reveal());
    }

    /**
     * Helper function for a mapping result with errors
     */
    protected function stubRequestWithMappingErrors()
    {
        $this->request->getOriginalRequest()->willReturn(null);
        $this->request->getArguments()->willReturn([]);
        $result = $this->prophesize(Result::class);
        $result->forProperty('objectName')->willReturn($result->reveal());
        $result->forProperty('someProperty')->willReturn($result->reveal());
        $result->hasErrors()->willReturn(true);
        $this->request->getOriginalRequestMappingResults()->willReturn($result->reveal());
    }

    /**
     * Helper function for the bound property
     *
     * @param $formObject
     */
    protected function stubVariableContainer($formObject)
    {
        $this->viewHelperVariableContainer->exists(Argument::cetera())->willReturn(true);
        $this->viewHelperVariableContainer->get(Argument::any(),
            'formObjectName')->willReturn('objectName');
        $this->viewHelperVariableContainer->get(Argument::any(),
            'fieldNamePrefix')->willReturn('fieldPrefix');
        $this->viewHelperVariableContainer->get(Argument::any(), 'formFieldNames')->willReturn([]);
        $this->viewHelperVariableContainer->get(Argument::any(),
            'formObject')->willReturn($formObject);
        $this->viewHelperVariableContainer->get(Argument::any(),
            'renderedHiddenFields')->willReturn([]);
        $this->viewHelperVariableContainer->addOrUpdate(Argument::cetera())->willReturn(null);
    }
}
