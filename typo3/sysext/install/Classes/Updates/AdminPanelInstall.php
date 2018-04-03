<?php
declare(strict_types=1);

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Installs EXT:adminpanel
 */
class AdminPanelInstall extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Install extension "adminpanel"';

    /**
     * @var string
     */
    protected $extensionKey = 'adminpanel';

    /**
     * Checks if an update is needed
     *
     * @param string $description The description for the update
     * @return bool Whether an update is needed (true) or not (false)
     */
    public function checkForUpdate(&$description): bool
    {
        $description = 'The TYPO3 admin panel was extracted to an own extension. This update installs the extension.';

        if (ExtensionManagementUtility::isLoaded('adminpanel')) {
            $this->markWizardAsDone();
        }

        $updateNeeded = false;
        if (!$this->isWizardDone()) {
            $updateNeeded = true;
        }
        return $updateNeeded;
    }

    /**
     * Performs the update
     *
     * @param array $databaseQueries Queries done in this update
     * @param string $customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage): bool
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $extensionInstallUtility = $objectManager->get(InstallUtility::class);
        try {
            $extensionInstallUtility->install('adminpanel');
            $updateSuccessful = true;
            $this->markWizardAsDone();
        } catch (ExtensionManagerException $e) {
            $updateSuccessful = false;
        }
        return $updateSuccessful;
    }
}
