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

namespace TYPO3\CMS\Backend\Tree\View;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Position map class - generating a page tree / content element list which links for inserting (copy/move) of records.
 * Used for pages / tt_content element wizards of various kinds.
 *
 * Moving of Content to a certain position of a page happens in the ContentMovingPagePositionMap.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class PagePositionMap
{
    // EXTERNAL, static:
    /**
     * @var string
     */
    public $moveOrCopy = 'move';

    /**
     * @var int
     */
    public $dontPrintPageInsertIcons = 0;

    // How deep the position page tree will go.
    /**
     * @var int
     */
    public $depth = 2;

    // INTERNAL, dynamic:
    // Request uri
    /**
     * @var string
     */
    public $R_URI = '';

    // tt_content element uid to move.
    /**
     * @var int
     */
    public $moveUid;

    /**
     * @var array
     */
    public $checkNewPageCache = [];

    /**
     * Page tree implementation class name
     *
     * @var string
     */
    protected $pageTreeClassName = PageTreeView::class;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor allowing to set pageTreeImplementation
     *
     * @param string|null $pageTreeClassName
     */
    public function __construct(string $pageTreeClassName = null)
    {
        if ($pageTreeClassName !== null) {
            $this->pageTreeClassName = $pageTreeClassName;
        }
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /*************************************
     *
     * Page position map:
     *
     **************************************/
    /**
     * Creates a "position tree" based on the page tree.
     *
     * @param int $id Current page id
     * @param array $pageinfo Current page record.
     * @param string $perms_clause Page selection permission clause.
     * @param string $R_URI Current REQUEST_URI
     * @return string HTML code for the tree.
     */
    public function positionTree($id, $pageinfo, $perms_clause, $R_URI)
    {
        // Make page tree object
        if ($this->pageTreeClassName === NewRecordPageTreeView::class) {
            $pageTree = GeneralUtility::makeInstance($this->pageTreeClassName, (int)$id);
        } else {
            $pageTree = GeneralUtility::makeInstance($this->pageTreeClassName);
        }
        /** @var PageTreeView $pageTree */
        $pageTree->init(' AND ' . $perms_clause);
        $pageTree->addField('pid');
        // Initialize variables:
        $this->R_URI = $R_URI;
        // Create page tree, in $this->depth levels.
        $pageTree->getTree($pageinfo['pid'] ?? 0, $this->depth);
        // Initialize variables:
        $saveLatestUid = [];
        $latestInvDepth = $this->depth;
        // Traverse the tree:
        $lines = [];
        foreach ($pageTree->tree as $cc => $dat) {
            if ($latestInvDepth > $dat['invertedDepth']) {
                $margin = 'style="margin-left: ' . ($dat['invertedDepth'] * 16 + 9) . 'px;"';
                $lines[] = '<ul class="list-tree" ' . $margin . '>';
            }
            // Make link + parameters.
            $latestInvDepth = $dat['invertedDepth'];
            $saveLatestUid[$latestInvDepth] = $dat;
            if (isset($pageTree->tree[$cc - 1])) {
                $prev_dat = $pageTree->tree[$cc - 1];
                // If current page, subpage?
                if ($prev_dat['row']['uid'] == $id) {
                    // 1) It must be allowed to create a new page and 2) If there are subpages there is no need to render a subpage icon here - it'll be done over the subpages...
                    if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($id) && !($prev_dat['invertedDepth'] > $pageTree->tree[$cc]['invertedDepth'])) {
                        end($lines);
                        $margin = 'style="margin-left: ' . (($dat['invertedDepth'] - 1) * 16 + 9) . 'px;"';
                        $lines[] = '<ul class="list-tree" ' . $margin . '><li><span class="text-nowrap"><a href="' . htmlspecialchars($this->getActionLink($id, $id)) . '"><i class="t3-icon fa fa-long-arrow-left" title="' . $this->insertlabel() . '"></i></a></span></li></ul>';
                    }
                }
                // If going down
                if ($prev_dat['invertedDepth'] > $pageTree->tree[$cc]['invertedDepth']) {
                    $prevPid = $pageTree->tree[$cc]['row']['pid'];
                } elseif ($prev_dat['invertedDepth'] < $pageTree->tree[$cc]['invertedDepth']) {
                    // If going up
                    // First of all the previous level should have an icon:
                    if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($prev_dat['row']['pid'])) {
                        $prevPid = -$prev_dat['row']['uid'];
                        end($lines);
                        $lines[] = '<li><span class="text-nowrap"><a href="' . htmlspecialchars($this->getActionLink((int)$prevPid, $prev_dat['row']['pid'])) . '"><i class="t3-icon fa fa-long-arrow-left" title="' . $this->insertlabel() . '"></i></a></span></li>';
                    }
                    // Then set the current prevPid
                    $prevPid = -$prev_dat['row']['pid'];
                    if ($prevPid !== $dat['row']['pid']) {
                        $lines[] = '</ul>';
                    }
                } else {
                    // In on the same level
                    $prevPid = -$prev_dat['row']['uid'];
                }
            } else {
                // First in the tree
                $prevPid = $dat['row']['pid'];
            }
            // print arrow on the same level
            if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($dat['row']['pid'])) {
                $lines[] = '<span class="text-nowrap"><a href="' . htmlspecialchars($this->getActionLink($prevPid, $dat['row']['pid'])) . '"><i class="t3-icon fa fa-long-arrow-left" title="' . $this->insertlabel() . '"></i></a></span>';
            }
            // The line with the icon and title:
            $toolTip = BackendUtility::getRecordToolTip($dat['row'], 'pages');
            $icon = '<span ' . $toolTip . '>' . $this->iconFactory->getIconForRecord('pages', $dat['row'], Icon::SIZE_SMALL)->render() . '</span>';

            $lines[] = '<span class="text-nowrap">' . $icon . $this->linkPageTitle($this->boldTitle(htmlspecialchars(GeneralUtility::fixed_lgd_cs($dat['row']['title'], $this->getBackendUser()->uc['titleLen'])), $dat, $id), $dat['row']) . '</span>';
        }
        // If the current page was the last in the tree:
        $prev_dat = end($pageTree->tree);
        if ($prev_dat['row']['uid'] == $id) {
            if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($id)) {
                $lines[] = '<ul class="list-tree" style="margin-left: 25px"><li><span class="text-nowrap"><a href="' . htmlspecialchars($this->getActionLink($id, $id)) . '"><i class="t3-icon fa fa-long-arrow-left" title="' . $this->insertlabel() . '"></i></a></span></li></ul>';
            }
        }
        for ($a = $latestInvDepth; $a <= $this->depth; $a++) {
            $dat = $saveLatestUid[$a];
            $prevPid = -$dat['row']['uid'];
            if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($dat['row']['pid'])) {
                if ($latestInvDepth < $dat['invertedDepth']) {
                    $lines[] = '</ul>';
                }
                $lines[] = '<span class="text-nowrap"><a href="' . htmlspecialchars($this->getActionLink((int)$prevPid, $dat['row']['pid'])) . '"><i class="t3-icon fa fa-long-arrow-left" title="' . $this->insertlabel() . '"></i></a></span>';
            }
        }

        $code = '<ul class="list-tree">';

        foreach ($lines as $line) {
            if ((strpos($line, '<ul') === 0) || (strpos($line, '</ul') === 0)) {
                $code .= $line;
            } else {
                $code .= '<li>' . $line . '</li>';
            }
        }

        $code .= '</ul>';
        return $code;
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
        if ($dat['row']['uid'] == $id) {
            $t_code = '<strong>' . $t_code . '</strong>';
        }
        return $t_code;
    }

    /**
     * Creates the onclick event for the insert-icons.
     *
     * TSconfig mod.newPageWizard.override may contain an alternative module / route which can be
     * used instead of the normal create new page wizard.
     *
     * @param int $pid The pid.
     * @param int $newPagePID New page id.
     * @return string Onclick attribute content
     */
    public function getActionLink($pid, $newPagePID): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $TSconfig = BackendUtility::getPagesTSconfig($newPagePID)['mod.']['newPageWizard.'] ?? [];
        if (isset($TSconfig['override']) && !empty($TSconfig['override'])) {
            $url = $uriBuilder->buildUriFromRoute(
                $TSconfig['override'],
                [
                    'positionPid' => $pid,
                    'newPageId'   => $newPagePID,
                    'cmd'         => 'crPage',
                    'returnUrl'   => $this->R_URI,
                ]
            );
            return (string)$url;
        }

        return (string)$uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                'pages' => [
                    $pid => 'new',
                ],
            ],
            'returnNewPageId' => '1',
            'returnUrl' => $this->R_URI,
        ]);
    }

    /**
     * Get label, htmlspecialchars()'ed
     *
     * @return string The localized label for "insert new page here"
     */
    protected function insertlabel()
    {
        return htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:insertNewPageHere'));
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
        return $str;
    }

    /**
     * Checks if the user has permission to created pages inside of the $pid page.
     * Uses caching so only one regular lookup is made - hence you can call the function multiple times without worrying about performance.
     *
     * @param int $pid Page id for which to test.
     * @return bool
     */
    public function checkNewPageInPid($pid)
    {
        if (!isset($this->checkNewPageCache[$pid])) {
            $pidInfo = BackendUtility::getRecord('pages', $pid);
            $this->checkNewPageCache[$pid] = $this->getBackendUser()->isAdmin() || $this->getBackendUser()->doesUserHaveAccess($pidInfo, Permission::PAGE_NEW);
        }
        return $this->checkNewPageCache[$pid];
    }

    /**
     * Returns the BackendUser
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
