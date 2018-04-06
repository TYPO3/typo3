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
 * Installs and downloads EXT:rtehtmlarea if needed
 */
class RteHtmlAreaExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var string
     */
    protected $title = 'Install extension "rtehtmlarea" from TER';

    /**
     * @var string
     */
    protected $extensionKey = 'rtehtmlarea';

    /**
     * @var array
     */
    protected $extensionDetails = [
        'rtehtmlarea' => [
            'title' => 'RTE HTMLArea for TYPO3',
            'description' => 'Provides the well-known RTE used in previous TYPO3 versions, if handling of images or custom configurations are necessary.',
            'versionString' => '8.7.0',
            'composerName' => 'friendsoftypo3/rtehtmlarea',
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
        $description = 'The extension "rtehtmlarea" (RTE based on HtmlArea) was extracted into'
            . ' the TYPO3 Extension Repository. This update downloads the TYPO3 Extension from the TER.'
            . ' Use this if you have special configurations or image handling within Rich Text fields and uninstall the shipped EXT:rte_ckeditor.';

        $updateNeeded = false;

        if (!$this->isWizardDone()) {
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
                    <p>You should install EXT:rtehtmlarea only if you really need it.</p>
                    <p>This update wizard cannot check if the extension was installed before the update.</p>
                    <p>Are you really sure, you want to install EXT:rtehtmlarea?</p>
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
     * Performs the update if EXT:compatibility7 should be installed.
     *
     * @param array $databaseQueries Queries done in this update
     * @param string $customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $requestParams = GeneralUtility::_GP('install');
        if (!isset($requestParams['values']['rtehtmlareaExtension']['install'])) {
            return false;
        }
        $install = (int)$requestParams['values']['rtehtmlareaExtension']['install'];

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
