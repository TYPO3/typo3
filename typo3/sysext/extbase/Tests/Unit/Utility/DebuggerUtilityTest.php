<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case
 */
class DebuggerUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

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

	/**
	 * @test
	 */
	public function varDumpShowsPropertiesOfStdClassObjects() {
		$testObject = new \stdClass();
		$testObject->foo = 'bar';
		$result = $this->debugger->var_dump($testObject, NULL, 8, TRUE, FALSE, TRUE);
		$this->assertRegExp('/foo.*bar/', $result);
	}
}
