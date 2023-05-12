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

namespace TYPO3\CMS\Impexp\Domain\Repository;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Impexp\Exception\InsufficientUserPermissionsException;
use TYPO3\CMS\Impexp\Exception\MalformedPresetException;
use TYPO3\CMS\Impexp\Exception\PresetNotFoundException;

/**
 * Export preset repository manages export presets.
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
final class PresetRepository
{
    protected const PRESET_TABLE = 'tx_impexp_presets';
    protected QueryBuilder $queryBuilder;
    protected Connection $connection;
    protected Context $context;

    public function __construct(
        ConnectionPool $connectionPool,
        Context $context
    ) {
        $this->queryBuilder = $connectionPool->getQueryBuilderForTable(self::PRESET_TABLE);
        $this->connection = $connectionPool->getConnectionForTable(self::PRESET_TABLE);
        $this->context = $context;
    }

    public function getPresets(int $pageId): array
    {
        $backendUser = $this->getBackendUser();
        $queryBuilder = $this->queryBuilder;
        $queryBuilder->select('*')
            ->from(self::PRESET_TABLE)
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->gt('public', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('user_uid', $queryBuilder->createNamedParameter($backendUser->user['uid'], Connection::PARAM_INT))
                )
            );
        if ($pageId) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('item_uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('item_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                )
            );
        }
        $presets = $queryBuilder->executeQuery();
        // @todo: View should handle default option and data details parsing, not repository.
        $options = [''];
        while ($presetCfg = $presets->fetchAssociative()) {
            $options[$presetCfg['uid']] = $presetCfg['title'] . ' [' . $presetCfg['uid'] . ']'
                . ($presetCfg['public'] ? ' [Public]' : '')
                . ((int)$presetCfg['user_uid'] === (int)$backendUser->user['uid'] ? ' [Own]' : '');
        }
        return $options;
    }

    public function createPreset(array $data): void
    {
        $backendUser = $this->getBackendUser();
        $timestamp = $this->context->getPropertyFromAspect('date', 'timestamp');
        $this->connection->insert(
            self::PRESET_TABLE,
            [
                'user_uid' => $backendUser->user['uid'],
                'public' => $data['preset']['public'],
                'title' => $data['preset']['title'],
                'item_uid' => (int)$data['pagetree']['id'],
                'preset_data' => serialize($data),
                'tstamp' => $timestamp,
                'crdate' => $timestamp,
            ],
            ['preset_data' => Connection::PARAM_LOB]
        );
    }

    /**
     * @throws InsufficientUserPermissionsException
     * @throws PresetNotFoundException
     */
    public function updatePreset(int $uid, array $data): void
    {
        $backendUser = $this->getBackendUser();
        $preset = $this->getPreset($uid);
        if (!($backendUser->isAdmin() || (int)$preset['user_uid'] === (int)$backendUser->user['uid'])) {
            throw new InsufficientUserPermissionsException(
                'ERROR: You were not the owner of the preset so you could not delete it.',
                1604584766
            );
        }
        $timestamp = $this->context->getPropertyFromAspect('date', 'timestamp');
        $this->connection->update(
            self::PRESET_TABLE,
            [
                'public' => $data['preset']['public'],
                'title' => $data['preset']['title'],
                'item_uid' => $data['pagetree']['id'],
                'preset_data' => serialize($data),
                'tstamp' => $timestamp,
            ],
            ['uid' => $uid],
            ['preset_data' => Connection::PARAM_LOB]
        );
    }

    /**
     * @throws MalformedPresetException
     */
    public function loadPreset(int $uid): array
    {
        $preset = $this->getPreset($uid);
        // @todo: Move this to a json array instead.
        $presetData = unserialize($preset['preset_data'], ['allowed_classes' => false]);
        if (!is_array($presetData)) {
            throw new MalformedPresetException(
                'ERROR: No configuration data found in preset record!',
                1604608922
            );
        }
        return $presetData;
    }

    /**
     * @throws InsufficientUserPermissionsException
     * @throws PresetNotFoundException
     */
    public function deletePreset(int $uid): void
    {
        $backendUser = $this->getBackendUser();
        $preset = $this->getPreset($uid);
        if (!($backendUser->isAdmin() || (int)$preset['user_uid'] === (int)$backendUser->user['uid'])) {
            throw new InsufficientUserPermissionsException(
                'ERROR: You were not the owner of the preset so you could not delete it.',
                1604564346
            );
        }
        $this->connection->delete(
            self::PRESET_TABLE,
            ['uid' => $uid]
        );
    }

    /**
     * Get raw preset from database. Does not unserialize preset_data as opposed to public loadPreset().
     *
     * @throws PresetNotFoundException
     */
    protected function getPreset(int $uid): array
    {
        $queryBuilder = $this->queryBuilder;
        $preset = $queryBuilder->select('*')
            ->from(self::PRESET_TABLE)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        if (!is_array($preset)) {
            throw new PresetNotFoundException(
                'ERROR: No valid preset #' . $uid . ' found.',
                1604608843
            );
        }
        return $preset;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
