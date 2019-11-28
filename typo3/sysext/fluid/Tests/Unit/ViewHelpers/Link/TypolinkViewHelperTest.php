<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Link;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\ViewHelpers\Link\TypolinkViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Class TypolinkViewHelperTest
 */
class TypolinkViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var TypolinkViewHelper|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @throws \InvalidArgumentException
     */
    protected function setUp(): void
    {
        $this->subject = $this->getAccessibleMock(TypolinkViewHelper::class, ['renderChildren']);
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->getMockBuilder(RenderingContext::class)->disableOriginalConstructor()->getMock();
        $this->subject->setRenderingContext($renderingContext);
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

    /**
     * @return array
     */
    public function typoScriptConfigurationData()
    {
        return [
            'empty input' => [
                '', // input from link field
                '', // target from fluid
                '', // class from fluid
                '', // title from fluid
                '', // additional parameters from fluid
                '',
            ],
            'simple id input' => [
                19,
                '',
                '',
                '',
                '',
                '19',
            ],
            'external url with target' => [
                'www.web.de _blank',
                '',
                '',
                '',
                '',
                'www.web.de _blank',
            ],
            'page with extended class' => [
                '42 - css-class',
                '',
                'fluid_class',
                '',
                '',
                '42 - "css-class fluid_class"',
            ],
            'classes are unique' => [
                '42 - css-class',
                '',
                'css-class',
                '',
                '',
                '42 - css-class',
            ],
            'page with overridden title' => [
                '42 - - "a link title"',
                '',
                '',
                'another link title',
                '',
                '42 - - "another link title"',
            ],
            'page with title and extended parameters' => [
                '42 - - "a link title" &x=y',
                '',
                '',
                '',
                '&a=b',
                '42 - - "a link title" &x=y&a=b',
            ],
            'page with complex title and extended parameters' => [
                '42 - - "a \\"link\\" title with \\\\" &x=y',
                '',
                '',
                '',
                '&a=b',
                '42 - - "a \\"link\\" title with \\\\" &x=y&a=b',
            ],
            'full parameter usage' => [
                '19 _blank css-class "testtitle with whitespace" &X=y',
                '-',
                'fluid_class',
                'a new title',
                '&a=b',
                '19 - "css-class fluid_class" "a new title" &X=y&a=b',
            ],
            'only page id and overwrite' => [
                '42',
                '',
                '',
                '',
                '&a=b',
                '42 - - - &a=b',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider typoScriptConfigurationData
     * @param string $input
     * @param string $targetFromFluid
     * @param string $classFromFluid
     * @param string $titleFromFluid
     * @param string $additionalParametersFromFluid
     * @param string $expected
     */
    public function createTypolinkParameterArrayFromArgumentsReturnsExpectedArray(
        $input,
        $targetFromFluid,
        $classFromFluid,
        $titleFromFluid,
        $additionalParametersFromFluid,
        $expected
    ) {
        $result = $this->subject->_call(
            'createTypolinkParameterArrayFromArguments',
            $input,
            $targetFromFluid,
            $classFromFluid,
            $titleFromFluid,
            $additionalParametersFromFluid
        );
        self::assertSame($expected, $result);
    }
}
