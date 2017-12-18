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

use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

/**
 * Base extension class which generates the folder tree.
 * Used directly by the RTE.
 * also used for the linkpicker on files
 *
 * Browsable folder tree, used in Element Browser and RTE (for which it will be extended)
 * previously located inside typo3/class.browse_links.php
 */
class ElementBrowserFolderTreeView extends FolderTreeView
{
    /**
     * @var string
     */
    public $ext_IconMode = 'titlelink';

    /**
     * @var LinkParameterProviderInterface
     */
    protected $linkParameterProvider;

    /**
     * @param LinkParameterProviderInterface $linkParameterProvider
     */
    public function setLinkParameterProvider(LinkParameterProviderInterface $linkParameterProvider)
    {
        $this->linkParameterProvider = $linkParameterProvider;
        $this->thisScript = $linkParameterProvider->getScriptUrl();
    }

    /**
     * Wrapping the folder icon
     *
     * @param string $icon The image tag for the icon
     * @param Folder $folderObject The row for the current element
     *
     * @return string The processed icon input value.
     * @internal
     */
    public function wrapIcon($icon, $folderObject)
    {
        // Add title attribute to input icon tag
        $theFolderIcon = '';

        // Wrap icon in link (in ElementBrowser only the "titlelink" is used).
        if ($this->ext_IconMode === 'titlelink') {
            $parameters = HttpUtility::buildQueryString(
                $this->linkParameterProvider->getUrlParameters(['identifier' => $folderObject->getCombinedIdentifier()])
            );
            $aOnClick = 'return jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . $parameters) . ');';
            $theFolderIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $icon . '</a>';
        }

        return $theFolderIcon;
    }

    /**
     * Wrapping the title in a link, if applicable.
     *
     * @param string $title Title, ready for output.
     * @param Folder $folderObject The record
     * @param int $bank Bank pointer (which mount point number)
     * @return string Wrapping title string.
     */
    public function wrapTitle($title, $folderObject, $bank = 0)
    {
        $parameters = HttpUtility::buildQueryString(
            $this->linkParameterProvider->getUrlParameters(['identifier' => $folderObject->getCombinedIdentifier()])
        );
        return '<a href="#" onclick="return jumpToUrl(' . htmlspecialchars(GeneralUtility::quoteJSvalue($this->getThisScript() . $parameters)) . ');">' . $title . '</a>';
    }

    /**
     * Returns TRUE if the input "record" contains a folder which can be linked.
     *
     * @param Folder $folderObject Object with information about the folder element. Contains keys like title, uid, path, _title
     * @return bool TRUE
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function ext_isLinkable(Folder $folderObject)
    {
        trigger_error('This method is obsolete and will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return true;
    }

    /**
     * @param string $cmd
     * @param bool $isOpen
     * @return string
     */
    protected function renderPMIconAndLink($cmd, $isOpen)
    {
        if (static::class === __CLASS__) {
            return $this->PMiconATagWrap('', $cmd, !$isOpen);
        }
        return parent::renderPMIconAndLink($cmd, $isOpen);
    }

    /**
     * Wrap the plus/minus icon in a link
     *
     * @param string $icon HTML string to wrap, probably an image tag.
     * @param string $cmd Command for 'PM' get var
     * @param bool|string $bMark If set, the link will have an anchor point (=$bMark) and a name attribute (=$bMark)
     * @param bool $isOpen check if the item has children
     * @return string Link-wrapped input string
     * @internal
     */
    public function PM_ATagWrap($icon, $cmd, $bMark = '', $isOpen = false)
    {
        $anchor = $bMark ? '#' . $bMark : '';
        $name = $bMark ? ' name=' . $bMark : '';
        $urlParameters = $this->linkParameterProvider->getUrlParameters([]);
        $urlParameters['PM'] = $cmd;
        $aOnClick = 'return jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . HttpUtility::buildQueryString($urlParameters)) . ',' . GeneralUtility::quoteJSvalue($anchor) . ');';
        return '<a href="#"' . htmlspecialchars($name) . ' onclick="' . htmlspecialchars($aOnClick) . '">' . $icon . '</a>';
    }

    /**
     * Wrap the plus/minus icon in a link
     *
     * @param string $icon HTML string to wrap, probably an image tag.
     * @param string $cmd Command for 'PM' get var
     * @param bool $isExpand Whether to be expanded
     * @return string Link-wrapped input string
     * @internal
     */
    public function PMiconATagWrap($icon, $cmd, $isExpand = true)
    {
        if (empty($this->scope)) {
            $this->scope = [
                'class' => static::class,
                'script' => $this->thisScript,
                'browser' => $this->linkParameterProvider->getUrlParameters([]),
            ];
        }

        return parent::PMiconATagWrap($icon, $cmd, $isExpand);
    }
}
