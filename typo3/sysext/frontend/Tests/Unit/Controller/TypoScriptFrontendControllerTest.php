<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Controller;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Testcase for TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
 */
class TypoScriptFrontendControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|TypoScriptFrontendController
     */
    protected $subject;

    protected function setUp()
    {
        GeneralUtility::flushInternalRuntimeCaches();
        $this->subject = $this->getAccessibleMock(TypoScriptFrontendController::class, ['dummy'], [], '', false);
        $this->subject->TYPO3_CONF_VARS = $GLOBALS['TYPO3_CONF_VARS'];
        $this->subject->TYPO3_CONF_VARS['SYS']['encryptionKey'] = '170928423746123078941623042360abceb12341234231';

        $pageRepository = $this->getMock(PageRepository::class);
        $this->subject->sys_page = $pageRepository;
    }

    /**
     * Tests concerning rendering content
     */

    /**
     * @test
     */
    public function headerAndFooterMarkersAreReplacedDuringIntProcessing()
    {
        $GLOBALS['TSFE'] = $this->setupTsfeMockForHeaderFooterReplacementCheck();
        $GLOBALS['TSFE']->INTincScript();
        $this->assertContains('headerData', $GLOBALS['TSFE']->content);
        $this->assertContains('footerData', $GLOBALS['TSFE']->content);
    }

    /**
     * This is the callback that mimics a USER_INT extension
     */
    public function INTincScript_processCallback()
    {
        $GLOBALS['TSFE']->additionalHeaderData[] = 'headerData';
        $GLOBALS['TSFE']->additionalFooterData[] = 'footerData';
    }

    /**
     * Setup a \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController object only for testing the header and footer
     * replacement during USER_INT rendering
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|TypoScriptFrontendController
     */
    protected function setupTsfeMockForHeaderFooterReplacementCheck()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TypoScriptFrontendController $tsfe */
        $tsfe = $this->getMock(TypoScriptFrontendController::class, [
            'INTincScript_process',
            'INTincScript_includeLibs',
            'INTincScript_loadJSCode',
            'setAbsRefPrefix',
            'regeneratePageTitle'
        ], [], '', false);
        $tsfe->expects($this->exactly(2))->method('INTincScript_process')->will($this->returnCallback([$this, 'INTincScript_processCallback']));
        $tsfe->content = file_get_contents(__DIR__ . '/Fixtures/renderedPage.html');
        $config = [
            'INTincScript_ext' => [
                'divKey' => '679b52796e75d474ccbbed486b6837ab',
            ],
            'INTincScript' => [
                'INT_SCRIPT.679b52796e75d474ccbbed486b6837ab' => [],
            ]
        ];
        $tsfe->config = $config;
        $GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\NullTimeTracker();

        return $tsfe;
    }

    /**
     * Tests concerning sL
     */

    /**
     * @test
     */
    public function localizationReturnsUnchangedStringIfNotLocallangLabel()
    {
        $string = $this->getUniqueId();
        $this->assertEquals($string, $this->subject->sL($string));
    }

    /**
     * Tests concerning getSysDomainCache
     */

    /**
     * @return array
     */
    public function getSysDomainCacheDataProvider()
    {
        return [
            'typo3.org' => [
                'typo3.org',
            ],
            'foo.bar' => [
                'foo.bar',
            ],
            'example.com' => [
                'example.com',
            ],
        ];
    }

    /**
     * @param string $currentDomain
     * @test
     * @dataProvider getSysDomainCacheDataProvider
     */
    public function getSysDomainCacheReturnsCurrentDomainRecord($currentDomain)
    {
        $_SERVER['HTTP_HOST'] = $currentDomain;
        $domainRecords = [
            'typo3.org' => [
                'uid' => '1',
                'pid' => '1',
                'domainName' => 'typo3.org',
                'forced' => 0,
            ],
            'foo.bar' => [
                'uid' => '2',
                'pid' => '1',
                'domainName' => 'foo.bar',
                'forced' => 0,
            ],
            'example.com' => [
                'uid' => '3',
                'pid' => '1',
                'domainName' => 'example.com',
                'forced' => 0,
            ],
        ];
        $GLOBALS['TYPO3_DB'] = $this->getMock(DatabaseConnection::class, ['exec_SELECTgetRows']);
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetRows')->willReturn($domainRecords);
        GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime')->flush();
        $expectedResult = [
            $domainRecords[$currentDomain]['pid'] => $domainRecords[$currentDomain],
        ];
        $this->assertEquals($expectedResult, $this->subject->_call('getSysDomainCache'));
    }

    /**
     * @param string $currentDomain
     * @test
     * @dataProvider getSysDomainCacheDataProvider
     */
    public function getSysDomainCacheReturnsForcedDomainRecord($currentDomain)
    {
        $_SERVER['HTTP_HOST'] = $currentDomain;
        $domainRecords = [
            'typo3.org' => [
                'uid' => '1',
                'pid' => '1',
                'domainName' => 'typo3.org',
                'forced' => 0,
            ],
            'foo.bar' => [
                'uid' => '2',
                'pid' => '1',
                'domainName' => 'foo.bar',
                'forced' => 1,
            ],
            'example.com' => [
                'uid' => '3',
                'pid' => '1',
                'domainName' => 'example.com',
                'forced' => 0,
            ],
        ];
        $GLOBALS['TYPO3_DB'] = $this->getMock(DatabaseConnection::class, ['exec_SELECTgetRows']);
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetRows')->willReturn($domainRecords);
        GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime')->flush();
        $expectedResult = [
            $domainRecords[$currentDomain]['pid'] => $domainRecords['foo.bar'],
        ];
        $this->assertEquals($expectedResult, $this->subject->_call('getSysDomainCache'));
    }

    /**
     * Tests concerning domainNameMatchesCurrentRequest
     */

    /**
     * @return array
     */
    public function domainNameMatchesCurrentRequestDataProvider()
    {
        return [
            'same domains' => [
                'typo3.org',
                'typo3.org',
                '/index.php',
                true,
            ],
            'same domains with subdomain' => [
                'www.typo3.org',
                'www.typo3.org',
                '/index.php',
                true,
            ],
            'different domains' => [
                'foo.bar',
                'typo3.org',
                '/index.php',
                false,
            ],
            'domain record with script name' => [
                'typo3.org',
                'typo3.org/foo/bar',
                '/foo/bar/index.php',
                true,
            ],
            'domain record with wrong script name' => [
                'typo3.org',
                'typo3.org/foo/bar',
                '/bar/foo/index.php',
                false,
            ],
        ];
    }

    /**
     * @param string $currentDomain
     * @param string $domainRecord
     * @param string $scriptName
     * @param bool $expectedResult
     * @test
     * @dataProvider domainNameMatchesCurrentRequestDataProvider
     */
    public function domainNameMatchesCurrentRequest($currentDomain, $domainRecord, $scriptName, $expectedResult)
    {
        $_SERVER['HTTP_HOST'] = $currentDomain;
        $_SERVER['SCRIPT_NAME'] = $scriptName;
        $this->assertEquals($expectedResult, $this->subject->domainNameMatchesCurrentRequest($domainRecord));
    }

    /**
     * @return array
     */
    public function baseUrlWrapHandlesDifferentUrlsDataProvider()
    {
        return [
            'without base url' => [
                '',
                'fileadmin/user_uploads/image.jpg',
                'fileadmin/user_uploads/image.jpg'
            ],
            'with base url' => [
                'http://www.google.com/',
                'fileadmin/user_uploads/image.jpg',
                'http://www.google.com/fileadmin/user_uploads/image.jpg'
            ],
            'without base url but with url prepended with a forward slash' => [
                '',
                '/fileadmin/user_uploads/image.jpg',
                '/fileadmin/user_uploads/image.jpg',
            ],
            'with base url but with url prepended with a forward slash' => [
                'http://www.google.com/',
                '/fileadmin/user_uploads/image.jpg',
                '/fileadmin/user_uploads/image.jpg',
            ],
        ];
    }

    /**
     * @dataProvider baseUrlWrapHandlesDifferentUrlsDataProvider
     * @test
     * @param string $baseUrl
     * @param string $url
     * @param string $expected
     */
    public function baseUrlWrapHandlesDifferentUrls($baseUrl, $url, $expected)
    {
        $this->subject->baseUrl = $baseUrl;
        $this->assertSame($expected, $this->subject->baseUrlWrap($url));
    }
}
