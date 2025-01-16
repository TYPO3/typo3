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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

/**
 * Generate data for type=select fields
 *
 * @internal
 */
final class TypeSelectRenderTypeSelectTree extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    protected array $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
            ],
        ],
    ];

    public function __construct(private readonly RecordFinder $recordFinder) {}

    public function generate(array $data): string
    {
        if (!isset($data['fieldConfig']['config']['minitems'])) {
            // "Parent count" field - just use zero as string here.
            return '0';
        }

        $numberOfItemsToSelect = $data['fieldConfig']['config']['minitems'];
        $items = $this->recordFinder->findUidsOfStyleguideEntryPages();
        $missingItems = count($items) - $numberOfItemsToSelect;
        for ($i = 0; $i < $missingItems; $i++) {
            // Use a holistic approach to use the entry page and just add an increment to it,
            // expecting that page to add. This is not yet triggered because no minitems=2
            // is defined.
            $items[] = $items[0] + $i;
        }

        return implode(',', $items);
    }
}
