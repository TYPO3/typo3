<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extensionmanager\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\Confirmation;

/**
 * Installs and downloads EXT:fe_login_mode
 *
 * @since 12.1
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('feLoginModeExtension')]
final class FeLoginModeExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    private const TABLE_NAME = 'pages';
    private const FIELD_NAME = 'fe_login_mode';

    public function __construct(private readonly ConnectionPool $connectionPool)
    {
        $this->extension = new ExtensionModel(
            'fe_login_mode',
            'Removed frontend user login mode functionality',
            '1.0.0',
            'o-ba/fe-login-mode',
            'This extension provides the frontend user login mode functionality, used in previous TYPO3 versions to reduce the amount of cache variants for complex user and group setups.'
        );
    }

    /**
     * Return a confirmation message instance
     */
    public function getConfirmation(): Confirmation
    {
        return new Confirmation(
            'Are you sure?',
            'You should install EXT:fe_login_mode only if you really need it. ' . $this->extension->getDescription(),
            false
        );
    }

    /**
     * Return the speaking name of this wizard
     */
    public function getTitle(): string
    {
        return 'Install extension "fe_login_mode" from TER';
    }

    /**
     * Return the description for this wizard
     */
    public function getDescription(): string
    {
        return 'To reduce complexity and speed up frontend requests, the rarely used "frontend user login mode"'
            . ' functionality has been extracted into the TYPO3 Extension Repository. This update downloads the TYPO3'
            . ' extension fe_login_mode from the TER. Use this if you\'re currently using this functionality.';
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     */
    public function updateNecessary(): bool
    {
        return !ExtensionManagementUtility::isLoaded($this->extension->getKey())
            && $this->columnExists()
            && $this->functionalityUsed();
    }

    /**
     * Returns an array of class names of Prerequisite classes
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     */
    public function getPrerequisites(): array
    {
        return [];
    }

    /**
     * Checks if the column still exists in the database, otherwise it's no
     * longer possible to determine whether the functionality was previously used.
     */
    protected function columnExists(): bool
    {
        $tableColumns = $this->connectionPool
            ->getConnectionForTable(self::TABLE_NAME)
            ->createSchemaManager()
            ->listTableColumns(self::TABLE_NAME);
        return isset($tableColumns[self::FIELD_NAME]);
    }

    /**
     * Check if the functionality was used by checking if at least one page
     * defines another value than the default (0) for the fe_login_mode field.
     */
    protected function functionalityUsed(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        return (bool)$queryBuilder
            ->count(self::FIELD_NAME)
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->neq(self::FIELD_NAME, 0))
            ->executeQuery()
            ->fetchOne();
    }
}
