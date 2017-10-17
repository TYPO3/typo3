<?php

namespace TYPO3\CMS\Core\Tests\Unit\DataHandler\Localization;

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

use TYPO3\CMS\Core\DataHandling\Localization\State;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class StateTest extends UnitTestCase
{
    const TABLE_NAME = 'tx_test_table';

    /**
     * Set up the tests
     */
    protected function setUp()
    {
        $GLOBALS['TCA'] = [];
    }

    /**
     * @param string $tableName
     * @param array $states
     *
     * @test
     * @dataProvider stateObjectCanBeCreatedDataProvider
     */
    public function stateObjectCanBeCreated(string $tableName, array $states)
    {
        $subject = new State($tableName, $states);

        $this->assertInstanceOf(State::class, $subject);
    }

    /**
     * @return array
     */
    public function stateObjectCanBeCreatedDataProvider(): array
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

    /**
     * @param array $states
     * @param array $expected
     *
     * @test
     * @dataProvider statesAreEnrichedAndSanitizedOnObjectCreationDataProvider
     */
    public function statesAreEnrichedAndSanitizedOnObjectCreation(
        array $states,
        array $expected
    ) {
        $GLOBALS['TCA'] = $this->provideTableConfiguration(
            'first_field',
            'second_field'
        );

        $subject = new State(static::TABLE_NAME, $states);

        $this->assertSame(
            $expected,
            $subject->toArray()
        );
    }

    /**
     * @return array
     */
    public function statesAreEnrichedAndSanitizedOnObjectCreationDataProvider(): array
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

    /**
     * @param string[] ...$fieldNames
     *
     * @return array
     */
    private function provideTableConfiguration(string ...$fieldNames): array
    {
        $columnsConfiguration = [];
        foreach ($fieldNames as $fieldName) {
            $columnsConfiguration[$fieldName]['config']['behaviour']['allowLanguageSynchronization'] = true;
        }
        return [
            static::TABLE_NAME => [
                'columns' => $columnsConfiguration,
            ],
        ];
    }
}
