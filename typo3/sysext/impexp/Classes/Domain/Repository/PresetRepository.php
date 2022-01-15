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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Exception\InsufficientUserPermissionsException;
use TYPO3\CMS\Impexp\Exception\MalformedPresetException;
use TYPO3\CMS\Impexp\Exception\PresetNotFoundException;

/**
 * Export preset repository
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
class PresetRepository
{
    /**
     * @var string
     */
    protected $table = 'tx_impexp_presets';

    /**
     * @param int $pageId
     * @return array
     */
    public function getPresets(int $pageId): array
    {
        $queryBuilder = $this->createQueryBuilder();

        $queryBuilder->select('*')
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->gt(
                        'public',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'user_uid',
                        $queryBuilder->createNamedParameter($this->getBackendUser()->user['uid'], \PDO::PARAM_INT)
                    )
                )
            );

        if ($pageId) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(
                        'item_uid',
                        $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'item_uid',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            );
        }

        $presets = $queryBuilder->executeQuery();

        $options = [''];
        while ($presetCfg = $presets->fetchAssociative()) {
            $options[$presetCfg['uid']] = $presetCfg['title'] . ' [' . $presetCfg['uid'] . ']'
                . ($presetCfg['public'] ? ' [Public]' : '')
                . ($presetCfg['user_uid'] === $this->getBackendUser()->user['uid'] ? ' [Own]' : '');
        }
        return $options;
    }

    /**
     * Get single preset record
     *
     * @param int $uid Preset record
     * @throws PresetNotFoundException
     * @return array Preset record
     */
    protected function getPreset(int $uid): array
    {
        $queryBuilder = $this->createQueryBuilder();

        $preset = $queryBuilder->select('*')
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
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

    public function createPreset(array $data): void
    {
        $timestamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
        $connection = $this->createConnection();
        $connection->insert(
            $this->table,
            [
                'user_uid' => $this->getBackendUser()->user['uid'],
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
     * @param int $uid
     * @param array $data
     * @throws InsufficientUserPermissionsException
     */
    public function updatePreset(int $uid, array $data): void
    {
        $preset = $this->getPreset($uid);

        if (!($this->getBackendUser()->isAdmin() || $preset['user_uid'] === $this->getBackendUser()->user['uid'])) {
            throw new InsufficientUserPermissionsException(
                'ERROR: You were not the owner of the preset so you could not delete it.',
                1604584766
            );
        }

        $timestamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');

        $connection = $this->createConnection();
        $connection->update(
            $this->table,
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
     * @param int $uid
     * @throws MalformedPresetException
     * @return array
     */
    public function loadPreset(int $uid): array
    {
        $preset = $this->getPreset($uid);

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
     * @param int $uid
     * @throws InsufficientUserPermissionsException
     */
    public function deletePreset(int $uid): void
    {
        $preset = $this->getPreset($uid);

        if (!($this->getBackendUser()->isAdmin() || $preset['user_uid'] === $this->getBackendUser()->user['uid'])) {
            throw new InsufficientUserPermissionsException(
                'ERROR: You were not the owner of the preset so you could not delete it.',
                1604564346
            );
        }

        $connection = $this->createConnection();
        $connection->delete(
            $this->table,
            ['uid' => $uid]
        );
    }

    /**
     * @return Connection
     */
    protected function createConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
    }

    /**
     * @return QueryBuilder
     */
    protected function createQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
