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

namespace TYPO3\CMS\Core\Tests\Unit\Domain\Persistence;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\Persistence\RecordIdentityMap;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RecordIdentityMapTest extends UnitTestCase
{
    #[Test]
    public function findByIdentifierThrowsExceptionOnMissingRecord(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1720730774);

        (new RecordIdentityMap())->findByIdentifier('unknown', 123);
    }

    #[Test]
    public function addingAndFechtingOfRecordsWorks(): void
    {
        $recordId = 123;
        $mainType = 'tt_content';

        $subject = new RecordIdentityMap();
        $record = new RawRecord(
            $recordId,
            456,
            [],
            new ComputedProperties(null, null, null, null),
            $mainType
        );

        $subject->add($record);

        self::assertTrue($subject->has($record));
        self::assertTrue($subject->hasIdentifier($mainType, $recordId));
        self::assertEquals($record, $subject->findByIdentifier($mainType, $recordId));
    }
}
