<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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

class Tx_Extbase_Configuration_AbstractConfigurationManager_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 */
	public function settingsCanBeLoaded() {
		$expectedSettings = array(
			'maxItems' => 3,
			'Post' => array(
				'singlePid' => 25,
				'maxItems' => 8,
				'index' => array(
					'maxItems' => 5
				),
			),
			'Comment' => array(
				'content' => array(
					'crop' => 100
					)
				)
			);

		$configurationSource = $this->getMock('Tx_Extbase_Configuration_Source_TypoScriptSource', array('load'));
		$configurationSource->expects($this->any())
			->method('load')
			->with('Tx_Extbase_Foo_Bar')
			->will($this->returnValue($expectedSettings));
		$configurationSources = array();
		$configurationSources[] = $configurationSource;

		$configurationManager = $this->getMock('Tx_Extbase_Configuration_AbstractConfigurationManager', array('loadTypoScriptSetup', 'getContextSpecificFrameworkConfiguration'), array($configurationSources));
		$actualSettings = $configurationManager->getSettings('Tx_Extbase_Foo_Bar');

		$this->assertEquals($expectedSettings, $actualSettings, 'The retrieved settings differs from the retrieved settings.');
	}

	/**
	 * @test
	 */
	public function storagePidFromExtbaseConfigurationOverridesDefaultStoragePid() {
		$typoScriptSetup = array(
			'config.' => array(
				'tx_extbase.' => array(
					'persistence' => array(
						'storagePid' => 'newStoragePid'
					)
				)
			)
		);
		$pluginConfiguration = array();

		$configurationManager = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Configuration_AbstractConfigurationManager'), array('loadTypoScriptSetup', 'getContextSpecificFrameworkConfiguration'), array(), '', FALSE);
		$configurationManager->expects($this->any())->method('loadTypoScriptSetup')->will($this->returnValue($typoScriptSetup));
		$configurationManager->expects($this->any())->method('getContextSpecificFrameworkConfiguration')->will($this->returnValue(array()));
		$expectedResult = array(
			'persistence' => array(
				'storagePid' => 'newStoragePid',
			),
		);
		$actualResult = $configurationManager->getFrameworkConfiguration($pluginConfiguration);

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getFrameworkConfigurationCorrectlyMergesPluginConfigurationWithExtbaseConfiguration() {
		$typoScriptSetup = array(
			'config.' => array(
				'tx_extbase.' => array(
					'foo' => 'bar',
					'persistence' => array(
						'some' => 'Default',
						'storagePid' => 'shouldBeOverwritten'
					)
				)
			)
		);
		$pluginConfiguration = array(
			'settings' => '< settingsReference',
			'persistence' => '< persistenceReference',
			'view' => '< viewReference',
		);

		$mockTypoScriptParser = $this->getMock('t3lib_TSparser');
		$mockTypoScriptParser->expects($this->at(0))->method('getVal')->with('settingsReference', $typoScriptSetup)->will($this->returnValue(array('', array('resolved' => 'settingsReference'))));
		$mockTypoScriptParser->expects($this->at(1))->method('getVal')->with('persistenceReference', $typoScriptSetup)->will($this->returnValue(array('', array('storagePid' => 'overwritten'))));
		$mockTypoScriptParser->expects($this->at(2))->method('getVal')->with('viewReference', $typoScriptSetup)->will($this->returnValue(array('', array('resolved' => 'viewReference'))));

		$configurationManager = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Configuration_AbstractConfigurationManager'), array('loadTypoScriptSetup', 'getContextSpecificFrameworkConfiguration'), array(), '', FALSE);
		$configurationManager->_set('typoScriptParser', $mockTypoScriptParser);
		$configurationManager->expects($this->any())->method('loadTypoScriptSetup')->will($this->returnValue($typoScriptSetup));
		$configurationManager->expects($this->any())->method('getContextSpecificFrameworkConfiguration')->will($this->returnValue(array()));

		$expectedResult = array(
			'persistence' => array(
				'storagePid' => 'overwritten',
				'some' => 'Default'
			),
			'foo' => 'bar',
			'settings' => array(
				'resolved' => 'settingsReference'
			),
			'view' => array(
				'resolved' => 'viewReference'
			)
		);
		$actualResult = $configurationManager->getFrameworkConfiguration($pluginConfiguration);

		$this->assertEquals($expectedResult, $actualResult);
	}

}
?>
