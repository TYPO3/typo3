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
use TYPO3\CMS\Core\DataHandling\ItemProcessingService;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AddItemProcessorTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['styleguide'];

    #[Test]
    public function examplesProcessItemsInKeyOrderAndAcceptTsConfigLabelOverrides(): void
    {
        foreach ([
            'tx_styleguide_elements_select' => 'select_processors',
            'tx_styleguide_elements_basic' => 'radio_processors',
        ] as $table => $field) {
            $config = $this->get(TcaSchemaFactory::class)->get($table)->getField($field)->getConfiguration();
            foreach ([['itemsProcessors.' => []], ['itemsProcessors.' => ['100.' => ['label' => 'From Page TSconfig']]]] as $tsConfig) {
                $context = new ItemsProcessorContext(
                    table: $table,
                    field: $field,
                    row: [],
                    fieldConfiguration: $config,
                    processorParameters: [],
                    realPid: 0,
                    site: new NullSite(),
                    fieldTSconfig: $tsConfig,
                );
                $items = $this->get(ItemProcessingService::class)->processItems(
                    SelectItemCollection::createFromArray($config['items'], $config['type']),
                    $context,
                )->toArray();
                self::assertSame([0, 10, 20], array_map(static fn(SelectItem $item) => $item->getValue(), $items));
                self::assertSame(
                    ['Static item', 'From processor 50', $tsConfig['itemsProcessors.']['100.']['label'] ?? 'From processor 100'],
                    array_map(static fn(SelectItem $item) => $item->getLabel(), $items),
                );
            }
        }
    }
}
