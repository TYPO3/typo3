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

namespace TYPO3\CMS\Styleguide\UserFunctions\FormEngine;

use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\CMS\Core\Tree\TableConfiguration\AbstractTableConfigurationTreeDataProvider;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A tree data provider for select_tree_8 that does not read records from the
 * database, e.g. like a provider fetching its data from a remote service.
 *
 * @internal
 */
final class TypeSelectTree8DataProvider extends AbstractTableConfigurationTreeDataProvider
{
    private const TREE = [
        'europe' => [
            'label' => 'Europe',
            'children' => [
                'at' => ['label' => 'Austria'],
                'de' => [
                    'label' => 'Germany',
                    'children' => [
                        'de-by' => ['label' => 'Bavaria'],
                        'de-be' => ['label' => 'Berlin'],
                    ],
                ],
                'ch' => ['label' => 'Switzerland'],
            ],
        ],
    ];

    public function getRoot(): DatabaseTreeNode
    {
        $root = $this->createNode('europe', self::TREE['europe']);
        $root->setSelectable(false);
        return $root;
    }

    private function createNode(string $identifier, array $configuration): DatabaseTreeNode
    {
        $node = new DatabaseTreeNode();
        $node->setId($identifier);
        $node->setLabel($configuration['label']);
        $node->setSelectable(true);
        $node->setSelected(GeneralUtility::inList($this->getSelectedList(), $identifier));
        if (($configuration['children'] ?? []) !== []) {
            $childNodes = new TreeNodeCollection();
            foreach ($configuration['children'] as $childIdentifier => $childConfiguration) {
                $childNodes->append($this->createNode($childIdentifier, $childConfiguration));
            }
            $node->setChildNodes($childNodes);
            $node->setHasChildren(true);
        }
        return $node;
    }
}
