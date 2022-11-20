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

namespace TYPO3\CMS\SysNote\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Sys_note repository
 *
 * @internal
 */
class SysNoteRepository
{
    public const SYS_NOTE_POSITION_BOTTOM = 0;
    public const SYS_NOTE_POSITION_TOP = 1;

    public function __construct(protected readonly ConnectionPool $connectionPool)
    {
    }

    /**
     * Find notes by given pid and author
     *
     * @param int $pid Single pid
     * @param int $author Author uid
     * @param int|null $position null for no restriction, integer for defined position
     */
    public function findByPidAndAuthorId(int $pid, int $author, int $position = null): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_note');
        $queryBuilder->getRestrictions()->removeAll();
        $res = $queryBuilder
            ->select(
                'sys_note.*',
                'be_users.username AS authorUsername',
                'be_users.realName AS authorRealName',
                'be_users.disable AS authorDisabled',
                'be_users.deleted AS authorDeleted'
            )
            ->from('sys_note')
            ->leftJoin(
                'sys_note',
                'be_users',
                'be_users',
                $queryBuilder->expr()->eq('sys_note.cruser', $queryBuilder->quoteIdentifier('be_users.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq('sys_note.deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_note.pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('sys_note.personal', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_note.cruser', $queryBuilder->createNamedParameter($author, Connection::PARAM_INT))
                )
            )
            ->orderBy('sorting', 'asc')
            ->addOrderBy('crdate', 'desc');

        if ($position !== null) {
            $res->andWhere(
                $queryBuilder->expr()->eq('sys_note.position', $queryBuilder->createNamedParameter($position, Connection::PARAM_INT))
            );
        }

        return $res->executeQuery()->fetchAllAssociative();
    }
}
