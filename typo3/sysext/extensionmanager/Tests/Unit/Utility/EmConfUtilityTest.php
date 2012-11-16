<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * EmConf utility test
 *
 */
class EmConfUtilityTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * Data Provider for construct em conf tests
	 *
	 * @return array
	 */
	public function constructEmConfDataProvider() {
		return array(
			array(
				array(
					'extKey' => 'enetcache',
					'EM_CONF' => array(
						'title' => 'Plugin cache engine',
						'description' => 'Provides an interface to cache plugin content elements based on 4.3 caching framework',
						'category' => 'Frontend',
						'shy' => 0,
						'version' => '1.0.6',
						'dependencies' => '',
						'conflicts' => '',
						'priority' => '',
						'loadOrder' => '',
						'TYPO3_version' => '4.3.0-0.0.0',
						'PHP_version' => '',
						'module' => '',
						'state' => 'stable',
						'uploadfolder' => 0,
						'createDirs' => '',
						'modify_tables' => '',
						'clearcacheonload' => 0,
						'lockType' => '',
						'author' => 'Firstname Lastname',
						'author_email' => 'test@example.com',
						'author_company' => 'test',
						'CGLcompliance' => NULL,
						'CGLcompliance_note' => NULL
					)
				)
			)
		);
	}

	/**
	 * Tests whether the comment block is added
	 *
	 * @param array $extensionData
	 * @test
	 * @dataProvider constructEmConfDataProvider
	 * @return void
	 */
	public function constructEmConfAddsCommentBlock(array $extensionData) {
		$fileHandlerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\EmConfUtility', array('includeEmConf'));
		$emConf = $fileHandlerMock->_call('constructEmConf', $extensionData);
		$this->assertContains('Extension Manager/Repository config file for ext', $emConf);
	}

}


?>