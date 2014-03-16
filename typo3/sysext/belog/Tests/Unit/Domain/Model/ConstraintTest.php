<?php
namespace TYPO3\CMS\Belog\Tests\Unit\Domain\Model;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Steffen Müller <typo3@t3node.com>
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
 *
 */
class ConstraintTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Belog\Domain\Model\Constraint
	 */
	protected $subject = NULL;

	public function setUp() {
		$this->subject = new \TYPO3\CMS\Belog\Domain\Model\Constraint();
	}

	/**
	 * @test
	 */
	public function setManualDateStartForDateTimeSetsManualDateStart() {
		$date = new \DateTime();
		$this->subject->setManualDateStart($date);

		$this->assertAttributeEquals($date, 'manualDateStart', $this->subject);
	}

	/**
	 * @test
	 */
	public function setManualDateStartForNoArgumentSetsManualDateStart() {
		$this->subject->setManualDateStart();

		$this->assertAttributeEquals(NULL, 'manualDateStart', $this->subject);
	}

	/**
	 * @test
	 */
	public function setManualDateStopForDateTimeSetsManualDateStop() {
		$date = new \DateTime();
		$this->subject->setManualDateStop($date);

		$this->assertAttributeEquals($date, 'manualDateStop', $this->subject);
	}

	/**
	 * @test
	 */
	public function setManualDateStopForNoArgumentSetsManualDateStop() {
		$this->subject->setManualDateStop();

		$this->assertAttributeEquals(NULL, 'manualDateStop', $this->subject);
	}
}
