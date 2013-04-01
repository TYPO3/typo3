<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
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
 * Testcase for the \TYPO3\CMS\Extbase\Utility\DebuggerUtility class.
 *
 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
 */
class DebuggerUtilityTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Utility\DebuggerUtility
	 */
	protected $debugger;

	public function setUp() {
		$this->debugger = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Utility\\DebuggerUtility', array('dummy'));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function debuggerRewindsInstancesOfIterator() {
		/** @var $objectStorage \TYPO3\CMS\Extbase\Persistence\ObjectStorage */
		$objectStorage = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', array('dummy'));
		for ($i = 0; $i < 5; $i++) {
			$obj = new \StdClass();
			$obj->property = $i;
			$objectStorage->attach($obj);
		}
		$this->debugger->var_dump($objectStorage, NULL, 8, TRUE, FALSE, TRUE);
		$this->assertTrue($objectStorage->valid());
	}

	/**
	 * @test
	 * @author Anja Leichsenring <anja.leichsenring@typo3.org>
	 */
	public function debuggerDoesNotRewindInstanceOfArrayAccess() {

		$parameters = array();
		for ($i = 0; $i < 5; $i++) {
			$argument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('argument_' . $i, 'integer');
			$parameters[$i] = $argument;
		}

		/** @var $arguments \TYPO3\CMS\Fluid\Core\ViewHelper\Arguments */
		$arguments = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Arguments', array('dummy'), array('arguments' => $parameters));

		$arguments->expects($this->never())->method('rewind');
		$this->debugger->var_dump($arguments, NULL, 8, TRUE, FALSE, TRUE);
	}
}

?>