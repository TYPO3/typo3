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

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractTypolinkBuilderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected bool $backupEnvironment = true;

    protected MockObject|TypoScriptFrontendController|AccessibleObjectInterface $frontendControllerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createMockedLoggerAndLogManager();
        $this->frontendControllerMock = $this
            ->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    //////////////////////
    // Utility functions
    //////////////////////

    /**
     * Avoid logging to the file system (file writer is currently the only configured writer)
     */
    protected function createMockedLoggerAndLogManager(): void
    {
        $logManagerMock = $this->getMockBuilder(LogManager::class)->getMock();
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logManagerMock
            ->method('getLogger')
            ->willReturn($loggerMock);
        GeneralUtility::setSingletonInstance(LogManager::class, $logManagerMock);
    }

    /**
     * @return array The test data for forceAbsoluteUrlReturnsAbsoluteUrl
     */
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrlDataProvider(): array
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

    /**
     * @param string $expected The expected URL
     * @param string $url The URL to parse and manipulate
     * @param array $configuration The configuration array
     * @test
     * @dataProvider forceAbsoluteUrlReturnsCorrectAbsoluteUrlDataProvider
     */
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrl(string $expected, string $url, array $configuration): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getPublicPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $this->frontendControllerMock->absRefPrefix = '';
        $cObj = new ContentObjectRenderer($this->frontendControllerMock, new Container());
        // Force hostname
        $serverRequest = new ServerRequest(
            'http://localhost/index.php',
            'GET',
            null,
            [],
            ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/index.php']
        );
        $cObj->setRequest($serverRequest);
        $subject = $this->getAccessibleMock(
            AbstractTypolinkBuilder::class,
            ['build'],
            [$cObj, $this->frontendControllerMock]
        );

        self::assertEquals($expected, $subject->_call('forceAbsoluteUrl', $url, $configuration));
    }

    /**
     * @test
     */
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrlWithSubfolder(): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getPublicPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $cObj = new ContentObjectRenderer($this->frontendControllerMock, new Container());

        // Force hostname
        $serverRequest = new ServerRequest(
            'http://localhost/subfolder/index.php',
            'GET',
            null,
            [],
            ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/subfolder/index.php']
        );
        $cObj->setRequest($serverRequest);
        $subject = $this->getAccessibleMock(
            AbstractTypolinkBuilder::class,
            ['build'],
            [$cObj, $this->frontendControllerMock]
        );

        $expected = 'http://localhost/subfolder/fileadmin/my.pdf';
        $url = 'fileadmin/my.pdf';
        $configuration = [
            'forceAbsoluteUrl' => '1',
        ];

        self::assertEquals($expected, $subject->_call('forceAbsoluteUrl', $url, $configuration));
    }

    /**
     * Data provider for resolveTargetAttribute
     *
     * @return array [[$expected, $conf, $name],]
     */
    public function resolveTargetAttributeDataProvider(): array
    {
        $targetName = StringUtility::getUniqueId('name_');
        $target = StringUtility::getUniqueId('target_');
        return [
            'Take target from $conf, if $conf[$targetName] is set.' =>
                [
                    $target,
                    [$targetName => $target], // $targetName is set
                    $targetName,
                ],
            ' If all hopes fail, an empty string is returned. ' =>
                [
                    '',
                    [],
                    $targetName,
                ],
            'It finally applies stdWrap' =>
                [
                    'wrap_target',
                    [$targetName . '.' =>
                        [ 'ifEmpty' => 'wrap_target' ],
                    ],
                    $targetName,
                ],
        ];
    }

    /**
     * @test
     * @dataProvider resolveTargetAttributeDataProvider
     */
    public function canResolveTheTargetAttribute(
        string $expected,
        array $conf,
        string $name,
    ): void {
        $cObj = new ContentObjectRenderer($this->frontendControllerMock, new Container());
        $serverRequest = new ServerRequest(
            'http://localhost/subfolder/index.php',
            'GET',
            null,
            [],
            ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/subfolder/index.php']
        );
        $cObj->setRequest($serverRequest);
        $subject = $this->getAccessibleMockForAbstractClass(AbstractTypolinkBuilder::class, [$cObj, $this->frontendControllerMock]);
        $actual = $subject->_call(
            'resolveTargetAttribute',
            $conf,
            $name,
        );
        self::assertEquals($expected, $actual);
    }
}
