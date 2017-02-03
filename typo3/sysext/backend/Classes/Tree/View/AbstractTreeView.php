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

use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\Pagetree\Commands;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Base class for creating a browsable array/page/folder tree in HTML
 */
abstract class AbstractTreeView
{
    // EXTERNAL, static:
    // If set, the first element in the tree is always expanded.
    /**
     * @var int
     */
    public $expandFirst = 0;

    // If set, then ALL items will be expanded, regardless of stored settings.
    /**
     * @var int
     */
    public $expandAll = 0;

    // Holds the current script to reload to.
    /**
     * @var string
     */
    public $thisScript = '';

    // Which HTML attribute to use: alt/title. See init().
    /**
     * @var string
     */
    public $titleAttrib = 'title';

    // If TRUE, no context menu is rendered on icons. If set to "titlelink" the
    // icon is linked as the title is.
    /**
     * @var bool
     */
    public $ext_IconMode = false;

    /**
     * @var bool
     */
    public $ext_showPathAboveMounts = false;

    // If set, the id of the mounts will be added to the internal ids array
    /**
     * @var int
     */
    public $addSelfId = 0;

    // Used if the tree is made of records (not folders for ex.)
    /**
     * @var string
     */
    public $title = 'no title';

    // If TRUE, a default title attribute showing the UID of the record is shown.
    // This cannot be enabled by default because it will destroy many applications
    // where another title attribute is in fact applied later.
    /**
     * @var bool
     */
    public $showDefaultTitleAttribute = false;

    /**
     * Needs to be initialized with $GLOBALS['BE_USER']
     * Done by default in init()
     *
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    public $BE_USER = '';

    /**
     * Needs to be initialized with e.g. $GLOBALS['BE_USER']->returnWebmounts()
     * Default setting in init() is 0 => 0
     * The keys are mount-ids (can be anything basically) and the
     * values are the ID of the root element (COULD be zero or anything else.
     * For pages that would be the uid of the page, zero for the pagetree root.)
     *
     * @var array|NULL
     */
    public $MOUNTS = null;

    /**
     * Database table to get the tree data from.
     * Leave blank if data comes from an array.
     *
     * @var string
     */
    public $table = '';

    /**
     * Defines the field of $table which is the parent id field (like pid for table pages).
     *
     * @var string
     */
    public $parentField = 'pid';

    /**
     * WHERE clause used for selecting records for the tree. Is set by function init.
     * Only makes sense when $this->table is set.
     *
     * @see init()
     * @var string
     */
    public $clause = '';

    /**
     * Field for ORDER BY. Is set by function init.
     * Only makes sense when $this->table is set.
     *
     * @see init()
     * @var string
     */
    public $orderByFields = '';

    /**
     * Default set of fields selected from the tree table.
     * Make SURE that these fields names listed herein are actually possible to select from $this->table (if that variable is set to a TCA table name)
     *
     * @see addField()
     * @var array
     */
    public $fieldArray = ['uid', 'pid', 'title'];

    /**
     * List of other fields which are ALLOWED to set (here, based on the "pages" table!)
     *
     * @see addField()
     * @var array
     */
    public $defaultList = 'uid,pid,tstamp,sorting,deleted,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,crdate,cruser_id';

    /**
     * Unique name for the tree.
     * Used as key for storing the tree into the BE users settings.
     * Used as key to pass parameters in links.
     * MUST NOT contain underscore chars.
     * etc.
     *
     * @var string
     */
    public $treeName = '';

    /**
     * A prefix for table cell id's which will be wrapped around an item.
     * Can be used for highlighting by JavaScript.
     * Needs to be unique if multiple trees are on one HTML page.
     *
     * @see printTree()
     * @var string
     */
    public $domIdPrefix = 'row';

    /**
     * If TRUE, HTML code is also accumulated in ->tree array during rendering of the tree.
     * If 2, then also the icon prefix code (depthData) is stored
     *
     * @var int
     */
    public $makeHTML = 1;

    /**
     * If TRUE, records as selected will be stored internally in the ->recs array
     *
     * @var int
     */
    public $setRecs = 0;

    /**
     * Sets the associative array key which identifies a new sublevel if arrays are used for trees.
     * This value has formerly been "subLevel" and "--sublevel--"
     *
     * @var string
     */
    public $subLevelID = '_SUB_LEVEL';

    // *********
    // Internal
    // *********
    // For record trees:
    // one-dim array of the uid's selected.
    /**
     * @var array
     */
    public $ids = [];

    // The hierarchy of element uids
    /**
     * @var array
     */
    public $ids_hierarchy = [];

    // The hierarchy of versioned element uids
    /**
     * @var array
     */
    public $orig_ids_hierarchy = [];

    // Temporary, internal array
    /**
     * @var array
     */
    public $buffer_idH = [];

    // For FOLDER trees:
    // Special UIDs for folders (integer-hashes of paths)
    /**
     * @var array
     */
    public $specUIDmap = [];

    // For arrays:
    // Holds the input data array
    /**
     * @var bool
     */
    public $data = false;

    // Holds an index with references to the data array.
    /**
     * @var bool
     */
    public $dataLookup = false;

    // For both types
    // Tree is accumulated in this variable
    /**
     * @var array
     */
    public $tree = [];

    // Holds (session stored) information about which items in the tree are unfolded and which are not.
    /**
     * @var array
     */
    public $stored = [];

    // Points to the current mountpoint key
    /**
     * @var int
     */
    public $bank = 0;

    // Accumulates the displayed records.
    /**
     * @var array
     */
    public $recs = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->determineScriptUrl();
    }

    /**
     * Sets the script url depending on being a module or script request
     */
    protected function determineScriptUrl()
    {
        if ($routePath = GeneralUtility::_GP('route')) {
            $router = GeneralUtility::makeInstance(Router::class);
            $route = $router->match($routePath);
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $this->thisScript = (string)$uriBuilder->buildUriFromRoute($route->getOption('_identifier'));
        } elseif ($moduleName = GeneralUtility::_GP('M')) {
            $this->thisScript = BackendUtility::getModuleUrl($moduleName);
        } else {
            $this->thisScript = GeneralUtility::getIndpEnv('SCRIPT_NAME');
        }
    }

    /**
     * @return string
     */
    protected function getThisScript()
    {
        return strpos($this->thisScript, '?') === false ? $this->thisScript . '?' : $this->thisScript . '&';
    }

    /**
     * Initialize the tree class. Needs to be overwritten
     *
     * @param string $clause Record WHERE clause
     * @param string $orderByFields Record ORDER BY field
     * @return void
     */
    public function init($clause = '', $orderByFields = '')
    {
        // Setting BE_USER by default
        $this->BE_USER = $GLOBALS['BE_USER'];
        // Setting clause
        if ($clause) {
            $this->clause = $clause;
        }
        if ($orderByFields) {
            $this->orderByFields = $orderByFields;
        }
        if (!is_array($this->MOUNTS)) {
            // Dummy
            $this->MOUNTS = [0 => 0];
        }
        // Sets the tree name which is used to identify the tree, used for JavaScript and other things
        $this->treeName = str_replace('_', '', $this->treeName ?: $this->table);
        // Setting this to FALSE disables the use of array-trees by default
        $this->data = false;
        $this->dataLookup = false;
    }

    /**
     * Adds a fieldname to the internal array ->fieldArray
     *
     * @param string $field Field name to
     * @param bool $noCheck If set, the fieldname will be set no matter what. Otherwise the field name must either be found as key in $GLOBALS['TCA'][$table]['columns'] or in the list ->defaultList
     * @return void
     */
    public function addField($field, $noCheck = false)
    {
        if ($noCheck || is_array($GLOBALS['TCA'][$this->table]['columns'][$field]) || GeneralUtility::inList($this->defaultList, $field)) {
            $this->fieldArray[] = $field;
        }
    }

    /**
     * Resets the tree, recs, ids, ids_hierarchy and orig_ids_hierarchy internal variables. Use it if you need it.
     *
     * @return void
     */
    public function reset()
    {
        $this->tree = [];
        $this->recs = [];
        $this->ids = [];
        $this->ids_hierarchy = [];
        $this->orig_ids_hierarchy = [];
    }

    /*******************************************
     *
     * output
     *
     *******************************************/
    /**
     * Will create and return the HTML code for a browsable tree
     * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
     *
     * @return string HTML code for the browsable tree
     */
    public function getBrowsableTree()
    {
        // Get stored tree structure AND updating it if needed according to incoming PM GET var.
        $this->initializePositionSaving();
        // Init done:
        $lastMountPointPid = 0;
        $treeArr = [];
        // Traverse mounts:
        foreach ($this->MOUNTS as $idx => $uid) {
            // Set first:
            $this->bank = $idx;
            $isOpen = $this->stored[$idx][$uid] || $this->expandFirst;
            // Save ids while resetting everything else.
            $curIds = $this->ids;
            $this->reset();
            $this->ids = $curIds;
            // Set PM icon for root of mount:
            $cmd = $this->bank . '_' . ($isOpen ? '0_' : '1_') . $uid . '_' . $this->treeName;

            $firstHtml = $this->PM_ATagWrap('', $cmd, '', $isOpen);
            // Preparing rootRec for the mount
            if ($uid) {
                $rootRec = $this->getRecord($uid);
                if (is_array($rootRec)) {
                    $firstHtml .= $this->getIcon($rootRec);
                }

                if ($this->ext_showPathAboveMounts) {
                    $mountPointPid = $rootRec['pid'];
                    if ($lastMountPointPid !== $mountPointPid) {
                        $title = Commands::getMountPointPath($mountPointPid);
                        $this->tree[] = ['isMountPointPath' => true, 'title' => $title];
                    }
                    $lastMountPointPid = $mountPointPid;
                }
            } else {
                // Artificial record for the tree root, id=0
                $rootRec = $this->getRootRecord();
                $firstHtml .= $this->getRootIcon($rootRec);
            }
            if (is_array($rootRec)) {
                // In case it was swapped inside getRecord due to workspaces.
                $uid = $rootRec['uid'];
                // Add the root of the mount to ->tree
                $this->tree[] = ['HTML' => $firstHtml, 'row' => $rootRec, 'hasSub' => $isOpen, 'bank' => $this->bank];
                // If the mount is expanded, go down:
                if ($isOpen) {
                    $depthData = '<span class="treeline-icon treeline-icon-clear"></span>';
                    if ($this->addSelfId) {
                        $this->ids[] = $uid;
                    }
                    $this->getTree($uid, 999, $depthData);
                }
                // Add tree:
                $treeArr = array_merge($treeArr, $this->tree);
            }
        }
        return $this->printTree($treeArr);
    }

    /**
     * Compiles the HTML code for displaying the structure found inside the ->tree array
     *
     * @param array|string $treeArr "tree-array" - if blank string, the internal ->tree array is used.
     * @return string The HTML code for the tree
     */
    public function printTree($treeArr = '')
    {
        $titleLen = (int)$this->BE_USER->uc['titleLen'];
        if (!is_array($treeArr)) {
            $treeArr = $this->tree;
        }
        $out = '';
        $closeDepth = [];
        foreach ($treeArr as $treeItem) {
            $classAttr = '';
            if ($treeItem['isFirst']) {
                $out .= '<ul class="list-tree">';
            }

            // Add CSS classes to the list item
            if ($treeItem['hasSub']) {
                $classAttr .= ' list-tree-control-open';
            }

            $idAttr = htmlspecialchars($this->domIdPrefix . $this->getId($treeItem['row']) . '_' . $treeItem['bank']);
            $out .= '
				<li id="' . $idAttr . '"' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '') . '>
					<span class="list-tree-group">
						<span class="list-tree-icon">' . $treeItem['HTML'] . '</span>
						<span class="list-tree-title">' . $this->wrapTitle($this->getTitleStr($treeItem['row'], $titleLen), $treeItem['row'], $treeItem['bank']) . '</span>
					</span>';

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
        $out = '<ul class="list-tree list-tree-root list-tree-root-clean">' . $out . '</ul>';
        return $out;
    }

    /*******************************************
     *
     * rendering parts
     *
     *******************************************/
    /**
     * Generate the plus/minus icon for the browsable tree.
     *
     * @param array $row Record for the entry
     * @param int $a The current entry number
     * @param int $c The total number of entries. If equal to $a, a "bottom" element is returned.
     * @param int $nextCount The number of sub-elements to the current element.
     * @param bool $isOpen The element was expanded to render subelements if this flag is set.
     * @return string Image tag with the plus/minus icon.
     * @access private
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::PMicon()
     */
    public function PMicon($row, $a, $c, $nextCount, $isOpen)
    {
        if ($nextCount) {
            $cmd = $this->bank . '_' . ($isOpen ? '0_' : '1_') . $row['uid'] . '_' . $this->treeName;
            $bMark = $this->bank . '_' . $row['uid'];
            return $this->PM_ATagWrap('', $cmd, $bMark, $isOpen);
        } else {
            return '';
        }
    }

    /**
     * Wrap the plus/minus icon in a link
     *
     * @param string $icon HTML string to wrap, probably an image tag.
     * @param string $cmd Command for 'PM' get var
     * @param string $bMark If set, the link will have an anchor point (=$bMark) and a name attribute (=$bMark)
     * @param bool $isOpen
     * @return string Link-wrapped input string
     * @access private
     */
    public function PM_ATagWrap($icon, $cmd, $bMark = '', $isOpen = false)
    {
        if ($this->thisScript) {
            $anchor = $bMark ? '#' . $bMark : '';
            $name = $bMark ? ' name="' . $bMark . '"' : '';
            $aUrl = $this->getThisScript() . 'PM=' . $cmd . $anchor;
            return '<a class="list-tree-control ' . ($isOpen ? 'list-tree-control-open' : 'list-tree-control-closed') . '" href="' . htmlspecialchars($aUrl) . '"' . $name . '><i class="fa"></i></a>';
        } else {
            return $icon;
        }
    }

    /**
     * Wrapping $title in a-tags.
     *
     * @param string $title Title string
     * @param array $row Item record
     * @param int $bank Bank pointer (which mount point number)
     * @return string
     * @access private
     */
    public function wrapTitle($title, $row, $bank = 0)
    {
        $aOnClick = 'return jumpTo(' . GeneralUtility::quoteJSvalue($this->getJumpToParam($row)) . ',this,' . GeneralUtility::quoteJSvalue($this->domIdPrefix . $this->getId($row)) . ',' . $bank . ');';
        return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a>';
    }

    /**
     * Wrapping the image tag, $icon, for the row, $row (except for mount points)
     *
     * @param string $icon The image tag for the icon
     * @param array $row The row for the current element
     * @return string The processed icon input value.
     * @access private
     */
    public function wrapIcon($icon, $row)
    {
        return $icon;
    }

    /**
     * Adds attributes to image tag.
     *
     * @param string $icon Icon image tag
     * @param string $attr Attributes to add, eg. ' border="0"'
     * @return string Image tag, modified with $attr attributes added.
     */
    public function addTagAttributes($icon, $attr)
    {
        return preg_replace('/ ?\\/?>$/', '', $icon) . ' ' . $attr . ' />';
    }

    /**
     * Adds a red "+" to the input string, $str, if the field "php_tree_stop" in the $row (pages) is set
     *
     * @param string $str Input string, like a page title for the tree
     * @param array $row record row with "php_tree_stop" field
     * @return string Modified string
     * @access private
     */
    public function wrapStop($str, $row)
    {
        if ($row['php_tree_stop']) {
            $str .= '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(['setTempDBmount' => $row['uid']])) . '" class="text-danger">+</a> ';
        }
        return $str;
    }

    /*******************************************
     *
     * tree handling
     *
     *******************************************/
    /**
     * Returns TRUE/FALSE if the next level for $id should be expanded - based on
     * data in $this->stored[][] and ->expandAll flag.
     * Extending parent function
     *
     * @param int $id Record id/key
     * @return bool
     * @access private
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::expandNext()
     */
    public function expandNext($id)
    {
        return $this->stored[$this->bank][$id] || $this->expandAll ? 1 : 0;
    }

    /**
     * Get stored tree structure AND updating it if needed according to incoming PM GET var.
     *
     * @return void
     * @access private
     */
    public function initializePositionSaving()
    {
        // Get stored tree structure:
        $this->stored = unserialize($this->BE_USER->uc['browseTrees'][$this->treeName]);
        // PM action
        // (If an plus/minus icon has been clicked, the PM GET var is sent and we
        // must update the stored positions in the tree):
        // 0: mount key, 1: set/clear boolean, 2: item ID (cannot contain "_"), 3: treeName
        $PM = explode('_', GeneralUtility::_GP('PM'));
        if (count($PM) === 4 && $PM[3] == $this->treeName) {
            if (isset($this->MOUNTS[$PM[0]])) {
                // set
                if ($PM[1]) {
                    $this->stored[$PM[0]][$PM[2]] = 1;
                    $this->savePosition();
                } else {
                    unset($this->stored[$PM[0]][$PM[2]]);
                    $this->savePosition();
                }
            }
        }
    }

    /**
     * Saves the content of ->stored (keeps track of expanded positions in the tree)
     * $this->treeName will be used as key for BE_USER->uc[] to store it in
     *
     * @return void
     * @access private
     */
    public function savePosition()
    {
        $this->BE_USER->uc['browseTrees'][$this->treeName] = serialize($this->stored);
        $this->BE_USER->writeUC();
    }

    /******************************
     *
     * Functions that might be overwritten by extended classes
     *
     ********************************/
    /**
     * Returns the root icon for a tree/mountpoint (defaults to the globe)
     *
     * @param array $rec Record for root.
     * @return string Icon image tag.
     */
    public function getRootIcon($rec)
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return $this->wrapIcon($iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render(), $rec);
    }

    /**
     * Get icon for the row.
     *
     * @param array|int $row Item row or uid
     * @return string Image tag.
     */
    public function getIcon($row)
    {
        if (is_int($row)) {
            $row = BackendUtility::getRecord($this->table, $row);
        }
        $title = $this->showDefaultTitleAttribute ? htmlspecialchars('UID: ' . $row['uid']) : $this->getTitleAttrib($row);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon = '<span title="' . $title . '">' . $iconFactory->getIconForRecord($this->table, $row, Icon::SIZE_SMALL)->render() . '</span>';
        return $this->wrapIcon($icon, $row);
    }

    /**
     * Returns the title for the input record. If blank, a "no title" label (localized) will be returned.
     * Do NOT htmlspecialchar the string from this function - has already been done.
     *
     * @param array $row The input row array (where the key "title" is used for the title)
     * @param int $titleLen Title length (30)
     * @return string The title.
     */
    public function getTitleStr($row, $titleLen = 30)
    {
        $title = htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], $titleLen));
        $title = trim($row['title']) === '' ? '<em>[' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', true) . ']</em>' : $title;
        return $title;
    }

    /**
     * Returns the value for the image "title" attribute
     *
     * @param array $row The input row array (where the key "title" is used for the title)
     * @return string The attribute value (is htmlspecialchared() already)
     * @see wrapIcon()
     */
    public function getTitleAttrib($row)
    {
        return htmlspecialchars($row['title']);
    }

    /**
     * Returns the id from the record (typ. uid)
     *
     * @param array $row Record array
     * @return int The "uid" field value.
     */
    public function getId($row)
    {
        return $row['uid'];
    }

    /**
     * Returns jump-url parameter value.
     *
     * @param array $row The record array.
     * @return string The jump-url parameter.
     */
    public function getJumpToParam($row)
    {
        return $this->getId($row);
    }

    /********************************
     *
     * tree data buidling
     *
     ********************************/
    /**
     * Fetches the data for the tree
     *
     * @param int $uid item id for which to select subitems (parent id)
     * @param int $depth Max depth (recursivity limit)
     * @param string $depthData HTML-code prefix for recursive calls.

     * @return int The count of items on the level
     */
    public function getTree($uid, $depth = 999, $depthData = '')
    {
        // Buffer for id hierarchy is reset:
        $this->buffer_idH = [];
        // Init vars
        $depth = (int)$depth;
        $HTML = '';
        $a = 0;
        $res = $this->getDataInit($uid);
        $c = $this->getDataCount($res);
        $crazyRecursionLimiter = 999;
        $idH = [];
        // Traverse the records:
        while ($crazyRecursionLimiter > 0 && ($row = $this->getDataNext($res))) {
            $pageUid = ($this->table === 'pages') ? $row['uid'] : $row['pid'];
            if (!$this->getBackendUser()->isInWebMount($pageUid)) {
                // Current record is not within web mount => skip it
                continue;
            }

            $a++;
            $crazyRecursionLimiter--;
            $newID = $row['uid'];
            if ($newID == 0) {
                throw new \RuntimeException('Endless recursion detected: TYPO3 has detected an error in the database. Please fix it manually (e.g. using phpMyAdmin) and change the UID of ' . $this->table . ':0 to a new value. See http://forge.typo3.org/issues/16150 to get more information about a possible cause.', 1294586383);
            }
            // Reserve space.
            $this->tree[] = [];
            end($this->tree);
            // Get the key for this space
            $treeKey = key($this->tree);
            // If records should be accumulated, do so
            if ($this->setRecs) {
                $this->recs[$row['uid']] = $row;
            }
            // Accumulate the id of the element in the internal arrays
            $this->ids[] = ($idH[$row['uid']]['uid'] = $row['uid']);
            $this->ids_hierarchy[$depth][] = $row['uid'];
            $this->orig_ids_hierarchy[$depth][] = $row['_ORIG_uid'] ?: $row['uid'];

            // Make a recursive call to the next level
            $nextLevelDepthData = $depthData . '<span class="treeline-icon treeline-icon-' . ($a === $c ? 'clear' : 'line') . '"></span>';
            $hasSub = $this->expandNext($newID) && !$row['php_tree_stop'];
            if ($depth > 1 && $hasSub) {
                $nextCount = $this->getTree($newID, $depth - 1, $nextLevelDepthData);
                if (!empty($this->buffer_idH)) {
                    $idH[$row['uid']]['subrow'] = $this->buffer_idH;
                }
                // Set "did expand" flag
                $isOpen = 1;
            } else {
                $nextCount = $this->getCount($newID);
                // Clear "did expand" flag
                $isOpen = 0;
            }
            // Set HTML-icons, if any:
            if ($this->makeHTML) {
                $HTML = $this->PMicon($row, $a, $c, $nextCount, $isOpen) . $this->wrapStop($this->getIcon($row), $row);
            }
            // Finally, add the row/HTML content to the ->tree array in the reserved key.
            $this->tree[$treeKey] = [
                'row' => $row,
                'HTML' => $HTML,
                'invertedDepth' => $depth,
                'depthData' => $depthData,
                'bank' => $this->bank,
                'hasSub' => $nextCount && $hasSub,
                'isFirst' => $a === 1,
                'isLast' => $a === $c,
            ];
        }

        $this->getDataFree($res);
        $this->buffer_idH = $idH;
        return $c;
    }

    /********************************
     *
     * Data handling
     * Works with records and arrays
     *
     ********************************/
    /**
     * Returns the number of records having the parent id, $uid
     *
     * @param int $uid Id to count subitems for
     * @return int
     * @access private
     */
    public function getCount($uid)
    {
        if (is_array($this->data)) {
            $res = $this->getDataInit($uid);
            return $this->getDataCount($res);
        } else {
            $db = $this->getDatabaseConnection();
            $where = $this->parentField . '=' . $db->fullQuoteStr($uid, $this->table) . BackendUtility::deleteClause($this->table) . BackendUtility::versioningPlaceholderClause($this->table) . $this->clause;
            return $db->exec_SELECTcountRows('uid', $this->table, $where);
        }
    }

    /**
     * Returns root record for uid (<=0)
     *
     * @return array Array with title/uid keys with values of $this->title/0 (zero)
     */
    public function getRootRecord()
    {
        return ['title' => $this->title, 'uid' => 0];
    }

    /**
     * Returns the record for a uid.
     * For tables: Looks up the record in the database.
     * For arrays: Returns the fake record for uid id.
     *
     * @param int $uid UID to look up
     * @return array The record
     */
    public function getRecord($uid)
    {
        if (is_array($this->data)) {
            return $this->dataLookup[$uid];
        } else {
            return BackendUtility::getRecordWSOL($this->table, $uid);
        }
    }

    /**
     * Getting the tree data: Selecting/Initializing data pointer to items for a certain parent id.
     * For tables: This will make a database query to select all children to "parent"
     * For arrays: This will return key to the ->dataLookup array
     *
     * @param int $parentId parent item id
     *
     * @return mixed Data handle (Tables: An sql-resource, arrays: A parentId integer. -1 is returned if there were NO subLevel.)
     * @access private
     */
    public function getDataInit($parentId)
    {
        if (is_array($this->data)) {
            if (!is_array($this->dataLookup[$parentId][$this->subLevelID])) {
                $parentId = -1;
            } else {
                reset($this->dataLookup[$parentId][$this->subLevelID]);
            }
            return $parentId;
        } else {
            $db = $this->getDatabaseConnection();
            $where = $this->parentField . '=' . $db->fullQuoteStr($parentId, $this->table) . BackendUtility::deleteClause($this->table) . BackendUtility::versioningPlaceholderClause($this->table) . $this->clause;
            return $db->exec_SELECTquery(implode(',', $this->fieldArray), $this->table, $where, '', $this->orderByFields);
        }
    }

    /**
     * Getting the tree data: Counting elements in resource
     *
     * @param mixed $res Data handle
     * @return int number of items
     * @access private
     * @see getDataInit()
     */
    public function getDataCount(&$res)
    {
        if (is_array($this->data)) {
            return count($this->dataLookup[$res][$this->subLevelID]);
        } else {
            return $this->getDatabaseConnection()->sql_num_rows($res);
        }
    }

    /**
     * Getting the tree data: next entry
     *
     * @param mixed $res Data handle
     *
     * @return array item data array OR FALSE if end of elements.
     * @access private
     * @see getDataInit()
     */
    public function getDataNext(&$res)
    {
        if (is_array($this->data)) {
            if ($res < 0) {
                $row = false;
            } else {
                list(, $row) = each($this->dataLookup[$res][$this->subLevelID]);
            }
            return $row;
        } else {
            while ($row = @$this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                BackendUtility::workspaceOL($this->table, $row, $this->BE_USER->workspace, true);
                if (is_array($row)) {
                    break;
                }
            }
            return $row;
        }
    }

    /**
     * Getting the tree data: frees data handle
     *
     * @param mixed $res Data handle
     * @return void
     * @access private
     */
    public function getDataFree(&$res)
    {
        if (!is_array($this->data)) {
            $this->getDatabaseConnection()->sql_free_result($res);
        }
    }

    /**
     * Used to initialize class with an array to browse.
     * The array inputted will be traversed and an internal index for lookup is created.
     * The keys of the input array are perceived as "uid"s of records which means that keys GLOBALLY must be unique like uids are.
     * "uid" and "pid" "fakefields" are also set in each record.
     * All other fields are optional.
     *
     * @param array $dataArr The input array, see examples below in this script.
     * @param bool $traverse Internal, for recursion.
     * @param int $pid Internal, for recursion.
     * @return void
     */
    public function setDataFromArray(&$dataArr, $traverse = false, $pid = 0)
    {
        if (!$traverse) {
            $this->data = &$dataArr;
            $this->dataLookup = [];
            // Add root
            $this->dataLookup[0][$this->subLevelID] = &$dataArr;
        }
        foreach ($dataArr as $uid => $val) {
            $dataArr[$uid]['uid'] = $uid;
            $dataArr[$uid]['pid'] = $pid;
            // Gives quick access to id's
            $this->dataLookup[$uid] = &$dataArr[$uid];
            if (is_array($val[$this->subLevelID])) {
                $this->setDataFromArray($dataArr[$uid][$this->subLevelID], true, $uid);
            }
        }
    }

    /**
     * Sets the internal data arrays
     *
     * @param array $treeArr Content for $this->data
     * @param array $treeLookupArr Content for $this->dataLookup
     * @return void
     */
    public function setDataFromTreeArray(&$treeArr, &$treeLookupArr)
    {
        $this->data = &$treeArr;
        $this->dataLookup = &$treeLookupArr;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
