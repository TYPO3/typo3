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

namespace TYPO3\CMS\Redirects\Tests\Unit\Service;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\CMS\Redirects\Service\RedirectService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RedirectServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var RedirectCacheService|ObjectProphecy
     */
    protected ObjectProphecy $redirectCacheServiceProphecy;

    /**
     * @var LinkService|ObjectProphecy
     */
    protected ObjectProphecy $linkServiceProphecy;

    protected RedirectService $redirectService;

    /**
     * @var ObjectProphecy|SiteFinder
     */
    protected ObjectProphecy $siteFinder;

    /**
     * @var ObjectProphecy|RedirectRepository
     */
    protected ObjectProphecy $redirectRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->redirectCacheServiceProphecy = $this->prophesize(RedirectCacheService::class);
        $this->linkServiceProphecy = $this->prophesize(LinkService::class);
        $this->siteFinder = $this->prophesize(SiteFinder::class);
        $this->redirectRepository = $this->prophesize(RedirectRepository::class);

        $this->redirectService = new RedirectService($this->redirectCacheServiceProphecy->reveal(), $this->linkServiceProphecy->reveal(), $this->siteFinder->reveal(), $this->redirectRepository->reveal());
        $this->redirectService->setLogger($loggerProphecy->reveal());

        $GLOBALS['SIM_ACCESS_TIME'] = 42;
    }

    /**
     * @test
     */
    public function matchRedirectReturnsNullIfNoRedirectsExist(): void
    {
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn([]);

        $result = $this->redirectService->matchRedirect('example.com', 'foo');

        self::assertNull($result);
    }

    /**
     * @test
     * @dataProvider matchRedirectReturnsRedirectOnFlatMatchDataProvider
     * @param string $path
     */
    public function matchRedirectReturnsRedirectOnFlatMatch(string $path = ''): void
    {
        $row = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0',
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'flat' => [
                        $path . '/' => [
                            1 => $row,
                        ],
                    ],
                ],
            ]
        );

        $result = $this->redirectService->matchRedirect('example.com', rawurlencode($path));

        self::assertSame($row, $result);
    }

    /**
     * @return array
     */
    public function matchRedirectReturnsRedirectOnFlatMatchDataProvider(): array
    {
        return [
            'default case' => [
                'foo',
            ],
            'umlauts' => [
                'äöü',
            ],
            'various special chars' => [
                'special-chars-«-∑-€-®-†-Ω-¨-ø-π-å-‚-∂-ƒ-©-ª-º-∆-@-¥-≈-ç-√-∫-~-µ-∞-…-–',
            ],
            'chinese' => [
                '应用',
            ],
            'hindi' => [
                'कंपनी',
            ],
            'cyrilic' => [
                'cyrilic-АВГДЄЅЗИѲІКЛМНѮѺПЧ',
            ],
        ];
    }

    /**
     * @test
     */
    public function matchRedirectReturnsRedirectOnRespectQueryParametersMatch(): void
    {
        $row = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '1',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0',
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'respect_query_parameters' => [
                        'index.php?id=123' => [
                            1 => $row,
                        ],
                    ],
                ],
            ]
        );

        $result = $this->redirectService->matchRedirect('example.com', 'index.php', 'id=123');

        self::assertSame($row, $result);
    }

    /**
     * @test
     */
    public function matchRedirectReturnsRedirectOnRespectQueryParametersMatchWithSlash(): void
    {
        $row = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '1',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0',
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'respect_query_parameters' => [
                        'index.php/?id=123' => [
                            1 => $row,
                        ],
                    ],
                ],
            ]
        );

        $result = $this->redirectService->matchRedirect('example.com', 'index.php', 'id=123');

        self::assertSame($row, $result);
    }

    /**
     * @test
     */
    public function matchRedirectReturnsRedirectOnFullRespectQueryParametersMatch(): void
    {
        $row = [
            'target' => 'https://example.com/target',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '1',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0',
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'respect_query_parameters' => [
                        'index.php?id=123&a=b' => [
                            1 => $row,
                        ],
                    ],
                ],
            ]
        );

        $result = $this->redirectService->matchRedirect('example.com', 'index.php', 'id=123&a=b');

        self::assertSame($row, $result);
    }

    /**
     * @test
     */
    public function matchRedirectReturnsNullOnPartialRespectQueryParametersMatch(): void
    {
        $row = [
            'target' => 'https://example.com/target',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '1',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0',
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'respect_query_parameters' => [
                        'index.php?id=123&a=b' => [
                            1 => $row,
                        ],
                    ],
                ],
            ]
        );

        $result = $this->redirectService->matchRedirect('example.com', 'index.php', 'id=123&a=a');

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function matchRedirectReturnsMatchingRedirectWithMatchingQueryParametersOverMatchingPath(): void
    {
        $row1 = [
            'target' => 'https://example.com/no-promotion',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '0',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0',
        ];
        $row2 = [
            'target' => 'https://example.com/promotion',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '1',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0',
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'flat' => [
                        'special/page/' =>
                        [
                            1 => $row1,
                        ],
                    ],
                    'respect_query_parameters' => [
                        'special/page?key=998877' => [
                            1 => $row2,
                        ],
                    ],
                ],
            ]
        );

        $result = $this->redirectService->matchRedirect('example.com', 'special/page', 'key=998877');

        self::assertSame($row2, $result);
    }

    /**
     * @test
     */
    public function matchRedirectReturnsRedirectSpecificToDomainOnFlatMatchIfSpecificAndNonSpecificExist(): void
    {
        $row1 = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0',
        ];
        $row2 = [
            'target' => 'https://example.net',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0',
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'flat' => [
                        'foo/' => [
                            1 => $row1,
                        ],
                    ],
                ],
                '*' => [
                    'flat' => [
                        'foo/' => [
                            2 => $row2,
                        ],
                    ],
                ],
            ]
        );

        $result = $this->redirectService->matchRedirect('example.com', 'foo');

        self::assertSame($row1, $result);
    }

    /**
     * @test
     */
    public function matchRedirectReturnsRedirectOnRegexMatch(): void
    {
        $row = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0',
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'regexp' => [
                        '/f.*?/' => [
                            1 => $row,
                        ],
                    ],
                ],
            ]
        );

        $result = $this->redirectService->matchRedirect('example.com', 'foo');

        self::assertSame($row, $result);
    }

    /**
     * @test
     */
    public function matchRedirectReturnsOnlyActiveRedirects(): void
    {
        $row1 = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'starttime' => '0',
            'endtime' => '0',
            'disabled' => '1',
        ];
        $row2 = [
            'target' => 'https://example.net',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'starttime' => '0',
            'endtime' => '0',
            'disabled' => '0',
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'flat' => [
                        'foo/' => [
                            1 => $row1,
                            2 => $row2,
                        ],
                    ],
                ],
            ]
        );

        $result = $this->redirectService->matchRedirect('example.com', 'foo');

        self::assertSame($row2, $result);
    }

    /**
     * @test
     */
    public function getTargetUrlReturnsNullIfUrlCouldNotBeResolved(): void
    {
        $this->linkServiceProphecy->resolve(Argument::any())->willThrow(new InvalidPathException('', 1516531195));

        $result = $this->redirectService->getTargetUrl(['target' => 'invalid'], new ServerRequest(new Uri()));

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function getTargetUrlReturnsUrlForTypeUrl(): void
    {
        $redirectTargetMatch = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
        ];
        $linkDetails = [
            'type' => LinkService::TYPE_URL,
            'url' => 'https://example.com/',
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $source = new Uri('https://example.com');
        $request = new ServerRequest($source);
        $request = $request->withAttribute('site', new Site('dummy', 13, []));
        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, $request);

        $uri = new Uri('https://example.com/');
        self::assertEquals($uri, $result);
    }

    /**
     * @test
     */
    public function getTargetUrlReturnsUrlForTypeFile(): void
    {
        $fileProphecy = $this->prophesize(File::class);
        $fileProphecy->getPublicUrl()->willReturn('https://example.com/file.txt');
        $redirectTargetMatch = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
        ];
        $linkDetails = [
            'type' => LinkService::TYPE_FILE,
            'file' => $fileProphecy->reveal(),
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $source = new Uri('https://example.com');
        $request = new ServerRequest($source);
        $request = $request->withAttribute('site', new Site('dummy', 13, []));
        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, $request);

        $uri = new Uri('https://example.com/file.txt');
        self::assertEquals($uri, $result);
    }

    /**
     * @test
     */
    public function getTargetUrlReturnsUrlForTypeFolder(): void
    {
        $folderProphecy = $this->prophesize(Folder::class);
        $folderProphecy->getPublicUrl()->willReturn('https://example.com/folder/');
        $redirectTargetMatch = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
        ];
        $folder = $folderProphecy->reveal();
        $linkDetails = [
            'type' => LinkService::TYPE_FOLDER,
            'folder' => $folder,
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $source = new Uri('https://example.com/');
        $request = new ServerRequest($source);
        $request = $request->withAttribute('site', new Site('dummy', 13, []));
        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, $request);

        $uri = new Uri('https://example.com/folder/');
        self::assertEquals($uri, $result);
    }

    /**
     * @test
     */
    public function getTargetUrlRespectsForceHttps(): void
    {
        $redirectTargetMatch = [
            'target' => 'https://example.com',
            'keep_query_parameters' => '0',
            'force_https' => '1',
        ];
        $linkDetails = [
            'type' => LinkService::TYPE_URL,
            'url' => 'http://example.com',
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $source = new Uri('https://example.com');
        $request = new ServerRequest($source);
        $request = $request->withAttribute('site', new Site('dummy', 13, []));
        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, $request);

        $uri = new Uri('https://example.com');
        self::assertEquals($uri, $result);
    }

    /**
     * @test
     */
    public function getTargetUrlAddsExistingQueryParams(): void
    {
        $redirectTargetMatch = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '1',
        ];
        $linkDetails = [
            'type' => LinkService::TYPE_URL,
            'url' => 'https://example.com/?foo=1&bar=2',
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $source = new Uri('https://example.com/?bar=2&baz=4&foo=1');
        $request = new ServerRequest($source);
        $request = $request->withQueryParams(['bar' => 3, 'baz' => 4]);
        $request = $request->withAttribute('site', new Site('dummy', 13, []));
        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, $request);

        $uri = new Uri('https://example.com/?bar=2&baz=4&foo=1');
        self::assertEquals($uri, $result);
    }

    /**
     * @test
     */
    public function getTargetUrlRespectsAdditionalParametersFromTypolink(): void
    {
        /** @var RedirectService $redirectService */
        $redirectService = $this->getAccessibleMock(
            RedirectService::class,
            ['getUriFromCustomLinkDetails'],
            [$this->redirectCacheServiceProphecy->reveal(), $this->linkServiceProphecy->reveal(), $this->siteFinder->reveal(), $this->redirectRepository->reveal()],
            '',
            true
        );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $redirectService->setLogger($loggerProphecy->reveal());

        $pageRecord = 't3://page?uid=13';
        $redirectTargetMatch = [
            'target' => $pageRecord . ' - - - foo=bar',
            'force_https' => 1,
            'keep_query_parameters' => 1,
        ];

        $linkDetails = [
            'pageuid' => 13,
            'type' => LinkService::TYPE_PAGE,
        ];
        $this->linkServiceProphecy->resolve($pageRecord)->willReturn($linkDetails);

        $queryParams = [];
        $queryParams['foo'] = 'bar';
        $uri = new Uri('/page?foo=bar');

        $frontendUserAuthentication = new FrontendUserAuthentication();
        $site = new Site('dummy', 13, []);
        $request = new ServerRequest($uri);
        $request = $request->withQueryParams($queryParams);
        $request = $request->withAttribute('site', $site);
        $request = $request->withAttribute('frontend.user', $frontendUserAuthentication);
        $redirectService->method('getUriFromCustomLinkDetails')
            ->with($redirectTargetMatch, $site, $linkDetails, $queryParams, $request)
            ->willReturn($uri);
        $result = $redirectService->getTargetUrl($redirectTargetMatch, $request);

        self::assertEquals($uri, $result);
    }

    /**
     * @test
     */
    public function getTargetUrlReplaceRegExpCaptureGroup(): void
    {
        $redirectTargetMatch = [
            'source_path' => '#^/foo/(.*)#',
            'target' => 'https://anotherdomain.com/$1',
            'force_https' => '0',
            'keep_query_parameters' => '1',
            'is_regexp' => 1,
        ];
        $linkDetails = [
            'type' => LinkService::TYPE_URL,
            'url' => 'https://anotherdomain.com/$1',
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $source = new Uri('https://example.com/foo/bar');
        $request = new ServerRequest($source);
        $request = $request->withAttribute('site', new Site('dummy', 13, []));
        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, $request);

        $uri = new Uri('https://anotherdomain.com/bar');
        self::assertEquals($uri, $result);
    }

    public function getTargetUrlWithQueryReplaceRegExpCaptureGroupDataProvider(): array
    {
        $cyrilicPlain = 'АВГДЄЅЗИѲІКЛМНѮѺПЧ';
        return [
            'index.php with query capture group - plain value' => [
                '#^/index.php\?option=com_content&page=(.*)#',
                'https://anotherdomain.com/$1',
                'https://example.com/index.php?option=com_content&page=target',
                'https://anotherdomain.com/target',
            ],
            'index.php with query capture group - cyrilic value' => [
                '#^/index.php\?option=com_content&page=(.*)#',
                'https://anotherdomain.com/$1',
                sprintf('https://example.com/index.php?option=com_content&page=%s', $cyrilicPlain),
                sprintf('https://anotherdomain.com/%s', $cyrilicPlain),
            ],
            'capture group in path and query capture group - cyrilic value' => [
                '#^/index-(.*).php\?option=com_content&page=(.*)#',
                'https://anotherdomain.com/$1/$2',
                sprintf('https://example.com/index-%s.php?option=com_content&page=cyrilic-%s', $cyrilicPlain, $cyrilicPlain),
                sprintf('https://anotherdomain.com/%s/cyrilic-%s', $cyrilicPlain, $cyrilicPlain),
            ],
            'cyrilic path with non-cyrilic capture group' => [
                sprintf('#^/index-%s.php\?option=com_content&page=(.*)#', $cyrilicPlain),
                'https://anotherdomain.com/$1',
                sprintf('https://example.com/index-%s.php?option=com_content&page=cyrilic-%s', $cyrilicPlain, $cyrilicPlain),
                sprintf('https://anotherdomain.com/cyrilic-%s', $cyrilicPlain),
            ],
            'cyrilic path with cyrilic capture group' => [
                sprintf('#^/index-%s.php\?option=com_content&page=(.*)#', $cyrilicPlain),
                'https://anotherdomain.com/$1',
                sprintf('https://example.com/index-%s.php?option=com_content&page=cyrilic-%s', $cyrilicPlain, $cyrilicPlain),
                sprintf('https://anotherdomain.com/cyrilic-%s', $cyrilicPlain),
            ],
            'cyrilic path with cyrilic capture group with cyrilic prefix' => [
                sprintf('#^/index-%s.php\?option=com_content&page=%s(.*)#', $cyrilicPlain, $cyrilicPlain),
                'https://anotherdomain.com/$1',
                sprintf('https://example.com/index-%s.php?option=com_content&page=%scyrilic-%s', $cyrilicPlain, $cyrilicPlain, $cyrilicPlain),
                sprintf('https://anotherdomain.com/cyrilic-%s', $cyrilicPlain),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getTargetUrlWithQueryReplaceRegExpCaptureGroupDataProvider
     */
    public function getTargetUrlWithQueryReplaceRegExpCaptureGroup(
        string $redirectSourcePath,
        string $redirectTarget,
        string $requestUri,
        string $expectedRedirectUri
    ) {
        $redirectTargetMatch = [
            'uid' => 1,
            'source_path' => $redirectSourcePath,
            'target' => $redirectTarget,
            'force_https' => '0',
            'keep_query_parameters' => 0,
            'is_regexp' => 1,
            'respect_query_parameters' => 1,
        ];
        $linkDetails = [
            'type' => LinkService::TYPE_URL,
            'url' => $redirectTarget,
            'query' => '',
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $source = new Uri($requestUri);
        $queryParams = [];
        parse_str($source->getQuery(), $queryParams);
        $request = new ServerRequest($source);
        $request = $request->withQueryParams($queryParams);
        $request = $request->withAttribute('site', new Site('dummy', 13, []));
        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, $request);

        $uri = new Uri($expectedRedirectUri);
        self::assertEquals($uri, $result);
    }

    public function getTargetUrlWithQueryAndSlashReplaceRegExpCaptureGroupDataProvider(): array
    {
        $cyrilicPlain = 'АВГДЄЅЗИѲІКЛМНѮѺПЧ';
        return [
            'index with path slash with query capture group - plain value' => [
                '#^/index/\?option=com_content&page=(.*)#',
                'https://anotherdomain.com/$1',
                'https://example.com/index/?option=com_content&page=target',
                'https://anotherdomain.com/target?option=com_content&page=target',
            ],
            'index with query capture group - cyrilic value' => [
                '#^/index/\?option=com_content&page=(.*)#',
                'https://anotherdomain.com/$1',
                sprintf('https://example.com/index/?option=com_content&page=%s', $cyrilicPlain),
                sprintf('https://anotherdomain.com/%s?option=com_content&page=%s', $cyrilicPlain, $cyrilicPlain),
            ],
            'capture group in path and query capture group - cyrilic value' => [
                '#^/index-(.*)/\?option=com_content&page=(.*)#',
                'https://anotherdomain.com/$1/$2',
                sprintf('https://example.com/index-%s/?option=com_content&page=cyrilic-%s', $cyrilicPlain, $cyrilicPlain),
                sprintf('https://anotherdomain.com/%s/cyrilic-%s?option=com_content&page=cyrilic-%s', $cyrilicPlain, $cyrilicPlain, $cyrilicPlain),
            ],
            'cyrilic path with non-cyrilic capture group' => [
                sprintf('#^/index-%s/\?option=com_content&page=(.*)#', $cyrilicPlain),
                'https://anotherdomain.com/$1',
                sprintf('https://example.com/index-%s/?option=com_content&page=cyrilic', $cyrilicPlain),
                'https://anotherdomain.com/cyrilic?option=com_content&page=cyrilic',
            ],
            'cyrilic path with cyrilic capture group' => [
                sprintf('#^/index-%s/\?option=com_content&page=(.*)#', $cyrilicPlain),
                'https://anotherdomain.com/$1',
                sprintf('https://example.com/index-%s/?option=com_content&page=cyrilic-%s', $cyrilicPlain, $cyrilicPlain),
                sprintf('https://anotherdomain.com/cyrilic-%s?option=com_content&page=cyrilic-%s', $cyrilicPlain, $cyrilicPlain),
            ],
            'cyrilic path with cyrilic capture group with cyrilic prefix' => [
                sprintf('#^/index-%s/\?option=com_content&page=%s(.*)#', $cyrilicPlain, $cyrilicPlain),
                'https://anotherdomain.com/$1',
                sprintf('https://example.com/index-%s/?option=com_content&page=%scyrilic-%s', $cyrilicPlain, $cyrilicPlain, $cyrilicPlain),
                sprintf('https://anotherdomain.com/cyrilic-%s?option=com_content&page=%scyrilic-%s', $cyrilicPlain, $cyrilicPlain, $cyrilicPlain),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getTargetUrlWithQueryAndSlashReplaceRegExpCaptureGroupDataProvider
     */
    public function getTargetUrlWithQueryAndSlashReplaceRegExpCaptureGroup(
        string $redirectSourcePath,
        string $redirectTarget,
        string $requestUri,
        string $expectedRedirectUri
    ) {
        $redirectTargetMatch = [
            'uid' => 1,
            'source_path' => $redirectSourcePath,
            'target' => $redirectTarget,
            'force_https' => '0',
            'keep_query_parameters' => '1',
            'is_regexp' => 1,
            'respect_query_parameters' => 1,
        ];
        $linkDetails = [
            'type' => LinkService::TYPE_URL,
            'url' => $redirectTarget,
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $source = new Uri($requestUri);
        $queryParams = [];
        parse_str($source->getQuery(), $queryParams);
        $request = new ServerRequest($source);
        $request = $request->withQueryParams($queryParams);
        $request = $request->withAttribute('site', new Site('dummy', 13, []));
        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, $request);

        $uri = new Uri($expectedRedirectUri);
        self::assertEquals($uri, $result);
    }
}
