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
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlentitiesDecodeViewHelper;

/**
 * Test case
 */
class HtmlentitiesDecodeViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var ReflectionService|ObjectProphecy
     */
    protected $reflectionServiceProphecy;

    /**
     * @var HtmlentitiesDecodeViewHelper
     */
    protected $viewHelper;

    /**
     * shortcut for default Arguments which would be prepared by initializeArguments()
     *
     * @var array
     */
    protected $defaultArguments;

    protected function setUp()
    {
        parent::setUp();
        $this->reflectionServiceProphecy = $this->prophesize(ReflectionService::class);
        $this->viewHelper = new HtmlentitiesDecodeViewHelper();
        $this->viewHelper->injectReflectionService($this->reflectionServiceProphecy->reveal());
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderUsesValueAsSourceIfSpecified()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'Some string'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('Some string', $actualResult);
    }

    /**
     * @test
     */
    public function renderUsesChildnodesAsSourceIfSpecified()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'Some string';
            }
        );
        $this->setArgumentsUnderTest($this->viewHelper);
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('Some string', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters()
    {
        $source = 'This is a sample text without special characters. <> &©"\'';
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source,
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertSame($source, $actualResult);
    }

    /**
     * @test
     */
    public function renderDecodesSimpleString()
    {
        $source = 'Some special characters: &amp; &quot; \' &lt; &gt; *';
        $expectedResult = 'Some special characters: & " \' < > *';
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsKeepQuoteArgument()
    {
        $source = 'Some special characters: &amp; &quot; \' &lt; &gt; *';
        $expectedResult = 'Some special characters: & &quot; \' < > *';
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source,
                'keepQuotes' => true,
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsEncodingArgument()
    {
        $source = utf8_decode('Some special characters: &amp; &quot; \' &lt; &gt; *');
        $expectedResult = 'Some special characters: & " \' < > *';
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source,
                'encoding' => 'ISO-8859-1',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsUnmodifiedSourceIfItIsNoString()
    {
        $source = new \stdClass();
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source
            ]
        );
        $actualResult = $this->viewHelper->render();
        $this->assertSame($source, $actualResult);
    }
}
