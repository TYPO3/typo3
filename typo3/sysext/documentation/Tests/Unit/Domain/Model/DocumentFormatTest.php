<?php
namespace TYPO3\CMS\Documentation\Tests\Unit\Domain\Model;

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
class DocumentFormatTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat();
	}

	/**
	 * @test
	 */
	public function setFormatForStringSetsFormat() {
		$this->fixture->setFormat('Conceived at T3DD13');

		$this->assertSame(
			'Conceived at T3DD13',
			$this->fixture->getFormat()
		);
	}

	/**
	 * @test
	 */
	public function setPathForStringSetsPath() {
		$this->fixture->setPath('Conceived at T3DD13');

		$this->assertSame(
			'Conceived at T3DD13',
			$this->fixture->getPath()
		);
	}

}
