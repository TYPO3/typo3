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

final class HasErrorsViewHelperTest extends FunctionalTestCase
{
    public static function renderDataProvider(): array
    {
        return [
            'then is rendered for a property with an error' => [
                '<f:form.hasErrors for="title"><f:then>yes</f:then><f:else>no</f:else></f:form.hasErrors>',
                'yes',
            ],
            'else is rendered for a property without an error' => [
                '<f:form.hasErrors for="subtitle"><f:then>yes</f:then><f:else>no</f:else></f:form.hasErrors>',
                'no',
            ],
            'an error on a sub property counts for the parent' => [
                '<f:form.hasErrors for="author"><f:then>yes</f:then><f:else>no</f:else></f:form.hasErrors>',
                'yes',
            ],
            'a nested property path is addressed directly' => [
                '<f:form.hasErrors for="author.email"><f:then>yes</f:then><f:else>no</f:else></f:form.hasErrors>',
                'yes',
            ],
            'inline notation returns the then argument' => [
                '{f:form.hasErrors(for: \'title\', then: \'has-error\')}',
                'has-error',
            ],
            'inline notation returns null without a match and without an else' => [
                '{f:form.hasErrors(for: \'subtitle\', then: \'has-error\')}',
                null,
            ],
            'inline notation returns the else argument without a match' => [
                '{f:form.hasErrors(for: \'subtitle\', then: \'has-error\', else: \'all-good\')}',
                'all-good',
            ],
        ];
    }

    #[DataProvider('renderDataProvider')]
    #[Test]
    public function viewHelperRendersExpectedOutput(string $template, mixed $expected): void
    {
        self::assertSame($expected, $this->renderTemplate($template));
    }

    #[Test]
    public function throwsExceptionIfForArgumentDoesNotResolveToANonEmptyString(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1789587437);

        $this->renderTemplate('<f:form.hasErrors for="{undefinedVariable}">yes</f:form.hasErrors>');
    }

    #[Test]
    public function throwsExceptionIfForArgumentIsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1789587437);

        $this->renderTemplate('<f:form.hasErrors for="">yes</f:form.hasErrors>');
    }

    #[Test]
    public function throwsExceptionWithoutAnExtbaseRequest(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.hasErrors for="title">yes</f:form.hasErrors>');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1789587438);

        new TemplateView($context)->render();
    }

    private function renderTemplate(string $template): mixed
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);

        $validationResults = new Result();
        $validationResults->forProperty('title')->addError(new Error('Title error', 123));
        $validationResults->forProperty('author.email')->addError(new Error('Email error', 456));

        $serverRequest = new ServerRequest()->withAttribute('extbase', new ExtbaseRequestParameters()->setOriginalRequestMappingResults($validationResults))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource($template);

        return new TemplateView($context)->render();
    }
}
