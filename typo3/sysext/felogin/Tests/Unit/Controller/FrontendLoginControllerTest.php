<?php
namespace TYPO3\CMS\Felogin\Tests\Unit\Controller;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case
 */
class FrontendLoginControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Felogin\Controller\FrontendLoginController|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $accessibleFixture;

    /**
     * @var string
     */
    protected $testHostName;

    /**
     * @var string
     */
    protected $testSitePath;

    /**
     * @var string
     */
    protected $testTableName;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->testTableName = 'sys_domain';
        $this->testHostName = 'hostname.tld';
        $this->testSitePath = '/';
        $this->accessibleFixture = $this->getAccessibleMock(\TYPO3\CMS\Felogin\Controller\FrontendLoginController::class, ['dummy']);
        $this->accessibleFixture->cObj = $this->getMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $this->accessibleFixture->_set('frontendController', $this->getMock(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class, [], [], '', false));
        $this->setUpFakeSitePathAndHost();
    }

    /**
     * Set up a fake site path and host
     */
    protected function setUpFakeSitePathAndHost()
    {
        GeneralUtility::flushInternalRuntimeCaches();
        $_SERVER['ORIG_PATH_INFO'] = $_SERVER['PATH_INFO'] = $_SERVER['ORIG_SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] = $this->testSitePath . TYPO3_mainDir;
        $_SERVER['HTTP_HOST'] = $this->testHostName;
    }

    /**
     * Mock database
     */
    protected function setUpDatabaseMock()
    {
        $db = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['exec_SELECTgetRows']);
        $db
            ->expects($this->any())
            ->method('exec_SELECTgetRows')
            ->will($this->returnCallback([$this, 'getDomainRecordsCallback']));
        $this->accessibleFixture->_set('databaseConnection', $db);
    }

    /**
     * Callback method for pageIdCanBeDetermined test cases.
     * Simulates TYPO3_DB->exec_SELECTgetRows().
     *
     * @param string $fields
     * @param string $table
     * @param string $where
     * @return mixed
     * @see setUpDatabaseMock
     */
    public function getDomainRecordsCallback($fields, $table, $where)
    {
        if ($table !== $this->testTableName) {
            return false;
        }
        return [
            ['domainName' => 'domainhostname.tld'],
            ['domainName' => 'otherhostname.tld/path'],
            ['domainName' => 'sub.domainhostname.tld/path/']
        ];
    }

    /**
     * @test
     */
    public function typo3SitePathEqualsStubSitePath()
    {
        $this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'), $this->testSitePath);
    }

    /**
     * @test
     */
    public function typo3SiteUrlEqualsStubSiteUrl()
    {
        $this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), ('http://' . $this->testHostName) . $this->testSitePath);
    }

    /**
     * @test
     */
    public function typo3SitePathEqualsStubSitePathAfterChangingInTest()
    {
        $this->testHostName = 'somenewhostname.com';
        $this->testSitePath = '/somenewpath/';
        $this->setUpFakeSitePathAndHost();
        $this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'), $this->testSitePath);
    }

    /**
     * @test
     */
    public function typo3SiteUrlEqualsStubSiteUrlAfterChangingInTest()
    {
        $this->testHostName = 'somenewhostname.com';
        $this->testSitePath = '/somenewpath/';
        $this->setUpFakeSitePathAndHost();
        $this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), ('http://' . $this->testHostName) . $this->testSitePath);
    }

    /**
     * Data provider for validateRedirectUrlClearsUrl
     *
     * @return array
     */
    public function validateRedirectUrlClearsUrlDataProvider()
    {
        return [
            'absolute URL, hostname not in sys_domain, trailing slash' => ['http://badhost.tld/'],
            'absolute URL, hostname not in sys_domain, no trailing slash' => ['http://badhost.tld'],
            'absolute URL, subdomain in sys_domain, but main domain not, trailing slash' => ['http://domainhostname.tld.badhost.tld/'],
            'absolute URL, subdomain in sys_domain, but main domain not, no trailing slash' => ['http://domainhostname.tld.badhost.tld'],
            'non http absolute URL 1' => ['its://domainhostname.tld/itunes/'],
            'non http absolute URL 2' => ['ftp://domainhostname.tld/download/'],
            'XSS attempt 1' => ['javascript:alert(123)'],
            'XSS attempt 2' => ['" onmouseover="alert(123)"'],
            'invalid URL, HTML break out attempt' => ['" >blabuubb'],
            'invalid URL, UNC path' => ['\\\\foo\\bar\\'],
            'invalid URL, backslashes in path' => ['http://domainhostname.tld\\bla\\blupp'],
            'invalid URL, linefeed in path' => ['http://domainhostname.tld/bla/blupp' . LF],
            'invalid URL, only one slash after scheme' => ['http:/domainhostname.tld/bla/blupp'],
            'invalid URL, illegal chars' => ['http://(<>domainhostname).tld/bla/blupp'],
        ];
    }

    /**
     * @test
     * @dataProvider validateRedirectUrlClearsUrlDataProvider
     * @param string $url Invalid Url
     */
    public function validateRedirectUrlClearsUrl($url)
    {
        $this->setUpDatabaseMock();
        $this->assertEquals('', $this->accessibleFixture->_call('validateRedirectUrl', $url));
    }

    /**
     * Data provider for validateRedirectUrlKeepsCleanUrl
     *
     * @return array
     */
    public function validateRedirectUrlKeepsCleanUrlDataProvider()
    {
        return [
            'sane absolute URL' => ['http://domainhostname.tld/'],
            'sane absolute URL with script' => ['http://domainhostname.tld/index.php?id=1'],
            'sane absolute URL with realurl' => ['http://domainhostname.tld/foo/bar/foo.html'],
            'sane absolute URL with homedir' => ['http://domainhostname.tld/~user/'],
            'sane absolute URL with some strange chars encoded' => ['http://domainhostname.tld/~user/a%cc%88o%cc%88%c3%9fa%cc%82/foo.html'],
            'sane absolute URL (domain record with path)' => ['http://otherhostname.tld/path/'],
            'sane absolute URL with script (domain record with path)' => ['http://otherhostname.tld/path/index.php?id=1'],
            'sane absolute URL with realurl (domain record with path)' => ['http://otherhostname.tld/path/foo/bar/foo.html'],
            'sane absolute URL (domain record with path and slash)' => ['http://sub.domainhostname.tld/path/'],
            'sane absolute URL with script (domain record with path slash)' => ['http://sub.domainhostname.tld/path/index.php?id=1'],
            'sane absolute URL with realurl (domain record with path slash)' => ['http://sub.domainhostname.tld/path/foo/bar/foo.html'],
            'relative URL, no leading slash 1' => ['index.php?id=1'],
            'relative URL, no leading slash 2' => ['foo/bar/index.php?id=2'],
            'relative URL, leading slash, no realurl' => ['/index.php?id=1'],
            'relative URL, leading slash, realurl' => ['/de/service/imprint.html'],
        ];
    }

    /**
     * @test
     * @dataProvider validateRedirectUrlKeepsCleanUrlDataProvider
     * @param string $url Clean URL to test
     */
    public function validateRedirectUrlKeepsCleanUrl($url)
    {
        $this->setUpDatabaseMock();
        $this->assertEquals($url, $this->accessibleFixture->_call('validateRedirectUrl', $url));
    }

    /**
     * Data provider for validateRedirectUrlClearsInvalidUrlInSubdirectory
     *
     * @return array
     */
    public function validateRedirectUrlClearsInvalidUrlInSubdirectoryDataProvider()
    {
        return [
            'absolute URL, missing subdirectory' => ['http://hostname.tld/'],
            'absolute URL, wrong subdirectory' => ['http://hostname.tld/hacker/index.php'],
            'absolute URL, correct subdirectory, no trailing slash' => ['http://hostname.tld/subdir'],
            'absolute URL, correct subdirectory of sys_domain record, no trailing slash' => ['http://otherhostname.tld/path'],
            'absolute URL, correct subdirectory of sys_domain record, no trailing slash, subdomain' => ['http://sub.domainhostname.tld/path'],
            'relative URL, leading slash, no path' => ['/index.php?id=1'],
            'relative URL, leading slash, wrong path' => ['/de/sub/site.html'],
            'relative URL, leading slash, slash only' => ['/'],
        ];
    }

    /**
     * @test
     * @dataProvider validateRedirectUrlClearsInvalidUrlInSubdirectoryDataProvider
     * @param string $url Invalid Url
     */
    public function validateRedirectUrlClearsInvalidUrlInSubdirectory($url)
    {
        $this->testSitePath = '/subdir/';
        $this->setUpFakeSitePathAndHost();
        $this->setUpDatabaseMock();
        $this->assertEquals('', $this->accessibleFixture->_call('validateRedirectUrl', $url));
    }

    /**
     * Data provider for validateRedirectUrlKeepsCleanUrlInSubdirectory
     *
     * @return array
     */
    public function validateRedirectUrlKeepsCleanUrlInSubdirectoryDataProvider()
    {
        return [
            'absolute URL, correct subdirectory' => ['http://hostname.tld/subdir/'],
            'absolute URL, correct subdirectory, realurl' => ['http://hostname.tld/subdir/de/imprint.html'],
            'absolute URL, correct subdirectory, no realurl' => ['http://hostname.tld/subdir/index.php?id=10'],
            'absolute URL, correct subdirectory of sys_domain record' => ['http://otherhostname.tld/path/'],
            'absolute URL, correct subdirectory of sys_domain record, subdomain' => ['http://sub.domainhostname.tld/path/'],
            'relative URL, no leading slash, realurl' => ['de/service/imprint.html'],
            'relative URL, no leading slash, no realurl' => ['index.php?id=1'],
            'relative nested URL, no leading slash, no realurl' => ['foo/bar/index.php?id=2']
        ];
    }

    /**
     * @test
     * @dataProvider validateRedirectUrlKeepsCleanUrlInSubdirectoryDataProvider
     * @param string $url Invalid Url
     */
    public function validateRedirectUrlKeepsCleanUrlInSubdirectory($url)
    {
        $this->testSitePath = '/subdir/';
        $this->setUpFakeSitePathAndHost();
        $this->setUpDatabaseMock();
        $this->assertEquals($url, $this->accessibleFixture->_call('validateRedirectUrl', $url));
    }

    /*************************
     * Test concerning getPreverveGetVars
     *************************/

    /**
     * @return array
     */
    public function getPreserveGetVarsReturnsCorrectResultDataProvider()
    {
        return [
            'special get var id is not preserved' => [
                [
                    'id' => 42,
                ],
                '',
                '',
            ],
            'simple additional parameter is not preserved if not specified in preservedGETvars' => [
                [
                    'id' => 42,
                    'special' => 23,
                ],
                '',
                '',
            ],
            'all params except ignored ones are preserved if preservedGETvars is set to "all"' => [
                [
                    'id' => 42,
                    'special1' => 23,
                    'special2' => [
                        'foo' => 'bar',
                    ],
                    'tx_felogin_pi1' => [
                        'forgot' => 1,
                    ],
                ],
                'all',
                '&special1=23&special2[foo]=bar',
            ],
            'preserve single parameter' => [
                [
                    'L' => 42,
                ],
                'L',
                '&L=42'
            ],
            'preserve whole parameter array' => [
                [
                    'L' => 3,
                    'tx_someext' => [
                        'foo' => 'simple',
                        'bar' => [
                            'baz' => 'simple',
                        ],
                    ],
                ],
                'L,tx_someext',
                '&L=3&tx_someext[foo]=simple&tx_someext[bar][baz]=simple',
            ],
            'preserve part of sub array' => [
                [
                    'L' => 3,
                    'tx_someext' => [
                        'foo' => 'simple',
                        'bar' => [
                            'baz' => 'simple',
                        ],
                    ],
                ],
                'L,tx_someext[bar]',
                '&L=3&tx_someext[bar][baz]=simple',
            ],
            'preserve keys on different levels' => [
                [
                    'L' => 3,
                    'no-preserve' => 'whatever',
                    'tx_ext2' => [
                        'foo' => 'simple',
                    ],
                    'tx_ext3' => [
                        'bar' => [
                            'baz' => 'simple',
                        ],
                        'go-away' => '',
                    ],
                ],
                'L,tx_ext2,tx_ext3[bar]',
                '&L=3&tx_ext2[foo]=simple&tx_ext3[bar][baz]=simple',
            ],
            'preserved value that does not exist in get' => [
                [],
                'L,foo[bar]',
                ''
            ],
            'url params are encoded' => [
                ['tx_ext1' => 'param with spaces and \\ %<>& /'],
                'L,tx_ext1',
                '&tx_ext1=param%20with%20spaces%20and%20%5C%20%25%3C%3E%26%20%2F'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getPreserveGetVarsReturnsCorrectResultDataProvider
     * @param array $getArray
     * @param string $preserveVars
     * @param string $expected
     * @return void
     */
    public function getPreserveGetVarsReturnsCorrectResult(array $getArray, $preserveVars, $expected)
    {
        $_GET = $getArray;
        $this->accessibleFixture->conf['preserveGETvars'] = $preserveVars;
        $this->assertSame($expected, $this->accessibleFixture->_call('getPreserveGetVars'));
    }

    /**************************************************
     * Tests concerning isInLocalDomain
     **************************************************/

    /**
     * Dataprovider for isInCurrentDomainIgnoresScheme
     *
     * @return array
     */
    public function isInCurrentDomainIgnoresSchemeDataProvider()
    {
        return [
            'url https, current host http' => [
                'example.com', // HTTP_HOST
                '0', // HTTPS
                'https://example.com/foo.html' // URL
            ],
            'url http, current host https' => [
                'example.com',
                '1',
                'http://example.com/foo.html'
            ],
            'url https, current host https' => [
                'example.com',
                '1',
                'https://example.com/foo.html'
            ],
            'url http, current host http' => [
                'example.com',
                '0',
                'http://example.com/foo.html'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider isInCurrentDomainIgnoresSchemeDataProvider
     * @param string $host $_SERVER['HTTP_HOST']
     * @param string $https $_SERVER['HTTPS']
     * @param string $url The url to test
     */
    public function isInCurrentDomainIgnoresScheme($host, $https, $url)
    {
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['HTTPS'] = $https;
        $this->assertTrue($this->accessibleFixture->_call('isInCurrentDomain', $url));
    }

    /**
     * @return array
     */
    public function isInCurrentDomainReturnsFalseIfDomainsAreDifferentDataProvider()
    {
        return [
            'simple difference' => [
                'example.com', // HTTP_HOST
                'http://typo3.org/foo.html' // URL
            ],
            'subdomain different' => [
                'example.com',
                'http://foo.example.com/bar.html'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider isInCurrentDomainReturnsFalseIfDomainsAreDifferentDataProvider
     * @param string $host $_SERVER['HTTP_HOST']
     * @param string $url The url to test
     */
    public function isInCurrentDomainReturnsFalseIfDomainsAreDifferent($host, $url)
    {
        $_SERVER['HTTP_HOST'] = $host;
        $this->assertFalse($this->accessibleFixture->_call('isInCurrentDomain', $url));
    }

    /**
     * @test
     */
    public function processRedirectReferrerDomainsMatchesDomains()
    {
        $conf = [
            'redirectMode' => 'refererDomains',
            'domains' => 'example.com'
        ];

        $this->accessibleFixture->_set('conf', $conf);
        $this->accessibleFixture->_set('logintype', 'login');
        $this->accessibleFixture->_set('referer', 'http://www.example.com/snafu');
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $this->accessibleFixture->_get('frontendController');
        $tsfe->loginUser = true;
        $this->assertSame(['http://www.example.com/snafu'], $this->accessibleFixture->_call('processRedirect'));
    }
}
