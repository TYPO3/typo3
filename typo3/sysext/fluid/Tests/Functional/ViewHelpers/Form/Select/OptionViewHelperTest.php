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

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class OptionViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function optionTagNameIsSet(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select.option />');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context->setRequest(new Request($serverRequest));
        self::assertSame('<option selected="selected" value="" />', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function childrenContentIsUsedAsValueAndLabelByDefault(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select.option>Option Label</f:form.select.option>');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context->setRequest(new Request($serverRequest));
        self::assertSame('<option value="Option Label">Option Label</option>', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function valueCanBeOverwrittenByArgument(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select.option value="value">Option Label</f:form.select.option>');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context->setRequest(new Request($serverRequest));
        self::assertSame('<option value="value">Option Label</option>', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function selectedIsAddedToSelectedOptionForNoSelectionOverride(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value="Option Label"><f:form.select.option>Option Label</f:form.select.option></f:form.select>');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context->setRequest(new Request($serverRequest));
        self::assertSame('<select name=""><option selected="selected" value="Option Label">Option Label</option></select>', (new TemplateView($context))->render());
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
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value="{selected}"><f:form.select.option value="{value}">Option Label</f:form.select.option></f:form.select>');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context->setRequest(new Request($serverRequest));
        $view = new TemplateView($context);
        $view->assignMultiple([
            'value' => $value,
            'selected' => $selected,
        ]);
        $expected = '<select name=""><option value="' . $value . '" selected="selected">Option Label</option></select>';
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectedIsNotAddedToUnselectedOptionForNoSelectionOverride(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value=""><f:form.select.option>Option Label</f:form.select.option></f:form.select>');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context->setRequest(new Request($serverRequest));
        self::assertSame('<select name=""><option value="Option Label">Option Label</option></select>', (new TemplateView($context))->render());
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
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value="{selected}"><f:form.select.option value="{value}">Option Label</f:form.select.option></f:form.select>');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context->setRequest(new Request($serverRequest));
        $view = new TemplateView($context);
        $view->assignMultiple([
            'value' => $value,
            'selected' => $selected,
        ]);
        $expected = '<select name=""><option value="' . $value . '">Option Label</option></select>';
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectedIsNotAddedToOptionForExplicitOverride(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value="Option Label"><f:form.select.option selected="false">Option Label</f:form.select.option></f:form.select>');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context->setRequest(new Request($serverRequest));
        $expected = '<select name=""><option value="Option Label">Option Label</option></select>';
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function selectedIsAddedToOptionForExplicitOverride(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="" value="Option Label"><f:form.select.option selected="true">Option Label</f:form.select.option></f:form.select>');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context->setRequest(new Request($serverRequest));
        $expected = '<select name=""><option selected="selected" value="Option Label">Option Label</option></select>';
        self::assertSame($expected, (new TemplateView($context))->render());
    }
}
