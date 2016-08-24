<?php
declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\DataTypes;

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

use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\BlobDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\LongBlobDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\MediumBlobDataType;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\TinyBlobDataType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing BLOB SQL data types
 */
class BlobTypesTest extends AbstractDataTypeBaseTestCase
{
    /**
     * Data provider for canParseBlobDataType()
     *
     * @return array
     */
    public function canParseBlobDataTypeProvider(): array
    {
        return [
            'TINYBLOB' => [
                'TINYBLOB',
                TinyBlobDataType::class,
            ],
            'BLOB' => [
                'BLOB',
                BlobDataType::class,
            ],
            'MEDIUMBLOB' => [
                'MEDIUMBLOB',
                MediumBlobDataType::class,
            ],
            'LONGBLOB' => [
                'LONGBLOB',
                LongBlobDataType::class,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseBlobDataTypeProvider
     * @param string $columnDefinition
     * @param string $className
     */
    public function canParseDataType(string $columnDefinition, string $className)
    {
        $subject = $this->createSubject($columnDefinition);

        $this->assertInstanceOf($className, $subject->dataType);
    }
}
