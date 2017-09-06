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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Write out system maintainer list to LocalConfiguration
 */
class SystemMaintainerWrite extends AbstractAjaxAction
{
    /**
     * Write system maintainer list
     *
     * @return array
     */
    protected function executeAction(): array
    {
        // Sanitize given user list and write out
        $newUserList = [];
        if (isset($this->postValues['users']) && is_array($this->postValues['users'])) {
            foreach ($this->postValues['users'] as $uid) {
                if (MathUtility::canBeInterpretedAsInteger($uid)) {
                    $newUserList[] = (int)$uid;
                }
            }
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $validatedUserList = $queryBuilder
            ->select('uid')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($newUserList, Connection::PARAM_INT_ARRAY))
                )
            )->execute()->fetchAll();

        $validatedUserList = array_column($validatedUserList, 'uid');

        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValuesByPathValuePairs(
            [ 'SYS/systemMaintainers' => $validatedUserList ]
        );

        $messages = [];
        if (empty($validatedUserList)) {
            $messages[] = new FlashMessage(
                '',
                'Set system maintainer list to an empty array',
                FlashMessage::INFO
            );
        } else {
            $messages[] = new FlashMessage(
                implode(', ', $validatedUserList),
                'New system maintainer uid list',
                FlashMessage::INFO
            );
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages
        ]);
        return $this->view->render();
    }
}
