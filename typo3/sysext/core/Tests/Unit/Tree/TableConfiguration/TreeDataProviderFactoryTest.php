<?php
namespace TYPO3\CMS\Core\Tests\Tree\TableConfiguration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Helmut Hummel <helmut.hummel@typo3.org>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory
 */
class TreeDataProviderFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory();
		$GLOBALS['TCA'] = array();
		$GLOBALS['TCA']['foo'] = array();
		$GLOBALS['TCA']['foo']['ctrl'] = array();
		$GLOBALS['TCA']['foo']['ctrl']['label'] = 'labelFoo';
		$GLOBALS['TCA']['foo']['columns'] = array();
	}

	/**
	 * @return array
	 */
	public function invalidConfigurationDataProvider() {
		return array(
			'Empty Configuration' => array(array()),
			'File Configuration' => array(array(
				'internal_type' => 'file',
				'treeConfig' => array(),
			)),
			'Unknown Type' => array(array(
				'internal_type' => 'foo',
				'treeConfig' => array(),
			)),
			'No foreign table' => array(array(
				'internal_type' => 'db',
				'treeConfig' => array(),
			)),
			'No tree configuration' => array(array(
				'internal_type' => 'db',
				'foreign_table' => 'foo',
			)),
			'Tree configuration not array' => array(array(
				'internal_type' => 'db',
				'foreign_table' => 'foo',
				'treeConfig' => 'bar',
			)),
			'Tree configuration missing childer and parent field' => array(array(
				'internal_type' => 'db',
				'foreign_table' => 'foo',
				'treeConfig' => array(),
			)),
		);
	}

	/**
	 * @param array $tcaConfiguration
	 * @test
	 * @dataProvider invalidConfigurationDataProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function factoryThrowsExceptionIfInvalidConfigurationIsGiven(array $tcaConfiguration) {
		$this->fixture->getDataProvider($tcaConfiguration, 'foo', 'bar', array('uid' => 1));
	}

	/**
	 * @test
	 */
	public function configuredDataProviderClassIsInstantiated() {
		$dataProviderMockClassName = uniqid('tx_coretest_tree_data_provider');
		eval('class ' . $dataProviderMockClassName . ' {
			function __construct($configuration) {
			}
		}');

		$tcaConfiguration = array('treeConfig' => array('dataProvider' => $dataProviderMockClassName), 'internal_type' => 'foo');
		$dataProvider = $this->fixture->getDataProvider($tcaConfiguration, 'foo', 'bar', array('uid' => 1));

		$this->assertInstanceOf($dataProviderMockClassName, $dataProvider);
	}

	/**
	 * @test
	 */
	public function configuredDataProviderClassIsInstantiatedWithTcaConfigurationInConstructor() {
		$dataProviderMockClassName = uniqid('tx_coretest_tree_data_provider');
		$tcaConfiguration = array('treeConfig' => array('dataProvider' => $dataProviderMockClassName), 'internal_type' => 'foo');
		$classCode = 'class ' . $dataProviderMockClassName . ' {
			function __construct($configuration) {
				if (!is_array($configuration)) throw new Exception(\'Failed asserting that the contructor arguments are an array\');
				if ($configuration !== ' . var_export($tcaConfiguration, TRUE) . ') throw new Exception(\'Failed asserting that the contructor arguments are correctly passed\');
			}
		}';
		eval($classCode);
		$dataProvider = $this->fixture->getDataProvider($tcaConfiguration, 'foo', 'bar', array('uid' => 1));

		$this->assertInstanceOf($dataProviderMockClassName, $dataProvider);
	}
}

?>