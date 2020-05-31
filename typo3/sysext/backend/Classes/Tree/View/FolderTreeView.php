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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generate a folder tree,
 * specially made for browsing folders in the File module
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class FolderTreeView extends AbstractTreeView
{
    /**
     * The users' file Storages
     *
     * @var ResourceStorage[]
     */
    protected $storages;

    /**
     * @var array
     */
    protected $storageHashNumbers;

    /**
     * Indicates, whether the AJAX call was successful,
     * i.e. the requested page has been found
     *
     * @var bool
     */
    protected $ajaxStatus = false;

    /**
     * @var array
     */
    protected $scope;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * override to not use a title attribute
     * @var string
     */
    public $titleAttrib = '';

    /**
     * override to use this treeName
     * does not need to be set in __construct()
     * @var string
     */
    public $treeName = 'folder';

    /**
     * override to use this domIdPrefix
     * @var string
     */
    public $domIdPrefix = 'folder';

    /**
     * Constructor function of the class
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->storages = $this->BE_USER->getFileStorages();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Generate the plus/minus icon for the browsable tree.
     *
     * @param Folder $folderObject Entry folder object
     * @param int $subFolderCounter The current entry number
     * @param int $totalSubFolders The total number of entries. If equal to $a, a "bottom" element is returned.
     * @param int $nextCount The number of sub-elements to the current element.
     * @param bool $isExpanded The element was expanded to render subelements if this flag is set.
     *
     * @return string Image tag with the plus/minus icon.
     * @internal
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::PMicon()
     */
    public function PMicon($folderObject, $subFolderCounter, $totalSubFolders, $nextCount, $isExpanded)
    {
        $icon = '';
        if ($nextCount) {
            $cmd = $this->generateExpandCollapseParameter($this->bank, !$isExpanded, $folderObject);
            $icon = $this->PMiconATagWrap($icon, $cmd, !$isExpanded);
        }
        return $icon;
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
            ];
        }

        if ($this->thisScript) {
            // Activates dynamic AJAX based tree
            $scopeData = json_encode($this->scope);
            $scopeHash = GeneralUtility::hmac($scopeData);
            $js = htmlspecialchars('Tree.load(' . GeneralUtility::quoteJSvalue($cmd) . ', ' . (int)$isExpand . ', this, ' . GeneralUtility::quoteJSvalue($scopeData) . ', ' . GeneralUtility::quoteJSvalue($scopeHash) . ');');
            return '<a class="list-tree-control' . (!$isExpand ? ' list-tree-control-open' : ' list-tree-control-closed') . '" onclick="' . $js . '"><i class="fa"></i></a>';
        }
        return $icon;
    }

    /**
     * @param string $cmd
     * @param bool $isOpen
     * @return string
     */
    protected function renderPMIconAndLink($cmd, $isOpen)
    {
        $link = $this->thisScript ? ' href="' . htmlspecialchars($this->getThisScript() . 'PM=' . $cmd) . '"' : '';
        return '<a class="list-tree-control list-tree-control-' . ($isOpen ? 'open' : 'closed') . '"' . $link . '><i class="fa"></i></a>';
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
        // Wrap icon in click-menu link.
        if (!$this->ext_IconMode) {
            // Check storage access to wrap with click menu
            if (!$folderObject instanceof InaccessibleFolder) {
                $tableName = $this->getTableNameForClickMenu($folderObject);
                $theFolderIcon = BackendUtility::wrapClickMenuOnIcon($icon, $tableName, $folderObject->getCombinedIdentifier(), 'tree');
            }
        } elseif ($this->ext_IconMode === 'titlelink') {
            $aOnClick = 'return jumpTo(' . GeneralUtility::quoteJSvalue($this->getJumpToParam($folderObject)) . ',this,' . GeneralUtility::quoteJSvalue($this->domIdPrefix . $this->getId($folderObject)) . ',' . $this->bank . ');';
            $theFolderIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $icon . '</a>';
        }
        return $theFolderIcon;
    }

    /**
     * Wrapping $title in a-tags.
     *
     * @param string $title Title string
     * @param Folder $folderObject the folder record
     * @param int $bank Bank pointer (which mount point number)
     *
     * @return string
     * @internal
     */
    public function wrapTitle($title, $folderObject, $bank = 0)
    {
        // Check storage access to wrap with click menu
        if ($folderObject instanceof InaccessibleFolder) {
            return $title;
        }
        $aOnClick = 'return jumpTo(' . GeneralUtility::quoteJSvalue($this->getJumpToParam($folderObject)) . ', this, ' . GeneralUtility::quoteJSvalue($this->domIdPrefix . $this->getId($folderObject)) . ', ' . $bank . ');';
        $tableName = $this->getTableNameForClickMenu($folderObject);
        $clickMenuParts = BackendUtility::wrapClickMenuOnIcon('', $tableName, $folderObject->getCombinedIdentifier(), 'tree', '', '', true);

        return '<a href="#" title="' . htmlspecialchars(strip_tags($title)) . '" onclick="' . htmlspecialchars($aOnClick) . '" ' . GeneralUtility::implodeAttributes($clickMenuParts) . '>' . $title . '</a>';
    }

    /**
     * Returns the id from the record - for folders, this is an md5 hash.
     *
     * @param Folder $folderObject The folder object
     *
     * @return int The "uid" field value.
     */
    public function getId($folderObject)
    {
        return GeneralUtility::md5int($folderObject->getCombinedIdentifier());
    }

    /**
     * Returns jump-url parameter value.
     *
     * @param Folder $folderObject The folder object
     *
     * @return string The jump-url parameter.
     */
    public function getJumpToParam($folderObject)
    {
        return rawurlencode($folderObject->getCombinedIdentifier());
    }

    /**
     * Returns the title for the input record. If blank, a "no title" label (localized) will be returned.
     * '_title' is used for setting an alternative title for folders.
     *
     * @param array $row The input row array (where the key "_title" is used for the title)
     * @param int $titleLen Title length (30)
     * @return string The title
     */
    public function getTitleStr($row, $titleLen = 30)
    {
        return $row['_title'] ?? parent::getTitleStr($row, $titleLen);
    }

    /**
     * Returns the value for the image "title" attribute
     *
     * @param Folder $folderObject The folder to be used
     *
     * @return string The attribute value (is htmlspecialchared() already)
     */
    public function getTitleAttrib($folderObject)
    {
        return htmlspecialchars($folderObject->getName());
    }

    /**
     * Will create and return the HTML code for a browsable tree of folders.
     * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
     *
     * @return string HTML code for the browsable tree
     */
    public function getBrowsableTree()
    {
        // Get stored tree structure AND updating it if needed according to incoming PM GET var.
        $this->initializePositionSaving();
        // Init done:
        $treeItems = [];
        // Traverse mounts:
        foreach ($this->storages as $storageObject) {
            $this->getBrowseableTreeForStorage($storageObject);
            // Add tree:
            $treeItems = array_merge($treeItems, $this->tree);
        }
        return $this->printTree($treeItems);
    }

    /**
     * Get a tree for one storage
     *
     * @param ResourceStorage $storageObject
     */
    public function getBrowseableTreeForStorage(ResourceStorage $storageObject)
    {
        // If there are filemounts, show each, otherwise just the rootlevel folder
        $fileMounts = $storageObject->getFileMounts();
        $rootLevelFolders = [];
        if (!empty($fileMounts)) {
            foreach ($fileMounts as $fileMountInfo) {
                $rootLevelFolders[] = [
                    'folder' => $fileMountInfo['folder'],
                    'name' => $fileMountInfo['title']
                ];
            }
        } elseif ($this->BE_USER->isAdmin()) {
            $rootLevelFolders[] = [
                'folder' => $storageObject->getRootLevelFolder(),
                'name' => $storageObject->getName()
            ];
        }
        // Clean the tree
        $this->reset();
        // Go through all "root level folders" of this tree (can be the rootlevel folder or any file mount points)
        foreach ($rootLevelFolders as $rootLevelFolderInfo) {
            /** @var Folder $rootLevelFolder */
            $rootLevelFolder = $rootLevelFolderInfo['folder'];
            $rootLevelFolderName = $rootLevelFolderInfo['name'];
            $folderHashSpecUID = GeneralUtility::md5int($rootLevelFolder->getCombinedIdentifier());
            $this->specUIDmap[$folderHashSpecUID] = $rootLevelFolder->getCombinedIdentifier();
            // Hash key
            $storageHashNumber = $this->getShortHashNumberForStorage($storageObject, $rootLevelFolder);
            // Set first:
            $this->bank = $storageHashNumber;
            $isOpen = $this->stored[$storageHashNumber][$folderHashSpecUID] || $this->expandFirst;
            // Set PM icon:
            $cmd = $this->generateExpandCollapseParameter($this->bank, !$isOpen, $rootLevelFolder);
            // Only show and link icon if storage is browseable
            if (!$storageObject->isBrowsable() || $this->getNumberOfSubfolders($rootLevelFolder) === 0) {
                $firstHtml = '';
            } else {
                $firstHtml = $this->renderPMIconAndLink($cmd, $isOpen);
            }
            // Mark a storage which is not online, as offline
            // maybe someday there will be a special icon for this
            if ($storageObject->isOnline() === false) {
                $rootLevelFolderName .= ' (' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_file.xlf:sys_file_storage.isOffline') . ')';
            }
            // Preparing rootRec for the mount
            $icon = $this->iconFactory->getIconForResource($rootLevelFolder, Icon::SIZE_SMALL, null, ['mount-root' => true]);
            $firstHtml .= $this->wrapIcon($icon, $rootLevelFolder);
            $row = [
                'uid' => $folderHashSpecUID,
                'title' => $rootLevelFolderName,
                'path' => $rootLevelFolder->getCombinedIdentifier(),
                'folder' => $rootLevelFolder
            ];
            // Add the storage root to ->tree
            $this->tree[] = [
                'HTML' => $firstHtml,
                'row' => $row,
                'bank' => $this->bank,
                // hasSub is TRUE when the root of the storage is expanded
                'hasSub' => $isOpen && $storageObject->isBrowsable(),
                'invertedDepth' => 1000,
            ];
            // If the mount is expanded, go down:
            if ($isOpen && $storageObject->isBrowsable()) {
                // Set depth:
                $this->getFolderTree($rootLevelFolder, 999);
            }
        }
    }

    /**
     * Fetches the data for the tree
     *
     * @param Folder $folderObject the folderobject
     * @param int $depth Max depth (recursivity limit)
     * @param string $type HTML-code prefix for recursive calls.
     *
     * @return int The count of items on the level
     * @see getBrowsableTree()
     */
    public function getFolderTree(Folder $folderObject, $depth = 999, $type = '')
    {
        $depth = (int)$depth;

        // This generates the directory tree
        /* array of \TYPO3\CMS\Core\Resource\Folder */
        if ($folderObject instanceof InaccessibleFolder) {
            $subFolders = [];
        } else {
            $subFolders = $folderObject->getSubfolders();
            $subFolders = \TYPO3\CMS\Core\Resource\Utility\ListUtility::resolveSpecialFolderNames($subFolders);
            uksort($subFolders, 'strnatcasecmp');
        }

        $totalSubFolders = count($subFolders);
        $HTML = '';
        $subFolderCounter = 0;
        $treeKey = '';
        /** @var Folder $subFolder */
        foreach ($subFolders as $subFolderName => $subFolder) {
            $subFolderCounter++;
            // Reserve space.
            $this->tree[] = [];
            // Get the key for this space
            end($this->tree);
            $isLocked = $subFolder instanceof InaccessibleFolder;
            $treeKey = key($this->tree);
            $specUID = GeneralUtility::md5int($subFolder->getCombinedIdentifier());
            $this->specUIDmap[$specUID] = $subFolder->getCombinedIdentifier();
            $row = [
                'uid' => $specUID,
                'path' => $subFolder->getCombinedIdentifier(),
                'title' => $subFolderName,
                'folder' => $subFolder
            ];
            // Make a recursive call to the next level
            if (!$isLocked && $depth > 1 && $this->expandNext($specUID)) {
                $nextCount = $this->getFolderTree($subFolder, $depth - 1, $type);
                // Set "did expand" flag
                $isOpen = 1;
            } else {
                $nextCount = $isLocked ? 0 : $this->getNumberOfSubfolders($subFolder);
                // Clear "did expand" flag
                $isOpen = 0;
            }
            // Set HTML-icons, if any:
            if ($this->makeHTML) {
                $HTML = $this->PMicon($subFolder, $subFolderCounter, $totalSubFolders, $nextCount, $isOpen);
                $type = '';

                $role = $subFolder->getRole();
                if ($role !== FolderInterface::ROLE_DEFAULT) {
                    $row['_title'] = '<strong>' . $subFolderName . '</strong>';
                }
                $icon = '<span title="' . htmlspecialchars($subFolderName) . '">'
                    . $this->iconFactory->getIconForResource($subFolder, Icon::SIZE_SMALL, null, ['folder-open' => (bool)$isOpen])
                    . '</span>';
                $HTML .= $this->wrapIcon($icon, $subFolder);
            }
            // Finally, add the row/HTML content to the ->tree array in the reserved key.
            $this->tree[$treeKey] = [
                'row' => $row,
                'HTML' => $HTML,
                'hasSub' => $nextCount && $this->expandNext($specUID),
                'isFirst' => $subFolderCounter == 1,
                'isLast' => false,
                'invertedDepth' => $depth,
                'bank' => $this->bank
            ];
        }
        if ($subFolderCounter > 0) {
            $this->tree[$treeKey]['isLast'] = true;
        }
        return $totalSubFolders;
    }

    /**
     * Compiles the HTML code for displaying the structure found inside the ->tree array
     *
     * @param array|string $treeItems "tree-array" - if blank string, the internal ->tree array is used.
     * @return string The HTML code for the tree
     */
    public function printTree($treeItems = '')
    {
        $doExpand = false;
        $doCollapse = false;
        $ajaxOutput = '';
        $titleLength = (int)$this->BE_USER->uc['titleLen'];
        if (!is_array($treeItems)) {
            $treeItems = $this->tree;
        }

        if (empty($treeItems)) {
            $message = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:foldertreeview.noFolders.message'),
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:foldertreeview.noFolders.title'),
                FlashMessage::INFO
            );
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($message);
            return $defaultFlashMessageQueue->renderFlashMessages();
        }

        $expandedFolderHash = '';
        $invertedDepthOfAjaxRequestedItem = 0;
        $out = '<ul class="list-tree list-tree-root">';
        // Evaluate AJAX request
        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
            list(, $expandCollapseCommand, $expandedFolderHash, ) = $this->evaluateExpandCollapseParameter();
            if ($expandCollapseCommand == 1) {
                $doExpand = true;
            } else {
                $doCollapse = true;
            }
        }
        // We need to count the opened <ul>'s every time we dig into another level,
        // so we know how many we have to close when all children are done rendering
        $closeDepth = [];
        foreach ($treeItems as $treeItem) {
            /** @var Folder $folderObject */
            $folderObject = $treeItem['row']['folder'];
            $classAttr = $treeItem['row']['_CSSCLASS'] ?? '';
            $folderIdentifier = $folderObject->getCombinedIdentifier();
            // this is set if the AJAX request has just opened this folder (via the PM command)
            $isExpandedFolderIdentifier = $expandedFolderHash == GeneralUtility::md5int($folderIdentifier);
            $idAttr = htmlspecialchars($this->domIdPrefix . $this->getId($folderObject) . '_' . $treeItem['bank']);
            $itemHTML = '';
            // If this item is the start of a new level,
            // then a new level <ul> is needed, but not in ajax mode
            if (!empty($treeItem['isFirst']) && !$doCollapse && !($doExpand && $isExpandedFolderIdentifier)) {
                $itemHTML = '<ul class="list-tree">';
            }
            // Add CSS classes to the list item
            if (!empty($treeItem['hasSub'])) {
                $classAttr .= ' list-tree-control-open';
            }
            $itemHTML .= '
				<li id="' . $idAttr . '" ' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '') . '><span class="list-tree-group">' . $treeItem['HTML'] . $this->wrapTitle($this->getTitleStr($treeItem['row'], $titleLength), $folderObject, $treeItem['bank']) . '</span>';
            if (empty($treeItem['hasSub'])) {
                $itemHTML .= '</li>';
            }
            // We have to remember if this is the last one
            // on level X so the last child on level X+1 closes the <ul>-tag
            if (!empty($treeItem['isLast']) && !($doExpand && $isExpandedFolderIdentifier)) {
                $closeDepth[$treeItem['invertedDepth']] = 1;
            }
            // If this is the last one and does not have subitems, we need to close
            // the tree as long as the upper levels have last items too
            if (!empty($treeItem['isLast']) && empty($treeItem['hasSub']) && !$doCollapse && !($doExpand && $isExpandedFolderIdentifier)) {
                for ($i = $treeItem['invertedDepth']; !empty($closeDepth[$i]); $i++) {
                    $closeDepth[$i] = 0;
                    $itemHTML .= '</ul></li>';
                }
            }
            // Ajax request: collapse
            if ($doCollapse && $isExpandedFolderIdentifier) {
                $this->ajaxStatus = true;
                return $itemHTML;
            }
            // Ajax request: expand
            if ($doExpand && $isExpandedFolderIdentifier) {
                $ajaxOutput .= $itemHTML;
                $invertedDepthOfAjaxRequestedItem = $treeItem['invertedDepth'];
            } elseif ($invertedDepthOfAjaxRequestedItem) {
                if ($treeItem['invertedDepth'] && ($treeItem['invertedDepth'] < $invertedDepthOfAjaxRequestedItem)) {
                    $ajaxOutput .= $itemHTML;
                } else {
                    $this->ajaxStatus = true;
                    return $ajaxOutput;
                }
            }
            $out .= $itemHTML;
        }
        // If this is an AJAX request, output directly
        if ($ajaxOutput) {
            $this->ajaxStatus = true;
            return $ajaxOutput;
        }
        // Finally close the first ul
        $out .= '</ul>';
        return $out;
    }

    /**
     * Returns table name for click menu
     *
     * @param Folder $folderObject
     * @return string
     */
    protected function getTableNameForClickMenu(Folder $folderObject)
    {
        if (strpos($folderObject->getRole(), FolderInterface::ROLE_MOUNT) !== false) {
            $tableName = 'sys_filemounts';
        } elseif ($folderObject->getIdentifier() === $folderObject->getStorage()->getRootLevelFolder()->getIdentifier()) {
            $tableName = 'sys_file_storage';
        } else {
            $tableName = 'sys_file';
        }
        return $tableName;
    }

    /**
     * Counts the number of directories in a file path.
     *
     * @param Folder $folderObject File path.
     *
     * @return int
     */
    public function getNumberOfSubfolders(Folder $folderObject)
    {
        $subFolders = $folderObject->getSubfolders();
        return count($subFolders);
    }

    /**
     * Get stored tree structure AND updating it if needed according to incoming PM GET var.
     *
     * @internal
     */
    public function initializePositionSaving()
    {
        // Get stored tree structure:
        $this->stored = unserialize($this->BE_USER->uc['browseTrees'][$this->treeName], ['allowed_classes' => false]);
        $this->getShortHashNumberForStorage();
        // PM action:
        // (If an plus/minus icon has been clicked,
        // the PM GET var is sent and we must update the stored positions in the tree):
        // 0: mount key, 1: set/clear boolean, 2: item ID (cannot contain "_"), 3: treeName
        list($storageHashNumber, $doExpand, $numericFolderHash, $treeName) = $this->evaluateExpandCollapseParameter();
        if ($treeName && $treeName == $this->treeName) {
            if (in_array($storageHashNumber, $this->storageHashNumbers)) {
                if ($doExpand == 1) {
                    // Set
                    $this->stored[$storageHashNumber][$numericFolderHash] = 1;
                } else {
                    // Clear
                    unset($this->stored[$storageHashNumber][$numericFolderHash]);
                }
                $this->savePosition();
            }
        }
    }

    /**
     * Helper method to map md5-hash to shorter number
     *
     * @param ResourceStorage $storageObject
     * @param Folder $startingPointFolder
     *
     * @return int
     */
    protected function getShortHashNumberForStorage(ResourceStorage $storageObject = null, Folder $startingPointFolder = null)
    {
        if (!$this->storageHashNumbers) {
            $this->storageHashNumbers = [];
            foreach ($this->storages as $storageUid => $storage) {
                $fileMounts = $storage->getFileMounts();
                if (!empty($fileMounts)) {
                    foreach ($fileMounts as $fileMount) {
                        $nkey = hexdec(substr(GeneralUtility::md5int($fileMount['folder']->getCombinedIdentifier()), 0, 4));
                        $this->storageHashNumbers[$storageUid . $fileMount['folder']->getCombinedIdentifier()] = $nkey;
                    }
                } else {
                    $folder = $storage->getRootLevelFolder();
                    $nkey = hexdec(substr(GeneralUtility::md5int($folder->getCombinedIdentifier()), 0, 4));
                    $this->storageHashNumbers[$storageUid . $folder->getCombinedIdentifier()] = $nkey;
                }
            }
        }
        if ($storageObject) {
            if ($startingPointFolder) {
                return $this->storageHashNumbers[$storageObject->getUid() . $startingPointFolder->getCombinedIdentifier()];
            }
            return $this->storageHashNumbers[$storageObject->getUid()];
        }
        return null;
    }

    /**
     * Gets the values from the Expand/Collapse Parameter (&PM)
     * previously known as "PM" (plus/minus)
     * PM action:
     * (If an plus/minus icon has been clicked,
     * the PM GET var is sent and we must update the stored positions in the tree):
     * 0: mount key, 1: set/clear boolean, 2: item ID (cannot contain "_"), 3: treeName
     *
     * @param string $PM The "plus/minus" command
     * @return array
     */
    protected function evaluateExpandCollapseParameter($PM = null)
    {
        if ($PM === null) {
            $PM = GeneralUtility::_GP('PM');
            // IE takes anchor as parameter
            if (($PMpos = strpos($PM, '#')) !== false) {
                $PM = substr($PM, 0, $PMpos);
            }
        }
        // Take the first three parameters
        list($mountKey, $doExpand, $folderIdentifier) = array_pad(explode('_', $PM, 3), 3, null);
        // In case the folder identifier contains "_", we just need to get the fourth/last parameter
        list($folderIdentifier, $treeName) = array_pad(GeneralUtility::revExplode('_', $folderIdentifier, 2), 2, null);
        return [
            $mountKey,
            $doExpand,
            $folderIdentifier,
            $treeName
        ];
    }

    /**
     * Generates the "PM" string to sent to expand/collapse items
     *
     * @param string $mountKey The mount key / storage UID
     * @param bool $doExpand Whether to expand/collapse
     * @param Folder $folderObject The folder object
     * @param string $treeName The name of the tree
     *
     * @return string
     */
    protected function generateExpandCollapseParameter($mountKey = null, $doExpand = false, Folder $folderObject = null, $treeName = null)
    {
        $parts = [
            $mountKey ?? $this->bank,
            $doExpand == 1 ? 1 : 0,
            $folderObject !== null ? GeneralUtility::md5int($folderObject->getCombinedIdentifier()) : '',
            $treeName ?? $this->treeName
        ];
        return implode('_', $parts);
    }

    /**
     * Gets the AJAX status.
     *
     * @return bool
     */
    public function getAjaxStatus()
    {
        return $this->ajaxStatus;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
