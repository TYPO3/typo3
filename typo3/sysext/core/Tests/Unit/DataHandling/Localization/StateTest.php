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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling\Localization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\DataHandling\Localization\State;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StateTest extends UnitTestCase
{
    public const TABLE_NAME = 'tx_test_table';

    protected TcaSchemaFactory $tcaSchemaFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isString())->willReturn(false);
        $this->tcaSchemaFactory = new TcaSchemaFactory(
            new RelationMapBuilder(),
            new FieldTypeFactory(),
            '',
            $cacheMock
        );
    }

    #[DataProvider('stateObjectCanBeCreatedDataProvider')]
    #[DoesNotPerformAssertions]
    #[Test]
    public function stateObjectCanBeCreated(string $tableName, array $states): void
    {
        $this->tcaSchemaFactory->load([self::TABLE_NAME => []], true);
        // We need two instances for the calls in ->sanitize() and ->enrich()
        GeneralUtility::addInstance(TcaSchemaFactory::class, $this->tcaSchemaFactory);
        GeneralUtility::addInstance(TcaSchemaFactory::class, $this->tcaSchemaFactory);

        new State($tableName, $states);
    }

    public static function stateObjectCanBeCreatedDataProvider(): array
    {
        return [
            'without states' => [
                static::TABLE_NAME,
                [],
            ],
            'with states' => [
                static::TABLE_NAME,
                ['nonExistingField' => 'invalidState'],
            ],
        ];
    }

    #[DataProvider('statesAreEnrichedAndSanitizedOnObjectCreationDataProvider')]
    #[Test]
    public function statesAreEnrichedAndSanitizedOnObjectCreation(
        array $states,
        array $expected
    ): void {
        $this->tcaSchemaFactory->load([
            'tx_test_table' => [
                'columns' => [
                    'first_field' => [
                        'config' => [
                            'type' => 'input',
                            'behaviour' => [
                                'allowLanguageSynchronization' => true,
                            ],
                        ],
                    ],
                    'second_field' => [
                        'config' => [
                            'type' => 'input',
                            'behaviour' => [
                                'allowLanguageSynchronization' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ], true);
        // We need two instances for the calls in ->sanitize() and ->enrich()
        GeneralUtility::addInstance(TcaSchemaFactory::class, $this->tcaSchemaFactory);
        GeneralUtility::addInstance(TcaSchemaFactory::class, $this->tcaSchemaFactory);
        $subject = new State('tx_test_table', $states);
        self::assertSame($expected, $subject->toArray());
    }

    public static function statesAreEnrichedAndSanitizedOnObjectCreationDataProvider(): array
    {
        return [
            'empty' => [
                [],
                [
                    'first_field' => 'parent',
                    'second_field' => 'parent',
                ],
            ],
            'invalid field only' => [
                [
                    'invalid_field' => 'invalidState',
                ],
                [
                    'first_field' => 'parent',
                    'second_field' => 'parent',
                ],
            ],
            'first_field only, valid state' => [
                [
                    'first_field' => 'custom',
                ],
                [
                    'first_field' => 'custom',
                    'second_field' => 'parent',
                ],
            ],
            'first_field only, invalid state' => [
                [
                    'first_field' => 'invalidState',
                ],
                [
                    'first_field' => 'parent',
                    'second_field' => 'parent',
                ],
            ],
            'all valid fields, valid states' => [
                [
                    'first_field' => 'custom',
                    'second_field' => 'parent',
                ],
                [
                    'first_field' => 'custom',
                    'second_field' => 'parent',
                ],
            ],
            'all valid fields, invalid states' => [
                [
                    'first_field' => 'invalidState',
                    'second_field' => 'invalidState',
                ],
                [
                    'first_field' => 'parent',
                    'second_field' => 'parent',
                ],
            ],
            'all valid fields, valid states and invalid field' => [
                [
                    'invalid_field' => 'invalidState',
                    'first_field' => 'custom',
                    'second_field' => 'parent',
                ],
                [
                    'first_field' => 'custom',
                    'second_field' => 'parent',
                ],
            ],
            'all valid fields, invalid states and invalid field' => [
                [
                    'invalid_field' => 'invalidState',
                    'first_field' => 'invalidState',
                    'second_field' => 'invalidState',
                ],
                [
                    'first_field' => 'parent',
                    'second_field' => 'parent',
                ],
            ],
        ];
    }
}
