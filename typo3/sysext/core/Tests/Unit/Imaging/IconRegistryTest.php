<?php
namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

/*
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

use TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\IconRegistry
 */
class IconRegistryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Imaging\IconRegistry
	 */
	protected $subject = NULL;

	/**
	 * @var string
	 */
	protected $notRegisteredIconIdentifier = 'my-super-unregistered-identifier';

	/**
	 * Set up
	 *
	 * @return void
	 */
	protected function setUp() {
		$this->subject = new \TYPO3\CMS\Core\Imaging\IconRegistry();
	}

	/**
	 * @test
	 */
	public function getDefaultIconIdentifierReturnsTheCorrectDefaultIconIdentifierString() {
		$result = $this->subject->getDefaultIconIdentifier();
		$this->assertEquals($result, 'default-not-found');
	}

	/**
	 * @test
	 */
	public function isRegisteredReturnsTrueForRegisteredIcon() {
		$result = $this->subject->isRegistered($this->subject->getDefaultIconIdentifier());
		$this->assertEquals($result, TRUE);
	}

	/**
	 * @test
	 */
	public function isRegisteredReturnsFalseForNotRegisteredIcon() {
		$result = $this->subject->isRegistered($this->notRegisteredIconIdentifier);
		$this->assertEquals($result, FALSE);
	}

	/**
	 * @test
	 */
	public function registerIconAddNewIconToRegistry() {
		$unregisterdIcon = 'foo-bar-unregistered';
		$this->assertFalse($this->subject->isRegistered($unregisterdIcon));
		$this->subject->registerIcon($unregisterdIcon, FontawesomeIconProvider::class, array(
			'name' => 'pencil',
			'additionalClasses' => 'fa-fw'
		));
		$this->assertTrue($this->subject->isRegistered($unregisterdIcon));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @test
	 */
	public function registerIconThrowsInvalidArgumentExceptionWithInvalidIconProvider() {
		$this->subject->registerIcon($this->notRegisteredIconIdentifier, GeneralUtility::class);
	}

	/**
	 * @expectedException \TYPO3\CMS\Core\Exception
	 * @test
	 */
	public function getIconConfigurationByIdentifierThrowsExceptionWithUnregisteredIconIdentifier() {
		$this->subject->getIconConfigurationByIdentifier($this->notRegisteredIconIdentifier);
	}

	/**
	 * @test
	 */
	public function getIconConfigurationByIdentifierReturnsCorrectConfiguration() {
		$result = $this->subject->getIconConfigurationByIdentifier('default-not-found');
		// result must contain at least provider and options array
		$this->assertArrayHasKey('provider', $result);
		$this->assertArrayHasKey('options', $result);
		// the provider must implement the IconProviderInterface
		$this->assertTrue(in_array(IconProviderInterface::class, class_implements($result['provider'])));
	}
}
