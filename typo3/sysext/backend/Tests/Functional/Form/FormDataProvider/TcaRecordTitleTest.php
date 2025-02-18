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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaRecordTitleTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TcaSelectItems/be_users.csv');
    }

    #[DataProvider('addDataReturnsRecordTitleForCountryTypeDataProvider')]
    #[Test]
    public function addDataReturnsRecordTitleForCountryType(string $locale, string $labelField, string $inputCountry, string $expectedLabel): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create($locale);
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);

        $input = [
            'tableName' => 'aTable',
            'isInlineChild' => false,
            'databaseRow' => [
                'uid' => '1',
                'aField' => $inputCountry,
            ],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'country',
                            'labelField' => $labelField,
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['recordTitle'] = $expectedLabel;
        self::assertSame($expected, (new TcaRecordTitle())->addData($input));
    }

    public static function addDataReturnsRecordTitleForCountryTypeDataProvider(): \Generator
    {
        yield 'Germany, localized name (in english)' => [
            'locale' => 'default',
            'labelField' => 'localizedName',
            'inputCountry' => 'DE',
            'expectedLabel' => 'Germany',
        ];
        yield 'Germany, localized name (in german)' => [
            'locale' => 'de_DE',
            'labelField' => 'localizedName',
            'inputCountry' => 'DE',
            'expectedLabel' => 'Deutschland',
        ];
        yield 'Germany, default name' => [
            'locale' => 'default',
            'labelField' => 'name',
            'inputCountry' => 'DE',
            'expectedLabel' => 'Germany',
        ];
        yield 'Germany, official name' => [
            'locale' => 'default',
            'labelField' => 'officialName',
            'inputCountry' => 'DE',
            'expectedLabel' => 'Federal Republic of Germany',
        ];
        yield 'Austria, localized official name' => [
            'locale' => 'default',
            'labelField' => 'localizedOfficialName',
            'inputCountry' => 'AT',
            'expectedLabel' => 'Republic of Austria',
        ];
        yield 'Austria, localized official name (german)' => [
            'locale' => 'de_DE',
            'labelField' => 'localizedOfficialName',
            'inputCountry' => 'AT',
            'expectedLabel' => 'Republik Österreich',
        ];
        yield 'Denmark, iso2' => [
            'locale' => 'default',
            'labelField' => 'iso2',
            'inputCountry' => 'AT',
            'expectedLabel' => 'AT',
        ];
        yield 'Denmark, iso3' => [
            'locale' => 'default',
            'labelField' => 'iso3',
            'inputCountry' => 'AT',
            'expectedLabel' => 'AUT',
        ];
        yield 'Invalid Country' => [
            'locale' => 'default',
            'labelField' => 'iso3',
            'inputCountry' => 'NOTHING',
            'expectedLabel' => 'NOTHING',
        ];
    }
}
