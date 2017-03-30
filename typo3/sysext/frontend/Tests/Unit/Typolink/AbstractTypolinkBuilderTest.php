<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Typolink;

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

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractTypolinkBuilderTest extends UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TypoScriptFrontendController|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $frontendControllerMock = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TemplateService
     */
    protected $templateServiceMock = null;

    /**
     * Set up
     */
    protected function setUp()
    {
        GeneralUtility::flushInternalRuntimeCaches();

        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->createMockedLoggerAndLogManager();

        $this->templateServiceMock =
            $this->getMockBuilder(TemplateService::class)
            ->setMethods(['getFileName', 'linkData'])->getMock();
        $pageRepositoryMock =
            $this->getAccessibleMock(PageRepository::class, ['getRawRecord', 'getMountPointInfo']);
        $this->frontendControllerMock =
            $this->getAccessibleMock(TypoScriptFrontendController::class,
            ['dummy'], [], '', false);
        $this->frontendControllerMock->tmpl = $this->templateServiceMock;
        $this->frontendControllerMock->config = [];
        $this->frontendControllerMock->page =  [];
        $this->frontendControllerMock->sys_page = $pageRepositoryMock;
        $GLOBALS['TSFE'] = $this->frontendControllerMock;
    }

    protected function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    //////////////////////
    // Utility functions
    //////////////////////

    /**
     * @return TypoScriptFrontendController
     */
    protected function getFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Avoid logging to the file system (file writer is currently the only configured writer)
     */
    protected function createMockedLoggerAndLogManager()
    {
        $logManagerMock = $this->getMockBuilder(LogManager::class)->getMock();
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logManagerMock->expects($this->any())
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
        $contentObjectRendererProphecy = $this->prophesize(ContentObjectRenderer::class);
        $subject = $this->getAccessibleMock(AbstractTypolinkBuilder::class,
            ['build'],
            [$contentObjectRendererProphecy->reveal()],
            '',
            false
        );
        // Force hostname
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/typo3/index.php';
        $GLOBALS['TSFE']->absRefPrefix = '';

        $this->assertEquals($expected, $subject->_call('forceAbsoluteUrl', $url, $configuration));
    }

    /**
     * @test
     */
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrlWithSubfolder()
    {
        $contentObjectRendererProphecy = $this->prophesize(ContentObjectRenderer::class);
        $subject = $this->getAccessibleMock(AbstractTypolinkBuilder::class,
            ['build'],
            [$contentObjectRendererProphecy->reveal()],
            '',
            false
        );
        // Force hostname
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/subfolder/typo3/index.php';

        $expected = 'http://localhost/subfolder/fileadmin/my.pdf';
        $url = 'fileadmin/my.pdf';
        $configuration = [
            'forceAbsoluteUrl' => '1'
        ];

        $this->assertEquals($expected, $subject->_call('forceAbsoluteUrl', $url, $configuration));
    }
}
