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
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for the "Option" Form view helper
 */
class OptionViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var OptionViewHelper|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(
            OptionViewHelper::class,
            ['isValueSelected', 'registerFieldNameForFormTokenGeneration', 'renderChildren']
        );
        $this->arguments['selected'] = null;
        $this->arguments['value'] = null;
        $this->tagBuilder = new TagBuilder();
        $this->viewHelper->_set('tag', $this->tagBuilder);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function optionTagNameIsSet()
    {
        $tagBuilder = $this->createMock(TagBuilder::class);
        $tagBuilder->expects($this->once())->method('setTagName')->with('option');

        $this->viewHelper->_set('tag', $tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function childrenContentIsUsedAsValueAndLabelByDefault()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Option Label'));
        $expected = '<option value="Option Label">Option Label</option>';
        $this->assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function valueCanBeOverwrittenByArgument()
    {
        $this->arguments['value'] = 'value';
        $this->viewHelper->setArguments($this->arguments);
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Option Label'));
        $expected = '<option value="value">Option Label</option>';
        $this->assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function selectedIsAddedToSelectedOptionForNoSelectionOverride()
    {
        $this->arguments['selected'] = null;
        $this->viewHelper->setArguments($this->arguments);

        $this->viewHelper->expects($this->once())->method('isValueSelected')->will($this->returnValue(true));
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Option Label'));

        $expected = '<option selected="selected" value="Option Label">Option Label</option>';
        $this->assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function selectedIsNotAddedToUnselectedOptionForNoSelectionOverride()
    {
        $this->arguments['selected'] = null;
        $this->viewHelper->setArguments($this->arguments);

        $this->viewHelper->expects($this->once())->method('isValueSelected')->will($this->returnValue(false));
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Option Label'));

        $expected = '<option value="Option Label">Option Label</option>';
        $this->assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function selectedIsNotAddedToOptionForExplicitOverride()
    {
        $this->arguments['selected'] = false;
        $this->viewHelper->setArguments($this->arguments);
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Option Label'));

        $expected = '<option value="Option Label">Option Label</option>';
        $this->assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function selectedIsAddedToOptionForExplicitOverride()
    {
        $this->arguments['selected'] = true;
        $this->viewHelper->setArguments($this->arguments);
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Option Label'));

        $expected = '<option selected="selected" value="Option Label">Option Label</option>';
        $this->assertEquals($expected, $this->viewHelper->initializeArgumentsAndRender());
    }
}
