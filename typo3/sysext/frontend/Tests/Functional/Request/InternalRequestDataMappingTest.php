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

namespace TYPO3\CMS\Frontend\Tests\Functional\Request;

use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class InternalRequestDataMappingTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_request_mirror',
    ];

    public function ensureRequestMappingWorksDataProvider(): \Generator
    {
        yield 'POST parsedBody(_POST) as parsedBody' => [
            'uri' => 'https://acme.com/request-mirror',
            'method' => 'POST',
            'parsedBody' => ['param1' => 'value1'],
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => null,
            'expectedJsonKeyValues' => [
                'method' => 'POST',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => [],
                'body' => '',
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'PATCH body as parsedBody' => [
            'uri' => 'https://acme.com/request-mirror',
            'method' => 'PATCH',
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'PATCH',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => [],
                'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'PUT body as parsedBody' => [
            'uri' => 'https://acme.com/request-mirror',
            'method' => 'PUT',
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'PUT',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => [],
                'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'DELETE body as parsedBody' => [
            'uri' => 'https://acme.com/request-mirror',
            'method' => 'DELETE',
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'DELETE',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => [],
                'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'POST parsedBody(_POST) as parsedBody and queryParams' => [
            'uri' => 'https://acme.com/request-mirror?queryParam1=queryValue1',
            'method' => 'POST',
            'parsedBody' => ['param1' => 'value1'],
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => null,
            'expectedJsonKeyValues' => [
                'method' => 'POST',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => ['queryParam1' => 'queryValue1'],
                'body' => '',
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'PATCH body as parsedBody and queryParams' => [
            'uri' => 'https://acme.com/request-mirror?queryParam1=queryValue1',
            'method' => 'PATCH',
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'PATCH',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => ['queryParam1' => 'queryValue1'],
                'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'PUT body as parsedBody and queryParams' => [
            'uri' => 'https://acme.com/request-mirror?queryParam1=queryValue1',
            'method' => 'PUT',
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'PUT',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => ['queryParam1' => 'queryValue1'],
                'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'DELETE body as parsedBody and queryParams' => [
            'uri' => 'https://acme.com/request-mirror?queryParam1=queryValue1',
            'method' => 'DELETE',
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'DELETE',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => ['queryParam1' => 'queryValue1'],
                'body' => \GuzzleHttp\Psr7\Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
    }

    /**
     * Verify testing-framework request details are properly received
     * in the application by adding an extension with a middleware.
     *
     * @test
     * @dataProvider ensureRequestMappingWorksDataProvider
     */
    public function ensureRequestMappingWorks(string $uri, string $method, ?array $parsedBody, array $headers, ?string $body, array $expectedJsonKeyValues): void
    {
        $request = (new InternalRequest($uri))
            ->withMethod($method)
            ->withParsedBody($parsedBody);
        foreach ($headers as $headerName => $headerValue) {
            $request = $request->withAddedHeader($headerName, $headerValue);
        }
        if ($body) {
            /** @var StreamFactory $streamFactory */
            $streamFactory = $this->get(StreamFactoryInterface::class);
            $request = $request->withBody($streamFactory->createStream($body));
        }

        $response = $this->executeFrontendSubRequest($request);
        self::assertSame(200, $response->getStatusCode());
        $json = json_decode((string)$response->getBody(), true);
        foreach ($expectedJsonKeyValues as $expectedKey => $expectedValue) {
            self::assertSame($expectedValue, $json[$expectedKey] ?? null);
        }
    }
}
