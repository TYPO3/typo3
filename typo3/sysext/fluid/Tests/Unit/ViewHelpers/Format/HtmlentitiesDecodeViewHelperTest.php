<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

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
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlentitiesDecodeViewHelper;

/**
 * Test case
 */
class HtmlentitiesDecodeViewHelperTest extends UnitTestCase
{
    /**
     * @var HtmlentitiesDecodeViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        $this->viewHelper = $this->getMockBuilder(HtmlentitiesDecodeViewHelper::class)
            ->setMethods(array('renderChildren'))
            ->getMock();

        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->createMock(\TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture::class);
        $this->viewHelper->setRenderingContext($renderingContext);
    }

    /**
     * @test
     */
    public function renderUsesValueAsSourceIfSpecified()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $actualResult = $this->viewHelper->render('Some string');
        $this->assertEquals('Some string', $actualResult);
    }

    /**
     * @test
     */
    public function renderUsesChildnodesAsSourceIfSpecified()
    {
        $this->viewHelper->expects($this->atLeastOnce())->method('renderChildren')->will($this->returnValue('Some string'));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('Some string', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters()
    {
        $source = 'This is a sample text without special characters. <> &©"\'';
        $actualResult = $this->viewHelper->render($source);
        $this->assertSame($source, $actualResult);
    }

    /**
     * @test
     */
    public function renderDecodesSimpleString()
    {
        $source = 'Some special characters: &amp; &quot; \' &lt; &gt; *';
        $expectedResult = 'Some special characters: & " \' < > *';
        $actualResult = $this->viewHelper->render($source);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsKeepQuoteArgument()
    {
        $source = 'Some special characters: &amp; &quot; \' &lt; &gt; *';
        $expectedResult = 'Some special characters: & &quot; \' < > *';
        $actualResult = $this->viewHelper->render($source, true);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsEncodingArgument()
    {
        $source = utf8_decode('Some special characters: &amp; &quot; \' &lt; &gt; *');
        $expectedResult = 'Some special characters: & " \' < > *';
        $actualResult = $this->viewHelper->render($source, false, 'ISO-8859-1');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsUnmodifiedSourceIfItIsNoString()
    {
        $source = new \stdClass();
        $actualResult = $this->viewHelper->render($source);
        $this->assertSame($source, $actualResult);
    }
}
