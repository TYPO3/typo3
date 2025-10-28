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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtensionTest extends UnitTestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function convertDependenciesToObjectsDoesNotFail(): void
    {
        $serializedDependencies = [
            'depends' => [
                'php' => '5.1.0-0.0.0',
                'typo3' => '4.2.0-4.4.99',
                'fn_lib' => '',
            ],
        ];
        Extension::createFromExtensionArray(['key' => 'no-name', 'constraints' => $serializedDependencies])->getDependencies();
    }

    #[Test]
    public function convertDependenciesToObjectsSetsIdentifier(): void
    {
        $serializedDependencies = [
            'depends' => [
                'php' => '5.1.0-0.0.0',
                'typo3' => '4.2.0-4.4.99',
                'fn_lib' => '',
            ],
        ];

        $dependencyObjects = Extension::createFromExtensionArray(['key' => 'no-name', 'constraints' => $serializedDependencies])->getDependencies();
        $identifiers = [];
        foreach ($dependencyObjects as $resultingDependency) {
            $identifiers[] = $resultingDependency->getIdentifier();
        }
        self::assertSame($identifiers, ['php', 'typo3', 'fn_lib']);
    }

    public static function convertDependenciesToObjectSetsVersionDataProvider(): array
    {
        return [
            'everything ok' => [
                [
                    'depends' => [
                        'typo3' => '4.2.0-4.4.99',
                    ],
                ],
                [
                    '4.2.0',
                    '4.4.99',
                ],
            ],
            'empty high value' => [
                [
                    'depends' => [
                        'typo3' => '4.2.0-0.0.0',
                    ],
                ],
                [
                    '4.2.0',
                    '',
                ],
            ],
            'empty low value' => [
                [
                    'depends' => [
                        'typo3' => '0.0.0-4.4.99',
                    ],
                ],
                [
                    '',
                    '4.4.99',
                ],
            ],
            'only one value' => [
                [
                    'depends' => [
                        'typo3' => '4.4.99',
                    ],
                ],
                [
                    '4.4.99',
                    '',
                ],
            ],
        ];
    }

    #[DataProvider('convertDependenciesToObjectSetsVersionDataProvider')]
    #[Test]
    public function convertDependenciesToObjectSetsVersion(array $dependencies, array $returnValue): void
    {
        $serializedDependencies = serialize($dependencies);
        $dependencyObjects = Extension::createFromExtensionArray(['key' => 'no-name', 'constraints' => $serializedDependencies])->getDependencies();
        foreach ($dependencyObjects as $resultingDependency) {
            self::assertSame($returnValue[0], $resultingDependency->getLowestVersion());
            self::assertSame($returnValue[1], $resultingDependency->getHighestVersion());
        }
    }

    #[Test]
    public function convertDependenciesToObjectCanDealWithEmptyStringDependencyValues(): void
    {
        $dependencies = [
            'depends' => '',
        ];
        $serializedDependencies = serialize($dependencies);
        $dependencyObjects = Extension::createFromExtensionArray(['key' => 'no-name', 'constraints' => $serializedDependencies])->getDependencies();
        self::assertCount(0, $dependencyObjects);
    }

    #[Test]
    public function getDistributionImageTest(): void
    {
        $imageUrl = 'https://example.org/path/to/image.png';

        $extension = new Extension();
        $extension->distributionImage = $imageUrl;

        self::assertEquals(
            $imageUrl,
            $extension->distributionImage
        );
    }

    #[Test]
    public function getDistributionWelcomeImageTest(): void
    {
        $imageUrl = 'https://example.org/path/to/image.png';

        $extension = new Extension();
        $extension->distributionWelcomeImage = $imageUrl;

        self::assertEquals(
            $imageUrl,
            $extension->distributionWelcomeImage
        );
    }
}
