<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Felogin\Updates;

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

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MigrateFeloginPluginsCtype implements UpgradeWizardInterface, RepeatableInterface
{

    private const CTYPE_PIBASE = 'login';
    private const CTYPE_EXTBASE = 'felogin_login';

    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string {
        return self::class;
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string {
        return 'Migrate felogin plugins to use extbase CType';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string {
        return  'This wizard migrates existing front end plugins of the extension felogin from piBase key to ' .
            'the new Extbase "CType"';
    }

    /**
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     *
     * @return bool
     */
    public function executeUpdate(): bool {
        $oldCType = $this->isExtbase() ? self::CTYPE_PIBASE : self::CTYPE_EXTBASE;
        $newCType = $this->isExtbase() ? self::CTYPE_EXTBASE : self::CTYPE_PIBASE;

        // Get all tt_content data for login plugins and update their CTypes and Flexforms settings
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $statement = $queryBuilder
            ->select('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter($oldCType)
                )
            )
            ->execute();

        while ($record = $statement->fetch()) {
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->update('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($record['uid'], \PDO::PARAM_INT)
                    )
                )
                ->set('CType', $newCType)
                ->execute();
        }

        return true;
    }

    /**
     * Is an update necessary?
     *
     * If the feature toggle is set: Looks for new fe plugins to be rolled back
     * Otherwise looks for old record sets to be migrated
     *
     * @return bool
     */
    public function updateNecessary(): bool {
        // Which content type should be search for?
        $CType = $this->isExtbase() ? self::CTYPE_PIBASE : self::CTYPE_EXTBASE;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $elementCount = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter($CType))
            )
            ->execute()->fetchColumn();

        return (bool)$elementCount;
    }

    /**
     * Returns an array of class names of Prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array {
        return [
            MigrateFeloginPlugins::class
        ];
    }

    /**
     * Checks if feature toggle to use extbase version is enabled
     *
     * @return bool
     */
    protected function isExtbase():bool {
        return GeneralUtility::makeInstance(Features::class)
            ->isFeatureEnabled('felogin.extbase');
    }
}
