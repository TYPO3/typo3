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

namespace TYPO3\CMS\FrontendLogin\Tests\Unit\Validation;

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\FrontendLogin\Validation\RedirectUrlValidator;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RedirectUrlValidatorTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    protected RedirectUrlValidator&AccessibleObjectInterface $accessibleFixture;
    protected RequestInterface $extbaseRequest;
    protected string $testHostName;
    protected string $testSitePath;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        $site1 = new Site('dummy', 1, ['base' => 'http://sub.domainhostname.tld/path/']);
        $site2 = new Site('dummy', 1, ['base' => 'http://sub2.domainhostname.tld/']);
        $mockedSiteFinder = $this->getAccessibleMock(SiteFinder::class, ['getAllSites'], [], '', false, false);
        $mockedSiteFinder->method('getAllSites')->willReturn([$site1, $site2]);

        $this->testHostName = 'hostname.tld';
        $this->testSitePath = '/';
        $this->accessibleFixture = $this->getAccessibleMock(RedirectUrlValidator::class, null, [$mockedSiteFinder]);
        $this->accessibleFixture->setLogger(new NullLogger());
        $this->setUpFakeSitePathAndHost();
    }

    /**
     * Set up a fake site path and host
     */
    protected function setUpFakeSitePathAndHost(): void
    {
        $_SERVER['ORIG_PATH_INFO'] = $_SERVER['PATH_INFO'] = $_SERVER['ORIG_SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] = $this->testSitePath . 'index.php';
        $_SERVER['HTTP_HOST'] = $this->testHostName;

        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $normalizedParams = NormalizedParams::createFromRequest($request);
        $request = $request->withAttribute('normalizedParams', $normalizedParams)->withAttribute('extbase', new ExtbaseRequestParameters());
        $this->extbaseRequest = new Request($request);
    }

    /**
     * Data provider for validateRedirectUrlClearsUrl
     */
    public function validateRedirectUrlClearsUrlDataProvider(): array
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
     */
    public function validateRedirectUrlClearsUrl(string $url): void
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
        self::assertFalse($this->accessibleFixture->isValid($this->extbaseRequest, $url));
    }

    /**
     * Data provider for validateRedirectUrlKeepsCleanUrl
     */
    public function validateRedirectUrlKeepsCleanUrlDataProvider(): array
    {
        return [
            'sane absolute URL' => ['http://sub.domainhostname.tld/path/'],
            'sane absolute URL with script' => ['http://sub.domainhostname.tld/path/index.php?id=1'],
            'sane absolute URL with routing' => ['http://sub.domainhostname.tld/path/foo/bar/foo.html'],
            'sane absolute URL with homedir' => ['http://sub.domainhostname.tld/path/~user/'],
            'sane absolute URL with some strange chars encoded' => ['http://sub.domainhostname.tld/path/~user/a%cc%88o%cc%88%c3%9fa%cc%82/foo.html'],
            'relative URL, no leading slash 1' => ['index.php?id=1'],
            'relative URL, no leading slash 2' => ['foo/bar/index.php?id=2'],
            'relative URL, leading slash, no routing' => ['/index.php?id=1'],
            'relative URL, leading slash, routing' => ['/de/service/imprint.html'],
        ];
    }

    /**
     * @test
     * @dataProvider validateRedirectUrlKeepsCleanUrlDataProvider
     */
    public function validateRedirectUrlKeepsCleanUrl(string $url): void
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
        self::assertTrue($this->accessibleFixture->isValid($this->extbaseRequest, $url));
    }

    /**
     * Data provider for validateRedirectUrlClearsInvalidUrlInSubdirectory
     */
    public function validateRedirectUrlClearsInvalidUrlInSubdirectoryDataProvider(): array
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
     */
    public function validateRedirectUrlClearsInvalidUrlInSubdirectory(string $url): void
    {
        GeneralUtility::flushInternalRuntimeCaches();
        $this->testSitePath = '/subdir/';
        $this->setUpFakeSitePathAndHost();
        self::assertFalse($this->accessibleFixture->isValid($this->extbaseRequest, $url));
    }

    /**
     * Data provider for validateRedirectUrlKeepsCleanUrlInSubdirectory
     */
    public function validateRedirectUrlKeepsCleanUrlInSubdirectoryDataProvider(): array
    {
        return [
            'absolute URL, correct subdirectory' => ['http://hostname.tld/subdir/'],
            'absolute URL, correct subdirectory, routing' => ['http://hostname.tld/subdir/de/imprint.html'],
            'absolute URL, correct subdirectory, no routing' => ['http://hostname.tld/subdir/index.php?id=10'],
            'absolute URL, correct subdirectory of site base' => ['http://sub.domainhostname.tld/path/'],
            'relative URL, no leading slash, routing' => ['de/service/imprint.html'],
            'relative URL, no leading slash, no routing' => ['index.php?id=1'],
            'relative nested URL, no leading slash, no routing' => ['foo/bar/index.php?id=2'],
        ];
    }

    /**
     * @test
     * @dataProvider validateRedirectUrlKeepsCleanUrlInSubdirectoryDataProvider
     */
    public function validateRedirectUrlKeepsCleanUrlInSubdirectory(string $url): void
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
        $this->testSitePath = '/subdir/';
        $this->setUpFakeSitePathAndHost();
        self::assertTrue($this->accessibleFixture->isValid($this->extbaseRequest, $url));
    }

    /**************************************************
     * Tests concerning isInCurrentDomain
     **************************************************/

    /**
     * Dataprovider for isInCurrentDomainIgnoresScheme
     */
    public function isInCurrentDomainIgnoresSchemeDataProvider(): array
    {
        return [
            'url https, current host http' => [
                'example.com', // HTTP_HOST
                '0', // HTTPS
                'https://example.com/foo.html', // URL
            ],
            'url http, current host https' => [
                'example.com',
                '1',
                'http://example.com/foo.html',
            ],
            'url https, current host https' => [
                'example.com',
                '1',
                'https://example.com/foo.html',
            ],
            'url http, current host http' => [
                'example.com',
                '0',
                'http://example.com/foo.html',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isInCurrentDomainIgnoresSchemeDataProvider
     * @param string $host $_SERVER['HTTP_HOST']
     * @param string $https $_SERVER['HTTPS']
     * @param string $url The url to test
     */
    public function isInCurrentDomainIgnoresScheme(string $host, string $https, string $url): void
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
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['HTTPS'] = $https;

        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $normalizedParams = NormalizedParams::createFromRequest($request);
        $request = $request->withAttribute('normalizedParams', $normalizedParams)->withAttribute('extbase', new ExtbaseRequestParameters());
        $extbaseRequest = new Request($request);

        self::assertTrue($this->accessibleFixture->_call('isInCurrentDomain', $extbaseRequest, $url));
    }

    public function isInCurrentDomainReturnsFalseIfDomainsAreDifferentDataProvider(): array
    {
        return [
            'simple difference' => [
                'example.com', // HTTP_HOST
                'http://typo3.org/foo.html', // URL
            ],
            'subdomain different' => [
                'example.com',
                'http://foo.example.com/bar.html',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isInCurrentDomainReturnsFalseIfDomainsAreDifferentDataProvider
     * @param string $host $_SERVER['HTTP_HOST']
     * @param string $url The url to test
     */
    public function isInCurrentDomainReturnsFalseIfDomainsAreDifferent(string $host, string $url): void
    {
        $_SERVER['HTTP_HOST'] = $host;

        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $normalizedParams = NormalizedParams::createFromRequest($request);
        $request = $request->withAttribute('normalizedParams', $normalizedParams)->withAttribute('extbase', new ExtbaseRequestParameters());
        $extbaseRequest = new Request($request);

        self::assertFalse($this->accessibleFixture->_call('isInCurrentDomain', $extbaseRequest, $url));
    }

    /**************************************************
     * Tests concerning isInLocalDomain
     **************************************************/

    /**
     * @test
     */
    public function isInLocalDomainValidatesSites(): void
    {
        $url = 'http://example.com';
        self::assertFalse($this->accessibleFixture->_call('isInLocalDomain', $url));

        $url = 'http://sub2.domainhostname.tld/some/path';
        self::assertTrue($this->accessibleFixture->_call('isInLocalDomain', $url));
    }
}
