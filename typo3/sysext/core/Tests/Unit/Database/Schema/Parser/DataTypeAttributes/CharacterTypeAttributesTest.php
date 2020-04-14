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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\DataTypeAttributes;

use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * MySQL supports the SQL standard integer types INTEGER (or INT) and SMALLINT.
 * As an extension to the standard, MySQL also supports the integer types TINYINT, MEDIUMINT, and BIGINT.
 */
class CharacterTypeAttributesTest extends AbstractDataTypeBaseTestCase
{

    /**
     * Data provider for canParseCharacterDataTypeAttributes()
     *
     * @return array
     */
    public function canParseCharacterDataTypeAttributesProvider(): array
    {
        return [
            'BINARY' => [
                'VARCHAR(255) BINARY',
                ['binary' => true, 'charset' => null, 'collation' => null],
            ],
            'CHARACTER SET' => [
                'TEXT CHARACTER SET latin1',
                ['binary' => false, 'charset' => 'latin1', 'collation' => null],
            ],
            'COLLATE' => [
                'CHAR(20) COLLATE latin1_german1_ci',
                ['binary' => false, 'charset' => null, 'collation' => 'latin1_german1_ci'],
            ],
            'CHARACTER SET + COLLATE' => [
                'CHAR(20) CHARACTER SET latin1 COLLATE latin1_german1_ci',
                ['binary' => false, 'charset' => 'latin1', 'collation' => 'latin1_german1_ci'],
            ],
            'BINARY, CHARACTER SET + COLLATE' => [
                'CHAR(20) BINARY CHARACTER SET latin1 COLLATE latin1_german1_ci',
                ['binary' => true, 'charset' => 'latin1', 'collation' => 'latin1_german1_ci'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseCharacterDataTypeAttributesProvider
     * @param string $columnDefinition
     * @param array $options
     */
    public function canParseDataType(string $columnDefinition, array $options)
    {
        $subject = $this->createSubject($columnDefinition);

        self::assertSame($options, $subject->dataType->getOptions());
    }
}
