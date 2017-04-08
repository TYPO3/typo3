<?php
namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling;

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

use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LinkServiceTest extends UnitTestCase
{
    /**
     * Data to resolve strings to arrays and vice versa, external, mail, page
     *
     * @return array
     */
    public function resolveParametersForNonFilesDataProvider()
    {
        return [
            'simple page - old style' => [
                // original input value
                '13',
                // splitted values
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => 13
                ],
                // final unified URN
                't3://page?uid=13'
            ],
            'page with type - old style' => [
                '13,31',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => 13,
                    'pagetype' => 31
                ],
                't3://page?uid=13&type=31'
            ],
            'page with type and fragment - old style' => [
                '13,31#uncool',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => '13',
                    'pagetype' => '31',
                    'fragment' => 'uncool'
                ],
                't3://page?uid=13&type=31#uncool'
            ],
            'page with type and parameters and fragment - old style' => [
                '13,31?unbel=ievable#uncool',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => '13',
                    'pagetype' => '31',
                    'parameters' => 'unbel=ievable',
                    'fragment' => 'uncool'
                ],
                't3://page?uid=13&type=31&unbel=ievable#uncool'
            ],
            'page with alias - old style' => [
                'alias13',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pagealias' => 'alias13'
                ],
                't3://page?alias=alias13'
            ],
            'http URL' => [
                'http://www.have.you/ever?did=this',
                [
                    'type' => LinkService::TYPE_URL,
                    'url' => 'http://www.have.you/ever?did=this'
                ],
                'http://www.have.you/ever?did=this'
            ],
            'http URL without scheme' => [
                'www.have.you/ever?did=this',
                [
                    'type' => LinkService::TYPE_URL,
                    'url' => 'http://www.have.you/ever?did=this'
                ],
                'http://www.have.you/ever?did=this'
            ],
            'https URL' => [
                'https://www.have.you/ever?did=this',
                [
                    'type' => LinkService::TYPE_URL,
                    'url' => 'https://www.have.you/ever?did=this'
                ],
                'https://www.have.you/ever?did=this'
            ],
            'https URL with port' => [
                'https://www.have.you:8088/ever?did=this',
                [
                    'type' => LinkService::TYPE_URL,
                    'url' => 'https://www.have.you:8088/ever?did=this'
                ],
                'https://www.have.you:8088/ever?did=this'
            ],
            'ftp URL' => [
                'ftp://www.have.you/ever?did=this',
                [
                    'type' => LinkService::TYPE_URL,
                    'url' => 'ftp://www.have.you/ever?did=this'
                ],
                'ftp://www.have.you/ever?did=this'
            ],
            'afp URL' => [
                'afp://www.have.you/ever?did=this',
                [
                    'type' => LinkService::TYPE_URL,
                    'url' => 'afp://www.have.you/ever?did=this'
                ],
                'afp://www.have.you/ever?did=this'
            ],
            'sftp URL' => [
                'sftp://nice:andsecret@www.have.you:23/ever?did=this',
                [
                    'type' => LinkService::TYPE_URL,
                    'url' => 'sftp://nice:andsecret@www.have.you:23/ever?did=this'
                ],
                'sftp://nice:andsecret@www.have.you:23/ever?did=this'
            ],
            'email with protocol' => [
                'mailto:one@love.com',
                [
                    'type' => LinkService::TYPE_EMAIL,
                    'email' => 'one@love.com'
                ],
                'mailto:one@love.com'
            ],
            'email without protocol' => [
                'one@love.com',
                [
                    'type' => LinkService::TYPE_EMAIL,
                    'email' => 'one@love.com'
                ],
                'mailto:one@love.com'
            ],
            'email without protocol and subject parameter' => [
                'email@mail.mail?subject=Anfrage:%20Text%20Text%20Lösungen',
                [
                    'type' => LinkService::TYPE_EMAIL,
                    'email' => 'email@mail.mail?subject=Anfrage:%20Text%20Text%20Lösungen'
                ],
                'mailto:email@mail.mail?subject=Anfrage:%20Text%20Text%20Lösungen'
            ],
            'current page - cool style' => [
                't3://page?uid=current',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => 'current'
                ],
                't3://page?uid=current'
            ],
            'current empty page - cool style' => [
                't3://page',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => 'current'
                ],
                't3://page?uid=current'
            ],
            'simple page - cool style' => [
                't3://page?uid=13',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => 13
                ],
                't3://page?uid=13'
            ],
            'page with alias - cool style' => [
                't3://page?alias=alias13',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pagealias' => 'alias13'
                ],
                't3://page?alias=alias13'
            ],
            'page with alias and type - cool style' => [
                't3://page?alias=alias13&type=31',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pagealias' => 'alias13',
                    'pagetype' => '31'
                ],
                't3://page?alias=alias13&type=31'
            ],
            'page with alias and parameters - cool style' => [
                't3://page?alias=alias13&my=additional&parameter=that&are=nice',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pagealias' => 'alias13',
                    'parameters' => 'my=additional&parameter=that&are=nice'
                ],
                't3://page?alias=alias13&my=additional&parameter=that&are=nice',
            ],
            'page with alias and parameters and fragment - cool style' => [
                't3://page?alias=alias13&my=additional&parameter=that&are=nice#again',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pagealias' => 'alias13',
                    'parameters' => 'my=additional&parameter=that&are=nice',
                    'fragment' => 'again'
                ],
                't3://page?alias=alias13&my=additional&parameter=that&are=nice#again',
            ]
        ];
    }

    /**
     * @test
     *
     * @param string $input
     * @param array  $expected
     * @param string $finalString
     *
     * @dataProvider resolveParametersForNonFilesDataProvider
     */
    public function resolveReturnsSplitParameters($input, $expected, $finalString)
    {
        $subject = new LinkService();
        $this->assertEquals($expected, $subject->resolve($input));
    }

    /**
     * @test
     *
     * @param string $input
     * @param array  $parameters
     * @param string $expected
     *
     * @dataProvider resolveParametersForNonFilesDataProvider
     */
    public function splitParametersToUnifiedIdentifier($input, $parameters, $expected)
    {
        $subject = new LinkService();
        $this->assertEquals($expected, $subject->asString($parameters));
    }
}
