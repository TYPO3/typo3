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
use TYPO3\CMS\Fluid\ViewHelpers\Form\PasswordViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for the "Password" Form view helper
 */
class PasswordViewHelperTest extends FormFieldViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Form\PasswordViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(PasswordViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
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
        $mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('input');
        $this->viewHelper->setTagBuilder($mockTagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTypeNameAndValueAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['addAttribute', 'setContent', 'render'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockTagBuilder->expects(self::at(0))->method('addAttribute')->with('type', 'password');
        $mockTagBuilder->expects(self::at(1))->method('addAttribute')->with('name', 'NameOfTextbox');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextbox');
        $mockTagBuilder->expects(self::at(2))->method('addAttribute')->with('value', 'Current value');
        $mockTagBuilder->expects(self::once())->method('render');
        $this->viewHelper->setTagBuilder($mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextbox',
            'value' => 'Current value'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects(self::once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }
}
