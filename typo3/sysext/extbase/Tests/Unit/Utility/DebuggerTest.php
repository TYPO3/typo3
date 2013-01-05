<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2012 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * Testcase for the Tx_Extbase_Tests_Unit_Utility_DebuggerTest class.
 *
 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
 */
class  Tx_Extbase_Tests_Unit_Utility_DebuggerTest extends tx_phpunit_testcase {

	/**
	 * @var Tx_Extbase_Utility_Debugger
	 */
	protected $debugger;

	public function setUp() {
		$this->debugger = $this->getAccessibleMock('Tx_Extbase_Utility_Debugger', array('dummy'));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function debuggerRewindsInstancesOfIterator() {
		/** @var $objectStorage Tx_Extbase_Persistence_ObjectStorage */
		$objectStorage = $this->getMock('Tx_Extbase_Persistence_ObjectStorage', array('dummy'));
		for ($i = 0; $i < 5; $i++) {
			$obj = new StdClass();
			$obj->property = $i;
			$objectStorage->attach($obj);
		}
		$this->debugger->var_dump($objectStorage, NULL, 8, TRUE, TRUE);
		$this->assertTrue($objectStorage->valid());
	}

	/**
	 * @test
	 * @author Anja Leichsenring <anja.leichsenring@typo3.org>
	 */
	public function debuggerDoesNotRewindInstanceOfArrayAccess() {

		$parameters = array();
		for ($i = 0; $i < 5; $i++) {
			$argument = new Tx_Extbase_MVC_Controller_Argument('argument_' . $i, 'integer');
			$parameters[$i] = $argument;
		}

		/** @var $arguments Tx_Fluid_Core_ViewHelper_Arguments */
		$arguments = $this->getMock('Tx_Fluid_Core_ViewHelper_Arguments', array('dummy'), array('arguments' => $parameters));

		$arguments->expects($this->never())->method('rewind');
		$this->debugger->var_dump($arguments, NULL, 8, TRUE, TRUE);
	}
}

?>