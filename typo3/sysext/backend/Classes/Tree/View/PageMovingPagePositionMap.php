<?php
namespace TYPO3\CMS\Backend\Tree\View;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Position map class for moving pages,
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class PageMovingPagePositionMap extends PagePositionMap
{
    /**
     * @var string
     */
    public $l_insertNewPageHere = 'movePageToHere';

    /**
     * Page tree implementation class name
     *
     * @var string
     */
    protected $pageTreeClassName = PageTreeView::class;

    /**
     * Creates the onclick event for the insert-icons.
     *
     * @param int $pid The pid.
     * @param int $newPagePID New page id.
     * @return string Onclick attribute content
     */
    public function onClickEvent($pid, $newPagePID)
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return 'window.location.href=' . GeneralUtility::quoteJSvalue((string)$uriBuilder->buildUriFromRoute('tce_db', [
            'cmd[pages][' . $this->moveUid . '][' . $this->moveOrCopy . ']' => $pid,
            'redirect' => $this->R_URI,
        ])) . ';return false;';
    }

    /**
     * Wrapping page title.
     *
     * @param string $str Page title.
     * @param array $rec Page record (?)
     * @return string Wrapped title.
     */
    public function linkPageTitle($str, $rec)
    {
        $url = GeneralUtility::linkThisScript(['uid' => (int)$rec['uid'], 'moveUid' => $this->moveUid]);
        return '<a href="' . htmlspecialchars($url) . '">' . $str . '</a>';
    }

    /**
     * Wrap $t_code in bold IF the $dat uid matches $id
     *
     * @param string $t_code Title string
     * @param array $dat Information array with record array inside.
     * @param int $id The current id.
     * @return string The title string.
     */
    public function boldTitle($t_code, $dat, $id)
    {
        return parent::boldTitle($t_code, $dat, $this->moveUid);
    }
}
