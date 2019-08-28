<?php
namespace TYPO3\CMS\Impexp\Domain\Repository;

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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handling of presets
 * @internal this is not part of TYPO3's Core API.
 */
class PresetRepository
{
    /**
     * @param int $pageId
     * @return array
     */
    public function getPresets($pageId)
    {
        $options = [''];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_impexp_presets');

        $queryBuilder->select('*')
            ->from('tx_impexp_presets')
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

        $presets = $queryBuilder->execute();
        while ($presetCfg = $presets->fetch()) {
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
     * @return array Preset record, if any (otherwise FALSE)
     */
    public function getPreset($uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_impexp_presets');

        return $queryBuilder->select('*')
            ->from('tx_impexp_presets')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
    }

    /**
     * Manipulate presets
     *
     * @param array $inData In data array, passed by reference!
     */
    public function processPresets(&$inData)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_impexp_presets');
        $presetData = GeneralUtility::_GP('preset');
        $err = false;
        $msg = '';
        // Save preset
        $beUser = $this->getBackendUser();
        // cast public checkbox to int, since this is an int field and NULL is not allowed
        $inData['preset']['public'] = (int)$inData['preset']['public'];
        if (isset($presetData['save'])) {
            $preset = $this->getPreset($presetData['select']);
            // Update existing
            if (is_array($preset)) {
                if ($beUser->isAdmin() || $preset['user_uid'] === $beUser->user['uid']) {
                    $connection->update(
                        'tx_impexp_presets',
                        [
                            'public' => $inData['preset']['public'],
                            'title' => $inData['preset']['title'],
                            'item_uid' => $inData['pagetree']['id'],
                            'preset_data' => serialize($inData)
                        ],
                        ['uid' => (int)$preset['uid']],
                        ['preset_data' => Connection::PARAM_LOB]
                    );

                    $msg = 'Preset #' . $preset['uid'] . ' saved!';
                } else {
                    $msg = 'ERROR: The preset was not saved because you were not the owner of it!';
                    $err = true;
                }
            } else {
                // Insert new:
                $connection->insert(
                    'tx_impexp_presets',
                    [
                        'user_uid' => $beUser->user['uid'],
                        'public' => $inData['preset']['public'],
                        'title' => $inData['preset']['title'],
                        'item_uid' => (int)$inData['pagetree']['id'],
                        'preset_data' => serialize($inData)
                    ],
                    ['preset_data' => Connection::PARAM_LOB]
                );

                $msg = 'New preset "' . htmlspecialchars($inData['preset']['title']) . '" is created';
            }
        }
        // Delete preset:
        if (isset($presetData['delete'])) {
            $preset = $this->getPreset($presetData['select']);
            if (is_array($preset)) {
                // Update existing
                if ($beUser->isAdmin() || $preset['user_uid'] === $beUser->user['uid']) {
                    $connection->delete(
                        'tx_impexp_presets',
                        ['uid' => (int)$preset['uid']]
                    );

                    $msg = 'Preset #' . $preset['uid'] . ' deleted!';
                } else {
                    $msg = 'ERROR: You were not the owner of the preset so you could not delete it.';
                    $err = true;
                }
            } else {
                $msg = 'ERROR: No preset selected for deletion.';
                $err = true;
            }
        }
        // Load preset
        if (isset($presetData['load']) || isset($presetData['merge'])) {
            $preset = $this->getPreset($presetData['select']);
            if (is_array($preset)) {
                // Update existing
                $inData_temp = unserialize($preset['preset_data'], ['allowed_classes' => false]);
                if (is_array($inData_temp)) {
                    if (isset($presetData['merge'])) {
                        // Merge records in:
                        if (is_array($inData_temp['record'])) {
                            $inData['record'] = array_merge((array)$inData['record'], $inData_temp['record']);
                        }
                        // Merge lists in:
                        if (is_array($inData_temp['list'])) {
                            $inData['list'] = array_merge((array)$inData['list'], $inData_temp['list']);
                        }
                    } else {
                        $msg = 'Preset #' . $preset['uid'] . ' loaded!';
                        $inData = $inData_temp;
                    }
                } else {
                    $msg = 'ERROR: No configuratio data found in preset record!';
                    $err = true;
                }
            } else {
                $msg = 'ERROR: No preset selected for loading.';
                $err = true;
            }
        }
        // Show message:
        if ($msg !== '') {
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                'Presets',
                $msg,
                $err ? FlashMessage::ERROR : FlashMessage::INFO
            );
            /** @var FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
