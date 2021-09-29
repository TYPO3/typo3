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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Position map class for moving content elements
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class ContentMovingPagePositionMap extends AbstractContentPagePositionMap
{
    /**
     * The move uid
     */
    public int $moveUid = 0;

    /**
     * The copy mode (either "move" or "copy")
     */
    public string $copyMode = 'move';

    /**
     * The return url, forwarded SimpleDataHandler
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
        if (is_array($row)) {
            $location = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                'cmd[tt_content][' . $this->moveUid . '][' . $this->copyMode . ']' => '-' . $row['uid'],
                'redirect' => $this->R_URI,
            ]);
        } else {
            $location = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                'cmd[tt_content][' . $this->moveUid . '][' . $this->copyMode . ']' => $pid,
                'data[tt_content][' . $this->moveUid . '][colPos]' => $colPos,
                'redirect' => $this->R_URI,
            ]);
        }
        return '
            <a href="' . htmlspecialchars($location) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:insertNewRecordHere')) . '">
                ' . $this->iconFactory->getIcon('actions-arrow-left', Icon::SIZE_SMALL)->render() . '
            </a>';
    }

    /**
     * Create record header (includes the record icon, record title etc.)
     *
     * @param array $row Record row.
     * @return string HTML
     */
    protected function getRecordHeader(array $row): string
    {
        $linkContent = '
            <span ' . BackendUtility::getRecordToolTip($row, 'tt_content') . '>
                ' . $this->iconFactory->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL)->render() . '
            </span>' . BackendUtility::getRecordTitle('tt_content', $row, true);

        if ($this->moveUid === (int)$row['uid']) {
            $linkContent = '<strong>' . $linkContent . '</strong>';
        }

        return '
            <a href="' . htmlspecialchars(GeneralUtility::linkThisScript(['uid' => (int)$row['uid'], 'moveUid' => ''])) . '">
                ' . $linkContent . '
            </a>';
    }
}
