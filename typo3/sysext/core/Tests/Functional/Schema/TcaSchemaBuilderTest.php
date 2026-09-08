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

namespace TYPO3\CMS\Core\Tests\Functional\Schema;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\TcaSchemaBuilder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaSchemaBuilderTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function getFinalFieldConfigurationProcessesColumnOverridesDataProvider(): iterable
    {
        yield 'No overrides, no label' => [
            'fieldName' => 'text',
            'schemaConfiguration' => [
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
            'subSchemaConfiguration' => [],
            'fieldLabel' => null,
            'expected' => [
                'label' => 'Text',
                'config' => [
                    'type' => 'text',
                ],
            ],
        ];

        yield 'No overrides, alternative label' => [
            'fieldName' => 'text',
            'schemaConfiguration' => [
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
            'subSchemaConfiguration' => [],
            'fieldLabel' => 'Text alt',
            'expected' => [
                'label' => 'Text alt',
                'config' => [
                    'type' => 'text',
                ],
            ],
        ];

        yield 'overrides, no label' => [
            'fieldName' => 'text',
            'schemaConfiguration' => [
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
            'subSchemaConfiguration' => [
                'columnsOverrides' => [
                    'text' => [
                        'config' => [
                            'required' => true,
                        ],
                    ],
                ],
            ],
            'fieldLabel' => null,
            'expected' => [
                'label' => 'Text',
                'config' => [
                    'type' => 'text',
                    'required' => true,
                ],
            ],
        ];

        yield 'overrides, alternative label' => [
            'fieldName' => 'text',
            'schemaConfiguration' => [
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                            'required' => false,
                        ],
                    ],
                ],
            ],
            'subSchemaConfiguration' => [
                'columnsOverrides' => [
                    'text' => [
                        'config' => [
                            'required' => true,
                        ],
                    ],
                ],
            ],
            'fieldLabel' => 'Alt label',
            'expected' => [
                'label' => 'Alt label',
                'config' => [
                    'type' => 'text',
                    'required' => true,
                ],
            ],
        ];
    }

    #[DataProvider('getFinalFieldConfigurationProcessesColumnOverridesDataProvider')]
    #[Test]
    public function getFinalFieldConfigurationProcessesColumnOverrides(string $fieldName, array $schemaConfiguration, array $subSchemaConfiguration, ?string $fieldLabel, array $expected): void
    {
        $subject = $this->get(TcaSchemaBuilder::class);
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'getFinalFieldConfiguration'));
        $result = $subjectMethodReflection->invoke($subject, $fieldName, $schemaConfiguration, $subSchemaConfiguration, $fieldLabel);
        self::assertSame($expected, $result);
    }
}
