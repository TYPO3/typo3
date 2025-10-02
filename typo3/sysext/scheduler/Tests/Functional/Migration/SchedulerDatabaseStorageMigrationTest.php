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

namespace TYPO3\CMS\Scheduler\Tests\Functional\Migration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use TYPO3\CMS\Scheduler\Migration\SchedulerDatabaseStorageMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SchedulerDatabaseStorageMigrationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'info',
        'linkvalidator',
        'recycler',
        'reports',
        'scheduler',
    ];

    #[Test]
    public function schedulerTasksAreMigrated(): void
    {
        $subject = new SchedulerDatabaseStorageMigration();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageMigrationBase.csv');
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        self::assertFalse($subject->updateNecessary());
        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageMigrationApplied.csv');

        // Just ensure that running the upgrade again does not change anything
        self::assertTrue($subject->executeUpdate());
        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageMigrationApplied.csv');
    }

    #[Test]
    public function schedulerTasksWithFailuresKeepWizardShowingUp(): void
    {
        $subject = new SchedulerDatabaseStorageMigration();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageFailedMigrationBase.csv');
        self::assertTrue($subject->updateNecessary());
        self::assertFalse($subject->executeUpdate());
        self::assertTrue($subject->updateNecessary());
        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageFailedMigrationApplied.csv');

        // Just ensure that running the upgrade again does not change anything
        self::assertFalse($subject->executeUpdate());
        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageFailedMigrationApplied.csv');
    }

    /**
     * Compares two arrays containing db rows and returns array containing column names which don't match
     * It's a helper method used in assertCSVDataSet.
     *
     * Modified {@see FunctionalTestCase::getDifferentFields()} clone to re-encode json field data by calling
     * {@see self::normalizeJsonFieldData()}, specified as hardcoded list. This is a workaround to mitigate
     * database different behaviours regarding json field data (sorting/removing space from database), while
     * executing a database assertion based on the a csv set.
     */
    protected function getDifferentFields(array $assertion, array $record): array
    {
        $differentFields = [];
        foreach ($assertion as $field => $value) {
            $value = $this->normalizeJsonFieldData($field, $value);
            $recordValue = $this->normalizeJsonFieldData($field, $record[$field]);
            if (str_starts_with((string)$value, '\\*')) {
                continue;
            }
            if (!array_key_exists($field, $record)) {
                throw new \ValueError(sprintf('"%s" column not found in the input data.', $field), 1744301313);
            }
            if (str_starts_with((string)$value, '<?xml')) {
                try {
                    self::assertXmlStringEqualsXmlString((string)$value, (string)$record[$field]);
                } catch (ExpectationFailedException) {
                    $differentFields[] = $field;
                }
            } elseif ($value === null && $recordValue !== $value) {
                $differentFields[] = $field;
            } elseif ((string)$recordValue !== (string)$value) {
                $differentFields[] = $field;
            }
        }
        return $differentFields;
    }

    /**
     * Helper method to re-encode scheduler json field data in {@see self::getDifferentFields()} as part of
     * {@see self::assertCSVDataSet()} assertions in above tests.
     */
    private function normalizeJsonFieldData(string $field, mixed $data): mixed
    {
        $jsonDecodeFields = [
            'parameters',
            'execution_details',
        ];
        if (!in_array($field, $jsonDecodeFields, true) || !is_string($data) || $data === '') {
            return $data;
        }
        $data = \json_decode(json: $data, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
        $this->naturalSortMultiDimensionalArray($data);
        return \json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * Resort multi-dimensional array using natural sort to ensure a comparable sorting.
     */
    private function naturalSortMultiDimensionalArray(mixed &$data): void
    {
        if (is_array($data)) {
            ksort($data, SORT_NATURAL);
            foreach ($data as &$item) {
                $this->naturalSortMultiDimensionalArray($item);
            }
        }
    }
}
