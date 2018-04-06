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
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Installs and downloads EXT:form_legacy if needed
 */
class FormLegacyExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var string
     */
    protected $title = 'Install extension "form_legacy" from TER';

    /**
     * @var string
     */
    protected $extensionKey = 'form_legacy';

    /**
     * @var array
     */
    protected $extensionDetails = [
        'form_legacy' => [
            'title' => 'Legacy form extension for TYPO3 v7 compatibility',
            'description' => 'Provides an additional backwards-compatibility layer with legacy functionality for sites that used the form extension in TYPO3 v7.',
            'versionString' => '8.7.0',
            'composerName' => 'friendsoftypo3/form-legacy',
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
        $description = 'The extension "form" was rewritten in TYPO3 v8 and follows a new approach.'
            . 'This update downloads the old implementation of the form extension as known from TYPO3 v7 from the TER.';

        $updateNeeded = false;

        if (!$this->isWizardDone() && !ExtensionManagementUtility::isLoaded('form_legacy')) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $count = $queryBuilder
                ->count('*')
                ->from('tt_content')
                ->where($queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('mailform')))
                ->execute()
                ->fetchColumn(0);
            if ($count > 0) {
                $updateNeeded = true;
            }
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
                    <p>You should install EXT:form_legacy only if you really need it.</p>
                    <p>This update wizard checked all content elements and found at least one not deleted element based
                    on the old form module. It is advised to manually convert those elements from the old form implementation
                    to the new implementation of EXT:form. EXT:form_legacy should be unloaded and removed afterwards.</p>
                    <p>Are you really sure, you want to install EXT:form_legacy?</p>
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
     * Performs the update if EXT:form_legacy should be installed.
     *
     * @param array $databaseQueries Queries done in this update
     * @param string $customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $requestParams = GeneralUtility::_GP('install');
        if (!isset($requestParams['values']['formLegacyExtractionUpdate']['install'])) {
            return false;
        }
        $install = (int)$requestParams['values']['formLegacyExtractionUpdate']['install'];

        if ($install === 1) {
            // user decided to install extension, install and mark wizard as done
            $updateSuccessful = $this->installExtension($this->extensionKey, $customMessage);
            if ($updateSuccessful) {
                $this->markWizardAsDone();
                return true;
            }
        } else {
            // user decided to not install extension, mark wizard as done
            $this->markWizardAsDone();
            return true;
        }
        return $updateSuccessful;
    }
}
