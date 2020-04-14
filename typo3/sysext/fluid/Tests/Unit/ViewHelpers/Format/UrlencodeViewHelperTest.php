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

use TYPO3\CMS\Fluid\ViewHelpers\Format\UrlencodeViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case
 */
class UrlencodeViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var UrlencodeViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new UrlencodeViewHelper();
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
                'value' => 'Source'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Source', $actualResult);
    }

    /**
     * @test
     */
    public function renderUsesChildnodesAsSourceIfSpecified()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'Source'
            ]
        );

        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Source', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters()
    {
        $source = 'StringWithoutSpecialCharacters';

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertSame($source, $actualResult);
    }

    /**
     * @test
     */
    public function renderEncodesString()
    {
        $source = 'Foo @+%/ "';
        $expectedResult = 'Foo%20%40%2B%25%2F%20%22';

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => $source
            ]
        );

        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Ensures that objects are handled properly:
     * + class not having __toString() method as given
     * + class having __toString() method gets rawurlencoded
     *
     * @param $source
     * @param $expectation
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
                return '<script>alert(\'"xss"\')</script>';
            }
        };

        return [
            'plain object' => [$stdClass, $stdClass],
            'object with __toString()' => [$toStringClass, '%3Cscript%3Ealert%28%27%22xss%22%27%29%3C%2Fscript%3E'],
        ];
    }
}
