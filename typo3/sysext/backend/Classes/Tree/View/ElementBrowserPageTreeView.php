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

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

/**
 * Class which generates the selectable page tree
 *
 * Browsable tree, used in PagePositionMaps (move elements), the Link Wizard and the Database Browser (for which it will be extended)
 */
class ElementBrowserPageTreeView extends BrowseTreeView
{
    /**
     * @var LinkParameterProviderInterface
     */
    protected $linkParameterProvider;

    /**
     * Constructor. Just calling init()
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->clause = ' AND doktype <> ' . PageRepository::DOKTYPE_RECYCLER . $this->clause;
    }

    /**
     * @param LinkParameterProviderInterface $linkParameterProvider
     */
    public function setLinkParameterProvider(LinkParameterProviderInterface $linkParameterProvider)
    {
        $this->linkParameterProvider = $linkParameterProvider;
        $this->thisScript = $linkParameterProvider->getScriptUrl();
    }

    /**
     * Wrapping the title in a link, if applicable.
     *
     * @param string $title Title, (must be ready for output, that means it must be htmlspecialchars()'ed).
     * @param array $v The record
     * @return string Wrapping title string.
     */
    public function wrapTitle($title, $v)
    {
        if ($this->ext_isLinkable($v['doktype'], $v['uid'])) {
            $url = GeneralUtility::makeInstance(LinkService::class)->asString(['type' => LinkService::TYPE_PAGE, 'pageuid' => (int)$v['uid']]);
            return '<span class="list-tree-title"><a href="' . htmlspecialchars($url) . '" class="t3js-pageLink">' . $title . '</a></span>';
        }
        return '<span class="list-tree-title text-muted">' . $title . '</span>';
    }

    /**
     * Create the page navigation tree in HTML
     *
     * @param array|string $treeArr Tree array
     * @return string HTML output.
     */
    public function printTree($treeArr = '')
    {
        $titleLen = (int)$this->getBackendUser()->uc['titleLen'];
        if (!is_array($treeArr)) {
            $treeArr = $this->tree;
        }
        $out = '';
        // We need to count the opened <ul>'s every time we dig into another level,
        // so we know how many we have to close when all children are done rendering
        $closeDepth = [];
        foreach ($treeArr as $treeItem) {
            $classAttr = $treeItem['row']['_CSSCLASS'];
            if ($treeItem['isFirst']) {
                $out .= '<ul class="list-tree">';
            }

            // Add CSS classes to the list item
            if ($treeItem['hasSub']) {
                $classAttr .= ' list-tree-control-open';
            }

            $selected = '';
            if ($this->linkParameterProvider->isCurrentlySelectedItem(['pid' => (int)$treeItem['row']['uid']])) {
                $selected = ' bg-success';
                $classAttr .= ' active';
            }
            $urlParameters = $this->linkParameterProvider->getUrlParameters(['pid' => (int)$treeItem['row']['uid']]);
            $cEbullet = $this->ext_isLinkable($treeItem['row']['doktype'], $treeItem['row']['uid'])
                ? '<a href="' . htmlspecialchars($this->getThisScript() . HttpUtility::buildQueryString($urlParameters)) . '" class="list-tree-show"><i class="fa fa-caret-square-o-right"></i></a>'
                : '';
            $out .= '
				<li' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '') . '>
					<span class="list-tree-group' . $selected . '">
						' . $cEbullet . $treeItem['HTML'] . $this->wrapTitle($this->getTitleStr($treeItem['row'], $titleLen), $treeItem['row']) . '
					</span>
				';
            if (!$treeItem['hasSub']) {
                $out .= '</li>';
            }

            // We have to remember if this is the last one
            // on level X so the last child on level X+1 closes the <ul>-tag
            if ($treeItem['isLast']) {
                $closeDepth[$treeItem['invertedDepth']] = 1;
            }
            // If this is the last one and does not have subitems, we need to close
            // the tree as long as the upper levels have last items too
            if ($treeItem['isLast'] && !$treeItem['hasSub']) {
                for ($i = $treeItem['invertedDepth']; $closeDepth[$i] == 1; $i++) {
                    $closeDepth[$i] = 0;
                    $out .= '</ul></li>';
                }
            }
        }
        return '<ul class="list-tree list-tree-root">' . $out . '</ul>';
    }

    /**
     * Returns TRUE if a doktype can be linked.
     *
     * @param int $doktype Doktype value to test
     * @param int $uid uid to test.
     * @return bool
     */
    public function ext_isLinkable($doktype, $uid)
    {
        $notLinkableDokTypes = [
            PageRepository::DOKTYPE_SPACER,
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_RECYCLER,
        ];
        return $uid && !in_array($doktype, $notLinkableDokTypes, true);
    }

    /**
     * Wrap the plus/minus icon in a link
     *
     * @param string $bMark If set, the link will have a name attribute (=$bMark)
     * @param bool $isOpen
     * @return string Link-wrapped input string
     */
    public function PM_ATagWrap($bMark = '', $isOpen = false)
    {
        $bMark = htmlspecialchars((string)$bMark);
        $anchor = $bMark ? '#' . $bMark : '';
        $name = $bMark ? ' name=' . $bMark : '';
        $urlParameters = $this->linkParameterProvider->getUrlParameters([]);
        return '<a class="list-tree-control ' . ($isOpen ? 'list-tree-control-open' : 'list-tree-control-closed')
            . '" href="' . htmlspecialchars($this->getThisScript() . HttpUtility::buildQueryString($urlParameters)) . $anchor . '"' . $name . '><i class="fa"></i></a>';
    }
}
