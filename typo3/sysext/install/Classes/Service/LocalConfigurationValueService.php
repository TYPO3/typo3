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
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status\OkStatus;

/**
 * Service handling bulk read and write of LocalConfiguration values.
 *
 * Used by "Configure global settings" / "All configuration" view.
 */
class LocalConfigurationValueService
{
    /**
     * Error handlers are a bit mask in PHP. This register hints the View to
     * add a fluid view helper resolving the bit mask to its representation
     * as constants again for the specified items in ['SYS'].
     *
     * @var array
     */
    protected $phpErrorCodesSettings = [
        'errorHandlerErrors',
        'exceptionalErrors',
        'syslogErrorReporting',
        'belogErrorReporting',
    ];

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
                $description = trim((string)$commentArray[$sectionName][$key]);
                $isTextarea = (bool)preg_match('/^(<.*?>)?string \\(textarea\\)/i', $description);
                $doNotRender = (bool)preg_match('/^(<.*?>)?string \\(exclude\\)/i', $description);

                if (!is_array($value) && !$doNotRender && (!preg_match('/[' . LF . CR . ']/', (string)$value) || $isTextarea)) {
                    $itemData = [];
                    $itemData['key'] = $key;
                    $itemData['description'] = $description;
                    if ($isTextarea) {
                        $itemData['type'] = 'textarea';
                        $itemData['value'] = str_replace(['\' . LF . \'', '\' . LF . \''], [LF, LF], $value);
                    } elseif (preg_match('/^(<.*?>)?boolean/i', $description)) {
                        $itemData['type'] = 'checkbox';
                        $itemData['value'] = $value ? '1' : '0';
                        $itemData['checked'] = (bool)$value;
                    } elseif (preg_match('/^(<.*?>)?integer/i', $description)) {
                        $itemData['type'] = 'number';
                        $itemData['value'] = (int)$value;
                    } else {
                        $itemData['type'] = 'input';
                        $itemData['value'] = $value;
                    }

                    // Check if the setting is a PHP error code, will trigger a view helper in fluid
                    if ($sectionName === 'SYS' && in_array($key, $this->phpErrorCodesSettings)) {
                        $itemData['phpErrorCode'] = true;
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
     * @return array StatusInterface[]
     */
    public function updateLocalConfigurationValues(array $valueList): array
    {
        $statusObjects = [];
        $configurationPathValuePairs = [];
        $commentArray = $this->getDefaultConfigArrayComments();
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        foreach ($valueList as $path => $value) {
            $oldValue = $configurationManager->getConfigurationValueByPath($path);
            $description = ArrayUtility::getValueByPath($commentArray, $path);

            if (preg_match('/^string \\(textarea\\)/i', $description)) {
                // Force Unix line breaks in text areas
                $value = str_replace(CR, '', $value);
                // Preserve line breaks
                $value = str_replace(LF, '\' . LF . \'', $value);
            }

            if (preg_match('/^(<.*?>)?boolean/i', $description)) {
                // When submitting settings in the Install Tool, values that default to "FALSE" or "TRUE"
                // in EXT:core/Configuration/DefaultConfiguration.php will be sent as "0" resp. "1".
                $value = $value === '1';
                $valueHasChanged = (bool)$oldValue !== $value;
            } elseif (preg_match('/^(<.*?>)?integer/i', $description)) {
                // Cast integer values to integers (but only for values that can not contain a string as well)
                $value = (int)$value;
                $valueHasChanged = (int)$oldValue !== $value;
            } else {
                $valueHasChanged = (string)$oldValue !== (string)$value;
            }

            // Save if value changed
            if ($valueHasChanged) {
                $configurationPathValuePairs[$path] = $value;
                $status = GeneralUtility::makeInstance(OkStatus::class);
                $status->setTitle($path);
                if (is_bool($value)) {
                    $status->setMessage('New value = ' . ($value ? 'true' : 'false'));
                } else {
                    $status->setMessage('New value = ' . $value);
                }
                $statusObjects[] = $status;
            }
        }
        if (!empty($statusObjects)) {
            $configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationPathValuePairs);
        }
        return $statusObjects;
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
        return require $configurationManager->getDefaultConfigurationDescriptionFileLocation();
    }
}
