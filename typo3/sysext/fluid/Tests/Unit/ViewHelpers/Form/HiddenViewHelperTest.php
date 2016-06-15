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
use TYPO3\CMS\Fluid\ViewHelpers\Form\HiddenViewHelper;

/**
 * Test for the "Hidden" Form view helper
 */
class HiddenViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Form\HiddenViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(HiddenViewHelper::class, array('setErrorClassAttribute', 'getName', 'getValueAttribute', 'registerFieldNameForFormTokenGeneration'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder(\TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder::class)
            ->setMethods(array('setTagName', 'addAttribute'))
            ->getMock();
        $mockTagBuilder->expects($this->once())->method('setTagName')->with('input');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'hidden');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->once())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->once())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
