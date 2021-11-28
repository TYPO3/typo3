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

use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class UriViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function renderRendersTagWithHrefFromRoute(): void
    {
        // Mock Uribuilder in this functional test so we don't have to work with existing routes
        $uriBuilderMock = $this->getMockBuilder(UriBuilder::class)->setConstructorArgs([new Router()])->getMock();
        $uriBuilderMock->expects(self::once())->method('buildUriFromRoute')
            ->with('theRouteArgument', ['parameter' => 'to pass'], 'theReferenceTypeArgument')->willReturn('theUri');
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);

        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource(
            '<f:be.uri route="theRouteArgument" parameters="{parameter: \'to pass\'}" referenceType="theReferenceTypeArgument">foo</f:be.uri>'
        );
        self::assertEquals('theUri', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderPassesEmptyArrayToUriBuilderForNoParameters(): void
    {
        // Mock Uribuilder in this functional test so we don't have to work with existing routes
        $uriBuilderMock = $this->getMockBuilder(UriBuilder::class)->setConstructorArgs([new Router()])->getMock();
        $uriBuilderMock->expects(self::once())->method('buildUriFromRoute')
            ->with('theRouteArgument', [], 'theReferenceTypeArgument')->willReturn('theUri');
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);

        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:be.uri route="theRouteArgument" referenceType="theReferenceTypeArgument">foo</f:be.uri>');
        self::assertEquals('theUri', (new TemplateView($context))->render());
    }
}
