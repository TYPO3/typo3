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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Property;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime\FormSession;
use TYPO3\CMS\Form\Mvc\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Regression test for the file upload MIME type validation.
 *
 * The MimeTypeValidator must be registered on the processing rule based on the
 * element's configured "allowedMimeTypes". This registration happens at runtime
 * in PropertyMappingConfiguration (the afterFormStateInitialized hook), because
 * the concrete form definition properties are not yet available when
 * FileUpload::initializeFormElement() runs during form building.
 */
final class FileUploadMimeTypeValidatorRegistrationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();

        // ArrayFormFactory resolves the prototype configuration via the Extbase
        // ConfigurationManager, which requires a request to be set.
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ExtbaseConfigurationManagerInterface::class)->setRequest($request);
    }

    private function buildFormDefinition(array $fileUploadProperties): FormDefinition
    {
        $configuration = [
            'identifier' => 'test-form',
            'prototypeName' => 'standard',
            'type' => 'Form',
            'renderables' => [
                [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'renderables' => [
                        [
                            'identifier' => 'file-1',
                            'type' => 'FileUpload',
                            'properties' => $fileUploadProperties,
                        ],
                    ],
                ],
            ],
        ];

        return $this->get(ArrayFormFactory::class)->build($configuration);
    }

    private function runtimeAllowingSubmission(FormDefinition $formDefinition): FormRuntime
    {
        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('getFormDefinition')->willReturn($formDefinition);
        $formRuntime->method('getFormSession')->willReturn(new FormSession());
        $formRuntime->method('canProcessFormSubmission')->willReturn(true);

        return $formRuntime;
    }

    private function processingRuleHasMimeTypeValidator(FormDefinition $formDefinition): bool
    {
        $processingRule = $formDefinition->getProcessingRules()['file-1'] ?? null;
        self::assertNotNull($processingRule, 'No processing rule found for "file-1".');

        foreach ($processingRule->getValidators() as $validator) {
            if ($validator instanceof MimeTypeValidator) {
                return true;
            }
        }

        return false;
    }

    #[Test]
    public function mimeTypeValidatorIsRegisteredForConfiguredAllowedMimeTypes(): void
    {
        $formDefinition = $this->buildFormDefinition(['allowedMimeTypes' => ['application/pdf']]);

        $this->get(PropertyMappingConfiguration::class)
            ->afterFormStateInitialized($this->runtimeAllowingSubmission($formDefinition));

        self::assertTrue(
            $this->processingRuleHasMimeTypeValidator($formDefinition),
            'MimeTypeValidator was NOT registered despite configured allowedMimeTypes.'
        );
    }

    #[Test]
    public function mimeTypeValidatorIsNotRegisteredWhenNoAllowedMimeTypesConfigured(): void
    {
        $formDefinition = $this->buildFormDefinition(['allowedMimeTypes' => []]);

        $this->get(PropertyMappingConfiguration::class)
            ->afterFormStateInitialized($this->runtimeAllowingSubmission($formDefinition));

        self::assertFalse(
            $this->processingRuleHasMimeTypeValidator($formDefinition),
            'MimeTypeValidator must not be registered when no allowedMimeTypes are configured.'
        );
    }

    #[Test]
    public function mimeTypeValidatorIsRegisteredOnlyOnceWhenHookRunsMultipleTimes(): void
    {
        $formDefinition = $this->buildFormDefinition(['allowedMimeTypes' => ['application/pdf']]);
        $subject = $this->get(PropertyMappingConfiguration::class);
        $formRuntime = $this->runtimeAllowingSubmission($formDefinition);

        $subject->afterFormStateInitialized($formRuntime);
        $subject->afterFormStateInitialized($formRuntime);

        $mimeTypeValidatorCount = 0;
        foreach ($formDefinition->getProcessingRules()['file-1']->getValidators() as $validator) {
            if ($validator instanceof MimeTypeValidator) {
                $mimeTypeValidatorCount++;
            }
        }

        self::assertSame(1, $mimeTypeValidatorCount, 'MimeTypeValidator was registered more than once.');
    }
}
