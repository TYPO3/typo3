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

namespace TYPO3\CMS\Core\Settings;

class CategoryAccumulator
{
    /**
     * Retrieve list of ordered sets, matched by
     * $setNames, including their dependencies (recursive)
     *
     * @param CategoryDefinition[] $categoryDefinitions
     * @param SettingDefinition[] $settingsDefinitions
     * @return list<Category>
     */
    public function getCategories(iterable $categoryDefinitions, iterable $settingsDefinitions): array
    {
        $categories = [];
        foreach ($categoryDefinitions as $category) {
            $data = $category->toArray();
            $parent = $data['parent'] ?? null;
            unset($data['parent']);
            $categories[$category->key] = [
                'children' => [],
                'parent' => $parent,
                'data' => $data,
            ];
        }
        foreach ($categoryDefinitions as $category) {
            if ($category->parent === null) {
                continue;
            }
            if (!isset($categories[$category->parent])) {
                throw new \RuntimeException('Missing parent category: ' . $category->parent, 1716291554);
            }
            $categories[$category->parent]['children'][] = $category->key;
        }

        $categorizedSettings = [];
        foreach ($settingsDefinitions as $definition) {
            $category = $definition->category ?? null;
            $categorizedSettings[isset($categories[$category]) ? $category : 'other'][] = $definition;
        }

        if (isset($categorizedSettings['other'])) {
            $categories['other'] = [
                'children' => [],
                'parent' => null,
                'data' => [
                    'key' => 'other',
                    'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_sitesettings.xlf:categories.other',
                    'description' => '',
                ],
            ];
        }

        $instances = [];
        foreach ($categories as $key => $category) {
            if ($category['parent'] === null) {
                $instances[] = $this->createInstance($categories, $key, $categorizedSettings);
            }
        }

        return $instances;
    }

    private function createInstance(array $categories, string $key, array $categorizedSettings): Category
    {
        try {
            return new Category(...[
                ...$categories[$key]['data'],
                'settings' => $categorizedSettings[$key] ?? [],
                'categories' => array_map(fn($key) => $this->createInstance($categories, $key, $categorizedSettings), $categories[$key]['children']),
            ]);
        } catch (\Error $e) {
            throw new \Exception('Invalid category definition: ' . json_encode($categories[$key]['data']), 1720528084, $e);
        }
    }
}
