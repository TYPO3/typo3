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

namespace TYPO3\CMS\Seo\Tests\Unit\XmlSitemap;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Seo\XmlSitemap\RecordsXmlSitemapDataProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RecordsXmlSitemapDataProviderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public static function getUrlFieldParameterMapReturnsNestedArrayDataProvider(): \Generator
    {
        yield 'simple bracket notation' => [
            'fieldToParameterMap' => ['uid' => 'tx_example_category[id]'],
            'data' => ['uid' => 42],
            'expected' => ['tx_example_category' => ['id' => 42]],
        ];
        yield 'no bracket notation' => [
            'fieldToParameterMap' => ['uid' => 'id'],
            'data' => ['uid' => 42],
            'expected' => ['id' => 42],
        ];
        yield 'deep bracket notation' => [
            'fieldToParameterMap' => ['uid' => 'tx_news[news][id]'],
            'data' => ['uid' => 7],
            'expected' => ['tx_news' => ['news' => ['id' => 7]]],
        ];
    }

    #[DataProvider('getUrlFieldParameterMapReturnsNestedArrayDataProvider')]
    #[Test]
    public function getUrlFieldParameterMapReturnsNestedArray(array $fieldToParameterMap, array $data, array $expected): void
    {
        $subject = $this->getAccessibleMock(
            RecordsXmlSitemapDataProvider::class,
            ['generateItems'],
            [],
            '',
            false
        );
        $subject->_set('config', [
            'url' => [
                'fieldToParameterMap' => $fieldToParameterMap,
            ],
        ]);
        $result = $subject->_call('getUrlFieldParameterMap', $data);
        self::assertEquals($expected, $result);
    }

    public static function getUrlAdditionalParamsReturnsNestedArrayDataProvider(): \Generator
    {
        yield 'simple additional get parameters' => [
            'additionalGetParameters' => [
                'tx_news' => [
                    'action' => 'show',
                    'controller' => 'News',
                ],
            ],
            'expected' => [
                'tx_news' => [
                    'action' => 'show',
                    'controller' => 'News',
                ],
            ],
        ];
        yield 'empty additional get parameters' => [
            'additionalGetParameters' => [],
            'expected' => [],
        ];
    }

    #[DataProvider('getUrlAdditionalParamsReturnsNestedArrayDataProvider')]
    #[Test]
    public function getUrlAdditionalParamsReturnsNestedArray(array $additionalGetParameters, array $expected): void
    {
        $subject = $this->getAccessibleMock(
            RecordsXmlSitemapDataProvider::class,
            ['generateItems'],
            [],
            '',
            false
        );
        $subject->_set('config', [
            'url' => [
                'additionalGetParameters' => $additionalGetParameters,
            ],
        ]);
        $result = $subject->_call('getUrlAdditionalParams', []);
        self::assertSame($expected, $result);
    }
}
