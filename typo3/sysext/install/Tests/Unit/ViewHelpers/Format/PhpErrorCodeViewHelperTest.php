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

namespace TYPO3\CMS\Install\Tests\Unit\ViewHelpers\Format;

use TYPO3\CMS\Install\ViewHelpers\Format\PhpErrorCodeViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case
 */
class PhpErrorCodeViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Format\NumberViewHelper
     */
    protected $viewHelper;

    /**
     * Setup the test case scenario
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new PhpErrorCodeViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @return array
     */
    public function errorCodesDataProvider(): array
    {
        return [
            [
                'errorCode' => E_ERROR,
                'expectedString' => 'E_ERROR',
            ],
            [
                'errorCode' => E_ALL,
                'expectedString' => 'E_ALL',
            ],
            [
                'errorCode' => E_ERROR ^ E_WARNING ^ E_PARSE,
                'expectedString' => 'E_ERROR | E_WARNING | E_PARSE',
            ],
            [
                'errorCode' => E_RECOVERABLE_ERROR ^ E_USER_DEPRECATED,
                'expectedString' => 'E_RECOVERABLE_ERROR | E_USER_DEPRECATED',
            ]
        ];
    }

    /**
     * @param $errorCode
     * @param $expectedString
     * @test
     * @dataProvider errorCodesDataProvider
     */
    public function renderPhpCodesCorrectly($errorCode, $expectedString): void
    {
        $this->viewHelper->setArguments([
            'phpErrorCode' => $errorCode
        ]);
        $actualString = $this->viewHelper->render();
        self::assertEquals($expectedString, $actualString);
    }
}
