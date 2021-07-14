<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Functional\ViewHelpers;

use Prophecy\Argument;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class IconViewHelperTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function renderCallsIconFactoryWithDefaultSizeAndDefaultStateAndReturnsResult(): void
    {
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        $iconProphecy = $this->prophesize(Icon::class);
        $iconFactoryProphecy->getIcon('myIdentifier', Icon::SIZE_SMALL, null, IconState::cast(IconState::STATE_DEFAULT))->shouldBeCalled()->willReturn($iconProphecy->reveal());
        $iconProphecy->render(null)->shouldBeCalled()->willReturn('htmlFoo');

        $view = new StandaloneView();
        $view->setTemplateSource('<core:icon identifier="myIdentifier" size="small" state="default" />');
        self::assertSame('htmlFoo', $view->render());
    }

    /**
     * @test
     */
    public function renderCallsIconFactoryWithGivenSizeAndReturnsResult(): void
    {
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        $iconProphecy = $this->prophesize(Icon::class);
        $iconFactoryProphecy->getIcon('myIdentifier', Icon::SIZE_LARGE, null, IconState::cast(IconState::STATE_DEFAULT))->shouldBeCalled()->willReturn($iconProphecy->reveal());
        $iconProphecy->render(null)->shouldBeCalled()->willReturn('htmlFoo');

        $view = new StandaloneView();
        $view->setTemplateSource('<core:icon identifier="myIdentifier" size="large" state="default" />');
        self::assertSame('htmlFoo', $view->render());
    }

    /**
     * @test
     */
    public function renderCallsIconFactoryWithGivenStateAndReturnsResult(): void
    {
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        $iconProphecy = $this->prophesize(Icon::class);
        $iconFactoryProphecy->getIcon('myIdentifier', Icon::SIZE_SMALL, null, IconState::cast(IconState::STATE_DISABLED))->shouldBeCalled()->willReturn($iconProphecy->reveal());
        $iconProphecy->render(null)->shouldBeCalled()->willReturn('htmlFoo');

        $view = new StandaloneView();
        $view->setTemplateSource('<core:icon identifier="myIdentifier" size="small" state="disabled" />');
        self::assertSame('htmlFoo', $view->render());
    }

    /**
     * @test
     */
    public function renderCallsIconFactoryWithGivenOverlayAndReturnsResult(): void
    {
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        $iconProphecy = $this->prophesize(Icon::class);
        $iconFactoryProphecy->getIcon('myIdentifier', Argument::any(), 'overlayString', IconState::cast(IconState::STATE_DEFAULT))->shouldBeCalled()->willReturn($iconProphecy->reveal());
        $iconProphecy->render(null)->shouldBeCalled()->willReturn('htmlFoo');

        $view = new StandaloneView();
        $view->setTemplateSource('<core:icon identifier="myIdentifier" size="large" state="default" overlay="overlayString" />');
        self::assertSame('htmlFoo', $view->render());
    }
}
