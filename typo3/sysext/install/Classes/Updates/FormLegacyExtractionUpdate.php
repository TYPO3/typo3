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
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class FormLegacyExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var \TYPO3\CMS\Install\Updates\Confirmation
     */
    protected $confirmation;

    public function __construct()
    {
        $this->extension = new ExtensionModel(
            'form_legacy',
            'Legacy form extension for TYPO3 v7 compatibility',
            '8.7.0',
            'friendsoftypo3/form-legacy',
            'Provides an additional backwards-compatibility layer with legacy functionality for sites that used the form extension in TYPO3 v7.'
        );

        $this->confirmation = new Confirmation(
            'Are you really sure, you want to install EXT:form_legacy?',
            'You should install EXT:form_legacy only if you really need it.'
                    . 'This update wizard checked all content elements and found at least one not deleted element based'
                    . 'on the old form module. It is advised to manually convert those elements from the old form implementation'
                    . 'to the new implementation of EXT:form. EXT:form_legacy should be unloaded and removed afterwards.',
            true
        );
    }

    /**
     * Return a confirmation message instance
     *
     * @return \TYPO3\CMS\Install\Updates\Confirmation
     */
    public function getConfirmation(): Confirmation
    {
        return $this->confirmation;
    }

    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'formLegacyExtractionUpdate';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Install extension "form_legacy"';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'The extension "form" was rewritten in TYPO3 v8 and follows a new approach.'
        . 'This update downloads the old implementation of the form extension as known from TYPO3 v7 from the TER.';
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $updateNeeded = false;

        if (!ExtensionManagementUtility::isLoaded('form_legacy')) {
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
     * Returns an array of class names of Prerequisite classes
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }
}
