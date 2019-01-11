<?php
declare(strict_types = 1);
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

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FrontendLoginControllerTest extends UnitTestCase
{
    /**
     * @var bool Restore Environment after tests
     */
    protected $backupEnvironment = true;

    /**
     * @var \TYPO3\CMS\Felogin\Controller\FrontendLoginController|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
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

    protected $resetSingletonInstances = true;

    /**
     * Set up
     */
    protected function setUp()
    {
        parent::setUp();
        $GLOBALS['TSFE'] = new \stdClass();
        $this->testHostName = 'hostname.tld';
        $this->testSitePath = '/';
        $this->accessibleFixture = $this->getAccessibleMock(\TYPO3\CMS\Felogin\Controller\FrontendLoginController::class, ['dummy']);
        $this->accessibleFixture->cObj = $this->createMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $this->accessibleFixture->_set('frontendController', $this->createMock(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class));
        $this->accessibleFixture->setLogger(new NullLogger());

        $site = new Site('dummy', 1, ['base' => 'http://sub.domainhostname.tld/path/']);
        $mockedSiteFinder = $this->getAccessibleMock(SiteFinder::class, ['getSiteByPageId'], [], '', false, false);
        $mockedSiteFinder->method('getSiteByPageId')->willReturn($site);
        $this->accessibleFixture->_set('siteFinder', $mockedSiteFinder);

        $this->setUpFakeSitePathAndHost();
    }

    /**
     * Set up a fake site path and host
     */
    protected function setUpFakeSitePathAndHost()
    {
        $_SERVER['ORIG_PATH_INFO'] = $_SERVER['PATH_INFO'] = $_SERVER['ORIG_SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] = $this->testSitePath . TYPO3_mainDir;
        $_SERVER['HTTP_HOST'] = $this->testHostName;
    }

    /**
     * Data provider for validateRedirectUrlClearsUrl
     *
     * @return array
     */
    public function validateRedirectUrlClearsUrlDataProvider()
    {
        return [
            'absolute URL, hostname not in site, trailing slash' => ['http://badhost.tld/'],
            'absolute URL, hostname not in site, no trailing slash' => ['http://badhost.tld'],
            'absolute URL, subdomain in site, but main domain not, trailing slash' => ['http://domainhostname.tld.badhost.tld/'],
            'absolute URL, subdomain in site, but main domain not, no trailing slash' => ['http://domainhostname.tld.badhost.tld'],
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
            'sane absolute URL' => ['http://sub.domainhostname.tld/path/'],
            'sane absolute URL with script' => ['http://sub.domainhostname.tld/path/index.php?id=1'],
            'sane absolute URL with realurl' => ['http://sub.domainhostname.tld/path/foo/bar/foo.html'],
            'sane absolute URL with homedir' => ['http://sub.domainhostname.tld/path/~user/'],
            'sane absolute URL with some strange chars encoded' => ['http://sub.domainhostname.tld/path/~user/a%cc%88o%cc%88%c3%9fa%cc%82/foo.html'],
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
            'absolute URL, correct subdirectory of site base' => ['http://sub.domainhostname.tld/path/'],
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
        $this->testSitePath = '/subdir/';
        $this->setUpFakeSitePathAndHost();
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
                [],
            ],
            'simple additional parameter is not preserved if not specified in preservedGETvars' => [
                [
                    'id' => 42,
                    'special' => 23,
                ],
                '',
                [],
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
                [
                    'special1' => 23,
                    'special2' => [
                        'foo' => 'bar',
                    ],
                ]
            ],
            'preserve single parameter' => [
                [
                    'L' => 42,
                ],
                'L',
                [
                    'L' => 42,
                ],
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
                [
                    'L' => 3,
                    'tx_someext' => [
                        'foo' => 'simple',
                        'bar' => [
                            'baz' => 'simple',
                        ],
                    ],
                ],
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
                [
                    'L' => 3,
                    'tx_someext' => [
                        'bar' => [
                            'baz' => 'simple',
                        ],
                    ],
                ],
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
                [
                    'L' => 3,
                    'tx_ext2' => [
                        'foo' => 'simple',
                    ],
                    'tx_ext3' => [
                        'bar' => [
                            'baz' => 'simple',
                        ],
                    ],
                ],
            ],
            'preserved value that does not exist in get' => [
                [],
                'L,foo%5Bbar%5D',
                [],
             ],
        ];
    }

    /**
     * @test
     * @dataProvider getPreserveGetVarsReturnsCorrectResultDataProvider
     * @param array $getArray
     * @param string $preserveVars
     * @param string $expected
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
        $this->accessibleFixture->_set('logintype', LoginType::LOGIN);
        $this->accessibleFixture->_set('referer', 'http://www.example.com/snafu');
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $this->accessibleFixture->_get('frontendController');
        $this->accessibleFixture->_set('userIsLoggedIn', true);
        $this->assertSame(['http://www.example.com/snafu'], $this->accessibleFixture->_call('processRedirect'));
    }

    /**
     *
     */
    public function processUserFieldsRespectsDefaultConfigurationForStdWrapDataProvider()
    {
        return [
            'Simple casing' => [
                [
                    'username' => 'Holy',
                    'lastname' => 'Wood',
                ],
                [
                    'username.' => [
                        'case' => 'upper'
                    ]
                ],
                [
                    '###FEUSER_USERNAME###' => 'HOLY',
                    '###FEUSER_LASTNAME###' => 'Wood',
                    '###USER###' => 'HOLY'
                ]
            ],
            'Default config applies' => [
                [
                    'username' => 'Holy',
                    'lastname' => 'O" Mally',
                ],
                [
                    'username.' => [
                        'case' => 'upper'
                    ]
                ],
                [
                    '###FEUSER_USERNAME###' => 'HOLY',
                    '###FEUSER_LASTNAME###' => 'O&quot; Mally',
                    '###USER###' => 'HOLY'
                ]
            ],
            'Specific config overrides default config' => [
                [
                    'username' => 'Holy',
                    'lastname' => 'O" Mally',
                ],
                [
                    'username.' => [
                        'case' => 'upper'
                    ],
                    'lastname.' => [
                        'htmlSpecialChars' => '0'
                    ]
                ],
                [
                    '###FEUSER_USERNAME###' => 'HOLY',
                    '###FEUSER_LASTNAME###' => 'O" Mally',
                    '###USER###' => 'HOLY'
                ]
            ],
            'No given user returns empty array' => [
                null,
                [
                    'username.' => [
                        'case' => 'upper'
                    ],
                    'lastname.' => [
                        'htmlSpecialChars' => '0'
                    ]
                ],
                []
            ],
        ];
    }

    /**
     * @test
     * @dataProvider processUserFieldsRespectsDefaultConfigurationForStdWrapDataProvider
     */
    public function processUserFieldsRespectsDefaultConfigurationForStdWrap($userRecord, $fieldConf, $expectedMarkers)
    {
        $tsfe = new \stdClass();
        $tsfe->fe_user = new \stdClass();
        $tsfe->fe_user->user = $userRecord;
        $conf = ['userfields.' => $fieldConf];
        $this->accessibleFixture->_set('cObj', new ContentObjectRenderer());
        $this->accessibleFixture->_set('frontendController', $tsfe);
        $this->accessibleFixture->_set('conf', $conf);
        $actualResult = $this->accessibleFixture->_call('getUserFieldMarkers');
        $this->assertEquals($expectedMarkers, $actualResult);
    }
}
