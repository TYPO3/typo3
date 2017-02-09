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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Move "wizard done" flags to system registry
 */
class WizardDoneToRegistry extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Move "wizard done" flags from LocalConfiguration.php to system registry';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $result = false;
        $description = 'Moves all "wizard done" flags from LocalConfiguration.php to system registry.';

        try {
            $wizardsDone = GeneralUtility::makeInstance(ConfigurationManager::class)->getLocalConfigurationValueByPath('INSTALL/wizardDone');

            if (!empty($wizardsDone)) {
                $result = true;
            }
        } catch (\RuntimeException $e) {
        }

        return $result;
    }

    /**
     * Performs the accordant updates.
     *
     * @param array &$dbQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool Whether everything went smoothly or not
     */
    public function performUpdate(array &$dbQueries, &$customMessage)
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

        $this->markWizardAsDone();
        return true;
    }
}
