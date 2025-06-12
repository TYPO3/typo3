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

namespace TYPO3\CMS\Install\Tests\Functional\Updates;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Tests\Functional\Updates\Fixtures\InvalidCustomCTypeMigrationEmptyArrayKey;
use TYPO3\CMS\Install\Tests\Functional\Updates\Fixtures\InvalidCustomCTypeMigrationEmptyArrayValue;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class InvalidCustomCTypeMigrationTest extends FunctionalTestCase
{
    protected const TABLE_CONTENT = 'tt_content';
    protected const TABLE_BACKEND_USER_GROUPS = 'be_groups';

    #[Test]
    public function invalidGetListTypeToCTypeMappingArrayWithEmptyArrayKeyThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1727605678);
        $connectionPool = $this->get(ConnectionPool::class);
        $subject = new InvalidCustomCTypeMigrationEmptyArrayKey($connectionPool);
    }

    #[Test]
    public function invalidGetListTypeToCTypeMappingArrayWithEmptyArrayValueThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1727605679);
        $connectionPool = $this->get(ConnectionPool::class);
        $subject = new InvalidCustomCTypeMigrationEmptyArrayValue($connectionPool);
    }
}
