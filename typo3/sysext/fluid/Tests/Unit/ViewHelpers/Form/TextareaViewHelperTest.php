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

use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\EmptySyntaxTreeNode;
use TYPO3\CMS\Fluid\ViewHelpers\Form\TextareaViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for the "Textarea" Form view helper
 */
class TextareaViewHelperTest extends FormFieldViewHelperBaseTestcase
{
    /**
     * @var TextareaViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(TextareaViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagName()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['setTagName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('textarea');
        $this->viewHelper->setTagBuilder($mockTagBuilder);

        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsNameAttributeAndContent()
    {
        $arguments = [
            'name' => 'NameOfTextarea',
            'value' => 'Current value'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $actual = $this->viewHelper->initializeArgumentsAndRender();
        $expected = '<textarea name="NameOfTextarea">Current value</textarea>';
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects(self::once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderEscapesTextareaContent()
    {
        $arguments = [
            'name' => 'NameOfTextarea',
            'value' => 'some <tag> & "quotes"'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $actual = $this->viewHelper->initializeArgumentsAndRender();
        $expected = '<textarea name="NameOfTextarea">some &lt;tag&gt; &amp; &quot;quotes&quot;</textarea>';
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function renderAddsPlaceholder()
    {
        $arguments = [
            'name' => 'NameOfTextarea',
            'placeholder' => 'SomePlaceholder'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $actual = $this->viewHelper->initializeArgumentsAndRender();
        $expected = '<textarea placeholder="SomePlaceholder" name="NameOfTextarea"></textarea>';
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function renderAddsReadonly()
    {
        $arguments = [
            'readonly' => 'foo',
            'name' => 'NameOfTextarea',
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $actual = $this->viewHelper->initializeArgumentsAndRender();
        $expected = '<textarea readonly="foo" name="NameOfTextarea"></textarea>';
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function renderAddsRequired()
    {
        $arguments = [
            'required' => true,
            'name' => 'NameOfTextarea',
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $actual = $this->viewHelper->initializeArgumentsAndRender();
        $expected = '<textarea name="NameOfTextarea" required="required"></textarea>';
        self::assertSame($expected, $actual);
    }
}
