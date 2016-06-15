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

/**
 * Test for the "Textfield" Form view helper
 */
class TextfieldViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Form\TextfieldViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\TextfieldViewHelper::class, array('setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration'));
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagName()
    {
        $mockTagBuilder = $this->getMockBuilder(\TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder::class)
            ->setMethods(array('setTagName'))
            ->disableOriginalConstructor()
            ->getMock();
        $mockTagBuilder->expects($this->once())->method('setTagName')->with('input');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTypeNameAndValueAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder(\TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder::class)
            ->setMethods(array('addAttribute', 'setContent', 'render'))
            ->disableOriginalConstructor()
            ->getMock();
        $mockTagBuilder->expects($this->at(0))->method('addAttribute')->with('type', 'text');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('name', 'NameOfTextfield');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextfield');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('value', 'Current value');
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $arguments = array(
            'name' => 'NameOfTextfield',
            'value' => 'Current value'
        );
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
    public function renderAddsPlaceholder()
    {
        $mockTagBuilder = $this->getMockBuilder(\TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder::class)
            ->setMethods(array('addAttribute', 'setContent', 'render'))
            ->disableOriginalConstructor()
            ->getMock();
        $mockTagBuilder->expects($this->at(0))->method('addAttribute')->with('placeholder', 'SomePlaceholder');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'text');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'NameOfTextfield');
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $arguments = array(
            'name' => 'NameOfTextfield',
            'placeholder' => 'SomePlaceholder'
        );
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsRequiredAttribute()
    {
        $mockTagBuilder = $this->getMockBuilder(\TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder::class)
            ->setMethods(array('addAttribute', 'setContent', 'render'))
            ->disableOriginalConstructor()
            ->getMock();
        $mockTagBuilder->expects($this->at(0))->method('addAttribute')->with('type', 'text');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('name', 'NameOfTextfield');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextfield');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('value', 'Current value');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('required', 'required');
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $arguments = array(
            'name' => 'NameOfTextfield',
            'value' => 'Current value'
        );
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render(true);
    }
}
