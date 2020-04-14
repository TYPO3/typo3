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

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractTypolinkBuilderTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var bool Restore Environment after tests
     */
    protected $backupEnvironment = true;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TypoScriptFrontendController|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $frontendControllerMock;

    /**
     * Set up
     */
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
    protected function createMockedLoggerAndLogManager()
    {
        $logManagerMock = $this->getMockBuilder(LogManager::class)->getMock();
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logManagerMock->expects(self::any())
            ->method('getLogger')
            ->willReturn($loggerMock);
        GeneralUtility::setSingletonInstance(LogManager::class, $logManagerMock);
    }

    /**
     * @return array The test data for forceAbsoluteUrlReturnsAbsoluteUrl
     */
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrlDataProvider()
    {
        return [
            'Missing forceAbsoluteUrl leaves URL untouched' => [
                'foo',
                'foo',
                []
            ],
            'Absolute URL stays unchanged' => [
                'http://example.org/',
                'http://example.org/',
                [
                    'forceAbsoluteUrl' => '1'
                ]
            ],
            'Absolute URL stays unchanged 2' => [
                'http://example.org/resource.html',
                'http://example.org/resource.html',
                [
                    'forceAbsoluteUrl' => '1'
                ]
            ],
            'Scheme and host w/o ending slash stays unchanged' => [
                'http://example.org',
                'http://example.org',
                [
                    'forceAbsoluteUrl' => '1'
                ]
            ],
            'Scheme can be forced' => [
                'typo3://example.org',
                'http://example.org',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3'
                    ]
                ]
            ],
            'Relative path old-style' => [
                'http://localhost/fileadmin/dummy.txt',
                '/fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                ]
            ],
            'Relative path' => [
                'http://localhost/fileadmin/dummy.txt',
                'fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                ]
            ],
            'Scheme can be forced with pseudo-relative path' => [
                'typo3://localhost/fileadmin/dummy.txt',
                '/fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3'
                    ]
                ]
            ],
            'Hostname only is not treated as valid absolute URL' => [
                'http://localhost/example.org',
                'example.org',
                [
                    'forceAbsoluteUrl' => '1'
                ]
            ],
            'Scheme and host is added to local file path' => [
                'typo3://localhost/fileadmin/my.pdf',
                'fileadmin/my.pdf',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3'
                    ]
                ]
            ]
        ];
    }

    /**
     * @param string $expected The expected URL
     * @param string $url The URL to parse and manipulate
     * @param array $configuration The configuration array
     * @test
     * @dataProvider forceAbsoluteUrlReturnsCorrectAbsoluteUrlDataProvider
     */
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrl($expected, $url, array $configuration)
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getBackendPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $this->frontendControllerMock->absRefPrefix = '';
        $contentObjectRendererProphecy = $this->prophesize(ContentObjectRenderer::class);
        $subject = $this->getAccessibleMock(
            AbstractTypolinkBuilder::class,
            ['build'],
            [$contentObjectRendererProphecy->reveal(), $this->frontendControllerMock]
        );
        // Force hostname
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/typo3/index.php';
        self::assertEquals($expected, $subject->_call('forceAbsoluteUrl', $url, $configuration));
    }

    /**
     * @test
     */
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrlWithSubfolder()
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getBackendPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $contentObjectRendererProphecy = $this->prophesize(ContentObjectRenderer::class);
        $subject = $this->getAccessibleMock(
            AbstractTypolinkBuilder::class,
            ['build'],
            [$contentObjectRendererProphecy->reveal(), $this->frontendControllerMock]
        );
        // Force hostname
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/subfolder/typo3/index.php';

        $expected = 'http://localhost/subfolder/fileadmin/my.pdf';
        $url = 'fileadmin/my.pdf';
        $configuration = [
            'forceAbsoluteUrl' => '1'
        ];

        self::assertEquals($expected, $subject->_call('forceAbsoluteUrl', $url, $configuration));
    }

    /**
     * Data provider for resolveTargetAttribute
     *
     * @return array [[$expected, $conf, $name, $respectFrameSetOption, $fallbackTarget],]
     */
    public function resolveTargetAttributeDataProvider(): array
    {
        $targetName = StringUtility::getUniqueId('name_');
        $target = StringUtility::getUniqueId('target_');
        $fallback = StringUtility::getUniqueId('fallback_');
        return [
            'Take target from $conf, if $conf[$targetName] is set.' =>
                [
                    $target,
                    [$targetName => $target], // $targetName is set
                    $targetName,
                    true,
                    $fallback,
                    'other doctype'
                ],
            'Else from fallback, if not $respectFrameSetOption ...' =>
                [
                    $fallback,
                    ['directImageLink' => false],
                    $targetName,
                    false, // $respectFrameSetOption false
                    $fallback,
                    'other doctype'
                ],
            ' ... or no doctype ... ' =>
                [
                    $fallback,
                    ['directImageLink' => false],
                    $targetName,
                    true,
                    $fallback,
                    null                       // no $doctype
                ],
            ' ... or doctype xhtml_trans... ' =>
                [
                    $fallback,
                    ['directImageLink' => false],
                    $targetName,
                    true,
                    $fallback,
                    'xhtml_trans'
                ],
            ' ... or doctype xhtml_basic... ' =>
                [
                    $fallback,
                    ['directImageLink' => false],
                    $targetName,
                    true,
                    $fallback,
                    'xhtml_basic'
                ],
            ' ... or doctype html5... ' =>
                [
                    $fallback,
                    ['directImageLink' => false],
                    $targetName,
                    true,
                    $fallback,
                    'html5'
                ],
            ' If all hopes fail, an empty string is returned. ' =>
                [
                    '',
                    [],
                    $targetName,
                    true,
                    $fallback,
                    'other doctype'
                ],
            'It finally applies stdWrap' =>
                [
                    'wrap_target',
                    [$targetName . '.' =>
                        [ 'ifEmpty' => 'wrap_target' ]
                    ],
                    $targetName,
                    true,
                    $fallback,
                    'other doctype'
                ],
        ];
    }

    /**
     * @test
     * @dataProvider resolveTargetAttributeDataProvider
     * @param string $expected
     * @param array $conf
     * @param string $name
     * @param bool $respectFrameSetOption
     * @param string $fallbackTarget
     * @param string|null $doctype
     */
    public function canResolveTheTargetAttribute(
        string $expected,
        array $conf,
        string $name,
        bool $respectFrameSetOption,
        string $fallbackTarget,
        $doctype
    ) {
        $this->frontendControllerMock->config =
            ['config' => [ 'doctype' => $doctype]];
        $renderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $subject = $this->getAccessibleMockForAbstractClass(AbstractTypolinkBuilder::class, [$renderer, $this->frontendControllerMock]);
        $actual = $subject->_call(
            'resolveTargetAttribute',
            $conf,
            $name,
            $respectFrameSetOption,
            $fallbackTarget
        );
        self::assertEquals($expected, $actual);
    }
}
