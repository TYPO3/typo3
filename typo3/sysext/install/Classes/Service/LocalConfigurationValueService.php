<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Service;

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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service handling bulk read and write of LocalConfiguration values.
 *
 * Used by "Configure global settings" / "All configuration" view.
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
        $data = [];
        $typo3ConfVars = array_keys($GLOBALS['TYPO3_CONF_VARS']);
        sort($typo3ConfVars);
        $commentArray = $this->getDefaultConfigArrayComments();
        foreach ($typo3ConfVars as $sectionName) {
            $data[$sectionName] = [];

            foreach ($GLOBALS['TYPO3_CONF_VARS'][$sectionName] as $key => $value) {
                $descriptionInfo = $commentArray[$sectionName]['items'][$key];
                $descriptionType = $descriptionInfo['type'];
                if (!is_array($value) && (!preg_match('/[' . LF . CR . ']/', (string)$value) || $descriptionType === 'multiline')) {
                    $itemData = [];
                    $itemData['key'] = $key;
                    $itemData['fieldType'] = $descriptionInfo['type'];
                    $itemData['description'] = $descriptionInfo['description'];
                    $itemData['allowedValues'] = $descriptionInfo['allowedValues'];
                    $itemData['key'] = $key;
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
                        // Check if the setting is a PHP error code, will trigger a view helper in fluid
                        case 'errors':
                            $itemData['type'] = 'input';
                            $itemData['value'] = $value;
                            $itemData['phpErrorCode'] = true;
                        break;
                        default:
                            $itemData['type'] = 'input';
                            $itemData['value'] = $value;
                    }

                    $data[$sectionName][] = $itemData;
                }
            }
        }
        return $data;
    }

    /**
     * Store changed values in LocalConfiguration
     *
     * @param array $valueList Nested array with key['key'] value
     * @return FlashMessageQueue
     */
    public function updateLocalConfigurationValues(array $valueList): FlashMessageQueue
    {
        $messageQueue = new FlashMessageQueue('install');
        $configurationPathValuePairs = [];
        $commentArray = $this->getDefaultConfigArrayComments();
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        foreach ($valueList as $path => $value) {
            $oldValue = $configurationManager->getConfigurationValueByPath($path);
            $pathParts = explode('/', $path);
            $descriptionData = $commentArray[$pathParts[0]]['items'][$pathParts[1]];
            $dataType = $descriptionData['type'];

            if ($dataType === 'multiline') {
                // Force Unix line breaks in text areas
                $value = str_replace(CR, '', $value);
                // Preserve line breaks
                $value = str_replace(LF, '\' . LF . \'', $value);
            }

            if ($dataType === 'bool') {
                // When submitting settings in the Install Tool, values that default to "FALSE" or "TRUE"
                // in EXT:core/Configuration/DefaultConfiguration.php will be sent as "0" resp. "1".
                $value = $value === '1';
                $valueHasChanged = (bool)$oldValue !== $value;
            } elseif ($dataType === 'int') {
                // Cast integer values to integers (but only for values that can not contain a string as well)
                $value = (int)$value;
                $valueHasChanged = (int)$oldValue !== $value;
            } else {
                $valueHasChanged = (string)$oldValue !== (string)$value;
            }

            // Save if value changed
            if ($valueHasChanged) {
                $configurationPathValuePairs[$path] = $value;
                if (is_bool($value)) {
                    $messageBody = 'New value = ' . ($value ? 'true' : 'false');
                } else {
                    $messageBody = 'New value = ' . $value;
                }
                $messageQueue->enqueue(new FlashMessage(
                    $messageBody,
                    $path
                ));
            }
        }
        if (!empty($messageQueue)) {
            $configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationPathValuePairs);
        }
        return $messageQueue;
    }

    /**
     * Returns an array of available sections and their description
     *
     * @return string[]
     */
    public function getSpeakingSectionNames(): array
    {
        return [
            'BE' => 'Backend',
            'DB' => 'Database',
            'EXT' => 'Extension Installation',
            'FE' => 'Frontend',
            'GFX' => 'Image Processing',
            'HTTP' => 'Connection',
            'MAIL' => 'Mail',
            'SYS' => 'System'
        ];
    }

    /**
     * Read descriptions from description file
     *
     * @return array
     */
    protected function getDefaultConfigArrayComments(): array
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $fileName = $configurationManager->getDefaultConfigurationDescriptionFileLocation();
        $fileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        return $fileLoader->load($fileName);
    }
}
