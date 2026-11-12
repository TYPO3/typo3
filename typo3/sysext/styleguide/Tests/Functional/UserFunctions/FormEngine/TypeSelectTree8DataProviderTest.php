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

namespace TYPO3\CMS\Styleguide\Tests\Functional\UserFunctions\FormEngine;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveEmptyRelations;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TypeSelectTree8DataProviderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['styleguide'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../../../backend/Tests/Functional/Fixtures/be_users.csv');
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
    }

    #[Test]
    public function selectTreeIsBuiltFromDataProviderWithoutForeignTable(): void
    {
        $result = $this->get(TcaSelectTreeItems::class)->addData($this->buildInput(true));

        $items = $result['processedTca']['columns']['select_tree_8']['config']['items'];
        self::assertSame(
            [
                ['europe', 0, false, false],
                ['at', 1, true, false],
                ['de', 1, true, true],
                ['de-by', 2, true, false],
                ['de-be', 2, true, false],
                ['ch', 1, true, true],
            ],
            array_map(static fn(array $item): array => [$item['identifier'], $item['depth'], $item['selectable'], $item['checked']], $items)
        );
    }

    #[Test]
    public function selectTreeFieldWithDataProviderWithoutForeignTableIsNotRemovedFromForm(): void
    {
        $result = $this->get(TcaSelectTreeItems::class)->addData($this->buildInput(false));
        $result = (new TcaColumnsRemoveEmptyRelations())->addData($result);

        self::assertArrayHasKey('select_tree_8', $result['processedTca']['columns']);
        self::assertSame(['de', 'ch'], $result['databaseRow']['select_tree_8']);
    }

    private function buildInput(bool $selectTreeCompileItems): array
    {
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        return [
            'tableName' => 'tx_styleguide_elements_select',
            'effectivePid' => 0,
            'databaseRow' => [
                'uid' => 1,
                'select_tree_8' => 'de,ch',
            ],
            'processedTca' => [
                'columns' => [
                    'select_tree_8' => [
                        'config' => $tcaSchemaFactory->get('tx_styleguide_elements_select')->getField('select_tree_8')->getConfiguration(),
                    ],
                ],
            ],
            'tcaSchemata' => $tcaSchemaFactory,
            'rootline' => [],
            'site' => null,
            'selectTreeCompileItems' => $selectTreeCompileItems,
        ];
    }
}
