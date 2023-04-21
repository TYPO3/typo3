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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for creating a browsable array/page/folder tree in HTML
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
abstract class AbstractTreeView
{
    // EXTERNAL, static:

    // Holds the current script to reload to.
    /**
     * @var string
     * @deprecated will be removed in TYPO3 v13.0.
     */
    public $thisScript = '';

    // Used if the tree is made of records (not folders for ex.)
    /**
     * @var string
     * @internal since TYPO3 v12 will be marked as protected in TYPO3 v13.0.
     */
    public $title = 'no title';

    /**
     * Needs to be initialized with $GLOBALS['BE_USER']
     * Done by default in init()
     *
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|string
     * @deprecated will be removed in TYPO3 v13.0 as it is not in use anymore.
     */
    public $BE_USER = '';

    /**
     * Database table to get the tree data from.
     * Leave blank if data comes from an array.
     *
     * @var string
     * @internal since TYPO3 v12 will be marked as protected in TYPO3 v13.0.
     */
    public $table = 'pages';

    /**
     * Defines the field of $table which is the parent id field (like pid for table pages).
     *
     * @var string
     * @internal since TYPO3 v12 will be marked as protected in TYPO3 v13.0.
     */
    public $parentField = 'pid';

    /**
     * WHERE clause used for selecting records for the tree. Is set by function init.
     *
     * @see init()
     * @var string
     * @internal since TYPO3 v12, will be marked as protected in TYPO3 v13.0.
     */
    public $clause = '';

    /**
     * Field for ORDER BY. Is set by function init.
     *
     * @see init()
     * @var string
     * @internal since TYPO3 v12, will be marked as protected in TYPO3 v13.0.
     */
    public $orderByFields = '';

    /**
     * Default set of fields selected from the tree table.
     * Make SURE that these fields names listed herein are actually possible to select from $this->table (if that variable is set to a TCA table name)
     *
     * @see addField()
     * @var array
     * @internal since TYPO3 v12, will be marked as protected in TYPO3 v13.0.
     */
    public $fieldArray = [
        'uid',
        'pid',
        'title',
        'is_siteroot',
        'doktype',
        'nav_title',
        'mount_pid',
        'php_tree_stop',
        't3ver_state',
        'hidden',
        'starttime',
        'endtime',
        'fe_group',
        'module',
        'extendToSubpages',
        'nav_hide',
        't3ver_wsid',
    ];

    /**
     * List of other fields which are ALLOWED to set (here, based on the "pages" table!)
     *
     * @see addField()
     * @var string
     * @internal since TYPO3 v12, will be marked as protected in TYPO3 v13.0.
     */
    public $defaultList = 'uid,pid,tstamp,sorting,deleted,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,crdate';

    /**
     * If 1, HTML code is also accumulated in ->tree array during rendering of the tree
     *
     * @var int
     */
    public $makeHTML = 1;

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

    // For both types
    // Tree is accumulated in this variable
    /**
     * @var array
     */
    public $tree = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];

        // @deprecated Copied from determineScriptUrl() to be able to trigger a deprecation entry. Remove in v13.0!
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ($route = $GLOBALS['TYPO3_REQUEST']->getAttribute('route')) instanceof Route
        ) {
            $this->thisScript = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoutePath(
                $route->getPath()
            );
        }
    }

    /**
     * Sets the script url depending on being a module or script request
     * @deprecated will be removed in TYPO3 v13.0.
     */
    protected function determineScriptUrl()
    {
        trigger_error(__CLASS__ . '->' . __METHOD__ . ' will be removed in TYPO3 v13.0.', E_USER_DEPRECATED);
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ($route = $GLOBALS['TYPO3_REQUEST']->getAttribute('route')) instanceof Route
        ) {
            $this->thisScript = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoutePath(
                $route->getPath()
            );
        }
    }

    /**
     * @return string
     * @deprecated will be removed in TYPO3 v13.0. No deprecation is thrown due to
     *             a usage in ElementBrowserPageTreeView (which is also deprecated).
     */
    protected function getThisScript()
    {
        return !str_contains($this->thisScript, '?') ? $this->thisScript . '?' : $this->thisScript . '&';
    }

    /**
     * Initialize the tree class. Needs to be overwritten
     *
     * @param string $clause Record WHERE clause
     * @param string $orderByFields Record ORDER BY field
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
    }

    /**
     * Adds a fieldname to the internal array ->fieldArray
     *
     * @param string $field Field name to
     * @param bool $noCheck If set, the fieldname will be set no matter what. Otherwise the field name must either be found as key in $GLOBALS['TCA'][$table]['columns'] or in the list ->defaultList
     */
    public function addField($field, $noCheck = false)
    {
        if ($noCheck || is_array($GLOBALS['TCA'][$this->table]['columns'][$field] ?? null) || GeneralUtility::inList($this->defaultList, $field)) {
            $this->fieldArray[] = $field;
        }
    }

    /**
     * Resets the tree, recs, ids, ids_hierarchy and orig_ids_hierarchy internal variables. Use it if you need it.
     */
    public function reset()
    {
        $this->tree = [];
        $this->ids = [];
        $this->ids_hierarchy = [];
        $this->orig_ids_hierarchy = [];
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
     * @internal
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::PMicon()
     */
    public function PMicon($row, $a, $c, $nextCount, $isOpen)
    {
        if ($nextCount) {
            return $this->PM_ATagWrap($row['uid'], $isOpen);
        }
        return '';
    }

    /**
     * Wrap the plus/minus icon in a link
     *
     * @param string $bMark If set, the link will have an anchor point (=$bMark) and a name attribute (=$bMark)
     * @param bool $isOpen
     * @return string Link-wrapped input string
     * @internal since TYPO3 v12, will be marked as protected in TYPO3 v13.0.
     */
    public function PM_ATagWrap($bMark = '', $isOpen = false)
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $anchor = $bMark ? '#' . $bMark : '';
        $name = $bMark ? ' name="' . $bMark . '"' : '';
        $aUrl = $this->getThisScript() . $anchor;
        if ($isOpen) {
            $class = 'treelist-control-open';
            $icon = $iconFactory->getIcon('actions-chevron-down', Icon::SIZE_SMALL);
        } else {
            $class = 'treelist-control-collapsed';
            $icon = $iconFactory->getIcon('actions-chevron-right', Icon::SIZE_SMALL);
        }
        return '<a class="treelist-control ' . $class . '" href="' . htmlspecialchars($aUrl) . '"' . $name . '>' . $icon->render(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE) . '</a>';
    }

    /**
     * Adds attributes to image tag.
     *
     * @param string $icon Icon image tag
     * @param string $attr Attributes to add, eg. ' border="0"'
     * @return string Image tag, modified with $attr attributes added.
     * @deprecated will be removed in TYPO3 v13.0.
     */
    public function addTagAttributes($icon, $attr)
    {
        trigger_error(__CLASS__ . '->' . __METHOD__ . ' will be removed in TYPO3 v13.0.', E_USER_DEPRECATED);
        return preg_replace('/ ?\\/?>$/', '', $icon) . ' ' . $attr . ' />';
    }

    /*******************************************
     *
     * tree handling
     *
     *******************************************/
    /**
     * Returns TRUE/FALSE if the next level for $id should be expanded - based on
     * data in $this->stored[][] and ->expandAll flag.
     * Used in subclasses
     *
     * @param int $id Record id/key
     * @return bool
     * @internal
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::expandNext()
     */
    public function expandNext($id)
    {
        return false;
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
     * @deprecated will be removed in TYPO3 v13.0.
     */
    public function getRootIcon($rec)
    {
        trigger_error(__CLASS__ . '->' . __METHOD__ . ' will be removed in TYPO3 v13.0.', E_USER_DEPRECATED);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return $iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render();
    }

    /**
     * Get the icon markup for the row
     *
     * @param array $row The row to get the icon for
     * @return string The icon markup, wrapped into a span tag, with the records title as title attribute
     * @internal since TYPO3 v12, will be marked as protected in TYPO3 v13.0.
     */
    public function getIcon(array $row): string
    {
        $title = $this->getTitleAttrib($row);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon = $row['is_siteroot'] ? $iconFactory->getIcon('apps-pagetree-folder-root', Icon::SIZE_SMALL) : $iconFactory->getIconForRecord($this->table, $row, Icon::SIZE_SMALL);
        return $icon->setTitle($title)->render();
    }

    /**
     * Returns the title for the input record. If blank, a "no title" label (localized) will be returned.
     * Do NOT htmlspecialchar the string from this function - has already been done.
     *
     * @param array $row The input row array (where the key "title" is used for the title)
     * @param int $titleLen Title length (30)
     * @return string The title.
     * @internal since TYPO3 v12, will be marked as protected in TYPO3 v13.0.
     */
    public function getTitleStr($row, $titleLen = 30)
    {
        $title = htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], (int)$titleLen));
        return trim($title) === '' ? '<em>[' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title')) . ']</em>' : $title;
    }

    /**
     * Returns the value for the image "title" attribute
     *
     * @param array $row The input row array (where the key "title" is used for the title)
     * @return string The attribute value (is htmlspecialchared() already)
     * @internal since TYPO3 v12, will be marked as protected in TYPO3 v13.0.
     */
    public function getTitleAttrib($row)
    {
        return htmlspecialchars($row['title']);
    }

    /********************************
     *
     * tree data building
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
        $crazyRecursionLimiter = 9999;
        $idH = [];
        // Traverse the records:
        while ($crazyRecursionLimiter > 0 && ($row = $this->getDataNext($res))) {
            /** @var array $row */
            if (!$this->getBackendUser()->isInWebMount($this->table === 'pages' ? $row : $row['pid'])) {
                // Current record is not within web mount => skip it
                continue;
            }

            $a++;
            $crazyRecursionLimiter--;
            $newID = $row['uid'];
            if ($newID == 0) {
                throw new \RuntimeException('Endless recursion detected: TYPO3 has detected an error in the database. Please fix it manually (e.g. using phpMyAdmin) and change the UID of ' . $this->table . ':0 to a new value. See https://forge.typo3.org/issues/16150 to get more information about a possible cause.', 1294586383);
            }
            // Reserve space.
            $this->tree[] = [];
            end($this->tree);
            // Get the key for this space
            $treeKey = key($this->tree);
            // Accumulate the id of the element in the internal arrays
            $this->ids[] = ($idH[$row['uid']]['uid'] = $row['uid']);
            $this->ids_hierarchy[$depth][] = $row['uid'];
            $this->orig_ids_hierarchy[$depth][] = (isset($row['_ORIG_uid']) && !empty($row['_ORIG_uid'])) ? $row['_ORIG_uid'] : $row['uid'];

            // Make a recursive call to the next level
            $nextLevelDepthData = $depthData . '<span class="treeline-icon treeline-icon-' . ($a === $c ? 'clear' : 'line') . '"></span>';
            $hasSub = $this->expandNext($newID) && !($row['php_tree_stop'] ?? false);
            if ($depth > 1 && $hasSub) {
                $nextCount = $this->getTree($newID, $depth - 1, $nextLevelDepthData);
                if (!empty($this->buffer_idH)) {
                    $idH[$row['uid']]['subrow'] = $this->buffer_idH;
                }
                // Set "did expand" flag
                $isOpen = true;
            } else {
                $nextCount = $this->getCount($newID);
                // Clear "did expand" flag
                $isOpen = false;
            }
            // Set HTML-icons, if any:
            if ($this->makeHTML) {
                $HTML = $this->PMicon($row, $a, $c, $nextCount, $isOpen);
            }
            // Finally, add the row/HTML content to the ->tree array in the reserved key.
            $this->tree[$treeKey] = [
                'row' => $row,
                'HTML' => $HTML,
                'icon' => $this->getIcon($row),
                'invertedDepth' => $depth,
                'depthData' => $depthData,
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
     * @internal
     */
    public function getCount($uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        $count = $queryBuilder
                ->count('uid')
                ->from($this->table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $this->parentField,
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    ),
                    QueryHelper::stripLogicalOperatorPrefix($this->clause)
                )
                ->executeQuery()
                ->fetchOne();

        return (int)$count;
    }

    /**
     * Returns root record for uid (<=0)
     *
     * @return array Array with title/uid keys with values of $this->title/0 (zero)
     * @deprecated will be removed in TYPO3 v13.0.
     */
    public function getRootRecord()
    {
        trigger_error(__CLASS__ . '->' . __METHOD__ . ' will be removed in TYPO3 v13.0.', E_USER_DEPRECATED);
        return ['title' => $this->title, 'uid' => 0];
    }

    /**
     * Getting the tree data: Selecting/Initializing data pointer to items for a certain parent id.
     * For tables: This will make a database query to select all children to "parent"
     * For arrays: This will return key to the ->dataLookup array
     *
     * @param int $parentId parent item id
     *
     * @return mixed Data handle (Tables: An sql-resource, arrays: A parentId integer. -1 is returned if there were NO subLevel.)
     * @internal
     */
    public function getDataInit($parentId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        $queryBuilder
                ->select(...$this->fieldArray)
                ->from($this->table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $this->parentField,
                        $queryBuilder->createNamedParameter($parentId, Connection::PARAM_INT)
                    ),
                    QueryHelper::stripLogicalOperatorPrefix($this->clause)
                );

        foreach (QueryHelper::parseOrderBy($this->orderByFields) as $orderPair) {
            [$fieldName, $order] = $orderPair;
            $queryBuilder->addOrderBy($fieldName, $order);
        }

        return $queryBuilder->executeQuery();
    }

    /**
     * Getting the tree data: Counting elements in resource
     *
     * @param mixed $res Data handle
     * @return int number of items
     * @internal
     * @see getDataInit()
     */
    public function getDataCount(&$res)
    {
        return $res->rowCount();
    }

    /**
     * Getting the tree data: next entry
     *
     * @param mixed $res Data handle
     *
     * @return array|bool item data array OR FALSE if end of elements.
     * @internal
     * @see getDataInit()
     */
    public function getDataNext(&$res)
    {
        while ($row = $res->fetchAssociative()) {
            BackendUtility::workspaceOL($this->table, $row, $this->getBackendUser()->workspace, true);
            if (is_array($row)) {
                break;
            }
        }
        return $row;
    }

    /**
     * Getting the tree data: frees data handle
     *
     * @param mixed $res Data handle
     * @internal
     */
    public function getDataFree(&$res)
    {
        $res->free();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
