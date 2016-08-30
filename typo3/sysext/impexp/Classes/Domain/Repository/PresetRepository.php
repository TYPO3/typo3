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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handling of presets
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
        $where = '(public>0 OR user_uid=' . (int)$this->getBackendUser()->user['uid'] . ')'
            . ($pageId ? ' AND (item_uid=' . (int)$pageId . ' OR item_uid=0)' : '');
        $presets = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'tx_impexp_presets', $where);
        if (is_array($presets)) {
            foreach ($presets as $presetCfg) {
                $options[$presetCfg['uid']] = $presetCfg['title'] . ' [' . $presetCfg['uid'] . ']'
                    . ($presetCfg['public'] ? ' [Public]' : '')
                    . ($presetCfg['user_uid'] === $this->getBackendUser()->user['uid'] ? ' [Own]' : '');
            }
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
        return $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'tx_impexp_presets', 'uid=' . (int)$uid);
    }

    /**
     * Manipulate presets
     *
     * @param array $inData In data array, passed by reference!
     * @return void
     */
    public function processPresets(&$inData)
    {
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
                    $fields_values = [
                        'public' => $inData['preset']['public'],
                        'title' => $inData['preset']['title'],
                        'item_uid' => $inData['pagetree']['id'],
                        'preset_data' => serialize($inData)
                    ];
                    $this->getDatabaseConnection()->exec_UPDATEquery('tx_impexp_presets', 'uid=' . (int)$preset['uid'], $fields_values);
                    $msg = 'Preset #' . $preset['uid'] . ' saved!';
                } else {
                    $msg = 'ERROR: The preset was not saved because you were not the owner of it!';
                    $err = true;
                }
            } else {
                // Insert new:
                $fields_values = [
                    'user_uid' => $beUser->user['uid'],
                    'public' => $inData['preset']['public'],
                    'title' => $inData['preset']['title'],
                    'item_uid' => (int)$inData['pagetree']['id'],
                    'preset_data' => serialize($inData)
                ];
                $this->getDatabaseConnection()->exec_INSERTquery('tx_impexp_presets', $fields_values);
                $msg = 'New preset "' . htmlspecialchars($inData['preset']['title']) . '" is created';
            }
        }
        // Delete preset:
        if (isset($presetData['delete'])) {
            $preset = $this->getPreset($presetData['select']);
            if (is_array($preset)) {
                // Update existing
                if ($beUser->isAdmin() || $preset['user_uid'] === $beUser->user['uid']) {
                    $this->getDatabaseConnection()->exec_DELETEquery('tx_impexp_presets', 'uid=' . (int)$preset['uid']);
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
                $inData_temp = unserialize($preset['preset_data']);
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
            /** @var $flashMessageService FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue FlashMessageQueue */
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

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
