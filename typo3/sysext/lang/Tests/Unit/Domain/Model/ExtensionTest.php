<?php

namespace TYPO3\CMS\Lang\Tests\Unit\Domain\Model;

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
 * Testcase for Extension
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 */
class ExtensionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Lang\Domain\Model\Extension
	 */
	protected $fixture = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Lang\Domain\Model\Extension();
	}

	/**
	 * @test
	 */
	public function getKeyInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->fixture->getKey()
		);
	}

	/**
	 * @test
	 */
	public function getKeyInitiallyReturnsGivenKeyFromConstruct() {
		$key = 'foo bar';
		$this->fixture = new \TYPO3\CMS\Lang\Domain\Model\Extension($key);

		$this->assertSame(
			$key,
			$this->fixture->getKey()
		);
	}

	/**
	 * @test
	 */
	public function setKeySetsKey() {
		$key = 'foo bar';
		$this->fixture->setKey($key);

		$this->assertSame(
			$key,
			$this->fixture->getKey()
		);
	}

	/**
	 * @test
	 */
	public function getTitleInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getTitleInitiallyReturnsGivenTitleFromConstruct() {
		$title = 'foo bar';
		$this->fixture = new \TYPO3\CMS\Lang\Domain\Model\Extension('', $title);

		$this->assertSame(
			$title,
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleSetsTitle() {
		$title = 'foo bar';
		$this->fixture->setTitle($title);

		$this->assertSame(
			$title,
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getIconInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->fixture->getIcon()
		);
	}

	/**
	 * @test
	 */
	public function getIconInitiallyReturnsGivenIconFromConstruct() {
		$icon = 'foo bar';
		$this->fixture = new \TYPO3\CMS\Lang\Domain\Model\Extension('', '', $icon);

		$this->assertSame(
			$icon,
			$this->fixture->getIcon()
		);
	}

	/**
	 * @test
	 */
	public function setIconSetsIcon() {
		$icon = 'foo bar';
		$this->fixture->setIcon($icon);

		$this->assertSame(
			$icon,
			$this->fixture->getIcon()
		);
	}

	/**
	 * @test
	 */
	public function getVersionInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->fixture->getVersion()
		);
	}

	/**
	 * @test
	 */
	public function setVersionSetsVersion() {
		$version = 10;
		$this->fixture->setVersion($version);

		$this->assertSame(
			$version,
			$this->fixture->getVersion()
		);
	}

	/**
	 * @test
	 */
	public function setVersionSetsVersionFromString() {
		$version = 4012003;
		$this->fixture->setVersionFromString('4.12.3');

		$this->assertSame(
			$version,
			$this->fixture->getVersion()
		);
	}

	/**
	 * @test
	 */
	public function getUpdateResultInitiallyReturnsEmptyArray() {
		$this->assertSame(
			array(),
			$this->fixture->getUpdateResult()
		);
	}

	/**
	 * @test
	 */
	public function setUpdateResultSetsUpdateResult() {
		$updateResult = array(
			'nl' => array(
				'icon' => '<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-info">&nbsp;</span>',
				'message' => 'translation_n_a'
			),
		);

		$this->fixture->setUpdateResult($updateResult);

		$this->assertSame(
			$updateResult,
			$this->fixture->getUpdateResult()
		);
	}

}
