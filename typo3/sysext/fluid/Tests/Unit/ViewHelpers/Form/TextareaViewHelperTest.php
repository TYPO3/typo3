<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

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
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for the "Textarea" Form view helper
 */
class TextareaViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Form\TextareaViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\TextareaViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
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
        $mockTagBuilder->expects($this->once())->method('setTagName')->with('textarea');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsNameAttributeAndContent()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['addAttribute', 'setContent', 'render'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('name', 'NameOfTextarea');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextarea');
        $mockTagBuilder->expects($this->once())->method('setContent')->with('Current value');
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextarea',
            'value' => 'Current value'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderEscapesTextareaContent()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['addAttribute', 'setContent', 'render'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('name', 'NameOfTextarea');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextarea');
        $mockTagBuilder->expects($this->once())->method('setContent')->with('some &lt;tag&gt; &amp; &quot;quotes&quot;');
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextarea',
            'value' => 'some <tag> & "quotes"'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAddsPlaceholder()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['addAttribute', 'setContent', 'render'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockTagBuilder->expects($this->at(0))->method('addAttribute')->with('placeholder', 'SomePlaceholder');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('name', 'NameOfTextarea');
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextarea',
            'placeholder' => 'SomePlaceholder'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
