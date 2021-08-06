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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Model;

use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Extension test
 */
class ExtensionTest extends UnitTestCase
{
    /**
     * Data provider for getCategoryIndexFromStringOrNumberReturnsIndex
     *
     * @return array
     */
    public function getCategoryIndexFromStringOrNumberReturnsIndexDataProvider()
    {
        return [
            'empty string' => [
                '',
                4
            ],
            'existing category string' => [
                'plugin',
                3
            ],
            'not existing category string' => [
                'foo',
                4
            ],
            'string number 3' => [
                '3',
                3
            ],
            'integer 3' => [
                3,
                3
            ],
            'string number not in range -1' => [
                '-1',
                4
            ],
            'integer not in range -1' => [
                -1,
                4
            ],
            'string number not in range 11' => [
                '11',
                4
            ],
            'integer not in range 11' => [
                11,
                4
            ],
            'object' => [
                new \stdClass(),
                4
            ],
            'array' => [
                [],
                4
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getCategoryIndexFromStringOrNumberReturnsIndexDataProvider
     * @param string|int $input Given input
     * @param int $expected Expected result
     */
    public function getCategoryIndexFromStringOrNumberReturnsIndex($input, $expected)
    {
        $extension = new Extension();
        self::assertEquals($expected, $extension->getCategoryIndexFromStringOrNumber($input));
    }
    /**
     * @test
     */
    public function convertDependenciesToObjectsCreatesObjectStorage(): void
    {
        $serializedDependencies = [
            'depends' => [
                'php' => '5.1.0-0.0.0',
                'typo3' => '4.2.0-4.4.99',
                'fn_lib' => ''
            ]
        ];
        $dependencyObjects = Extension::createFromExtensionArray(['key' => 'no-name', 'constraints' => $serializedDependencies])->getDependencies();
        self::assertInstanceOf(\SplObjectStorage::class, $dependencyObjects);
    }

    /**
     * @test
     */
    public function convertDependenciesToObjectsSetsIdentifier(): void
    {
        $serializedDependencies = [
            'depends' => [
                'php' => '5.1.0-0.0.0',
                'typo3' => '4.2.0-4.4.99',
                'fn_lib' => ''
            ]
        ];

        $dependencyObjects = Extension::createFromExtensionArray(['key' => 'no-name', 'constraints' => $serializedDependencies])->getDependencies();
        $identifiers = [];
        foreach ($dependencyObjects as $resultingDependency) {
            $identifiers[] = $resultingDependency->getIdentifier();
        }
        self::assertSame($identifiers, ['php', 'typo3', 'fn_lib']);
    }

    /**
     * @return array
     */
    public function convertDependenciesToObjectSetsVersionDataProvider(): array
    {
        return [
            'everything ok' => [
                [
                    'depends' => [
                        'typo3' => '4.2.0-4.4.99'
                    ]
                ],
                [
                    '4.2.0',
                    '4.4.99'
                ]
            ],
            'empty high value' => [
                [
                    'depends' => [
                        'typo3' => '4.2.0-0.0.0'
                    ]
                ],
                [
                    '4.2.0',
                    ''
                ]
            ],
            'empty low value' => [
                [
                    'depends' => [
                        'typo3' => '0.0.0-4.4.99'
                    ]
                ],
                [
                    '',
                    '4.4.99'
                ]
            ],
            'only one value' => [
                [
                    'depends' => [
                        'typo3' => '4.4.99'
                    ]
                ],
                [
                    '4.4.99',
                    '',
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider convertDependenciesToObjectSetsVersionDataProvider
     * @param array $dependencies
     * @param array $returnValue
     */
    public function convertDependenciesToObjectSetsVersion(array $dependencies, array $returnValue): void
    {
        $serializedDependencies = serialize($dependencies);
        $dependencyObjects = Extension::createFromExtensionArray(['key' => 'no-name', 'constraints' => $serializedDependencies])->getDependencies();
        foreach ($dependencyObjects as $resultingDependency) {
            self::assertSame($returnValue[0], $resultingDependency->getLowestVersion());
            self::assertSame($returnValue[1], $resultingDependency->getHighestVersion());
        }
    }

    /**
     * @test
     */
    public function convertDependenciesToObjectCanDealWithEmptyStringDependencyValues(): void
    {
        $dependencies = [
            'depends' => ''
        ];
        $serializedDependencies = serialize($dependencies);
        $dependencyObjects = Extension::createFromExtensionArray(['key' => 'no-name', 'constraints' => $serializedDependencies])->getDependencies();
        self::assertSame(0, $dependencyObjects->count());
    }

    /**
     * @test
     */
    public function getDistributionImageTest(): void
    {
        $distributionImage = 'https://example.org/path/to/image.png';

        $extension = new Extension();
        $extension->setDistributionImage($distributionImage);

        self::assertEquals(
            $distributionImage,
            $extension->getDistributionImage()
        );
    }
}
