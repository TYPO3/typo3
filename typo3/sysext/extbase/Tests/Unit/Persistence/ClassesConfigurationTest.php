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

use TYPO3\CMS\Extbase\Persistence\ClassesConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TYPO3\CMS\Extbase\Tests\Unit\Persistence\ClassesConfigurationTest
 */
class ClassesConfigurationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function hasClassReturnsTrue(): void
    {
        $className = 'ClassName';
        $classesConfiguration = new ClassesConfiguration([$className => []]);
        self::assertTrue($classesConfiguration->hasClass($className));
    }

    /**
     * @test
     */
    public function hasClassReturnsFalse(): void
    {
        $className = 'ClassName';
        $classesConfiguration = new ClassesConfiguration([]);
        self::assertFalse($classesConfiguration->hasClass($className));
    }

    /**
     * @test
     */
    public function getConfigurationForReturnsArray(): void
    {
        $configuration = [
            'ClassName' => [
                'tableName' => 'table'
            ]
        ];
        $classesConfiguration = new ClassesConfiguration($configuration);
        self::assertSame(
            $configuration['ClassName'],
            $classesConfiguration->getConfigurationFor('ClassName')
        );
    }

    /**
     * @test
     */
    public function getConfigurationForReturnsNull(): void
    {
        $classesConfiguration = new ClassesConfiguration([]);
        self::assertNull($classesConfiguration->getConfigurationFor('ClassName'));
    }

    /**
     * @return array
     */
    public function resolveSubclassesRecursiveDataProvider(): array
    {
        return [
            [
                [
                    'B',
                    'C',
                    'A',
                ],
                [
                    'A' => [
                        'subclasses' => [
                            'B',
                        ]
                    ],
                    'B' => [
                        'subclasses' => [
                            'C'
                        ]
                    ],
                    'C' => [
                        'subclasses' => [
                            'A'
                        ]
                    ],
                ],
                'A'
            ],
            [
                [
                    'A',
                    'B',
                    'C',
                ],
                [
                    'A' => [
                        'subclasses' => [
                            'B',
                        ]
                    ],
                    'B' => [
                        'subclasses' => [
                            'C'
                        ]
                    ],
                    'C' => [
                        'subclasses' => [
                            'A'
                        ]
                    ],
                ],
                'C'
            ],
            [
                [
                    'C',
                    'A',
                    'B',
                ],
                [
                    'A' => [
                        'subclasses' => [
                            'C',
                        ]
                    ],
                    'B' => [
                        'subclasses' => [
                            'C',
                            'B',
                        ]
                    ],
                    'C' => [
                        'subclasses' => [
                            'A',
                            'B',
                        ]
                    ],
                ],
                'B'
            ],
        ];
    }

    /**
     * @dataProvider resolveSubclassesRecursiveDataProvider
     * @test
     * @param array $expected
     * @param array $configuration
     * @param string $className
     */
    public function getSubclasses(array $expected, array $configuration, string $className): void
    {
        $classesConfiguration = new ClassesConfiguration($configuration);
        self::assertSame(
            $expected,
            $classesConfiguration->getSubClasses($className)
        );
    }
}
