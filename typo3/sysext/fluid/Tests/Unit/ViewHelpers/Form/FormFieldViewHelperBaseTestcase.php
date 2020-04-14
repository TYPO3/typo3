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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

use Prophecy\Argument;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;

/**
 * Test for the Abstract Form view helper
 */
abstract class FormFieldViewHelperBaseTestcase extends ViewHelperBaseTestcase
{
    /**
     * @var ConfigurationManagerInterface
     */
    protected $mockConfigurationManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockConfigurationManager = $this->createMock(ConfigurationManagerInterface::class);
    }

    /**
     * @param ViewHelperInterface $viewHelper
     */
    protected function injectDependenciesIntoViewHelper(ViewHelperInterface $viewHelper)
    {
        $viewHelper->injectConfigurationManager($this->mockConfigurationManager);
        parent::injectDependenciesIntoViewHelper($viewHelper);
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
        $this->viewHelperVariableContainer->get(
            Argument::any(),
            'formObjectName'
        )->willReturn('objectName');
        $this->viewHelperVariableContainer->get(
            Argument::any(),
            'fieldNamePrefix'
        )->willReturn('fieldPrefix');
        $this->viewHelperVariableContainer->get(Argument::any(), 'formFieldNames')->willReturn([]);
        $this->viewHelperVariableContainer->get(
            Argument::any(),
            'formObject'
        )->willReturn($formObject);
        $this->viewHelperVariableContainer->get(
            Argument::any(),
            'renderedHiddenFields'
        )->willReturn([]);
        $this->viewHelperVariableContainer->addOrUpdate(Argument::cetera())->willReturn(null);
    }
}
