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

namespace TYPO3\CMS\Core\Site\Set;

use TYPO3\CMS\Core\Settings\Category;
use TYPO3\CMS\Core\Settings\CategoryAccumulator;

class CategoryRegistry
{
    public function __construct(
        protected SetRegistry $setRegistry,
    ) {}

    /**
     * Retrieve list of instantiated categories for the list of
     * provided $setNames, including their dependencies (recursive)
     *
     * @return list<Category>
     */
    public function getCategories(string ...$setNames): array
    {
        $sets = $this->setRegistry->getSets(...$setNames);
        $categories = [];

        $categoryDefinitions = [];
        foreach ($sets as $set) {
            foreach ($set->categoryDefinitions as $definition) {
                $categoryDefinitions[$definition->key] = $definition;
            }
        }
        $settingsDefinitions = [];
        foreach ($sets as $set) {
            foreach ($set->settingsDefinitions as $definition) {
                $settingsDefinitions[$definition->key] = $definition;
            }
        }

        $cateryAccumulator = new CategoryAccumulator();
        return $cateryAccumulator->getCategories(
            $categoryDefinitions,
            $settingsDefinitions,
        );
    }
}
