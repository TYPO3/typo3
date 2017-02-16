<?php
namespace TYPO3\CMS\Frontend\Tests\Functional\Controller;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case
 */
class TypoScriptFrontendControllerTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @var TypoScriptFrontendController
     */
    protected $tsFrontendController;

    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/fixtures.xml');

        $GLOBALS['TSFE']->gr_list = '';
        $this->tsFrontendController = $this->getAccessibleMock(
            TypoScriptFrontendController::class,
            ['dummy'],
            [],
            '',
            false
        );

        $pageContextMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\Page\PageRepository::class)->getMock();
        $this->tsFrontendController->_set('sys_page', $pageContextMock);
    }

    /**
     * @test
     */
    public function getFirstTimeValueForRecordReturnCorrectData()
    {
        $this->assertSame(
            $this->getFirstTimeValueForRecordCall('tt_content:2', 1),
            2,
            'The next start/endtime should be 2'
        );
        $this->assertSame(
            $this->getFirstTimeValueForRecordCall('tt_content:2', 2),
            3,
            'The next start/endtime should be 3'
        );
        $this->assertSame(
            $this->getFirstTimeValueForRecordCall('tt_content:2', 4),
            5,
            'The next start/endtime should be 5'
        );
        $this->assertSame(
            $this->getFirstTimeValueForRecordCall('tt_content:2', 5),
            PHP_INT_MAX,
            'The next start/endtime should be PHP_INT_MAX as there are no more'
        );
        $this->assertSame(
            $this->getFirstTimeValueForRecordCall('tt_content:3', 1),
            PHP_INT_MAX,
            'Should be PHP_INT_MAX as table has not this PID'
        );
        $this->assertSame(
            $this->getFirstTimeValueForRecordCall('fe_groups:2', 1),
            PHP_INT_MAX,
            'Should be PHP_INT_MAX as table fe_groups has no start/endtime in TCA'
        );
    }

    /**
     * @param string $currentDomain
     * @test
     * @dataProvider getSysDomainCacheDataProvider
     */
    public function getSysDomainCacheReturnsCurrentDomainRecord($currentDomain)
    {
        GeneralUtility::flushInternalRuntimeCaches();

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

        foreach ($domainRecords as $domainRecord) {
            (new ConnectionPool())->getConnectionForTable('sys_domain')->insert(
                'sys_domain',
                $domainRecord
            );
        }

        GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime')->flush();
        $expectedResult = [
            $domainRecords[$currentDomain]['pid'] => $domainRecords[$currentDomain],
        ];

        $actualResult = $this->tsFrontendController->_call('getSysDomainCache');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @param string $currentDomain
     * @test
     * @dataProvider getSysDomainCacheDataProvider
     */
    public function getSysDomainCacheReturnsForcedDomainRecord($currentDomain)
    {
        GeneralUtility::flushInternalRuntimeCaches();

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

        foreach ($domainRecords as $domainRecord) {
            (new ConnectionPool())->getConnectionForTable('sys_domain')->insert(
                'sys_domain',
                $domainRecord
            );
        }

        GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime')->flush();
        $expectedResult = [
            $domainRecords[$currentDomain]['pid'] => $domainRecords['foo.bar'],
        ];
        $actualResult = $this->tsFrontendController->_call('getSysDomainCache');

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @param string $tablePid
     * @param int $now
     * @return int
     */
    public function getFirstTimeValueForRecordCall($tablePid, $now)
    {
        return $this->tsFrontendController->_call('getFirstTimeValueForRecord', $tablePid, $now);
    }

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
}
