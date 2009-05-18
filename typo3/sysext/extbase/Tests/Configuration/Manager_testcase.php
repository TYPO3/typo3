<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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

class Tx_Extbase_Configuration_Manager_testcase extends Tx_Extbase_Base_testcase {
	
	public function setUp() {
		$this->settings = array(
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
			->will($this->returnValue($this->settings));
		$this->configurationSources = array();
		$this->configurationSources[] = $configurationSource;
	}
	
	/**
	 * @test
	 */
	public function settingsCanBeLoaded() {
		$configurationManager = t3lib_div::makeInstance('Tx_Extbase_Configuration_Manager', $this->configurationSources);
		$settings = $configurationManager->getSettings('Tx_Extbase_Foo_Bar');
		$this->assertEquals($this->settings, $settings, 'The retrieved settings differs from the retrieved settings.');		
	}
	
}
?>