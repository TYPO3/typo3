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
}
