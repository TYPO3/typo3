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
        ]
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

            $columns = $this->getDatabaseConnection()->admin_get_fields('fe_users');
            if (isset($columns['tx_openid_openid'])) {
                $columnsExists = true;
            }
            $columns = $this->getDatabaseConnection()->admin_get_fields('be_users');
            if (isset($columns['tx_openid_openid'])) {
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
     * @param mixed $customMessages Custom messages
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessages)
    {
        $updateSuccessful = $this->installExtension($this->extensionKey, $customMessages);
        if ($updateSuccessful) {
            $this->markWizardAsDone();
        }
        return $updateSuccessful;
    }
}
