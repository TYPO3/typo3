<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

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

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\ClassNamingUtility
 */
class ClassNamingUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * DataProvider for translateModelNameToRepositoryName
     * and translateRepositoryNameToModelName
     * and translateModelNameToValidatorName
     *
     * @return array
     */
    public function repositoryAndModelClassNames()
    {
        return [
            [
                'Tx_BlogExample_Domain_Repository_BlogRepository',
                'Tx_BlogExample_Domain_Model_Blog',
                'Tx_BlogExample_Domain_Validator_BlogValidator'
            ],
            [
                ' _Domain_Repository_Content_PageRepository',
                ' _Domain_Model_Content_Page',
                ' _Domain_Validator_Content_PageValidator'
            ],
            [
                'Tx_RepositoryExample_Domain_Repository_SomeModelRepository',
                'Tx_RepositoryExample_Domain_Model_SomeModel',
                'Tx_RepositoryExample_Domain_Validator_SomeModelValidator'
            ],
            [
                'Tx_RepositoryExample_Domain_Repository_RepositoryRepository',
                'Tx_RepositoryExample_Domain_Model_Repository',
                'Tx_RepositoryExample_Domain_Validator_RepositoryValidator'
            ],
            [
                'Tx_Repository_Domain_Repository_RepositoryRepository',
                'Tx_Repository_Domain_Model_Repository',
                'Tx_Repository_Domain_Validator_RepositoryValidator'
            ],
            [
                'Tx_ModelCollection_Domain_Repository_ModelRepository',
                'Tx_ModelCollection_Domain_Model_Model',
                'Tx_ModelCollection_Domain_Validator_ModelValidator'
            ],
            [
                'Tx_Model_Domain_Repository_ModelRepository',
                'Tx_Model_Domain_Model_Model',
                'Tx_Model_Domain_Validator_ModelValidator'
            ],
            [
                'VENDOR\\EXT\\Domain\\Repository\\BlogRepository',
                'VENDOR\\EXT\\Domain\\Model\\Blog',
                'VENDOR\\EXT\\Domain\\Validator\\BlogValidator'
            ],
            [
                'VENDOR\\EXT\\Domain\\Repository\\_PageRepository',
                'VENDOR\\EXT\\Domain\\Model\\_Page',
                'VENDOR\\EXT\\Domain\\Validator\\_PageValidator'
            ],
            [
                'VENDOR\\Repository\\Domain\\Repository\\SomeModelRepository',
                'VENDOR\\Repository\\Domain\\Model\\SomeModel',
                'VENDOR\\Repository\\Domain\\Validator\\SomeModelValidator'
            ],
            [
                'VENDOR\\EXT\\Domain\\Repository\\RepositoryRepository',
                'VENDOR\\EXT\\Domain\\Model\\Repository',
                'VENDOR\\EXT\\Domain\\Validator\\RepositoryValidator'
            ],
            [
                'VENDOR\\Repository\\Domain\\Repository\\RepositoryRepository',
                'VENDOR\\Repository\\Domain\\Model\\Repository',
                'VENDOR\\Repository\\Domain\\Validator\\RepositoryValidator'
            ],
            [
                'VENDOR\\ModelCollection\\Domain\\Repository\\ModelRepository',
                'VENDOR\\ModelCollection\\Domain\\Model\\Model',
                'VENDOR\\ModelCollection\\Domain\\Validator\\ModelValidator'
            ],
            [
                'VENDOR\\Model\\Domain\\Repository\\ModelRepository',
                'VENDOR\\Model\\Domain\\Model\\Model',
                'VENDOR\\Model\\Domain\\Validator\\ModelValidator'
            ],
        ];
    }

    /**
     * @dataProvider repositoryAndModelClassNames
     * @param string $expectedRepositoryName
     * @param string $modelName
     * @param string $dummyValidatorName not needed here - just a dummy to be able to cleanly use the same dataprovider
     * @test
     */
    public function translateModelNameToRepositoryName($expectedRepositoryName, $modelName, $dummyValidatorName)
    {
        $translatedRepositoryName = \TYPO3\CMS\Core\Utility\ClassNamingUtility::translateModelNameToRepositoryName($modelName);
        $this->assertSame($expectedRepositoryName, $translatedRepositoryName);
    }

    /**
     * @dataProvider repositoryAndModelClassNames
     * @param string $repositoryName
     * @param string $expectedModelName
     * @param string $dummyValidatorName not needed here - just a dummy to be able to use the same dataprovider
     * @test
     */
    public function translateRepositoryNameToModelName($repositoryName, $expectedModelName, $dummyValidatorName)
    {
        $translatedModelName = \TYPO3\CMS\Core\Utility\ClassNamingUtility::translateRepositoryNameToModelName($repositoryName);
        $this->assertSame($expectedModelName, $translatedModelName);
    }

    /**
     * @dataProvider repositoryAndModelClassNames
     * @param string $repositoryName
     * @param string $modelName
     * @param string $expectedValidatorName
     * @test
     */
    public function translateModelNameToValidatorName($repositoryName, $modelName, $expectedValidatorName)
    {
        $translatedModelName = \TYPO3\CMS\Core\Utility\ClassNamingUtility::translateModelNameToValidatorName($modelName);
        $this->assertSame($expectedValidatorName, $translatedModelName);
    }

    /**
     * DataProvider for explodeObjectControllerName
     *
     * @return array
     */
    public function controllerObjectNamesAndMatches()
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
                \TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\PaginateController::class,
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
            // Oldschool
            [
                'Tx_Ext_Controller_FooController',
                [
                    'vendorName' => null,
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'Foo',
                ]
            ],
            [
                'Tx_Ext_Command_FooCommandController',
                [
                    'vendorName' => null,
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'FooCommand',
                ]
            ],
            [
                'Tx_Fluid_ViewHelpers_Widget_Controller_PaginateController',
                [
                    'vendorName' => null,
                    'extensionName' => 'Fluid',
                    'subpackageKey' => 'ViewHelpers_Widget',
                    'controllerName' => 'Paginate',
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
        $matches = \TYPO3\CMS\Core\Utility\ClassNamingUtility::explodeObjectControllerName($controllerObjectName);

        $actualMatches = [
            'vendorName' => $matches['vendorName'],
            'extensionName' => $matches['extensionName'],
            'subpackageKey' => $matches['subpackageKey'],
            'controllerName' => $matches['controllerName'],
        ];

        $this->assertSame($expectedMatches, $actualMatches);
    }
}
