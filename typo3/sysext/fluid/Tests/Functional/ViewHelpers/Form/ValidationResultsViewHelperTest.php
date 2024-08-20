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
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ValidationResultsViewHelperTest extends FunctionalTestCase
{
    public static function renderValidationResultsDataProvider(): array
    {
        return [
            'all validation results' => [
                '<f:form.validationResults as="results"><f:for each="{results.flattenedErrors}" as="errors" key="propertyPath">[{propertyPath}:{errors.0}]</f:for></f:form.validationResults>',
                '[test:Test error][test.sub:Sub error]',
            ],
            'result for subitem' => [
                '<f:form.validationResults for="test.sub" as="results"><f:for each="{results.flattenedErrors}" as="errors">{errors.0}</f:for></f:form.validationResults>',
                'Sub error',
            ],
            'result variable is local' => [
                '<f:form.validationResults as="results"></f:form.validationResults><f:for each="{results.flattenedErrors}" as="errors">{errors.0}</f:for>',
                '',
            ],
        ];
    }

    #[DataProvider('renderValidationResultsDataProvider')]
    #[Test]
    public function renderValidationResults(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);

        $validationResults = new Result();
        $validationResults->forProperty('test')->addError(new Error('Test error', 123));
        $validationResults->forProperty('test.sub')->addError(new Error('Sub error', 456));

        $context = $this->get(RenderingContextFactory::class)->create();
        $serverRequest = (new ServerRequest())->withAttribute('extbase', (new ExtbaseRequestParameters())->setOriginalRequestMappingResults($validationResults))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context->setRequest(new Request($serverRequest));

        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, (new TemplateView($context))->render());
    }
}
