<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Select;

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

use TYPO3\CMS\Fluid\ViewHelpers\Form\Select\OptionViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for the "Option" Form view helper
 */
class OptionViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var OptionViewHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(OptionViewHelper::class)
            ->setMethods(['isValueSelected', 'registerFieldNameForFormTokenGeneration', 'renderChildren'])
            ->getMock();
        $this->arguments['selected'] = null;
        $this->arguments['value'] = null;
        $this->tagBuilder = new TagBuilder();
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function optionTagNameIsSet(): void
    {
        $tagBuilder = $this->createMock(TagBuilder::class);
        $tagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('option');
        $this->viewHelper->setTagBuilder($tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function childrenContentIsUsedAsValueAndLabelByDefault(): void
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('Option Label'));
        $expected = '<option value="Option Label">Option Label</option>';
        self::assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function valueCanBeOverwrittenByArgument(): void
    {
        $this->arguments['value'] = 'value';
        $this->viewHelper->setArguments($this->arguments);
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('Option Label'));
        $expected = '<option value="value">Option Label</option>';
        self::assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function selectedIsAddedToSelectedOptionForNoSelectionOverride(): void
    {
        $this->arguments['selected'] = null;
        $this->viewHelper->setArguments($this->arguments);

        $this->viewHelper->expects(self::once())->method('isValueSelected')->will(self::returnValue(true));
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('Option Label'));

        $expected = '<option selected="selected" value="Option Label">Option Label</option>';
        self::assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function selectedIsNotAddedToUnselectedOptionForNoSelectionOverride(): void
    {
        $this->arguments['selected'] = null;
        $this->viewHelper->setArguments($this->arguments);

        $this->viewHelper->expects(self::once())->method('isValueSelected')->will(self::returnValue(false));
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('Option Label'));

        $expected = '<option value="Option Label">Option Label</option>';
        self::assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function selectedIsNotAddedToOptionForExplicitOverride(): void
    {
        $this->arguments['selected'] = false;
        $this->viewHelper->setArguments($this->arguments);
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('Option Label'));

        $expected = '<option value="Option Label">Option Label</option>';
        self::assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function selectedIsAddedToOptionForExplicitOverride(): void
    {
        $this->arguments['selected'] = true;
        $this->viewHelper->setArguments($this->arguments);
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('Option Label'));

        $expected = '<option selected="selected" value="Option Label">Option Label</option>';
        self::assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }
}
