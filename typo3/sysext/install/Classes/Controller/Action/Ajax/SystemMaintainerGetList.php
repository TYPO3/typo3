<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Get list of backend admin users with information if they are system maintainers
 */
class SystemMaintainerGetList extends AbstractAjaxAction
{
    /**
     * Get backend admin user list
     *
     * @return array
     */
    protected function executeAction(): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        // We have to respect the enable fields here by our own because no TCA is loaded in standalone mode
        $queryBuilder = $connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();

        $users = $queryBuilder
            ->select('uid', 'username', 'disable', 'starttime', 'endtime')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
                )
            )
            ->orderBy('uid')
            ->execute()
            ->fetchAll();

        $systemMaintainerList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? [];
        $currentTime = time();
        foreach ($users as &$user) {
            $user['disable'] = $user['disable'] ||
                ((int)$user['starttime'] !== 0 && $user['starttime'] > $currentTime) ||
                ((int)$user['endtime'] !== 0 && $user['endtime'] < $currentTime);
            $user['isSystemMaintainer'] = in_array((int)$user['uid'], $systemMaintainerList, true);
        }
        $this->view->assignMultiple([
            'success' => true,
            'status' => [],
            'users' => $users,
        ]);
        return $this->view->render();
    }
}
