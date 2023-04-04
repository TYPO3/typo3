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

namespace TYPO3\CMS\Linkvalidator\Tests\Unit\Linktype;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Linktype\ExternalLinktype;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExternalLinktypeTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->buildLanguageServiceMock();
    }

    private function buildLanguageServiceMock(): MockObject
    {
        $languageServiceMock = $this->getMockBuilder(LanguageService::class)->disableOriginalConstructor()->getMock();
        $languageServiceMock->method('getLL')->with(self::anything())->willReturn('translation string');
        return $languageServiceMock;
    }

    /**
     * @test
     */
    public function checkLinkWithExternalUrlNotFoundReturnsFalse(): void
    {
        $response = new Response(404);
        $clientExceptionMock = $this->getMockBuilder(ClientException::class)->disableOriginalConstructor()->getMock();
        $clientExceptionMock->expects(self::once())->method('hasResponse')->willReturn(true);
        $clientExceptionMock->expects(self::once())->method('getResponse')->willReturn($response);

        $url = 'https://example.org/~not-existing-url';
        $options = $this->getRequestHeaderOptions();
        $requestFactoryMock = $this->getMockBuilder(RequestFactory::class)->disableOriginalConstructor()->getMock();
        $requestFactoryMock->method('request')->with($url, 'HEAD', $options)
            ->willThrowException($clientExceptionMock);

        $optionsSecondTryWithGET = array_merge_recursive($options, ['headers' => ['Range' => 'bytes=0-4048']]);
        $requestFactoryMock->method('request')->with($url, 'GET', $optionsSecondTryWithGET)
            ->willThrowException($clientExceptionMock);
        $subject = new ExternalLinktype($requestFactoryMock);

        $result = $subject->checkLink($url, null, null);

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function checkLinkWithExternalUrlNotFoundResultsNotFoundErrorType(): void
    {
        $response = new Response(404);
        $clientExceptionMock = $this->getMockBuilder(ClientException::class)->disableOriginalConstructor()->getMock();
        $clientExceptionMock->expects(self::once())->method('hasResponse')->willReturn(true);
        $clientExceptionMock->expects(self::once())->method('getResponse')->willReturn($response);

        $options = $this->getRequestHeaderOptions();

        $url = 'https://example.org/~not-existing-url';
        $requestFactoryMock = $this->getMockBuilder(RequestFactory::class)->disableOriginalConstructor()->getMock();
        $requestFactoryMock->method('request')->with($url, 'HEAD', $options)
            ->willThrowException($clientExceptionMock);
        $optionsSecondTryWithGET = array_merge_recursive($options, ['headers' => ['Range' => 'bytes=0-4048']]);
        $requestFactoryMock->method('request')->with($url, 'GET', $optionsSecondTryWithGET)
            ->willThrowException($clientExceptionMock);

        $subject = new ExternalLinktype($requestFactoryMock);

        $subject->checkLink($url, null, null);
        $errorParams = $subject->getErrorParams();

        self::assertSame($errorParams['errorType'], 'httpStatusCode');
        self::assertSame($errorParams['errno'], 404);
    }

    private function getRequestHeaderOptions(): array
    {
        return [
            'cookies' => new CookieJar(),
            'allow_redirects' => ['strict' => true],
            'headers' => [
                'User-Agent' => 'TYPO3 linkvalidator',
                'Accept' => '*/*',
                'Accept-Language' => '*',
                'Accept-Encoding' => '*',
            ],
        ];
    }

    public static function preprocessUrlsDataProvider(): \Generator
    {
        // regression test for issue #92230: handle incomplete or faulty URLs gracefully
        yield 'faulty URL with mailto' => [
            'mailto:http://example.org',
            'mailto:http://example.org',
        ];
        yield 'Relative URL' => [
            '/abc',
            '/abc',
        ];

        // regression tests for issues #89488, #89682
        yield 'URL with query parameter and ampersand' => [
            'https://standards.cen.eu/dyn/www/f?p=204:6:0::::FSP_ORG_ID,FSP_LANG_ID:,22&cs=1A3FFBC44FAB6B2A181C9525249C3A829',
            'https://standards.cen.eu/dyn/www/f?p=204:6:0::::FSP_ORG_ID,FSP_LANG_ID:,22&cs=1A3FFBC44FAB6B2A181C9525249C3A829',
        ];
        yield 'URL with query parameter and ampersand with HTML entities' => [
            'https://standards.cen.eu/dyn/www/f?p=204:6:0::::FSP_ORG_ID,FSP_LANG_ID:,22&amp;cs=1A3FFBC44FAB6B2A181C9525249C3A829',
            'https://standards.cen.eu/dyn/www/f?p=204:6:0::::FSP_ORG_ID,FSP_LANG_ID:,22&cs=1A3FFBC44FAB6B2A181C9525249C3A829',
        ];

        // regression tests for #89378
        yield 'URL with path with dashes' => [
                'https://example.com/Unternehmen/Ausbildung-Qualifikation/Weiterbildung-in-Niedersachsen/',
                'https://example.com/Unternehmen/Ausbildung-Qualifikation/Weiterbildung-in-Niedersachsen/',
            ];
        yield 'URL with path with dashes (2)' => [
            'https://example.com/startseite/wirtschaft/wirtschaftsfoerderung/beratung-foerderung/gruenderberatung/gruenderforen.html',
            'https://example.com/startseite/wirtschaft/wirtschaftsfoerderung/beratung-foerderung/gruenderberatung/gruenderforen.html',
            ];
        yield 'URL with path with dashes (3)' => [
            'http://example.com/universitaet/die-uni-im-ueberblick/lageplan/gebaeude/building/120',
            'http://example.com/universitaet/die-uni-im-ueberblick/lageplan/gebaeude/building/120',
            ];
        yield 'URL with path and query parameters (including &, ~,; etc.)' => [
            'http://example.com/tv?bcpid=1701167454001&amp;amp;amp;bckey=AQ~~,AAAAAGL7LqU~,aXlKNnCf9d9Tmck-kOc4PGFfCgHjM5JR&amp;amp;amp;bctid=1040702768001',
            'http://example.com/tv?bcpid=1701167454001&amp;amp;bckey=AQ~~,AAAAAGL7LqU~,aXlKNnCf9d9Tmck-kOc4PGFfCgHjM5JR&amp;amp;bctid=1040702768001',
        ];

        // make sure we correctly handle URLs with query parameters and fragment etc.
        yield 'URL with query parameters, fragment, user, pass, port etc.' => [
            'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
            'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
        ];
        yield 'domain with special characters, URL with query parameters, fragment, user, pass, port etc.' => [
            'http://usr:pss@äxample.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
            'http://usr:pss@xn--xample-9ta.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
        ];

        // domains with special characters: should be converted to punycode
        yield 'domain with special characters' => [
                'https://www.grün-example.org',
                'https://www.xn--grn-example-uhb.org',
            ];
        yield 'domain with special characters and path' => [
                'https://www.grün-example.org/a/bcd-efg/sfsfsfsfsf',
                'https://www.xn--grn-example-uhb.org/a/bcd-efg/sfsfsfsfsf',
            ];
    }

    /**
     * @test
     * @dataProvider preprocessUrlsDataProvider
     */
    public function preprocessUrlReturnsCorrectString(string $inputUrl, $expectedResult): void
    {
        $subject = new ExternalLinktype(new RequestFactory(new GuzzleClientFactory()));
        $method = new \ReflectionMethod($subject, 'preprocessUrl');
        $result = $method->invokeArgs($subject, [$inputUrl]);
        self::assertEquals($result, $expectedResult);
    }

    /**
     * @test
     */
    public function setAdditionalConfigMergesHeaders(): void
    {
        $requestFactoryMock = $this->getMockBuilder(RequestFactory::class)->disableOriginalConstructor()->getMock();
        $requestFactoryMock->expects(self::once())->method('request')->with(
            'http://example.com',
            'HEAD',
            self::callback(static function ($result) {
                return $result['headers']['X-MAS'] === 'Merry!' && $result['headers']['User-Agent'] === 'TYPO3 linkvalidator';
            })
        );

        $externalLinkType = new ExternalLinktype($requestFactoryMock);
        $externalLinkType->setAdditionalConfig(['headers.' => [
            'X-MAS' => 'Merry!',
        ]]);

        $externalLinkType->checkLink(
            'http://example.com',
            [],
            $this->getMockBuilder(LinkAnalyzer::class)->disableOriginalConstructor()->getMock()
        );
    }

    /**
     * If the timeout is not set via TSconfig, core $GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout'] should
     * be used. Which is the case if timeout is not passed to the request() function.
     * @test
     */
    public function requestWithNoTimeoutIsCalledIfTimeoutNotSetByTsConfig(): void
    {
        $requestFactoryMock = $this->getMockBuilder(RequestFactory::class)->disableOriginalConstructor()->getMock();
        $requestFactoryMock->expects(self::once())->method('request')->with(
            'http://example.com',
            'HEAD',
            self::callback(static function ($result) {
                if (isset($result['timeout'])) {
                    return false;
                }
                return true;
            })
        );

        $externalLinkType = new ExternalLinktype($requestFactoryMock);
        $externalLinkType->setAdditionalConfig([]);
        $externalLinkType->checkLink(
            'http://example.com',
            [],
            $this->getMockBuilder(LinkAnalyzer::class)->disableOriginalConstructor()->getMock()
        );
    }

    /**
     * @test
     */
    public function setAdditionalConfigOverwritesUserAgent(): void
    {
        $requestFactoryMock = $this->getMockBuilder(RequestFactory::class)->disableOriginalConstructor()->getMock();
        $requestFactoryMock->expects(self::once())->method('request')->with(
            'http://example.com',
            'HEAD',
            self::callback(static function ($result) {
                return $result['headers']['User-Agent'] === 'TYPO3 Testing';
            })
        );

        $externalLinktype = new ExternalLinktype($requestFactoryMock);
        $externalLinktype->setAdditionalConfig([
            'httpAgentName' => 'TYPO3 Testing',
        ]);

        $externalLinktype->checkLink(
            'http://example.com',
            [],
            $this->getMockBuilder(LinkAnalyzer::class)->disableOriginalConstructor()->getMock()
        );
    }

    /**
     * @test
     */
    public function setAdditionalConfigAppendsAgentUrlIfConfigured(): void
    {
        $requestFactoryMock = $this->getMockBuilder(RequestFactory::class)->disableOriginalConstructor()->getMock();
        $requestFactoryMock->expects(self::once())->method('request')->with(
            'http://example.com',
            'HEAD',
            self::callback(static function ($result) {
                return $result['headers']['User-Agent'] === 'TYPO3 linkvalidator http://example.com';
            })
        );

        $externalLinkType = new ExternalLinktype($requestFactoryMock);
        $externalLinkType->setAdditionalConfig([
            'httpAgentUrl' => 'http://example.com',
        ]);

        $externalLinkType->checkLink(
            'http://example.com',
            [],
            $this->getMockBuilder(LinkAnalyzer::class)->disableOriginalConstructor()->getMock()
        );
    }

    /**
     * @test
     */
    public function setAdditionalConfigAppendsEmailIfConfigured(): void
    {
        $requestFactoryMock = $this->getMockBuilder(RequestFactory::class)->disableOriginalConstructor()->getMock();
        $requestFactoryMock->expects(self::once())->method('request')->with(
            'http://example.com',
            'HEAD',
            self::callback(static function ($result) {
                return $result['headers']['User-Agent'] === 'TYPO3 linkvalidator;mail@example.com';
            })
        );

        $externalLinktype = new ExternalLinktype($requestFactoryMock);
        $externalLinktype->setAdditionalConfig([
            'httpAgentEmail' => 'mail@example.com',
        ]);

        $externalLinktype->checkLink(
            'http://example.com',
            [],
            $this->getMockBuilder(LinkAnalyzer::class)->disableOriginalConstructor()->getMock()
        );
    }

    /**
     * @test
     */
    public function setAdditionalConfigAppendsEmailFromGlobalsIfConfigured(): void
    {
        $requestFactoryMock = $this->getMockBuilder(RequestFactory::class)->disableOriginalConstructor()->getMock();
        $requestFactoryMock->expects(self::once())->method('request')->with(
            'http://example.com',
            'HEAD',
            self::callback(static function ($result) {
                return $result['headers']['User-Agent'] === 'TYPO3 linkvalidator;test@example.com';
            })
        );

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'test@example.com';

        $externalLinkType = new ExternalLinktype($requestFactoryMock);
        $externalLinkType->setAdditionalConfig([]);

        $externalLinkType->checkLink(
            'http://example.com',
            [],
            $this->getMockBuilder(LinkAnalyzer::class)->disableOriginalConstructor()->getMock()
        );
    }

    /**
     * @test
     */
    public function setAdditionalConfigSetsRangeAndMethod(): void
    {
        $requestFactoryMock = $this->getMockBuilder(RequestFactory::class)->disableOriginalConstructor()->getMock();
        $requestFactoryMock->expects(self::once())->method('request')->with(
            'http://example.com',
            'GET',
            self::callback(static function ($result) {
                return $result['headers']['Range'] === 'bytes=0-2048';
            })
        );

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'test@example.com';

        $externalLinkType = new ExternalLinktype($requestFactoryMock);
        $externalLinkType->setAdditionalConfig([
            'method' => 'GET',
            'range' => '0-2048',
        ]);

        $externalLinkType->checkLink(
            'http://example.com',
            [],
            $this->getMockBuilder(LinkAnalyzer::class)->disableOriginalConstructor()->getMock()
        );
    }
}
