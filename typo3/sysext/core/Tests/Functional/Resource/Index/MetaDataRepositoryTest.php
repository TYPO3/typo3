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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Index;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MetaDataRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['filemetadata'];

    #[Test]
    public function canUpdateAnExistingFieldWithEmptyValue(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileWithMetaData.csv');
        $subject = $this->get(MetaDataRepository::class);
        $result = $subject->update(1, [
            'copyright' => '',
        ]);
        self::assertSame('', $result['copyright']);
    }

    #[Test]
    public function canUpdateAnExistingFieldWithNewValue(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileWithMetaData.csv');
        $subject = $this->get(MetaDataRepository::class);
        $result = $subject->update(1, [
            'copyright' => 'Something New',
        ]);
        self::assertSame('Something New', $result['copyright']);
    }

    #[Test]
    public function updateCropsValueToMaximumLengthOfField(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileWithMetaData.csv');
        $subject = $this->get(MetaDataRepository::class);
        $result = $subject->update(1, [
            'creator' => str_repeat('a', 300),
        ]);
        self::assertSame(str_repeat('a', 255), $result['creator']);
        self::assertSame(str_repeat('a', 255), $subject->findByFileUid(1)['creator']);
    }

    #[Test]
    public function updateCropsMultibyteValueByCharacters(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileWithMetaData.csv');
        $subject = $this->get(MetaDataRepository::class);
        $result = $subject->update(1, [
            'creator' => str_repeat('联', 300),
        ]);
        self::assertSame(str_repeat('联', 255), $result['creator']);
    }

    #[Test]
    public function updateKeepsValueWithinMaximumLengthOfField(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileWithMetaData.csv');
        $subject = $this->get(MetaDataRepository::class);
        $result = $subject->update(1, [
            'creator' => str_repeat('联', 255),
        ]);
        self::assertSame(str_repeat('联', 255), $result['creator']);
    }

    /**
     * "alternative" is a TEXT column limited to 65535 bytes on MySQL and MariaDB,
     * which is cropped by bytes without splitting a multibyte character.
     * Other platforms have no length limit for TEXT columns and keep the value.
     */
    #[Test]
    public function updateCropsMultibyteValueOfTextFieldWithoutSplittingCharacters(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileWithMetaData.csv');
        $subject = $this->get(MetaDataRepository::class);
        $value = str_repeat('联', 30000);
        $subject->update(1, [
            'alternative' => $value,
        ]);
        $storedValue = $subject->findByFileUid(1)['alternative'];
        self::assertTrue(mb_check_encoding($storedValue, 'UTF-8'));
        self::assertStringStartsWith($storedValue, $value);
    }

    #[Test]
    public function createMetaDataRecordCropsValueToMaximumLengthOfField(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileWithMetaData.csv');
        $subject = $this->get(MetaDataRepository::class);
        $result = $subject->createMetaDataRecord(2, [
            'creator' => str_repeat('a', 300),
        ]);
        self::assertSame(str_repeat('a', 255), $result['creator']);
    }
}
