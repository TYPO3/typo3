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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Link;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\ViewHelpers\Link\TypolinkViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\Variables\VariableProviderInterface;

/**
 * Class TypolinkViewHelperTest
 */
class TypolinkViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var TypolinkViewHelper|MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @throws \InvalidArgumentException
     */
    protected function setUp(): void
    {
        $this->subject = $this->getAccessibleMock(TypolinkViewHelper::class, ['renderChildren']);
        /** @var VariableProviderInterface|MockObject $variableProvider */
        $variableProvider = $this->getMockBuilder(VariableProviderInterface::class)->getMock();
        /** @var RenderingContext|MockObject $renderingContext */
        $renderingContext = $this->getMockBuilder(RenderingContext::class)->disableOriginalConstructor()->getMock();
        $renderingContext->expects(self::any())->method('getVariableProvider')->willReturn($variableProvider);
        $this->subject->setRenderingContext($renderingContext);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function renderReturnsResultOfContentObjectRenderer()
    {
        $this->subject->expects(self::any())->method('renderChildren')->willReturn('innerContent');
        $this->subject->setArguments([
            'parameter' => '42',
            'target' => '',
            'class' => '',
            'title' => '',
            'additionalParams' => '',
            'additionalAttributes' => [],
        ]);
        $contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);
        $contentObjectRendererMock->expects(self::once())->method('stdWrap')->willReturn('foo');
        GeneralUtility::addInstance(ContentObjectRenderer::class, $contentObjectRendererMock);
        self::assertEquals('foo', $this->subject->render());
    }

    /**
     * @test
     */
    public function renderCallsStdWrapWithRightParameters()
    {
        $addQueryString = true;
        $addQueryStringMethod = 'GET';
        $addQueryStringExclude = 'cHash';

        $this->subject->expects(self::any())->method('renderChildren')->willReturn('innerContent');
        $this->subject->setArguments([
            'parameter' => '42',
            'target' => '',
            'class' => '',
            'title' => '',
            'additionalParams' => '',
            'additionalAttributes' => [],
            'addQueryString' => $addQueryString,
            'addQueryStringMethod' => $addQueryStringMethod,
            'addQueryStringExclude' => $addQueryStringExclude,
            'absolute' => false
        ]);
        $contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);
        $contentObjectRendererMock->expects(self::once())
            ->method('stdWrap')
            ->with(
                'innerContent',
                [
                    'typolink.' => [
                        'parameter' => '42',
                        'ATagParams' => '',
                        'addQueryString' => $addQueryString,
                        'addQueryString.' => [
                            'method' => $addQueryStringMethod,
                            'exclude' => $addQueryStringExclude,
                        ],
                        'forceAbsoluteUrl' => false,
                    ],
                ]
            )
            ->willReturn('foo');
        GeneralUtility::addInstance(ContentObjectRenderer::class, $contentObjectRendererMock);
        self::assertEquals('foo', $this->subject->render());
    }

    public function decodedConfigurationAndFluidArgumentDataProvider(): array
    {
        return [
            'blank input' => [
                [   // TypoLinkCodecService::decode() result of input value from link field
                    'url' => '',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [   // ViewHelper arguments
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [   // expectation
                    'url' => '',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
            ],
            'empty input' => [
                [],
                [
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [],
            ],
            'simple id input' => [
                [
                    'url' => 19,
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [
                    'url' => 19,
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
            ],
            'external url with target' => [
                [
                    'url' => 'www.web.de',
                    'target' => '_blank',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [
                    'url' => 'www.web.de',
                    'target' => '_blank',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
            ],
            'page with extended class' => [
                [
                    'url' => 42,
                    'target' => '',
                    'class' => 'css-class',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [
                    'target' => '',
                    'class' => 'fluid-class',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [
                    'url' => 42,
                    'target' => '',
                    'class' => 'css-class fluid-class',
                    'title' => '',
                    'additionalParams' => '',
                ],
            ],
            'page with same class' => [
                [
                    'url' => 42,
                    'target' => '',
                    'class' => 'css-class',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [
                    'target' => '',
                    'class' => 'css-class',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [
                    'url' => 42,
                    'target' => '',
                    'class' => 'css-class',
                    'title' => '',
                    'additionalParams' => '',
                ],
            ],
            'page with overridden title' => [
                [
                    'url' => 42,
                    'target' => '',
                    'class' => '',
                    'title' => 'a link title',
                    'additionalParams' => '',
                ],
                [
                    'target' => '',
                    'class' => '',
                    'title' => 'another link title',
                    'additionalParams' => '',
                ],
                [
                    'url' => 42,
                    'target' => '',
                    'class' => '',
                    'title' => 'another link title',
                    'additionalParams' => '',
                ],
            ],
            'page with title and extended parameters' => [
                [
                    'url' => 42,
                    'target' => '',
                    'class' => '',
                    'title' => 'a link title',
                    'additionalParams' => '&x=y',
                ],
                [
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '&a=b',
                ],
                [
                    'url' => 42,
                    'target' => '',
                    'class' => '',
                    'title' => 'a link title',
                    'additionalParams' => '&x=y&a=b',
                ],
            ],
            'overwrite all' => [
                [
                    'url' => 42,
                    'target' => '_top',
                    'class' => 'css-class',
                    'title' => 'a link title',
                    'additionalParams' => '&x=y',
                ],
                [
                    'target' => '_blank',
                    'class' => 'fluid-class',
                    'title' => 'another link title',
                    'additionalParams' => '&a=b',
                ],
                [
                    'url' => 42,
                    'target' => '_blank',
                    'class' => 'css-class fluid-class',
                    'title' => 'another link title',
                    'additionalParams' => '&x=y&a=b',
                ],
            ],
        ];
    }

    /**
     * @param array $decodedConfiguration
     * @param array $viewHelperArguments
     * @param array $expectation
     * @test
     * @dataProvider decodedConfigurationAndFluidArgumentDataProvider
     */
    public function mergeTypoLinkConfigurationMergesData(
        array $decodedConfiguration,
        array $viewHelperArguments,
        array $expectation
    ) {
        $result = $this->subject->_call(
            'mergeTypoLinkConfiguration',
            $decodedConfiguration,
            $viewHelperArguments
        );
        self::assertSame($expectation, $result);
    }
}
