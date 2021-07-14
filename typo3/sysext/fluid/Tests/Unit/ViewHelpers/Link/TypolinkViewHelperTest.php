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

use TYPO3\CMS\Fluid\ViewHelpers\Link\TypolinkViewHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TypolinkViewHelperTest extends UnitTestCase
{
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
     * @test
     * @dataProvider decodedConfigurationAndFluidArgumentDataProvider
     */
    public function mergeTypoLinkConfigurationMergesData(
        array $decodedConfiguration,
        array $viewHelperArguments,
        array $expectation
    ): void {
        $subject = $this->getAccessibleMock(TypolinkViewHelper::class, ['renderChildren']);
        $result = $subject->_call(
            'mergeTypoLinkConfiguration',
            $decodedConfiguration,
            $viewHelperArguments
        );
        self::assertSame($expectation, $result);
    }
}
