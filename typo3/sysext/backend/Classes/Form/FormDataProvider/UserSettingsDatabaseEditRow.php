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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\UserSettingsSchema;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FormDataProvider for backend user settings.
 *
 * Loads user data from BE_USER->user and BE_USER->uc into databaseRow
 * for the user settings form.
 *
 * @internal
 */
readonly class UserSettingsDatabaseEditRow implements FormDataProviderInterface
{
    public function __construct(private UserSettingsSchema $userSettingsSchema) {}

    public function addData(array $result): array
    {
        if ($result['command'] !== 'edit' || $result['tableName'] !== 'be_users_settings') {
            return $result;
        }

        $backendUser = $this->getBackendUser();
        $userSettings = $backendUser->getUserSettings()->toArray();

        $userSettingsColumns = $this->userSettingsSchema->getColumns();
        $jsonFieldSettingKeys = $this->userSettingsSchema->getJsonFieldSettingKeys();
        // Also provide direct access to be_users fields that are shown in the form
        // These are needed for fields with inheritFromParent=true
        foreach ($userSettingsColumns as $column => $config) {
            $partitionedColumnName = $this->userSettingsSchema->getTcaFieldName($column);
            if (isset($backendUser->user[$column])) {
                $result['databaseRow'][$partitionedColumnName] = $backendUser->user[$column];
            } elseif (isset($userSettings[$column])) {
                $result['databaseRow'][$partitionedColumnName] = $userSettings[$column];
            }
        }
        // Set the uid from the current user
        $result['databaseRow']['uid'] = (int)$backendUser->user['uid'];
        $result['databaseRow']['pid'] = 0;
        // Fill in random to passwords to avoid FormEngine issuing the required field error
        $randomPassword = bin2hex(random_bytes(20));
        $passwordFieldName = $this->userSettingsSchema->getTcaFieldName('password');
        $result['databaseRow'][$passwordFieldName] = $randomPassword;
        $passwordConfirmationFieldName = $this->userSettingsSchema->getTcaFieldName('password2');
        $result['databaseRow'][$passwordConfirmationFieldName] = $randomPassword;
        // Forward the avatar FAL id
        $avatarFieldName = $this->userSettingsSchema->getTcaFieldName('avatar');
        $result['databaseRow'][$avatarFieldName] = $this->getAvatarFileUid((int)$backendUser->user['uid']);

        return $result;
    }

    protected function getAvatarFileUid(int $beUserId): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $file = $queryBuilder->select('uid_local')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('be_users')
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('avatar')
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($beUserId, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();

        return (int)$file;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
