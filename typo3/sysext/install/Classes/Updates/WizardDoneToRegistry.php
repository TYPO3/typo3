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
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Move "wizard done" flags to system registry
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class WizardDoneToRegistry implements UpgradeWizardInterface
{
    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'wizardDoneToRegistry';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Move "wizard done" flags from LocalConfiguration.php to system registry';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Moves all "wizard done" flags from LocalConfiguration.php to system registry.';
    }

    /**
     * Checks if an update is needed
     *
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
        $result = false;
        try {
            $wizardsDone = GeneralUtility::makeInstance(ConfigurationManager::class)
                ->getLocalConfigurationValueByPath('INSTALL/wizardDone');
            if (!empty($wizardsDone)) {
                $result = true;
            }
        } catch (MissingArrayPathException $e) {
            // Result stays false with broken path
        }
        return $result;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Performs the accordant updates.
     *
     * @return bool Whether everything went smoothly or not
     */
    public function executeUpdate(): bool
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $registry = GeneralUtility::makeInstance(Registry::class);
        $wizardsDone = $configurationManager->getLocalConfigurationValueByPath('INSTALL/wizardDone');
        $configurationKeysToRemove = [];
        foreach ($wizardsDone as $wizardClassName => $value) {
            $registry->set('installUpdate', $wizardClassName, $value);
            $configurationKeysToRemove[] = 'INSTALL/wizardDone/' . $wizardClassName;
        }
        $configurationKeysToRemove[] = 'INSTALL/wizardDone';
        $configurationManager->removeLocalConfigurationKeysByPath($configurationKeysToRemove);
        return true;
    }
}
