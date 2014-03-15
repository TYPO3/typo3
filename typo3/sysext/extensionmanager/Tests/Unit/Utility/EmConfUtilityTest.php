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
 * Test case
 */
class EmConfUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function constructEmConfAddsCommentBlock() {
		$extensionData = array(
			'extKey' => 'key',
			'EM_CONF' => array(),
		);
		$fixture = new \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility();
		$emConf = $fixture->constructEmConf($extensionData);
		$this->assertContains('Extension Manager/Repository config file for ext', $emConf);
	}

	/**
	 * @test
	 */
	public function fixEmConfTransfersOldConflictSettingToNewFormatWithSingleConflictingExtension() {
		$input = array(
			'title' => 'a title',
			'conflicts' => 'foo',
		);
		$expected = array(
			'title' => 'a title',
			'conflicts' => 'foo',
			'constraints' => array(
				'depends' => array(),
				'conflicts' => array(
					'foo' => '',
				),
				'suggests' => array(),
			),
		);
		$fixture = new \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility();
		$this->assertEquals($expected, $fixture->fixEmConf($input));
	}

	/**
	 * @test
	 */
	public function fixEmConfTransfersOldConflictSettingToNewFormatWithTwoConflictingExtensions() {
		$input = array(
			'title' => 'a title',
			'conflicts' => 'foo,bar',
		);
		$expected = array(
			'title' => 'a title',
			'conflicts' => 'foo,bar',
			'constraints' => array(
				'depends' => array(),
				'conflicts' => array(
					'foo' => '',
					'bar' => '',
				),
				'suggests' => array(),
			),
		);
		$fixture = new \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility();
		$this->assertEquals($expected, $fixture->fixEmConf($input));
	}

	/**
	 * @test
	 */
	public function dependencyToStringUnsetsDependencies() {
		$config = array(
			'depends' => array(
				'php' => '5.0',
				'something' => 'foo',
				'anything' => 'bar'
			)
		);
		$expected = 'something,anything';

		/** @var \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility $fixture */
		$fixture = $fixture = new \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility();
		$result = $fixture::dependencyToString($config);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function dependencyToStringDealsWithInvalidInput() {
		$input = array(
			'depends' => 'hello world'
		);
		/** @var \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility $fixture */
		$fixture = $fixture = new \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility();
		$this->assertEquals('', $fixture::dependencyToString($input));
	}
}
