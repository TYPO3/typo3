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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Select;

use TYPO3\CMS\Fluid\ViewHelpers\Form\Select\OptionViewHelper;
use TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for the "Option" Form view helper
 */
class OptionViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var OptionViewHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(OptionViewHelper::class)
            ->onlyMethods(['registerFieldNameForFormTokenGeneration', 'renderChildren'])
            ->getMock();
        $this->viewHelperVariableContainer->get(SelectViewHelper::class, 'registerFieldNameForFormTokenGeneration')->willReturn('');
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
        $this->viewHelperVariableContainer->get(SelectViewHelper::class, 'selectedValue')->willReturn('');
        $tagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('option');
        $this->viewHelper->setTagBuilder($tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function childrenContentIsUsedAsValueAndLabelByDefault(): void
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->willReturn('Option Label');
        $this->viewHelperVariableContainer->get(SelectViewHelper::class, 'selectedValue')->willReturn('');
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
        $this->viewHelperVariableContainer->get(SelectViewHelper::class, 'selectedValue')->willReturn('');
        $this->viewHelper->expects(self::once())->method('renderChildren')->willReturn('Option Label');
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
        $this->viewHelperVariableContainer->get(SelectViewHelper::class, 'selectedValue')->willReturn('Option Label');
        $this->viewHelper->expects(self::once())->method('renderChildren')->willReturn('Option Label');

        $expected = '<option selected="selected" value="Option Label">Option Label</option>';
        self::assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    public function selectedIsAddedToSelectedOptionForProvidedValueDataProvider(): array
    {
        return [
            'string value, string selection' => [
                'val1', 'val1'
            ],
            'string value, array selection' => [
                'val1', ['val1']
            ],
            'string value, iterable selection' => [
                'val1', (new \ArrayObject(['val1']))->getIterator()
            ],
            'int value, array selection' => [
                1, ['1']
            ]
        ];
    }

    /**
     * @test
     * @dataProvider selectedIsAddedToSelectedOptionForProvidedValueDataProvider
     * @param string $value
     * @param mixed $selected
     */
    public function selectedIsAddedToSelectedOptionForProvidedValue($value, $selected): void
    {
        $this->arguments['selected'] = null;
        $this->arguments['value'] = $value;
        $this->viewHelper->expects(self::once())->method('renderChildren')->willReturn('Option Label');
        $this->viewHelper->setArguments($this->arguments);
        $this->viewHelperVariableContainer->get(SelectViewHelper::class, 'selectedValue')->willReturn($selected);

        $expected = '<option value="' . $value . '" selected="selected">Option Label</option>';
        self::assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function selectedIsNotAddedToUnselectedOptionForNoSelectionOverride(): void
    {
        $this->arguments['selected'] = null;
        $this->viewHelper->setArguments($this->arguments);
        $this->viewHelperVariableContainer->get(SelectViewHelper::class, 'selectedValue')->willReturn('');
        $this->viewHelper->expects(self::once())->method('renderChildren')->willReturn('Option Label');

        $expected = '<option value="Option Label">Option Label</option>';
        self::assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    public function selectedIsNotAddedToSelectedOptionForProvidedValueDataProvider(): array
    {
        return [
            'string value, string selection' => [
                '1',
                '1-2'
            ],
            'string value, array selection' => [
                'val1',
                ['val3']
            ],
            'string value, iterable selection' => [
                'val1',
                (new \ArrayObject(['val3']))->getIterator()
            ],
            'int value, array selection' => [
                1,
                ['1-2']
            ]
        ];
    }

    /**
     * @test
     * @dataProvider selectedIsNotAddedToSelectedOptionForProvidedValueDataProvider
     * @param string $value
     * @param mixed $selected
     */
    public function selectedIsNotAddedToSelectedOptionForProvidedValue($value, $selected): void
    {
        $this->arguments['selected'] = null;
        $this->arguments['value'] = $value;
        $this->viewHelper->expects(self::once())->method('renderChildren')->willReturn('Option Label');
        $this->viewHelper->setArguments($this->arguments);
        $this->viewHelperVariableContainer->get(SelectViewHelper::class, 'selectedValue')->willReturn($selected);

        $expected = '<option value="' . $value . '">Option Label</option>';
        self::assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function selectedIsNotAddedToOptionForExplicitOverride(): void
    {
        $this->arguments['selected'] = false;
        $this->viewHelper->setArguments($this->arguments);
        $this->viewHelper->expects(self::once())->method('renderChildren')->willReturn('Option Label');

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
        $this->viewHelper->expects(self::once())->method('renderChildren')->willReturn('Option Label');

        $expected = '<option selected="selected" value="Option Label">Option Label</option>';
        self::assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }
}
