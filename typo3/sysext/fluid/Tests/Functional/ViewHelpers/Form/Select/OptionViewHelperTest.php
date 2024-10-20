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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class OptionViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function optionTagNameIsSet(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select.option />');
        self::assertSame('<option selected="selected" value="" />', (new TemplateView($context))->render());
    }

    #[Test]
    public function childrenContentIsUsedAsValueAndLabelByDefault(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select.option>Option Label</f:form.select.option>');
        self::assertSame('<option value="Option Label">Option Label</option>', (new TemplateView($context))->render());
    }

    #[Test]
    public function valueCanBeOverwrittenByArgument(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select.option value="value">Option Label</f:form.select.option>');
        self::assertSame('<option value="value">Option Label</option>', (new TemplateView($context))->render());
    }

    #[Test]
    public function selectedIsAddedToSelectedOptionForNoSelectionOverride(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value="Option Label"><f:form.select.option>Option Label</f:form.select.option></f:form.select>');
        self::assertSame('<select name=""><option selected="selected" value="Option Label">Option Label</option></select>', (new TemplateView($context))->render());
    }

    public static function selectedIsAddedToSelectedOptionForProvidedValueDataProvider(): array
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

    #[DataProvider('selectedIsAddedToSelectedOptionForProvidedValueDataProvider')]
    #[Test]
    public function selectedIsAddedToSelectedOptionForProvidedValue($value, $selected): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value="{selected}"><f:form.select.option value="{value}">{value}</f:form.select.option></f:form.select>');
        $view = new TemplateView($context);
        $view->assignMultiple([
            'value' => $value,
            'selected' => $selected,
        ]);
        $expected = '<select name=""><option selected="selected" value="' . $value . '">' . $value . '</option></select>';
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function selectedIsNotAddedToUnselectedOptionForNoSelectionOverride(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value=""><f:form.select.option>Option Label</f:form.select.option></f:form.select>');
        self::assertSame('<select name=""><option value="Option Label">Option Label</option></select>', (new TemplateView($context))->render());
    }

    public static function selectedIsNotAddedToSelectedOptionForProvidedValueDataProvider(): array
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

    #[DataProvider('selectedIsNotAddedToSelectedOptionForProvidedValueDataProvider')]
    #[Test]
    public function selectedIsNotAddedToSelectedOptionForProvidedValue($value, $selected): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value="{selected}"><f:form.select.option value="{value}">Option Label</f:form.select.option></f:form.select>');
        $view = new TemplateView($context);
        $view->assignMultiple([
            'value' => $value,
            'selected' => $selected,
        ]);
        $expected = '<select name=""><option value="' . $value . '">Option Label</option></select>';
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function selectedIsNotAddedToOptionForExplicitOverride(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value="Option Label"><f:form.select.option selected="false">Option Label</f:form.select.option></f:form.select>');
        $expected = '<select name=""><option value="Option Label">Option Label</option></select>';
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function selectedIsAddedToOptionForExplicitOverride(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value="Option Label"><f:form.select.option selected="true">Option Label</f:form.select.option></f:form.select>');
        $expected = '<select name=""><option selected="selected" value="Option Label">Option Label</option></select>';
        self::assertSame($expected, (new TemplateView($context))->render());
    }
}
