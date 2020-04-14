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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

use TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlentitiesViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case
 */
class HtmlentitiesViewHelperTest extends ViewHelperBaseTestcase
{

    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlentitiesViewHelper
     */
    protected $viewHelper;

    /**
     * shortcut for default Arguments which would be prepared by initializeArguments()
     *
     * @var array
     */
    protected $defaultArguments;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new HtmlentitiesViewHelper();
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
                'value' => 'Some string',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Some string', $actualResult);
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
        self::assertEquals('Some string', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters()
    {
        $source = 'This is a sample text without special characters.';
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source,
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertSame($source, $actualResult);
    }

    /**
     * @test
     */
    public function renderEncodesSimpleString()
    {
        $source = 'Some special characters: &©"\'';
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source
            ]
        );
        $expectedResult = 'Some special characters: &amp;&copy;&quot;&#039;';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsKeepQuoteArgument()
    {
        $source = 'Some special characters: &©"\'';
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source,
                'keepQuotes' => true,
            ]
        );
        $expectedResult = 'Some special characters: &amp;&copy;"\'';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsEncodingArgument()
    {
        $source = utf8_decode('Some special characters: &©"\'');
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source,
                'encoding' => 'ISO-8859-1',
            ]
        );
        $expectedResult = 'Some special characters: &amp;&copy;&quot;&#039;';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderConvertsAlreadyConvertedEntitiesByDefault()
    {
        $source = 'already &quot;encoded&quot;';
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source
            ]
        );
        $expectedResult = 'already &amp;quot;encoded&amp;quot;';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotConvertAlreadyConvertedEntitiesIfDoubleQuoteIsFalse()
    {
        $source = 'already &quot;encoded&quot;';
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source,
                'doubleEncode' => false,
            ]
        );
        $expectedResult = 'already &quot;encoded&quot;';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * This test tests the behaviour of render without relying on the validation of registerArguments
     * In the normal course of things "value" can't be anything but a string as it is registered that way
     *
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
        self::assertSame($source, $actualResult);
    }

    /**
     * Ensures that objects are handled properly:
     * + class not having __toString() method as given
     * + class having __toString() method gets encoded
     *
     * @param object $source
     * @param mixed $expectation
     * @test
     * @dataProvider renderEscapesObjectIfPossibleDataProvider
     */
    public function renderEscapesObjectIfPossible($source, $expectation)
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source
            ]
        );
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectation, $actualResult);
    }

    /**
     * @return array
     */
    public function renderEscapesObjectIfPossibleDataProvider(): array
    {
        $stdClass = new \stdClass();
        $toStringClass = new class() {
            public function __toString(): string
            {
                return '<script>alert(\'"&xss"\')</script>';
            }
        };

        return [
            'plain object' => [$stdClass, $stdClass],
            'object with __toString()' => [$toStringClass, '&lt;script&gt;alert(&#039;&quot;&amp;xss&quot;&#039;)&lt;/script&gt;'],
        ];
    }
}
