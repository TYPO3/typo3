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

namespace TYPO3\CMS\IndexedSearch\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Page browser for indexed search, and only useful here, as the
 * regular pagebrowser
 * so this is a cleaner "pi_browsebox" but not a real page browser
 * functionality
 * @internal
 */
class PageBrowsingViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected static $prefixId = 'tx_indexedsearch';

    /**
     * @var string
     */
    protected $tagName = 'ul';

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('maximumNumberOfResultPages', 'int', '', true);
        $this->registerArgument('numberOfResults', 'int', '', true);
        $this->registerArgument('resultsPerPage', 'int', '', true);
        $this->registerArgument('currentPage', 'int', '', false, 0);
        $this->registerArgument('freeIndexUid', 'int', '');
        $this->registerUniversalTagAttributes();
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $maximumNumberOfResultPages = $this->arguments['maximumNumberOfResultPages'];
        $numberOfResults = $this->arguments['numberOfResults'];
        $resultsPerPage = $this->arguments['resultsPerPage'];
        $currentPage = $this->arguments['currentPage'];
        $freeIndexUid = $this->arguments['freeIndexUid'];

        if ($resultsPerPage <= 0) {
            $resultsPerPage = 10;
        }
        $pageCount = (int)ceil($numberOfResults / $resultsPerPage);
        // only show the result browser if more than one page is needed
        if ($pageCount === 1) {
            return '';
        }

        // Check if $currentPage is in range
        $currentPage = MathUtility::forceIntegerInRange($currentPage, 0, $pageCount - 1);

        $content = '';
        // prev page
        // show on all pages after the 1st one
        if ($currentPage > 0) {
            $label = LocalizationUtility::translate('displayResults.previous', 'IndexedSearch') ?? '';
            $content .= '<li>' . $this->makecurrentPageSelector_link($label, $currentPage - 1, $freeIndexUid) . '</li>';
        }
        // Check if $maximumNumberOfResultPages is in range
        $maximumNumberOfResultPages = MathUtility::forceIntegerInRange($maximumNumberOfResultPages, 1, $pageCount, 10);
        // Assume $currentPage is in the middle and calculate the index limits of the result page listing
        $minPage = $currentPage - (int)floor($maximumNumberOfResultPages / 2);
        $maxPage = $minPage + $maximumNumberOfResultPages - 1;
        // Check if the indexes are within the page limits
        if ($minPage < 0) {
            $maxPage -= $minPage;
            $minPage = 0;
        } elseif ($maxPage >= $pageCount) {
            $minPage -= $maxPage - $pageCount + 1;
            $maxPage = $pageCount - 1;
        }
        $pageLabel = LocalizationUtility::translate('displayResults.page', 'IndexedSearch');
        for ($a = $minPage; $a <= $maxPage; $a++) {
            $label = trim($pageLabel . ' ' . ($a + 1));
            $label = $this->makecurrentPageSelector_link($label, $a, $freeIndexUid);
            if ($a === $currentPage) {
                $content .= '<li class="tx-indexedsearch-browselist-currentPage"><strong>' . $label . '</strong></li>';
            } else {
                $content .= '<li>' . $label . '</li>';
            }
        }
        // next link
        if ($currentPage < $pageCount - 1) {
            $label = LocalizationUtility::translate('displayResults.next', 'IndexedSearch') ?? '';
            $content .= '<li>' . $this->makecurrentPageSelector_link($label, $currentPage + 1, $freeIndexUid) . '</li>';
        }

        if (!$this->tag->hasAttribute('class')) {
            $this->tag->addAttribute('class', 'tx-indexedsearch-browsebox');
        }
        $this->tag->setContent($content);
        return $this->tag->render();
    }

    /**
     * Used to make the link for the result-browser.
     * Notice how the links must resubmit the form after setting the new currentPage-value in a hidden formfield.
     *
     * @param string $str String to wrap in <a> tag
     * @param int $p currentPage value
     * @param string $freeIndexUid List of integers pointing to free indexing configurations to search. -1 represents no filtering, 0 represents TYPO3 pages only, any number above zero is a uid of an indexing configuration!
     * @return string Input string wrapped in <a> tag with onclick event attribute set.
     */
    protected function makecurrentPageSelector_link($str, $p, $freeIndexUid)
    {
        $onclick = 'document.getElementById(' . GeneralUtility::quoteJSvalue(self::$prefixId . '_pointer') . ').value=' . GeneralUtility::quoteJSvalue((string)$p) . ';';
        if ($freeIndexUid !== null) {
            $onclick .= 'document.getElementById(' . GeneralUtility::quoteJSvalue(self::$prefixId . '_freeIndexUid') . ').value=' . GeneralUtility::quoteJSvalue($freeIndexUid) . ';';
        }
        $onclick .= 'document.getElementById(' . GeneralUtility::quoteJSvalue(self::$prefixId) . ').submit();return false;';
        return '<a href="#" onclick="' . htmlspecialchars($onclick) . '">' . htmlspecialchars($str) . '</a>';
    }
}
