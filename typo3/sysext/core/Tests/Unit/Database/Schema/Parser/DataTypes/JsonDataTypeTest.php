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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\DataTypes;

use TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\JsonDataType;
use TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser\AbstractDataTypeBaseTestCase;

/**
 * Tests for parsing JSON SQL data type
 */
class JsonDataTypeTest extends AbstractDataTypeBaseTestCase
{
    /**
     * @test
     */
    public function canParseBitDataType(): void
    {
        $subject = $this->createSubject('JSON');

        self::assertInstanceOf(JsonDataType::class, $subject->dataType);
    }
}
