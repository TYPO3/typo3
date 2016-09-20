<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Link;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\CMS\Fluid\ViewHelpers\Link\TypolinkViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class TypolinkViewHelperTest
 */
class TypolinkViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var TypolinkViewHelper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @throws \InvalidArgumentException
     */
    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(TypolinkViewHelper::class, ['renderChildren']);
        /** @var RenderingContext  $renderingContext */
        $renderingContext = $this->getMock(RenderingContext::class);
        $this->subject->setRenderingContext($renderingContext);
    }

    /**
     * @test
     */
    public function renderReturnsResultOfContentObjectRenderer()
    {
        $this->subject->expects($this->any())->method('renderChildren')->will($this->returnValue('innerContent'));
        $contentObjectRendererMock = $this->getMock(ContentObjectRenderer::class, [], [], '', false);
        $contentObjectRendererMock->expects($this->once())->method('stdWrap')->will($this->returnValue('foo'));
        GeneralUtility::addInstance(ContentObjectRenderer::class, $contentObjectRendererMock);
        $this->assertEquals('foo', $this->subject->render('42'));
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
    public function createTypolinkParameterArrayFromArgumentsReturnsExpectedArray($input, $targetFromFluid, $classFromFluid, $titleFromFluid, $additionalParametersFromFluid, $expected)
    {
        $result = $this->subject->_call('createTypolinkParameterArrayFromArguments', $input, $targetFromFluid, $classFromFluid, $titleFromFluid, $additionalParametersFromFluid);
        $this->assertSame($expected, $result);
    }
}
