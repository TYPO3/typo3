<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Utility;

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

use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the \TYPO3\CMS\Core\Utility\ClientUtility class.
 */
class GeneralUtilityTest extends UnitTestCase
{
    public function splitHeaderLinesDataProvider(): array
    {
        return [
            'one-line, single header' => [
                ['Content-Security-Policy:default-src \'self\'; img-src https://*; child-src \'none\';'],
                ['Content-Security-Policy' => 'default-src \'self\'; img-src https://*; child-src \'none\';']
            ],
            'one-line, multiple headers' => [
                [
                    'Content-Security-Policy:default-src \'self\'; img-src https://*; child-src \'none\';',
                    'Content-Security-Policy-Report-Only:default-src https:; report-uri /csp-violation-report-endpoint/'
                ],
                [
                    'Content-Security-Policy' => 'default-src \'self\'; img-src https://*; child-src \'none\';',
                    'Content-Security-Policy-Report-Only' => 'default-src https:; report-uri /csp-violation-report-endpoint/'
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider splitHeaderLinesDataProvider
     * @param array $headers
     * @param array $expectedHeaders
     */
    public function splitHeaderLines(array $headers, array $expectedHeaders): void
    {
        $stream = $this->prophesize(StreamInterface::class);
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn($stream);
        $requestFactory = $this->prophesize(RequestFactory::class);
        $requestFactory->request(Argument::cetera())->willReturn($response);

        GeneralUtility::addInstance(RequestFactory::class, $requestFactory->reveal());
        GeneralUtility::getUrl('http://example.com', 0, $headers);

        $requestFactory->request(Argument::any(), Argument::any(), ['headers' => $expectedHeaders])
            ->shouldHaveBeenCalled();
    }
}
