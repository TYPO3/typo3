<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Hooks;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Form\Service\TranslationService;

/**
 * Hooks into flex form handling of backend for tt_content form elements:
 *
 * * Adds existing forms to flex form drop down list
 * * Adds finisher settings if "override finishers" is active
 *
 * Scope: backend
 * @internal
 */
class DataStructureIdentifierHook
{

    /**
     * The data structure depends on a current form selection (persistenceIdentifier)
     * and if the field "overrideFinishers" is active. Add both to the identifier to
     * hand these information over to parseDataStructureByIdentifierPostProcess() hook.
     *
     * @param array $fieldTca Incoming field TCA
     * @param string $tableName Handled table
     * @param string $fieldName Handled field
     * @param array $row Current data row
     * @param array $identifier Already calculated identifier
     * @return array Modified identifier
     */
    public function getDataStructureIdentifierPostProcess(
        array $fieldTca,
        string $tableName,
        string $fieldName,
        array $row,
        array $identifier
    ): array {
        if ($tableName === 'tt_content' && $fieldName === 'pi_flexform' && $row['CType'] === 'form_formframework') {
            $currentFlexData = [];
            if (!is_array($row['pi_flexform']) && !empty($row['pi_flexform'])) {
                $currentFlexData = GeneralUtility::xml2array($row['pi_flexform']);
            }

            // Add selected form value
            $identifier['ext-form-persistenceIdentifier'] = '';
            if (!empty($currentFlexData['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'])) {
                $identifier['ext-form-persistenceIdentifier'] = $currentFlexData['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'];
            }

            // Add bool - finisher override active or not
            $identifier['ext-form-overrideFinishers'] = false;
            if (
                isset($currentFlexData['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEF'])
                && (int)$currentFlexData['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEF'] === 1
            ) {
                $identifier['ext-form-overrideFinishers'] = true;
            }
        }
        return $identifier;
    }

    /**
     * Returns a modified flexform data array.
     *
     * This adds the list of existing form definitions to the form selection drop down
     * and adds sheets to override finisher settings if requested.
     *
     * @param array $dataStructure
     * @param array $identifier
     * @return array
     */
    public function parseDataStructureByIdentifierPostProcess(array $dataStructure, array $identifier): array
    {
        if (isset($identifier['ext-form-persistenceIdentifier'])) {
            // Add list of existing forms to drop down if we find our key in the identifier
            $formPersistenceManager = GeneralUtility::makeInstance(ObjectManager::class)->get(FormPersistenceManagerInterface::class);
            foreach ($formPersistenceManager->listForms() as $form) {
                $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['TCEforms']['config']['items'][] = [
                    $form['name'] . ' (' . $form['persistenceIdentifier'] . ')',
                    $form['persistenceIdentifier'],
                ];
            }

            // If a specific form is selected and if finisher override is active, add finisher sheets
            if (!empty($identifier['ext-form-persistenceIdentifier'])
                && isset($identifier['ext-form-overrideFinishers'])
                && $identifier['ext-form-overrideFinishers'] === true
            ) {
                $persistenceIdentifier = $identifier['ext-form-persistenceIdentifier'];
                $formDefinition = $formPersistenceManager->load($persistenceIdentifier);
                $newSheets = $this->getAdditionalFinisherSheets($persistenceIdentifier, $formDefinition);
                ArrayUtility::mergeRecursiveWithOverrule(
                    $dataStructure,
                    $newSheets
                );
            }
        }
        return $dataStructure;
    }

    /**
     * Returns additional flexform sheets with finisher fields
     *
     * @param string $persistenceIdentifier Current persistence identifier
     * @param array $formDefinition The form definition
     * @return array
     */
    protected function getAdditionalFinisherSheets(string $persistenceIdentifier, array $formDefinition): array
    {
        if (!isset($formDefinition['finishers']) || empty($formDefinition['finishers'])) {
            return [];
        }

        $prototypeName = isset($formDefinition['prototypeName']) ? $formDefinition['prototypeName'] : 'standard';
        $prototypeConfiguration = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ConfigurationService::class)
            ->getPrototypeConfiguration($prototypeName);

        if (!isset($prototypeConfiguration['finishersDefinition']) || empty($prototypeConfiguration['finishersDefinition'])) {
            return [];
        }

        $formIdentifier = $formDefinition['identifier'];
        $finishersDefinition = $prototypeConfiguration['finishersDefinition'];

        $sheets = ['sheets' => []];
        foreach ($formDefinition['finishers'] as $finisherValue) {
            $finisherIdentifier = $finisherValue['identifier'];
            if (!isset($finishersDefinition[$finisherIdentifier]['FormEngine']['elements'])) {
                continue;
            }
            $sheetIdentifier = md5(
                implode('', [
                    $persistenceIdentifier,
                    $prototypeName,
                    $formIdentifier,
                    $finisherIdentifier
                ])
            );

            $translationFile = $finishersDefinition[$finisherIdentifier]['FormEngine']['translationFile'];
            $finishersDefinition[$finisherIdentifier]['FormEngine'] = TranslationService::getInstance()->translateValuesRecursive(
                $finishersDefinition[$finisherIdentifier]['FormEngine'],
                $translationFile
            );
            $finisherLabel = $finishersDefinition[$finisherIdentifier]['FormEngine']['label'];
            $sheet = $this->initializeNewSheetArray($sheetIdentifier, $finisherLabel);

            $sheetElements = [];
            foreach ($finisherValue['options'] as $optionKey => $optionValue) {
                if (is_array($optionValue)) {
                    $optionKey = $optionKey . '.' . $this->extractDottedPathToLastElement($finisherValue['options'][$optionKey]);
                    $elementConfiguration = ArrayUtility::getValueByPath($finishersDefinition[$finisherIdentifier]['FormEngine']['elements'], $optionKey);
                    $optionValue = ArrayUtility::getValueByPath($finisherValue['options'], $optionKey);
                } else {
                    $elementConfiguration = $finishersDefinition[$finisherIdentifier]['FormEngine']['elements'][$optionKey];
                }

                if (empty($elementConfiguration)) {
                    continue;
                }

                if (empty($optionValue)) {
                    $elementConfiguration['label'] .= ' (default: "[Empty]")';
                } else {
                    $elementConfiguration['label'] .= ' (default: "' . $optionValue . '")';
                }
                $elementConfiguration['config']['default'] = $optionValue;
                $sheetElements['settings.finishers.' . $finisherIdentifier . '.' . $optionKey] = $elementConfiguration;
            }

            ksort($sheetElements);

            $sheet[$sheetIdentifier]['ROOT']['el'] = $sheetElements;
            ArrayUtility::mergeRecursiveWithOverrule($sheets['sheets'], $sheet);
        }
        if (empty($sheets['sheets'])) {
            return [];
        }

        return $sheets;
    }

    /**
     * Boilerplate XML array of a new sheet
     *
     * @param string $sheetIdentifier
     * @param string $finisherName
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function initializeNewSheetArray(string $sheetIdentifier, string $finisherName): array
    {
        if (empty($sheetIdentifier)) {
            throw new \InvalidArgumentException('$sheetIdentifier must not be empty.', 1472060918);
        }
        if (empty($finisherName)) {
            throw new \InvalidArgumentException('$finisherName must not be empty.', 1472060919);
        }

        return [
            $sheetIdentifier => [
                'ROOT' => [
                    'TCEforms' => [
                        'sheetTitle' => $finisherName,
                    ],
                    'type' => 'array',
                    'el' => [],
                ],
            ],
        ];
    }

    /**
     * Recursive helper to implode a nested array to a dotted path notation
     *
     * @param array $array
     * @return string
     */
    protected function extractDottedPathToLastElement(array $array): string
    {
        $dottedPath = key($array);
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $dottedPath = $dottedPath . '.' . $this->extractDottedPathToLastElement($value);
            }
        }
        return $dottedPath;
    }
}
