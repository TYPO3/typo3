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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\EnhancerLinkGenerator;

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class DefaultExtbaseControllerTest extends AbstractEnhancerLinkGeneratorTestCase
{
    /**
     * @return array
     */
    public function defaultExtbaseControllerActionNamesAreAppliedWithAdditionalNonMappedQueryArgumentsDataProvider(): array
    {
        return [
            '*::*' => [
                '&tx_testing_link[value]=1&tx_testing_link[excludedValue]=random',
                'https://acme.us/welcome/link/index/one?tx_testing_link%5BexcludedValue%5D=random',
            ],
            '*::list' => [
                '&tx_testing_link[action]=list&tx_testing_link[value]=1&tx_testing_link[excludedValue]=random',
                'https://acme.us/welcome/link/list/one?tx_testing_link%5BexcludedValue%5D=random',
            ],
            'Link::*' => [
                // correctly falling back to defaultController here
                '&tx_testing_link[controller]=Link&tx_testing_link[value]=1&tx_testing_link[excludedValue]=random',
                'https://acme.us/welcome/link/index/one?tx_testing_link%5BexcludedValue%5D=random',
            ],
            'Page::*' => [
                // correctly falling back to defaultController here
                '&tx_testing_link[controller]=Page&tx_testing_link[value]=1&tx_testing_link[excludedValue]=random',
                'https://acme.us/welcome/link/index/one?tx_testing_link%5BexcludedValue%5D=random',
            ],
            'Page::show' => [
                '&tx_testing_link[controller]=Page&tx_testing_link[action]=show&tx_testing_link[value]=1&tx_testing_link[excludedValue]=random',
                'https://acme.us/welcome/page/show/one?tx_testing_link%5BexcludedValue%5D=random',
            ],
        ];
    }

    /**
     * Tests whether ExtbasePluginEnhancer applies `defaultController` values correctly but keeps additional Query Parameters.
     *
     * @param string $additionalParameters
     * @param string $expectation
     * @test
     * @dataProvider defaultExtbaseControllerActionNamesAreAppliedWithAdditionalNonMappedQueryArgumentsDataProvider
     */
    public function defaultExtbaseControllerActionNamesAreAppliedWithAdditionalNonMappedQueryArguments(string $additionalParameters, string $expectation): void
    {
        $targetLanguageId = 0;
        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => [
                'Enhancer' => [
                    'type' => 'Extbase',
                    'routes' => [
                        ['routePath' => '/link/index/{value}', '_controller' => 'Link::index'],
                        ['routePath' => '/link/list/{value}', '_controller' => 'Link::list'],
                        ['routePath' => '/page/show/{value}', '_controller' => 'Page::show'],
                    ],
                    'defaultController' => 'Link::index',
                    'extension' => 'testing',
                    'plugin' => 'link',
                    'aspects' => [
                        'value' => [
                            'type' => 'StaticValueMapper',
                            'map' => [
                                'one' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('https://acme.us/'))
                ->withPageId(1100)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => 1100,
                        'language' => $targetLanguageId,
                        'additionalParams' => $additionalParameters,
                        'forceAbsoluteUrl' => 1,
                    ]),
                ])
        );

        self::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @return array
     */
    public function defaultExtbaseControllerActionNamesAreAppliedDataProvider(): array
    {
        return [
            '*::*' => [
                '&tx_testing_link[value]=1',
                'https://acme.us/welcome/link/index/one',
            ],
            '*::list' => [
                '&tx_testing_link[action]=list&tx_testing_link[value]=1',
                'https://acme.us/welcome/link/list/one',
            ],
            'Link::*' => [
                // correctly falling back to defaultController here
                '&tx_testing_link[controller]=Link&tx_testing_link[value]=1',
                'https://acme.us/welcome/link/index/one',
            ],
            'Page::*' => [
                // correctly falling back to defaultController here
                '&tx_testing_link[controller]=Page&tx_testing_link[value]=1',
                'https://acme.us/welcome/link/index/one',
            ],
            'Page::show' => [
                '&tx_testing_link[controller]=Page&tx_testing_link[action]=show&tx_testing_link[value]=1',
                'https://acme.us/welcome/page/show/one',
            ],
        ];
    }

    /**
     * Tests whether ExtbasePluginEnhancer applies `defaultController` values correctly.
     *
     * @param string $additionalParameters
     * @param string $expectation
     * @test
     * @dataProvider defaultExtbaseControllerActionNamesAreAppliedDataProvider
     */
    public function defaultExtbaseControllerActionNamesAreApplied(string $additionalParameters, string $expectation): void
    {
        $targetLanguageId = 0;
        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => [
                'Enhancer' => [
                    'type' => 'Extbase',
                    'routes' => [
                        ['routePath' => '/link/index/{value}', '_controller' => 'Link::index'],
                        ['routePath' => '/link/list/{value}', '_controller' => 'Link::list'],
                        ['routePath' => '/page/show/{value}', '_controller' => 'Page::show'],
                    ],
                    'defaultController' => 'Link::index',
                    'extension' => 'testing',
                    'plugin' => 'link',
                    'aspects' => [
                        'value' => [
                            'type' => 'StaticValueMapper',
                            'map' => [
                                'one' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('https://acme.us/'))
                ->withPageId(1100)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => 1100,
                        'language' => $targetLanguageId,
                        'additionalParams' => $additionalParameters,
                        'forceAbsoluteUrl' => 1,
                    ]),
                ])
        );

        self::assertSame($expectation, (string)$response->getBody());
    }
}
