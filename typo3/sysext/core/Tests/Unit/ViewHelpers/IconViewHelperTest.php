<?php
namespace TYPO3\CMS\Core\Tests\Unit\ViewHelpers;

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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\ViewHelpers\IconViewHelper;
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case
 */
class IconViewHelperTest extends ViewHelperBaseTestcase {

	/**
	 * @var IconViewHelper
	 */
	protected $viewHelper;

	protected function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getAccessibleMock(IconViewHelper::class, array('renderChildren'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 */
	public function renderCallsIconFactoryWithDefaultSizeAndReturnsResult() {
		$iconFactoryProphecy = $this->prophesize(IconFactory::class);
		GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
		$iconProphecy = $this->prophesize(Icon::class);

		$iconFactoryProphecy->getIcon('myIdentifier', Icon::SIZE_SMALL, NULL)->shouldBeCalled()->willReturn($iconProphecy->reveal());
		$iconProphecy->render()->shouldBeCalled()->willReturn('htmlFoo');

		$this->assertSame('htmlFoo', $this->viewHelper->render('myIdentifier'));
	}

	/**
	 * @test
	 */
	public function renderCallsIconFactoryWithGivenSizeAndReturnsResult() {
		$iconFactoryProphecy = $this->prophesize(IconFactory::class);
		GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
		$iconProphecy = $this->prophesize(Icon::class);

		$iconFactoryProphecy->getIcon('myIdentifier', Icon::SIZE_LARGE, NULL)->shouldBeCalled()->willReturn($iconProphecy->reveal());
		$iconProphecy->render()->shouldBeCalled()->willReturn('htmlFoo');

		$this->assertSame('htmlFoo', $this->viewHelper->render('myIdentifier', Icon::SIZE_LARGE));
	}

	/**
	 * @test
	 */
	public function renderCallsIconFactoryWithGivenOverlayAndReturnsResult() {
		$iconFactoryProphecy = $this->prophesize(IconFactory::class);
		GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
		$iconProphecy = $this->prophesize(Icon::class);

		$iconFactoryProphecy->getIcon('myIdentifier', Argument::any(), 'overlayString')->shouldBeCalled()->willReturn($iconProphecy->reveal());
		$iconProphecy->render()->shouldBeCalled()->willReturn('htmlFoo');

		$this->assertSame('htmlFoo', $this->viewHelper->render('myIdentifier', Icon::SIZE_LARGE, 'overlayString'));
	}

}