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

namespace TYPO3\CMS\IndexedSearch\ViewHelpers\SearchResult;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to display relevancy / rating information about a search result record
 *
 * ```
 *   <is:searchResult.rating firstRow="{firstRow}" sortOrder="{searchParams.sortOrder}" row="{row}" />
 * ```
 *
 * @internal
 */
class RatingViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('row', 'array', '', true);
        $this->registerArgument('firstRow', 'array', '', true);
        $this->registerArgument('sortOrder', 'string', '', true);
    }

    public function render(): string
    {
        $row = $this->arguments['row'];
        $firstRow = $this->arguments['firstRow'];
        $default = ' ';
        switch ($this->arguments['sortOrder']) {
            case 'rank_count':
                return $row['order_val'] . ' ' . LocalizationUtility::translate('result.ratingMatches', 'IndexedSearch');
            case 'rank_first':
                return ceil(MathUtility::forceIntegerInRange(255 - $row['order_val'], 1, 255) / 255 * 100) . '%';
            case 'rank_flag':
                if ($firstRow['order_val2'] ?? 0) {
                    // (3 MSB bit, 224 is highest value of order_val1 currently)
                    $base = $row['order_val1'] * 256;
                    // 15-3 MSB = 12
                    $freqNumber = $row['order_val2'] / $firstRow['order_val2'] * 2 ** 12;
                    $total = MathUtility::forceIntegerInRange($base + $freqNumber, 0, 32767);
                    return ceil(log($total) / log(32767) * 100) . '%';
                }
                return $default;
            case 'rank_freq':
                $max = 10000;
                $total = MathUtility::forceIntegerInRange($row['order_val'], 0, $max);
                return ceil(log($total) / log($max) * 100) . '%';
            case 'crdate':
                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                return $cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_crdate']);
            case 'mtime':
                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                return $cObj->calcAge($GLOBALS['EXEC_TIME'] - $row['item_mtime']);
            default:
                return $default;
        }
    }

}
