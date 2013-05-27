<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Markus Günther <mail@markus-guenther.de>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for \TYPO3\CMS\Extbase\Domain\Model\FileMount.
 *
 * @author Markus Günther <mail@markus-guenther.de>
 * @api
 */
class FileMountTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\FileMount
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Extbase\Domain\Model\FileMount();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function getTitleInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getTitle());
	}

	/**
	 * @test
	 */
	public function setTitleSetsTitle() {
		$title = 'foobar mount';
		$this->fixture->setTitle($title);
		$this->assertSame($title, $this->fixture->getTitle());
	}

	/**
	 * @test
	 */
	public function getPathInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getPath());
	}

	/**
	 * @test
	 */
	public function setPathSetsPath() {
		$path = 'foo/bar/';
		$this->fixture->setPath($path);
		$this->assertSame($path, $this->fixture->getPath());
	}

	/**
	 * @test
	 */
	public function getIsAbsolutePathInitiallyReturnsFalse() {
		$this->assertFalse($this->fixture->getIsAbsolutePath());
	}

	/**
	 * @test
	 */
	public function setIsAbsolutePathCanSetBaseIsAbsolutePathToTrue() {
		$this->fixture->setIsAbsolutePath(TRUE);
		$this->assertTrue($this->fixture->getIsAbsolutePath());
	}
}

?>