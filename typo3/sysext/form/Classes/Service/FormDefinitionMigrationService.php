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

namespace TYPO3\CMS\Form\Service;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Service for migrating form definitions to newer formats.
 *
 * This service handles automatic migrations of form definitions when they are loaded,
 * ensuring backward compatibility with older form configurations.
 *
 * @internal
 */
class FormDefinitionMigrationService implements SingletonInterface
{
    /**
     * Apply all necessary migrations to a form definition.
     *
     * This method is called automatically when a form definition is loaded
     * and applies all registered migrations in sequence.
     */
    public function migrate(array $formDefinition, string $persistenceIdentifier = ''): array
    {
        $formDefinition = $this->migrateFieldExplanationText($formDefinition, $persistenceIdentifier);

        return $formDefinition;
    }

    /**
     * Migrate fieldExplanationText to description in formElementsDefinition.
     */
    private function migrateFieldExplanationText(array $formDefinition, string $persistenceIdentifier): array
    {
        if (!isset($formDefinition['prototypes'])) {
            return $formDefinition;
        }

        foreach ($formDefinition['prototypes'] as $prototypeName => $prototype) {
            if (!isset($prototype['formElementsDefinition'])) {
                continue;
            }

            foreach ($prototype['formElementsDefinition'] as $elementTypeName => $elementType) {
                if (!isset($elementType['formEditor'])) {
                    continue;
                }

                $basePath = ['prototypes', $prototypeName, 'formElementsDefinition', $elementTypeName, 'formEditor'];

                $formDefinition['prototypes'][$prototypeName]['formElementsDefinition'][$elementTypeName]['formEditor'] = $this->migrateFieldExplanationTextRecursive(
                    $elementType['formEditor'],
                    $persistenceIdentifier,
                    $basePath
                );
            }
        }

        return $formDefinition;
    }

    /**
     * Recursively migrate fieldExplanationText to description.
     *
     * Deprecated: fieldExplanationText was renamed to description in TYPO3 v14.0
     * and will be removed in v15.0.
     */
    private function migrateFieldExplanationTextRecursive(array $data, string $persistenceIdentifier, array $path): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $currentPath = array_merge($path, [$key]);

            if ($key === 'fieldExplanationText') {
                trigger_error(
                    sprintf(
                        'Using "fieldExplanationText" is deprecated, use "description" instead. Found at path "%s". Support for "fieldExplanationText" will be removed in TYPO3 v15.0.',
                        implode('.', $currentPath)
                    ),
                    E_USER_DEPRECATED
                );
                $result['description'] = $value;
                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->migrateFieldExplanationTextRecursive($value, $persistenceIdentifier, $currentPath);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
