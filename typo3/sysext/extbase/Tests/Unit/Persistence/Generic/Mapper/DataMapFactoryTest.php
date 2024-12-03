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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Mapper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DataMapFactoryTest extends UnitTestCase
{
    public static function classNameTableNameMappings(): array
    {
        return [
            'Core classes' => [LogEntry::class, 'tx_belog_domain_model_logentry'],
            'Core classes with namespaces and leading backslash' => [LogEntry::class, 'tx_belog_domain_model_logentry'],
            'Extension classes' => ['ExtbaseTeam\\BlogExample\\Domain\\Model\\Blog', 'tx_blogexample_domain_model_blog'],
            'Extension classes with namespaces and leading backslash' => ['\\ExtbaseTeam\\BlogExample\\Domain\\Model\\Blog', 'tx_blogexample_domain_model_blog'],
        ];
    }

    #[DataProvider('classNameTableNameMappings')]
    #[Test]
    public function resolveTableNameReturnsExpectedTablenames($className, $expected): void
    {
        $subject = $this->getAccessibleMock(DataMapFactory::class, null, [], '', false);
        self::assertSame($expected, $subject->_call('resolveTableName', $className));
    }
}
