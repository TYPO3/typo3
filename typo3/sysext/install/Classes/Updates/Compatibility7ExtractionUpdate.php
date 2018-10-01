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
 * Installs and downloads EXT:compatibility7 if needed
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class Compatibility7ExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var string
     */
    protected $title = 'Install extension "compatibility7" from TER';

    /**
     * @var string
     */
    protected $extensionKey = 'compatibility7';

    /**
     * @var array
     */
    protected $extensionDetails = [
        'compatibility7' => [
            'title' => 'Compatibility Mode for TYPO3 v7',
            'description' => 'Provides an additional backwards-compatibility layer with legacy functionality for sites that haven\'t fully migrated to TYPO3 v8 yet.',
            'versionString' => '8.7.1',
            'composerName' => 'friendsoftypo3/compatibility7',
        ],
    ];

    /**
     * @var \TYPO3\CMS\Install\Updates\ExtensionModel
     */
    protected $extension;

    /**
     * @var \TYPO3\CMS\Install\Updates\Confirmation
     */
    protected $confirmation;

    public function __construct()
    {
        $this->extension = new ExtensionModel(
            'compatibility7',
            'Compatibility Mode for TYPO3 v7',
            '8.7.1',
            'friendsoftypo3/compatibility7',
            'Provides an additional backwards-compatibility layer with legacy functionality for sites that haven\'t fully migrated to TYPO3 v8 yet.'
        );

        $this->confirmation = new Confirmation(
            'Are you sure?',
            'The compatibility extensions come with a performance penalty, use only if needed. ' . $this->extension->getDescription(),
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
        return 'compatibility7Extension';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Install compatibility extension for TYPO3 7 compatibility';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'The extension "compatibility7" (Compatibility Mode for TYPO3 v7) was extracted into '
               . 'the TYPO3 Extension Repository. This update downloads the TYPO3 Extension from the TER.';
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        return !ExtensionManagementUtility::isLoaded('compatibility7');
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
