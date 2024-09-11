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

namespace TYPO3\CMS\Frontend\Tests\Unit\Typolink;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Tests\Unit\Typolink\Fixtures\AbstractTypolinkBuilderFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractTypolinkBuilderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;
    protected bool $backupEnvironment = true;

    protected function setUp(): void
    {
        parent::setUp();
        $logManagerMock = $this->getMockBuilder(LogManager::class)->getMock();
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logManagerMock->method('getLogger')->willReturn($loggerMock);
        GeneralUtility::setSingletonInstance(LogManager::class, $logManagerMock);
    }

    public static function forceAbsoluteUrlReturnsCorrectAbsoluteUrlDataProvider(): array
    {
        return [
            'Missing forceAbsoluteUrl leaves URL untouched' => [
                'foo',
                'foo',
                [],
            ],
            'Absolute URL stays unchanged' => [
                'http://example.org/',
                'http://example.org/',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Absolute URL stays unchanged 2' => [
                'http://example.org/resource.html',
                'http://example.org/resource.html',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Scheme and host w/o ending slash stays unchanged' => [
                'http://example.org',
                'http://example.org',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Scheme can be forced' => [
                'typo3://example.org',
                'http://example.org',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3',
                    ],
                ],
            ],
            'Relative path old-style' => [
                'http://localhost/fileadmin/dummy.txt',
                '/fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Relative path' => [
                'http://localhost/fileadmin/dummy.txt',
                'fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Scheme can be forced with pseudo-relative path' => [
                'typo3://localhost/fileadmin/dummy.txt',
                '/fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3',
                    ],
                ],
            ],
            'Hostname only is not treated as valid absolute URL' => [
                'http://localhost/example.org',
                'example.org',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Scheme and host is added to local file path' => [
                'typo3://localhost/fileadmin/my.pdf',
                'fileadmin/my.pdf',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3',
                    ],
                ],
            ],
            'Scheme can be forced with full URL with path' => [
                'typo3://example.org/subfolder/file.txt',
                'http://example.org/subfolder/file.txt',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('forceAbsoluteUrlReturnsCorrectAbsoluteUrlDataProvider')]
    #[Test]
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrl(string $expected, string $url, array $configuration): void
    {
        $cObj = new ContentObjectRenderer();
        // Force hostname
        $serverRequest = new ServerRequest(
            'http://localhost/index.php',
            'GET',
            null,
            [],
            ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/index.php']
        );
        $serverRequest = $serverRequest->withAttribute('currentContentObject', $cObj);
        $cObj->setRequest($serverRequest);
        $subject = new AbstractTypolinkBuilderFixture();
        self::assertEquals($expected, $subject->forceAbsoluteUrl($url, $configuration, $serverRequest));
    }

    #[Test]
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrlWithSubfolder(): void
    {
        $cObj = new ContentObjectRenderer();
        // Force hostname
        $serverRequest = new ServerRequest(
            'http://localhost/subfolder/index.php',
            'GET',
            null,
            [],
            ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/subfolder/index.php']
        );
        $cObj->setRequest($serverRequest);
        $serverRequest = $serverRequest->withAttribute('currentContentObject', $cObj);
        $subject = new AbstractTypolinkBuilderFixture();
        $expected = 'http://localhost/subfolder/fileadmin/my.pdf';
        $url = 'fileadmin/my.pdf';
        $configuration = [
            'forceAbsoluteUrl' => '1',
        ];
        self::assertEquals($expected, $subject->forceAbsoluteUrl($url, $configuration, $serverRequest));
    }

    public static function resolveTargetAttributeDataProvider(): array
    {
        $targetName = StringUtility::getUniqueId('name_');
        $target = StringUtility::getUniqueId('target_');
        return [
            'Take target from $conf, if $conf[$targetName] is set.' =>
                [
                    'expected' => $target,
                    'conf' => [$targetName => $target], // $targetName is set
                    'name' => $targetName,
                ],
            ' If all hopes fail, an empty string is returned. ' =>
                [
                    'expected' => '',
                    'conf' => [],
                    'name' => $targetName,
                ],
            'It finally applies stdWrap' =>
                [
                    'expected' => 'wrap_target',
                    'conf' => [$targetName . '.' =>
                        [ 'ifEmpty' => 'wrap_target' ],
                    ],
                    'name' => $targetName,
                ],
        ];
    }

    #[DataProvider('resolveTargetAttributeDataProvider')]
    #[Test]
    public function canResolveTheTargetAttribute(
        string $expected,
        array $conf,
        string $name,
    ): void {
        $cObj = new ContentObjectRenderer();
        $serverRequest = new ServerRequest(
            'http://localhost/subfolder/index.php',
            'GET',
            null,
            [],
            ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/subfolder/index.php']
        );
        $serverRequest = $serverRequest->withAttribute('currentContentObject', $cObj);
        $cObj->setRequest($serverRequest);
        $container = new Container();
        $container->set(EventDispatcherInterface::class, new NoopEventDispatcher());
        GeneralUtility::setContainer($container);
        $subject = new AbstractTypolinkBuilderFixture();
        self::assertEquals($expected, $subject->resolveTargetAttribute($conf, $name, $cObj));
    }
}
