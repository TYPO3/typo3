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

namespace TYPO3\CMS\Backend\Tree\View;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Position map class for creating content elements
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class ContentCreationPagePositionMap extends AbstractContentPagePositionMap
{
    /**
     * Default values defined for the item
     */
    public array $defVals = [];

    /**
     * Whether the item should directly be persisted (avoiding FormEngine)
     */
    public bool $saveAndClose = false;

    /**
     * The return url, forwarded to FormEngine (or SimpleDataHandler)
     */
    public string $R_URI = '';

    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;

    public function __construct(IconFactory $iconFactory, UriBuilder $uriBuilder, BackendLayoutView $backendLayoutView)
    {
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
        parent::__construct($backendLayoutView);
    }

    /**
     * {@inheritdoc}
     */
    protected function insertPositionIcon(?array $row, int $colPos, int $pid): string
    {
        if ($this->saveAndClose) {
            $target = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                'data' => [
                    'tt_content' => [
                        StringUtility::getUniqueId('NEW') => array_replace($this->defVals, [
                            'colPos' => $colPos,
                            'pid' => (is_array($row) ? -$row['uid'] : $pid),
                            'sys_language_uid' => $this->cur_sys_language,
                        ]),
                    ],
                ],
                'redirect' => $this->R_URI,
            ]);
        } else {
            $target = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'tt_content' => [
                        (is_array($row) ? -$row['uid'] : $pid) => 'new',
                    ],
                ],
                'returnUrl' => $this->R_URI,
                'defVals' => [
                    'tt_content' => array_replace($this->defVals, [
                        'colPos' => $colPos,
                        'sys_language_uid' => $this->cur_sys_language,
                    ]),
                ],
            ]);
        }

        return '
            <button type="button"  class="btn btn-link p-0" data-target="' . htmlspecialchars($target) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:insertNewRecordHere')) . '">
                ' . $this->iconFactory->getIcon('actions-arrow-left', Icon::SIZE_SMALL)->render() . '
            </button>';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRecordHeader(array $row): string
    {
        return '
            <span ' . BackendUtility::getRecordToolTip($row, 'tt_content') . '>
                ' . $this->iconFactory->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL)->render() . '
            </span>' . BackendUtility::getRecordTitle('tt_content', $row, true);
    }
}
