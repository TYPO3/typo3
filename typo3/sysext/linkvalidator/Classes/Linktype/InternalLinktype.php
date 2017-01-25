<?php
namespace TYPO3\CMS\Linkvalidator\Linktype;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides Check Internal Links plugin implementation
 */
class InternalLinktype extends AbstractLinktype
{
    /**
     * @var string
     */
    const DELETED = 'deleted';

    /**
     * @var string
     */
    const HIDDEN = 'hidden';

    /**
     * @var string
     */
    const MOVED = 'moved';

    /**
     * @var string
     */
    const NOTEXISTING = 'notExisting';

    /**
     * Result of the check, if the current page uid is valid or not
     *
     * @var bool
     */
    protected $responsePage = true;

    /**
     * Result of the check, if the current content uid is valid or not
     *
     * @var bool
     */
    protected $responseContent = true;

    /**
     * Checks a given URL + /path/filename.ext for validity
     *
     * @param string $url Url to check as page-id or page-id#anchor (if anchor is present)
     * @param array $softRefEntry: The soft reference entry which builds the context of that url
     * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $reference Parent instance
     * @return bool TRUE on success or FALSE on error
     */
    public function checkLink($url, $softRefEntry, $reference)
    {
        $anchor = '';
        $this->responseContent = true;
        // Might already contain values - empty it
        unset($this->errorParams);
        // Only check pages records. Content elements will also be checked
        // as we extract the anchor in the next step.
        if (strpos($softRefEntry['substr']['recordRef'], 'pages:') !== 0) {
            return true;
        }
        // Defines the linked page and anchor (if any).
        if (strpos($url, '#c') !== false) {
            $parts = explode('#c', $url);
            $page = $parts[0];
            $anchor = $parts[1];
        } else {
            $page = $url;
        }
        // Check if the linked page is OK
        $this->responsePage = $this->checkPage($page);
        // Check if the linked content element is OK
        if ($anchor) {
            // Check if the content element is OK
            $this->responseContent = $this->checkContent($page, $anchor);
        }
        if (
            is_array($this->errorParams['page']) && !$this->responsePage
            || is_array($this->errorParams['content']) && !$this->responseContent
        ) {
            $this->setErrorParams($this->errorParams);
        }

        return $this->responsePage && $this->responseContent;
    }

    /**
     * Checks a given page uid for validity
     *
     * @param string $page Page uid to check
     * @return bool TRUE on success or FALSE on error
     */
    protected function checkPage($page)
    {
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid, title, deleted, hidden, starttime, endtime', 'pages', 'uid = ' . (int)$page);
        $this->responsePage = true;
        if ($row) {
            if ($row['deleted'] == '1') {
                $this->errorParams['errorType']['page'] = self::DELETED;
                $this->errorParams['page']['title'] = $row['title'];
                $this->errorParams['page']['uid'] = $row['uid'];
                $this->responsePage = false;
            } elseif ($row['hidden'] == '1' || $GLOBALS['EXEC_TIME'] < (int)$row['starttime'] || $row['endtime'] && (int)$row['endtime'] < $GLOBALS['EXEC_TIME']) {
                $this->errorParams['errorType']['page'] = self::HIDDEN;
                $this->errorParams['page']['title'] = $row['title'];
                $this->errorParams['page']['uid'] = $row['uid'];
                $this->responsePage = false;
            }
        } else {
            $this->errorParams['errorType']['page'] = self::NOTEXISTING;
            $this->errorParams['page']['uid'] = (int)$page;
            $this->responsePage = false;
        }
        return $this->responsePage;
    }

    /**
     * Checks a given content uid for validity
     *
     * @param string $page Uid of the page to which the link is pointing
     * @param string $anchor Uid of the content element to check
     * @return bool TRUE on success or FALSE on error
     */
    protected function checkContent($page, $anchor)
    {
        // Get page ID on which the content element in fact is located
        $res = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid, pid, header, deleted, hidden, starttime, endtime',
            'tt_content',
            'uid = ' . (int)$anchor
        );
        $this->responseContent = true;
        // this content element exists
        if ($res) {
            // page ID on which this CE is in fact located.
            $correctPageID = $res['pid'];
            // Check if the element is on the linked page
            // (The element might have been moved to another page)
            if (!($correctPageID === $page)) {
                $this->errorParams['errorType']['content'] = self::MOVED;
                $this->errorParams['content']['uid'] = (int)$anchor;
                $this->errorParams['content']['wrongPage'] = (int)$page;
                $this->errorParams['content']['rightPage'] = (int)$correctPageID;
                $this->responseContent = false;
            } else {
                // The element is located on the page to which the link is pointing
                if ($res['deleted'] == '1') {
                    $this->errorParams['errorType']['content'] = self::DELETED;
                    $this->errorParams['content']['title'] = $res['header'];
                    $this->errorParams['content']['uid'] = $res['uid'];
                    $this->responseContent = false;
                } elseif ($res['hidden'] == '1' || $GLOBALS['EXEC_TIME'] < (int)$res['starttime'] || $res['endtime'] && (int)$res['endtime'] < $GLOBALS['EXEC_TIME']) {
                    $this->errorParams['errorType']['content'] = self::HIDDEN;
                    $this->errorParams['content']['title'] = $res['header'];
                    $this->errorParams['content']['uid'] = $res['uid'];
                    $this->responseContent = false;
                }
            }
        } else {
            // The content element does not exist
            $this->errorParams['errorType']['content'] = self::NOTEXISTING;
            $this->errorParams['content']['uid'] = (int)$anchor;
            $this->responseContent = false;
        }
        return $this->responseContent;
    }

    /**
     * Generates the localized error message from the error params saved from the parsing
     *
     * @param array $errorParams All parameters needed for the rendering of the error message
     * @return string Validation error message
     */
    public function getErrorMessage($errorParams)
    {
        $lang = $this->getLanguageService();
        $errorType = $errorParams['errorType'];
        if (is_array($errorParams['page'])) {
            switch ($errorType['page']) {
                case self::DELETED:
                    $errorPage = str_replace(
                        [
                            '###title###',
                            '###uid###'
                        ],
                        [
                            $errorParams['page']['title'],
                            $errorParams['page']['uid']
                        ],
                        $lang->getLL('list.report.pagedeleted')
                    );
                    break;
                case self::HIDDEN:
                    $errorPage = str_replace(
                        [
                            '###title###',
                            '###uid###'
                        ],
                        [
                            $errorParams['page']['title'],
                            $errorParams['page']['uid']
                        ],
                        $lang->getLL('list.report.pagenotvisible')
                    );
                    break;
                default:
                    $errorPage = str_replace(
                        '###uid###',
                        $errorParams['page']['uid'],
                        $lang->getLL('list.report.pagenotexisting')
                    );
            }
        }
        if (is_array($errorParams['content'])) {
            switch ($errorType['content']) {
                case self::DELETED:
                    $errorContent = str_replace(
                        [
                            '###title###',
                            '###uid###'
                        ],
                        [
                            $errorParams['content']['title'],
                            $errorParams['content']['uid']
                        ],
                        $lang->getLL('list.report.contentdeleted')
                    );
                    break;
                case self::HIDDEN:
                    $errorContent = str_replace(
                        [
                            '###title###',
                            '###uid###'
                        ],
                        [
                            $errorParams['content']['title'],
                            $errorParams['content']['uid']
                        ],
                        $lang->getLL('list.report.contentnotvisible')
                    );
                    break;
                case self::MOVED:
                    $errorContent = str_replace(
                        [
                            '###title###',
                            '###uid###',
                            '###wrongpage###',
                            '###rightpage###'
                        ],
                        [
                            $errorParams['content']['title'],
                            $errorParams['content']['uid'],
                            $errorParams['content']['wrongPage'],
                            $errorParams['content']['rightPage']
                        ],
                        $lang->getLL('list.report.contentmoved')
                    );
                    break;
                default:
                    $errorContent = str_replace('###uid###', $errorParams['content']['uid'], $lang->getLL('list.report.contentnotexisting'));
            }
        }
        if (isset($errorPage) && isset($errorContent)) {
            $response = $errorPage . LF . $errorContent;
        } elseif (isset($errorPage)) {
            $response = $errorPage;
        } elseif (isset($errorContent)) {
            $response = $errorContent;
        } else {
            // This should not happen
            $response = $lang->getLL('list.report.noinformation');
        }
        return $response;
    }

    /**
     * Constructs a valid Url for browser output
     *
     * @param array $row Broken link record
     * @return string Parsed broken url
     */
    public function getBrokenUrl($row)
    {
        $domain = rtrim(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), '/');
        $rootLine = BackendUtility::BEgetRootLine($row['record_pid']);
        // checks alternate domains
        if (!empty($rootLine)) {
            $protocol = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://';
            $domainRecord = BackendUtility::firstDomainRecord($rootLine);
            if (!empty($domainRecord)) {
                $domain = $protocol . $domainRecord;
            }
        }
        return $domain . '/index.php?id=' . $row['url'];
    }
}
