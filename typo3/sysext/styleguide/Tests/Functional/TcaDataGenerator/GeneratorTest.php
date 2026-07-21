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

namespace TYPO3\CMS\Styleguide\Tests\Functional\TcaDataGenerator;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class GeneratorTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['styleguide'];

    #[Test]
    public function createGeneratesDemoDataForStyleguideTables(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->get(Generator::class)->create();

        $connectionPool = $this->get(ConnectionPool::class);

        // A row for the "general" handled table exists with tstamp / crdate set
        $basicRow = $connectionPool->getConnectionForTable('tx_styleguide_elements_basic')
            ->select(['*'], 'tx_styleguide_elements_basic', ['l10n_parent' => 0, 'sys_language_uid' => 0])
            ->fetchAssociative();
        self::assertIsArray($basicRow);
        self::assertGreaterThan(0, (int)$basicRow['tstamp']);
        self::assertGreaterThan(0, (int)$basicRow['crdate']);

        // Field generators created values for the column types
        self::assertNotSame('', (string)$basicRow['input_1']);
        self::assertNotSame('', (string)$basicRow['text_1']);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            (string)$basicRow['uuid_1']
        );

        // The "use combination" inline scenarios created four child rows, two of them
        // connected via mm. Translation handling copies some of these rows in addition,
        // with a non-deterministic amount, so only the lower bounds are asserted.
        foreach ([
            'tx_styleguide_inline_usecombination_child' => 4,
            'tx_styleguide_inline_usecombination_mm' => 2,
            'tx_styleguide_inline_usecombinationgroup_child' => 4,
            'tx_styleguide_inline_usecombinationgroup_mm' => 2,
        ] as $tableName => $minimumRowCount) {
            $rowCount = $connectionPool->getConnectionForTable($tableName)
                ->count('*', $tableName, ['sys_language_uid' => 0]);
            self::assertGreaterThanOrEqual($minimumRowCount, $rowCount, 'Too few rows in ' . $tableName);
        }
    }
}
