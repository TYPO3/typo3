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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\PaginateController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\ClassNamingUtility
 */
class ClassNamingUtilityTest extends UnitTestCase
{

    /**
     * DataProvider for translateModelNameToRepositoryName
     * and translateRepositoryNameToModelName
     *
     * @return array
     */
    public function repositoryAndModelClassNames(): array
    {
        return [
            [
                'VENDOR\\EXT\\Domain\\Repository\\BlogRepository',
                'VENDOR\\EXT\\Domain\\Model\\Blog',
            ],
            [
                'VENDOR\\EXT\\Domain\\Repository\\_PageRepository',
                'VENDOR\\EXT\\Domain\\Model\\_Page',
            ],
            [
                'VENDOR\\Repository\\Domain\\Repository\\SomeModelRepository',
                'VENDOR\\Repository\\Domain\\Model\\SomeModel',
            ],
            [
                'VENDOR\\EXT\\Domain\\Repository\\RepositoryRepository',
                'VENDOR\\EXT\\Domain\\Model\\Repository',
            ],
            [
                'VENDOR\\Repository\\Domain\\Repository\\RepositoryRepository',
                'VENDOR\\Repository\\Domain\\Model\\Repository',
            ],
            [
                'VENDOR\\ModelCollection\\Domain\\Repository\\ModelRepository',
                'VENDOR\\ModelCollection\\Domain\\Model\\Model',
            ],
            [
                'VENDOR\\Model\\Domain\\Repository\\ModelRepository',
                'VENDOR\\Model\\Domain\\Model\\Model',
            ],
        ];
    }

    /**
     * @dataProvider repositoryAndModelClassNames
     * @param string $expectedRepositoryName
     * @param string $modelName
     * @test
     */
    public function translateModelNameToRepositoryName($expectedRepositoryName, $modelName)
    {
        $translatedRepositoryName = ClassNamingUtility::translateModelNameToRepositoryName($modelName);
        self::assertSame($expectedRepositoryName, $translatedRepositoryName);
    }

    /**
     * @dataProvider repositoryAndModelClassNames
     * @param string $repositoryName
     * @param string $expectedModelName
     * @test
     */
    public function translateRepositoryNameToModelName($repositoryName, $expectedModelName)
    {
        $translatedModelName = ClassNamingUtility::translateRepositoryNameToModelName($repositoryName);
        self::assertSame($expectedModelName, $translatedModelName);
    }

    /**
     * DataProvider for explodeObjectControllerName
     *
     * @return array
     */
    public function controllerObjectNamesAndMatches(): array
    {
        return [
            [
                'TYPO3\\CMS\\Ext\\Controller\\FooController',
                [
                    'vendorName' => 'TYPO3\\CMS',
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'Foo',
                ]
            ],
            [
                'TYPO3\\CMS\\Ext\\Command\\FooCommandController',
                [
                    'vendorName' => 'TYPO3\\CMS',
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'FooCommand',
                ]
            ],
            [
                PaginateController::class,
                [
                    'vendorName' => 'TYPO3\\CMS',
                    'extensionName' => 'Fluid',
                    'subpackageKey' => 'ViewHelpers\\Widget',
                    'controllerName' => 'Paginate',
                ]
            ],
            [
                'VENDOR\\Ext\\Controller\\FooController',
                [
                    'vendorName' => 'VENDOR',
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'Foo',
                ]
            ],
            [
                'VENDOR\\Ext\\Command\\FooCommandController',
                [
                    'vendorName' => 'VENDOR',
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'FooCommand',
                ]
            ],
            [
                'VENDOR\\Ext\\ViewHelpers\\Widget\\Controller\\FooController',
                [
                    'vendorName' => 'VENDOR',
                    'extensionName' => 'Ext',
                    'subpackageKey' => 'ViewHelpers\\Widget',
                    'controllerName' => 'Foo',
                ]
            ],
        ];
    }

    /**
     * @dataProvider controllerObjectNamesAndMatches
     *
     * @param string $controllerObjectName
     * @param array $expectedMatches
     * @test
     */
    public function explodeObjectControllerName($controllerObjectName, $expectedMatches)
    {
        $actualMatches = ClassNamingUtility::explodeObjectControllerName($controllerObjectName);
        self::assertSame($expectedMatches, $actualMatches);
    }
}
