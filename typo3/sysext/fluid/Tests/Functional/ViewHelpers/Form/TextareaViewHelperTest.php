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

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class TextareaViewHelperTest extends FunctionalTestCase
{
    public function renderDataProvider(): array
    {
        return [
            'renderCorrectlySetsTagName' => [
                '<f:form.textarea />',
                '<textarea name=""></textarea>',
            ],
            'renderCorrectlySetsNameAttributeAndContent' => [
                '<f:form.textarea name="NameOfTextarea" value="Current value" />',
                '<textarea name="NameOfTextarea">Current value</textarea>',
            ],
            'renderEscapesTextareaContent' => [
                '<f:form.textarea name="NameOfTextarea" value="some <tag> & \"quotes\"" />',
                '<textarea name="NameOfTextarea">some &lt;tag&gt; &amp; &quot;quotes&quot;</textarea>',
            ],
            'renderAddsPlaceholder' => [
                '<f:form.textarea name="NameOfTextarea" placeholder="SomePlaceholder" />',
                '<textarea placeholder="SomePlaceholder" name="NameOfTextarea"></textarea>',
            ],
            'renderAddsReadonly' => [
                '<f:form.textarea name="NameOfTextarea" readonly="foo" />',
                '<textarea readonly="foo" name="NameOfTextarea"></textarea>',
            ],
            'renderAddsRequired' => [
                '<f:form.textarea name="NameOfTextarea" required="true" />',
                '<textarea name="NameOfTextarea" required="required"></textarea>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(string $template, string $expected): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $context->setRequest(new Request());
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute(): void
    {
        // Create an extbase request that contains mapping results of the form object property we're working with.
        $mappingResult = new Result();
        $objectResult = $mappingResult->forProperty('myObjectName');
        $propertyResult = $objectResult->forProperty('someProperty');
        $propertyResult->addError(new Error('invalidProperty', 2));
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setOriginalRequestMappingResults($mappingResult);
        $psr7Request = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters);
        $extbaseRequest = new Request($psr7Request);

        $formObject = new \stdClass();
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.textarea property="someProperty" errorClass="myError" /></f:form>');
        $context->setRequest($extbaseRequest);
        $view = new TemplateView($context);
        $view->assign('formObject', $formObject);
        // The point is that 'class="myError"' is added since the form had mapping errors for this property.
        self::assertStringContainsString('<textarea name="myFieldPrefix[myObjectName][someProperty]" class="myError"></textarea>', $view->render());
    }
}
