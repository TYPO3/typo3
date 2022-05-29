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

namespace TYPO3\CMS\FrontendLogin\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
final class MigrateFeloginPlugins implements UpgradeWizardInterface
{
    /**
     * @var array Flexform fields which we are interested in updating
     */
    protected static $flexFormFields = [
        'showForgotPassword',
        'showPermaLogin',
        'showLogoutFormAfterLogin',
        'pages',
        'recursive',
        'redirectMode',
        'redirectFirstMethod',
        'redirectPageLogin',
        'redirectPageLoginError',
        'redirectPageLogout',
        'redirectDisable',
        'welcome_header',
        'welcome_message',
        'success_header',
        'success_message',
        'error_header',
        'error_message',
        'status_header',
        'status_message',
        'logout_header',
        'logout_message',
        'forgot_header',
        'forgot_reset_message',
    ];

    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'TYPO3\\CMS\\Felogin\\Updates\\MigrateFeloginPlugins';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Migrate felogin plugins to use prefixed flexform keys';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'This wizard migrates existing front end plugins of the extension felogin to' .
            ' make use of the streamlined flexform keys. Therefore it updates the field values' .
            ' "pi_flexform" within the tt_content table';
    }

    /**
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        // Get all tt_content data for login plugins and update their flexforms settings
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');

        $queryBuilder = $connection->createQueryBuilder();
        $statement = $queryBuilder->select('uid')
            ->addSelect('pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('login'))
            )
            ->execute();

        // Update the found record sets
        while ($record = $statement->fetchAssociative()) {
            $queryBuilder = $connection->createQueryBuilder();
            $updateResult = $queryBuilder->update('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($record['uid'], \PDO::PARAM_INT)
                    )
                )
                ->set('pi_flexform', $this->migrateFlexformSettings($record['pi_flexform']))
                ->execute();

            //exit if at least one update statement is not successful
            if (!((bool)$updateResult)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Is an update necessary?
     *
     * Looks for fe plugins in tt_content table to be migrated
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content')
            ->createQueryBuilder();

        $queryBuilder->select('pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('login')),
                $this->getFlexformConstraints($queryBuilder)
            );

        return (bool)$queryBuilder->execute()->fetchOne();
    }

    /**
     * Returns an array of class names of Prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param string $oldValue
     * @return string
     */
    protected function migrateFlexformSettings(string $oldValue): string
    {
        $fieldNames = implode('|', self::$flexFormFields);
        $pattern = '/<field index="(' . $fieldNames . ')">/';
        $replacement = '<field index="settings.$1">';

        return preg_replace($pattern, $replacement, $oldValue);
    }

    /**
     * Creates a "like" statement for every flexform fields
     *
     * @param QueryBuilder $queryBuilder
     * @return CompositeExpression
     */
    protected function getFlexformConstraints(QueryBuilder $queryBuilder): CompositeExpression
    {
        $constraints = [];

        foreach (self::$flexFormFields as $flexFormField) {
            $value = '%<field index="' . $flexFormField . '">%';
            $constraints[] = $queryBuilder->expr()->like('pi_flexform', $queryBuilder->createNamedParameter($value));
        }

        return $queryBuilder->expr()->orX(...$constraints);
    }
}
