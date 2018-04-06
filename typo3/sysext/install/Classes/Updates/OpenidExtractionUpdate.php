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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Installs and downloads EXT:openid if needed
 */
class OpenidExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var string
     */
    protected $title = 'Installs extension "openid" from TER if openid is used.';

    /**
     * @var string
     */
    protected $extensionKey = 'openid';

    /**
     * @var array
     */
    protected $extensionDetails = [
        'openid' => [
            'title' => 'OpenID authentication',
            'description' => 'Adds OpenID authentication to TYPO3',
            'versionString' => '7.6.4',
            'composerName' => 'friendsoftypo3/openid',
        ],
    ];

    /**
     * Checks if an update is needed
     *
     * @param string $description The description for the update
     * @return bool Whether an update is needed (true) or not (false)
     */
    public function checkForUpdate(&$description)
    {
        $updateNeeded = false;

        if (!$this->isWizardDone()) {
            $columnsExists = false;

            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $schemaManager = $connectionPool->getConnectionForTable('fe_users')->getSchemaManager();

            if ($schemaManager->listTableDetails('fe_users')->hasColumn('tx_openid_openid')) {
                $columnsExists = true;
            }

            // Reinitialize schemaManager, since be_users could be on another connection
            $schemaManager = $connectionPool->getConnectionForTable('be_users')->getSchemaManager();

            if ($schemaManager->listTableDetails('be_users')->hasColumn('tx_openid_openid')) {
                $columnsExists = true;
            }
            if ($columnsExists) {
                $updateNeeded = true;
            }
        }

        $description = 'The extension "openid" (OpenID authentication) was extracted into '
            . 'the TYPO3 Extension Repository. This update checks if openid id used and '
            . 'downloads the TYPO3 Extension from the TER.';

        return $updateNeeded;
    }

    /**
     * Performs the update if EXT:openid is used.
     *
     * @param array $databaseQueries Queries done in this update
     * @param string $customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $updateSuccessful = $this->installExtension($this->extensionKey, $customMessage);
        if ($updateSuccessful) {
            $this->markWizardAsDone();
        }
        return $updateSuccessful;
    }
}
