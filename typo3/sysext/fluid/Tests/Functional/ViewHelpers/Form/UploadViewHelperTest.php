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

class UploadViewHelperTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function renderCorrectlySetsTagName(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.upload />');
        self::assertSame('<input type="file" name="" />', $view->render());
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTypeNameAttributes(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.upload name="someName" />');
        self::assertSame('<input type="file" name="someName" />', $view->render());
    }

    /**
     * @test
     */
    public function renderSetsAttributeNameAsArrayIfMultipleIsGiven(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.upload multiple="multiple" name="someName" />');
        self::assertSame('<input multiple="multiple" type="file" name="someName[]" />', $view->render());
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
        $view->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.upload property="someProperty" errorClass="myError" /></f:form>');
        // The point is that 'class="myError"' is added since the form had mapping errors for this property.
        self::assertStringContainsString('<input type="file" name="myFieldPrefix[myObjectName][someProperty]" class="myError" />', $view->render());
    }
}
