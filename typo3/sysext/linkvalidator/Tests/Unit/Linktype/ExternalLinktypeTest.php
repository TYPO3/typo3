<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Linkvalidator\Tests\Unit\Linktype;

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

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Linkvalidator\Linktype\ExternalLinktype;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExternalLinktypeTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->buildLanguageServiceProphecy()->reveal();
    }

    private function buildLanguageServiceProphecy(): ObjectProphecy
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy
            ->includeLLFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf');
        $languageServiceProphecy->getLL(Argument::any())->willReturn('translation string');
        return $languageServiceProphecy;
    }

    /**
     * @test
     */
    public function checkLinkWithExternalUrlNotFoundReturnsFalse()
    {
        $responseProphecy = $this->prophesize(Response::class);
        $responseProphecy->getStatusCode()->willReturn(404);

        $exceptionProphecy = $this->prophesize(ClientException::class);
        $exceptionProphecy->hasResponse()
            ->willReturn(true);
        $exceptionProphecy->getResponse()
            ->willReturn($responseProphecy->reveal());

        $url = 'https://example.org/~not-existing-url';
        $options = $this->getRequestHeaderOptions();
        $requestFactoryProphecy = $this->prophesize(RequestFactory::class);
        $requestFactoryProphecy->request($url, 'HEAD', $options)
            ->willThrow($exceptionProphecy->reveal());

        $optionsSecondTryWithGET = array_merge_recursive($options, ['headers' => ['Range' => 'bytes=0-4048']]);
        $requestFactoryProphecy->request($url, 'GET', $optionsSecondTryWithGET)
            ->willThrow($exceptionProphecy->reveal());
        $subject = new ExternalLinktype($requestFactoryProphecy->reveal());

        $result = $subject->checkLink($url, null, null);

        self::assertSame(false, $result);
    }

    /**
     * @test
     */
    public function checkLinkWithExternalUrlNotFoundResultsNotFoundErrorType()
    {
        $responseProphecy = $this->prophesize(Response::class);
        $responseProphecy->getStatusCode()->willReturn(404);

        $exceptionProphecy = $this->prophesize(ClientException::class);
        $exceptionProphecy->hasResponse()
            ->willReturn(true);
        $exceptionProphecy->getResponse()
            ->willReturn($responseProphecy->reveal());

        $options = $this->getRequestHeaderOptions();

        $url = 'https://example.org/~not-existing-url';
        $requestFactoryProphecy = $this->prophesize(RequestFactory::class);
        $requestFactoryProphecy->request($url, 'HEAD', $options)
            ->willThrow($exceptionProphecy->reveal());
        $optionsSecondTryWithGET = array_merge_recursive($options, ['headers' => ['Range' => 'bytes=0-4048']]);
        $requestFactoryProphecy->request($url, 'GET', $optionsSecondTryWithGET)
            ->willThrow($exceptionProphecy->reveal());
        $subject = new ExternalLinktype($requestFactoryProphecy->reveal());

        $subject->checkLink($url, null, null);
        $result = $subject->getErrorParams()['errorType'];

        self::assertSame(404, $result);
    }

    private function getCookieJarProphecy(): CookieJar
    {
        $cookieJar = $this->prophesize(CookieJar::class);
        $cookieJar = $cookieJar->reveal();
        GeneralUtility::addInstance(CookieJar::class, $cookieJar);
        return $cookieJar;
    }

    private function getRequestHeaderOptions(): array
    {
        return [
            'cookies' => $this->getCookieJarProphecy(),
            'allow_redirects' => ['strict' => true],
            'headers' => [
                'User-Agent' => 'TYPO3 linkvalidator',
                'Accept' => '*/*',
                'Accept-Language' => '*',
                'Accept-Encoding' => '*'
            ]
        ];
    }

    public function preprocessUrlsDataProvider()
    {
        // regression test for issue #92230: handle incomplete or faulty URLs gracefully
        yield 'faulty URL with mailto' => [
            'mailto:http://example.org',
            'mailto:http://example.org'
        ];
        yield 'Relative URL' => [
            '/abc',
            '/abc'
        ];

        // regression tests for issues #89488, #89682
        yield 'URL with query parameter and ampersand' => [
            'https://standards.cen.eu/dyn/www/f?p=204:6:0::::FSP_ORG_ID,FSP_LANG_ID:,22&cs=1A3FFBC44FAB6B2A181C9525249C3A829',
            'https://standards.cen.eu/dyn/www/f?p=204:6:0::::FSP_ORG_ID,FSP_LANG_ID:,22&cs=1A3FFBC44FAB6B2A181C9525249C3A829'
        ];
        yield 'URL with query parameter and ampersand with HTML entities' => [
            'https://standards.cen.eu/dyn/www/f?p=204:6:0::::FSP_ORG_ID,FSP_LANG_ID:,22&amp;cs=1A3FFBC44FAB6B2A181C9525249C3A829',
            'https://standards.cen.eu/dyn/www/f?p=204:6:0::::FSP_ORG_ID,FSP_LANG_ID:,22&cs=1A3FFBC44FAB6B2A181C9525249C3A829'
        ];

        // regression tests for #89378
        yield 'URL with path with dashes' => [
                'https://example.com/Unternehmen/Ausbildung-Qualifikation/Weiterbildung-in-Niedersachsen/',
                'https://example.com/Unternehmen/Ausbildung-Qualifikation/Weiterbildung-in-Niedersachsen/'
            ];
        yield 'URL with path with dashes (2)' => [
            'https://example.com/startseite/wirtschaft/wirtschaftsfoerderung/beratung-foerderung/gruenderberatung/gruenderforen.html',
            'https://example.com/startseite/wirtschaft/wirtschaftsfoerderung/beratung-foerderung/gruenderberatung/gruenderforen.html'
            ];
        yield 'URL with path with dashes (3)' => [
            'http://example.com/universitaet/die-uni-im-ueberblick/lageplan/gebaeude/building/120',
            'http://example.com/universitaet/die-uni-im-ueberblick/lageplan/gebaeude/building/120'
            ];
        yield 'URL with path and query parameters (including &, ~,; etc.)' => [
            'http://example.com/tv?bcpid=1701167454001&amp;amp;amp;bckey=AQ~~,AAAAAGL7LqU~,aXlKNnCf9d9Tmck-kOc4PGFfCgHjM5JR&amp;amp;amp;bctid=1040702768001',
            'http://example.com/tv?bcpid=1701167454001&amp;amp;bckey=AQ~~,AAAAAGL7LqU~,aXlKNnCf9d9Tmck-kOc4PGFfCgHjM5JR&amp;amp;bctid=1040702768001'
        ];

        // make sure we correctly handle URLs with query parameters and fragment etc.
        yield 'URL with query parameters, fragment, user, pass, port etc.' => [
            'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
            'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment'
        ];
        yield 'domain with special characters, URL with query parameters, fragment, user, pass, port etc.' => [
            'http://usr:pss@äxample.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
            'http://usr:pss@xn--xample-9ta.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment'
        ];

        // domains with special characters: should be converted to punycode
        yield 'domain with special characters' => [
                'https://www.grün-example.org',
                'https://www.xn--grn-example-uhb.org'
            ];
        yield 'domain with special characters and path' => [
                'https://www.grün-example.org/a/bcd-efg/sfsfsfsfsf',
                'https://www.xn--grn-example-uhb.org/a/bcd-efg/sfsfsfsfsf'
            ];
    }

    /**
     * @test
     * @dataProvider preprocessUrlsDataProvider
     */
    public function preprocessUrlReturnsCorrectString(string $inputUrl, $expectedResult)
    {
        $subject = new ExternalLinktype();
        $method = new \ReflectionMethod($subject, 'preprocessUrl');
        $method->setAccessible(true);
        $result = $method->invokeArgs($subject, [$inputUrl]);
        $this->assertEquals($result, $expectedResult);
    }
}
