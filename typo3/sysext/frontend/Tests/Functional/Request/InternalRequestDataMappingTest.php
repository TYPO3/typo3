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

use GuzzleHttp\Psr7\Query;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class InternalRequestDataMappingTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_request_mirror',
    ];

    public static function ensureRequestMappingWorksDataProvider(): \Generator
    {
        yield 'POST parsedBody(_POST) as parsedBody' => [
            'uri' => 'https://acme.com/request-mirror',
            'method' => 'POST',
            'queryParams' => [],
            'parsedBody' => ['param1' => 'value1'],
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => null,
            'expectedJsonKeyValues' => [
                'method' => 'POST',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => [],
                'body' => Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'POST parsedBody(_POST) as parsedBody, body using multidimensional array that guzzle is not supporting' => [
            'uri' => 'https://acme.com/request-mirror',
            'method' => 'POST',
            'queryParams' => [],
            'parsedBody' => ['param1' => 'value1', 'subparam' => ['subsubparam' => 'value', 'subsubsubparam' => ['key' => 'value']]],
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query(['param1' => 'value1', 'subparam' => ['subsubparam' => 'value', 'subsubsubparam' => ['key' => 'value']]]),
            'expectedJsonKeyValues' => [
                'method' => 'POST',
                'parsedBody' => ['param1' => 'value1', 'subparam' => ['subsubparam' => 'value', 'subsubsubparam' => ['key' => 'value']]],
                'queryParams' => [],
                'body' => http_build_query(['param1' => 'value1', 'subparam' => ['subsubparam' => 'value', 'subsubsubparam' => ['key' => 'value']]]),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'PATCH body as parsedBody' => [
            'uri' => 'https://acme.com/request-mirror',
            'method' => 'PATCH',
            'queryParams' => [],
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'PATCH',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => [],
                'body' => Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'PUT body as parsedBody' => [
            'uri' => 'https://acme.com/request-mirror',
            'method' => 'PUT',
            'queryParams' => [],
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'PUT',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => [],
                'body' => Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'DELETE body as parsedBody' => [
            'uri' => 'https://acme.com/request-mirror',
            'method' => 'DELETE',
            'queryParams' => [],
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'DELETE',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => [],
                'body' => Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'POST parsedBody(_POST) as parsedBody and queryParams' => [
            'uri' => 'https://acme.com/request-mirror?queryParam1=queryValue1',
            'method' => 'POST',
            'queryParams' => [],
            'parsedBody' => ['param1' => 'value1'],
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => null,
            'expectedJsonKeyValues' => [
                'method' => 'POST',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => ['queryParam1' => 'queryValue1'],
                'body' => Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'PATCH body as parsedBody and queryParams' => [
            'uri' => 'https://acme.com/request-mirror?queryParam1=queryValue1',
            'method' => 'PATCH',
            'queryParams' => [],
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'PATCH',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => ['queryParam1' => 'queryValue1'],
                'body' => Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'PUT body as parsedBody and queryParams' => [
            'uri' => 'https://acme.com/request-mirror?queryParam1=queryValue1',
            'method' => 'PUT',
            'queryParams' => [],
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'PUT',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => ['queryParam1' => 'queryValue1'],
                'body' => Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'DELETE body as parsedBody and queryParams' => [
            'uri' => 'https://acme.com/request-mirror?queryParam1=queryValue1',
            'method' => 'DELETE',
            'queryParams' => [],
            'parsedBody' => null,
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => Query::build(['param1' => 'value1']),
            'expectedJsonKeyValues' => [
                'method' => 'DELETE',
                'parsedBody' => ['param1' => 'value1'],
                'queryParams' => ['queryParam1' => 'queryValue1'],
                'body' => Query::build(['param1' => 'value1']),
                'headers' => [
                    'Content-type' => [
                        'application/x-www-form-urlencoded',
                    ],
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'GET missing parsedParams filled from request query' => [
            'uri' => 'https://acme.com/request-mirror?queryParam1=value1',
            'method' => 'GET',
            'queryParams' => [],
            'parsedBody' => null,
            'headers' => [],
            'body' => null,
            'expectedJsonKeyValues' => [
                'method' => 'GET',
                'uri' => 'https://acme.com/request-mirror?queryParam1=value1',
                'queryParams' => ['queryParam1' => 'value1'],
                'body' => '',
                'headers' => [
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'GET added missing request uri query arguments from queryParams' => [
            'uri' => 'https://acme.com/request-mirror',
            'method' => 'GET',
            'queryParams' => ['queryParam1' => 'value1'],
            'parsedBody' => null,
            'headers' => [],
            'body' => null,
            'expectedJsonKeyValues' => [
                'method' => 'GET',
                'uri' => 'https://acme.com/request-mirror?queryParam1=value1',
                'queryParams' => ['queryParam1' => 'value1'],
                'body' => '',
                'headers' => [
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
        yield 'GET request uri queryParams and queryParams are merged' => [
            'uri' => 'https://acme.com/request-mirror?queryParam2=value2',
            'method' => 'GET',
            'queryParams' => ['queryParam1' => 'value1'],
            'parsedBody' => null,
            'headers' => [],
            'body' => null,
            'expectedJsonKeyValues' => [
                'method' => 'GET',
                'uri' => 'https://acme.com/request-mirror?queryParam1=value1&queryParam2=value2',
                'queryParams' => [
                    'queryParam1' => 'value1',
                    'queryParam2' => 'value2',
                ],
                'body' => '',
                'headers' => [
                    'Host' => [
                        'acme.com',
                    ],
                ],
            ],
        ];
    }

    /**
     * Verify testing-framework request details are properly received
     * in the application by adding an extension with a middleware.
     */
    #[DataProvider('ensureRequestMappingWorksDataProvider')]
    #[Test]
    public function ensureRequestMappingWorks(
        string $uri,
        string $method,
        array $queryParams,
        ?array $parsedBody,
        array $headers,
        ?string $body,
        array $expectedJsonKeyValues
    ): void {
        $request = (new InternalRequest($uri))
            ->withMethod($method)
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody);
        foreach ($headers as $headerName => $headerValue) {
            $request = $request->withAddedHeader($headerName, $headerValue);
        }
        if ($body) {
            $streamFactory = $this->get(StreamFactoryInterface::class);
            $request = $request->withBody($streamFactory->createStream($body));
        }

        $response = $this->executeFrontendSubRequest($request);
        self::assertSame(200, $response->getStatusCode());
        $json = json_decode((string)$response->getBody(), null, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
        foreach ($expectedJsonKeyValues as $expectedKey => $expectedValue) {
            self::assertSame($expectedValue, $json[$expectedKey] ?? null, 'Field "' . $expectedKey . '" must match value');
        }
    }
}
