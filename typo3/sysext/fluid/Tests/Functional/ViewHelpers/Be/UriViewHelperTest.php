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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Be;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Routing\RequestContextFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class UriViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function renderRendersTagWithHrefFromRoute(): void
    {
        // Mock Uribuilder in this functional test so we don't have to work with existing routes
        $formProtectionFactoryMock = $this->createMock(FormProtectionFactory::class);
        $backendEntryPointResolver = new BackendEntryPointResolver();
        $requestContextFactory = new RequestContextFactory($backendEntryPointResolver);
        $router = new Router($requestContextFactory, $backendEntryPointResolver);
        $uriBuilderMock = $this->getMockBuilder(UriBuilder::class)->setConstructorArgs([$router, $formProtectionFactoryMock, $requestContextFactory])->getMock();
        $uriBuilderMock->expects($this->once())->method('buildUriFromRoute')
            ->with('theRouteArgument', ['parameter' => 'to pass'], 'theReferenceTypeArgument')->willReturn('theUri');
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource(
            '<f:be.uri route="theRouteArgument" parameters="{parameter: \'to pass\'}" referenceType="theReferenceTypeArgument">foo</f:be.uri>'
        );
        self::assertEquals('theUri', (new TemplateView($context))->render());
    }

    #[Test]
    public function renderPassesEmptyArrayToUriBuilderForNoParameters(): void
    {
        // Mock Uribuilder in this functional test so we don't have to work with existing routes
        $formProtectionFactoryMock = $this->createMock(FormProtectionFactory::class);
        $backendEntryPointResolver = new BackendEntryPointResolver();
        $requestContextFactory = new RequestContextFactory($backendEntryPointResolver);
        $router = new Router($requestContextFactory, $backendEntryPointResolver);
        $uriBuilderMock = $this->getMockBuilder(UriBuilder::class)->setConstructorArgs([$router, $formProtectionFactoryMock, $requestContextFactory])->getMock();
        $uriBuilderMock->expects($this->once())->method('buildUriFromRoute')
            ->with('theRouteArgument', [], 'theReferenceTypeArgument')->willReturn('theUri');
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:be.uri route="theRouteArgument" referenceType="theReferenceTypeArgument">foo</f:be.uri>');
        self::assertEquals('theUri', (new TemplateView($context))->render());
    }
}
