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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Installs and downloads EXT:adodb and EXT:dbal
 */
class DbalAndAdodbExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var string
     */
    protected $title = '[Optional] Install extensions "dbal" and "adodb" from TER.';

    /**
     * @var array
     */
    protected $extensionDetails = [
        'adodb' => [
            'title' => 'ADOdb',
            'description' => 'Adds ADOdb to TYPO3',
            'versionString' => '8.4.0',
            'composerName' => 'friendsoftypo3/adodb',
        ],
        'dbal' => [
            'title' => 'dbal',
            'description' => 'Adds old database abstraction layer to TYPO3',
            'versionString' => '8.4.0',
            'composerName' => 'friendsoftypo3/dbal',
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
        $description = 'The extensions "dbal" and "adodb" have been extracted to'
            . ' the TYPO3 Extension Repository. This update downloads the TYPO3 Extension from the TER'
            . ' if the two extensions are still needed.';

        return !$this->isWizardDone();
    }

    /**
     * Second step: Ask user to install the extensions
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
                    <p>You should install EXT:adodb and EXT:dbal only if you really need it.</p>
                    <p>This update wizard cannot check if the extension was installed before the update.</p>
                    <p>Are you really sure, you want to install these two extensions?</p>
                    <p>They are only needed if this instance connects to a database server that is NOT MySQL
                    and if an active extension uses $GLOBALS[\'TYPO3_DB\'] and a table mapping for EXT:dbal
                    is configured.</p>
                    <p>Loading these two extensions is a rather seldom exceptions, the vast majority of
                    instances should say "no" here.</p>
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
     * Fetch and enable ext:adodb and ext:dbal
     *
     * @param array $databaseQueries Queries done in this update
     * @param string $customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $requestParams = GeneralUtility::_GP('install');
        if (!isset($requestParams['values']['TYPO3\CMS\Install\Updates\DbalAndAdodbExtractionUpdate']['install'])) {
            return false;
        }
        $install = (int)$requestParams['values']['TYPO3\CMS\Install\Updates\DbalAndAdodbExtractionUpdate']['install'];

        if ($install === 1) {
            // user decided to install extensions, install and mark wizard as done
            $adodbSuccessful = $this->installExtension('adodb', $customMessage);
            $dbalSuccessful = $this->installExtension('dbal', $customMessage);
            if ($adodbSuccessful && $dbalSuccessful) {
                $this->markWizardAsDone();
                return true;
            }
        } else {
            // user decided to not install extension, mark wizard as done
            $this->markWizardAsDone();
            return true;
        }
        return false;
    }
}
