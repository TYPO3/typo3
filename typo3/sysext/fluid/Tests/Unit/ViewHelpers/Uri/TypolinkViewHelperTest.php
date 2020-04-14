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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Uri;

use TYPO3\CMS\Fluid\ViewHelpers\Uri\TypolinkViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Class TypolinkViewHelperTest
 */
class TypolinkViewHelperTest extends ViewHelperBaseTestcase
{
    public function plainDecodedConfigurationDataProvider(): array
    {
        return [
            'empty input' => [
                [], // TypoLinkCodecService::decode() result of input value from link field
            ],
            'simple id input' => [
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
            ],
            'page with class' => [
                [
                    'url' => 'www.web.de',
                    'target' => '',
                    'class' => 'css-class',
                    'title' => '',
                    'additionalParams' => '',
                ],
            ],
            'page with title' => [
                [
                    'url' => 'www.web.de',
                    'target' => '',
                    'class' => '',
                    'title' => 'a link title',
                    'additionalParams' => '',
                ],
            ],
            'page with title and parameters' => [
                [
                    'url' => 'www.web.de',
                    'target' => '',
                    'class' => '',
                    'title' => 'a link title',
                    'additionalParams' => '&x=y',
                ],
            ],
        ];
    }

    /**
     * @param array $decodedConfiguration
     *
     * @test
     * @dataProvider plainDecodedConfigurationDataProvider
     */
    public function mergeTypoLinkConfigurationDoesNotModifyData(array $decodedConfiguration)
    {
        /** @var \TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(TypolinkViewHelper::class, ['dummy']);
        $result = $subject->_call('mergeTypoLinkConfiguration', $decodedConfiguration, []);
        self::assertSame($decodedConfiguration, $result);
    }

    public function decodedConfigurationAndFluidArgumentDataProvider(): array
    {
        return [
            'empty input' => [
                [], // TypoLinkCodecService::decode() result of input value from link field
                [], // ViewHelper arguments
                [], // expected typolink configuration
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
            'only page id and overwrite' => [
                [
                    'url' => 42,
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [
                    'additionalParams' => '&a=b',
                ],
                [
                    'url' => 42,
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '&a=b',
                ],
            ],
            't3:// with title and extended parameters' => [
                [
                    'url' => 't3://url?url=https://example.org?param=1&other=dude',
                    'target' => '',
                    'class' => '',
                    'title' => 'a link title',
                    'additionalParams' => '&x=y',
                ],
                [
                    'additionalParams' => '&a=b',
                ],
                [
                    'url' => 't3://url?url=https://example.org?param=1&other=dude',
                    'target' => '',
                    'class' => '',
                    'title' => 'a link title',
                    'additionalParams' => '&x=y&a=b',
                ],
            ],
            't3:// and overwrite' => [
                [
                    'url' => 't3://url?url=https://example.org?param=1&other=dude',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
                [
                    'additionalParams' => '&a=b',
                ],
                [
                    'url' => 't3://url?url=https://example.org?param=1&other=dude',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '&a=b',
                ],
            ],
        ];
    }

    /**
     * @param array $decodedConfiguration
     * @param array $viewHelperArguments
     * @param array $expected
     *
     * @test
     * @dataProvider decodedConfigurationAndFluidArgumentDataProvider
     */
    public function mergeTypoLinkConfigurationMergesData(array $decodedConfiguration, array $viewHelperArguments, array $expected)
    {
        /** @var \TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(TypolinkViewHelper::class, ['dummy']);
        $result = $subject->_call('mergeTypoLinkConfiguration', $decodedConfiguration, $viewHelperArguments);
        self::assertSame($expected, $result);
    }
}
