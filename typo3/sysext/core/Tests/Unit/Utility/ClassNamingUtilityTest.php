<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Extbase Team
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for class \TYPO3\CMS\Core\Utility\ClassNamingUtility
 */
class ClassNamingUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * DataProvider for translateModelNameToRepositoryName
	 * and translateRepositoryNameToModelName
	 * and translateModelNameToValidatorName
	 *
	 * @return array
	 */
	public function repositoryAndModelClassNames() {
		return array(
			array(
				'Tx_BlogExample_Domain_Repository_BlogRepository',
				'Tx_BlogExample_Domain_Model_Blog',
				'Tx_BlogExample_Domain_Validator_BlogValidator'
			),
			array(
				' _Domain_Repository_Content_PageRepository',
				' _Domain_Model_Content_Page',
				' _Domain_Validator_Content_PageValidator'
			),
			array(
				'Tx_RepositoryExample_Domain_Repository_SomeModelRepository',
				'Tx_RepositoryExample_Domain_Model_SomeModel',
				'Tx_RepositoryExample_Domain_Validator_SomeModelValidator'
			),
			array(
				'Tx_RepositoryExample_Domain_Repository_RepositoryRepository',
				'Tx_RepositoryExample_Domain_Model_Repository',
				'Tx_RepositoryExample_Domain_Validator_RepositoryValidator'
			),
			array(
				'Tx_Repository_Domain_Repository_RepositoryRepository',
				'Tx_Repository_Domain_Model_Repository',
				'Tx_Repository_Domain_Validator_RepositoryValidator'
			),
			array(
				'Tx_ModelCollection_Domain_Repository_ModelRepository',
				'Tx_ModelCollection_Domain_Model_Model',
				'Tx_ModelCollection_Domain_Validator_ModelValidator'
			),
			array(
				'Tx_Model_Domain_Repository_ModelRepository',
				'Tx_Model_Domain_Model_Model',
				'Tx_Model_Domain_Validator_ModelValidator'
			),
			array(
				'VENDOR\\EXT\\Domain\\Repository\\BlogRepository',
				'VENDOR\\EXT\\Domain\\Model\\Blog',
				'VENDOR\\EXT\\Domain\\Validator\\BlogValidator'
			),
			array(
				'VENDOR\\EXT\\Domain\\Repository\\_PageRepository',
				'VENDOR\\EXT\\Domain\\Model\\_Page',
				'VENDOR\\EXT\\Domain\\Validator\\_PageValidator'
			),
			array(
				'VENDOR\\Repository\\Domain\\Repository\\SomeModelRepository',
				'VENDOR\\Repository\\Domain\\Model\\SomeModel',
				'VENDOR\\Repository\\Domain\\Validator\\SomeModelValidator'
			),
			array(
				'VENDOR\\EXT\\Domain\\Repository\\RepositoryRepository',
				'VENDOR\\EXT\\Domain\\Model\\Repository',
				'VENDOR\\EXT\\Domain\\Validator\\RepositoryValidator'
			),
			array(
				'VENDOR\\Repository\\Domain\\Repository\\RepositoryRepository',
				'VENDOR\\Repository\\Domain\\Model\\Repository',
				'VENDOR\\Repository\\Domain\\Validator\\RepositoryValidator'
			),
			array(
				'VENDOR\\ModelCollection\\Domain\\Repository\\ModelRepository',
				'VENDOR\\ModelCollection\\Domain\\Model\\Model',
				'VENDOR\\ModelCollection\\Domain\\Validator\\ModelValidator'
			),
			array(
				'VENDOR\\Model\\Domain\\Repository\\ModelRepository',
				'VENDOR\\Model\\Domain\\Model\\Model',
				'VENDOR\\Model\\Domain\\Validator\\ModelValidator'
			),
		);
	}

	/**
	 * @dataProvider repositoryAndModelClassNames
	 * @param string $expectedRepositoryName
	 * @param string $modelName
	 * @param string $dummyValidatorName not needed here - just a dummy to be able to cleanly use the same dataprovider
	 * @test
	 */
	public function translateModelNameToRepositoryName($expectedRepositoryName, $modelName, $dummyValidatorName) {
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
	public function translateRepositoryNameToModelName($repositoryName, $expectedModelName, $dummyValidatorName) {
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
	public function translateModelNameToValidatorName($repositoryName, $modelName, $expectedValidatorName) {
		$translatedModelName = \TYPO3\CMS\Core\Utility\ClassNamingUtility::translateModelNameToValidatorName($modelName);
		$this->assertSame($expectedValidatorName, $translatedModelName);
	}

	/**
	 * DataProvider for explodeObjectControllerName
	 *
	 * @return array
	 */
	public function controllerObjectNamesAndMatches() {
		return array(
			array(
				'TYPO3\\CMS\\Ext\\Controller\\FooController',
				array(
					'vendorName' => 'TYPO3\\CMS',
					'extensionName' => 'Ext',
					'subpackageKey' => '',
					'controllerName' => 'Foo',
				)
			),
			array(
				'TYPO3\\CMS\\Ext\\Command\\FooCommandController',
				array(
					'vendorName' => 'TYPO3\\CMS',
					'extensionName' => 'Ext',
					'subpackageKey' => '',
					'controllerName' => 'FooCommand',
				)
			),
			array(
				'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\Controller\\PaginateController',
				array(
					'vendorName' => 'TYPO3\\CMS',
					'extensionName' => 'Fluid',
					'subpackageKey' => 'ViewHelpers\\Widget',
					'controllerName' => 'Paginate',
				)
			),
			array(
				'VENDOR\\Ext\\Controller\\FooController',
				array(
					'vendorName' => 'VENDOR',
					'extensionName' => 'Ext',
					'subpackageKey' => '',
					'controllerName' => 'Foo',
				)
			),
			array(
				'VENDOR\\Ext\\Command\\FooCommandController',
				array(
					'vendorName' => 'VENDOR',
					'extensionName' => 'Ext',
					'subpackageKey' => '',
					'controllerName' => 'FooCommand',
				)
			),
			array(
				'VENDOR\\Ext\\ViewHelpers\\Widget\\Controller\\FooController',
				array(
					'vendorName' => 'VENDOR',
					'extensionName' => 'Ext',
					'subpackageKey' => 'ViewHelpers\\Widget',
					'controllerName' => 'Foo',
				)
			),
			// Oldschool
			array(
				'Tx_Ext_Controller_FooController',
				array(
					'vendorName' => NULL,
					'extensionName' => 'Ext',
					'subpackageKey' => '',
					'controllerName' => 'Foo',
				)
			),
			array(
				'Tx_Ext_Command_FooCommandController',
				array(
					'vendorName' => NULL,
					'extensionName' => 'Ext',
					'subpackageKey' => '',
					'controllerName' => 'FooCommand',
				)
			),
			array(
				'Tx_Fluid_ViewHelpers_Widget_Controller_PaginateController',
				array(
					'vendorName' => NULL,
					'extensionName' => 'Fluid',
					'subpackageKey' => 'ViewHelpers_Widget',
					'controllerName' => 'Paginate',
				)
			),
		);
	}

	/**
	 * @dataProvider controllerObjectNamesAndMatches
	 *
	 * @param string $controllerObjectName
	 * @param array $expectedMatches
	 * @test
	 */
	public function explodeObjectControllerName($controllerObjectName, $expectedMatches) {
		$matches = \TYPO3\CMS\Core\Utility\ClassNamingUtility::explodeObjectControllerName($controllerObjectName);

		$actualMatches = array(
			'vendorName' => $matches['vendorName'],
			'extensionName' => $matches['extensionName'],
			'subpackageKey' => $matches['subpackageKey'],
			'controllerName' => $matches['controllerName'],
		);

		$this->assertSame($expectedMatches, $actualMatches);
	}
}

?>
