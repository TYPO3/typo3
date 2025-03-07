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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Position map class for moving content elements
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
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

    protected IconFactory $iconFactory;

    public function __construct(IconFactory $iconFactory, BackendLayoutView $backendLayoutView)
    {
        $this->iconFactory = $iconFactory;
        parent::__construct($backendLayoutView);
    }

    /**
     * {@inheritdoc}
     */
    protected function insertPositionIcon(?array $row, int $colPos, int $pid): string
    {
        if (is_array($row)) {
            $attributes = [
                'data-action' => 'paste',
                'data-position' => '-' . $row['uid'],
                'data-colpos' => $colPos,
            ];
        } else {
            $attributes = [
                'data-action' => 'paste',
                'data-position' => $pid,
                'data-colpos' => $colPos,
            ];
        }
        $buttonLabelTransUnit = $this->copyMode === 'move' ? 'moveElementToHere' : 'copyElementToHere';
        $buttonLabel = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:' . $buttonLabelTransUnit));
        return '
            <div class="page-position-action">
                <button class="btn btn-default" title="' . $buttonLabel . '" ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                    ' . $this->iconFactory->getIcon('actions-arrow-left-alt', IconSize::SMALL)->render() . ' <span class="t3js-button-label">' . $buttonLabel . '</span>
                </button>
            </div>';
    }

    /**
     * Create record header (includes the record icon, record title etc.)
     *
     * @param array $row Record row.
     * @return string HTML
     */
    protected function getRecordHeader(array $row): string
    {
        return '
            <div class="page-position-record">
                <span title="' . BackendUtility::getRecordIconAltText($row, 'tt_content') . '">
                    ' . $this->iconFactory->getIconForRecord('tt_content', $row, IconSize::SMALL)->render() . '
                    ' . ($this->moveUid === (int)$row['uid'] ? '<strong>' : '') . '
                    ' . BackendUtility::getRecordTitle('tt_content', $row, true) . '
                    ' . ($this->moveUid === (int)$row['uid'] ? '</strong>' : '') . '
                </span>
            </div>';
    }
}
