<?php

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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Be;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Be\UriViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test-case for Be\UriViewHelper
 */
class UriViewHelperTest extends ViewHelperBaseTestcase
{

    /**
     * @var UriViewHelper|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
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
    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new UriViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->uriBuilderMock = $this->getMockBuilder(UriBuilder::class)->getMock();
    }

    /**
     * @test
     */
    public function initializeArgumentsRegistersExpectedArguments()
    {
        $viewHelper = $this->getMockBuilder(UriViewHelper::class)
            ->setMethods(['registerArgument'])
            ->getMock();

        $viewHelper->expects(self::at(0))->method('registerArgument')->with('route', 'string', self::anything());
        $viewHelper->expects(self::at(1))->method('registerArgument')->with('parameters', 'array', self::anything());
        $viewHelper->expects(self::at(2))->method('registerArgument')
            ->with('referenceType', 'string', self::anything(), false, UriBuilder::ABSOLUTE_PATH);
        $viewHelper->initializeArguments();
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

        $this->uriBuilderMock->expects(self::once())->method('buildUriFromRoute')
            ->with('theRouteArgument', ['parameter' => 'to pass'], 'theReferenceTypeArgument')->willReturn('theUri');

        self::assertEquals('theUri', $this->viewHelper->render());
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

        $this->uriBuilderMock->expects(self::once())->method('buildUriFromRoute')
            ->with('theRouteArgument', [], 'theReferenceTypeArgument')->willReturn('theUri');
        $this->viewHelper->render();
    }
}
