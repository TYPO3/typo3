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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Installs and downloads EXT:rtehtmlarea if needed
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class RteHtmlAreaExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var \TYPO3\CMS\Install\Updates\Confirmation
     */
    protected $confirmation;

    public function __construct()
    {
        $this->extension = new ExtensionModel(
            'rtehtmlarea',
            'RTE HTMLArea for TYPO3',
            '8.7.0',
            'friendsoftypo3/rtehtmlarea',
            'The extension provides the well-known RTE used in previous TYPO3 versions, if handling of images or custom legacy configurations are necessary.'
        );

        $this->confirmation = new Confirmation(
            'Are you sure?',
            'You should install EXT:rtehtmlarea only if you really need it. ' . $this->extension->getDescription(),
            false
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
        return 'rtehtmlareaExtension';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Install extension "rtehtmlarea" from TER';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'The extension "rtehtmlarea" (RTE based on HtmlArea) was extracted into'
               . ' the TYPO3 Extension Repository. This update downloads the TYPO3 Extension from the TER.'
               . ' Use this if you have special configurations or image handling within Rich Text fields and uninstall the shipped EXT:rte_ckeditor.';
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        return !ExtensionManagementUtility::isLoaded('rtehtmlarea');
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
        return [];
    }
}
