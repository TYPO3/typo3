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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Migrate CTypes 'text', 'image' and 'textpic' to 'textmedia' for extension 'frontend'
 */
class ContentTypesToTextMediaUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Migrate CTypes text, image and textpic to textmedia and move file relations from "image" to "asset_references"';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if (
            !ExtensionManagementUtility::isLoaded('fluid_styled_content')
            || ExtensionManagementUtility::isLoaded('css_styled_content')
            || $this->isWizardDone()
        ) {
            return false;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $nonTextmediaCount = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->in(
                    'CType',
                    $queryBuilder->createNamedParameter(
                        ['text', 'image', 'textpic'],
                        Connection::PARAM_STR_ARRAY
                    )
                )
            )
            ->execute()->fetchColumn(0);

        if ((bool)$nonTextmediaCount) {
            $description = 'The extension "fluid_styled_content" is using a new CType, textmedia, ' .
                'which replaces the CTypes text, image and textpic. ' .
                'This update wizard migrates these old CTypes to the new one in the database. ' .
                'If backend groups have the explicit deny/allow flag set for any of the old CTypes, ' .
                'the according flag for the CType textmedia is set as well.';
        }

        return (bool)$nonTextmediaCount;
    }

    /**
     * Performs the database update if old CTypes are available
     *
     * @param array &$databaseQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessages)
    {
        $ttContentConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');
        $falConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_reference');

        // Update text to textmedia
        $queryBuilder = $ttContentConnection->createQueryBuilder();
        $queryBuilder->update('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('text', \PDO::PARAM_STR)
                )
            )
            ->set('CType', 'textmedia');
        $databaseQueries[] = $queryBuilder->getSQL();
        $queryBuilder->execute();

        // Update 'textpic' and 'image' records
        $queryBuilder = $ttContentConnection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('uid', 'image', 'CType')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(
                        'CType',
                        $queryBuilder->createNamedParameter('textpic', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'CType',
                        $queryBuilder->createNamedParameter('image', \PDO::PARAM_STR)
                    )
                )
            )->execute();
        while ($ttContentRow = $statement->fetch()) {
            $falQueryBuilder = $falConnection->createQueryBuilder();
            $falQueryBuilder->update('sys_file_reference')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid_foreign',
                        $falQueryBuilder->createNamedParameter($ttContentRow['uid'], \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $falQueryBuilder->createNamedParameter('tt_content', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $falQueryBuilder->createNamedParameter('image', \PDO::PARAM_STR)
                    )
                )
                ->set('fieldname', 'assets');
            $databaseQueries[] = $falQueryBuilder->getSQL();
            $falQueryBuilder->execute();

            $ttContentQueryBuilder = $ttContentConnection->createQueryBuilder();
            $ttContentQueryBuilder->update('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $ttContentQueryBuilder->createNamedParameter($ttContentRow['uid'], \PDO::PARAM_INT)
                    )
                )
                ->set('CType', 'textmedia')
                ->set('assets', (int)$ttContentRow['image'])
                ->set('image', 0);
            $databaseQueries[] = $ttContentQueryBuilder->getSQL();
            $ttContentQueryBuilder->execute();
        }

        // Update explicitDeny - ALLOW
        $beGroupsConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_groups');
        $queryBuilder = $beGroupsConnection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('uid', 'explicit_allowdeny')
            ->from('be_groups')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->notLike(
                        'explicit_allowdeny',
                        $queryBuilder->createNamedParameter('%tt_content:CType:textmedia:ALLOW%', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->like(
                            'explicit_allowdeny',
                            $queryBuilder->createNamedParameter('%tt_content:CType:textpic:ALLOW%', \PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->like(
                            'explicit_allowdeny',
                            $queryBuilder->createNamedParameter('%tt_content:CType:image:ALLOW%', \PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->like(
                            'explicit_allowdeny',
                            $queryBuilder->createNamedParameter('%tt_content:CType:text:ALLOW%', \PDO::PARAM_STR)
                        )
                    )
                )
            )->execute();
        while ($beGroupsRow = $statement->fetch()) {
            $queryBuilder = $beGroupsConnection->createQueryBuilder();
            $queryBuilder->update('be_groups')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($beGroupsRow['uid'], \PDO::PARAM_INT)
                    )
                )
                ->set('explicit_allowdeny', $beGroupsRow['explicit_allowdeny'] . ',tt_content:CType:textmedia:ALLOW');
            $databaseQueries[] = $queryBuilder->getSQL();
            $queryBuilder->execute();
        }

        // Update explicitDeny - DENY
        $queryBuilder = $beGroupsConnection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('uid', 'explicit_allowdeny')
            ->from('be_groups')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->notLike(
                        'explicit_allowdeny',
                        $queryBuilder->createNamedParameter('%tt_content:CType:textmedia:DENY%', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->like(
                            'explicit_allowdeny',
                            $queryBuilder->createNamedParameter('%tt_content:CType:textpic:DENY%', \PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->like(
                            'explicit_allowdeny',
                            $queryBuilder->createNamedParameter('%tt_content:CType:image:DENY%', \PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->like(
                            'explicit_allowdeny',
                            $queryBuilder->createNamedParameter('%tt_content:CType:text:DENY%', \PDO::PARAM_STR)
                        )
                    )
                )
            )->execute();
        while ($beGroupsRow = $statement->fetch()) {
            $queryBuilder = $beGroupsConnection->createQueryBuilder();
            $queryBuilder->update('be_groups')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($beGroupsRow['uid'], \PDO::PARAM_INT)
                    )
                )
                ->set('explicit_allowdeny', $beGroupsRow['explicit_allowdeny'] . ',tt_content:CType:textmedia:DENY');
            $databaseQueries[] = $queryBuilder->getSQL();
            $queryBuilder->execute();
        }

        $this->markWizardAsDone();

        return true;
    }
}
