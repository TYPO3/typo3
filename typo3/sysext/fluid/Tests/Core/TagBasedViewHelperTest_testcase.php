<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package
 * @subpackage
 * @version $Id$
 */

include_once(dirname(__FILE__) . '/Fixtures/TestTagBasedViewHelper.php');

/**
 * Testcase for TagBasedViewHelper
 *
 * @package
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_TagBasedViewHelperTest_testcase extends Tx_Extbase_Base_testcase {

	public function setUp() {
		$this->viewHelper = new Tx_Fluid_TestTagBasedViewHelper();
	}
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_oneTagAttributeIsRenderedCorrectly() {
		$tagBuilderMock = $this->getMock('Tx_Fluid_Core_TagBuilder', array('addAttribute'), array(), '', FALSE);
		$tagBuilderMock->expects($this->once())->method('addAttribute')->with('foo', 'bar');
		$this->viewHelper->injectTagBuilder($tagBuilderMock);

		$this->viewHelper->registerTagAttribute('foo', 'string', 'Description', FALSE);
		$arguments = new Tx_Fluid_Core_ViewHelperArguments(array('foo' => 'bar'));
		$this->viewHelper->setArguments($arguments);
		$this->viewHelper->initialize();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_additionalTagAttributesAreRenderedCorrectly() {
		$tagBuilderMock = $this->getMock('Tx_Fluid_Core_TagBuilder', array('addAttribute'), array(), '', FALSE);
		$tagBuilderMock->expects($this->once())->method('addAttribute')->with('foo', 'bar');
		$this->viewHelper->injectTagBuilder($tagBuilderMock);

		$this->viewHelper->registerTagAttribute('foo', 'string', 'Description', FALSE);
		$arguments = new Tx_Fluid_Core_ViewHelperArguments(array('additionalAttributes' => array('foo' => 'bar')));
		$this->viewHelper->setArguments($arguments);
		$this->viewHelper->initialize();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_standardTagAttributesAreRegistered() {
		$tagBuilderMock = $this->getMock('Tx_Fluid_Core_TagBuilder', array('addAttribute'), array(), '', FALSE);
		$tagBuilderMock->expects($this->at(0))->method('addAttribute')->with('class', 'classAttribute');
		$tagBuilderMock->expects($this->at(1))->method('addAttribute')->with('dir', 'dirAttribute');
		$tagBuilderMock->expects($this->at(2))->method('addAttribute')->with('id', 'idAttribute');
		$tagBuilderMock->expects($this->at(3))->method('addAttribute')->with('lang', 'langAttribute');
		$tagBuilderMock->expects($this->at(4))->method('addAttribute')->with('style', 'styleAttribute');
		$tagBuilderMock->expects($this->at(5))->method('addAttribute')->with('title', 'titleAttribute');
		$tagBuilderMock->expects($this->at(6))->method('addAttribute')->with('accesskey', 'accesskeyAttribute');
		$tagBuilderMock->expects($this->at(7))->method('addAttribute')->with('tabindex', 'tabindexAttribute');
		$this->viewHelper->injectTagBuilder($tagBuilderMock);

		$arguments = new Tx_Fluid_Core_ViewHelperArguments(
			array(
				'class' => 'classAttribute',
				'dir' => 'dirAttribute',
				'id' => 'idAttribute',
				'lang' => 'langAttribute',
				'style' => 'styleAttribute',
				'title' => 'titleAttribute',
				'accesskey' => 'accesskeyAttribute',
				'tabindex' => 'tabindexAttribute'
			)
		);
		$this->viewHelper->setArguments($arguments);
		$this->viewHelper->initializeArguments();
		$this->viewHelper->initialize();
	}
}



?>
