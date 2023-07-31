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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $fileRecord = $params['row']['uid_local'][0]['row'] ?? null;

        if ($fileRecord === null) {
            // no file record so nothing more to do
            $params['title'] = $params['row']['uid'];
            return;
        }

        // Configuration
        $value = '';
        $recordTitle = $this->getTitleForRecord($params['row'], $fileRecord);
        $recordName = $this->getLabelFieldForRecord($params['row'], $fileRecord, 'name');

        $labelField = !empty($recordTitle) ? 'title' : 'name';

        if (!empty($recordTitle)) {
            $value .= $recordTitle . ' (' . $recordName . ')';
        } else {
            $value .= $recordName;
        }

        $title = '
            <dt class="col-1">
                ' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.' . $labelField)) . '
            </dt>
            <dd class="col text-truncate">
                ' . $value . '
            </dd>
            <div class="w-100"></div>';

        // In debug mode, add the table name to the record title
        if ($this->getBackendUserAuthentication()->shallDisplayDebugInformation()) {
            $title .= '<div class="col"><code class="m-0">[' . htmlspecialchars($params['table']) . ']</code></div>';
        }

        $params['title'] = '<dl class="row row-cols-auto">' . $title . '</dl>';
    }

    protected function getTitleForRecord(array $databaseRow, array $fileRecord): string
    {
        $fullTitle = '';
        if (isset($databaseRow['title'])) {
            $fullTitle = $databaseRow['title'];
        } else {
            try {
                $metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
                $metaData = $metaDataRepository->findByFileUid($fileRecord['uid']);
                $fullTitle = $metaData['title'] ?? '';
            } catch (InvalidUidException $e) {
            }
        }

        return BackendUtility::getRecordTitlePrep($fullTitle);
    }

    protected function getLabelFieldForRecord(array $databaseRow, array $fileRecord, string $field): string
    {
        $value = '';
        if (isset($databaseRow[$field])) {
            $value = htmlspecialchars($databaseRow[$field]);
        } elseif (isset($fileRecord[$field])) {
            $value = BackendUtility::getRecordTitlePrep($fileRecord[$field]);
        }

        return $value;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
