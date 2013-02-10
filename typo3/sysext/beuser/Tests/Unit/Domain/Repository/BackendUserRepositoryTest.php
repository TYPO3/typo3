<?php
namespace TYPO3\CMS\Beuser\Tests\Unit\Domain\Repository;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Felix Kopp <felix-source@phorax.com>
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
 * Testcase for the \TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository class.
 *
 * @author Felix Kopp <felix-source@phorax.com>
 */
class BackendUserRepositoryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository
	 */
	protected $fixture;

	public function setUp() {
		$this->objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$this->fixture = new \TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository($this->objectManager);
	}

	public function tearDown() {
		unset($this->fixture);
		unset($this->objectManager);
	}

	/**
	 * @test
	 */
	public function classCanBeInstantiated() {

	}

}

?>