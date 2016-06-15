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
 * Test for the "Button" Form view helper
 */
class ButtonViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Form\ButtonViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\ButtonViewHelper::class, array('renderChildren'));
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder(\TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder::class)
            ->setMethods(array('setTagName', 'addAttribute', 'setContent'))
            ->getMock();
        $mockTagBuilder->expects($this->once())->method('setTagName')->with('button');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'submit');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', '');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', '');
        $mockTagBuilder->expects($this->at(4))->method('setContent')->with('Button Content');

        $this->viewHelper->expects($this->atLeastOnce())->method('renderChildren')->will($this->returnValue('Button Content'));

        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
