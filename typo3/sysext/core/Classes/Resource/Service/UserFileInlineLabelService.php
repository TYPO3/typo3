<?php

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

namespace TYPO3\CMS\Core\Resource\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * User file inline label service
 */
class UserFileInlineLabelService
{
    /**
     * Get the user function label for the file_reference table
     *
     * @param array $params
     */
    public function getInlineLabel(array &$params)
    {
        $sysFileFields = isset($params['options']['sys_file']) && is_array($params['options']['sys_file'])
            ? $params['options']['sys_file']
            : [];

        if (empty($sysFileFields)) {
            // Nothing to do
            $params['title'] = $params['row']['uid'];
            return;
        }

        // In case of a group field uid_local is a resolved array
        $fileRecord = $params['row']['uid_local'][0]['row'];

        // Configuration
        $title = '';
        foreach ($sysFileFields as $field) {
            $value = '';
            if ($field === 'title') {
                if (isset($params['row']['title'])) {
                    $fullTitle = $params['row']['title'];
                } else {
                    try {
                        $metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
                        $metaData = $metaDataRepository->findByFileUid($fileRecord['uid']);
                        $fullTitle = $metaData['title'] ?? '';
                    } catch (InvalidUidException $e) {
                        /**
                         * We just catch the exception here
                         * Reasoning: There is nothing an editor or even admin could do
                         */
                        $fullTitle = '';
                    }
                }

                $value = BackendUtility::getRecordTitlePrep($fullTitle);
            } else {
                if (isset($params['row'][$field])) {
                    $value = htmlspecialchars($params['row'][$field]);
                } elseif (isset($fileRecord[$field])) {
                    $value = BackendUtility::getRecordTitlePrep($fileRecord[$field]);
                }
            }
            if ((string)$value === '') {
                continue;
            }
            $labelText = (string)LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.' . $field);
            $title = '<dt class="col text-truncate">' . htmlspecialchars($labelText) . '</dt><dd class="col">' . $value . '</dd>';
            // In debug mode, add the table name to the record title
            if ($this->getBackendUserAuthentication()->shallDisplayDebugInformation()) {
                $title .= '<div class="col"><code class="m-0">[' . htmlspecialchars($params['table']) . ']</code></div>';
            }
        }
        $params['title'] = '<dl class="row row-cols-auto g-2">' . $title . '</dl>';
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
