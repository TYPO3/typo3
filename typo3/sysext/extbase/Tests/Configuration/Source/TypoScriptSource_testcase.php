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

class Tx_Extbase_Configuration_Source_TypoScriptSource_testcase extends Tx_Extbase_Base_testcase {
	
	/**
	 * @test
	 */	
	public function postProcessSettingsRemovesTrailingDots() {		
		$typoScriptSettings = array(
			'10' => 'TEXT',
			'10.' => array(
				'value' => 'Hello World!',
				'foo.' => array(
					'bar' => 5,
					),
				),
			);
		$expectedSettings = array(
			'10' => array(
				'value' => 'Hello World!',
				'foo' => array(
					'bar' => 5,					
					),
				'_typoScriptNodeValue' => 'TEXT',
				),
			);
		$configurationSource = t3lib_div::makeInstance($this->buildAccessibleProxy('Tx_Extbase_Configuration_Source_TypoScriptSource'));
		$processedSettings = $configurationSource->_callRef('postProcessSettings', $typoScriptSettings);

		$this->assertEquals($expectedSettings, $processedSettings);		
	}
	
	/**
	 * @test
	 */
	public function postProcessSettingsRemovesTrailingDotsWithChangedOrderInTheTypoScriptArray() {		
		$typoScriptSettings = array(
			'10.' => array(
				'value' => 'Hello World!',
				'foo.' => array(
					'bar' => 5,
					),
				),
			'10' => 'TEXT', // This line was moved down
			);
		$expectedSettings = array(
			'10' => array(
				'value' => 'Hello World!',
				'foo' => array(
					'bar' => 5,					
					),
				'_typoScriptNodeValue' => 'TEXT',
				),
			);
		$configurationSource = t3lib_div::makeInstance($this->buildAccessibleProxy('Tx_Extbase_Configuration_Source_TypoScriptSource'));
		$processedSettings = $configurationSource->_callRef('postProcessSettings', $typoScriptSettings);

		$this->assertEquals($expectedSettings, $processedSettings);		
	}
	
}
?>