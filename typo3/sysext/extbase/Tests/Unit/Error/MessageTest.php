<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Error;

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
 * Testcase for the Message object
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class MessageTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function theConstructorSetsTheMessageMessageCorrectly() {
		$messageMessage = 'The message';
		$error = new \TYPO3\CMS\Extbase\Error\Message($messageMessage, 0);
		$this->assertEquals($messageMessage, $error->getMessage());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function theConstructorSetsTheMessageCodeCorrectly() {
		$messageCode = 123456789;
		$error = new \TYPO3\CMS\Extbase\Error\Message('', $messageCode);
		$this->assertEquals($messageCode, $error->getCode());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function theConstructorSetsTheMessageArgumentsCorrectly() {
		$messageArguments = array('foo', 'bar');
		$error = new \TYPO3\CMS\Extbase\Error\Message('', 1, $messageArguments);
		$this->assertEquals($messageArguments, $error->getArguments());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function theConstructorSetsTheMessageTitleCorrectly() {
		$messageTitle = 'Title';
		$error = new \TYPO3\CMS\Extbase\Error\Message('', 1, array(), $messageTitle);
		$this->assertEquals($messageTitle, $error->getTitle());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function renderRendersCorrectlyWithoutArguments() {
		$error = new \TYPO3\CMS\Extbase\Error\Message('Message', 1);
		$this->assertEquals('Message', $error->render());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function renderRendersCorrectlyWithArguments() {
		$error = new \TYPO3\CMS\Extbase\Error\Message('Foo is %s and Bar is %s', 1, array('baz', 'qux'));
		$this->assertEquals('Foo is baz and Bar is qux', $error->render());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function toStringCallsRender() {
		$error = new \TYPO3\CMS\Extbase\Error\Message('Foo is %s and Bar is %s', 1, array('baz', 'qux'));
		$this->assertEquals('Foo is baz and Bar is qux', $error);
	}
}

?>