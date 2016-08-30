<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for RenderViewHelper
 */
class RenderViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\RenderViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->templateVariableContainer = new \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer();
        $this->renderingContext->injectTemplateVariableContainer($this->templateVariableContainer);
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\RenderViewHelper::class, ['dummy']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function loadSettingsIntoArgumentsSetsSettingsIfNoSettingsAreSpecified()
    {
        $arguments = [
            'someArgument' => 'someValue'
        ];
        $expected = [
            'someArgument' => 'someValue',
            'settings' => 'theSettings'
        ];
        $this->templateVariableContainer->add('settings', 'theSettings');

        $actual = $this->viewHelper->_call('loadSettingsIntoArguments', $arguments, $this->renderingContext);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function loadSettingsIntoArgumentsDoesNotOverrideGivenSettings()
    {
        $arguments = [
            'someArgument' => 'someValue',
            'settings' => 'specifiedSettings'
        ];
        $expected = [
            'someArgument' => 'someValue',
            'settings' => 'specifiedSettings'
        ];
        $this->templateVariableContainer->add('settings', 'theSettings');

        $actual = $this->viewHelper->_call('loadSettingsIntoArguments', $arguments, $this->renderingContext);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function loadSettingsIntoArgumentsDoesNotThrowExceptionIfSettingsAreNotInTemplateVariableContainer()
    {
        $arguments = [
            'someArgument' => 'someValue'
        ];
        $expected = [
            'someArgument' => 'someValue'
        ];

        $actual = $this->viewHelper->_call('loadSettingsIntoArguments', $arguments, $this->renderingContext);
        $this->assertEquals($expected, $actual);
    }
}
