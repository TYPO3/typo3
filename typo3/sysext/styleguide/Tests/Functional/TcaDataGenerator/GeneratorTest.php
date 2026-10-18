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
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaSelectTreeAjaxFieldData;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\SearchableSchemaFieldsCollector;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class GeneratorTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['styleguide'];

    #[Test]
    public function createGeneratesTreeStartingPointsWithMatchingSubtrees(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->get(Generator::class)->create();

        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_styleguide_elements_select')
            ->select(['uid', 'pid'], 'tx_styleguide_elements_select', ['sys_language_uid' => 0])
            ->fetchAssociative();
        self::assertIsArray($row);

        $expectedTrees = [
            'select_tree_static' => ['Tree branch A', 'Tree leaf A'],
            'select_tree_pid' => ['elements select', 'Tree branch A', 'Tree leaf A', 'Tree branch B', 'Tree leaf B'],
            'select_tree_tsconfig' => ['Tree branch A', 'Tree leaf A'],
            'select_tree_tsconfig_list' => ['Tree branch A', 'Tree leaf A', 'Tree branch B', 'Tree leaf B'],
            'category_tree_tsconfig' => ['Tree category A', 'Tree category leaf A'],
            'category_tree_tsconfig_list' => ['Tree category A', 'Tree category leaf A', 'Tree category B', 'Tree category leaf B'],
        ];
        $initialData = [
            'vanillaUid' => (int)$row['uid'],
            'request' => new ServerRequest(),
            'command' => 'edit',
            'tableName' => 'tx_styleguide_elements_select',
        ];
        $formData = $this->get(FormDataCompiler::class)->compile($initialData, $this->get(TcaDatabaseRecord::class));
        $fieldNames = [...array_keys($expectedTrees), 'select_tree_siteroot'];
        foreach ($fieldNames as $fieldName) {
            self::assertArrayHasKey($fieldName, $formData['processedTca']['columns']);
        }

        $treeTca = $formData['fullTca']['tx_styleguide_elements_select'];
        $treeTca['columns'] = array_intersect_key($treeTca['columns'], array_flip($fieldNames));
        $formData = $this->get(FormDataCompiler::class)->compile(
            $initialData + [
                'processedTca' => $treeTca,
                'recordTypeValue' => '0',
                'selectTreeCompileItems' => true,
            ],
            $this->get(TcaSelectTreeAjaxFieldData::class),
        );

        foreach ($expectedTrees as $fieldName => $expectedNames) {
            $items = $formData['processedTca']['columns'][$fieldName]['config']['items'];
            $names = array_column(array_filter($items, static fn(array $item): bool => (int)$item['identifier'] > 0), 'name');
            sort($expectedNames);
            sort($names);
            self::assertSame($expectedNames, $names, $fieldName);
        }

        $siteTree = $formData['processedTca']['columns']['select_tree_siteroot']['config'];
        self::assertSame((string)$formData['site']->getRootPageId(), $siteTree['treeConfig']['startingPoints']);
        self::assertContains('Tree leaf A', array_column($siteTree['items'], 'name'));
        self::assertContains('elements basic', array_column($siteTree['items'], 'name'));

        $this->get(Generator::class)->delete();
        $connectionPool = $this->get(ConnectionPool::class);
        self::assertSame(0, $connectionPool->getConnectionForTable('pages')->count('*', 'pages', ['pid' => (int)$row['pid'], 'deleted' => 0]));
        self::assertSame(0, $connectionPool->getConnectionForTable('sys_category')->count('*', 'sys_category', ['pid' => (int)$row['pid'], 'deleted' => 0]));
    }

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
        self::assertSame('0', (string)$basicRow['radio_processors']);
        $selectRow = $connectionPool->getConnectionForTable('tx_styleguide_elements_select')
            ->select(['select_processors'], 'tx_styleguide_elements_select', ['l10n_parent' => 0, 'sys_language_uid' => 0])
            ->fetchAssociative();
        self::assertIsArray($selectRow);
        self::assertSame('0', (string)$selectRow['select_processors']);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            (string)$basicRow['uuid_1']
        );

        self::assertIsArray(json_decode($basicRow['json_3'], true, 512, JSON_THROW_ON_ERROR));
        self::assertIsArray(json_decode($basicRow['json_4'], true, 512, JSON_THROW_ON_ERROR));

        $searchableFields = $this->get(SearchableSchemaFieldsCollector::class)->getFieldNames('tx_styleguide_elements_basic');
        foreach (['input', 'text'] as $type) {
            self::assertSame('sgsearchincluded' . $type, $basicRow[$type . '_search_included']);
            self::assertSame('sgsearchexcluded' . $type, $basicRow[$type . '_search_excluded']);
            self::assertContains($type . '_search_included', $searchableFields);
            self::assertNotContains($type . '_search_excluded', $searchableFields);
        }

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
