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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\EmptySyntaxTreeNode;
use TYPO3\CMS\Fluid\ViewHelpers\Form\UploadViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for the "Upload" Form view helper
 */
class UploadViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Form\UploadViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(UploadViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagName()
    {
        $this->tagBuilder = $this->createMock(TagBuilder::class);
        $this->tagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('input');
        $this->viewHelper->setTagBuilder($this->tagBuilder);
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
        $mockTagBuilder->expects(self::at(0))->method('addAttribute')->with('type', 'file');
        $mockTagBuilder->expects(self::at(1))->method('addAttribute')->with('name', 'someName');
        $this->viewHelper->expects(self::at(0))->method('registerFieldNameForFormTokenGeneration')->with('someName[name]');
        $this->viewHelper->expects(self::at(1))->method('registerFieldNameForFormTokenGeneration')->with('someName[type]');
        $this->viewHelper->expects(self::at(2))->method('registerFieldNameForFormTokenGeneration')->with('someName[tmp_name]');
        $this->viewHelper->expects(self::at(3))->method('registerFieldNameForFormTokenGeneration')->with('someName[error]');
        $this->viewHelper->expects(self::at(4))->method('registerFieldNameForFormTokenGeneration')->with('someName[size]');
        $mockTagBuilder->expects(self::once())->method('render');
        $this->viewHelper->setTagBuilder($mockTagBuilder);
        $arguments = [
            'name' => 'someName'
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
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderSetsAttributeNameAsArrayIfMultipleIsGiven()
    {
        /** @var TagBuilder $tagBuilder */
        $tagBuilder = GeneralUtility::makeInstance(TagBuilder::class);
        $tagBuilder->addAttribute('multiple', 'multiple');
        $this->viewHelper->setTagBuilder($tagBuilder);
        $arguments = [
            'name' => 'someName',
            'multiple' => 'multiple'
        ];
        $this->viewHelper->setArguments($arguments);
        $result = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('<input multiple="multiple" type="file" name="someName[]" />', $result);
    }
}
