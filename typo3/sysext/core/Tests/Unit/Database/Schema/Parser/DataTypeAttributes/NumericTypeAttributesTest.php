<?php
declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\DataTypeAttributes;

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

use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * MySQL supports the SQL standard integer types INTEGER (or INT) and SMALLINT.
 * As an extension to the standard, MySQL also supports the integer types TINYINT, MEDIUMINT, and BIGINT.
 */
class NumericTypeAttributesTest extends AbstractDataTypeBaseTestCase
{

    /**
     * Data provider for canParseNumericDataTypeAttributes()
     *
     * @return array
     */
    public function canParseNumericDataTypeAttributesProvider(): array
    {
        return [
            'UNSIGNED' => [
                'INT(11) UNSIGNED',
                ['unsigned' => true, 'zerofill' => false],
            ],
            'ZEROFILL' => [
                'INT(11) ZEROFILL',
                ['unsigned' => false, 'zerofill' => true],
            ],
            'UNSIGNED ZEROFILL' => [
                'INT(11) UNSIGNED ZEROFILL',
                ['unsigned' => true, 'zerofill' => true],
            ]
        ];
    }

    /**
     * @test
     * @dataProvider canParseNumericDataTypeAttributesProvider
     * @param string $columnDefinition
     * @param array $options
     */
    public function canParseDataType(string $columnDefinition, array $options)
    {
        $subject = $this->createSubject($columnDefinition);

        $this->assertSame($options, $subject->dataType->getOptions());
    }
}
