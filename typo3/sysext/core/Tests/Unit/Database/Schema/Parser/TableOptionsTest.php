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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser;

use TYPO3\CMS\Core\Database\Schema\Parser\AST\AbstractCreateStatement;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableStatement;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for CreateTableStatement table options
 */
class TableOptionsTest extends UnitTestCase
{
    /**
     * Each parameter array consists of the following values:
     *  - table options SQL fragment
     *  - expected options array
     *
     * @return array
     */
    public function canParseTableOptionsDataProvider(): array
    {
        return [
            'ENGINE engine_name' => [
                'ENGINE MyISAM',
                ['engine' => 'MyISAM'],
            ],
            'ENGINE = engine_name' => [
                'ENGINE = InnoDB',
                ['engine' => 'InnoDB'],
            ],
            'AUTO_INCREMENT' => [
                'AUTO_INCREMENT = 17',
                ['auto_increment' => 17],
            ],
            'AVG_ROW_LENGTH' => [
                'AVG_ROW_LENGTH=21',
                ['average_row_length' => 21],
            ],
            'DEFAULT CHARACTER SET' => [
                'DEFAULT CHARACTER SET latin1',
                ['character_set' => 'latin1'],
            ],
            'CHECKSUM' => [
                'CHECKSUM =0',
                ['checksum' => 0],
            ],
            'COLLATE' => [
                'COLLATE = utf8mb4_general_ci',
                ['collation' => 'utf8mb4_general_ci'],
            ],
            'COMMENT' => [
                "COMMENT = 'aComment'",
                ['comment' => 'aComment'],
            ],
            'COMPRESSION' => [
                'COMPRESSION = ZLIB',
                ['compression' => 'ZLIB'],
            ],
            'CONNECTION' => [
                'CONNECTION = connect_string',
                ['connection' => 'connect_string'],
            ],
            'DATA DIRECTORY' => [
                'DATA DIRECTORY = \'/var/lib/mysql/\'',
                ['data_directory' => '/var/lib/mysql/'],
            ],
            'DELAY_KEY_WRITE' => [
                'DELAY_KEY_WRITE 0',
                ['delay_key_write' => 0],
            ],
            'ENCRYPTION' => [
                'ENCRYPTION = Y',
                ['encryption' => 'Y'],
            ],
            'INDEX DIRECTORY' => [
                'INDEX DIRECTORY = \'/data/mysql/\'',
                ['index_directory' => '/data/mysql/'],
            ],
            'INSERT_METHOD' => [
                'INSERT_METHOD FIRST',
                ['insert_method' => 'FIRST'],
            ],
            'KEY_BLOCK_SIZE' => [
                'KEY_BLOCK_SIZE 16',
                ['key_block_size' => 16],
            ],
            'MAX_ROWS' => [
                'MAX_ROWS = 1000',
                ['max_rows' => 1000],
            ],
            'MIN_ROWS' => [
                'MIN_ROWS 10',
                ['min_rows' => 10],
            ],
            'PACK_KEYS' => [
                'PACK_KEYS DEFAULT',
                ['pack_keys' => 'DEFAULT'],
            ],
            'PASSWORD' => [
                "PASSWORD = 'aPassword'",
                ['password' => 'aPassword'],
            ],
            'ROW_FORMAT' => [
                'ROW_FORMAT = DYNAMIC',
                ['row_format' => 'DYNAMIC'],
            ],
            'STATS_AUTO_RECALC' => [
                'STATS_AUTO_RECALC 1',
                ['stats_auto_recalc' => '1'],
            ],
            'STATS_PERSISTENT' => [
                'STATS_PERSISTENT 0',
                ['stats_persistent' => '0'],
            ],
            'STATS_SAMPLE_PAGES' => [
                'STATS_SAMPLE_PAGES DEFAULT',
                ['stats_sample_pages' => 'DEFAULT'],
            ],
            'TABLESPACE' => [
                'TABLESPACE `anotherTableSpace`',
                ['tablespace' => 'anotherTableSpace'],
            ]
        ];
    }

    /**
     * @test
     * @dataProvider canParseTableOptionsDataProvider
     * @param string $tableOptionsSQL
     * @param array $expectedTableOptions
     */
    public function canParseTableOptions(
        string $tableOptionsSQL,
        array $expectedTableOptions
    ) {
        $statement = sprintf('CREATE TABLE `aTable`(`aField` INT(11)) %s;', $tableOptionsSQL);
        $subject = $this->createSubject($statement);

        self::assertInstanceOf(CreateTableStatement::class, $subject);
        self::assertSame($expectedTableOptions, $subject->tableOptions);
    }

    /**
     * Parse the CREATE TABLE statement and return the reference definition
     *
     * @param string $statement
     * @return AbstractCreateStatement|CreateTableStatement
     */
    protected function createSubject(string $statement): AbstractCreateStatement
    {
        $parser = new Parser($statement);
        return $parser->getAST();
    }
}
