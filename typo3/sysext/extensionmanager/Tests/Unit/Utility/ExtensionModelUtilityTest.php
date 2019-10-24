<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

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
    public function convertDependenciesToObjectsCreatesObjectStorage()
    {
        $serializedDependencies = serialize([
            'depends' => [
                'php' => '5.1.0-0.0.0',
                'typo3' => '4.2.0-4.4.99',
                'fn_lib' => ''
            ]
        ]);
        /** @var $dependencyUtility \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility */
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility::class, ['dummy']);
        $objectManagerMock = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $dependencyModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['dummy']);
        $objectManagerMock->expects(self::any())->method('get')->will(self::returnValue($dependencyModelMock));
        $dependencyUtility->_set('objectManager', $objectManagerMock);
        $objectStorage = $dependencyUtility->convertDependenciesToObjects($serializedDependencies);
        self::assertTrue($objectStorage instanceof \SplObjectStorage);
    }

    /**
     * @test
     */
    public function convertDependenciesToObjectsSetsIdentifier()
    {
        $serializedDependencies = serialize([
            'depends' => [
                'php' => '5.1.0-0.0.0',
                'typo3' => '4.2.0-4.4.99',
                'fn_lib' => ''
            ]
        ]);
        /** @var $dependencyUtility \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility */
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility::class, ['dummy']);
        $objectManagerMock = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $dependencyModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['setIdentifier']);
        $objectManagerMock->expects(self::any())->method('get')->will(self::returnValue($dependencyModelMock));
        $dependencyUtility->_set('objectManager', $objectManagerMock);
        $dependencyModelMock->expects(self::at(0))->method('setIdentifier')->with('php');
        $dependencyModelMock->expects(self::at(1))->method('setIdentifier')->with('typo3');
        $dependencyModelMock->expects(self::at(2))->method('setIdentifier')->with('fn_lib');
        $dependencyUtility->convertDependenciesToObjects($serializedDependencies);
    }

    /**
     * @return array
     */
    public function convertDependenciesToObjectSetsVersionDataProvider()
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
    public function convertDependenciesToObjectSetsVersion(array $dependencies, array $returnValue)
    {
        $serializedDependencies = serialize($dependencies);
        /** @var $dependencyUtility \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility */
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility::class, ['dummy']);
        $objectManagerMock = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $dependencyModelMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['setHighestVersion', 'setLowestVersion']);
        $objectManagerMock->expects(self::any())->method('get')->will(self::returnValue($dependencyModelMock));
        $dependencyUtility->_set('objectManager', $objectManagerMock);
        $dependencyModelMock->expects(self::atLeastOnce())->method('setLowestVersion')->with(self::identicalTo($returnValue[0]));
        $dependencyModelMock->expects(self::atLeastOnce())->method('setHighestVersion')->with(self::identicalTo($returnValue[1]));
        $dependencyUtility->convertDependenciesToObjects($serializedDependencies);
    }

    /**
     * @test
     */
    public function convertDependenciesToObjectCanDealWithEmptyStringDependencyValues()
    {
        $dependencies = [
            'depends' => ''
        ];
        $serializedDependencies = serialize($dependencies);
        /** @var $dependencyUtility \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility */
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility::class, ['dummy']);
        $dependencyObject = $dependencyUtility->convertDependenciesToObjects($serializedDependencies);
        self::assertSame(0, $dependencyObject->count());
    }
}
