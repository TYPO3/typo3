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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class IconViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function renderCallsIconFactoryWithDefaultSizeAndDefaultStateAndReturnsResult(): void
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryMock);
        $iconMock = $this->createMock(Icon::class);
        $iconFactoryMock->expects(self::atLeastOnce())->method('getIcon')
            ->with('myIdentifier', Icon::SIZE_SMALL, null, IconState::cast(IconState::STATE_DEFAULT))
            ->willReturn($iconMock);
        $iconMock->expects(self::atLeastOnce())->method('render')->willReturn('htmlFoo');

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<core:icon identifier="myIdentifier" size="small" state="default" />');
        self::assertSame('htmlFoo', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderCallsIconFactoryWithGivenSizeAndReturnsResult(): void
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryMock);
        $iconMock = $this->createMock(Icon::class);
        $iconFactoryMock->expects(self::atLeastOnce())->method('getIcon')
            ->with('myIdentifier', Icon::SIZE_LARGE, null, IconState::cast(IconState::STATE_DEFAULT))
            ->willReturn($iconMock);
        $iconMock->expects(self::atLeastOnce())->method('render')->willReturn('htmlFoo');

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<core:icon identifier="myIdentifier" size="large" state="default" />');
        self::assertSame('htmlFoo', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderCallsIconFactoryWithGivenStateAndReturnsResult(): void
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryMock);
        $iconMock = $this->createMock(Icon::class);
        $iconFactoryMock->expects(self::atLeastOnce())->method('getIcon')
            ->with('myIdentifier', Icon::SIZE_SMALL, null, IconState::cast(IconState::STATE_DISABLED))
            ->willReturn($iconMock);
        $iconMock->expects(self::atLeastOnce())->method('render')->willReturn('htmlFoo');

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<core:icon identifier="myIdentifier" size="small" state="disabled" />');
        self::assertSame('htmlFoo', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderCallsIconFactoryWithGivenOverlayAndReturnsResult(): void
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryMock);
        $iconMock = $this->createMock(Icon::class);
        $iconFactoryMock->expects(self::atLeastOnce())->method('getIcon')
            ->with('myIdentifier', self::anything(), 'overlayString', IconState::cast(IconState::STATE_DEFAULT))
            ->willReturn($iconMock);
        $iconMock->expects(self::atLeastOnce())->method('render')->willReturn('htmlFoo');

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<core:icon identifier="myIdentifier" size="large" state="default" overlay="overlayString" />');
        self::assertSame('htmlFoo', (new TemplateView($context))->render());
    }
}
