<?php

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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test for ExtensionModelUtilityTest
 */
class ExtensionModelUtilityTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function convertDependenciesToObjectsCreatesObjectStorage(): void
    {
        $serializedDependencies = serialize([
            'depends' => [
                'php' => '5.1.0-0.0.0',
                'typo3' => '4.2.0-4.4.99',
                'fn_lib' => ''
            ]
        ]);
        /** @var $dependencyUtility \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility */
        $dependencyUtility = new ExtensionModelUtility();
        $objectManagerMock = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $objectManagerMock->method('get')->willReturn(new Dependency());
        $dependencyUtility->injectObjectManager($objectManagerMock);
        $objectStorage = $dependencyUtility->convertDependenciesToObjects($serializedDependencies);
        self::assertInstanceOf(\SplObjectStorage::class, $objectStorage);
    }

    /**
     * @test
     */
    public function convertDependenciesToObjectsSetsIdentifier(): void
    {
        $serializedDependencies = serialize([
            'depends' => [
                'php' => '5.1.0-0.0.0',
                'typo3' => '4.2.0-4.4.99',
                'fn_lib' => ''
            ]
        ]);

        $dependencyUtility = new ExtensionModelUtility();
        $objectManagerMock = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        // ensure we get a new dependency on subsequent calls
        $objectManagerMock->method('get')->willReturnCallback(
            static function () {
                return new Dependency();
            }
        );
        $dependencyUtility->injectObjectManager($objectManagerMock);
        $dependencyObjects = $dependencyUtility->convertDependenciesToObjects($serializedDependencies);
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
        $dependencyUtility = new ExtensionModelUtility();
        $objectManagerMock = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        // ensure we get a new dependency on subsequent calls
        $objectManagerMock->method('get')->willReturnCallback(
            static function () {
                return new Dependency();
            }
        );
        $dependencyUtility->injectObjectManager($objectManagerMock);
        $dependencyObjects = $dependencyUtility->convertDependenciesToObjects($serializedDependencies);
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
        $dependencyObject = (new ExtensionModelUtility())->convertDependenciesToObjects($serializedDependencies);
        self::assertSame(0, $dependencyObject->count());
    }
}
