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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\EmptySyntaxTreeNode;
use TYPO3\CMS\Fluid\ViewHelpers\Form\TextfieldViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for the "Textfield" Form view helper
 */
class TextfieldViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var TextfieldViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new TextfieldViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagName()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper
        );

        $expectedResult = '<input type="text" name="" />';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTypeNameAndValueAttributes()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'name' => 'NameOfTextfield',
                'value' => 'Current value',
                'type' => 'text'
            ]
        );

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $expectedResult = '<input type="text" name="NameOfTextfield" value="Current value" />';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper = $this->getAccessibleMock(
            TextfieldViewHelper::class,
            [
                'setErrorClassAttribute',
                'registerFieldNameForFormTokenGeneration'
            ]
        );
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->expects(self::once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAddsPlaceholder()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'name' => 'NameOfTextfield',
                'placeholder' => 'SomePlaceholder',
                'type' => 'text'
            ]
        );

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $expectedResult = '<input placeholder="SomePlaceholder" type="text" name="NameOfTextfield" />';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsRequiredAttribute()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'name' => 'NameOfTextfield',
                'value' => 'Current value',
                'type' => 'text',
                'required' => 'required'
            ]
        );

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $expectedResult = '<input type="text" name="NameOfTextfield" value="Current value" required="required" />';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }
}
