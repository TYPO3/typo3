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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TextfieldViewHelperTest extends FunctionalTestCase
{
    public function renderDataProvider(): array
    {
        return [
            'renderCorrectlySetsTagName' => [
                '<f:form.textfield />',
                '<input type="text" name="" />',
            ],
            'renderCorrectlySetsTypeNameAndValueAttributes' => [
                '<f:form.textfield name="NameOfTextfield" value="Current value" type="text" />',
                '<input type="text" name="NameOfTextfield" value="Current value" />',
            ],
            'renderAddsPlaceholder' => [
                '<f:form.textfield name="NameOfTextfield" placeholder="SomePlaceholder" />',
                '<input placeholder="SomePlaceholder" type="text" name="NameOfTextfield" />',
            ],
            'renderCorrectlySetsRequiredAttribute' => [
                '<f:form.textfield required="true" name="NameOfTextfield" />',
                '<input type="text" name="NameOfTextfield" required="required" />',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(string $template, string $expected): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource($template);
        self::assertSame($expected, $view->render());
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
        GeneralUtility::addInstance(Request::class, $extbaseRequest);

        $formObject = new \stdClass();
        $view = new StandaloneView();
        $view->assign('formObject', $formObject);
        $view->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.textfield property="someProperty" errorClass="myError" /></f:form>');
        // The point is that 'class="myError"' is added since the form had mapping errors for this property.
        self::assertStringContainsString('<input type="text" name="myFieldPrefix[myObjectName][someProperty]" class="myError" />', $view->render());
    }
}
