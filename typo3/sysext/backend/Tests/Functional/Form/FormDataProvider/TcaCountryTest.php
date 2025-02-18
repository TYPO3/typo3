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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaCountry;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaCountryTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TcaSelectItems/be_users.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
    }

    #[Test]
    public function addDataKeepExistingItems(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    'label' => 'foo',
                                    'value' => 'bar',
                                ],
                            ],
                        ],
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'group',
                            'items' => [
                                0 => [
                                    'label' => 'foo',
                                    'value' => 'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        self::assertSame($expected, $this->get(TcaCountry::class)->addData($input));
    }

    #[Test]
    public function addDataThrowsExceptionWithInvalidLabelField(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'country',
                            'labelField' => 'broken',
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1675895616);
        $this->get(TcaCountry::class)->addData($input);
    }

    #[Test]
    public function addDataAddsCountries(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'country',
                        ],
                    ],
                ],
            ],
        ];
        $result = $this->get(TcaCountry::class)->addData($input);
        self::assertIsArray($result['processedTca']['columns']['aField']['config']['items']);
        self::assertNotEmpty($result['processedTca']['columns']['aField']['config']['items']);
    }

    #[Test]
    public function addDataExcludesCountry(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'country',
                            'filter' => [
                                'excludeCountries' => ['AT'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $result = $this->get(TcaCountry::class)->addData($input);
        self::assertNotEmpty($result['processedTca']['columns']['aField']['config']['items']);
        $result = array_column($result['processedTca']['columns']['aField']['config']['items'], 'value');
        $result = array_flip($result);
        self::assertArrayNotHasKey('AT', $result);
    }

    #[Test]
    public function addDataFiltersOnlyCountriesWithRequiredInput(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'country',
                            'filter' => [
                                'onlyCountries' => ['AT', 'DE'],
                            ],
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ];
        $result = $this->get(TcaCountry::class)->addData($input);
        self::assertCount(2, $result['processedTca']['columns']['aField']['config']['items']);
    }

    #[Test]
    public function addDataFiltersOnlyCountriesWithoutRequiredInput(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'country',
                            'filter' => [
                                'onlyCountries' => ['AT', 'DE'],
                            ],
                            'required' => false,
                        ],
                    ],
                ],
            ],
        ];
        $result = $this->get(TcaCountry::class)->addData($input);
        self::assertCount(3, $result['processedTca']['columns']['aField']['config']['items']);
        self::assertEquals('', $result['processedTca']['columns']['aField']['config']['items'][0]['value']);
    }
}
