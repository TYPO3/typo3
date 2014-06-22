<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Model;

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
class FileMountTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\FileMount
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Extbase\Domain\Model\FileMount();
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
