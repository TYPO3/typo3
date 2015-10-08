<?php
namespace TYPO3\CMS\Rtehtmlarea;

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

use TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * RTE class which generates the folder tree.
 */
class FolderTree extends ElementBrowserFolderTreeView
{
    /**
     * @var BrowseLinks|SelectImage
     */
    protected $linkParameterProvider;

    /**
     * Will create and return the HTML code for a browsable tree of folders.
     * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
     *
     * @return string HTML code for the browsable tree
     */
    public function getBrowsableTree()
    {
        // TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController does not set custom parameters on an Ajax expand/collapse request
        if (!$this->linkParameterProvider->editorNo) {
            $scopeData = (string)GeneralUtility::_GP('scopeData');
            $scopeHash = (string)GeneralUtility::_GP('scopeHash');
            if (!empty($scopeData) && GeneralUtility::hmac($scopeData) === $scopeHash) {
                $scopeData = unserialize($scopeData);
                if ($scopeData['browser']['editorNo']) {
                    $this->linkParameterProvider->editorNo = $scopeData['browser']['editorNo'];
                }
                if ($this->linkParameterProvider instanceof SelectImage && $scopeData['browser']['sys_language_content']) {
                    $this->linkParameterProvider->sys_language_content = $scopeData['browser']['sys_language_content'];
                }
                if ($this->linkParameterProvider instanceof BrowseLinks && $scopeData['browser']['contentTypo3Language']) {
                    $this->linkParameterProvider->contentTypo3Language = $scopeData['browser']['contentTypo3Language'];
                }
            }
        }
        return parent::getBrowsableTree();
    }

    /**
     * Wrapping the title in a link, if applicable.
     *
     * @param string $title Title, ready for output.
     * @param Folder $folderObject The "record"
     * @return string Wrapping title string.
     */
    public function wrapTitle($title, Folder $folderObject)
    {
        if ($this->ext_isLinkable($folderObject)) {
            $parameters = 'act=' . $this->linkParameterProvider->act
                . '&mode=' . $this->linkParameterProvider->mode
                . '&editorNo=' . $this->linkParameterProvider->editorNo
                . '&expandFolder=' . $this->getJumpToParam($folderObject);
            if ($this->linkParameterProvider instanceof SelectImage && $this->linkParameterProvider->sys_language_content) {
                $parameters .= '&sys_language_content=' . $this->linkParameterProvider->sys_language_content;
            }
            if ($this->linkParameterProvider instanceof BrowseLinks && $this->linkParameterProvider->contentTypo3Language) {
                $parameters .= '&contentTypo3Language=' . $this->linkParameterProvider->contentTypo3Language;
            }
            $aOnClick = 'return jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . $parameters) . ');';
            return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a>';
        }
        return '<span class="text-muted">' . $title . '</span>';
    }

    /**
     * Wrap the plus/minus icon in a link
     *
     * @param string $icon HTML string to wrap, probably an image tag.
     * @param string $cmd Command for 'PM' get var
     * @param bool $isExpand If expanded
     * @return string Link-wrapped input string
     * @access private
     */
    public function PMiconATagWrap($icon, $cmd, $isExpand = true)
    {
        if (empty($this->scope)) {
            $this->scope = array(
                'class' => get_class($this),
                'script' => $this->thisScript,
                'ext_noTempRecyclerDirs' => $this->ext_noTempRecyclerDirs,
                'browser' => array(
                    'mode' => $this->linkParameterProvider->mode,
                    'act' => $this->linkParameterProvider->act
                )
            );
            if ($this->linkParameterProvider instanceof BrowseLinks) {
                if ($this->linkParameterProvider->editorNo) {
                    $this->scope['browser']['editorNo'] = $this->linkParameterProvider->editorNo;
                }
                if ($this->linkParameterProvider->contentTypo3Language) {
                    $this->scope['browser']['contentTypo3Language'] = $this->linkParameterProvider->contentTypo3Language;
                }
            }
            if ($this->linkParameterProvider instanceof SelectImage) {
                if ($this->linkParameterProvider->sys_language_content) {
                    $this->scope['browser']['sys_language_content'] = $this->linkParameterProvider->sys_language_content;
                }
            }
        }
        return parent::PMiconATagWrap($icon, $cmd, $isExpand);
    }
}
