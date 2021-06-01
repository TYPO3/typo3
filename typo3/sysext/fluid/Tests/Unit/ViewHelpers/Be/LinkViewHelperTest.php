<?php

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Be;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Be\LinkViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test-case for Be\LinkViewHelper
 */
class LinkViewHelperTest extends ViewHelperBaseTestcase
{

    /**
     * @var LinkViewHelper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $viewHelper;

    /**
     * @var UriBuilder|\PHPUnit_Framework_MockObject_MockBuilder
     */
    protected $uriBuilderMock;

    protected $resetSingletonInstances = true;

    /**
     * setUp function
     */
    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(LinkViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->uriBuilderMock = $this->getMockBuilder(UriBuilder::class)->getMock();

        $this->tagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods([
            'addAttribute',
            'setContent',
            'forceClosingTag',
            'render'
        ])->getMock();

        $this->inject($this->viewHelper, 'tag', $this->tagBuilder);
    }

    /**
     * @test
     */
    public function renderRendersTagWithHrefFromRoute()
    {
        $this->viewHelper->setArguments([
            'route' => 'theRouteArgument',
            'parameters' => ['parameter' => 'to pass'],
            'referenceType' => 'theReferenceTypeArgument'
        ]);

        GeneralUtility::setSingletonInstance(UriBuilder::class, $this->uriBuilderMock);

        $this->uriBuilderMock->expects($this->once())->method('buildUriFromRoute')
            ->with('theRouteArgument', ['parameter' => 'to pass'], 'theReferenceTypeArgument')->willReturn('theUri');

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('href', 'theUri');
        $this->tagBuilder->expects($this->once())->method('setContent');
        $this->tagBuilder->expects($this->once())->method('forceClosingTag')->with(true);
        $this->tagBuilder->expects($this->once())->method('render');

        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderPassesEmptyArrayToUriBuilderForNoParameters()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'route' => 'theRouteArgument',
                'referenceType' => 'theReferenceTypeArgument'
            ]
        );
        GeneralUtility::setSingletonInstance(UriBuilder::class, $this->uriBuilderMock);

        $this->uriBuilderMock->expects($this->once())->method('buildUriFromRoute')
            ->with('theRouteArgument', [], 'theReferenceTypeArgument')->willReturn('theUri');
        $this->viewHelper->render();
    }
}
