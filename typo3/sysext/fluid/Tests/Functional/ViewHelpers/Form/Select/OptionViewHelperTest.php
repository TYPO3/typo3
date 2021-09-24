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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Form\Select;

use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class OptionViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    /**
     * @test
     */
    public function optionTagNameIsSet(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.select.option />');
        self::assertSame('<option selected="selected" value="" />', $view->render());
    }

    /**
     * @test
     */
    public function childrenContentIsUsedAsValueAndLabelByDefault(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.select.option>Option Label</f:form.select.option>');
        self::assertSame('<option value="Option Label">Option Label</option>', $view->render());
    }

    /**
     * @test
     */
    public function valueCanBeOverwrittenByArgument(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.select.option value="value">Option Label</f:form.select.option>');
        self::assertSame('<option value="value">Option Label</option>', $view->render());
    }

    /**
     * @test
     */
    public function selectedIsAddedToSelectedOptionForNoSelectionOverride(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.select name="" value="Option Label"><f:form.select.option>Option Label</f:form.select.option></f:form.select>');
        self::assertSame('<select name=""><option selected="selected" value="Option Label">Option Label</option></select>', $view->render());
    }

    public function selectedIsAddedToSelectedOptionForProvidedValueDataProvider(): array
    {
        return [
            'string value, string selection' => [
                'val1', 'val1',
            ],
            'string value, int selection' => [
                '1', 1,
            ],
            'string value, int array selection' => [
                '1', [1],
            ],
            'string value, iterable selection with int' => [
                '1', (new \ArrayObject([1]))->getIterator(),
            ],
            'string value, array selection' => [
                'val1', ['val1'],
            ],
            'string value, iterable selection' => [
                'val1', (new \ArrayObject(['val1']))->getIterator(),
            ],
            'int value, array selection' => [
                1, ['1'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider selectedIsAddedToSelectedOptionForProvidedValueDataProvider
     */
    public function selectedIsAddedToSelectedOptionForProvidedValue($value, $selected): void
    {
        $view = new StandaloneView();
        $view->assignMultiple([
            'value' => $value,
            'selected' => $selected,
        ]);
        $view->setTemplateSource('<f:form.select name="" value="{selected}"><f:form.select.option value="{value}">Option Label</f:form.select.option></f:form.select>');
        $expected = '<select name=""><option value="' . $value . '" selected="selected">Option Label</option></select>';
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectedIsNotAddedToUnselectedOptionForNoSelectionOverride(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.select name="" value=""><f:form.select.option>Option Label</f:form.select.option></f:form.select>');
        self::assertSame('<select name=""><option value="Option Label">Option Label</option></select>', $view->render());
    }

    public function selectedIsNotAddedToSelectedOptionForProvidedValueDataProvider(): array
    {
        return [
            'string value, string selection' => [
                '1',
                '1-2',
            ],
            'string value, array selection' => [
                'val1',
                ['val3'],
            ],
            'string value, iterable selection' => [
                'val1',
                (new \ArrayObject(['val3']))->getIterator(),
            ],
            'int value, array selection' => [
                1,
                ['1-2'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider selectedIsNotAddedToSelectedOptionForProvidedValueDataProvider
     */
    public function selectedIsNotAddedToSelectedOptionForProvidedValue($value, $selected): void
    {
        $view = new StandaloneView();
        $view->assignMultiple([
            'value' => $value,
            'selected' => $selected,
        ]);
        $view->setTemplateSource('<f:form.select name="" value="{selected}"><f:form.select.option value="{value}">Option Label</f:form.select.option></f:form.select>');
        $expected = '<select name=""><option value="' . $value . '">Option Label</option></select>';
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectedIsNotAddedToOptionForExplicitOverride(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.select name="" value="Option Label"><f:form.select.option selected="false">Option Label</f:form.select.option></f:form.select>');
        $expected = '<select name=""><option value="Option Label">Option Label</option></select>';
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectedIsAddedToOptionForExplicitOverride(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.select name="" value="Option Label"><f:form.select.option selected="true">Option Label</f:form.select.option></f:form.select>');
        $expected = '<select name=""><option selected="selected" value="Option Label">Option Label</option></select>';
        self::assertSame($expected, $view->render());
    }
}
