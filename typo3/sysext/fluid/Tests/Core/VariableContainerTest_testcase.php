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
 * @package Fluid
 * @subpackage Tests
 * @version $Id$
 */
/**
 * Testcase for VariableContainer
 *
 * @package Fluid
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_VariableContainerTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer();
	}
	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function tearDown() {
		unset($this->variableContainer);
	}
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function addedObjectsCanBeRetrievedAgain() {
		$object = "StringObject";
		$this->variableContainer->add("variable", $object);
		$this->assertSame($this->variableContainer->get('variable'), $object, 'The retrieved object from the context is not the same as the stored object.');
	}
	
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function addedObjectsExistInArray() {
		$object = "StringObject";
		$this->variableContainer->add("variable", $object);
		$this->assertSame($this->variableContainer->exists('variable'), TRUE, 'The object is reported to not be in the VariableContainer, but it is.');
	}
	
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function addedObjectsExistInAllIdentifiers() {
		$object = "StringObject";
		$this->variableContainer->add("variable", $object);
		$this->assertEquals($this->variableContainer->getAllIdentifiers(), array('variable'), 'Added key is not visible in getAllIdentifiers');
	}
	
	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function contextTakesOnlyArraysInConstructor() {
		new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer("string");
	}
	
	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function duplicateIdentifiersThrowException() {
		$this->variableContainer->add('variable', 'string1');
		$this->variableContainer->add('variable', 'string2');
	}
	
	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function gettingNonexistentValueThrowsException() {
		$this->variableContainer->get('nonexistent');
	}
	
	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function deletingNonexistentValueThrowsException() {
		$this->variableContainer->remove('nonexistent');
	}
	
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function deleteReallyDeletesObjects() {
		$this->variableContainer->add('variable', 'string1');
		$this->variableContainer->remove('variable');
		try {
			$this->variableContainer->get('variable');
		} catch (Tx_Fluid_Core_RuntimeException $e) {}
	}
}



?>
