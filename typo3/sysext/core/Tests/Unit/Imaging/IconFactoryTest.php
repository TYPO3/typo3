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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\IconFactory
 */
class IconFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Imaging\IconFactory
	 */
	protected $subject = NULL;

	/**
	 * @var string
	 */
	protected $notRegisteredIconIdentifier = 'my-super-unregistered-identifier';

	/**
	 * @var string
	 */
	protected $registeredIconIdentifier = 'actions-document-close';

	/**
	 * @var string
	 */
	protected $registeredSpinningIconIdentifier = 'spinning-icon';

	/**
	 * @var \TYPO3\CMS\Core\Imaging\IconRegistry
	 */
	protected $iconRegistryMock;

	/**
	 * Set up
	 *
	 * @return void
	 */
	protected function setUp() {
		$this->iconRegistryMock = $this->prophesize(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
		$this->subject = new IconFactory($this->iconRegistryMock->reveal());

		$this->iconRegistryMock->isRegistered(Argument::any())->willReturn(TRUE);
		$this->iconRegistryMock->getIconConfigurationByIdentifier(Argument::any())->willReturn([
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'times',
				'additionalClasses' => 'fa-fw'
			)
		]);
	}

	/**
	 * DataProvider for icon sizes
	 *
	 * @return array
	 */
	public function differentSizesDataProvider() {
		return [
			['size ' . Icon::SIZE_SMALL => ['input' => Icon::SIZE_SMALL, 'expected' => Icon::SIZE_SMALL]],
			['size ' . Icon::SIZE_DEFAULT => ['input' => Icon::SIZE_DEFAULT, 'expected' => Icon::SIZE_DEFAULT]],
			['size ' . Icon::SIZE_LARGE => ['input' => Icon::SIZE_LARGE, 'expected' => Icon::SIZE_LARGE]]
		];
	}

	/**
	 * @test
	 */
	public function getIconReturnsIconWithCorrectMarkupWrapperIfRegisteredIconIdentifierIsUsed() {
		$this->assertContains('<span class="icon-markup">',
			$this->subject->getIcon($this->registeredIconIdentifier)->render());
	}

	/**
	 * @test
	 */
	public function getIconByIdentifierReturnsIconWithCorrectMarkupIfRegisteredIconIdentifierIsUsed() {
		$this->assertContains('<span class="icon icon-size-default icon-actions-document-close">',
			$this->subject->getIcon($this->registeredIconIdentifier)->render());
	}

	/**
	 * @test
	 * @dataProvider differentSizesDataProvider
	 */
	public function getIconByIdentifierAndSizeReturnsIconWithCorrectMarkupIfRegisteredIconIdentifierIsUsed($size) {
		$this->assertContains('<span class="icon icon-size-' . $size['expected'] . ' icon-actions-document-close">',
			$this->subject->getIcon($this->registeredIconIdentifier, $size['input'])->render());
	}

	/**
	 * @test
	 * @dataProvider differentSizesDataProvider
	 */
	public function getIconByIdentifierAndSizeAndWithOverlayReturnsIconWithCorrectOverlayMarkupIfRegisteredIconIdentifierIsUsed($size) {
		$this->assertContains('<span class="icon-overlay icon-overlay-read-only">',
			$this->subject->getIcon($this->registeredIconIdentifier, $size['input'], 'overlay-read-only')->render());
	}

	/**
	 * @test
	 */
	public function getIconReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed() {
		$this->iconRegistryMock->isRegistered(Argument::any())->willReturn(FALSE);
		$this->iconRegistryMock->getDefaultIconIdentifier(Argument::any())->willReturn('default-not-found');
		$this->iconRegistryMock->getIconConfigurationByIdentifier('default-not-found')->willReturn([
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'times-circle',
				'additionalClasses' => 'fa-fw'
			)
		]);
		$this->assertContains('<span class="icon icon-size-default icon-default-not-found">',
			$this->subject->getIcon($this->notRegisteredIconIdentifier)->render());
	}

	/**
	 * @test
	 * @dataProvider differentSizesDataProvider
	 */
	public function getIconByIdentifierAndSizeReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed($size) {
		$this->iconRegistryMock->isRegistered(Argument::any())->willReturn(FALSE);
		$this->iconRegistryMock->getDefaultIconIdentifier(Argument::any())->willReturn('default-not-found');
		$this->iconRegistryMock->getIconConfigurationByIdentifier('default-not-found')->willReturn([
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'times-circle',
				'additionalClasses' => 'fa-fw'
			)
		]);
		$this->assertContains('<span class="icon icon-size-' . $size['expected'] . ' icon-default-not-found">',
			$this->subject->getIcon($this->notRegisteredIconIdentifier, $size['input'])->render());
	}

	/**
	 * @test
	 */
	public function getIconReturnsCorrectMarkupIfIconIsRegisteredAsSpinningIcon() {
		$this->iconRegistryMock->getIconConfigurationByIdentifier($this->registeredSpinningIconIdentifier)->willReturn([
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'times-circle',
				'additionalClasses' => 'fa-fw',
				'spinning' => TRUE
			)
		]);
		$this->assertContains('<span class="icon icon-size-default icon-' . $this->registeredSpinningIconIdentifier . ' icon-spin">',
			$this->subject->getIcon($this->registeredSpinningIconIdentifier)->render());
	}

	/**
	 * @test
	 * @dataProvider differentSizesDataProvider
	 * @param string $size
	 */
	public function getIconByIdentifierAndSizeAndOverlayReturnsNotFoundIconWithCorrectMarkupIfUnregisteredIdentifierIsUsed($size) {
		$this->assertContains('<span class="icon-overlay icon-overlay-read-only">',
			$this->subject->getIcon($this->notRegisteredIconIdentifier, $size['input'], 'overlay-read-only')->render());
	}

	/**
	 * @test
	 */
	public function getIconThrowsExceptionIfInvalidSizeIsGiven() {
		$this->setExpectedException('InvalidArgumentException');
		$this->subject->getIcon($this->registeredIconIdentifier, 'foo')->render();
	}

}
