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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence;

use TYPO3\CMS\Extbase\Persistence\ClassesConfigurationFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TYPO3\CMS\Extbase\Tests\Unit\Persistence\ClassesConfigurationTest
 */
class ClassesConfigurationFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function inheritPropertiesFromParentClasses(): void
    {
        $classesConfigurationFactory = new ClassesConfigurationFactory();

        $classes = [
            Fixture\Domain\Model\A::class => [
                'properties' => [
                    'propertiesFromA' => [
                        'fieldName' => 'field_name_a'
                    ]
                ]
            ],
            Fixture\Domain\Model\B::class => [
                'properties' => [
                    'propertiesFromA' => [
                        'fieldName' => 'field_name_z'
                    ],
                    'propertiesFromB' => [
                        'fieldName' => 'field_name_b'
                    ]
                ]
            ],
            Fixture\Domain\Model\C::class => [
                'properties' => [
                    'columnNameC' => [
                        'fieldName' => 'field_name_c'
                    ]
                ]
            ],
        ];

        $reflectionMethod = (new \ReflectionClass($classesConfigurationFactory))
            ->getMethod('inheritPropertiesFromParentClasses');
        $reflectionMethod->setAccessible(true);
        $classes = $reflectionMethod->invoke($classesConfigurationFactory, $classes);

        self::assertSame(
            [
                Fixture\Domain\Model\A::class => [
                    'properties' => [
                        'propertiesFromA' => [
                            'fieldName' => 'field_name_a'
                        ],
                    ]
                ],
                Fixture\Domain\Model\B::class => [
                    'properties' => [
                        'propertiesFromA' => [
                            // todo: this is flawed, we'd actually expect field_name_z here
                            // todo: see https://forge.typo3.org/issues/87566
                            'fieldName' => 'field_name_a'
                        ],
                        'propertiesFromB' => [
                            'fieldName' => 'field_name_b'
                        ],
                    ]
                ],
                Fixture\Domain\Model\C::class => [
                    'properties' => [
                        'columnNameC' => [
                            'fieldName' => 'field_name_c'
                        ],
                        'propertiesFromA' => [
                            'fieldName' => 'field_name_a'
                        ],
                        'propertiesFromB' => [
                            'fieldName' => 'field_name_b'
                        ],
                    ]
                ],
            ],
            $classes
        );
    }
}
