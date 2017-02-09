<?php
namespace TYPO3\CMS\Install\Updates;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Move access right parameters from "BE" to "SYS" configuration section
 */
class AccessRightParametersUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Move access right parameters configuration to "SYS" section';

    /**
     * @var array
     */
    protected $movedAccessRightConfigurationSettings = [
        'BE/fileCreateMask' => 'SYS/fileCreateMask',
        'BE/folderCreateMask' => 'SYS/folderCreateMask',
        'BE/createGroup' => 'SYS/createGroup',
    ];

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $updateNeeded = false;

        /** @var ConfigurationManager $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);

        // If the local configuration path can be accessed, the path is valid and the update wizard has to be executed
        foreach ($this->movedAccessRightConfigurationSettings as $oldPath => $newPath) {
            try {
                $configurationManager->getLocalConfigurationValueByPath($oldPath);
                $updateNeeded = true;
                break;
            } catch (\RuntimeException $e) {
            }
        }

        $description = 'Some access right parameters were moved from the "BE" to the "SYS" configuration section. ' .
            'The update wizards moves the settings to the new configuration destination.';

        return $updateNeeded;
    }

    /**
     * Performs the configuration update
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        /** @var ConfigurationManager $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        foreach ($this->movedAccessRightConfigurationSettings as $oldPath => $newPath) {
            try {
                $value = $configurationManager->getLocalConfigurationValueByPath($oldPath);
                $configurationManager->setLocalConfigurationValueByPath($newPath, $value);
            } catch (\RuntimeException $e) {
            }
        }
        $configurationManager->removeLocalConfigurationKeysByPath(array_keys($this->movedAccessRightConfigurationSettings));

        $this->markWizardAsDone();
        return true;
    }
}
