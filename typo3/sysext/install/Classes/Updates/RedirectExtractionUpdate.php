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
 * Installs and downloads EXT:rdct if cache_md5params is filled
 */
class RedirectExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var string
     */
    protected $title = 'Install extension "rdct" from TER if DB table cache_md5params is filled';

    /**
     * @var string
     */
    protected $extensionKey = 'rdct';

    /**
     * @var array
     */
    protected $extensionDetails = [
        'rdct' => [
            'title' => 'Redirects based on &RDCT parameter',
            'description' => 'Provides redirects based on "cache_md5params" and the GET parameter &RDCT for extensions that still rely on it.',
            'versionString' => '1.0.0',
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
        $description = 'The extension "rdct" includes redirects based on the GET parameter &RDCT. The functionality has been extracted to'
            . ' the TYPO3 Extension Repository. This update downloads the TYPO3 extension from the TER.'
            . ' Use this if you are dealing with extensions in the instance that rely on this kind of redirects.';

        $updateNeeded = false;

        // Check if table exists and table is not empty, and the wizard has not been run already
        if ($this->checkIfWizardIsRequired() && !$this->isWizardDone()) {
            $updateNeeded = true;
        }

        return $updateNeeded;
    }

    /**
     * Second step: Ask user to install the extension
     *
     * @param string $inputPrefix input prefix, all names of form fields have to start with this. Append custom name in [ ... ]
     * @return string HTML output
     */
    public function getUserInput($inputPrefix)
    {
        return '
            <div class="panel panel-danger">
                <div class="panel-heading">Are you really sure?</div>
                <div class="panel-body">
                    <p>You should install EXT:rdct only if you really need it.</p>
                    <p>If you have never heard of index.php?RDCT then we are 99% confident that you don\'t need to install this extension.</p>
                    <p>Are you really sure, you want to install EXT:rdct?</p>
                    <div class="btn-group clearfix" data-toggle="buttons">
                        <label class="btn btn-default active">
                            <input type="radio" name="' . $inputPrefix . '[install]" value="0" checked="checked" /> no, don\'t install
                        </label>
                        <label class="btn btn-default">
                            <input type="radio" name="' . $inputPrefix . '[install]" value="1" /> yes, please install
                        </label>
                    </div>
                </div>
            </div>
        ';
    }

    /**
     * Performs the update if EXT:rdct should be installed.
     *
     * @param array $databaseQueries Queries done in this update
     * @param string $customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $requestParams = GeneralUtility::_GP('install');
        if (!isset($requestParams['values']['rdctExtension']['install'])) {
            return false;
        }
        $install = (int)$requestParams['values']['rdctExtension']['install'];

        $updateSuccessful = true;
        if ($install === 1) {
            // user decided to install extension, install and mark wizard as done
            $updateSuccessful = $this->installExtension($this->extensionKey, $customMessage);
        }
        if ($updateSuccessful) {
            $this->markWizardAsDone();
        }
        return $updateSuccessful;
    }

    /**
     * Check if the database table "cache_md5params" exists and if so, if there are entries in the DB table.
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function checkIfWizardIsRequired(): bool
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $tableNames = $connection->getSchemaManager()->listTableNames();
        if (in_array('cache_md5params', $tableNames, true)) {
            // table is available, now check if there are entries in it
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('cache_md5params');
            $numberOfEntries = $queryBuilder->count('*')
                ->from('cache_md5params')
                ->execute()
                ->fetchColumn();
            return (bool)$numberOfEntries;
        }

        return false;
    }
}
