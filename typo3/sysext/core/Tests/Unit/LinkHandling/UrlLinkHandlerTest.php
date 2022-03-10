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

namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling;

use TYPO3\CMS\Core\LinkHandling\LinkHandlingInterface;
use TYPO3\CMS\Core\LinkHandling\UrlLinkHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UrlLinkHandlerTest extends UnitTestCase
{

    /**
     * Data to resolve strings to arrays and vice versa, external, mail, page
     *
     * @return array
     */
    public function resolveParametersForNonFilesDataProvider(): array
    {
        return [
            'URL without a scheme' => [
                [
                    'url' => 'www.have.you/ever?did=this',
                ],
                [
                    'url' => 'http://www.have.you/ever?did=this',
                ],
                'http://www.have.you/ever?did=this',
            ],
            'http URL' => [
                [
                    'url' => 'http://www.have.you/ever?did=this',
                ],
                [
                    'url' => 'http://www.have.you/ever?did=this',
                ],
                'http://www.have.you/ever?did=this',
            ],
            'https URL' => [
                [
                    'url' => 'https://www.have.you/ever?did=this',
                ],
                [
                    'url' => 'https://www.have.you/ever?did=this',
                ],
                'https://www.have.you/ever?did=this',
            ],
            'https URL with port' => [
                [
                    'url' => 'https://www.have.you:8088/ever?did=this',
                ],
                [
                    'url' => 'https://www.have.you:8088/ever?did=this',
                ],
                'https://www.have.you:8088/ever?did=this',
            ],
            'ftp URL' => [
                [
                    'url' => 'ftp://www.have.you/ever?did=this',
                ],
                [
                    'url' => 'ftp://www.have.you/ever?did=this',
                ],
                'ftp://www.have.you/ever?did=this',
            ],
            'afp URL' => [
                [
                    'url' => 'afp://www.have.you/ever?did=this',
                ],
                [
                    'url' => 'afp://www.have.you/ever?did=this',
                ],
                'afp://www.have.you/ever?did=this',
            ],
            'sftp URL' => [
                [
                    'url' => 'sftp://nice:andsecret@www.have.you:23/ever?did=this',
                ],
                [
                    'url' => 'sftp://nice:andsecret@www.have.you:23/ever?did=this',
                ],
                'sftp://nice:andsecret@www.have.you:23/ever?did=this',
            ],
            'tel URL' => [
                ['url' => 'tel:+1-2345-6789'],
                ['url' => 'tel:+1-2345-6789'],
                'tel:+1-2345-6789',
            ],
            'javascript URL (denied)' => [
                ['url' => 'javascript:alert(\'XSS\')'],
                ['url' => ''],
                '',
            ],
            'data URL (denied)' => [
                ['url' => 'data:text/html;base64,SGVsbG8sIFdvcmxkIQ%3D%3D'],
                ['url' => ''],
                '',
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider resolveParametersForNonFilesDataProvider
     */
    public function resolveReturnsSplitParameters(array $input, array $expected, string $finalString): void
    {
        $subject = new UrlLinkHandler();
        self::assertEquals($expected, $subject->resolveHandlerData($input));
    }

    /**
     * @test
     *
     * @dataProvider resolveParametersForNonFilesDataProvider
     */
    public function splitParametersToUnifiedIdentifier(array $input, array $parameters, string $expected): void
    {
        $subject = new UrlLinkHandler();
        self::assertEquals($expected, $subject->asString($parameters));
    }

    /**
     * @test
     */
    public function getDefaultSchemeForNoSchemeInConfigurationReturnsFallbackScheme(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultScheme']);

        $result = UrlLinkHandler::getDefaultScheme();

        self::assertSame(LinkHandlingInterface::DEFAULT_SCHEME, $result);
    }

    /**
     * @test
     */
    public function getDefaultSchemeForEmptySchemeInConfigurationReturnsFallbackScheme(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultScheme'] = '';

        $result = UrlLinkHandler::getDefaultScheme();

        self::assertSame(LinkHandlingInterface::DEFAULT_SCHEME, $result);
    }

    /**
     * @test
     */
    public function getDefaultSchemeForSchemeInConfigurationReturnsSchemeFromConfiguration(): void
    {
        $scheme = 'https';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultScheme'] = $scheme;

        $result = UrlLinkHandler::getDefaultScheme();

        self::assertSame($scheme, $result);
    }
}
