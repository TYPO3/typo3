<?php
declare(strict_types = 1);

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
 * Installs EXT:adminpanel
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class AdminPanelInstall extends AbstractDownloadExtensionUpdate
{

    /**
     * @var \TYPO3\CMS\Install\Updates\Confirmation
     */
    protected $confirmation;

    public function __construct()
    {
        $this->extension = new ExtensionModel(
            'adminpanel',
            'TYPO3 Admin Panel',
            '9.2',
            'typo3/cms-adminpanel',
            'The TYPO3 admin panel provides a panel with additional functionality in the frontend (Debugging, Caching, Preview...)'
        );

        $this->confirmation = new Confirmation(
            'Are you sure?',
            'You should install the "adminpanel" only if needed. ' . $this->extension->getDescription(),
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
        return 'adminpanelExtension';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Install extension "adminpanel"';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'The TYPO3 admin panel was extracted to an own extension. This update installs the extension.';
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        return !ExtensionManagementUtility::isLoaded('adminpanel');
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
