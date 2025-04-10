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

namespace TYPO3\CMS\Install\Service;

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service handling bulk read and write of LocalConfiguration values.
 *
 * Used by "Configure global settings" / "All configuration" view.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class LocalConfigurationValueService
{
    /**
     * Get up configuration data. Prepares main TYPO3_CONF_VARS
     * array to be displayed and merges is with the description file
     *
     * @return array Configuration data
     */
    public function getCurrentConfigurationData(): array
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $localConfiguration = $configurationManager->getMergedLocalConfiguration();

        $data = [];
        $commentArray = $this->getDefaultConfigArrayComments();

        foreach ($localConfiguration as $sectionName => $section) {
            if (isset($commentArray[$sectionName])) {
                $data[$sectionName]['description'] = $commentArray[$sectionName]['description'] ?? $sectionName;
                $data[$sectionName]['items'] = $this->recursiveConfigurationFetching(
                    $section,
                    $GLOBALS['TYPO3_CONF_VARS'][$sectionName] ?? null,
                    $commentArray[$sectionName]
                );
            }
        }

        ksort($data);

        return $data;
    }

    /**
     * Because configuration entries can be at any sub-array level, we need
     * to check entries recursively.
     * Supported description types are:
     *  - `bool`            boolean on/off toggles
     *  - `dropdown`        dropdowns
     *  - `text`            single-line text
     *  - `int`             number inputs
     *  - `list`            single-line text with comma-separated values
     *  - `multiline`       multi-line text input
     *  - `password`        password input
     *  - `mixed`           mixed-types, which can behave either as "text" or an array (only via manually editing `settings.php`)
     *  - `container`       a container for grouping multiple inputs
     *  - `phpClass`        a string representing a PHP classname
     *  - `errors`          a special dropdowns for PHP error mappings
     *  - `array`           comma-separated values treated as a "list" (like an "array with numerical values")
     *  - `map`             array keys+values (`$someArray['someKey' => 'someValue']`)
     *  - `element-list`    numerical indexed array values (`$someArray[] = 'someValue'`)
     */
    protected function recursiveConfigurationFetching(array $sections, array $sectionsFromCurrentConfiguration, array $descriptions, array $path = []): array
    {
        $data = [];

        foreach ($sections as $key => $value) {
            if (!isset($descriptions['items'][$key])) {
                // @todo should we do something here?
                continue;
            }

            $descriptionInfo = $descriptions['items'][$key];
            $descriptionType = $descriptionInfo['type'];

            $newPath = $path;
            $newPath[] = $key;

            if ($descriptionType === 'container') {
                $valueFromCurrentConfiguration = $sectionsFromCurrentConfiguration[$key] ?? null;
                $data = array_merge($data, $this->recursiveConfigurationFetching($value, $valueFromCurrentConfiguration, $descriptionInfo, $newPath));
            } elseif (!preg_match('/[' . LF . CR . ']/', (string)(is_array($value) ? '' : $value)) || $descriptionType === 'multiline') {
                $itemData = [];
                $itemData['key'] = implode('/', $newPath);
                $itemData['path'] = '[' . implode('][', $newPath) . ']';
                $itemData['fieldType'] = $descriptionInfo['type'];
                $itemData['description'] = $descriptionInfo['description'] ?? '';
                $itemData['readonly'] = $descriptionInfo['readonly'] ?? false;
                $itemData['allowedValues'] = $descriptionInfo['allowedValues'] ?? [];
                $itemData['differentValueInCurrentConfiguration'] = (!isset($descriptionInfo['compareValuesWithCurrentConfiguration']) ||
                    $descriptionInfo['compareValuesWithCurrentConfiguration']) &&
                    isset($sectionsFromCurrentConfiguration[$key]) &&
                    $value !== $sectionsFromCurrentConfiguration[$key];
                switch ($descriptionType) {
                    case 'multiline':
                        $itemData['type'] = 'textarea';
                        $itemData['value'] = str_replace(['\' . LF . \'', '\' . LF . \''], [LF, LF], $value);
                        break;
                    case 'bool':
                        $itemData['type'] = 'checkbox';
                        $itemData['value'] = $value ? '1' : '0';
                        $itemData['checked'] = (bool)$value;
                        break;
                    case 'int':
                        $itemData['type'] = 'number';
                        $itemData['value'] = (int)$value;
                        break;
                    case 'map':
                        $itemData['type'] = 'map';
                        // Compatibility
                        $itemData['value'] = is_array($value) ? implode(',', $value) : (string)$value;
                        $itemData['values'] = is_array($value) ? $value : null;
                        $itemData['hideValue'] = true;
                        $itemData['arrayKey'] = $descriptionInfo['arrayKey'] ?? 'Key';
                        $itemData['arrayValue'] = $descriptionInfo['arrayValue'] ?? 'Value';
                        break;
                    case 'element-list':
                        // Same as above, but without special array key (just numerical index).
                        $itemData['type'] = 'element-list';
                        // Compatibility
                        $itemData['value'] = is_array($value) ? implode(',', $value) : (string)$value;
                        $itemData['values'] = is_array($value) ? $value : null;
                        $itemData['hideValue'] = true;
                        $itemData['arrayValue'] = $descriptionInfo['arrayValue'] ?? 'Value';
                        break;
                    case 'array':
                        $itemData['type'] = 'input';
                        // @todo The line below should be improved when the array handling is introduced in the global settings manager.
                        // @todo Also the types 'map' and 'element-list' above should be revisited then.
                        $itemData['value'] = is_array($value)
                            ? implode(',', $value)
                            : (string)$value;
                        break;
                        // Check if the setting is a PHP error code, will trigger a view helper in fluid
                    case 'errors':
                        $itemData['type'] = 'input';
                        $itemData['value'] = $value;
                        $itemData['phpErrorCode'] = true;
                        break;
                    case 'password':
                        $itemData['type'] = 'password';
                        $itemData['value'] = $value;
                        $itemData['hideValue'] = true;
                        break;
                    default:
                        $itemData['type'] = 'input';
                        $itemData['value'] = $value;
                }

                $data[] = $itemData;
            }
        }

        return $data;
    }

    /**
     * Store changed values in LocalConfiguration
     *
     * @param array $valueList Nested array with key['key'] value
     */
    public function updateLocalConfigurationValues(array $valueList): FlashMessageQueue
    {
        $messageQueue = new FlashMessageQueue('install');
        $configurationPathValuePairs = [];
        $commentArray = $this->getDefaultConfigArrayComments();
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        foreach ($valueList as $path => $value) {
            try {
                $oldValue = $configurationManager->getConfigurationValueByPath($path);
            } catch (MissingArrayPathException) {
                $messageQueue->enqueue(new FlashMessage(
                    'Update rejected, the category of this setting does not exist',
                    $path,
                    ContextualFeedbackSeverity::ERROR
                ));
                continue;
            }
            $pathParts = explode('/', $path);
            $descriptionData = $commentArray[$pathParts[0]];

            while ($part = next($pathParts)) {
                if (!isset($descriptionData['items'][$part])) {
                    $messageQueue->enqueue(new FlashMessage(
                        'Update rejected, this setting is not writable',
                        $path,
                        ContextualFeedbackSeverity::ERROR
                    ));
                    continue 2;
                }
                $descriptionData = $descriptionData['items'][$part];
            }

            $dataType = $descriptionData['type'];

            if ($dataType === 'multiline') {
                $value = str_replace(CR, '', $value);
                $valueHasChanged = (string)$oldValue !== (string)$value;
            } elseif ($dataType === 'bool') {
                // When submitting settings in the Install Tool, values that default to "FALSE" or "TRUE"
                // in EXT:core/Configuration/DefaultConfiguration.php will be sent as "0" resp. "1".
                $value = $value === '1';
                $valueHasChanged = (bool)$oldValue !== $value;
            } elseif ($dataType === 'int') {
                // Cast integer values to integers (but only for values that can not contain a string as well)
                $value = (int)$value;
                $valueHasChanged = (int)$oldValue !== $value;
            } elseif ($dataType === 'map') {
                $oldValueAsJson = json_encode($oldValue);
                $valueHasChanged = $oldValueAsJson !== json_encode($value);
                // Validate array
                if (!is_array($value)) {
                    $value = [];
                }
                $cleanedArray = [];
                foreach ($value as $arrayKey => $arrayValue) {
                    if (!is_scalar($arrayValue)) {
                        // Sub-arrays and any non-scalar key or value type not supported
                        continue;
                    }
                    // Note: Actual values (unlike keys) are scrubbed later and need no slashing here.
                    // Restoring config values with slashes is a problem, so instead we use htmlentities() to escape
                    // single and double quotes, which keeps PHP namespace backslashes.
                    // @todo may need further inspection.
                    $cleanedArray[htmlentities($arrayKey)] = $arrayValue;
                }
                $value = $cleanedArray;
            } elseif ($dataType === 'element-list') {
                // Validate array
                if (!is_array($value)) {
                    $value = [];
                }

                // Iterate list, throw away keys, start off zero-based.
                $elementList = $value;
                $value = [];
                foreach ($elementList as $arrayValue) {
                    if (is_scalar($arrayValue)) {
                        // Sub-arrays and any non-scalar key or value type not supported
                        $value[] = $arrayValue;
                    }
                }
                $oldValueAsJson = json_encode($oldValue);
                $valueHasChanged = $oldValueAsJson !== json_encode($elementList);
            } elseif ($dataType === 'array') {
                $oldValueAsString = is_array($oldValue)
                    ? implode(',', $oldValue)
                    : (string)$oldValue;
                $valueHasChanged = $oldValueAsString !== $value;
                $value = GeneralUtility::trimExplode(',', $value, true);
            } else {
                $valueHasChanged = (string)$oldValue !== (string)$value;
            }

            $readonly = $descriptionData['readonly'] ?? false;
            if ($readonly && $valueHasChanged) {
                $messageQueue->enqueue(new FlashMessage(
                    'Update rejected, this setting is readonly',
                    $path,
                    ContextualFeedbackSeverity::ERROR
                ));
                continue;
            }

            // Save if value changed
            if ($valueHasChanged) {
                $configurationPathValuePairs[$path] = $value;

                if (is_bool($value)) {
                    $messageBody = 'New value = ' . ($value ? 'true' : 'false');
                } elseif ($dataType === 'map') {
                    // "element-list" is covered by the 'is_array()' case.
                    $messageBody = 'New array value = ' . json_encode($value);
                } elseif (empty($value)) {
                    $messageBody = 'New value = none';
                } elseif (is_array($value)) {
                    $messageBody = "New value = ['" . implode("', '", $value) . "']";
                } elseif ($dataType === 'password') {
                    $messageBody = 'New value is set';
                } else {
                    $messageBody = 'New value = ' . $value;
                }

                $messageQueue->enqueue(new FlashMessage(
                    $messageBody,
                    $path
                ));
            }
        }
        if ($messageQueue->count() > 0) {
            $configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationPathValuePairs);
        }
        return $messageQueue;
    }

    /**
     * Read descriptions from description file
     */
    protected function getDefaultConfigArrayComments(): array
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $fileName = $configurationManager->getDefaultConfigurationDescriptionFileLocation();
        $fileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        return $fileLoader->load($fileName);
    }
}
