<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Redirects\Tests\Unit\Service;

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\CMS\Redirects\Service\RedirectService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RedirectServiceTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var RedirectCacheService|ObjectProphecy
     */
    protected $redirectCacheServiceProphecy;

    /**
     * @var LinkService|ObjectProphecy
     */
    protected $linkServiceProphecy;

    /**
     * @var RedirectService
     */
    protected $redirectService;

    protected function setUp(): void
    {
        parent::setUp();
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->redirectCacheServiceProphecy = $this->prophesize(RedirectCacheService::class);
        $this->linkServiceProphecy = $this->prophesize(LinkService::class);

        $this->redirectService = new RedirectService($this->redirectCacheServiceProphecy->reveal(), $this->linkServiceProphecy->reveal());
        $this->redirectService->setLogger($loggerProphecy->reveal());

        $GLOBALS['SIM_ACCESS_TIME'] = 42;
    }

    /**
     * @test
     */
    public function matchRedirectReturnsNullIfNoRedirectsExist()
    {
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn([]);

        $result = $this->redirectService->matchRedirect('example.com', 'foo');

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function matchRedirectReturnsRedirectOnFlatMatch()
    {
        $row = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0'
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'flat' => [
                        'foo/' => [
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
    public function matchRedirectReturnsRedirectOnRespectQueryParametersMatch()
    {
        $row = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '1',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0'
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
    public function matchRedirectReturnsRedirectOnRespectQueryParametersMatchWithSlash()
    {
        $row = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '1',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0'
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
    public function matchRedirectReturnsRedirectOnFullRespectQueryParametersMatch()
    {
        $row = [
            'target' => 'https://example.com/target',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '1',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0'
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
    public function matchRedirectReturnsNullOnPartialRespectQueryParametersMatch()
    {
        $row = [
            'target' => 'https://example.com/target',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '1',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0'
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

        self::assertSame(null, $result);
    }

    /**
     * @test
     */
    public function matchRedirectReturnsMatchingRedirectWithMatchingQueryParametersOverMatchingPath()
    {
        $row1 = [
            'target' => 'https://example.com/no-promotion',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '0',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0'
        ];
        $row2 = [
            'target' => 'https://example.com/promotion',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'respect_query_parameters' => '1',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0'
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'flat' => [
                        'special/page/' =>
                        [
                            1 => $row1,
                        ]
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
    public function matchRedirectReturnsRedirectSpecificToDomainOnFlatMatchIfSpecificAndNonSpecificExist()
    {
        $row1 = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0'
        ];
        $row2 = [
            'target' => 'https://example.net',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0'
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
    public function matchRedirectReturnsRedirectOnRegexMatch()
    {
        $row = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'disabled' => '0',
            'starttime' => '0',
            'endtime' => '0'
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
    public function matchRedirectReturnsOnlyActiveRedirects()
    {
        $row1 = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'starttime' => '0',
            'endtime' => '0',
            'disabled' => '1'
        ];
        $row2 = [
            'target' => 'https://example.net',
            'force_https' => '0',
            'keep_query_parameters' => '0',
            'target_statuscode' => '307',
            'starttime' => '0',
            'endtime' => '0',
            'disabled' => '0'
        ];
        $this->redirectCacheServiceProphecy->getRedirects()->willReturn(
            [
                'example.com' => [
                    'flat' => [
                        'foo/' => [
                            1 => $row1,
                            2 => $row2
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
    public function getTargetUrlReturnsNullIfUrlCouldNotBeResolved()
    {
        $this->linkServiceProphecy->resolve(Argument::any())->willThrow(new InvalidPathException('', 1516531195));

        $result = $this->redirectService->getTargetUrl(['target' => 'invalid'], [], new Site('dummy', 13, []));

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function getTargetUrlReturnsUrlForTypeUrl()
    {
        $redirectTargetMatch = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '0'
        ];
        $linkDetails = [
            'type' => LinkService::TYPE_URL,
            'url' => 'https://example.com/'
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, [], new Site('dummy', 13, []));

        $uri = new Uri('https://example.com/');
        self::assertEquals($uri, $result);
    }

    /**
     * @test
     */
    public function getTargetUrlReturnsUrlForTypeFile()
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
            'file' => $fileProphecy->reveal()
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, [], new Site('dummy', 13, []));

        $uri = new Uri('https://example.com/file.txt');
        self::assertEquals($uri, $result);
    }

    /**
     * @test
     */
    public function getTargetUrlReturnsUrlForTypeFolder()
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
            'folder' => $folder
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, [], new Site('dummy', 13, []));

        $uri = new Uri('https://example.com/folder/');
        self::assertEquals($uri, $result);
    }

    /**
     * @test
     */
    public function getTargetUrlRespectsForceHttps()
    {
        $redirectTargetMatch = [
            'target' => 'https://example.com',
            'keep_query_parameters' => '0',
            'force_https' => '1',
        ];
        $linkDetails = [
            'type' => LinkService::TYPE_URL,
            'url' => 'http://example.com'
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, [], new Site('dummy', 13, []));

        $uri = new Uri('https://example.com');
        self::assertEquals($uri, $result);
    }

    /**
     * @test
     */
    public function getTargetUrlAddsExistingQueryParams()
    {
        $redirectTargetMatch = [
            'target' => 'https://example.com',
            'force_https' => '0',
            'keep_query_parameters' => '1'
        ];
        $linkDetails = [
            'type' => LinkService::TYPE_URL,
            'url' => 'https://example.com/?foo=1&bar=2'
        ];
        $this->linkServiceProphecy->resolve($redirectTargetMatch['target'])->willReturn($linkDetails);

        $result = $this->redirectService->getTargetUrl($redirectTargetMatch, ['bar' => 3, 'baz' => 4], new Site('dummy', 13, []));

        $uri = new Uri('https://example.com/?bar=2&baz=4&foo=1');
        self::assertEquals($uri, $result);
    }
}
