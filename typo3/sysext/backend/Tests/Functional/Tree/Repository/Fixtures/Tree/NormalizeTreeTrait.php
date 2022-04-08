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

namespace TYPO3\CMS\Backend\Tests\Functional\Tree\Repository\Fixtures\Tree;

trait NormalizeTreeTrait
{
    /**
     * Sorts tree array by index of each section item recursively.
     */
    private function sortTreeArray(array $tree): array
    {
        ksort($tree);
        return array_map(
            function (array $item) {
                foreach ($item as $propertyName => $propertyValue) {
                    if (!is_array($propertyValue)) {
                        continue;
                    }
                    $item[$propertyName] = $this->sortTreeArray($propertyValue);
                }
                return $item;
            },
            $tree
        );
    }

    /**
     * Normalizes a tree array, re-indexes numeric indexes, only keep given properties.
     *
     * @param array $tree Whole tree array
     * @param array $keepProperties (property names to be used as indexes for array_intersect_key())
     * @return array Normalized tree array
     */
    private function normalizeTreeArray(array $tree, array $keepProperties): array
    {
        return array_map(
            function (array $item) use ($keepProperties) {
                // only keep these property names
                $item = array_intersect_key($item, $keepProperties);
                foreach ($item as $propertyName => $propertyValue) {
                    if (!is_array($propertyValue)) {
                        continue;
                    }
                    // process recursively for nested array items (e.g. `_children`)
                    $item[$propertyName] = $this->normalizeTreeArray($propertyValue, $keepProperties);
                }
                return $item;
            },
            // normalize numeric indexes (remove sorting markers)
            array_values($tree)
        );
    }
}
