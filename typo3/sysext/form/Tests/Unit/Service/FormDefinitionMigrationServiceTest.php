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

namespace TYPO3\CMS\Form\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use TYPO3\CMS\Form\Service\FormDefinitionMigrationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test cases for FormDefinitionMigrationService.
 */
final class FormDefinitionMigrationServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    #[WithoutErrorHandler]
    public function migrateConvertsFieldExplanationTextToDescription(): void
    {
        $migrationService = new FormDefinitionMigrationService();
        $input = [
            'prototypes' => [
                'standard' => [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'fieldExplanationText' => 'Top level',
                                'editors' => [
                                    800 => ['fieldExplanationText' => 'Nested'],
                                ],
                                'propertyCollections' => [
                                    'validators' => [
                                        10 => [
                                            'editors' => [
                                                200 => ['fieldExplanationText' => 'Validator'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'custom' => [
                    'formElementsDefinition' => [
                        'Custom' => ['formEditor' => ['fieldExplanationText' => 'Custom']],
                    ],
                ],
            ],
        ];

        $result = $migrationService->migrate($input);

        self::assertSame('Top level', $result['prototypes']['standard']['formElementsDefinition']['Text']['formEditor']['description']);
        self::assertSame('Nested', $result['prototypes']['standard']['formElementsDefinition']['Text']['formEditor']['editors'][800]['description']);
        self::assertSame('Validator', $result['prototypes']['standard']['formElementsDefinition']['Text']['formEditor']['propertyCollections']['validators'][10]['editors'][200]['description']);
        self::assertSame('Custom', $result['prototypes']['custom']['formElementsDefinition']['Custom']['formEditor']['description']);
    }
}
