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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class RadioViewHelperTest extends FunctionalTestCase
{
    public static function renderDataProvider(): array
    {
        return [
            'renderSetsTagNameAndDefaultAttributes' => [
                '<f:form.radio name="foo" value="" />',
                '<input type="radio" name="foo" value="" />',
            ],
            'renderSetsCheckedAttributeIfSpecified' => [
                '<f:form.radio name="foo" value="" checked="true" />',
                '<input type="radio" name="foo" value="" checked="checked" />',
            ],
        ];
    }

    #[DataProvider('renderDataProvider')]
    #[Test]
    public function render(string $template, string $expected): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function renderIgnoresBoundPropertyIfCheckedIsSet(): void
    {
        $formObject = new \stdClass();
        $formObject->someProperty = false;
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.radio value="foo" property="someProperty" checked="true" /></f:form>');
        $view = new TemplateView($context);
        $view->assign('formObject', $formObject);
        self::assertStringContainsString('<input type="radio" name="myFieldPrefix[myObjectName][someProperty]" value="foo" checked="checked" />', $view->render());
    }

    #[Test]
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeBoolean(): void
    {
        $formObject = new \stdClass();
        $formObject->someProperty = true;
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.radio value="foo" property="someProperty" /></f:form>');
        $view = new TemplateView($context);
        $view->assign('formObject', $formObject);
        self::assertStringContainsString('<input type="radio" name="myFieldPrefix[myObjectName][someProperty]" value="foo" checked="checked" />', $view->render());
    }

    #[Test]
    public function renderDoesNotAppendSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray(): void
    {
        $formObject = new \stdClass();
        $formObject->someProperty = [];
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.radio value="foo" property="someProperty" /></f:form>');
        $view = new TemplateView($context);
        $view->assign('formObject', $formObject);
        self::assertStringContainsString('<input type="radio" name="myFieldPrefix[myObjectName][someProperty]" value="foo" />', $view->render());
    }

    #[Test]
    public function renderSetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeString(): void
    {
        $formObject = new \stdClass();
        $formObject->someProperty = '';
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.radio value="foo" property="someProperty" /></f:form>');
        $view = new TemplateView($context);
        $view->assign('formObject', $formObject);
        self::assertStringContainsString('<input type="radio" name="myFieldPrefix[myObjectName][someProperty]" value="foo" />', $view->render());
    }

    #[Test]
    public function renderDoesNotSetsCheckedAttributeIfBoundPropertyIsNull(): void
    {
        $formObject = new \stdClass();
        $formObject->someProperty = null;
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.radio value="foo" property="someProperty" /></f:form>');
        $view = new TemplateView($context);
        $view->assign('formObject', $formObject);
        self::assertStringContainsString('<input type="radio" name="myFieldPrefix[myObjectName][someProperty]" value="foo" />', $view->render());
    }

    #[Test]
    public function renderCallSetsErrorClassAttribute(): void
    {
        // Create an extbase request that contains mapping results of the form object property we're working with.
        $mappingResult = new Result();
        $objectResult = $mappingResult->forProperty('myObjectName');
        $propertyResult = $objectResult->forProperty('someProperty');
        $propertyResult->addError(new Error('invalidProperty', 2));
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setOriginalRequestMappingResults($mappingResult);
        $psr7Request = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseRequest = new Request($psr7Request);

        $formObject = new \stdClass();
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.radio value="foo" property="someProperty" errorClass="myError" /></f:form>');
        $view = new TemplateView($context);
        $view->assign('formObject', $formObject);
        // The point is that 'class="myError"' is added since the form had mapping errors for this property.
        self::assertStringContainsString('<input type="radio" name="myFieldPrefix[myObjectName][someProperty]" value="foo" class="myError" />', $view->render());
    }

    #[Test]
    public function renderCallSetsStandardErrorClassAttributeIfNonIsSpecified(): void
    {
        $mappingResult = new Result();
        $objectResult = $mappingResult->forProperty('myObjectName');
        $propertyResult = $objectResult->forProperty('someProperty');
        $propertyResult->addError(new Error('invalidProperty', 2));
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setOriginalRequestMappingResults($mappingResult);
        $psr7Request = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseRequest = new Request($psr7Request);

        $formObject = new \stdClass();
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.radio value="foo" property="someProperty" /></f:form>');
        $view = new TemplateView($context);
        $view->assign('formObject', $formObject);
        self::assertStringContainsString('<input type="radio" name="myFieldPrefix[myObjectName][someProperty]" value="foo" class="f3-form-error" />', $view->render());
    }

    #[Test]
    public function renderCallExtendsClassAttributeWithErrorClass(): void
    {
        $mappingResult = new Result();
        $objectResult = $mappingResult->forProperty('myObjectName');
        $propertyResult = $objectResult->forProperty('someProperty');
        $propertyResult->addError(new Error('invalidProperty', 2));
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setOriginalRequestMappingResults($mappingResult);
        $psr7Request = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseRequest = new Request($psr7Request);

        $formObject = new \stdClass();
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.radio value="foo" property="someProperty" class="myClass" /></f:form>');
        $view = new TemplateView($context);
        $view->assign('formObject', $formObject);
        self::assertStringContainsString('<input class="myClass f3-form-error" type="radio" name="myFieldPrefix[myObjectName][someProperty]" value="foo" />', $view->render());
    }
}
