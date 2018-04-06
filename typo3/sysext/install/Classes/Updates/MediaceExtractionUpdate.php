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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Installs and downloads EXT:mediace if needed
 */
class MediaceExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var string
     */
    protected $title = 'Installs extension "mediace" from TER if media elements are used.';

    /**
     * @var string
     */
    protected $extensionKey = 'mediace';

    /**
     * @var array
     */
    protected $extensionDetails = [
        'mediace' => [
            'title' => 'Media Content Element',
            'description' => 'The media functionality from TYPO3 6.2 and earlier can be found here.'
                . ' This extension provides ContentObjects and Content Elements.',
            'versionString' => '7.6.3',
            'composerName' => 'friendsoftypo3/mediace',
        ],
    ];

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $needsExecution = true;
        if ($this->isWizardDone() || ExtensionManagementUtility::isLoaded('mediace')) {
            $needsExecution = false;
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
            $queryBuilder->getRestrictions()->removeAll()
                 ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $numberOfRecords = $queryBuilder->count('uid')
                ->from('tt_content')
                ->where($queryBuilder->expr()->in(
                    'CType',
                    $queryBuilder->createNamedParameter(['media', 'multimedia'], Connection::PARAM_STR_ARRAY)
                ))
                ->execute()
                ->fetchColumn(0);
            if ((int)$numberOfRecords === 0) {
                $needsExecution = false;
            }
        }

        if ($needsExecution) {
            $description = 'The extension "mediace" (Media Content Element) was extracted into the'
                . ' TYPO3 Extension Repository. This update checks if media content elements are used'
                . ' and downloads the TYPO3 Extension from the TER.';
        }

        return $needsExecution;
    }

    /**
     * Performs the database update if media CTypes are available.
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
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
