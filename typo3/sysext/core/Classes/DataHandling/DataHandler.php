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

namespace TYPO3\CMS\Core\DataHandling;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform as PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SQLServer2012Platform as SQLServerPlatform;
use Doctrine\DBAL\Types\IntegerType;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowLoopException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowRootException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidPointerFieldValueException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\DataHandling\Localization\DataMapProcessor;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\SysLog\Action\Cache as SystemLogCacheAction;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * The main data handler class which takes care of correctly updating and inserting records.
 * This class was formerly known as TCEmain.
 *
 * This is the TYPO3 Core Engine class for manipulation of the database
 * This class is used by eg. the tce_db BE route (SimpleDataHandlerController) which provides an interface for POST forms to this class.
 *
 * Dependencies:
 * - $GLOBALS['TCA'] must exist
 * - $GLOBALS['LANG'] must exist
 *
 * Also see document 'TYPO3 Core API' for details.
 */
class DataHandler implements LoggerAwareInterface
{
    use LogDataTrait;
    use LoggerAwareTrait;

    // *********************
    // Public variables you can configure before using the class:
    // *********************
    /**
     * If TRUE, the default log-messages will be stored. This should not be necessary if the locallang-file for the
     * log-display is properly configured. So disabling this will just save some database-space as the default messages are not saved.
     *
     * @var bool
     */
    public $storeLogMessages = true;

    /**
     * If TRUE, actions are logged to sys_log.
     *
     * @var bool
     */
    public $enableLogging = true;

    /**
     * If TRUE, the datamap array is reversed in the order, which is a nice thing if you're creating a whole new
     * bunch of records.
     *
     * @var bool
     */
    public $reverseOrder = false;

    /**
     * If TRUE, only fields which are different from the database values are saved! In fact, if a whole input array
     * is similar, it's not saved then.
     *
     * @var bool
     * @internal should only be used from within TYPO3 Core
     */
    public $checkSimilar = true;

    /**
     * This will read the record after having updated or inserted it. If anything is not properly submitted an error
     * is written to the log. This feature consumes extra time by selecting records
     *
     * @var bool
     */
    public $checkStoredRecords = true;

    /**
     * If set, values '' and 0 will equal each other when the stored records are checked.
     *
     * @var bool
     */
    public $checkStoredRecords_loose = true;

    /**
     * If set, then the 'hideAtCopy' flag for tables will be ignored.
     *
     * @var bool
     */
    public $neverHideAtCopy = false;

    /**
     * If set, then the TCE class has been instantiated during an import action of a T3D
     *
     * @var bool
     */
    public $isImporting = false;

    /**
     * If set, then transformations are NOT performed on the input.
     *
     * @var bool
     */
    public $dontProcessTransformations = false;

    /**
     * Will distinguish between translations (with parent) and localizations (without parent) while still using the same methods to copy the records
     * TRUE: translation of a record connected to the default language
     * FALSE: localization of a record without connection to the default language
     *
     * @var bool
     */
    protected $useTransOrigPointerField = true;

    /**
     * If TRUE, workspace restrictions are bypassed on edit and create actions (process_datamap()).
     * YOU MUST KNOW what you do if you use this feature!
     *
     * @var bool
     * @internal should only be used from within TYPO3 Core
     */
    public $bypassWorkspaceRestrictions = false;

    /**
     * If TRUE, access check, check for deleted etc. for records is bypassed.
     * YOU MUST KNOW what you are doing if you use this feature!
     *
     * @var bool
     */
    public $bypassAccessCheckForRecords = false;

    /**
     * Comma-separated list. This list of tables decides which tables will be copied. If empty then none will.
     * If '*' then all will (that the user has permission to of course)
     *
     * @var string
     * @internal should only be used from within TYPO3 Core
     */
    public $copyWhichTables = '*';

    /**
     * If 0 then branch is NOT copied.
     * If 1 then pages on the 1st level is copied.
     * If 2 then pages on the second level is copied ... and so on
     *
     * @var int
     */
    public $copyTree = 0;

    /**
     * [table][fields]=value: New records are created with default values and you can set this array on the
     * form $defaultValues[$table][$field] = $value to override the default values fetched from TCA.
     * If ->setDefaultsFromUserTS is called UserTSconfig default values will overrule existing values in this array
     * (thus UserTSconfig overrules externally set defaults which overrules TCA defaults)
     *
     * @var array
     * @internal should only be used from within TYPO3 Core
     */
    public $defaultValues = [];

    /**
     * [table][fields]=value: You can set this array on the form $overrideValues[$table][$field] = $value to
     * override the incoming data. You must set this externally. You must make sure the fields in this array are also
     * found in the table, because it's not checked. All columns can be set by this array!
     *
     * @var array
     * @internal should only be used from within TYPO3 Core
     */
    public $overrideValues = [];

    /**
     * If entries are set in this array corresponding to fields for update, they are ignored and thus NOT updated.
     * You could set this array from a series of checkboxes with value=0 and hidden fields before the checkbox with 1.
     * Then an empty checkbox will disable the field.
     *
     * @var array
     * @internal should only be used from within TYPO3 Core
     */
    public $data_disableFields = [];

    /**
     * Use this array to validate suggested uids for tables by setting [table]:[uid]. This is a dangerous option
     * since it will force the inserted record to have a certain UID. The value just have to be TRUE, but if you set
     * it to "DELETE" it will make sure any record with that UID will be deleted first (raw delete).
     * The option is used for import of T3D files when synchronizing between two mirrored servers.
     * As a security measure this feature is available only for Admin Users (for now)
     *
     * @var array
     */
    public $suggestedInsertUids = [];

    /**
     * Object. Call back object for FlexForm traversal. Useful when external classes wants to use the
     * iteration functions inside DataHandler for traversing a FlexForm structure.
     *
     * @var object
     * @internal should only be used from within TYPO3 Core
     */
    public $callBackObj;

    /**
     * A string which can be used as correlationId for RecordHistory entries.
     * The string can later be used to rollback multiple changes at once.
     *
     * @var CorrelationId|null
     */
    protected $correlationId;

    // *********************
    // Internal variables (mapping arrays) which can be used (read-only) from outside
    // *********************
    /**
     * Contains mapping of auto-versionized records.
     *
     * @var array<string, array<int, string>>
     * @internal should only be used from within TYPO3 Core
     */
    public $autoVersionIdMap = [];

    /**
     * When new elements are created, this array contains a map between their "NEW..." string IDs and the eventual UID they got when stored in database
     *
     * @var array
     */
    public $substNEWwithIDs = [];

    /**
     * Like $substNEWwithIDs, but where each old "NEW..." id is mapped to the table it was from.
     *
     * @var array
     * @internal should only be used from within TYPO3 Core
     */
    public $substNEWwithIDs_table = [];

    /**
     * Holds the tables and there the ids of newly created child records from IRRE
     *
     * @var array
     * @internal should only be used from within TYPO3 Core
     */
    public $newRelatedIDs = [];

    /**
     * This array is the sum of all copying operations in this class.
     *
     * @var array
     * @internal should only be used from within TYPO3 Core
     */
    public $copyMappingArray_merged = [];

    /**
     * Per-table array with UIDs that have been deleted.
     *
     * @var array
     */
    protected $deletedRecords = [];

    /**
     * Errors are collected in this variable.
     *
     * @var array
     * @internal should only be used from within TYPO3 Core
     */
    public $errorLog = [];

    /**
     * Fields from the pages-table for which changes will trigger a pagetree refresh
     *
     * @var array
     */
    public $pagetreeRefreshFieldsFromPages = ['pid', 'sorting', 'deleted', 'hidden', 'title', 'doktype', 'is_siteroot', 'fe_group', 'nav_hide', 'nav_title', 'module', 'starttime', 'endtime', 'content_from_pid', 'extendToSubpages'];

    /**
     * Indicates whether the pagetree needs a refresh because of important changes
     *
     * @var bool
     * @internal should only be used from within TYPO3 Core
     */
    public $pagetreeNeedsRefresh = false;

    // *********************
    // Internal Variables, do not touch.
    // *********************

    // Variables set in init() function:

    /**
     * The user-object the script uses. If not set from outside, this is set to the current global $BE_USER.
     *
     * @var BackendUserAuthentication
     */
    public $BE_USER;

    /**
     * Will be set to uid of be_user executing this script
     *
     * @var int
     * @internal should only be used from within TYPO3 Core
     */
    public $userid;

    /**
     * Will be set to username of be_user executing this script
     *
     * @var string
     * @internal should only be used from within TYPO3 Core
     */
    public $username;

    /**
     * Will be set if user is admin
     *
     * @var bool
     * @internal should only be used from within TYPO3 Core
     */
    public $admin;

    /**
     * @var PagePermissionAssembler
     */
    protected $pagePermissionAssembler;

    /**
     * The list of <table>-<fields> that cannot be edited by user. This is compiled from TCA/exclude-flag combined with non_exclude_fields for the user.
     *
     * @var array
     */
    protected $excludedTablesAndFields = [];

    /**
     * Data submitted from the form view, used to control behaviours,
     * e.g. this is used to activate/deactivate fields and thus store NULL values
     *
     * @var array
     */
    protected $control = [];

    /**
     * Set with incoming data array
     *
     * @var array<int|string, array<int|string, array>>
     */
    public $datamap = [];

    /**
     * Set with incoming cmd array
     *
     * @var array<string, array<int|string, array>>
     */
    public $cmdmap = [];

    /**
     * List of changed old record ids to new records ids
     *
     * @var array
     */
    protected $mmHistoryRecords = [];

    /**
     * List of changed old record ids to new records ids
     *
     * @var array
     */
    protected $historyRecords = [];

    // Internal static:

    /**
     * The interval between sorting numbers used with tables with a 'sorting' field defined.
     *
     * Min 1, should be power of 2
     *
     * @var int
     * @internal should only be used from within TYPO3 Core
     */
    public $sortIntervals = 256;

    // Internal caching arrays
    /**
     * User by function checkRecordInsertAccess() to store whether a record can be inserted on a page id
     *
     * @var array
     */
    protected $recInsertAccessCache = [];

    /**
     * Caching array for check of whether records are in a webmount
     *
     * @var array
     */
    protected $isRecordInWebMount_Cache = [];

    /**
     * Caching array for page ids in webmounts
     *
     * @var array
     */
    protected $isInWebMount_Cache = [];

    /**
     * Used for caching page records in pageInfo()
     *
     * @var array<int, array<string, array>>
     */
    protected $pageCache = [];

    // Other arrays:
    /**
     * For accumulation of MM relations that must be written after new records are created.
     *
     * @var array
     * @internal
     */
    public $dbAnalysisStore = [];

    /**
     * Used for tracking references that might need correction after operations
     *
     * @var array<string, array<int, array>>
     * @internal
     */
    public $registerDBList = [];

    /**
     * Used for tracking references that might need correction in pid field after operations (e.g. IRRE)
     *
     * @var array
     * @internal
     */
    public $registerDBPids = [];

    /**
     * Used by the copy action to track the ids of new pages so subpages are correctly inserted!
     * THIS is internally cleared for each executed copy operation! DO NOT USE THIS FROM OUTSIDE!
     * Read from copyMappingArray_merged instead which is accumulating this information.
     *
     * NOTE: This is used by some outside scripts (e.g. hooks), as the results in $copyMappingArray_merged
     * are only available after an action has been completed.
     *
     * @var array<string, array>
     * @internal
     */
    public $copyMappingArray = [];

    /**
     * Array used for remapping uids and values at the end of process_datamap
     *
     * @var array
     * @internal
     */
    public $remapStack = [];

    /**
     * Array used for remapping uids and values at the end of process_datamap
     * (e.g. $remapStackRecords[<table>][<uid>] = <index in $remapStack>)
     *
     * @var array
     * @internal
     */
    public $remapStackRecords = [];

    /**
     * Array used for checking whether new children need to be remapped
     *
     * @var array
     */
    protected $remapStackChildIds = [];

    /**
     * Array used for executing addition actions after remapping happened (set processRemapStack())
     *
     * @var array
     */
    protected $remapStackActions = [];

    /**
     * Registry object to gather reference index update requests and perform updates after
     * main processing has been done. The first call to start() instantiates this object.
     * Recursive sub instances receive this instance via __construct().
     * The final update() call is done at the end of process_cmdmap() or process_datamap()
     * in the outer most instance.
     *
     * @var ReferenceIndexUpdater
     */
    protected $referenceIndexUpdater;

    // Various

    /**
     * Set to "currentRecord" during checking of values.
     *
     * @var array
     * @internal
     */
    public $checkValue_currentRecord = [];

    /**
     * Disable delete clause
     *
     * @var bool
     */
    protected $disableDeleteClause = false;

    /**
     * @var array|null
     */
    protected $checkModifyAccessListHookObjects;

    /**
     * The outer most instance of \TYPO3\CMS\Core\DataHandling\DataHandler:
     * This object instantiates itself on versioning and localization ...
     *
     * @var \TYPO3\CMS\Core\DataHandling\DataHandler|null
     */
    protected $outerMostInstance;

    /**
     * Internal cache for collecting records that should trigger cache clearing
     *
     * @var array
     */
    protected static $recordsToClearCacheFor = [];

    /**
     * Internal cache for pids of records which were deleted. It's not possible
     * to retrieve the parent folder/page at a later stage
     *
     * @var array
     */
    protected static $recordPidsForDeletedRecords = [];

    /**
     * Runtime Cache to store and retrieve data computed for a single request
     *
     * @var FrontendInterface
     */
    protected $runtimeCache;

    /**
     * Prefix for the cache entries of nested element calls since the runtimeCache has a global scope.
     *
     * @var string
     */
    protected $cachePrefixNestedElementCalls = 'core-datahandler-nestedElementCalls-';

    /**
     * Sets up the data handler cache and some additional options, the main logic is done in the start() method.
     *
     * @param ReferenceIndexUpdater|null $referenceIndexUpdater Hand over from outer most instance to sub instances
     */
    public function __construct(ReferenceIndexUpdater $referenceIndexUpdater = null)
    {
        $this->checkStoredRecords = (bool)$GLOBALS['TYPO3_CONF_VARS']['BE']['checkStoredRecords'];
        $this->checkStoredRecords_loose = (bool)$GLOBALS['TYPO3_CONF_VARS']['BE']['checkStoredRecordsLoose'];
        $this->runtimeCache = $this->getRuntimeCache();
        $this->pagePermissionAssembler = GeneralUtility::makeInstance(PagePermissionAssembler::class, $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPermissions']);
        if ($referenceIndexUpdater === null) {
            // Create ReferenceIndexUpdater object. This should only happen on outer most instance,
            // sub instances should receive the reference index updater from a parent.
            $referenceIndexUpdater = GeneralUtility::makeInstance(ReferenceIndexUpdater::class);
        }
        $this->referenceIndexUpdater = $referenceIndexUpdater;
    }

    /**
     * @param array $control
     * @internal
     */
    public function setControl(array $control)
    {
        $this->control = $control;
    }

    /**
     * Initializing.
     * For details, see 'TYPO3 Core API' document.
     * This function does not start the processing of data, but merely initializes the object
     *
     * @param array $data Data to be modified or inserted in the database
     * @param array $cmd Commands to copy, move, delete, localize, versionize records.
     * @param BackendUserAuthentication|null $altUserObject An alternative userobject you can set instead of the default, which is $GLOBALS['BE_USER']
     */
    public function start($data, $cmd, $altUserObject = null)
    {
        // Initializing BE_USER
        $this->BE_USER = is_object($altUserObject) ? $altUserObject : $GLOBALS['BE_USER'];
        $this->userid = $this->BE_USER->user['uid'] ?? 0;
        $this->username = $this->BE_USER->user['username'] ?? '';
        $this->admin = $this->BE_USER->user['admin'] ?? false;

        // set correlation id for each new set of data or commands
        $this->correlationId = CorrelationId::forScope(
            md5(StringUtility::getUniqueId(self::class))
        );

        // Get default values from user TSconfig
        $tcaDefaultOverride = $this->BE_USER->getTSConfig()['TCAdefaults.'] ?? null;
        if (is_array($tcaDefaultOverride)) {
            $this->setDefaultsFromUserTS($tcaDefaultOverride);
        }

        // generates the excludelist, based on TCA/exclude-flag and non_exclude_fields for the user:
        if (!$this->admin) {
            $this->excludedTablesAndFields = array_flip($this->getExcludeListArray());
        }
        // Setting the data and cmd arrays
        if (is_array($data)) {
            reset($data);
            $this->datamap = $data;
        }
        if (is_array($cmd)) {
            reset($cmd);
            $this->cmdmap = $cmd;
        }
    }

    /**
     * Function that can mirror input values in datamap-array to other uid numbers.
     * Example: $mirror[table][11] = '22,33' will look for content in $this->datamap[table][11] and copy it to $this->datamap[table][22] and $this->datamap[table][33]
     *
     * @param array $mirror This array has the syntax $mirror[table_name][uid] = [list of uids to copy data-value TO!]
     * @internal
     */
    public function setMirror($mirror)
    {
        if (!is_array($mirror)) {
            return;
        }

        foreach ($mirror as $table => $uid_array) {
            if (!isset($this->datamap[$table])) {
                continue;
            }

            foreach ($uid_array as $id => $uidList) {
                if (!isset($this->datamap[$table][$id])) {
                    continue;
                }

                $theIdsInArray = GeneralUtility::trimExplode(',', $uidList, true);
                foreach ($theIdsInArray as $copyToUid) {
                    $this->datamap[$table][$copyToUid] = $this->datamap[$table][$id];
                }
            }
        }
    }

    /**
     * Initializes default values coming from User TSconfig
     *
     * @param array $userTS User TSconfig array
     * @internal should only be used from within DataHandler
     */
    public function setDefaultsFromUserTS($userTS)
    {
        if (!is_array($userTS)) {
            return;
        }

        foreach ($userTS as $k => $v) {
            $k = mb_substr($k, 0, -1);
            if (!$k || !is_array($v) || !isset($GLOBALS['TCA'][$k])) {
                continue;
            }

            if (is_array($this->defaultValues[$k] ?? false)) {
                $this->defaultValues[$k] = array_merge($this->defaultValues[$k], $v);
            } else {
                $this->defaultValues[$k] = $v;
            }
        }
    }

    /**
     * When a new record is created, all values that haven't been set but are set via PageTSconfig / UserTSconfig
     * get applied here.
     *
     * This is only executed for new records. The most important part is that the pageTS of the actual resolved $pid
     * is taken, and a new field array with empty defaults is set again.
     *
     * @param string $table
     * @param int $pageId
     * @param array $prepopulatedFieldArray
     * @return array
     */
    protected function applyDefaultsForFieldArray(string $table, int $pageId, array $prepopulatedFieldArray): array
    {
        // First set TCAdefaults respecting the given PageID
        $tcaDefaults = BackendUtility::getPagesTSconfig($pageId)['TCAdefaults.'] ?? null;
        // Re-apply $this->defaultValues settings
        $this->setDefaultsFromUserTS($tcaDefaults);
        $cleanFieldArray = $this->newFieldArray($table);
        if (isset($prepopulatedFieldArray['pid'])) {
            $cleanFieldArray['pid'] = $prepopulatedFieldArray['pid'];
        }
        $sortColumn = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? null;
        if ($sortColumn !== null && isset($prepopulatedFieldArray[$sortColumn])) {
            $cleanFieldArray[$sortColumn] = $prepopulatedFieldArray[$sortColumn];
        }
        return $cleanFieldArray;
    }

    /*********************************************
     *
     * HOOKS
     *
     *********************************************/
    /**
     * Hook: processDatamap_afterDatabaseOperations
     * (calls $hookObj->processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $this);)
     *
     * Note: When using the hook after INSERT operations, you will only get the temporary NEW... id passed to your hook as $id,
     * but you can easily translate it to the real uid of the inserted record using the $this->substNEWwithIDs array.
     *
     * @param array $hookObjectsArr (reference) Array with hook objects
     * @param string $status (reference) Status of the current operation, 'new' or 'update
     * @param string $table (reference) The table currently processing data for
     * @param string $id (reference) The record uid currently processing data for, [integer] or [string] (like 'NEW...')
     * @param array $fieldArray (reference) The field array of a record
     * @internal should only be used from within DataHandler
     */
    public function hook_processDatamap_afterDatabaseOperations(&$hookObjectsArr, &$status, &$table, &$id, &$fieldArray)
    {
        // Process hook directly:
        if (!isset($this->remapStackRecords[$table][$id])) {
            foreach ($hookObjectsArr as $hookObj) {
                if (method_exists($hookObj, 'processDatamap_afterDatabaseOperations')) {
                    $hookObj->processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $this);
                }
            }
        } else {
            $this->remapStackRecords[$table][$id]['processDatamap_afterDatabaseOperations'] = [
                'status' => $status,
                'fieldArray' => $fieldArray,
                'hookObjectsArr' => $hookObjectsArr,
            ];
        }
    }

    /**
     * Gets the 'checkModifyAccessList' hook objects.
     * The first call initializes the accordant objects.
     *
     * @return array The 'checkModifyAccessList' hook objects (if any)
     * @throws \UnexpectedValueException
     */
    protected function getCheckModifyAccessListHookObjects()
    {
        if (!isset($this->checkModifyAccessListHookObjects)) {
            $this->checkModifyAccessListHookObjects = [];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof DataHandlerCheckModifyAccessListHookInterface) {
                    throw new \UnexpectedValueException($className . ' must implement interface ' . DataHandlerCheckModifyAccessListHookInterface::class, 1251892472);
                }
                $this->checkModifyAccessListHookObjects[] = $hookObject;
            }
        }
        return $this->checkModifyAccessListHookObjects;
    }

    /*********************************************
     *
     * PROCESSING DATA
     *
     *********************************************/
    /**
     * Processing the data-array
     * Call this function to process the data-array set by start()
     *
     * @return bool|void
     */
    public function process_datamap()
    {
        $this->controlActiveElements();

        // Keep versionized(!) relations here locally:
        $registerDBList = [];
        $this->registerElementsToBeDeleted();
        $this->datamap = $this->unsetElementsToBeDeleted($this->datamap);
        // Editing frozen:
        if ($this->BE_USER->workspace !== 0 && ($this->BE_USER->workspaceRec['freeze'] ?? false)) {
            $this->log('sys_workspace', $this->BE_USER->workspace, SystemLogDatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'All editing in this workspace has been frozen!');
            return false;
        }
        // First prepare user defined objects (if any) for hooks which extend this function:
        $hookObjectsArr = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (method_exists($hookObject, 'processDatamap_beforeStart')) {
                $hookObject->processDatamap_beforeStart($this);
            }
            $hookObjectsArr[] = $hookObject;
        }
        // Pre-process data-map and synchronize localization states
        $this->datamap = GeneralUtility::makeInstance(SlugEnricher::class)->enrichDataMap($this->datamap);
        $this->datamap = DataMapProcessor::instance($this->datamap, $this->BE_USER, $this->referenceIndexUpdater)->process();
        // Organize tables so that the pages-table is always processed first. This is required if you want to make sure that content pointing to a new page will be created.
        $orderOfTables = [];
        // Set pages first.
        if (isset($this->datamap['pages'])) {
            $orderOfTables[] = 'pages';
        }
        $orderOfTables = array_unique(array_merge($orderOfTables, array_keys($this->datamap)));
        // Process the tables...
        foreach ($orderOfTables as $table) {
            // Check if
            //	   - table is set in $GLOBALS['TCA'],
            //	   - table is NOT readOnly
            //	   - the table is set with content in the data-array (if not, there's nothing to process...)
            //	   - permissions for tableaccess OK
            $modifyAccessList = $this->checkModifyAccessList($table);
            if (!$modifyAccessList) {
                $this->log($table, 0, SystemLogDatabaseAction::UPDATE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to modify table \'%s\' without permission', 1, [$table]);
            }
            if (!isset($GLOBALS['TCA'][$table]) || $this->tableReadOnly($table) || !is_array($this->datamap[$table]) || !$modifyAccessList) {
                continue;
            }

            if ($this->reverseOrder) {
                $this->datamap[$table] = array_reverse($this->datamap[$table], true);
            }
            // For each record from the table, do:
            // $id is the record uid, may be a string if new records...
            // $incomingFieldArray is the array of fields
            foreach ($this->datamap[$table] as $id => $incomingFieldArray) {
                if (!is_array($incomingFieldArray)) {
                    continue;
                }
                $theRealPid = null;

                // Hook: processDatamap_preProcessFieldArray
                foreach ($hookObjectsArr as $hookObj) {
                    if (method_exists($hookObj, 'processDatamap_preProcessFieldArray')) {
                        $hookObj->processDatamap_preProcessFieldArray($incomingFieldArray, $table, $id, $this);
                    }
                }
                // ******************************
                // Checking access to the record
                // ******************************
                $createNewVersion = false;
                $old_pid_value = '';
                // Is it a new record? (Then Id is a string)
                if (!MathUtility::canBeInterpretedAsInteger($id)) {
                    // Get a fieldArray with tca default values
                    $fieldArray = $this->newFieldArray($table);
                    // A pid must be set for new records.
                    if (isset($incomingFieldArray['pid'])) {
                        $pid_value = $incomingFieldArray['pid'];
                        // Checking and finding numerical pid, it may be a string-reference to another value
                        $canProceed = true;
                        // If a NEW... id
                        if (str_contains($pid_value, 'NEW')) {
                            if ($pid_value[0] === '-') {
                                $negFlag = -1;
                                $pid_value = substr($pid_value, 1);
                            } else {
                                $negFlag = 1;
                            }
                            // Trying to find the correct numerical value as it should be mapped by earlier processing of another new record.
                            if (isset($this->substNEWwithIDs[$pid_value])) {
                                if ($negFlag === 1) {
                                    $old_pid_value = $this->substNEWwithIDs[$pid_value];
                                }
                                $pid_value = (int)($negFlag * $this->substNEWwithIDs[$pid_value]);
                            } else {
                                $canProceed = false;
                            }
                        }
                        $pid_value = (int)$pid_value;
                        if ($canProceed) {
                            $fieldArray = $this->resolveSortingAndPidForNewRecord($table, $pid_value, $fieldArray);
                        }
                    }
                    $theRealPid = $fieldArray['pid'];
                    // Checks if records can be inserted on this $pid.
                    // If this is a page translation, the check needs to be done for the l10n_parent record
                    $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
                    $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;
                    if ($table === 'pages'
                        && $languageField && isset($incomingFieldArray[$languageField]) && $incomingFieldArray[$languageField] > 0
                        && $transOrigPointerField && isset($incomingFieldArray[$transOrigPointerField]) && $incomingFieldArray[$transOrigPointerField] > 0
                    ) {
                        $recordAccess = $this->checkRecordInsertAccess($table, $incomingFieldArray[$transOrigPointerField]);
                    } else {
                        $recordAccess = $this->checkRecordInsertAccess($table, $theRealPid);
                    }
                    if ($recordAccess) {
                        $this->addDefaultPermittedLanguageIfNotSet($table, $incomingFieldArray, $theRealPid);
                        $recordAccess = $this->BE_USER->recordEditAccessInternals($table, $incomingFieldArray, true);
                        if (!$recordAccess) {
                            $this->log($table, 0, SystemLogDatabaseAction::INSERT, 0, SystemLogErrorClassification::USER_ERROR, 'recordEditAccessInternals() check failed. [{reason}]', -1, ['reason' => $this->BE_USER->errorMsg]);
                        } elseif (!$this->bypassWorkspaceRestrictions && !$this->BE_USER->workspaceAllowsLiveEditingInTable($table)) {
                            // If LIVE records cannot be created due to workspace restrictions, prepare creation of placeholder-record
                            // So, if no live records were allowed in the current workspace, we have to create a new version of this record
                            if (BackendUtility::isTableWorkspaceEnabled($table)) {
                                $createNewVersion = true;
                            } else {
                                $recordAccess = false;
                                $this->log($table, 0, SystemLogDatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Record could not be created in this workspace');
                            }
                        }
                    }
                    // Yes new record, change $record_status to 'insert'
                    $status = 'new';
                } else {
                    // Nope... $id is a number
                    $id = (int)$id;
                    $fieldArray = [];
                    $recordAccess = $this->checkRecordUpdateAccess($table, $id, $incomingFieldArray, $hookObjectsArr);
                    if (!$recordAccess) {
                        if ($this->enableLogging) {
                            $propArr = $this->getRecordProperties($table, $id);
                            $this->log($table, $id, SystemLogDatabaseAction::UPDATE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to modify record \'%s\' (%s) without permission. Or non-existing page.', 2, [$propArr['header'], $table . ':' . $id], $propArr['event_pid']);
                        }
                        continue;
                    }
                    // Next check of the record permissions (internals)
                    $recordAccess = $this->BE_USER->recordEditAccessInternals($table, $id);
                    if (!$recordAccess) {
                        $this->log($table, $id, SystemLogDatabaseAction::UPDATE, 0, SystemLogErrorClassification::USER_ERROR, 'recordEditAccessInternals() check failed. [{reason}]', -1, ['reason' => $this->BE_USER->errorMsg]);
                    } else {
                        // Here we fetch the PID of the record that we point to...
                        $tempdata = $this->recordInfo($table, $id, 'pid' . (BackendUtility::isTableWorkspaceEnabled($table) ? ',t3ver_oid,t3ver_wsid,t3ver_stage' : ''));
                        $theRealPid = $tempdata['pid'] ?? null;
                        // Use the new id of the versionized record we're trying to write to:
                        // (This record is a child record of a parent and has already been versionized.)
                        if (!empty($this->autoVersionIdMap[$table][$id])) {
                            // For the reason that creating a new version of this record, automatically
                            // created related child records (e.g. "IRRE"), update the accordant field:
                            $this->getVersionizedIncomingFieldArray($table, $id, $incomingFieldArray, $registerDBList);
                            // Use the new id of the copied/versionized record:
                            $id = $this->autoVersionIdMap[$table][$id];
                            $recordAccess = true;
                        } elseif (!$this->bypassWorkspaceRestrictions && $tempdata && ($errorCode = $this->workspaceCannotEditRecord($table, $tempdata))) {
                            $recordAccess = false;
                            // Versioning is required and it must be offline version!
                            // Check if there already is a workspace version
                            $workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($this->BE_USER->workspace, $table, $id, 'uid,t3ver_oid');
                            if ($workspaceVersion) {
                                $id = $workspaceVersion['uid'];
                                $recordAccess = true;
                            } elseif ($this->workspaceAllowAutoCreation($table, $id, $theRealPid)) {
                                // new version of a record created in a workspace - so always refresh pagetree to indicate there is a change in the workspace
                                $this->pagetreeNeedsRefresh = true;

                                $tce = GeneralUtility::makeInstance(self::class, $this->referenceIndexUpdater);
                                $tce->enableLogging = $this->enableLogging;
                                // Setting up command for creating a new version of the record:
                                $cmd = [];
                                $cmd[$table][$id]['version'] = [
                                    'action' => 'new',
                                    // Default is to create a version of the individual records
                                    'label' => 'Auto-created for WS #' . $this->BE_USER->workspace,
                                ];
                                $tce->start([], $cmd, $this->BE_USER);
                                $tce->process_cmdmap();
                                $this->errorLog = array_merge($this->errorLog, $tce->errorLog);
                                // If copying was successful, share the new uids (also of related children):
                                if (!empty($tce->copyMappingArray[$table][$id])) {
                                    foreach ($tce->copyMappingArray as $origTable => $origIdArray) {
                                        foreach ($origIdArray as $origId => $newId) {
                                            $this->autoVersionIdMap[$origTable][$origId] = $newId;
                                        }
                                    }
                                    // Update registerDBList, that holds the copied relations to child records:
                                    $registerDBList = array_merge($registerDBList, $tce->registerDBList);
                                    // For the reason that creating a new version of this record, automatically
                                    // created related child records (e.g. "IRRE"), update the accordant field:
                                    $this->getVersionizedIncomingFieldArray($table, $id, $incomingFieldArray, $registerDBList);
                                    // Use the new id of the copied/versionized record:
                                    $id = $this->autoVersionIdMap[$table][$id];
                                    $recordAccess = true;
                                } else {
                                    $this->log($table, $id, SystemLogDatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Could not be edited in offline workspace in the branch where found (failure state: \'{reason}\'). Auto-creation of version failed!', -1, ['reason' => $errorCode]);
                                }
                            } else {
                                $this->log($table, $id, SystemLogDatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Could not be edited in offline workspace in the branch where found (failure state: \'{reason}\'). Auto-creation of version not allowed in workspace!', -1, ['reason' => $errorCode]);
                            }
                        }
                    }
                    // The default is 'update'
                    $status = 'update';
                }
                // If access was granted above, proceed to create or update record:
                if (!$recordAccess) {
                    continue;
                }

                // Here the "pid" is set IF NOT the old pid was a string pointing to a place in the subst-id array.
                [$tscPID] = BackendUtility::getTSCpid($table, $id, $old_pid_value ?: ($fieldArray['pid'] ?? 0));
                if ($status === 'new') {
                    // Apply TCAdefaults from pageTS
                    $fieldArray = $this->applyDefaultsForFieldArray($table, (int)$tscPID, $fieldArray);
                    // Apply page permissions as well
                    if ($table === 'pages') {
                        $fieldArray = $this->pagePermissionAssembler->applyDefaults(
                            $fieldArray,
                            (int)$tscPID,
                            (int)$this->userid,
                            (int)$this->BE_USER->firstMainGroup
                        );
                    }
                    // Ensure that the default values, that are stored in the $fieldArray (built from internal default values)
                    // Are also placed inside the incomingFieldArray, so this is checked in "fillInFieldArray" and
                    // all default values are also checked for validity
                    // This allows to set TCAdefaults (for example) without having to use FormEngine to have the fields available first.
                    $incomingFieldArray = array_replace_recursive($fieldArray, $incomingFieldArray);
                }
                // Processing of all fields in incomingFieldArray and setting them in $fieldArray
                $fieldArray = $this->fillInFieldArray($table, $id, $fieldArray, $incomingFieldArray, $theRealPid, $status, $tscPID);
                // NOTICE! All manipulation beyond this point bypasses both "excludeFields" AND possible "MM" relations to field!
                // Forcing some values unto field array:
                // NOTICE: This overriding is potentially dangerous; permissions per field is not checked!!!
                $fieldArray = $this->overrideFieldArray($table, $fieldArray);
                // Setting system fields
                if ($status === 'new') {
                    if ($GLOBALS['TCA'][$table]['ctrl']['crdate'] ?? false) {
                        $fieldArray[$GLOBALS['TCA'][$table]['ctrl']['crdate']] = $GLOBALS['EXEC_TIME'];
                    }
                    if ($GLOBALS['TCA'][$table]['ctrl']['cruser_id'] ?? false) {
                        $fieldArray[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']] = $this->userid;
                    }
                } elseif ($this->checkSimilar) {
                    // Removing fields which are equal to the current value:
                    $fieldArray = $this->compareFieldArrayWithCurrentAndUnset($table, $id, $fieldArray);
                }
                if (($GLOBALS['TCA'][$table]['ctrl']['tstamp'] ?? false) && !empty($fieldArray)) {
                    $fieldArray[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
                }
                // Set stage to "Editing" to make sure we restart the workflow
                if (BackendUtility::isTableWorkspaceEnabled($table)) {
                    $fieldArray['t3ver_stage'] = 0;
                }
                // Hook: processDatamap_postProcessFieldArray
                foreach ($hookObjectsArr as $hookObj) {
                    if (method_exists($hookObj, 'processDatamap_postProcessFieldArray')) {
                        $hookObj->processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, $this);
                    }
                }
                // Performing insert/update. If fieldArray has been unset by some userfunction (see hook above), don't do anything
                // Kasper: Unsetting the fieldArray is dangerous; MM relations might be saved already
                if (is_array($fieldArray)) {
                    if ($status === 'new') {
                        if ($table === 'pages') {
                            // for new pages always a refresh is needed
                            $this->pagetreeNeedsRefresh = true;
                        }

                        // This creates a version of the record, instead of adding it to the live workspace
                        if ($createNewVersion) {
                            // new record created in a workspace - so always refresh pagetree to indicate there is a change in the workspace
                            $this->pagetreeNeedsRefresh = true;
                            $fieldArray['pid'] = $theRealPid;
                            $fieldArray['t3ver_oid'] = 0;
                            // Setting state for version (so it can know it is currently a new version...)
                            $fieldArray['t3ver_state'] = (string)new VersionState(VersionState::NEW_PLACEHOLDER);
                            $fieldArray['t3ver_wsid'] = $this->BE_USER->workspace;
                            $this->insertDB($table, $id, $fieldArray, true, (int)($incomingFieldArray['uid'] ?? 0));
                            // Hold auto-versionized ids of placeholders
                            $this->autoVersionIdMap[$table][$this->substNEWwithIDs[$id]] = $this->substNEWwithIDs[$id];
                        } else {
                            $this->insertDB($table, $id, $fieldArray, false, (int)($incomingFieldArray['uid'] ?? 0));
                        }
                    } else {
                        if ($table === 'pages') {
                            // only a certain number of fields needs to be checked for updates
                            // if $this->checkSimilar is TRUE, fields with unchanged values are already removed here
                            $fieldsToCheck = array_intersect($this->pagetreeRefreshFieldsFromPages, array_keys($fieldArray));
                            if (!empty($fieldsToCheck)) {
                                $this->pagetreeNeedsRefresh = true;
                            }
                        }
                        $this->updateDB($table, $id, $fieldArray);
                    }
                }
                // Hook: processDatamap_afterDatabaseOperations
                // Note: When using the hook after INSERT operations, you will only get the temporary NEW... id passed to your hook as $id,
                // but you can easily translate it to the real uid of the inserted record using the $this->substNEWwithIDs array.
                $this->hook_processDatamap_afterDatabaseOperations($hookObjectsArr, $status, $table, $id, $fieldArray);
            }
        }
        // Process the stack of relations to remap/correct
        $this->processRemapStack();
        $this->dbAnalysisStoreExec();
        // Hook: processDatamap_afterAllOperations
        // Note: When this hook gets called, all operations on the submitted data have been finished.
        foreach ($hookObjectsArr as $hookObj) {
            if (method_exists($hookObj, 'processDatamap_afterAllOperations')) {
                $hookObj->processDatamap_afterAllOperations($this);
            }
        }

        if ($this->isOuterMostInstance()) {
            $this->referenceIndexUpdater->update();
            $this->processClearCacheQueue();
            $this->resetElementsToBeDeleted();
        }
    }

    /**
     * @param string $table
     * @param string $value
     * @param string $dbType
     * @return string
     */
    protected function normalizeTimeFormat(string $table, string $value, string $dbType): string
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $platform = $connection->getDatabasePlatform();
        if ($platform instanceof SQLServerPlatform) {
            $defaultLength = QueryHelper::getDateTimeFormats()[$dbType]['empty'];
            $value = substr(
                $value,
                0,
                strlen($defaultLength)
            );
        }
        return $value;
    }

    /**
     * Sets the "sorting" DB field and the "pid" field of an incoming record that should be added (NEW1234)
     * depending on the record that should be added or where it should be added.
     *
     * This method is called from process_datamap()
     *
     * @param string $table the table name of the record to insert
     * @param int $pid the real PID (numeric) where the record should be
     * @param array $fieldArray field+value pairs to add
     * @return array the modified field array
     */
    protected function resolveSortingAndPidForNewRecord(string $table, int $pid, array $fieldArray): array
    {
        $sortColumn = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? '';
        // Points to a page on which to insert the element, possibly in the top of the page
        if ($pid >= 0) {
            // Ensure that the "pid" is not a translated page ID, but the default page ID
            $pid = $this->getDefaultLanguagePageId($pid);
            // The numerical pid is inserted in the data array
            $fieldArray['pid'] = $pid;
            // If this table is sorted we better find the top sorting number
            if ($sortColumn) {
                $fieldArray[$sortColumn] = $this->getSortNumber($table, 0, $pid);
            }
        } elseif ($sortColumn) {
            // Points to another record before itself
            // If this table is sorted we better find the top sorting number
            // Because $pid is < 0, getSortNumber() returns an array
            $sortingInfo = $this->getSortNumber($table, 0, $pid);
            $fieldArray['pid'] = $sortingInfo['pid'];
            $fieldArray[$sortColumn] = $sortingInfo['sortNumber'];
        } else {
            // Here we fetch the PID of the record that we point to
            $record = $this->recordInfo($table, abs($pid), 'pid');
            // Ensure that the "pid" is not a translated page ID, but the default page ID
            $fieldArray['pid'] = $this->getDefaultLanguagePageId($record['pid']);
        }
        return $fieldArray;
    }

    /**
     * Filling in the field array
     * $this->excludedTablesAndFields is used to filter fields if needed.
     *
     * @param string $table Table name
     * @param int|string $id Record ID
     * @param array $fieldArray Default values, Preset $fieldArray with 'pid' maybe (pid and uid will be not be overridden anyway)
     * @param array $incomingFieldArray Is which fields/values you want to set. There are processed and put into $fieldArray if OK
     * @param int $realPid The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted.
     * @param string $status Is 'new' or 'update'
     * @param int $tscPID TSconfig PID
     * @return array Field Array
     * @internal should only be used from within DataHandler
     */
    public function fillInFieldArray($table, $id, $fieldArray, $incomingFieldArray, $realPid, $status, $tscPID)
    {
        // Initialize:
        $originalLanguageRecord = null;
        $originalLanguage_diffStorage = null;
        $diffStorageFlag = false;
        // Setting 'currentRecord' and 'checkValueRecord':
        if (str_contains((string)$id, 'NEW')) {
            // Must have the 'current' array - not the values after processing below...
            $checkValueRecord = $fieldArray;
            // IF $incomingFieldArray is an array, overlay it.
            // The point is that when new records are created as copies with flex type fields there might be a field containing information about which DataStructure to use and without that information the flexforms cannot be correctly processed.... This should be OK since the $checkValueRecord is used by the flexform evaluation only anyways...
            if (is_array($incomingFieldArray) && is_array($checkValueRecord)) {
                ArrayUtility::mergeRecursiveWithOverrule($checkValueRecord, $incomingFieldArray);
            }
            $currentRecord = $checkValueRecord;
        } else {
            $id = (int)$id;
            // We must use the current values as basis for this!
            $currentRecord = ($checkValueRecord = $this->recordInfo($table, $id, '*'));
        }

        // Get original language record if available:
        if (is_array($currentRecord)
            && ($GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField'] ?? false)
            && !empty($GLOBALS['TCA'][$table]['ctrl']['languageField'])
            && (int)($currentRecord[$GLOBALS['TCA'][$table]['ctrl']['languageField']] ?? 0) > 0
            && ($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? false)
            && (int)($currentRecord[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] ?? 0) > 0
        ) {
            $originalLanguageRecord = $this->recordInfo($table, $currentRecord[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']], '*');
            BackendUtility::workspaceOL($table, $originalLanguageRecord);
            $originalLanguage_diffStorage = json_decode(
                (string)($currentRecord[$GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']] ?? ''),
                true
            );
        }

        $this->checkValue_currentRecord = $checkValueRecord;
        // In the following all incoming value-fields are tested:
        // - Are the user allowed to change the field?
        // - Is the field uid/pid (which are already set)
        // - perms-fields for pages-table, then do special things...
        // - If the field is nothing of the above and the field is configured in TCA, the fieldvalues are evaluated by ->checkValue
        // If everything is OK, the field is entered into $fieldArray[]
        foreach ($incomingFieldArray as $field => $fieldValue) {
            if (isset($this->excludedTablesAndFields[$table . '-' . $field]) || (bool)($this->data_disableFields[$table][$id][$field] ?? false)) {
                continue;
            }

            // The field must be editable.
            // Checking if a value for language can be changed:
            if (($GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? false)
                && (string)$GLOBALS['TCA'][$table]['ctrl']['languageField'] === (string)$field
                && !$this->BE_USER->checkLanguageAccess($fieldValue)
            ) {
                continue;
            }

            switch ($field) {
                case 'uid':
                case 'pid':
                    // Nothing happens, already set
                    break;
                case 'perms_userid':
                case 'perms_groupid':
                case 'perms_user':
                case 'perms_group':
                case 'perms_everybody':
                    // Permissions can be edited by the owner or the administrator
                    if ($table === 'pages' && ($this->admin || $status === 'new' || $this->pageInfo((int)$id, 'perms_userid') == $this->userid)) {
                        $value = (int)$fieldValue;
                        switch ($field) {
                            case 'perms_userid':
                            case 'perms_groupid':
                                $fieldArray[$field] = $value;
                                break;
                            default:
                                if ($value >= 0 && $value < (2 ** 5)) {
                                    $fieldArray[$field] = $value;
                                }
                        }
                    }
                    break;
                case 't3ver_oid':
                case 't3ver_wsid':
                case 't3ver_state':
                case 't3ver_stage':
                    break;
                case 'l10n_state':
                    $fieldArray[$field] = $fieldValue;
                    break;
                default:
                    if (isset($GLOBALS['TCA'][$table]['columns'][$field])) {
                        // Evaluating the value
                        $res = $this->checkValue($table, $field, $fieldValue, $id, $status, $realPid, $tscPID, $incomingFieldArray);
                        if (array_key_exists('value', $res)) {
                            $fieldArray[$field] = $res['value'];
                        }
                        // Add the value of the original record to the diff-storage content:
                        if ($GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField'] ?? false) {
                            $originalLanguage_diffStorage[$field] = (string)($originalLanguageRecord[$field] ?? '');
                            $diffStorageFlag = true;
                        }
                    } elseif (isset($GLOBALS['TCA'][$table]['ctrl']['origUid']) && $GLOBALS['TCA'][$table]['ctrl']['origUid'] === $field) {
                        // Allow value for original UID to pass by...
                        $fieldArray[$field] = $fieldValue;
                    }
            }
        }

        // Dealing with a page translation, setting "sorting", "pid", "perms_*" to the same values as the original record
        if ($table === 'pages' && is_array($originalLanguageRecord)) {
            $fieldArray['sorting'] = $originalLanguageRecord['sorting'];
            $fieldArray['perms_userid'] = $originalLanguageRecord['perms_userid'];
            $fieldArray['perms_groupid'] = $originalLanguageRecord['perms_groupid'];
            $fieldArray['perms_user'] = $originalLanguageRecord['perms_user'];
            $fieldArray['perms_group'] = $originalLanguageRecord['perms_group'];
            $fieldArray['perms_everybody'] = $originalLanguageRecord['perms_everybody'];
        }

        // Add diff-storage information:
        if ($diffStorageFlag
            && !array_key_exists($GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField'], $fieldArray)
        ) {
            // If the field is set it would probably be because of an undo-operation - in which case we should not update the field of course...
            $fieldArray[$GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']] = json_encode($originalLanguage_diffStorage);
        }
        // Return fieldArray
        return $fieldArray;
    }

    /*********************************************
     *
     * Evaluation of input values
     *
     ********************************************/
    /**
     * Evaluates a value according to $table/$field settings.
     * This function is for real database fields - NOT FlexForm "pseudo" fields.
     * NOTICE: Calling this function expects this: 1) That the data is saved!
     *
     * @param string $table Table name
     * @param string $field Field name
     * @param string $value Value to be evaluated. Notice, this is the INPUT value from the form. The original value (from any existing record) must be manually looked up inside the function if needed - or taken from $currentRecord array.
     * @param int|string $id The record-uid, mainly - but not exclusively - used for logging
     * @param string $status 'update' or 'new' flag
     * @param int $realPid The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted.
     * @param int $tscPID TSconfig PID
     * @param array $incomingFieldArray the fields being explicitly set by the outside (unlike $fieldArray)
     * @return array Returns the evaluated $value as key "value" in this array. Can be checked with isset($res['value']) ...
     * @internal should only be used from within DataHandler
     */
    public function checkValue($table, $field, $value, $id, $status, $realPid, $tscPID, $incomingFieldArray = [])
    {
        $curValueRec = null;
        // Result array
        $res = [];

        // Processing special case of field pages.doktype
        if ($table === 'pages' && $field === 'doktype') {
            // If the user may not use this specific doktype, we issue a warning
            if (!($this->admin || GeneralUtility::inList($this->BE_USER->groupData['pagetypes_select'], $value))) {
                if ($this->enableLogging) {
                    $propArr = $this->getRecordProperties($table, $id);
                    $this->log($table, (int)$id, SystemLogDatabaseAction::CHECK, 0, SystemLogErrorClassification::USER_ERROR, 'You cannot change the \'doktype\' of page \'%s\' to the desired value.', 1, [$propArr['header']], $propArr['event_pid']);
                }
                return $res;
            }
            if ($status === 'update') {
                // This checks 1) if we should check for disallowed tables and 2) if there are records from disallowed tables on the current page
                $onlyAllowedTables = $GLOBALS['PAGES_TYPES'][$value]['onlyAllowedTables'] ?? $GLOBALS['PAGES_TYPES']['default']['onlyAllowedTables'];
                if ($onlyAllowedTables) {
                    // use the real page id (default language)
                    $recordId = $this->getDefaultLanguagePageId((int)$id);
                    $theWrongTables = $this->doesPageHaveUnallowedTables($recordId, (int)$value);
                    if ($theWrongTables) {
                        if ($this->enableLogging) {
                            $propArr = $this->getRecordProperties($table, $id);
                            $this->log($table, (int)$id, SystemLogDatabaseAction::CHECK, 0, SystemLogErrorClassification::USER_ERROR, '\'doktype\' of page \'%s\' could not be changed because the page contains records from disallowed tables; %s', 2, [$propArr['header'], $theWrongTables], $propArr['event_pid']);
                        }
                        return $res;
                    }
                }
            }
        }

        $curValue = null;
        if ((int)$id !== 0) {
            // Get current value:
            $curValueRec = $this->recordInfo($table, (int)$id, $field);
            // isset() won't work here, since values can be NULL
            if ($curValueRec !== null && array_key_exists($field, $curValueRec)) {
                $curValue = $curValueRec[$field];
            }
        }

        if ($table === 'be_users'
            && ($field === 'admin' || $field === 'password')
            && $status === 'update'
        ) {
            // Do not allow a non system maintainer admin to change admin flag and password of system maintainers
            $systemMaintainers = array_map('intval', $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? []);
            // False if current user is not in system maintainer list or if switch to user mode is active
            $isCurrentUserSystemMaintainer = $this->BE_USER->isSystemMaintainer();
            $isTargetUserInSystemMaintainerList = in_array((int)$id, $systemMaintainers, true);
            if ($field === 'admin') {
                $isFieldChanged = (int)$curValueRec[$field] !== (int)$value;
            } else {
                $isFieldChanged = $curValueRec[$field] !== $value;
            }
            if (!$isCurrentUserSystemMaintainer && $isTargetUserInSystemMaintainerList && $isFieldChanged) {
                $value = $curValueRec[$field];
                $this->log(
                    $table,
                    (int)$id,
                    SystemLogDatabaseAction::UPDATE,
                    0,
                    SystemLogErrorClassification::SECURITY_NOTICE,
                    'Only system maintainers can change the admin flag and password of other system maintainers. The value has not been updated.'
                );
            }
        }

        // Getting config for the field
        $tcaFieldConf = $this->resolveFieldConfigurationAndRespectColumnsOverrides($table, $field);

        // Create $recFID only for those types that need it
        if ($tcaFieldConf['type'] === 'flex') {
            $recFID = $table . ':' . $id . ':' . $field;
        } else {
            $recFID = '';
        }

        // Perform processing:
        $res = $this->checkValue_SW($res, $value, $tcaFieldConf, $table, $id, $curValue, $status, $realPid, $recFID, $field, $tscPID, ['incomingFieldArray' => $incomingFieldArray]);
        return $res;
    }

    /**
     * Use columns overrides for evaluation.
     *
     * Fetch the TCA ["config"] part for a specific field, including the columnsOverrides value.
     * Used for checkValue purposes currently (as it takes the checkValue_currentRecord value).
     *
     * @param string $table
     * @param string $field
     * @return array
     */
    protected function resolveFieldConfigurationAndRespectColumnsOverrides(string $table, string $field): array
    {
        $tcaFieldConf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        $recordType = BackendUtility::getTCAtypeValue($table, $this->checkValue_currentRecord);
        $columnsOverridesConfigOfField = $GLOBALS['TCA'][$table]['types'][$recordType]['columnsOverrides'][$field]['config'] ?? null;
        if ($columnsOverridesConfigOfField) {
            ArrayUtility::mergeRecursiveWithOverrule($tcaFieldConf, $columnsOverridesConfigOfField);
        }
        return $tcaFieldConf;
    }

    /**
     * Branches out evaluation of a field value based on its type as configured in $GLOBALS['TCA']
     * Can be called for FlexForm pseudo fields as well, BUT must not have $field set if so.
     * And hey, there's a good thing about the method arguments: 13 is prime :-P
     *
     * @param array $res The result array. The processed value (if any!) is set in the "value" key.
     * @param string $value The value to set.
     * @param array $tcaFieldConf Field configuration from $GLOBALS['TCA']
     * @param string $table Table name
     * @param int $id UID of record
     * @param mixed $curValue Current value of the field
     * @param string $status 'update' or 'new' flag
     * @param int $realPid The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted.
     * @param string $recFID Field identifier [table:uid:field] for flexforms
     * @param string $field Field name. Must NOT be set if the call is for a flexform field (since flexforms are not allowed within flexforms).
     * @param int $tscPID TSconfig PID
     * @param array|null $additionalData Additional data to be forwarded to sub-processors
     * @return array Returns the evaluated $value as key "value" in this array.
     * @internal should only be used from within DataHandler
     */
    public function checkValue_SW($res, $value, $tcaFieldConf, $table, $id, $curValue, $status, $realPid, $recFID, $field, $tscPID, array $additionalData = null)
    {
        // Convert to NULL value if defined in TCA
        if ($value === null && !empty($tcaFieldConf['eval']) && GeneralUtility::inList($tcaFieldConf['eval'], 'null')) {
            $res = ['value' => null];
            return $res;
        }

        switch ($tcaFieldConf['type']) {
            case 'text':
                $res = $this->checkValueForText($value, $tcaFieldConf, $table, $realPid, $field);
                break;
            case 'passthrough':
            case 'imageManipulation':
            case 'user':
                $res['value'] = $value;
                break;
            case 'input':
                $res = $this->checkValueForInput($value, $tcaFieldConf, $table, $id, $realPid, $field);
                break;
            case 'slug':
                $res = $this->checkValueForSlug((string)$value, $tcaFieldConf, $table, $id, (int)$realPid, $field, $additionalData['incomingFieldArray'] ?? []);
                break;
            case 'language':
                $res = $this->checkValueForLanguage((int)$value, $table, $field);
                break;
            case 'category':
                $res = $this->checkValueForCategory($res, (string)$value, $tcaFieldConf, (string)$table, $id, (string)$status, (string)$field);
                break;
            case 'check':
                $res = $this->checkValueForCheck($res, $value, $tcaFieldConf, $table, $id, $realPid, $field);
                break;
            case 'radio':
                $res = $this->checkValueForRadio($res, $value, $tcaFieldConf, $table, $id, $realPid, $field);
                break;
            case 'group':
            case 'select':
                $res = $this->checkValueForGroupSelect($res, $value, $tcaFieldConf, $table, $id, $status, $field);
                break;
            case 'inline':
                $res = $this->checkValueForInline($res, $value, $tcaFieldConf, $table, $id, $status, $field, $additionalData) ?: [];
                break;
            case 'flex':
                // FlexForms are only allowed for real fields.
                if ($field) {
                    $res = $this->checkValueForFlex($res, $value, $tcaFieldConf, $table, $id, $curValue, $status, $realPid, $recFID, $tscPID, $field);
                }
                break;
            default:
                // Do nothing
        }
        $res = $this->checkValueForInternalReferences($res, $value, $tcaFieldConf, $table, $id, $field);
        return $res;
    }

    /**
     * Checks values that are used for internal references. If the provided $value
     * is a NEW-identifier, the direct processing is stopped. Instead, the value is
     * forwarded to the remap-stack to be post-processed and resolved into a proper
     * UID after all data has been resolved.
     *
     * This method considers TCA types that cannot handle and resolve these internal
     * values directly, like 'passthrough', 'none' or 'user'. Values are only modified
     * here if the $field is used as 'transOrigPointerField' or 'translationSource'.
     *
     * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
     * @param string $value The value to set.
     * @param array $tcaFieldConf Field configuration from TCA
     * @param string $table Table name
     * @param int|string $id UID of record
     * @param string $field The field name
     * @return array The result array. The processed value (if any!) is set in the "value" key.
     */
    protected function checkValueForInternalReferences(array $res, $value, $tcaFieldConf, $table, $id, $field)
    {
        $relevantFieldNames = [
            $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null,
            $GLOBALS['TCA'][$table]['ctrl']['translationSource'] ?? null,
        ];

        if (
            // in case field is empty
            empty($field)
            // in case the field is not relevant
            || !in_array($field, $relevantFieldNames)
            // in case the 'value' index has been unset already
            || !array_key_exists('value', $res)
            // in case it's not a NEW-identifier
            || !str_contains($value, 'NEW')
        ) {
            return $res;
        }

        $valueArray = [$value];
        $this->remapStackRecords[$table][$id] = ['remapStackIndex' => count($this->remapStack)];
        $this->addNewValuesToRemapStackChildIds($valueArray);
        $this->remapStack[] = [
            'args' => [$valueArray, $tcaFieldConf, $id, $table, $field],
            'pos' => ['valueArray' => 0, 'tcaFieldConf' => 1, 'id' => 2, 'table' => 3],
            'field' => $field,
        ];
        unset($res['value']);

        return $res;
    }

    /**
     * Evaluate "text" type values.
     *
     * @param string|null $value The value to set.
     * @param array $tcaFieldConf Field configuration from TCA
     * @param string $table Table name
     * @param int $realPid The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted.
     * @param string $field Field name
     * @return array $res The result array. The processed value (if any!) is set in the "value" key.
     */
    protected function checkValueForText($value, $tcaFieldConf, $table, $realPid, $field)
    {
        if (isset($tcaFieldConf['eval']) && $tcaFieldConf['eval'] !== '') {
            $cacheId = $this->getFieldEvalCacheIdentifier($tcaFieldConf['eval']);
            $evalCodesArray = $this->runtimeCache->get($cacheId);
            if (!is_array($evalCodesArray)) {
                $evalCodesArray = GeneralUtility::trimExplode(',', $tcaFieldConf['eval'], true);
                $this->runtimeCache->set($cacheId, $evalCodesArray);
            }
            $valueArray = $this->checkValue_text_Eval($value, $evalCodesArray, $tcaFieldConf['is_in'] ?? '');
        } else {
            $valueArray = ['value' => $value];
        }

        // Handle richtext transformations
        if ($this->dontProcessTransformations) {
            return $valueArray;
        }
        // Keep null as value
        if ($value === null) {
            return $valueArray;
        }
        if (isset($tcaFieldConf['enableRichtext']) && (bool)$tcaFieldConf['enableRichtext'] === true) {
            $recordType = BackendUtility::getTCAtypeValue($table, $this->checkValue_currentRecord);
            $richtextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
            $richtextConfiguration = $richtextConfigurationProvider->getConfiguration($table, $field, $realPid, $recordType, $tcaFieldConf);
            $rteParser = GeneralUtility::makeInstance(RteHtmlParser::class);
            $valueArray['value'] = $rteParser->transformTextForPersistence((string)$value, $richtextConfiguration['proc.'] ?? []);
        }

        return $valueArray;
    }

    /**
     * Evaluate "input" type values.
     *
     * @param string $value The value to set.
     * @param array $tcaFieldConf Field configuration from TCA
     * @param string $table Table name
     * @param int $id UID of record
     * @param int $realPid The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted.
     * @param string $field Field name
     * @return array $res The result array. The processed value (if any!) is set in the "value" key.
     */
    protected function checkValueForInput($value, $tcaFieldConf, $table, $id, $realPid, $field)
    {
        // Handle native date/time fields
        $isDateOrDateTimeField = false;
        $format = '';
        $emptyValue = '';
        $dateTimeTypes = QueryHelper::getDateTimeTypes();
        // normal integer "date" fields (timestamps) are handled in checkValue_input_Eval
        if (isset($tcaFieldConf['dbType']) && in_array($tcaFieldConf['dbType'], $dateTimeTypes, true)) {
            if (empty($value)) {
                $value = null;
            } else {
                $isDateOrDateTimeField = true;
                $dateTimeFormats = QueryHelper::getDateTimeFormats();
                $format = $dateTimeFormats[$tcaFieldConf['dbType']]['format'];

                // Convert the date/time into a timestamp for the sake of the checks
                $emptyValue = $dateTimeFormats[$tcaFieldConf['dbType']]['empty'];
                // We expect the ISO 8601 $value to contain a UTC timezone specifier.
                // We explicitly fallback to UTC if no timezone specifier is given (e.g. for copy operations).
                $dateTime = new \DateTime($value, new \DateTimeZone('UTC'));
                // The timestamp (UTC) returned by getTimestamp() will be converted to
                // a local time string by gmdate() later.
                $value = $value === $emptyValue ? null : $dateTime->getTimestamp();
            }
        }
        // Secures the string-length to be less than max.
        if (isset($tcaFieldConf['max']) && (int)$tcaFieldConf['max'] > 0) {
            $value = mb_substr((string)$value, 0, (int)$tcaFieldConf['max'], 'utf-8');
        }

        if (empty($tcaFieldConf['eval'])) {
            $res = ['value' => $value];
        } else {
            // Process evaluation settings:
            $cacheId = $this->getFieldEvalCacheIdentifier($tcaFieldConf['eval']);
            $evalCodesArray = $this->runtimeCache->get($cacheId);
            if (!is_array($evalCodesArray)) {
                $evalCodesArray = GeneralUtility::trimExplode(',', $tcaFieldConf['eval'], true);
                $this->runtimeCache->set($cacheId, $evalCodesArray);
            }

            $res = $this->checkValue_input_Eval((string)$value, $evalCodesArray, $tcaFieldConf['is_in'] ?? '', $table, $id);
            if (isset($tcaFieldConf['dbType']) && isset($res['value']) && !$res['value']) {
                // set the value to null if we have an empty value for a native field
                $res['value'] = null;
            }

            // Process UNIQUE settings:
            // Field is NOT set for flexForms - which also means that uniqueInPid and unique is NOT available for flexForm fields! Also getUnique should not be done for versioning
            if ($field && !empty($res['value'])) {
                if (in_array('uniqueInPid', $evalCodesArray, true)) {
                    $res['value'] = $this->getUnique($table, $field, $res['value'], $id, $realPid);
                }
                if ($res['value'] && in_array('unique', $evalCodesArray, true)) {
                    $res['value'] = $this->getUnique($table, $field, $res['value'], $id);
                }
            }
        }

        // Skip range validation, if the default value equals 0 and the input value is 0, "0" or an empty string.
        // This is needed for timestamp date fields with ['range']['lower'] set.
        $skipRangeValidation =
            isset($tcaFieldConf['default'], $res['value'])
            && (int)$tcaFieldConf['default'] === 0
            && ($res['value'] === '' || $res['value'] === '0' || $res['value'] === 0);

        // Checking range of value:
        if (!$skipRangeValidation && isset($tcaFieldConf['range']) && is_array($tcaFieldConf['range'])) {
            if (isset($tcaFieldConf['range']['upper']) && ceil($res['value']) > (int)$tcaFieldConf['range']['upper']) {
                $res['value'] = (int)$tcaFieldConf['range']['upper'];
            }
            if (isset($tcaFieldConf['range']['lower']) && floor($res['value']) < (int)$tcaFieldConf['range']['lower']) {
                $res['value'] = (int)$tcaFieldConf['range']['lower'];
            }
        }

        // Handle native date/time fields
        if ($isDateOrDateTimeField) {
            // Convert the timestamp back to a date/time
            $res['value'] = $res['value'] ? gmdate($format, $res['value']) : $emptyValue;
        }
        return $res;
    }

    /**
     * Evaluate "slug" type values.
     *
     * @param string $value The value to set.
     * @param array $tcaFieldConf Field configuration from TCA
     * @param string $table Table name
     * @param int $id UID of record
     * @param int $realPid The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted.
     * @param string $field Field name
     * @param array $incomingFieldArray the fields being explicitly set by the outside (unlike $fieldArray) for the record
     * @return array $res The result array. The processed value (if any!) is set in the "value" key.
     * @see SlugEnricher
     * @see SlugHelper
     */
    protected function checkValueForSlug(string $value, array $tcaFieldConf, string $table, $id, int $realPid, string $field, array $incomingFieldArray = []): array
    {
        $workspaceId = $this->BE_USER->workspace;
        $helper = GeneralUtility::makeInstance(SlugHelper::class, $table, $field, $tcaFieldConf, $workspaceId);
        $fullRecord = array_replace_recursive($this->checkValue_currentRecord, $incomingFieldArray ?? []);
        // Generate a value if there is none, otherwise ensure that all characters are cleaned up
        if ($value === '') {
            $value = $helper->generate($fullRecord, $realPid);
        } else {
            $value = $helper->sanitize($value);
        }

        // Return directly in case no evaluations are defined
        if (empty($tcaFieldConf['eval'])) {
            return ['value' => $value];
        }

        $state = RecordStateFactory::forName($table)
            ->fromArray($fullRecord, $realPid, $id);
        $evalCodesArray = GeneralUtility::trimExplode(',', $tcaFieldConf['eval'], true);
        if (in_array('unique', $evalCodesArray, true)) {
            $value = $helper->buildSlugForUniqueInTable($value, $state);
        }
        if (in_array('uniqueInSite', $evalCodesArray, true)) {
            $value = $helper->buildSlugForUniqueInSite($value, $state);
        }
        if (in_array('uniqueInPid', $evalCodesArray, true)) {
            $value = $helper->buildSlugForUniqueInPid($value, $state);
        }

        return ['value' => $value];
    }

    /**
     * Evaluate "language" type value.
     *
     * Checks whether the user is allowed to add such a value as language
     *
     * @param int $value The value to set.
     * @param string $table Table name
     * @param string $field Field name
     * @return array $res The result array. The processed value (if any!) is set in the "value" key.
     */
    protected function checkValueForLanguage(int $value, string $table, string $field): array
    {
        // If given table is localizable and the given field is the defined
        // languageField, check if the selected language is allowed for the user.
        // Note: Usually this method should never be reached, in case the language value is
        // not valid, since recordEditAccessInternals checks for proper permission beforehand.
        if (BackendUtility::isTableLocalizable($table)
            && ($GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '') === $field
            && !$this->BE_USER->checkLanguageAccess($value)
        ) {
            return [];
        }

        // @todo Should we also check if the language is allowed for the current site - if record has site context?

        return ['value' => $value];
    }

    /**
     * Evaluate 'category' type values
     *
     * @param array $result The result array. The processed value (if any!) is set in the 'value' key.
     * @param string $value The value to set.
     * @param array $tcaFieldConf Field configuration from TCA
     * @param string $table Table name
     * @param int|string $id uid of record
     * @param string $status The status - 'update' or 'new' flag
     * @param string $field Field name
     * @return array
     */
    protected function checkValueForCategory(
        array $result,
        string $value,
        array $tcaFieldConf,
        string $table,
        $id,
        string $status,
        string $field
    ): array {
        // Exploded comma-separated values and remove duplicates
        $valueArray = array_unique(GeneralUtility::trimExplode(',', $value, true));
        // If an exclusive key is found, discard all others:
        if ($tcaFieldConf['exclusiveKeys'] ?? false) {
            $exclusiveKeys = GeneralUtility::trimExplode(',', $tcaFieldConf['exclusiveKeys']);
            foreach ($valueArray as $index => $key) {
                if (in_array($key, $exclusiveKeys, true)) {
                    $valueArray = [$index => $key];
                    break;
                }
            }
        }
        $unsetResult = false;
        if (str_contains($value, 'NEW')) {
            $this->remapStackRecords[$table][$id] = ['remapStackIndex' => count($this->remapStack)];
            $this->addNewValuesToRemapStackChildIds($valueArray);
            $this->remapStack[] = [
                'func' => 'checkValue_category_processDBdata',
                'args' => [$valueArray, $tcaFieldConf, $id, $status, $table, $field],
                'pos' => ['valueArray' => 0, 'tcaFieldConf' => 1, 'id' => 2, 'table' => 4],
                'field' => $field,
            ];
            $unsetResult = true;
        } else {
            $valueArray = $this->checkValue_category_processDBdata($valueArray, $tcaFieldConf, $id, $status, $table, $field);
        }
        if ($unsetResult) {
            unset($result['value']);
        } else {
            $newVal = implode(',', $this->checkValue_checkMax($tcaFieldConf, $valueArray));
            $result['value'] = $newVal !== '' ? $newVal : 0;
        }
        return $result;
    }

    /**
     * Evaluates 'check' type values.
     *
     * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
     * @param string $value The value to set.
     * @param array $tcaFieldConf Field configuration from TCA
     * @param string $table Table name
     * @param int $id UID of record
     * @param int $realPid The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted.
     * @param string $field Field name
     * @return array Modified $res array
     */
    protected function checkValueForCheck($res, $value, $tcaFieldConf, $table, $id, $realPid, $field)
    {
        $items = $tcaFieldConf['items'] ?? null;
        if (!empty($tcaFieldConf['itemsProcFunc'])) {
            $processingService = GeneralUtility::makeInstance(ItemProcessingService::class);
            $items = $processingService->getProcessingItems(
                $table,
                $realPid,
                $field,
                $this->checkValue_currentRecord,
                $tcaFieldConf,
                $tcaFieldConf['items']
            );
        }

        $itemC = 0;
        if ($items !== null) {
            $itemC = count($items);
        }
        if (!$itemC) {
            $itemC = 1;
        }
        $maxV = (2 ** $itemC) - 1;
        if ($value < 0) {
            // @todo: throw LogicException here? Negative values for checkbox items do not make sense and indicate a coding error.
            $value = 0;
        }
        if ($value > $maxV) {
            // @todo: This case is pretty ugly: If there is an itemsProcFunc registered, and if it returns a dynamic,
            // @todo: changing list of items, then it may happen that a value is transformed and vanished checkboxes
            // @todo: are permanently removed from the value.
            // @todo: Suggestion: Throw an exception instead? Maybe a specific, catchable exception that generates a
            // @todo: error message to the user - dynamic item sets via itemProcFunc on check would be a bad idea anyway.
            $value = $value & $maxV;
        }
        if ($field && $value > 0 && !empty($tcaFieldConf['eval'])) {
            $evalCodesArray = GeneralUtility::trimExplode(',', $tcaFieldConf['eval'], true);
            $otherRecordsWithSameValue = [];
            $maxCheckedRecords = 0;
            if (in_array('maximumRecordsCheckedInPid', $evalCodesArray, true)) {
                $otherRecordsWithSameValue = $this->getRecordsWithSameValue($table, $id, $field, $value, $realPid);
                $maxCheckedRecords = (int)$tcaFieldConf['validation']['maximumRecordsCheckedInPid'];
            }
            if (in_array('maximumRecordsChecked', $evalCodesArray, true)) {
                $otherRecordsWithSameValue = $this->getRecordsWithSameValue($table, $id, $field, $value);
                $maxCheckedRecords = (int)$tcaFieldConf['validation']['maximumRecordsChecked'];
            }

            // there are more than enough records with value "1" in the DB
            // if so, set this value to "0" again
            if ($maxCheckedRecords && count($otherRecordsWithSameValue) >= $maxCheckedRecords) {
                $value = 0;
                $this->log($table, $id, SystemLogDatabaseAction::CHECK, 0, SystemLogErrorClassification::USER_ERROR, 'Could not activate checkbox for field "%s". A total of %s record(s) can have this checkbox activated. Uncheck other records first in order to activate the checkbox of this record.', -1, [$this->getLanguageService()->sL(BackendUtility::getItemLabel($table, $field)), $maxCheckedRecords]);
            }
        }
        $res['value'] = $value;
        return $res;
    }

    /**
     * Evaluates 'radio' type values.
     *
     * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
     * @param string $value The value to set.
     * @param array $tcaFieldConf Field configuration from TCA
     * @param string $table The table of the record
     * @param int $id The id of the record
     * @param int $pid The pid of the record
     * @param string $field The field to check
     * @return array Modified $res array
     */
    protected function checkValueForRadio($res, $value, $tcaFieldConf, $table, $id, $pid, $field)
    {
        if (is_array($tcaFieldConf['items'])) {
            foreach ($tcaFieldConf['items'] as $set) {
                if ((string)$set[1] === (string)$value) {
                    $res['value'] = $value;
                    break;
                }
            }
        }

        // if no value was found and an itemsProcFunc is defined, check that for the value
        if (!empty($tcaFieldConf['itemsProcFunc']) && empty($res['value'])) {
            $processingService = GeneralUtility::makeInstance(ItemProcessingService::class);
            $processedItems = $processingService->getProcessingItems(
                $table,
                $pid,
                $field,
                $this->checkValue_currentRecord,
                $tcaFieldConf,
                $tcaFieldConf['items']
            );

            foreach ($processedItems as $set) {
                if ((string)$set[1] === (string)$value) {
                    $res['value'] = $value;
                    break;
                }
            }
        }

        return $res;
    }

    /**
     * Evaluates 'group' or 'select' type values.
     *
     * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
     * @param string|array $value The value to set.
     * @param array $tcaFieldConf Field configuration from TCA
     * @param string $table Table name
     * @param int $id UID of record
     * @param string $status 'update' or 'new' flag
     * @param string $field Field name
     * @return array Modified $res array
     */
    protected function checkValueForGroupSelect($res, $value, $tcaFieldConf, $table, $id, $status, $field)
    {
        // Detecting if value sent is an array and if so, implode it around a comma:
        if (is_array($value)) {
            $value = implode(',', $value);
        } else {
            $value = (string)$value;
        }

        // When values are sent as group or select they come as comma-separated values which are exploded by this function:
        $valueArray = $this->checkValue_group_select_explodeSelectGroupValue($value);
        // If multiple is not set, remove duplicates:
        if (!($tcaFieldConf['multiple'] ?? false)) {
            $valueArray = array_unique($valueArray);
        }
        // If an exclusive key is found, discard all others:
        if ($tcaFieldConf['type'] === 'select' && ($tcaFieldConf['exclusiveKeys'] ?? false)) {
            $exclusiveKeys = GeneralUtility::trimExplode(',', $tcaFieldConf['exclusiveKeys']);
            foreach ($valueArray as $index => $key) {
                if (in_array($key, $exclusiveKeys, true)) {
                    $valueArray = [$index => $key];
                    break;
                }
            }
        }
        // This could be a good spot for parsing the array through a validation-function which checks if the values are correct (except that database references are not in their final form - but that is the point, isn't it?)
        // NOTE!!! Must check max-items of files before the later check because that check would just leave out file names if there are too many!!
        $valueArray = $this->applyFiltersToValues($tcaFieldConf, $valueArray);
        // Checking for select / authMode, removing elements from $valueArray if any of them is not allowed!
        if ($tcaFieldConf['type'] === 'select' && ($tcaFieldConf['authMode'] ?? false)) {
            $preCount = count($valueArray);
            foreach ($valueArray as $index => $key) {
                if (!$this->BE_USER->checkAuthMode($table, $field, $key, $tcaFieldConf['authMode'])) {
                    unset($valueArray[$index]);
                }
            }
            // During the check it turns out that the value / all values were removed - we respond by simply returning an empty array so nothing is written to DB for this field.
            if ($preCount && empty($valueArray)) {
                return [];
            }
        }
        // For select types which has a foreign table attached:
        $unsetResult = false;
        if (($tcaFieldConf['type'] === 'group' && ($tcaFieldConf['internal_type'] ?? '') !== 'folder')
            || ($tcaFieldConf['type'] === 'select' && ($tcaFieldConf['foreign_table'] ?? false))
        ) {
            // check, if there is a NEW... id in the value, that should be substituted later
            if (str_contains($value, 'NEW')) {
                $this->remapStackRecords[$table][$id] = ['remapStackIndex' => count($this->remapStack)];
                $this->addNewValuesToRemapStackChildIds($valueArray);
                $this->remapStack[] = [
                    'func' => 'checkValue_group_select_processDBdata',
                    'args' => [$valueArray, $tcaFieldConf, $id, $status, $tcaFieldConf['type'], $table, $field],
                    'pos' => ['valueArray' => 0, 'tcaFieldConf' => 1, 'id' => 2, 'table' => 5],
                    'field' => $field,
                ];
                $unsetResult = true;
            } else {
                $valueArray = $this->checkValue_group_select_processDBdata($valueArray, $tcaFieldConf, $id, $status, $tcaFieldConf['type'], $table, $field);
            }
        }
        if (!$unsetResult) {
            $newVal = $this->checkValue_checkMax($tcaFieldConf, $valueArray);
            $res['value'] = $this->castReferenceValue(implode(',', $newVal), $tcaFieldConf);
        } else {
            unset($res['value']);
        }
        return $res;
    }

    /**
     * Applies the filter methods from a column's TCA configuration to a value array.
     *
     * @param array $tcaFieldConfiguration
     * @param array $values
     * @return array|mixed
     * @throws \RuntimeException
     */
    protected function applyFiltersToValues(array $tcaFieldConfiguration, array $values)
    {
        if (!is_array($tcaFieldConfiguration['filter'] ?? null)) {
            return $values;
        }
        foreach ($tcaFieldConfiguration['filter'] as $filter) {
            if (empty($filter['userFunc'])) {
                continue;
            }
            $parameters = $filter['parameters'] ?? [];
            if (!is_array($parameters)) {
                $parameters = [];
            }
            $parameters['values'] = $values;
            $parameters['tcaFieldConfig'] = $tcaFieldConfiguration;
            $values = GeneralUtility::callUserFunction($filter['userFunc'], $parameters, $this);
            if (!is_array($values)) {
                throw new \RuntimeException('Expected userFunc filter "' . $filter['userFunc'] . '" to return an array. Got ' . gettype($values) . '.', 1336051942);
            }
        }
        return $values;
    }

    /**
     * Evaluates 'flex' type values.
     *
     * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
     * @param string|array $value The value to set.
     * @param array $tcaFieldConf Field configuration from TCA
     * @param string $table Table name
     * @param int $id UID of record
     * @param mixed $curValue Current value of the field
     * @param string $status 'update' or 'new' flag
     * @param int $realPid The real PID value of the record. For updates, this is just the pid of the record. For new records this is the PID of the page where it is inserted.
     * @param string $recFID Field identifier [table:uid:field] for flexforms
     * @param int $tscPID TSconfig PID
     * @param string $field Field name
     * @return array Modified $res array
     */
    protected function checkValueForFlex($res, $value, $tcaFieldConf, $table, $id, $curValue, $status, $realPid, $recFID, $tscPID, $field)
    {
        if (is_array($value)) {
            // This value is necessary for flex form processing to happen on flexform fields in page records when they are copied.
            // Problem: when copying a page, flexform XML comes along in the array for the new record - but since $this->checkValue_currentRecord
            // does not have a uid or pid for that sake, the FlexFormTools->getDataStructureIdentifier() function returns no good DS. For new
            // records we do know the expected PID so therefore we send that with this special parameter. Only active when larger than zero.
            $row = $this->checkValue_currentRecord;
            if ($status === 'new') {
                $row['pid'] = $realPid;
            }

            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

            // Get data structure. The methods may throw various exceptions, with some of them being
            // ok in certain scenarios, for instance on new record rows. Those are ok to "eat" here
            // and substitute with a dummy DS.
            $dataStructureArray = ['sheets' => ['sDEF' => []]];
            try {
                $dataStructureIdentifier = $flexFormTools->getDataStructureIdentifier(
                    ['config' => $tcaFieldConf],
                    $table,
                    $field,
                    $row
                );

                $dataStructureArray = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
            } catch (InvalidParentRowException|InvalidParentRowLoopException|InvalidParentRowRootException|InvalidPointerFieldValueException|InvalidIdentifierException $e) {
            }

            // Get current value array:
            $currentValueArray = (string)$curValue !== '' ? GeneralUtility::xml2array($curValue) : [];
            if (!is_array($currentValueArray)) {
                $currentValueArray = [];
            }
            // Remove all old meta for languages...
            // Evaluation of input values:
            $value['data'] = $this->checkValue_flex_procInData($value['data'] ?? [], $currentValueArray['data'] ?? [], $dataStructureArray, [$table, $id, $curValue, $status, $realPid, $recFID, $tscPID]);
            // Create XML from input value:
            $xmlValue = $this->checkValue_flexArray2Xml($value, true);

            // Here we convert the currently submitted values BACK to an array, then merge the two and then BACK to XML again. This is needed to ensure the charsets are the same
            // (provided that the current value was already stored IN the charset that the new value is converted to).
            $arrValue = GeneralUtility::xml2array($xmlValue);

            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkFlexFormValue'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (method_exists($hookObject, 'checkFlexFormValue_beforeMerge')) {
                    $hookObject->checkFlexFormValue_beforeMerge($this, $currentValueArray, $arrValue);
                }
            }

            ArrayUtility::mergeRecursiveWithOverrule($currentValueArray, $arrValue);
            $xmlValue = $this->checkValue_flexArray2Xml($currentValueArray, true);

            // Action commands (sorting order and removals of elements) for flexform sections,
            // see FormEngine for the use of this GP parameter
            $actionCMDs = GeneralUtility::_GP('_ACTION_FLEX_FORMdata');
            if (is_array($actionCMDs[$table][$id][$field]['data'] ?? null)) {
                $arrValue = GeneralUtility::xml2array($xmlValue);
                $this->_ACTION_FLEX_FORMdata($arrValue['data'], $actionCMDs[$table][$id][$field]['data']);
                $xmlValue = $this->checkValue_flexArray2Xml($arrValue, true);
            }
            // Create the value XML:
            $res['value'] = '';
            $res['value'] .= $xmlValue;
        } else {
            // Passthrough...:
            $res['value'] = $value;
        }

        return $res;
    }

    /**
     * Converts an array to FlexForm XML
     *
     * @param array $array Array with FlexForm data
     * @param bool $addPrologue If set, the XML prologue is returned as well.
     * @return string Input array converted to XML
     * @internal should only be used from within DataHandler
     */
    public function checkValue_flexArray2Xml($array, $addPrologue = false)
    {
        $flexObj = GeneralUtility::makeInstance(FlexFormTools::class);
        return $flexObj->flexArray2Xml($array, $addPrologue);
    }

    /**
     * Actions for flex form element (move, delete)
     * allows to remove and move flexform sections
     *
     * @param array $valueArray by reference
     * @param array $actionCMDs
     */
    protected function _ACTION_FLEX_FORMdata(&$valueArray, $actionCMDs)
    {
        if (!is_array($valueArray) || !is_array($actionCMDs)) {
            return;
        }

        foreach ($actionCMDs as $key => $value) {
            if ($key === '_ACTION') {
                // First, check if there are "commands":
                if (empty(array_filter($actionCMDs[$key]))) {
                    continue;
                }

                asort($actionCMDs[$key]);
                $newValueArray = [];
                foreach ($actionCMDs[$key] as $idx => $order) {
                    // Just one reflection here: It is clear that when removing elements from a flexform, then we will get lost
                    // files unless we act on this delete operation by traversing and deleting files that were referred to.
                    if ($order !== 'DELETE') {
                        $newValueArray[$idx] = $valueArray[$idx];
                    }
                    unset($valueArray[$idx]);
                }
                $valueArray += $newValueArray;
            } elseif (is_array($actionCMDs[$key]) && isset($valueArray[$key])) {
                $this->_ACTION_FLEX_FORMdata($valueArray[$key], $actionCMDs[$key]);
            }
        }
    }

    /**
     * Evaluates 'inline' type values.
     * (partly copied from the select_group function on this issue)
     *
     * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
     * @param string $value The value to set.
     * @param array $tcaFieldConf Field configuration from TCA
     * @param array $PP Additional parameters in a numeric array: $table,$id,$curValue,$status,$realPid,$recFID
     * @param string $field Field name
     * @param array $additionalData Additional data to be forwarded to sub-processors
     * @internal should only be used from within DataHandler
     */
    public function checkValue_inline($res, $value, $tcaFieldConf, $PP, $field, array $additionalData = null)
    {
        [$table, $id, , $status] = $PP;
        $this->checkValueForInline($res, $value, $tcaFieldConf, $table, $id, $status, $field, $additionalData);
    }

    /**
     * Evaluates 'inline' type values.
     * (partly copied from the select_group function on this issue)
     *
     * @param array $res The result array. The processed value (if any!) is set in the 'value' key.
     * @param string $value The value to set.
     * @param array $tcaFieldConf Field configuration from TCA
     * @param string $table Table name
     * @param int $id UID of record
     * @param string $status 'update' or 'new' flag
     * @param string $field Field name
     * @param array $additionalData Additional data to be forwarded to sub-processors
     * @return array|false Modified $res array
     * @internal should only be used from within DataHandler
     */
    public function checkValueForInline($res, $value, $tcaFieldConf, $table, $id, $status, $field, array $additionalData = null)
    {
        if (!$tcaFieldConf['foreign_table']) {
            // Fatal error, inline fields should always have a foreign_table defined
            return false;
        }
        // When values are sent they come as comma-separated values which are exploded by this function:
        $valueArray = GeneralUtility::trimExplode(',', $value);
        // Remove duplicates: (should not be needed)
        $valueArray = array_unique($valueArray);
        // Example for received data:
        // $value = 45,NEW4555fdf59d154,12,123
        // We need to decide whether we use the stack or can save the relation directly.
        if (!empty($value) && (str_contains($value, 'NEW') || !MathUtility::canBeInterpretedAsInteger($id))) {
            $this->remapStackRecords[$table][$id] = ['remapStackIndex' => count($this->remapStack)];
            $this->addNewValuesToRemapStackChildIds($valueArray);
            $this->remapStack[] = [
                'func' => 'checkValue_inline_processDBdata',
                'args' => [$valueArray, $tcaFieldConf, $id, $status, $table, $field, $additionalData],
                'pos' => ['valueArray' => 0, 'tcaFieldConf' => 1, 'id' => 2, 'table' => 4],
                'additionalData' => $additionalData,
                'field' => $field,
            ];
            unset($res['value']);
        } elseif ($value || MathUtility::canBeInterpretedAsInteger($id)) {
            $res['value'] = $this->checkValue_inline_processDBdata($valueArray, $tcaFieldConf, $id, $status, $table, $field);
        }
        return $res;
    }

    /**
     * Checks if a fields has more items than defined via TCA in maxitems.
     * If there are more items than allowed, the item list is truncated to the defined number.
     *
     * @param array $tcaFieldConf Field configuration from TCA
     * @param array $valueArray Current value array of items
     * @return array The truncated value array of items
     * @internal should only be used from within DataHandler
     */
    public function checkValue_checkMax($tcaFieldConf, $valueArray)
    {
        // BTW, checking for min and max items here does NOT make any sense when MM is used because the above function
        // calls will just return an array with a single item (the count) if MM is used... Why didn't I perform the check
        // before? Probably because we could not evaluate the validity of record uids etc... Hmm...
        // NOTE to the comment: It's not really possible to check for too few items, because you must then determine first,
        // if the field is actual used regarding the CType.
        $maxitems = isset($tcaFieldConf['maxitems']) ? (int)$tcaFieldConf['maxitems'] : 99999;
        return array_slice($valueArray, 0, $maxitems);
    }

    /*********************************************
     *
     * Helper functions for evaluation functions.
     *
     ********************************************/
    /**
     * Gets a unique value for $table/$id/$field based on $value
     *
     * @param string $table Table name
     * @param string $field Field name for which $value must be unique
     * @param string $value Value string.
     * @param int $id UID to filter out in the lookup (the record itself...)
     * @param int $newPid If set, the value will be unique for this PID
     * @return string Modified value (if not-unique). Will be the value appended with a number (until 100, then the function just breaks).
     * @todo: consider workspaces, especially when publishing a unique value which has a unique value already in live
     * @internal should only be used from within DataHandler
     */
    public function getUnique($table, $field, $value, $id, $newPid = 0)
    {
        if (!is_array($GLOBALS['TCA'][$table]) || !is_array($GLOBALS['TCA'][$table]['columns'][$field])) {
            // Field is not configured in TCA
            return $value;
        }

        if (($GLOBALS['TCA'][$table]['columns'][$field]['l10n_mode'] ?? '') === 'exclude') {
            $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
            $l10nParent = (int)$this->checkValue_currentRecord[$transOrigPointerField];
            if ($l10nParent > 0) {
                // Current record is a translation and l10n_mode "exclude" just copies the value from source language
                return $value;
            }
        }

        $newValue = $originalValue = $value;
        $queryBuilder = $this->getUniqueCountStatement($newValue, $table, $field, (int)$id, (int)$newPid);
        // For as long as records with the test-value existing, try again (with incremented numbers appended)
        $statement = $queryBuilder->prepare();
        $result = $statement->executeQuery();
        if ($result->fetchOne()) {
            for ($counter = 0; $counter <= 100; $counter++) {
                $result->free();
                $newValue = $value . $counter;
                $statement->bindValue(1, $newValue);
                $result = $statement->executeQuery();
                if (!$result->fetchOne()) {
                    break;
                }
            }
            $result->free();
        }

        if ($originalValue !== $newValue) {
            $this->log($table, $id, SystemLogDatabaseAction::CHECK, 0, SystemLogErrorClassification::WARNING, 'The value of the field "%s" has been changed from "%s" to "%s" as it is required to be unique.', 1, [$field, $originalValue, $newValue], $newPid);
        }

        return $newValue;
    }

    /**
     * Gets the count of records for a unique field
     *
     * @param string $value The string value which should be unique
     * @param string $table Table name
     * @param string $field Field name for which $value must be unique
     * @param int $uid UID to filter out in the lookup (the record itself...)
     * @param int $pid If set, the value will be unique for this PID
     * @return QueryBuilder Return the prepared statement to check uniqueness
     */
    protected function getUniqueCountStatement(
        string $value,
        string $table,
        string $field,
        int $uid,
        int $pid
    ) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());
        $queryBuilder
            ->count('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq($field, $queryBuilder->createPositionalParameter($value)),
                $queryBuilder->expr()->neq('uid', $queryBuilder->createPositionalParameter($uid, \PDO::PARAM_INT))
            );
        // ignore translations of current record if field is configured with l10n_mode = "exclude"
        if (($GLOBALS['TCA'][$table]['columns'][$field]['l10n_mode'] ?? '') === 'exclude'
            && ($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? '') !== ''
            && ($GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '') !== '') {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                    // records without l10n_parent must be taken into account (in any language)
                        $queryBuilder->expr()->eq(
                            $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                            $queryBuilder->createPositionalParameter(0, \PDO::PARAM_INT)
                        ),
                        // translations of other records must be taken into account
                        $queryBuilder->expr()->neq(
                            $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                            $queryBuilder->createPositionalParameter($uid, \PDO::PARAM_INT)
                        )
                    )
                );
        }
        if ($pid !== 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createPositionalParameter($pid, \PDO::PARAM_INT))
            );
        } else {
            // pid>=0 for versioning
            $queryBuilder->andWhere(
                $queryBuilder->expr()->gte('pid', $queryBuilder->createPositionalParameter(0, \PDO::PARAM_INT))
            );
        }
        return $queryBuilder;
    }

    /**
     * gets all records that have the same value in a field
     * excluding the given uid
     *
     * @param string $tableName Table name
     * @param int $uid UID to filter out in the lookup (the record itself...)
     * @param string $fieldName Field name for which $value must be unique
     * @param string $value Value string.
     * @param int $pageId If set, the value will be unique for this PID
     * @return array
     * @internal should only be used from within DataHandler
     */
    public function getRecordsWithSameValue($tableName, $uid, $fieldName, $value, $pageId = 0)
    {
        $result = [];
        if (empty($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
            return $result;
        }

        $uid = (int)$uid;
        $pageId = (int)$pageId;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->BE_USER->workspace));

        $queryBuilder->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    $fieldName,
                    $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->neq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            );

        if ($pageId) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT))
            );
        }

        $result = $queryBuilder->executeQuery()->fetchAllAssociative();

        return $result;
    }

    /**
     * @param string $value The field value to be evaluated
     * @param array $evalArray Array of evaluations to traverse.
     * @param string $is_in The "is_in" value of the field configuration from TCA
     * @return array
     * @internal should only be used from within DataHandler
     */
    public function checkValue_text_Eval($value, $evalArray, $is_in)
    {
        $res = [];
        $set = true;
        foreach ($evalArray as $func) {
            switch ($func) {
                case 'trim':
                    $value = trim((string)$value);
                    break;
                case 'required':
                    if (!$value) {
                        $set = false;
                    }
                    break;
                default:
                    if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func])) {
                        if (class_exists($func)) {
                            $evalObj = GeneralUtility::makeInstance($func);
                            if (method_exists($evalObj, 'evaluateFieldValue')) {
                                $value = $evalObj->evaluateFieldValue($value, $is_in, $set);
                            }
                        }
                    }
            }
        }
        if ($set) {
            $res['value'] = $value;
        }
        return $res;
    }

    /**
     * Evaluation of 'input'-type values based on 'eval' list
     *
     * @param string $value Value to evaluate
     * @param array $evalArray Array of evaluations to traverse.
     * @param string $is_in Is-in string for 'is_in' evaluation
     * @param string $table Table name the eval is evaluated on
     * @param string|int $id Record ID the eval is evaluated on
     * @return array Modified $value in key 'value' or empty array
     * @internal should only be used from within DataHandler
     */
    public function checkValue_input_Eval($value, $evalArray, $is_in, string $table = '', $id = ''): array
    {
        $res = [];
        $set = true;
        foreach ($evalArray as $func) {
            switch ($func) {
                case 'int':
                case 'year':
                    $value = (int)$value;
                    break;
                case 'time':
                case 'timesec':
                    // If $value is a pure integer we have the number of seconds, we can store that directly
                    if ($value !== '' && !MathUtility::canBeInterpretedAsInteger($value)) {
                        // $value is an ISO 8601 date
                        $value = (new \DateTime($value))->getTimestamp();
                    }
                    break;
                case 'date':
                case 'datetime':
                    // If $value is a pure integer we have the number of seconds, we can store that directly
                    if ($value !== null && $value !== '' && !MathUtility::canBeInterpretedAsInteger($value)) {
                        // The value we receive from JS is an ISO 8601 date, which is always in UTC. (the JS code works like that, on purpose!)
                        // For instance "1999-11-11T11:11:11Z"
                        // Since the user actually specifies the time in the server's local time, we need to mangle this
                        // to reflect the server TZ. So we make this 1999-11-11T11:11:11+0200 (assuming Europe/Vienna here)
                        // In the database we store the date in UTC (1999-11-11T09:11:11Z), hence we take the timestamp of this converted value.
                        // For achieving this we work with timestamps only (which are UTC) and simply adjust it for the
                        // TZ difference.
                        try {
                            // Make the date from JS a timestamp
                            $value = (new \DateTime($value))->getTimestamp();
                        } catch (\Exception $e) {
                            // set the default timezone value to achieve the value of 0 as a result
                            $value = (int)date('Z', 0);
                        }

                        // @todo this hacky part is problematic when it comes to times around DST switch! Add test to prove that this is broken.
                        $value -= date('Z', $value);
                    }
                    break;
                case 'double2':
                    $value = preg_replace('/[^0-9,\\.-]/', '', $value);
                    $negative = substr($value, 0, 1) === '-';
                    $value = strtr($value, [',' => '.', '-' => '']);
                    if (!str_contains($value, '.')) {
                        $value .= '.0';
                    }
                    $valueArray = explode('.', $value);
                    $dec = array_pop($valueArray);
                    $value = implode('', $valueArray) . '.' . $dec;
                    if ($negative) {
                        $value *= -1;
                    }
                    $value = number_format((float)$value, 2, '.', '');
                    break;
                case 'md5':
                    if (strlen($value) !== 32) {
                        $set = false;
                    }
                    break;
                case 'trim':
                    $value = trim($value);
                    break;
                case 'upper':
                    $value = mb_strtoupper($value, 'utf-8');
                    break;
                case 'lower':
                    $value = mb_strtolower($value, 'utf-8');
                    break;
                case 'required':
                    if (!isset($value) || $value === '') {
                        $set = false;
                    }
                    break;
                case 'is_in':
                    $c = mb_strlen($value);
                    if ($c) {
                        $newVal = '';
                        for ($a = 0; $a < $c; $a++) {
                            $char = mb_substr($value, $a, 1);
                            if (str_contains($is_in, $char)) {
                                $newVal .= $char;
                            }
                        }
                        $value = $newVal;
                    }
                    break;
                case 'nospace':
                    $value = str_replace(' ', '', $value);
                    break;
                case 'alpha':
                    $value = preg_replace('/[^a-zA-Z]/', '', $value);
                    break;
                case 'num':
                    $value = preg_replace('/[^0-9]/', '', $value);
                    break;
                case 'alphanum':
                    $value = preg_replace('/[^a-zA-Z0-9]/', '', $value);
                    break;
                case 'alphanum_x':
                    $value = preg_replace('/[^a-zA-Z0-9_-]/', '', $value);
                    break;
                case 'domainname':
                    if (!preg_match('/^[a-z0-9.\\-]*$/i', $value)) {
                        $value = (string)idn_to_ascii($value);
                    }
                    break;
                case 'email':
                    if ((string)$value !== '') {
                        $this->checkValue_input_ValidateEmail($value, $set, $table, $id);
                    }
                    break;
                case 'saltedPassword':
                    // An incoming value is either the salted password if the user did not change existing password
                    // when submitting the form, or a plaintext new password that needs to be turned into a salted password now.
                    // The strategy is to see if a salt instance can be created from the incoming value. If so,
                    // no new password was submitted and we keep the value. If no salting instance can be created,
                    // incoming value must be a new plain text value that needs to be hashed.
                    $hashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
                    $mode = $table === 'fe_users' ? 'FE' : 'BE';
                    try {
                        $hashFactory->get($value, $mode);
                    } catch (InvalidPasswordHashException $e) {
                        // We got no salted password instance, incoming value must be a new plaintext password
                        // Get an instance of the current configured salted password strategy and hash the value
                        $newHashInstance = $hashFactory->getDefaultHashInstance($mode);
                        $value = $newHashInstance->getHashedPassword($value);
                    }
                    break;
                default:
                    if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func])) {
                        if (class_exists($func)) {
                            $evalObj = GeneralUtility::makeInstance($func);
                            if (method_exists($evalObj, 'evaluateFieldValue')) {
                                $value = $evalObj->evaluateFieldValue($value, $is_in, $set);
                            }
                        }
                    }
            }
        }
        if ($set) {
            $res['value'] = $value;
        }
        return $res;
    }

    /**
     * If $value is not a valid e-mail address,
     * $set will be set to false and a flash error
     * message will be added
     *
     * @param string $value Value to evaluate
     * @param bool $set TRUE if an update should be done
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function checkValue_input_ValidateEmail($value, &$set, string $table, $id)
    {
        if (GeneralUtility::validEmail($value)) {
            return;
        }

        $set = false;
        $this->log(
            $table,
            $id,
            SystemLogDatabaseAction::UPDATE,
            0,
            SystemLogErrorClassification::SECURITY_NOTICE,
            '"' . $value . '" is not a valid e-mail address.',
            -1,
            [$this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.invalidEmail'), $value]
        );
    }

    /**
     * Returns processed data for category fields
     *
     * @param array $valueArray Current value array
     * @param array $tcaFieldConf TCA field config
     * @param string|int $id Record id, used for look-up of MM relations (local_uid)
     * @param string $status Status string ('update' or 'new')
     * @param string $table Table name, needs to be passed to \TYPO3\CMS\Core\Database\RelationHandler
     * @param string $field field name, needs to be set for writing to sys_history
     * @return array Modified value array
     * @internal should only be used from within DataHandler
     */
    public function checkValue_category_processDBdata(
        array $valueArray,
        array $tcaFieldConf,
        $id,
        string $status,
        string $table,
        string $field
    ): array {
        $newRelations = implode(',', $valueArray);
        $relationHandler = $this->createRelationHandlerInstance();
        $relationHandler->start($newRelations, $tcaFieldConf['foreign_table'], '', 0, $table, $tcaFieldConf);
        if ($tcaFieldConf['MM'] ?? false) {
            $relationHandler->convertItemArray();
            if ($status === 'update') {
                $relationHandleForOldRelations = $this->createRelationHandlerInstance();
                $relationHandleForOldRelations->start('', $tcaFieldConf['foreign_table'], $tcaFieldConf['MM'], $id, $table, $tcaFieldConf);
                $oldRelations = implode(',', $relationHandleForOldRelations->getValueArray());
                $relationHandler->writeMM($tcaFieldConf['MM'], $id);
                if ($oldRelations !== $newRelations) {
                    $this->mmHistoryRecords[$table . ':' . $id]['oldRecord'][$field] = $oldRelations;
                    $this->mmHistoryRecords[$table . ':' . $id]['newRecord'][$field] = $newRelations;
                } else {
                    $this->mmHistoryRecords[$table . ':' . $id]['oldRecord'][$field] = '';
                    $this->mmHistoryRecords[$table . ':' . $id]['newRecord'][$field] = '';
                }
            } else {
                $this->dbAnalysisStore[] = [$relationHandler, $tcaFieldConf['MM'], $id, '', $table];
            }
            $valueArray = $relationHandler->countItems();
        } else {
            $valueArray = $relationHandler->getValueArray();
        }
        return $valueArray;
    }

    /**
     * Returns data for group/db and select fields
     *
     * @param array $valueArray Current value array
     * @param array $tcaFieldConf TCA field config
     * @param int $id Record id, used for look-up of MM relations (local_uid)
     * @param string $status Status string ('update' or 'new')
     * @param string $type The type, either 'select', 'group' or 'inline'
     * @param string $currentTable Table name, needs to be passed to \TYPO3\CMS\Core\Database\RelationHandler
     * @param string $currentField field name, needs to be set for writing to sys_history
     * @return array Modified value array
     * @internal should only be used from within DataHandler
     */
    public function checkValue_group_select_processDBdata($valueArray, $tcaFieldConf, $id, $status, $type, $currentTable, $currentField)
    {
        $tables = $type === 'group' ? $tcaFieldConf['allowed'] : $tcaFieldConf['foreign_table'];
        $prep = $type === 'group' ? ($tcaFieldConf['prepend_tname'] ?? '') : '';
        $newRelations = implode(',', $valueArray);
        $dbAnalysis = $this->createRelationHandlerInstance();
        $dbAnalysis->registerNonTableValues = !empty($tcaFieldConf['allowNonIdValues']);
        $dbAnalysis->start($newRelations, $tables, '', 0, $currentTable, $tcaFieldConf);
        if ($tcaFieldConf['MM'] ?? false) {
            // convert submitted items to use version ids instead of live ids
            // (only required for MM relations in a workspace context)
            $dbAnalysis->convertItemArray();
            if ($status === 'update') {
                $oldRelations_dbAnalysis = $this->createRelationHandlerInstance();
                $oldRelations_dbAnalysis->registerNonTableValues = !empty($tcaFieldConf['allowNonIdValues']);
                // Db analysis with $id will initialize with the existing relations
                $oldRelations_dbAnalysis->start('', $tables, $tcaFieldConf['MM'], $id, $currentTable, $tcaFieldConf);
                $oldRelations = implode(',', $oldRelations_dbAnalysis->getValueArray());
                $dbAnalysis->writeMM($tcaFieldConf['MM'], $id, $prep);
                if ($oldRelations != $newRelations) {
                    $this->mmHistoryRecords[$currentTable . ':' . $id]['oldRecord'][$currentField] = $oldRelations;
                    $this->mmHistoryRecords[$currentTable . ':' . $id]['newRecord'][$currentField] = $newRelations;
                } else {
                    $this->mmHistoryRecords[$currentTable . ':' . $id]['oldRecord'][$currentField] = '';
                    $this->mmHistoryRecords[$currentTable . ':' . $id]['newRecord'][$currentField] = '';
                }
            } else {
                $this->dbAnalysisStore[] = [$dbAnalysis, $tcaFieldConf['MM'], $id, $prep, $currentTable];
            }
            $valueArray = $dbAnalysis->countItems();
        } else {
            $valueArray = $dbAnalysis->getValueArray($prep);
        }
        // Here we should see if 1) the records exist anymore, 2) which are new and check if the BE_USER has read-access to the new ones.
        return $valueArray;
    }

    /**
     * Explodes the $value, which is a list of files/uids (group select)
     *
     * @param string $value Input string, comma separated values. For each part it will also be detected if a '|' is found and the first part will then be used if that is the case. Further the value will be rawurldecoded.
     * @return array The value array.
     * @internal should only be used from within DataHandler
     */
    public function checkValue_group_select_explodeSelectGroupValue($value)
    {
        $valueArray = GeneralUtility::trimExplode(',', $value, true);
        foreach ($valueArray as &$newVal) {
            $temp = explode('|', $newVal, 2);
            $newVal = str_replace(['|', ','], '', rawurldecode($temp[0]));
        }
        unset($newVal);
        return $valueArray;
    }

    /**
     * Starts the processing the input data for flexforms. This will traverse all sheets / languages and for each it will traverse the sub-structure.
     * See checkValue_flex_procInData_travDS() for more details.
     * WARNING: Currently, it traverses based on the actual _data_ array and NOT the _structure_. This means that values for non-valid fields, lKey/vKey/sKeys will be accepted! For traversal of data with a call back function you should rather use \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools
     *
     * @param array $dataPart The 'data' part of the INPUT flexform data
     * @param array $dataPart_current The 'data' part of the CURRENT flexform data
     * @param array $dataStructure Data structure for the form (might be sheets or not). Only values in the data array which has a configuration in the data structure will be processed.
     * @param array $pParams A set of parameters to pass through for the calling of the evaluation functions
     * @param string $callBackFunc Optional call back function, see checkValue_flex_procInData_travDS()  DEPRECATED, use \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools instead for traversal!
     * @param array $workspaceOptions
     * @return array The modified 'data' part.
     * @see checkValue_flex_procInData_travDS()
     * @internal should only be used from within DataHandler
     */
    public function checkValue_flex_procInData($dataPart, $dataPart_current, $dataStructure, $pParams, $callBackFunc = '', array $workspaceOptions = [])
    {
        if (is_array($dataPart)) {
            foreach ($dataPart as $sKey => $sheetDef) {
                if (isset($dataStructure['sheets'][$sKey]) && is_array($dataStructure['sheets'][$sKey]) && is_array($sheetDef)) {
                    foreach ($sheetDef as $lKey => $lData) {
                        $this->checkValue_flex_procInData_travDS(
                            $dataPart[$sKey][$lKey],
                            $dataPart_current[$sKey][$lKey] ?? null,
                            $dataStructure['sheets'][$sKey]['ROOT']['el'] ?? null,
                            $pParams,
                            $callBackFunc,
                            $sKey . '/' . $lKey . '/',
                            $workspaceOptions
                        );
                    }
                }
            }
        }
        return $dataPart;
    }

    /**
     * Processing of the sheet/language data array
     * When it finds a field with a value the processing is done by ->checkValue_SW() by default but if a call back function name is given that method in this class will be called for the processing instead.
     *
     * @param array $dataValues New values (those being processed): Multidimensional Data array for sheet/language, passed by reference!
     * @param array $dataValues_current Current values: Multidimensional Data array. May be empty array() if not needed (for callBackFunctions)
     * @param array $DSelements Data structure which fits the data array
     * @param array $pParams A set of parameters to pass through for the calling of the evaluation functions / call back function
     * @param string $callBackFunc Call back function, default is checkValue_SW(). If $this->callBackObj is set to an object, the callback function in that object is called instead.
     * @param string $structurePath
     * @param array $workspaceOptions
     * @see checkValue_flex_procInData()
     * @internal should only be used from within DataHandler
     */
    public function checkValue_flex_procInData_travDS(&$dataValues, $dataValues_current, $DSelements, $pParams, $callBackFunc, $structurePath, array $workspaceOptions = [])
    {
        if (!is_array($DSelements)) {
            return;
        }

        // For each DS element:
        foreach ($DSelements as $key => $dsConf) {
            // Array/Section:
            if (isset($DSelements[$key]['type']) && $DSelements[$key]['type'] === 'array') {
                if (!is_array($dataValues[$key]['el'] ?? null)) {
                    continue;
                }

                if ($DSelements[$key]['section']) {
                    foreach ($dataValues[$key]['el'] as $ik => $el) {
                        if (!is_array($el)) {
                            continue;
                        }

                        if (!is_array($dataValues_current[$key]['el'] ?? false)) {
                            $dataValues_current[$key]['el'] = [];
                        }
                        $theKey = key($el);
                        if (!is_array($dataValues[$key]['el'][$ik][$theKey]['el'])) {
                            continue;
                        }

                        $this->checkValue_flex_procInData_travDS(
                            $dataValues[$key]['el'][$ik][$theKey]['el'],
                            $dataValues_current[$key]['el'][$ik][$theKey]['el'] ?? [],
                            $DSelements[$key]['el'][$theKey]['el'] ?? [],
                            $pParams,
                            $callBackFunc,
                            $structurePath . $key . '/el/' . $ik . '/' . $theKey . '/el/',
                            $workspaceOptions
                        );
                    }
                } else {
                    if (!isset($dataValues[$key]['el'])) {
                        $dataValues[$key]['el'] = [];
                    }
                    $this->checkValue_flex_procInData_travDS($dataValues[$key]['el'], $dataValues_current[$key]['el'], $DSelements[$key]['el'], $pParams, $callBackFunc, $structurePath . $key . '/el/', $workspaceOptions);
                }
            } else {
                // When having no specific sheets, it's "TCEforms.config", when having a sheet, it's just "config"
                $fieldConfiguration = $dsConf['TCEforms']['config'] ?? $dsConf['config'] ?? null;
                // init with value from config for passthrough fields
                if (!empty($fieldConfiguration['type']) && $fieldConfiguration['type'] === 'passthrough') {
                    if (!empty($dataValues_current[$key]['vDEF'])) {
                        // If there is existing value, keep it
                        $dataValues[$key]['vDEF'] = $dataValues_current[$key]['vDEF'];
                    } elseif (
                        !empty($fieldConfiguration['default'])
                        && isset($pParams[1])
                        && !MathUtility::canBeInterpretedAsInteger($pParams[1])
                    ) {
                        // If is new record and a default is specified for field, use it.
                        $dataValues[$key]['vDEF'] = $fieldConfiguration['default'];
                    }
                }
                if (!is_array($fieldConfiguration) || !isset($dataValues[$key]) || !is_array($dataValues[$key])) {
                    continue;
                }

                foreach ($dataValues[$key] as $vKey => $data) {
                    if ($callBackFunc) {
                        if (is_object($this->callBackObj)) {
                            $res = $this->callBackObj->{$callBackFunc}(
                                $pParams,
                                $fieldConfiguration,
                                $dataValues[$key][$vKey] ?? null,
                                $dataValues_current[$key][$vKey] ?? null,
                                $structurePath . $key . '/' . $vKey . '/',
                                $workspaceOptions
                            );
                        } else {
                            $res = $this->{$callBackFunc}(
                                $pParams,
                                $fieldConfiguration,
                                $dataValues[$key][$vKey] ?? null,
                                $dataValues_current[$key][$vKey] ?? null,
                                $structurePath . $key . '/' . $vKey . '/',
                                $workspaceOptions
                            );
                        }
                    } else {
                        // Default
                        [$CVtable, $CVid, $CVcurValue, $CVstatus, $CVrealPid, $CVrecFID, $CVtscPID] = $pParams;

                        $additionalData = [
                            'flexFormId' => $CVrecFID,
                            'flexFormPath' => trim(rtrim($structurePath, '/') . '/' . $key . '/' . $vKey, '/'),
                        ];

                        $res = $this->checkValue_SW(
                            [],
                            $dataValues[$key][$vKey] ?? null,
                            $fieldConfiguration,
                            $CVtable,
                            $CVid,
                            $dataValues_current[$key][$vKey] ?? null,
                            $CVstatus,
                            $CVrealPid,
                            $CVrecFID,
                            '',
                            $CVtscPID,
                            $additionalData
                        );
                    }
                    // Adding the value:
                    if (isset($res['value'])) {
                        $dataValues[$key][$vKey] = $res['value'];
                    }
                }
            }
        }
    }

    /**
     * Returns data for inline fields.
     *
     * @param array $valueArray Current value array
     * @param array $tcaFieldConf TCA field config
     * @param int $id Record id
     * @param string $status Status string ('update' or 'new')
     * @param string $table Table name, needs to be passed to \TYPO3\CMS\Core\Database\RelationHandler
     * @param string $field The current field the values are modified for
     * @return string Modified values
     */
    protected function checkValue_inline_processDBdata($valueArray, $tcaFieldConf, $id, $status, $table, $field)
    {
        $foreignTable = $tcaFieldConf['foreign_table'];
        $valueArray = $this->applyFiltersToValues($tcaFieldConf, $valueArray);
        // Fetch the related child records using \TYPO3\CMS\Core\Database\RelationHandler
        $dbAnalysis = $this->createRelationHandlerInstance();
        $dbAnalysis->start(implode(',', $valueArray), $foreignTable, '', 0, $table, $tcaFieldConf);
        // IRRE with a pointer field (database normalization):
        if ($tcaFieldConf['foreign_field'] ?? false) {
            // update record in intermediate table (sorting & pointer uid to parent record)
            $dbAnalysis->writeForeignField($tcaFieldConf, $id, 0);
            $newValue = $dbAnalysis->countItems(false);
        } elseif ($this->getInlineFieldType($tcaFieldConf) === 'mm') {
            // In order to fully support all the MM stuff, directly call checkValue_group_select_processDBdata instead of repeating the needed code here
            $valueArray = $this->checkValue_group_select_processDBdata($valueArray, $tcaFieldConf, $id, $status, 'select', $table, $field);
            $newValue = $valueArray[0];
        } else {
            $valueArray = $dbAnalysis->getValueArray();
            // Checking that the number of items is correct:
            $valueArray = $this->checkValue_checkMax($tcaFieldConf, $valueArray);
            $newValue = $this->castReferenceValue(implode(',', $valueArray), $tcaFieldConf);
        }
        return $newValue;
    }

    /*********************************************
     *
     * PROCESSING COMMANDS
     *
     ********************************************/
    /**
     * Processing the cmd-array
     * See "TYPO3 Core API" for a description of the options.
     *
     * @return void|bool
     */
    public function process_cmdmap()
    {
        // Editing frozen:
        if ($this->BE_USER->workspace !== 0 && ($this->BE_USER->workspaceRec['freeze'] ?? false)) {
            $this->log('sys_workspace', $this->BE_USER->workspace, SystemLogDatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'All editing in this workspace has been frozen!');
            return false;
        }
        // Hook initialization:
        $hookObjectsArr = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'processCmdmap_beforeStart')) {
                $hookObj->processCmdmap_beforeStart($this);
            }
            $hookObjectsArr[] = $hookObj;
        }
        $pasteDatamap = [];
        // Traverse command map:
        foreach ($this->cmdmap as $table => $_) {
            // Check if the table may be modified!
            $modifyAccessList = $this->checkModifyAccessList($table);
            if (!$modifyAccessList) {
                $this->log($table, 0, SystemLogDatabaseAction::UPDATE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to modify table \'%s\' without permission', 1, [$table]);
            }
            // Check basic permissions and circumstances:
            if (!isset($GLOBALS['TCA'][$table]) || $this->tableReadOnly($table) || !is_array($this->cmdmap[$table]) || !$modifyAccessList) {
                continue;
            }

            // Traverse the command map:
            foreach ($this->cmdmap[$table] as $id => $incomingCmdArray) {
                if (!is_array($incomingCmdArray)) {
                    continue;
                }

                if ($table === 'pages') {
                    // for commands on pages do a pagetree-refresh
                    $this->pagetreeNeedsRefresh = true;
                }

                foreach ($incomingCmdArray as $command => $value) {
                    $pasteUpdate = false;
                    if (is_array($value) && isset($value['action']) && $value['action'] === 'paste') {
                        // Extended paste command: $command is set to "move" or "copy"
                        // $value['update'] holds field/value pairs which should be updated after copy/move operation
                        // $value['target'] holds original $value (target of move/copy)
                        $pasteUpdate = $value['update'];
                        $value = $value['target'];
                    }
                    foreach ($hookObjectsArr as $hookObj) {
                        if (method_exists($hookObj, 'processCmdmap_preProcess')) {
                            $hookObj->processCmdmap_preProcess($command, $table, $id, $value, $this, $pasteUpdate);
                        }
                    }
                    // Init copyMapping array:
                    // Must clear this array before call from here to those functions:
                    // Contains mapping information between new and old id numbers.
                    $this->copyMappingArray = [];
                    // process the command
                    $commandIsProcessed = false;
                    foreach ($hookObjectsArr as $hookObj) {
                        if (method_exists($hookObj, 'processCmdmap')) {
                            $hookObj->processCmdmap($command, $table, $id, $value, $commandIsProcessed, $this, $pasteUpdate);
                        }
                    }
                    // Only execute default commands if a hook hasn't been processed the command already
                    if (!$commandIsProcessed) {
                        $procId = $id;
                        $backupUseTransOrigPointerField = $this->useTransOrigPointerField;
                        // Branch, based on command
                        switch ($command) {
                            case 'move':
                                $this->moveRecord($table, (int)$id, $value);
                                break;
                            case 'copy':
                                $target = $value['target'] ?? $value;
                                $ignoreLocalization = (bool)($value['ignoreLocalization'] ?? false);
                                if ($table === 'pages') {
                                    $this->copyPages((int)$id, $target);
                                } else {
                                    $this->copyRecord($table, (int)$id, $target, true, [], '', 0, $ignoreLocalization);
                                }
                                $procId = $this->copyMappingArray[$table][$id];
                                break;
                            case 'localize':
                                $this->useTransOrigPointerField = true;
                                $this->localize($table, (int)$id, $value);
                                break;
                            case 'copyToLanguage':
                                $this->useTransOrigPointerField = false;
                                $this->localize($table, (int)$id, $value);
                                break;
                            case 'inlineLocalizeSynchronize':
                                $this->inlineLocalizeSynchronize($table, (int)$id, $value);
                                break;
                            case 'delete':
                                $this->deleteAction($table, (int)$id);
                                break;
                            case 'undelete':
                                $this->undeleteRecord((string)$table, (int)$id);
                                break;
                        }
                        $this->useTransOrigPointerField = $backupUseTransOrigPointerField;
                        if (is_array($pasteUpdate)) {
                            $pasteDatamap[$table][$procId] = $pasteUpdate;
                        }
                    }
                    foreach ($hookObjectsArr as $hookObj) {
                        if (method_exists($hookObj, 'processCmdmap_postProcess')) {
                            $hookObj->processCmdmap_postProcess($command, $table, $id, $value, $this, $pasteUpdate, $pasteDatamap);
                        }
                    }
                    // Merging the copy-array info together for remapping purposes.
                    ArrayUtility::mergeRecursiveWithOverrule($this->copyMappingArray_merged, $this->copyMappingArray);
                }
            }
        }
        $copyTCE = $this->getLocalTCE();
        $copyTCE->start($pasteDatamap, [], $this->BE_USER);
        $copyTCE->process_datamap();
        $this->errorLog = array_merge($this->errorLog, $copyTCE->errorLog);
        unset($copyTCE);

        // Finally, before exit, check if there are ID references to remap.
        // This might be the case if versioning or copying has taken place!
        $this->remapListedDBRecords();
        $this->processRemapStack();
        foreach ($hookObjectsArr as $hookObj) {
            if (method_exists($hookObj, 'processCmdmap_afterFinish')) {
                $hookObj->processCmdmap_afterFinish($this);
            }
        }
        if ($this->isOuterMostInstance()) {
            $this->referenceIndexUpdater->update();
            $this->processClearCacheQueue();
            $this->resetNestedElementCalls();
        }
    }

    /*********************************************
     *
     * Cmd: Copying
     *
     ********************************************/
    /**
     * Copying a single record
     *
     * @param string $table Element table
     * @param int $uid Element UID
     * @param int $destPid >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
     * @param bool $first Is a flag set, if the record copied is NOT a 'slave' to another record copied. That is, if this record was asked to be copied in the cmd-array
     * @param array $overrideValues Associative array with field/value pairs to override directly. Notice; Fields must exist in the table record and NOT be among excluded fields!
     * @param string $excludeFields Commalist of fields to exclude from the copy process (might get default values)
     * @param int $language Language ID (from sys_language table)
     * @param bool $ignoreLocalization If TRUE, any localization routine is skipped
     * @return int|null ID of new record, if any
     * @internal should only be used from within DataHandler
     */
    public function copyRecord($table, $uid, $destPid, $first = false, $overrideValues = [], $excludeFields = '', $language = 0, $ignoreLocalization = false)
    {
        $uid = ($origUid = (int)$uid);
        // Only copy if the table is defined in $GLOBALS['TCA'], a uid is given and the record wasn't copied before:
        if (empty($GLOBALS['TCA'][$table]) || $uid === 0) {
            return null;
        }
        if ($this->isRecordCopied($table, $uid)) {
            return null;
        }

        // Fetch record with permission check
        $row = $this->recordInfoWithPermissionCheck($table, $uid, Permission::PAGE_SHOW);

        // This checks if the record can be selected which is all that a copy action requires.
        if ($row === false) {
            $this->log($table, $uid, SystemLogDatabaseAction::INSERT, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to copy record "%s:%s" which does not exist or you do not have permission to read', -1, [$table, $uid]);
            return null;
        }

        // NOT using \TYPO3\CMS\Backend\Utility\BackendUtility::getTSCpid() because we need the real pid - not the ID of a page, if the input is a page...
        $tscPID = (int)BackendUtility::getTSconfig_pidValue($table, $uid, $destPid);

        // Check if table is allowed on destination page
        if (!$this->isTableAllowedForThisPage($tscPID, $table)) {
            $this->log($table, $uid, SystemLogDatabaseAction::INSERT, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to insert record "%s:%s" on a page (%s) that can\'t store record type.', -1, [$table, $uid, $tscPID]);
            return null;
        }

        $fullLanguageCheckNeeded = $table !== 'pages';
        // Used to check language and general editing rights
        if (!$ignoreLocalization && ($language <= 0 || !$this->BE_USER->checkLanguageAccess($language)) && !$this->BE_USER->recordEditAccessInternals($table, $uid, false, false, $fullLanguageCheckNeeded)) {
            $this->log($table, $uid, SystemLogDatabaseAction::INSERT, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to copy record "%s:%s" without having permissions to do so. [%s].', -1, [$table, $uid, $this->BE_USER->errorMsg]);
            return null;
        }

        $data = [];
        $nonFields = array_unique(GeneralUtility::trimExplode(',', 'uid,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,t3ver_oid,t3ver_wsid,t3ver_state,t3ver_stage,' . $excludeFields, true));
        BackendUtility::workspaceOL($table, $row, $this->BE_USER->workspace);
        $row = BackendUtility::purgeComputedPropertiesFromRecord($row);

        // Initializing:
        $theNewID = StringUtility::getUniqueId('NEW');
        $enableField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] ?? '';
        $headerField = $GLOBALS['TCA'][$table]['ctrl']['label'];
        // Getting "copy-after" fields if applicable:
        $copyAfterFields = $destPid < 0 ? $this->fixCopyAfterDuplFields($table, $uid, abs($destPid), false) : [];
        // Page TSconfig related:
        $TSConfig = BackendUtility::getPagesTSconfig($tscPID)['TCEMAIN.'] ?? [];
        $tE = $this->getTableEntries($table, $TSConfig);
        // Traverse ALL fields of the selected record:
        foreach ($row as $field => $value) {
            if (!in_array($field, $nonFields, true)) {
                // Get TCA configuration for the field:
                $conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'] ?? [];
                // Preparation/Processing of the value:
                // "pid" is hardcoded of course:
                // isset() won't work here, since values can be NULL in each of the arrays
                // except setDefaultOnCopyArray, since we exploded that from a string
                if ($field === 'pid') {
                    $value = $destPid;
                } elseif (array_key_exists($field, $overrideValues)) {
                    // Override value...
                    $value = $overrideValues[$field];
                } elseif (array_key_exists($field, $copyAfterFields)) {
                    // Copy-after value if available:
                    $value = $copyAfterFields[$field];
                } else {
                    // Hide at copy may override:
                    if ($first && $field == $enableField
                        && ($GLOBALS['TCA'][$table]['ctrl']['hideAtCopy'] ?? false)
                        && !$this->neverHideAtCopy
                        && !($tE['disableHideAtCopy'] ?? false)
                    ) {
                        $value = 1;
                    }
                    // Prepend label on copy:
                    if ($first && $field == $headerField
                        && ($GLOBALS['TCA'][$table]['ctrl']['prependAtCopy'] ?? false)
                        && !($tE['disablePrependAtCopy'] ?? false)
                    ) {
                        $value = $this->getCopyHeader($table, $this->resolvePid($table, $destPid), $field, $this->clearPrefixFromValue($table, $value), 0);
                    }
                    // Processing based on the TCA config field type (files, references, flexforms...)
                    $value = $this->copyRecord_procBasedOnFieldType($table, $uid, $field, $value, $row, $conf, $tscPID, $language);
                }
                // Add value to array.
                $data[$table][$theNewID][$field] = $value;
            }
        }
        // Overriding values:
        if ($GLOBALS['TCA'][$table]['ctrl']['editlock'] ?? false) {
            $data[$table][$theNewID][$GLOBALS['TCA'][$table]['ctrl']['editlock']] = 0;
        }
        // Setting original UID:
        if ($GLOBALS['TCA'][$table]['ctrl']['origUid'] ?? false) {
            $data[$table][$theNewID][$GLOBALS['TCA'][$table]['ctrl']['origUid']] = $uid;
        }
        // Do the copy by simply submitting the array through DataHandler:
        $copyTCE = $this->getLocalTCE();
        $copyTCE->start($data, [], $this->BE_USER);
        $copyTCE->process_datamap();
        // Getting the new UID:
        $theNewSQLID = $copyTCE->substNEWwithIDs[$theNewID] ?? null;
        if ($theNewSQLID) {
            $this->copyMappingArray[$table][$origUid] = $theNewSQLID;
            // Keep automatically versionized record information:
            if (isset($copyTCE->autoVersionIdMap[$table][$theNewSQLID])) {
                $this->autoVersionIdMap[$table][$theNewSQLID] = $copyTCE->autoVersionIdMap[$table][$theNewSQLID];
            }
        }
        $this->errorLog = array_merge($this->errorLog, $copyTCE->errorLog);
        unset($copyTCE);
        if (!$ignoreLocalization && $language == 0) {
            //repointing the new translation records to the parent record we just created
            if (isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])) {
                $overrideValues[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] = $theNewSQLID;
            }
            if (isset($GLOBALS['TCA'][$table]['ctrl']['translationSource'])) {
                $overrideValues[$GLOBALS['TCA'][$table]['ctrl']['translationSource']] = 0;
            }
            $this->copyL10nOverlayRecords($table, $uid, $destPid, $first, $overrideValues, $excludeFields);
        }

        return $theNewSQLID;
    }

    /**
     * Copying pages
     * Main function for copying pages.
     *
     * @param int $uid Page UID to copy
     * @param int $destPid Destination PID: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
     * @internal should only be used from within DataHandler
     */
    public function copyPages($uid, $destPid)
    {
        // Initialize:
        $uid = (int)$uid;
        $destPid = (int)$destPid;

        $copyTablesAlongWithPage = $this->getAllowedTablesToCopyWhenCopyingAPage();
        // Begin to copy pages if we're allowed to:
        if ($this->admin || in_array('pages', $copyTablesAlongWithPage, true)) {
            // Copy this page we're on. And set first-flag (this will trigger that the record is hidden if that is configured)
            // This method also copies the localizations of a page
            $theNewRootID = $this->copySpecificPage($uid, $destPid, $copyTablesAlongWithPage, true);
            // If we're going to copy recursively
            if ($theNewRootID && $this->copyTree) {
                // Get ALL subpages to copy (read-permissions are respected!):
                $CPtable = $this->int_pageTreeInfo([], $uid, (int)$this->copyTree, $theNewRootID);
                // Now copying the subpages:
                foreach ($CPtable as $thePageUid => $thePagePid) {
                    $newPid = $this->copyMappingArray['pages'][$thePagePid];
                    if (isset($newPid)) {
                        $this->copySpecificPage($thePageUid, $newPid, $copyTablesAlongWithPage);
                    } else {
                        $this->log('pages', $uid, SystemLogDatabaseAction::CHECK, 0, SystemLogErrorClassification::USER_ERROR, 'Something went wrong during copying branch');
                        break;
                    }
                }
            }
        } else {
            $this->log('pages', $uid, SystemLogDatabaseAction::CHECK, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to copy page {uid} without permission to this table', -1, ['uid' => $uid]);
        }
    }

    /**
     * Compile a list of tables that should be copied along when a page is about to be copied.
     *
     * First, get the list that the user is allowed to modify (all if admin),
     * and then check against a possible limitation within "DataHandler->copyWhichTables" if not set to "*"
     * to limit the list further down
     *
     * @return array
     */
    protected function getAllowedTablesToCopyWhenCopyingAPage(): array
    {
        // Finding list of tables to copy.
        // These are the tables, the user may modify
        $copyTablesArray = $this->admin ? $this->compileAdminTables() : explode(',', $this->BE_USER->groupData['tables_modify']);
        // If not all tables are allowed then make a list of allowed tables.
        // That is the tables that figure in both allowed tables AND the copyTable-list
        if (!str_contains($this->copyWhichTables, '*')) {
            $definedTablesToCopy = GeneralUtility::trimExplode(',', $this->copyWhichTables, true);
            // Pages are always allowed
            $definedTablesToCopy[] = 'pages';
            $definedTablesToCopy = array_flip($definedTablesToCopy);
            foreach ($copyTablesArray as $k => $table) {
                if (!$table || !isset($definedTablesToCopy[$table])) {
                    unset($copyTablesArray[$k]);
                }
            }
        }
        $copyTablesArray = array_unique($copyTablesArray);
        return $copyTablesArray;
    }
    /**
     * Copying a single page ($uid) to $destPid and all tables in the array copyTablesArray.
     *
     * @param int $uid Page uid
     * @param int $destPid Destination PID: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
     * @param array $copyTablesArray Table on pages to copy along with the page.
     * @param bool $first Is a flag set, if the record copied is NOT a 'slave' to another record copied. That is, if this record was asked to be copied in the cmd-array
     * @return int|null The id of the new page, if applicable.
     * @internal should only be used from within DataHandler
     */
    public function copySpecificPage($uid, $destPid, $copyTablesArray, $first = false)
    {
        // Copy the page itself:
        $theNewRootID = $this->copyRecord('pages', $uid, $destPid, $first);
        $currentWorkspaceId = (int)$this->BE_USER->workspace;
        // If a new page was created upon the copy operation we will proceed with all the tables ON that page:
        if ($theNewRootID) {
            foreach ($copyTablesArray as $table) {
                // All records under the page is copied.
                if ($table && is_array($GLOBALS['TCA'][$table]) && $table !== 'pages') {
                    $fields = ['uid'];
                    $languageField = null;
                    $transOrigPointerField = null;
                    $translationSourceField = null;
                    if (BackendUtility::isTableLocalizable($table)) {
                        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
                        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
                        $fields[] = $languageField;
                        $fields[] = $transOrigPointerField;
                        if (isset($GLOBALS['TCA'][$table]['ctrl']['translationSource'])) {
                            $translationSourceField = $GLOBALS['TCA'][$table]['ctrl']['translationSource'];
                            $fields[] = $translationSourceField;
                        }
                    }
                    $isTableWorkspaceEnabled = BackendUtility::isTableWorkspaceEnabled($table);
                    if ($isTableWorkspaceEnabled) {
                        $fields[] = 't3ver_oid';
                        $fields[] = 't3ver_state';
                        $fields[] = 't3ver_wsid';
                    }
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                    $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());
                    $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $currentWorkspaceId));
                    $queryBuilder
                        ->select(...$fields)
                        ->from($table)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'pid',
                                $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                            )
                        );
                    if (!empty($GLOBALS['TCA'][$table]['ctrl']['sortby'])) {
                        $queryBuilder->orderBy($GLOBALS['TCA'][$table]['ctrl']['sortby'], 'DESC');
                    }
                    $queryBuilder->addOrderBy('uid');
                    try {
                        $result = $queryBuilder->executeQuery();
                        $rows = [];
                        $movedLiveIds = [];
                        $movedLiveRecords = [];
                        while ($row = $result->fetchAssociative()) {
                            if ($isTableWorkspaceEnabled && (int)$row['t3ver_state'] === VersionState::MOVE_POINTER) {
                                $movedLiveIds[(int)$row['t3ver_oid']] = (int)$row['uid'];
                            }
                            $rows[(int)$row['uid']] = $row;
                        }
                        // Resolve placeholders of workspace versions
                        if (!empty($rows) && $currentWorkspaceId > 0 && $isTableWorkspaceEnabled) {
                            // If a record was moved within the page, the PlainDataResolver needs the moved record
                            // but not the original live version, otherwise the moved record is not considered at all.
                            // For this reason, we find the live ids, where there was also a moved record in the SQL
                            // query above in $movedLiveIds and now we removed them before handing them over to PlainDataResolver.
                            // see changeContentSortingAndCopyDraftPage test
                            foreach ($movedLiveIds as $liveId => $movePlaceHolderId) {
                                if (isset($rows[$liveId])) {
                                    $movedLiveRecords[$movePlaceHolderId] = $rows[$liveId];
                                    unset($rows[$liveId]);
                                }
                            }
                            $rows = array_reverse(
                                $this->resolveVersionedRecords(
                                    $table,
                                    implode(',', $fields),
                                    $GLOBALS['TCA'][$table]['ctrl']['sortby'],
                                    array_keys($rows)
                                ),
                                true
                            );
                            foreach ($movedLiveRecords as $movePlaceHolderId => $liveRecord) {
                                $rows[$movePlaceHolderId] = $liveRecord;
                            }
                        }
                        if (is_array($rows)) {
                            $languageSourceMap = [];
                            $overrideValues = $translationSourceField ? [$translationSourceField => 0] : [];
                            $doRemap = false;
                            foreach ($rows as $row) {
                                // Skip localized records that will be processed in
                                // copyL10nOverlayRecords() on copying the default language record
                                $transOrigPointer = $row[$transOrigPointerField] ?? 0;
                                if (!empty($languageField)
                                    && $row[$languageField] > 0
                                    && $transOrigPointer > 0
                                    && (isset($rows[$transOrigPointer]) || isset($movedLiveIds[$transOrigPointer]))
                                ) {
                                    continue;
                                }
                                // Copying each of the underlying records...
                                $newUid = $this->copyRecord($table, $row['uid'], $theNewRootID, false, $overrideValues);
                                if ($translationSourceField) {
                                    $languageSourceMap[$row['uid']] = $newUid;
                                    if ($row[$languageField] > 0) {
                                        $doRemap = true;
                                    }
                                }
                            }
                            if ($doRemap) {
                                //remap is needed for records in non-default language records in the "free mode"
                                $this->copy_remapTranslationSourceField($table, $rows, $languageSourceMap);
                            }
                        }
                    } catch (DBALException $e) {
                        $databaseErrorMessage = $e->getPrevious()->getMessage();
                        $this->log($table, $uid, SystemLogDatabaseAction::CHECK, 0, SystemLogErrorClassification::USER_ERROR, 'An SQL error occurred: {reason}', -1, ['reason' => $databaseErrorMessage]);
                    }
                }
            }
            $this->processRemapStack();
            return $theNewRootID;
        }
        return null;
    }

    /**
     * Copying records, but makes a "raw" copy of a record.
     * Basically the only thing observed is field processing like the copying of files and correction of ids. All other fields are 1-1 copied.
     * Technically the copy is made with THIS instance of the DataHandler class contrary to copyRecord() which creates a new instance and uses the processData() function.
     * The copy is created by insertNewCopyVersion() which bypasses most of the regular input checking associated with processData() - maybe copyRecord() should even do this as well!?
     * This function is used to create new versions of a record.
     * NOTICE: DOES NOT CHECK PERMISSIONS to create! And since page permissions are just passed through and not changed to the user who executes the copy we cannot enforce permissions without getting an incomplete copy - unless we change permissions of course.
     *
     * @param string $table Element table
     * @param int $uid Element UID
     * @param int $pid Element PID (real PID, not checked)
     * @param array $overrideArray Override array - must NOT contain any fields not in the table!
     * @param array $workspaceOptions Options to be forwarded if actions happen on a workspace currently
     * @return int Returns the new ID of the record (if applicable)
     * @internal should only be used from within DataHandler
     */
    public function copyRecord_raw($table, $uid, $pid, $overrideArray = [], array $workspaceOptions = [])
    {
        $uid = (int)$uid;
        // Stop any actions if the record is marked to be deleted:
        // (this can occur if IRRE elements are versionized and child elements are removed)
        if ($this->isElementToBeDeleted($table, $uid)) {
            return null;
        }
        // Only copy if the table is defined in TCA, a uid is given and the record wasn't copied before:
        if (!$GLOBALS['TCA'][$table] || !$uid || $this->isRecordCopied($table, $uid)) {
            return null;
        }

        // Fetch record with permission check
        $row = $this->recordInfoWithPermissionCheck($table, $uid, Permission::PAGE_SHOW);

        // This checks if the record can be selected which is all that a copy action requires.
        if ($row === false) {
            $this->log(
                $table,
                $uid,
                SystemLogDatabaseAction::INSERT,
                0,
                SystemLogErrorClassification::USER_ERROR,
                'Attempt to rawcopy/versionize record which either does not exist or you don\'t have permission to read'
            );
            return null;
        }

        // Set up fields which should not be processed. They are still written - just passed through no-questions-asked!
        $nonFields = ['uid', 'pid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_stage', 'perms_userid', 'perms_groupid', 'perms_user', 'perms_group', 'perms_everybody'];

        // Merge in override array.
        $row = array_merge($row, $overrideArray);
        // Traverse ALL fields of the selected record:
        foreach ($row as $field => $value) {
            /** @var string $field */
            if (!in_array($field, $nonFields, true)) {
                // Get TCA configuration for the field:
                $conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'] ?? false;
                if (is_array($conf)) {
                    // Processing based on the TCA config field type (files, references, flexforms...)
                    $value = $this->copyRecord_procBasedOnFieldType($table, $uid, $field, $value, $row, $conf, $pid, 0, $workspaceOptions);
                }
                // Add value to array.
                $row[$field] = $value;
            }
        }
        $row['pid'] = $pid;
        // Setting original UID:
        if ($GLOBALS['TCA'][$table]['ctrl']['origUid'] ?? '') {
            $row[$GLOBALS['TCA'][$table]['ctrl']['origUid']] = $uid;
        }
        // Do the copy by internal function
        $theNewSQLID = $this->insertNewCopyVersion($table, $row, $pid);

        // When a record is copied in workspace (eg. to create a delete placeholder record for a live record), records
        // pointing to that record need a reference index update. This is for instance the case in FAL, if a sys_file_reference
        // for a eg. tt_content record is marked as deleted. The tt_content record then needs a reference index update.
        // This scenario seems to currently only show up if in workspaces, so the refindex update is restricted to this for now.
        if (!empty($workspaceOptions)) {
            $this->referenceIndexUpdater->registerUpdateForReferencesToItem($table, (int)$row['uid'], (int)$this->BE_USER->workspace);
        }

        if ($theNewSQLID) {
            $this->dbAnalysisStoreExec();
            $this->dbAnalysisStore = [];
            return $this->copyMappingArray[$table][$uid] = $theNewSQLID;
        }
        return null;
    }

    /**
     * Inserts a record in the database, passing TCA configuration values through checkValue() but otherwise does NOTHING and checks nothing regarding permissions.
     * Passes the "version" parameter to insertDB() so the copy will look like a new version in the log - should probably be changed or modified a bit for more broad usage...
     *
     * @param string $table Table name
     * @param array $fieldArray Field array to insert as a record
     * @param int $realPid The value of PID field.
     * @return int|null Returns the new ID of the record (if applicable)
     * @internal should only be used from within DataHandler
     */
    public function insertNewCopyVersion($table, $fieldArray, $realPid)
    {
        $id = StringUtility::getUniqueId('NEW');
        // $fieldArray is set as current record.
        // The point is that when new records are created as copies with flex type fields there might be a field containing information about which DataStructure to use and without that information the flexforms cannot be correctly processed.... This should be OK since the $checkValueRecord is used by the flexform evaluation only anyways...
        $this->checkValue_currentRecord = $fieldArray;
        // Makes sure that transformations aren't processed on the copy.
        $backupDontProcessTransformations = $this->dontProcessTransformations;
        $this->dontProcessTransformations = true;
        // Traverse record and input-process each value:
        foreach ($fieldArray as $field => $fieldValue) {
            if (isset($GLOBALS['TCA'][$table]['columns'][$field])) {
                // Evaluating the value.
                $res = $this->checkValue($table, $field, $fieldValue, $id, 'new', $realPid, 0, $fieldArray);
                if (isset($res['value'])) {
                    $fieldArray[$field] = $res['value'];
                }
            }
        }
        // System fields being set:
        if ($GLOBALS['TCA'][$table]['ctrl']['crdate']) {
            $fieldArray[$GLOBALS['TCA'][$table]['ctrl']['crdate']] = $GLOBALS['EXEC_TIME'];
        }
        if ($GLOBALS['TCA'][$table]['ctrl']['cruser_id']) {
            $fieldArray[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']] = $this->userid;
        }
        if ($GLOBALS['TCA'][$table]['ctrl']['tstamp']) {
            $fieldArray[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
        }
        // Finally, insert record:
        $this->insertDB($table, $id, $fieldArray, true);
        // Resets dontProcessTransformations to the previous state.
        $this->dontProcessTransformations = $backupDontProcessTransformations;
        // Return new id:
        return $this->substNEWwithIDs[$id] ?? null;
    }

    /**
     * Processing/Preparing content for copyRecord() function
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param string $field Field name being processed
     * @param string $value Input value to be processed.
     * @param array $row Record array
     * @param array $conf TCA field configuration
     * @param int $realDestPid Real page id (pid) the record is copied to
     * @param int $language Language ID (from sys_language table) used in the duplicated record
     * @param array $workspaceOptions Options to be forwarded if actions happen on a workspace currently
     * @return array|string
     * @internal
     * @see copyRecord()
     */
    public function copyRecord_procBasedOnFieldType($table, $uid, $field, $value, $row, $conf, $realDestPid, $language = 0, array $workspaceOptions = [])
    {
        $inlineSubType = $this->getInlineFieldType($conf);
        // Get the localization mode for the current (parent) record (keep|select):
        // Register if there are references to take care of or MM is used on an inline field (no change to value):
        if ($this->isReferenceField($conf) || $inlineSubType === 'mm') {
            $value = $this->copyRecord_processManyToMany($table, $uid, $field, $value, $conf, $language);
        } elseif ($inlineSubType !== false) {
            $value = $this->copyRecord_processInline($table, $uid, $field, $value, $row, $conf, $realDestPid, $language, $workspaceOptions);
        }
        // For "flex" fieldtypes we need to traverse the structure for two reasons: If there are file references they have to be prepended with absolute paths and if there are database reference they MIGHT need to be remapped (still done in remapListedDBRecords())
        if (isset($conf['type']) && $conf['type'] === 'flex') {
            // Get current value array:
            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
            $dataStructureIdentifier = $flexFormTools->getDataStructureIdentifier(
                ['config' => $conf],
                $table,
                $field,
                $row
            );
            $dataStructureArray = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
            $currentValueArray = GeneralUtility::xml2array($value);
            // Traversing the XML structure, processing files:
            if (is_array($currentValueArray)) {
                $currentValueArray['data'] = $this->checkValue_flex_procInData($currentValueArray['data'], [], $dataStructureArray, [$table, $uid, $field, $realDestPid], 'copyRecord_flexFormCallBack', $workspaceOptions);
                // Setting value as an array! -> which means the input will be processed according to the 'flex' type when the new copy is created.
                $value = $currentValueArray;
            }
        }
        return $value;
    }

    /**
     * Processes the children of an MM relation field (select, group, inline) when the parent record is copied.
     *
     * @param string $table
     * @param int $uid
     * @param string $field
     * @param string $value
     * @param array $conf
     * @param int $language
     * @return string
     */
    protected function copyRecord_processManyToMany($table, $uid, $field, $value, $conf, $language)
    {
        $allowedTables = $conf['type'] === 'group' ? $conf['allowed'] : $conf['foreign_table'];
        $allowedTablesArray = GeneralUtility::trimExplode(',', $allowedTables, true);
        $prependName = $conf['type'] === 'group' ? ($conf['prepend_tname'] ?? '') : '';
        $mmTable = !empty($conf['MM']) ? $conf['MM'] : '';

        $dbAnalysis = $this->createRelationHandlerInstance();
        $dbAnalysis->start($value, $allowedTables, $mmTable, $uid, $table, $conf);
        $purgeItems = false;

        // Check if referenced records of select or group fields should also be localized in general.
        // A further check is done in the loop below for each table name.
        if ($language > 0 && $mmTable === '' && !empty($conf['localizeReferencesAtParentLocalization'])) {
            // Check whether allowed tables can be localized.
            $localizeTables = [];
            foreach ($allowedTablesArray as $allowedTable) {
                $localizeTables[$allowedTable] = BackendUtility::isTableLocalizable($allowedTable);
            }

            foreach ($dbAnalysis->itemArray as $index => $item) {
                // No action required, if referenced tables cannot be localized (current value will be used).
                if (empty($localizeTables[$item['table']])) {
                    continue;
                }

                // Since select or group fields can reference many records, check whether there's already a localization.
                $recordLocalization = BackendUtility::getRecordLocalization($item['table'], $item['id'], $language);
                if ($recordLocalization) {
                    $dbAnalysis->itemArray[$index]['id'] = $recordLocalization[0]['uid'];
                } elseif ($this->isNestedElementCallRegistered($item['table'], $item['id'], 'localize-' . $language) === false) {
                    $dbAnalysis->itemArray[$index]['id'] = $this->localize($item['table'], $item['id'], $language);
                }
            }
            $purgeItems = true;
        }

        if ($purgeItems || $mmTable !== '') {
            $dbAnalysis->purgeItemArray();
            $value = implode(',', $dbAnalysis->getValueArray($prependName));
        }
        // Setting the value in this array will notify the remapListedDBRecords() function that this field MAY need references to be corrected.
        if ($value) {
            $this->registerDBList[$table][$uid][$field] = $value;
        }

        return $value;
    }

    /**
     * Processes child records in an inline (IRRE) element when the parent record is copied.
     *
     * @param string $table
     * @param int $uid
     * @param string $field
     * @param string $value
     * @param array $row
     * @param array $conf
     * @param int $realDestPid
     * @param int $language
     * @param array $workspaceOptions
     * @return string
     */
    protected function copyRecord_processInline(
        $table,
        $uid,
        $field,
        $value,
        $row,
        $conf,
        $realDestPid,
        $language,
        array $workspaceOptions
    ) {
        // Fetch the related child records using \TYPO3\CMS\Core\Database\RelationHandler
        $dbAnalysis = $this->createRelationHandlerInstance();
        $dbAnalysis->start($value, $conf['foreign_table'], '', $uid, $table, $conf);
        // Walk through the items, copy them and remember the new id:
        foreach ($dbAnalysis->itemArray as $k => $v) {
            $newId = null;
            // If language is set and differs from original record, this isn't a copy action but a localization of our parent/ancestor:
            if ($language > 0 && BackendUtility::isTableLocalizable($table) && $language != $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]) {
                // Children should be localized when the parent gets localized the first time, just do it:
                $newId = $this->localize($v['table'], $v['id'], $language);
            } else {
                if (!MathUtility::canBeInterpretedAsInteger($realDestPid)) {
                    $newId = $this->copyRecord($v['table'], $v['id'], -(int)($v['id']));
                // If the destination page id is a NEW string, keep it on the same page
                } elseif ($this->BE_USER->workspace > 0 && BackendUtility::isTableWorkspaceEnabled($v['table'])) {
                    // A filled $workspaceOptions indicated that this call
                    // has it's origin in previous versionizeRecord() processing
                    if (!empty($workspaceOptions)) {
                        // Versions use live default id, thus the "new"
                        // id is the original live default child record
                        $newId = $v['id'];
                        $this->versionizeRecord(
                            $v['table'],
                            $v['id'],
                            $workspaceOptions['label'] ?? 'Auto-created for WS #' . $this->BE_USER->workspace,
                            $workspaceOptions['delete'] ?? false
                        );
                    // Otherwise just use plain copyRecord() to create placeholders etc.
                    } else {
                        // If a record has been copied already during this request,
                        // prevent superfluous duplication and use the existing copy
                        if (isset($this->copyMappingArray[$v['table']][$v['id']])) {
                            $newId = $this->copyMappingArray[$v['table']][$v['id']];
                        } else {
                            $newId = $this->copyRecord($v['table'], $v['id'], $realDestPid);
                        }
                    }
                } elseif ($this->BE_USER->workspace > 0 && !BackendUtility::isTableWorkspaceEnabled($v['table'])) {
                    // We are in workspace context creating a new parent version and have a child table
                    // that is not workspace aware. We don't do anything with this child.
                    continue;
                } else {
                    // If a record has been copied already during this request,
                    // prevent superfluous duplication and use the existing copy
                    if (isset($this->copyMappingArray[$v['table']][$v['id']])) {
                        $newId = $this->copyMappingArray[$v['table']][$v['id']];
                    } else {
                        $newId = $this->copyRecord_raw($v['table'], $v['id'], $realDestPid, [], $workspaceOptions);
                    }
                }
            }
            // If the current field is set on a page record, update the pid of related child records:
            if ($table === 'pages') {
                $this->registerDBPids[$v['table']][$v['id']] = $uid;
            } elseif (isset($this->registerDBPids[$table][$uid])) {
                $this->registerDBPids[$v['table']][$v['id']] = $this->registerDBPids[$table][$uid];
            }
            $dbAnalysis->itemArray[$k]['id'] = $newId;
        }
        // Store the new values, we will set up the uids for the subtype later on (exception keep localization from original record):
        $value = implode(',', $dbAnalysis->getValueArray());
        $this->registerDBList[$table][$uid][$field] = $value;

        return $value;
    }

    /**
     * Callback function for traversing the FlexForm structure in relation to creating copied files of file relations inside of flex form structures.
     *
     * @param array $pParams Array of parameters in num-indexes: table, uid, field
     * @param array $dsConf TCA field configuration (from Data Structure XML)
     * @param string $dataValue The value of the flexForm field
     * @param string $_1 Not used.
     * @param string $_2 Not used.
     * @param array $workspaceOptions
     * @return array Result array with key "value" containing the value of the processing.
     * @see copyRecord()
     * @see checkValue_flex_procInData_travDS()
     * @internal should only be used from within DataHandler
     */
    public function copyRecord_flexFormCallBack($pParams, $dsConf, $dataValue, $_1, $_2, $workspaceOptions)
    {
        // Extract parameters:
        [$table, $uid, $field, $realDestPid] = $pParams;
        // If references are set for this field, set flag so they can be corrected later (in ->remapListedDBRecords())
        if (($this->isReferenceField($dsConf) || $this->getInlineFieldType($dsConf) !== false) && (string)$dataValue !== '') {
            $dataValue = $this->copyRecord_procBasedOnFieldType($table, $uid, $field, $dataValue, [], $dsConf, $realDestPid, 0, $workspaceOptions);
            $this->registerDBList[$table][$uid][$field] = 'FlexForm_reference';
        }
        // Return
        return ['value' => $dataValue];
    }

    /**
     * Find l10n-overlay records and perform the requested copy action for these records.
     *
     * @param string $table Record Table
     * @param int $uid UID of the record in the default language
     * @param int $destPid Position to copy to
     * @param bool $first
     * @param array $overrideValues
     * @param string $excludeFields
     * @internal should only be used from within DataHandler
     */
    public function copyL10nOverlayRecords($table, $uid, $destPid, $first = false, $overrideValues = [], $excludeFields = '')
    {
        // There's no need to perform this for tables that are not localizable
        if (!BackendUtility::isTableLocalizable($table)) {
            return;
        }

        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->BE_USER->workspace));

        $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $transOrigPointerField,
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT, ':pointer')
                )
            );

        // Never copy the actual placeholders around, as the newly copied records are
        // always created as new record / new placeholder pairs
        if (BackendUtility::isTableWorkspaceEnabled($table)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->neq(
                    't3ver_state',
                    VersionState::DELETE_PLACEHOLDER
                )
            );
        }

        // If $destPid is < 0, get the pid of the record with uid equal to abs($destPid)
        $tscPID = BackendUtility::getTSconfig_pidValue($table, $uid, $destPid) ?? 0;
        // Get the localized records to be copied
        $l10nRecords = $queryBuilder->executeQuery()->fetchAllAssociative();
        if (is_array($l10nRecords)) {
            $localizedDestPids = [];
            // If $destPid < 0, then it is the uid of the original language record we are inserting after
            if ($destPid < 0) {
                // Get the localized records of the record we are inserting after
                $queryBuilder->setParameter('pointer', abs($destPid), \PDO::PARAM_INT);
                $destL10nRecords = $queryBuilder->executeQuery()->fetchAllAssociative();
                // Index the localized record uids by language
                if (is_array($destL10nRecords)) {
                    foreach ($destL10nRecords as $record) {
                        $localizedDestPids[$record[$languageField]] = -$record['uid'];
                    }
                }
            }
            $languageSourceMap = [
                $uid => $overrideValues[$transOrigPointerField],
            ];
            // Copy the localized records after the corresponding localizations of the destination record
            foreach ($l10nRecords as $record) {
                $localizedDestPid = (int)($localizedDestPids[$record[$languageField]] ?? 0);
                if ($localizedDestPid < 0) {
                    $newUid = $this->copyRecord($table, $record['uid'], $localizedDestPid, $first, $overrideValues, $excludeFields, $record[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
                } else {
                    $newUid = $this->copyRecord($table, $record['uid'], $destPid < 0 ? $tscPID : $destPid, $first, $overrideValues, $excludeFields, $record[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
                }
                $languageSourceMap[$record['uid']] = $newUid;
            }
            $this->copy_remapTranslationSourceField($table, $l10nRecords, $languageSourceMap);
        }
    }

    /**
     * Remap languageSource field to uids of newly created records
     *
     * @param string $table Table name
     * @param array $l10nRecords array of localized records from the page we're copying from (source records)
     * @param array $languageSourceMap array mapping source records uids to newly copied uids
     */
    protected function copy_remapTranslationSourceField($table, $l10nRecords, $languageSourceMap)
    {
        if (empty($GLOBALS['TCA'][$table]['ctrl']['translationSource']) || empty($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])) {
            return;
        }
        $translationSourceFieldName = $GLOBALS['TCA'][$table]['ctrl']['translationSource'];
        $translationParentFieldName = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];

        //We can avoid running these update queries by sorting the $l10nRecords by languageSource dependency (in copyL10nOverlayRecords)
        //and first copy records depending on default record (and map the field).
        foreach ($l10nRecords as $record) {
            $oldSourceUid = $record[$translationSourceFieldName];
            if ($oldSourceUid <= 0 && $record[$translationParentFieldName] > 0) {
                //BC fix - in connected mode 'translationSource' field should not be 0
                $oldSourceUid = $record[$translationParentFieldName];
            }
            if ($oldSourceUid > 0) {
                if (empty($languageSourceMap[$oldSourceUid])) {
                    // we don't have mapping information available e.g when copyRecord returned null
                    continue;
                }
                $newFieldValue = $languageSourceMap[$oldSourceUid];
                $updateFields = [
                    $translationSourceFieldName => $newFieldValue,
                ];
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable($table)
                    ->update($table, $updateFields, ['uid' => (int)$languageSourceMap[$record['uid']]]);
                if ($this->BE_USER->workspace > 0) {
                    GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getConnectionForTable($table)
                        ->update($table, $updateFields, ['t3ver_oid' => (int)$languageSourceMap[$record['uid']], 't3ver_wsid' => $this->BE_USER->workspace]);
                }
            }
        }
    }

    /*********************************************
     *
     * Cmd: Moving, Localizing
     *
     ********************************************/
    /**
     * Moving single records
     *
     * @param string $table Table name to move
     * @param int $uid Record uid to move
     * @param int $destPid Position to move to: $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
     * @internal should only be used from within DataHandler
     */
    public function moveRecord($table, $uid, $destPid)
    {
        if (!$GLOBALS['TCA'][$table]) {
            return;
        }

        // In case the record to be moved turns out to be an offline version,
        // we have to find the live version and work on that one.
        if ($lookForLiveVersion = BackendUtility::getLiveVersionOfRecord($table, $uid, 'uid')) {
            $uid = $lookForLiveVersion['uid'];
        }
        // Initialize:
        $destPid = (int)$destPid;
        // Get this before we change the pid (for logging)
        $propArr = $this->getRecordProperties($table, $uid);
        $moveRec = $this->getRecordProperties($table, $uid, true);
        // This is the actual pid of the moving to destination
        $resolvedPid = $this->resolvePid($table, $destPid);
        // Finding out, if the record may be moved from where it is. If the record is a non-page, then it depends on edit-permissions.
        // If the record is a page, then there are two options: If the page is moved within itself,
        // (same pid) it's edit-perms of the pid. If moved to another place then its both delete-perms of the pid and new-page perms on the destination.
        if ($table !== 'pages' || $resolvedPid == $moveRec['pid']) {
            // Edit rights for the record...
            $mayMoveAccess = $this->checkRecordUpdateAccess($table, $uid);
        } else {
            $mayMoveAccess = $this->doesRecordExist($table, $uid, Permission::PAGE_DELETE);
        }
        // Finding out, if the record may be moved TO another place. Here we check insert-rights (non-pages = edit, pages = new),
        // unless the pages are moved on the same pid, then edit-rights are checked
        if ($table !== 'pages' || $resolvedPid != $moveRec['pid']) {
            // Insert rights for the record...
            $mayInsertAccess = $this->checkRecordInsertAccess($table, $resolvedPid, SystemLogDatabaseAction::MOVE);
        } else {
            $mayInsertAccess = $this->checkRecordUpdateAccess($table, $uid);
        }
        // Checking if there is anything else disallowing moving the record by checking if editing is allowed
        $fullLanguageCheckNeeded = $table !== 'pages';
        $mayEditAccess = $this->BE_USER->recordEditAccessInternals($table, $uid, false, false, $fullLanguageCheckNeeded);
        // If moving is allowed, begin the processing:
        if (!$mayEditAccess) {
            $this->log($table, $uid, SystemLogDatabaseAction::MOVE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to move record "%s" (%s) without having permissions to do so. [%s]', 14, [$propArr['header'], $table . ':' . $uid, $this->BE_USER->errorMsg], $propArr['event_pid']);
            return;
        }

        if (!$mayMoveAccess) {
            $this->log($table, $uid, SystemLogDatabaseAction::MOVE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to move record \'%s\' (%s) without having permissions to do so.', 14, [$propArr['header'], $table . ':' . $uid], $propArr['event_pid']);
            return;
        }

        if (!$mayInsertAccess) {
            $this->log($table, $uid, SystemLogDatabaseAction::MOVE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to move record \'%s\' (%s) without having permissions to insert.', 14, [$propArr['header'], $table . ':' . $uid], $propArr['event_pid']);
            return;
        }

        $recordWasMoved = false;
        // Move the record via a hook, used e.g. for versioning
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'moveRecord')) {
                $hookObj->moveRecord($table, $uid, $destPid, $propArr, $moveRec, $resolvedPid, $recordWasMoved, $this);
            }
        }
        // Move the record if a hook hasn't moved it yet
        if (!$recordWasMoved) {
            $this->moveRecord_raw($table, $uid, $destPid);
        }
    }

    /**
     * Moves a record without checking security of any sort.
     * USE ONLY INTERNALLY
     *
     * @param string $table Table name to move
     * @param int $uid Record uid to move
     * @param int $destPid Position to move to: $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
     * @see moveRecord()
     * @internal should only be used from within DataHandler
     */
    public function moveRecord_raw($table, $uid, $destPid)
    {
        $sortColumn = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? '';
        $origDestPid = $destPid;
        // This is the actual pid of the moving to destination
        $resolvedPid = $this->resolvePid($table, $destPid);
        // Checking if the pid is negative, but no sorting row is defined. In that case, find the correct pid.
        // Basically this check make the error message 4-13 meaning less... But you can always remove this check if you
        // prefer the error instead of a no-good action (which is to move the record to its own page...)
        if (($destPid < 0 && !$sortColumn) || $destPid >= 0) {
            $destPid = $resolvedPid;
        }
        // Get this before we change the pid (for logging)
        $propArr = $this->getRecordProperties($table, $uid);
        $moveRec = $this->getRecordProperties($table, $uid, true);
        // Prepare user defined objects (if any) for hooks which extend this function:
        $hookObjectsArr = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'] ?? [] as $className) {
            $hookObjectsArr[] = GeneralUtility::makeInstance($className);
        }
        // Timestamp field:
        $updateFields = [];
        if ($GLOBALS['TCA'][$table]['ctrl']['tstamp'] ?? false) {
            $updateFields[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
        }

        // Check if this is a translation of a page, if so then it just needs to be kept "sorting" in sync
        // Usually called from moveL10nOverlayRecords()
        if ($table === 'pages') {
            $defaultLanguagePageUid = $this->getDefaultLanguagePageId((int)$uid);
            // In workspaces, the default language page may have been moved to a different pid than the
            // default language page record of live workspace. In this case, localized pages need to be
            // moved to the pid of the workspace move record.
            $defaultLanguagePageWorkspaceOverlay = BackendUtility::getWorkspaceVersionOfRecord((int)$this->BE_USER->workspace, 'pages', $defaultLanguagePageUid, 'uid');
            if (is_array($defaultLanguagePageWorkspaceOverlay)) {
                $defaultLanguagePageUid = (int)$defaultLanguagePageWorkspaceOverlay['uid'];
            }
            if ($defaultLanguagePageUid !== (int)$uid) {
                // If the default language page has been moved, localized pages need to be moved to
                // that pid and sorting, too.
                $originalTranslationRecord = $this->recordInfo($table, $defaultLanguagePageUid, 'pid,' . $sortColumn);
                $updateFields[$sortColumn] = $originalTranslationRecord[$sortColumn];
                $destPid = $originalTranslationRecord['pid'];
            }
        }

        // Insert as first element on page (where uid = $destPid)
        if ($destPid >= 0) {
            if ($table !== 'pages' || $this->destNotInsideSelf($destPid, $uid)) {
                // Clear cache before moving
                [$parentUid] = BackendUtility::getTSCpid($table, $uid, '');
                $this->registerRecordIdForPageCacheClearing($table, $uid, $parentUid);
                // Setting PID
                $updateFields['pid'] = $destPid;
                // Table is sorted by 'sortby'
                if ($sortColumn && !isset($updateFields[$sortColumn])) {
                    $sortNumber = $this->getSortNumber($table, $uid, $destPid);
                    $updateFields[$sortColumn] = $sortNumber;
                }
                // Check for child records that have also to be moved
                $this->moveRecord_procFields($table, $uid, $destPid);
                // Create query for update:
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable($table)
                    ->update($table, $updateFields, ['uid' => (int)$uid]);
                // Check for the localizations of that element
                $this->moveL10nOverlayRecords($table, $uid, $destPid, $destPid);
                // Call post processing hooks:
                foreach ($hookObjectsArr as $hookObj) {
                    if (method_exists($hookObj, 'moveRecord_firstElementPostProcess')) {
                        $hookObj->moveRecord_firstElementPostProcess($table, $uid, $destPid, $moveRec, $updateFields, $this);
                    }
                }

                $this->getRecordHistoryStore()->moveRecord($table, $uid, ['oldPageId' => $propArr['pid'], 'newPageId' => $destPid, 'oldData' => $propArr, 'newData' => $updateFields], $this->correlationId);
                if ($this->enableLogging) {
                    // Logging...
                    $oldpagePropArr = $this->getRecordProperties('pages', $propArr['pid']);
                    if ($destPid != $propArr['pid']) {
                        // Logged to old page
                        $newPropArr = $this->getRecordProperties($table, $uid);
                        $newpagePropArr = $this->getRecordProperties('pages', $destPid);
                        $this->log($table, $uid, SystemLogDatabaseAction::MOVE, $destPid, SystemLogErrorClassification::MESSAGE, 'Moved record \'%s\' (%s) to page \'%s\' (%s)', 2, [$propArr['header'], $table . ':' . $uid, $newpagePropArr['header'], $newPropArr['pid']], $propArr['pid']);
                        // Logged to new page
                        $this->log($table, $uid, SystemLogDatabaseAction::MOVE, $destPid, SystemLogErrorClassification::MESSAGE, 'Moved record \'%s\' (%s) from page \'%s\' (%s)', 3, [$propArr['header'], $table . ':' . $uid, $oldpagePropArr['header'], $propArr['pid']], $destPid);
                    } else {
                        // Logged to new page
                        $this->log($table, $uid, SystemLogDatabaseAction::MOVE, $destPid, SystemLogErrorClassification::MESSAGE, 'Moved record \'%s\' (%s) on page \'%s\' (%s)', 4, [$propArr['header'], $table . ':' . $uid, $oldpagePropArr['header'], $propArr['pid']], $destPid);
                    }
                }
                // Clear cache after moving
                $this->registerRecordIdForPageCacheClearing($table, $uid);
                $this->fixUniqueInPid($table, $uid);
                $this->fixUniqueInSite($table, (int)$uid);
                if ($table === 'pages') {
                    $this->fixUniqueInSiteForSubpages((int)$uid);
                }
            } elseif ($this->enableLogging) {
                $destPropArr = $this->getRecordProperties('pages', $destPid);
                $this->log($table, $uid, SystemLogDatabaseAction::MOVE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to move page \'%s\' (%s) to inside of its own rootline (at page \'%s\' (%s))', 10, [$propArr['header'], $uid, $destPropArr['header'], $destPid], $propArr['pid']);
            }
        } elseif ($sortColumn) {
            // Put after another record
            // Table is being sorted
            // Save the position to which the original record is requested to be moved
            $originalRecordDestinationPid = $destPid;
            $sortInfo = $this->getSortNumber($table, $uid, $destPid);
            // Setting the destPid to the new pid of the record.
            $destPid = $sortInfo['pid'];
            // If not an array, there was an error (which is already logged)
            if (is_array($sortInfo)) {
                if ($table !== 'pages' || $this->destNotInsideSelf($destPid, $uid)) {
                    // clear cache before moving
                    $this->registerRecordIdForPageCacheClearing($table, $uid);
                    // We now update the pid and sortnumber (if not set for page translations)
                    $updateFields['pid'] = $destPid;
                    if (!isset($updateFields[$sortColumn])) {
                        $updateFields[$sortColumn] = $sortInfo['sortNumber'];
                    }
                    // Check for child records that have also to be moved
                    $this->moveRecord_procFields($table, $uid, $destPid);
                    // Create query for update:
                    GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getConnectionForTable($table)
                        ->update($table, $updateFields, ['uid' => (int)$uid]);
                    // Check for the localizations of that element
                    $this->moveL10nOverlayRecords($table, $uid, $destPid, $originalRecordDestinationPid);
                    // Call post processing hooks:
                    foreach ($hookObjectsArr as $hookObj) {
                        if (method_exists($hookObj, 'moveRecord_afterAnotherElementPostProcess')) {
                            $hookObj->moveRecord_afterAnotherElementPostProcess($table, $uid, $destPid, $origDestPid, $moveRec, $updateFields, $this);
                        }
                    }
                    $this->getRecordHistoryStore()->moveRecord($table, $uid, ['oldPageId' => $propArr['pid'], 'newPageId' => $destPid, 'oldData' => $propArr, 'newData' => $updateFields], $this->correlationId);
                    if ($this->enableLogging) {
                        // Logging...
                        $oldpagePropArr = $this->getRecordProperties('pages', $propArr['pid']);
                        if ($destPid != $propArr['pid']) {
                            // Logged to old page
                            $newPropArr = $this->getRecordProperties($table, $uid);
                            $newpagePropArr = $this->getRecordProperties('pages', $destPid);
                            $this->log($table, $uid, SystemLogDatabaseAction::MOVE, 0, SystemLogErrorClassification::MESSAGE, 'Moved record \'%s\' (%s) to page \'%s\' (%s)', 2, [$propArr['header'], $table . ':' . $uid, $newpagePropArr['header'], $newPropArr['pid']], $propArr['pid']);
                            // Logged to old page
                            $this->log($table, $uid, SystemLogDatabaseAction::MOVE, 0, SystemLogErrorClassification::MESSAGE, 'Moved record \'%s\' (%s) from page \'%s\' (%s)', 3, [$propArr['header'], $table . ':' . $uid, $oldpagePropArr['header'], $propArr['pid']], $destPid);
                        } else {
                            // Logged to old page
                            $this->log($table, $uid, SystemLogDatabaseAction::MOVE, 0, SystemLogErrorClassification::MESSAGE, 'Moved record \'%s\' (%s) on page \'%s\' (%s)', 4, [$propArr['header'], $table . ':' . $uid, $oldpagePropArr['header'], $propArr['pid']], $destPid);
                        }
                    }
                    // Clear cache after moving
                    $this->registerRecordIdForPageCacheClearing($table, $uid);
                    $this->fixUniqueInPid($table, $uid);
                    $this->fixUniqueInSite($table, (int)$uid);
                    if ($table === 'pages') {
                        $this->fixUniqueInSiteForSubpages((int)$uid);
                    }
                } elseif ($this->enableLogging) {
                    $destPropArr = $this->getRecordProperties('pages', $destPid);
                    $this->log($table, $uid, SystemLogDatabaseAction::MOVE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to move page \'%s\' (%s) to inside of its own rootline (at page \'%s\' (%s))', 10, [$propArr['header'], $uid, $destPropArr['header'], $destPid], $propArr['pid']);
                }
            } else {
                $this->log($table, $uid, SystemLogDatabaseAction::MOVE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to move record \'%s\' (%s) to after another record, although the table has no sorting row.', 13, [$propArr['header'], $table . ':' . $uid], $propArr['event_pid']);
            }
        }
    }

    /**
     * Walk through all fields of the moved record and look for children of e.g. the inline type.
     * If child records are found, they are also move to the new $destPid.
     *
     * @param string $table Record Table
     * @param int $uid Record UID
     * @param int $destPid Position to move to
     * @internal should only be used from within DataHandler
     */
    public function moveRecord_procFields($table, $uid, $destPid)
    {
        $row = BackendUtility::getRecordWSOL($table, $uid);
        if (is_array($row) && (int)$destPid !== (int)$row['pid']) {
            $conf = $GLOBALS['TCA'][$table]['columns'];
            foreach ($row as $field => $value) {
                $this->moveRecord_procBasedOnFieldType($table, $uid, $destPid, $value, $conf[$field]['config'] ?? []);
            }
        }
    }

    /**
     * Move child records depending on the field type of the parent record.
     *
     * @param string $table Record Table
     * @param int $uid Record UID
     * @param int $destPid Position to move to
     * @param string $value Record field value
     * @param array $conf TCA configuration of current field
     * @internal should only be used from within DataHandler
     */
    public function moveRecord_procBasedOnFieldType($table, $uid, $destPid, $value, $conf)
    {
        $dbAnalysis = null;
        if (!empty($conf['type']) && $conf['type'] === 'inline') {
            $foreign_table = $conf['foreign_table'];
            $moveChildrenWithParent = !isset($conf['behaviour']['disableMovingChildrenWithParent']) || !$conf['behaviour']['disableMovingChildrenWithParent'];
            if ($foreign_table && $moveChildrenWithParent) {
                $inlineType = $this->getInlineFieldType($conf);
                if ($inlineType === 'list' || $inlineType === 'field') {
                    if ($table === 'pages') {
                        // If the inline elements are related to a page record,
                        // make sure they reside at that page and not at its parent
                        $destPid = $uid;
                    }
                    $dbAnalysis = $this->createRelationHandlerInstance();
                    $dbAnalysis->start($value, $conf['foreign_table'], '', $uid, $table, $conf);
                }
            }
        }
        // Move the records
        if (isset($dbAnalysis)) {
            // Moving records to a positive destination will insert each
            // record at the beginning, thus the order is reversed here:
            foreach (array_reverse($dbAnalysis->itemArray) as $v) {
                $this->moveRecord($v['table'], $v['id'], $destPid);
            }
        }
    }

    /**
     * Find l10n-overlay records and perform the requested move action for these records.
     *
     * @param string $table Record Table
     * @param int $uid Record UID
     * @param int $destPid Position to move to
     * @param int $originalRecordDestinationPid Position to move the original record to
     * @internal should only be used from within DataHandler
     */
    public function moveL10nOverlayRecords($table, $uid, $destPid, $originalRecordDestinationPid)
    {
        // There's no need to perform this for non-localizable tables
        if (!BackendUtility::isTableLocalizable($table)) {
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->BE_USER->workspace));

        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;
        $l10nRecords = $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $transOrigPointerField,
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT, ':pointer')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        if (is_array($l10nRecords)) {
            $localizedDestPids = [];
            // If $$originalRecordDestinationPid < 0, then it is the uid of the original language record we are inserting after
            if ($originalRecordDestinationPid < 0) {
                // Get the localized records of the record we are inserting after
                $queryBuilder->setParameter('pointer', abs($originalRecordDestinationPid), \PDO::PARAM_INT);
                $destL10nRecords = $queryBuilder->executeQuery()->fetchAllAssociative();
                // Index the localized record uids by language
                if (is_array($destL10nRecords)) {
                    foreach ($destL10nRecords as $record) {
                        $localizedDestPids[$record[$languageField]] = -$record['uid'];
                    }
                }
            }
            // Move the localized records after the corresponding localizations of the destination record
            foreach ($l10nRecords as $record) {
                $localizedDestPid = (int)($localizedDestPids[$record[$languageField]] ?? 0);
                if ($localizedDestPid < 0) {
                    $this->moveRecord($table, $record['uid'], $localizedDestPid);
                } else {
                    $this->moveRecord($table, $record['uid'], $destPid);
                }
            }
        }
    }

    /**
     * Localizes a record to another system language
     *
     * @param string $table Table name
     * @param int $uid Record uid (to be localized)
     * @param int $language Language ID (from sys_language table)
     * @return int|bool The uid (int) of the new translated record or FALSE (bool) if something went wrong
     * @internal should only be used from within DataHandler
     */
    public function localize($table, $uid, $language)
    {
        $newId = false;
        $uid = (int)$uid;
        if (!$GLOBALS['TCA'][$table] || !$uid || $this->isNestedElementCallRegistered($table, $uid, 'localize-' . (string)$language) !== false) {
            return false;
        }

        $this->registerNestedElementCall($table, $uid, 'localize-' . (string)$language);
        if (empty($GLOBALS['TCA'][$table]['ctrl']['languageField']) || empty($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])) {
            $this->log($table, $uid, SystemLogDatabaseAction::LOCALIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Localization failed; "languageField" and "transOrigPointerField" must be defined for the table {table}', -1, ['table' => $table]);
            return false;
        }

        if (!$this->doesRecordExist($table, $uid, Permission::PAGE_SHOW)) {
            $this->log($table, $uid, SystemLogDatabaseAction::LOCALIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to localize record {table}:{uid} without permission.', -1, ['table' => $table, 'uid' => (int)$uid]);
            return false;
        }

        // Getting workspace overlay if possible - this will localize versions in workspace if any
        $row = BackendUtility::getRecordWSOL($table, $uid);
        if (!is_array($row)) {
            $this->log($table, $uid, SystemLogDatabaseAction::LOCALIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to localize record {table}:{uid} that did not exist!', -1, ['table' => $table, 'uid' => (int)$uid]);
            return false;
        }

        [$pageId] = BackendUtility::getTSCpid($table, $uid, '');
        // Try to fetch the site language from the pages' associated site
        $siteLanguage = $this->getSiteLanguageForPage((int)$pageId, (int)$language);
        if ($siteLanguage === null) {
            $this->log($table, $uid, SystemLogDatabaseAction::LOCALIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Language ID "{languageId}" not found for page {pageId}!', -1, ['languageId' => (int)$language, 'pageId' => (int)$pageId]);
            return false;
        }

        // Make sure that records which are translated from another language than the default language have a correct
        // localization source set themselves, before translating them to another language.
        if ((int)$row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] !== 0
            && $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] > 0) {
            $localizationParentRecord = BackendUtility::getRecord(
                $table,
                $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]
            );
            if ((int)$localizationParentRecord[$GLOBALS['TCA'][$table]['ctrl']['languageField']] !== 0) {
                $this->log($table, $localizationParentRecord['uid'], SystemLogDatabaseAction::LOCALIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Localization failed; Source record {table}:{originalRecordId} contained a reference to an original record that is not a default record (which is strange)!', -1, ['table' => $table, 'originalRecordId' => $localizationParentRecord['uid']]);
                return false;
            }
        }

        // Default language records must never have a localization parent as they are the origin of any translation.
        if ((int)$row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] !== 0
            && (int)$row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] === 0) {
            $this->log($table, $row['uid'], SystemLogDatabaseAction::LOCALIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Localization failed; Source record {table}:{uid} contained a reference to an original default record but is a default record itself (which is strange)!', -1, ['table' => $table, 'uid' => (int)$row['uid']]);
            return false;
        }

        $recordLocalizations = BackendUtility::getRecordLocalization($table, $uid, $language, 'AND pid=' . (int)$row['pid']);

        if (!empty($recordLocalizations)) {
            $this->log(
                $table,
                $uid,
                SystemLogDatabaseAction::LOCALIZE,
                0,
                SystemLogErrorClassification::USER_ERROR,
                'Localization failed: there already are localizations (%s) for language %d of the "%s" record %d!',
                -1,
                [
                    implode(', ', array_column($recordLocalizations, 'uid')),
                    $language,
                    $table,
                    $uid,
                ]
            );
            return false;
        }

        // Initialize:
        $overrideValues = [];
        // Set override values:
        $overrideValues[$GLOBALS['TCA'][$table]['ctrl']['languageField']] = (int)$language;
        // If the translated record is a default language record, set it's uid as localization parent of the new record.
        // If translating from any other language, no override is needed; we just can copy the localization parent of
        // the original record (which is pointing to the correspondent default language record) to the new record.
        // In copy / free mode the TransOrigPointer field is always set to 0, as no connection to the localization parent is wanted in that case.
        // For pages, there is no "copy/free mode".
        if (($this->useTransOrigPointerField || $table === 'pages') && (int)$row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] === 0) {
            $overrideValues[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] = $uid;
        } elseif (!$this->useTransOrigPointerField) {
            $overrideValues[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] = 0;
        }
        if (isset($GLOBALS['TCA'][$table]['ctrl']['translationSource'])) {
            $overrideValues[$GLOBALS['TCA'][$table]['ctrl']['translationSource']] = $uid;
        }
        // Copy the type (if defined in both tables) from the original record so that translation has same type as original record
        if (isset($GLOBALS['TCA'][$table]['ctrl']['type'])) {
            // @todo: Possible bug here? type can be something like 'table:field', which is then null in $row, writing null to $overrideValues
            $overrideValues[$GLOBALS['TCA'][$table]['ctrl']['type']] = $row[$GLOBALS['TCA'][$table]['ctrl']['type']] ?? null;
        }
        // Set exclude Fields:
        foreach ($GLOBALS['TCA'][$table]['columns'] as $fN => $fCfg) {
            $translateToMsg = '';
            // Check if we are just prefixing:
            if (isset($fCfg['l10n_mode']) && $fCfg['l10n_mode'] === 'prefixLangTitle') {
                if (($fCfg['config']['type'] === 'text' || $fCfg['config']['type'] === 'input') && (string)$row[$fN] !== '') {
                    $TSConfig = BackendUtility::getPagesTSconfig($pageId)['TCEMAIN.'] ?? [];
                    $tableEntries = $this->getTableEntries($table, $TSConfig);
                    if (!empty($TSConfig['translateToMessage']) && !($tableEntries['disablePrependAtCopy'] ?? false)) {
                        $translateToMsg = $this->getLanguageService()->sL($TSConfig['translateToMessage']);
                        $translateToMsg = @sprintf($translateToMsg, $siteLanguage->getTitle());
                    }

                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processTranslateToClass'] ?? [] as $className) {
                        $hookObj = GeneralUtility::makeInstance($className);
                        if (method_exists($hookObj, 'processTranslateTo_copyAction')) {
                            // @todo Deprecate passing an array and pass the full SiteLanguage object instead
                            $hookObj->processTranslateTo_copyAction(
                                $row[$fN],
                                ['uid' => $siteLanguage->getLanguageId(), 'title' => $siteLanguage->getTitle()],
                                $this,
                                $fN
                            );
                        }
                    }
                    if (!empty($translateToMsg)) {
                        $overrideValues[$fN] = '[' . $translateToMsg . '] ' . $row[$fN];
                    } else {
                        $overrideValues[$fN] = $row[$fN];
                    }
                }
            }
            if (($fCfg['config']['MM'] ?? false) && !empty($fCfg['config']['MM_oppositeUsage'])) {
                // We are localizing the 'local' side of an MM relation. (eg. localizing a category).
                // In this case, MM relations connected to the default lang record should not be copied,
                // so we set an override here to not trigger mm handling of 'items' field for this.
                $overrideValues[$fN] = 0;
            }
        }

        if ($table !== 'pages') {
            // Get the uid of record after which this localized record should be inserted
            $previousUid = $this->getPreviousLocalizedRecordUid($table, $uid, $row['pid'], $language);
            // Execute the copy:
            $newId = $this->copyRecord($table, $uid, -$previousUid, true, $overrideValues, '', $language);
        } else {
            // Create new page which needs to contain the same pid as the original page
            $overrideValues['pid'] = $row['pid'];
            // Take over the hidden state of the original language state, this is done due to legacy reasons where-as
            // pages_language_overlay was set to "hidden -> default=0" but pages hidden -> default 1"
            if (!empty($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'])) {
                $hiddenFieldName = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
                $overrideValues[$hiddenFieldName] = $row[$hiddenFieldName] ?? $GLOBALS['TCA'][$table]['columns'][$hiddenFieldName]['config']['default'];
                // Override by TCA "hideAtCopy" or pageTS "disableHideAtCopy"
                // Only for visible pages to get the same behaviour as for copy
                if (!$overrideValues[$hiddenFieldName]) {
                    $TSConfig = BackendUtility::getPagesTSconfig($uid)['TCEMAIN.'] ?? [];
                    $tableEntries = $this->getTableEntries($table, $TSConfig);
                    if (
                        ($GLOBALS['TCA'][$table]['ctrl']['hideAtCopy'] ?? false)
                        && !$this->neverHideAtCopy
                        && !($tableEntries['disableHideAtCopy'] ?? false)
                    ) {
                        $overrideValues[$hiddenFieldName] = 1;
                    }
                }
            }
            $temporaryId = StringUtility::getUniqueId('NEW');
            $copyTCE = $this->getLocalTCE();
            $copyTCE->start([$table => [$temporaryId => $overrideValues]], [], $this->BE_USER);
            $copyTCE->process_datamap();
            // Getting the new UID as if it had been copied:
            $theNewSQLID = $copyTCE->substNEWwithIDs[$temporaryId];
            if ($theNewSQLID) {
                $this->copyMappingArray[$table][$uid] = $theNewSQLID;
                $newId = $theNewSQLID;
            }
        }

        return $newId;
    }

    /**
     * Performs localization or synchronization of child records.
     * The $command argument expects an array, but supports a string for backward-compatibility.
     *
     * $command = array(
     *   'field' => 'tx_myfieldname',
     *   'language' => 2,
     *   // either the key 'action' or 'ids' must be set
     *   'action' => 'synchronize', // or 'localize'
     *   'ids' => array(1, 2, 3, 4) // child element ids
     * );
     *
     * @param string $table The table of the localized parent record
     * @param int $id The uid of the localized parent record
     * @param array|string $command Defines the command to be performed (see example above)
     */
    protected function inlineLocalizeSynchronize($table, $id, $command)
    {
        $parentRecord = BackendUtility::getRecordWSOL($table, $id);

        // Backward-compatibility handling
        if (!is_array($command)) {
            // @deprecated, will be removed in TYPO3 v12.0.
            trigger_error('DataHandler command InlineLocalizeSynchronize needs to use an array as command input, which is available since TYPO3 v7.6. This fallback mechanism will be removed in TYPO3 v12.0.', E_USER_DEPRECATED);
            // <field>, (localize | synchronize | <uid>):
            $parts = GeneralUtility::trimExplode(',', $command);
            $command = [
                'field' => $parts[0],
                // The previous process expected $id to point to the localized record already
                'language' => (int)$parentRecord[$GLOBALS['TCA'][$table]['ctrl']['languageField']],
            ];
            if (!MathUtility::canBeInterpretedAsInteger($parts[1])) {
                $command['action'] = $parts[1];
            } else {
                $command['ids'] = [$parts[1]];
            }
        }

        // In case the parent record is the default language record, fetch the localization
        if (empty($parentRecord[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
            // Fetch the live record
            // @todo: this needs to be revisited, as getRecordLocalization() does a BackendWorkspaceRestriction
            // based on $GLOBALS[BE_USER], which could differ from the $this->BE_USER->workspace value
            $parentRecordLocalization = BackendUtility::getRecordLocalization($table, $id, $command['language'], 'AND t3ver_oid=0');
            if (empty($parentRecordLocalization)) {
                $this->log($table, $id, SystemLogDatabaseAction::LOCALIZE, 0, SystemLogErrorClassification::MESSAGE, 'Localization for parent record {table}:{uid} cannot be fetched', -1, ['table' => $table, 'uid' => (int)$id], $this->eventPid($table, $id, $parentRecord['pid']));
                return;
            }
            $parentRecord = $parentRecordLocalization[0];
            $id = $parentRecord['uid'];
            // Process overlay for current selected workspace
            BackendUtility::workspaceOL($table, $parentRecord);
        }

        $field = $command['field'] ?? '';
        $language = $command['language'] ?? 0;
        $action = $command['action'] ?? '';
        $ids = $command['ids'] ?? [];

        if (!$field || !($action === 'localize' || $action === 'synchronize') && empty($ids) || !isset($GLOBALS['TCA'][$table]['columns'][$field]['config'])) {
            return;
        }

        $config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        $foreignTable = $config['foreign_table'];

        $transOrigPointer = (int)$parentRecord[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']];
        $childTransOrigPointerField = $GLOBALS['TCA'][$foreignTable]['ctrl']['transOrigPointerField'];

        if (!$parentRecord || !is_array($parentRecord) || $language <= 0 || !$transOrigPointer) {
            return;
        }

        $inlineSubType = $this->getInlineFieldType($config);
        if ($inlineSubType === false) {
            return;
        }

        $transOrigRecord = BackendUtility::getRecordWSOL($table, $transOrigPointer);

        $removeArray = [];
        $mmTable = $inlineSubType === 'mm' && isset($config['MM']) && $config['MM'] ? $config['MM'] : '';
        // Fetch children from original language parent:
        $dbAnalysisOriginal = $this->createRelationHandlerInstance();
        $dbAnalysisOriginal->start($transOrigRecord[$field], $foreignTable, $mmTable, $transOrigRecord['uid'], $table, $config);
        $elementsOriginal = [];
        foreach ($dbAnalysisOriginal->itemArray as $item) {
            $elementsOriginal[$item['id']] = $item;
        }
        unset($dbAnalysisOriginal);
        // Fetch children from current localized parent:
        $dbAnalysisCurrent = $this->createRelationHandlerInstance();
        $dbAnalysisCurrent->start($parentRecord[$field], $foreignTable, $mmTable, $id, $table, $config);
        // Perform synchronization: Possibly removal of already localized records:
        if ($action === 'synchronize') {
            foreach ($dbAnalysisCurrent->itemArray as $index => $item) {
                $childRecord = BackendUtility::getRecordWSOL($item['table'], $item['id']);
                if (isset($childRecord[$childTransOrigPointerField]) && $childRecord[$childTransOrigPointerField] > 0) {
                    $childTransOrigPointer = $childRecord[$childTransOrigPointerField];
                    // If synchronization is requested, child record was translated once, but original record does not exist anymore, remove it:
                    if (!isset($elementsOriginal[$childTransOrigPointer])) {
                        unset($dbAnalysisCurrent->itemArray[$index]);
                        $removeArray[$item['table']][$item['id']]['delete'] = 1;
                    }
                }
            }
        }
        // Perform synchronization/localization: Possibly add unlocalized records for original language:
        if ($action === 'localize' || $action === 'synchronize') {
            foreach ($elementsOriginal as $originalId => $item) {
                if ($this->isRecordLocalized((string)$item['table'], (int)$item['id'], (int)$language)) {
                    continue;
                }
                $item['id'] = $this->localize($item['table'], $item['id'], $language);

                if (is_int($item['id'])) {
                    $item['id'] = $this->overlayAutoVersionId($item['table'], $item['id']);
                }
                $dbAnalysisCurrent->itemArray[] = $item;
            }
        } elseif (!empty($ids)) {
            foreach ($ids as $childId) {
                if (!MathUtility::canBeInterpretedAsInteger($childId) || !isset($elementsOriginal[$childId])) {
                    continue;
                }
                $item = $elementsOriginal[$childId];
                if ($this->isRecordLocalized((string)$item['table'], (int)$item['id'], (int)$language)) {
                    continue;
                }
                $item['id'] = $this->localize($item['table'], $item['id'], $language);
                if (is_int($item['id'])) {
                    $item['id'] = $this->overlayAutoVersionId($item['table'], $item['id']);
                }
                $dbAnalysisCurrent->itemArray[] = $item;
            }
        }
        // Store the new values, we will set up the uids for the subtype later on (exception keep localization from original record):
        $value = implode(',', $dbAnalysisCurrent->getValueArray());
        $this->registerDBList[$table][$id][$field] = $value;
        // Remove child records (if synchronization requested it):
        if (is_array($removeArray) && !empty($removeArray)) {
            $tce = GeneralUtility::makeInstance(self::class, $this->referenceIndexUpdater);
            $tce->enableLogging = $this->enableLogging;
            $tce->start([], $removeArray, $this->BE_USER);
            $tce->process_cmdmap();
            unset($tce);
        }
        $updateFields = [];
        // Handle, reorder and store relations:
        if ($inlineSubType === 'list') {
            $updateFields = [$field => $value];
        } elseif ($inlineSubType === 'field') {
            $dbAnalysisCurrent->writeForeignField($config, $id);
            $updateFields = [$field => $dbAnalysisCurrent->countItems(false)];
        } elseif ($inlineSubType === 'mm') {
            $dbAnalysisCurrent->writeMM($config['MM'], $id);
            $updateFields = [$field => $dbAnalysisCurrent->countItems(false)];
        }
        // Update field referencing to child records of localized parent record:
        if (!empty($updateFields)) {
            $this->updateDB($table, $id, $updateFields);
        }
    }

    /**
     * Returns true if a localization of a record exists.
     *
     * @param string $table
     * @param int $uid
     * @param int $language
     * @return bool
     */
    protected function isRecordLocalized(string $table, int $uid, int $language): bool
    {
        $row = BackendUtility::getRecordWSOL($table, $uid);
        $localizations = BackendUtility::getRecordLocalization($table, $uid, $language, 'pid=' . (int)$row['pid']);
        return !empty($localizations);
    }

    /*********************************************
     *
     * Cmd: delete
     *
     ********************************************/
    /**
     * Delete a single record
     *
     * @param string $table Table name
     * @param int $id Record UID
     * @internal should only be used from within DataHandler
     */
    public function deleteAction($table, $id)
    {
        $recordToDelete = BackendUtility::getRecord($table, $id);

        if (is_array($recordToDelete) && isset($recordToDelete['t3ver_wsid']) && (int)$recordToDelete['t3ver_wsid'] !== 0) {
            // When dealing with a workspace record, use discard.
            $this->discard($table, null, $recordToDelete);
            return;
        }

        // Record asked to be deleted was found:
        if (is_array($recordToDelete)) {
            $recordWasDeleted = false;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'] ?? [] as $className) {
                $hookObj = GeneralUtility::makeInstance($className);
                if (method_exists($hookObj, 'processCmdmap_deleteAction')) {
                    $hookObj->processCmdmap_deleteAction($table, $id, $recordToDelete, $recordWasDeleted, $this);
                }
            }
            // Delete the record if a hook hasn't deleted it yet
            if (!$recordWasDeleted) {
                $this->deleteEl($table, $id);
            }
        }
    }

    /**
     * Delete element from any table
     *
     * @param string $table Table name
     * @param int $uid Record UID
     * @param bool $noRecordCheck Flag: If $noRecordCheck is set, then the function does not check permission to delete record
     * @param bool $forceHardDelete If TRUE, the "deleted" flag is ignored if applicable for record and the record is deleted COMPLETELY!
     * @param bool $deleteRecordsOnPage If false and if deleting pages, records on the page will not be deleted (edge case while swapping workspaces)
     * @internal should only be used from within DataHandler
     */
    public function deleteEl($table, $uid, $noRecordCheck = false, $forceHardDelete = false, bool $deleteRecordsOnPage = true)
    {
        if ($table === 'pages') {
            $this->deletePages($uid, $noRecordCheck, $forceHardDelete, $deleteRecordsOnPage);
        } else {
            $this->discardLocalizedWorkspaceVersionsOfRecord((string)$table, (int)$uid);
            $this->discardWorkspaceVersionsOfRecord($table, $uid);
            $this->deleteRecord($table, $uid, $noRecordCheck, $forceHardDelete);
        }
    }

    /**
     * When deleting a live element with sys_language_uid = 0, there may be translated records that
     * have been created in workspaces only (t3ver_state=1). Those have to be discarded explicitly
     * since the other 'delete' related code does not consider this case, otherwise the 'new' workspace
     * translation would be dangling when the live record is gone.
     */
    protected function discardLocalizedWorkspaceVersionsOfRecord(string $table, int $uid): void
    {
        if (!BackendUtility::isTableLocalizable($table)
            || !BackendUtility::isTableWorkspaceEnabled($table)
            || !$this->BE_USER->recordEditAccessInternals($table, $uid)
        ) {
            return;
        }
        $liveRecord = BackendUtility::getRecord($table, $uid);
        if ((int)($liveRecord['sys_language_uid'] ?? 0) !== 0 || (int)($liveRecord['t3ver_wsid'] ?? 0) !== 0) {
            // Don't do anything if we're not deleting a live record in default language
            return;
        }
        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
        $localizationParentFieldName = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder = $queryBuilder->select('*')->from($table)
            ->where(
                // workspace elements
                $queryBuilder->expr()->gt('t3ver_wsid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                // with sys_language_uid > 0
                $queryBuilder->expr()->gt($languageField, $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                // in state 'new'
                $queryBuilder->expr()->eq('t3ver_state', $queryBuilder->createNamedParameter(VersionState::NEW_PLACEHOLDER, \PDO::PARAM_INT)),
                // with "l10n_parent" set to uid of live record
                $queryBuilder->expr()->eq($localizationParentFieldName, $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            );
        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            // BE user must be put into this workspace temporarily so stuff like refindex updating
            // is properly registered for this workspace when discarding records in there.
            $currentUserWorkspace = $this->BE_USER->workspace;
            $this->BE_USER->workspace = (int)$row['t3ver_wsid'];
            $this->discard($table, null, $row);
            // Switch user back to original workspace
            $this->BE_USER->workspace = $currentUserWorkspace;
        }
    }

    /**
     * Discard workspace overlays of a live record: When a live row
     * is deleted, all existing workspace overlays are discarded.
     *
     * @param string $table Table name
     * @param int $uid Record UID
     * @internal should only be used from within DataHandler
     */
    protected function discardWorkspaceVersionsOfRecord($table, $uid): void
    {
        $versions = BackendUtility::selectVersionsOfRecord($table, $uid, '*', null);
        if ($versions === null) {
            // Null is returned by selectVersionsOfRecord() when table is not workspace aware.
            return;
        }
        foreach ($versions as $record) {
            if ($record['_CURRENT_VERSION'] ?? false) {
                // The live record is included in the result from selectVersionsOfRecord()
                // and marked as '_CURRENT_VERSION'. Skip this one.
                continue;
            }
            // BE user must be put into this workspace temporarily so stuff like refindex updating
            // is properly registered for this workspace when discarding records in there.
            $currentUserWorkspace = $this->BE_USER->workspace;
            $this->BE_USER->workspace = (int)$record['t3ver_wsid'];
            $this->discard($table, null, $record);
            // Switch user back to original workspace
            $this->BE_USER->workspace = $currentUserWorkspace;
        }
    }

    /**
     * Deleting a record
     * This function may not be used to delete pages-records unless the underlying records are already deleted
     * Deletes a record regardless of versioning state (live or offline, doesn't matter, the uid decides)
     * If both $noRecordCheck and $forceHardDelete are set it could even delete a "deleted"-flagged record!
     *
     * @param string $table Table name
     * @param int $uid Record UID
     * @param bool $noRecordCheck Flag: If $noRecordCheck is set, then the function does not check permission to delete record
     * @param bool $forceHardDelete If TRUE, the "deleted" flag is ignored if applicable for record and the record is deleted COMPLETELY!
     * @internal should only be used from within DataHandler
     */
    public function deleteRecord($table, $uid, $noRecordCheck = false, $forceHardDelete = false)
    {
        $currentUserWorkspace = (int)$this->BE_USER->workspace;
        $uid = (int)$uid;
        if (!$GLOBALS['TCA'][$table] || !$uid) {
            $this->log($table, $uid, SystemLogDatabaseAction::DELETE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to delete record without delete-permissions. [{reason}]', -1, ['reason' => $this->BE_USER->errorMsg]);
            return;
        }
        // Skip processing already deleted records
        if (!$forceHardDelete && $this->hasDeletedRecord($table, $uid)) {
            return;
        }

        // Checking if there is anything else disallowing deleting the record by checking if editing is allowed
        $fullLanguageAccessCheck = true;
        if ($table === 'pages') {
            // If this is a page translation, the full language access check should not be done
            $defaultLanguagePageId = $this->getDefaultLanguagePageId($uid);
            if ($defaultLanguagePageId !== $uid) {
                $fullLanguageAccessCheck = false;
            }
        }
        $hasEditAccess = $this->BE_USER->recordEditAccessInternals($table, $uid, false, $forceHardDelete, $fullLanguageAccessCheck);
        if (!$hasEditAccess) {
            $this->log($table, $uid, SystemLogDatabaseAction::DELETE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to delete record without delete-permissions');
            return;
        }
        if ($table === 'pages') {
            $perms = Permission::PAGE_DELETE;
        } elseif ($table === 'sys_file_reference' && array_key_exists('pages', $this->datamap)) {
            // @todo: find a more generic way to handle content relations of a page (without needing content editing access to that page)
            $perms = Permission::PAGE_EDIT;
        } else {
            $perms = Permission::CONTENT_EDIT;
        }
        if (!$noRecordCheck && !$this->doesRecordExist($table, $uid, $perms)) {
            return;
        }

        $recordToDelete = [];
        $recordWorkspaceId = 0;
        if (BackendUtility::isTableWorkspaceEnabled($table)) {
            $recordToDelete = BackendUtility::getRecord($table, $uid);
            $recordWorkspaceId = (int)($recordToDelete['t3ver_wsid'] ?? 0);
        }

        // Clear cache before deleting the record, else the correct page cannot be identified by clear_cache
        [$parentUid] = BackendUtility::getTSCpid($table, $uid, '');
        $this->registerRecordIdForPageCacheClearing($table, $uid, $parentUid);
        $deleteField = $GLOBALS['TCA'][$table]['ctrl']['delete'] ?? false;
        $databaseErrorMessage = '';
        if ($recordWorkspaceId > 0) {
            // If this is a workspace record, use discard
            $this->BE_USER->workspace = $recordWorkspaceId;
            $this->discard($table, null, $recordToDelete);
            // Switch user back to original workspace
            $this->BE_USER->workspace = $currentUserWorkspace;
        } elseif ($deleteField && !$forceHardDelete) {
            $updateFields = [
                $deleteField => 1,
            ];
            if ($GLOBALS['TCA'][$table]['ctrl']['tstamp']) {
                $updateFields[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
            }
            // before deleting this record, check for child records or references
            $this->deleteRecord_procFields($table, $uid);
            try {
                // Delete all l10n records as well
                $this->deletedRecords[$table][] = (int)$uid;
                $this->deleteL10nOverlayRecords($table, $uid);
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable($table)
                    ->update($table, $updateFields, ['uid' => (int)$uid]);
            } catch (DBALException $e) {
                $databaseErrorMessage = $e->getPrevious()->getMessage();
            }
        } else {
            // Delete the hard way...:
            try {
                $this->hardDeleteSingleRecord($table, (int)$uid);
                $this->deletedRecords[$table][] = (int)$uid;
                $this->deleteL10nOverlayRecords($table, $uid);
            } catch (DBALException $e) {
                $databaseErrorMessage = $e->getPrevious()->getMessage();
            }
        }
        if ($this->enableLogging) {
            $state = SystemLogDatabaseAction::DELETE;
            if ($databaseErrorMessage === '') {
                if ($forceHardDelete) {
                    $message = 'Record \'%s\' (%s) was deleted unrecoverable from page \'%s\' (%s)';
                } else {
                    $message = 'Record \'%s\' (%s) was deleted from page \'%s\' (%s)';
                }
                $propArr = $this->getRecordProperties($table, $uid);
                $pagePropArr = $this->getRecordProperties('pages', $propArr['pid']);

                $this->log($table, $uid, $state, 0, SystemLogErrorClassification::MESSAGE, $message, 0, [
                    $propArr['header'],
                    $table . ':' . $uid,
                    $pagePropArr['header'],
                    $propArr['pid'],
                ], $propArr['event_pid']);
            } else {
                $this->log($table, $uid, $state, 0, SystemLogErrorClassification::SYSTEM_ERROR, $databaseErrorMessage);
            }
        }

        // Add history entry
        $this->getRecordHistoryStore()->deleteRecord($table, $uid, $this->correlationId);

        // Update reference index with table/uid on left side (recuid)
        $this->updateRefIndex($table, $uid);
        // Update reference index with table/uid on right side (ref_uid). Important if children of a relation are deleted.
        $this->referenceIndexUpdater->registerUpdateForReferencesToItem($table, $uid, $currentUserWorkspace);
    }

    /**
     * Used to delete page because it will check for branch below pages and disallowed tables on the page as well.
     *
     * @param int $uid Page id
     * @param bool $force If TRUE, pages are not checked for permission.
     * @param bool $forceHardDelete If TRUE, the "deleted" flag is ignored if applicable for record and the record is deleted COMPLETELY!
     * @param bool $deleteRecordsOnPage If false, records on the page will not be deleted (edge case while swapping workspaces)
     * @internal should only be used from within DataHandler
     */
    public function deletePages($uid, $force = false, $forceHardDelete = false, bool $deleteRecordsOnPage = true)
    {
        $uid = (int)$uid;
        if ($uid === 0) {
            $this->log('pages', $uid, SystemLogDatabaseAction::DELETE, 0, SystemLogErrorClassification::SYSTEM_ERROR, 'Deleting all pages starting from the root-page is disabled.', -1, [], 0);
            return;
        }
        // Getting list of pages to delete:
        if ($force) {
            // Returns the branch WITHOUT permission checks (0 secures that), so it cannot return -1
            $pageIdsInBranch = $this->doesBranchExist('', $uid, 0, true);
            $res = GeneralUtility::intExplode(',', $pageIdsInBranch . $uid, true);
        } else {
            $res = $this->canDeletePage($uid);
        }
        // Perform deletion if not error:
        if (is_array($res)) {
            foreach ($res as $deleteId) {
                $this->deleteSpecificPage($deleteId, $forceHardDelete, $deleteRecordsOnPage);
            }
        } else {
            $this->log(
                'pages',
                $uid,
                SystemLogDatabaseAction::DELETE,
                0,
                SystemLogErrorClassification::SYSTEM_ERROR,
                $res,
                -1,
                [$res],
            );
        }
    }

    /**
     * Delete a page (or set deleted field to 1) and all records on it.
     *
     * @param int $uid Page id
     * @param bool $forceHardDelete If TRUE, the "deleted" flag is ignored if applicable for record and the record is deleted COMPLETELY!
     * @param bool $deleteRecordsOnPage If false, records on the page will not be deleted (edge case while swapping workspaces)
     * @internal
     * @see deletePages()
     */
    public function deleteSpecificPage($uid, $forceHardDelete = false, bool $deleteRecordsOnPage = true)
    {
        $uid = (int)$uid;
        if (!$uid) {
            // Early void return on invalid uid
            return;
        }
        $forceHardDelete = (bool)$forceHardDelete;

        // Delete either a default language page or a translated page
        $pageIdInDefaultLanguage = $this->getDefaultLanguagePageId($uid);
        $isPageTranslation = false;
        $pageLanguageId = 0;
        if ($pageIdInDefaultLanguage !== $uid) {
            // For translated pages, translated records in other tables (eg. tt_content) for the
            // to-delete translated page have their pid field set to the uid of the default language record,
            // NOT the uid of the translated page record.
            // If a translated page is deleted, only translations of records in other tables of this language
            // should be deleted. The code checks if the to-delete page is a translated page and
            // adapts the query for other tables to use the uid of the default language page as pid together
            // with the language id of the translated page.
            $isPageTranslation = true;
            $pageLanguageId = $this->pageInfo($uid, $GLOBALS['TCA']['pages']['ctrl']['languageField']);
        }

        if ($deleteRecordsOnPage) {
            $tableNames = $this->compileAdminTables();
            foreach ($tableNames as $table) {
                if ($table === 'pages' || ($isPageTranslation && !BackendUtility::isTableLocalizable($table))) {
                    // Skip pages table. And skip table if not translatable, but a translated page is deleted
                    continue;
                }

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());
                $queryBuilder
                    ->select('uid')
                    ->from($table)
                    // order by uid is needed here to process possible live records first - overlays always
                    // have a higher uid. Otherwise dbms like postgres may return rows in arbitrary order,
                    // leading to hard to debug issues. This is especially relevant for the
                    // discardWorkspaceVersionsOfRecord() call below.
                    ->addOrderBy('uid');

                if ($isPageTranslation) {
                    // Only delete records in the specified language
                    $queryBuilder->where(
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter($pageIdInDefaultLanguage, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            $GLOBALS['TCA'][$table]['ctrl']['languageField'],
                            $queryBuilder->createNamedParameter($pageLanguageId, \PDO::PARAM_INT)
                        )
                    );
                } else {
                    // Delete all records on this page
                    $queryBuilder->where(
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                        )
                    );
                }

                $currentUserWorkspace = (int)$this->BE_USER->workspace;
                if ($currentUserWorkspace !== 0 && BackendUtility::isTableWorkspaceEnabled($table)) {
                    // If we are in a workspace, make sure only records of this workspace are deleted.
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->eq(
                            't3ver_wsid',
                            $queryBuilder->createNamedParameter($currentUserWorkspace, \PDO::PARAM_INT)
                        )
                    );
                }

                $statement = $queryBuilder->executeQuery();

                while ($row = $statement->fetchAssociative()) {
                    // Delete any further workspace overlays of the record in question, then delete the record.
                    $this->discardWorkspaceVersionsOfRecord($table, $row['uid']);
                    $this->deleteRecord($table, $row['uid'], true, $forceHardDelete);
                }
            }
        }

        // Delete any further workspace overlays of the record in question, then delete the record.
        $this->discardWorkspaceVersionsOfRecord('pages', $uid);
        $this->deleteRecord('pages', $uid, true, $forceHardDelete);
    }

    /**
     * Used to evaluate if a page can be deleted
     *
     * @param int $uid Page id
     * @return int[]|string If array: List of page uids to traverse and delete (means OK), if string: error message.
     * @internal should only be used from within DataHandler
     */
    public function canDeletePage($uid)
    {
        $uid = (int)$uid;
        $isTranslatedPage = null;

        // If we may at all delete this page
        // If this is a page translation, do the check against the perms_* of the default page
        // Because it is currently only deleting the translation
        $defaultLanguagePageId = $this->getDefaultLanguagePageId($uid);
        if ($defaultLanguagePageId !== $uid) {
            if ($this->doesRecordExist('pages', (int)$defaultLanguagePageId, Permission::PAGE_DELETE)) {
                $isTranslatedPage = true;
            } else {
                return 'Attempt to delete page without permissions';
            }
        } elseif (!$this->doesRecordExist('pages', $uid, Permission::PAGE_DELETE)) {
            return 'Attempt to delete page without permissions';
        }

        $pageIdsInBranch = $this->doesBranchExist('', $uid, Permission::PAGE_DELETE, true);

        if ($pageIdsInBranch === -1) {
            return 'Attempt to delete pages in branch without permissions';
        }

        $pagesInBranch = GeneralUtility::intExplode(',', $pageIdsInBranch . $uid, true);

        if ($disallowedTables = $this->checkForRecordsFromDisallowedTables($pagesInBranch)) {
            return 'Attempt to delete records from disallowed tables (' . implode(', ', $disallowedTables) . ')';
        }

        foreach ($pagesInBranch as $pageInBranch) {
            if (!$this->BE_USER->recordEditAccessInternals('pages', $pageInBranch, false, false, $isTranslatedPage ? false : true)) {
                return 'Attempt to delete page which has prohibited localizations.';
            }
        }
        return $pagesInBranch;
    }

    /**
     * Returns TRUE if record CANNOT be deleted, otherwise FALSE. Used to check before the versioning API allows a record to be marked for deletion.
     *
     * @param string $table Record Table
     * @param int $id Record UID
     * @return string Returns a string IF there is an error (error string explaining). FALSE means record can be deleted
     * @internal should only be used from within DataHandler
     */
    public function cannotDeleteRecord($table, $id)
    {
        if ($table === 'pages') {
            $res = $this->canDeletePage($id);
            return is_array($res) ? false : $res;
        }
        if ($table === 'sys_file_reference' && array_key_exists('pages', $this->datamap)) {
            // @todo: find a more generic way to handle content relations of a page (without needing content editing access to that page)
            $perms = Permission::PAGE_EDIT;
        } else {
            $perms = Permission::CONTENT_EDIT;
        }
        return $this->doesRecordExist($table, $id, $perms) ? false : 'No permission to delete record';
    }

    /**
     * Before a record is deleted, check if it has references such as inline type or MM references.
     * If so, set these child records also to be deleted.
     *
     * @param string $table Record Table
     * @param int $uid Record UID
     * @see deleteRecord()
     * @internal should only be used from within DataHandler
     */
    public function deleteRecord_procFields($table, $uid)
    {
        $conf = $GLOBALS['TCA'][$table]['columns'];
        $row = BackendUtility::getRecord($table, $uid, '*', '', false);
        if (empty($row)) {
            return;
        }
        foreach ($row as $field => $value) {
            $this->deleteRecord_procBasedOnFieldType($table, $uid, $value, $conf[$field]['config'] ?? []);
        }
    }

    /**
     * Process fields of a record to be deleted and search for special handling, like
     * inline type, MM records, etc.
     *
     * @param string $table Record Table
     * @param int $uid Record UID
     * @param string $value Record field value
     * @param array $conf TCA configuration of current field
     * @see deleteRecord()
     * @internal should only be used from within DataHandler
     */
    public function deleteRecord_procBasedOnFieldType($table, $uid, $value, $conf): void
    {
        if (!isset($conf['type'])) {
            return;
        }
        if ($conf['type'] === 'inline') {
            $foreign_table = $conf['foreign_table'];
            if ($foreign_table) {
                $inlineType = $this->getInlineFieldType($conf);
                if ($inlineType === 'list' || $inlineType === 'field') {
                    $dbAnalysis = $this->createRelationHandlerInstance();
                    $dbAnalysis->start($value, $conf['foreign_table'], '', $uid, $table, $conf);
                    $dbAnalysis->undeleteRecord = true;

                    $enableCascadingDelete = true;
                    // non type save comparison is intended!
                    if (isset($conf['behaviour']['enableCascadingDelete']) && $conf['behaviour']['enableCascadingDelete'] == false) {
                        $enableCascadingDelete = false;
                    }

                    // Walk through the items and remove them
                    foreach ($dbAnalysis->itemArray as $v) {
                        if ($enableCascadingDelete) {
                            $this->deleteAction($v['table'], $v['id']);
                        }
                    }
                }
            }
        } elseif ($this->isReferenceField($conf)) {
            $allowedTables = $conf['type'] === 'group' ? $conf['allowed'] : $conf['foreign_table'];
            $dbAnalysis = $this->createRelationHandlerInstance();
            $dbAnalysis->start($value, $allowedTables, $conf['MM'] ?? '', $uid, $table, $conf);
            foreach ($dbAnalysis->itemArray as $v) {
                $this->updateRefIndex($v['table'], $v['id']);
            }
        }
    }

    /**
     * Find l10n-overlay records and perform the requested delete action for these records.
     *
     * @param string $table Record Table
     * @param int $uid Record UID
     * @internal should only be used from within DataHandler
     */
    public function deleteL10nOverlayRecords($table, $uid)
    {
        // Check whether table can be localized
        if (!BackendUtility::isTableLocalizable($table)) {
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->BE_USER->workspace));

        $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            );

        $result = $queryBuilder->executeQuery();
        while ($record = $result->fetchAssociative()) {
            // Ignore workspace delete placeholders. Those records have been marked for
            // deletion before - deleting them again in a workspace would revert that state.
            if ((int)$this->BE_USER->workspace > 0 && BackendUtility::isTableWorkspaceEnabled($table)) {
                BackendUtility::workspaceOL($table, $record, $this->BE_USER->workspace);
                if (VersionState::cast($record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                    continue;
                }
            }
            $this->deleteAction($table, (int)($record['t3ver_oid'] ?? 0) > 0 ? (int)$record['t3ver_oid'] : (int)$record['uid']);
        }
    }

    /*********************************************
     *
     * Cmd: undelete / restore
     *
     ********************************************/

    /**
     * Restore live records by setting soft-delete flag to 0.
     *
     * Usually only used by ext:recycler.
     * Connected relations (eg. inline) are restored, too.
     * Additional existing localizations are not restored.
     *
     * @param string $table Record table name
     * @param int $uid Record uid
     */
    protected function undeleteRecord(string $table, int $uid): void
    {
        $record = BackendUtility::getRecord($table, $uid, '*', '', false);
        $deleteField = (string)($GLOBALS['TCA'][$table]['ctrl']['delete'] ?? '');
        $timestampField = (string)($GLOBALS['TCA'][$table]['ctrl']['tstamp'] ?? '');

        if ($record === null
            || $deleteField === ''
            || !isset($record[$deleteField])
            || (bool)$record[$deleteField] === false
            || ($timestampField !== '' && !isset($record[$timestampField]))
            || (int)$this->BE_USER->workspace > 0
            || (BackendUtility::isTableWorkspaceEnabled($table) && (int)($record['t3ver_wsid'] ?? 0) > 0)
        ) {
            // Return early and silently, if:
            // * Record not found
            // * Table is not soft-delete aware
            // * Record does not have deleted field - db analyzer not up-to-date?
            // * Record is not deleted - may eventually happen via recursion with self referencing records?
            // * Table is tstamp aware, but field does not exist - db analyzer not up-to-date?
            // * User is in a workspace - does not make sense
            // * Record is in a workspace - workspace records are not soft-delete aware
            return;
        }

        $recordPid = (int)($record['pid'] ?? 0);
        if ($recordPid > 0) {
            // Record is not on root level. Parent page record must exist and must not be deleted itself.
            $page = BackendUtility::getRecord('pages', $recordPid, 'deleted', '', false);
            if ($page === null || !isset($page['deleted']) || (bool)$page['deleted'] === true) {
                $this->log(
                    $table,
                    $uid,
                    SystemLogDatabaseAction::DELETE,
                    0,
                    SystemLogErrorClassification::USER_ERROR,
                    sprintf('Record "%s:%s" can\'t be restored: The page:%s containing it does not exist or is soft-deleted.', $table, $uid, $recordPid),
                    0,
                    [],
                    $recordPid
                );
                return;
            }
        }

        // @todo: When restoring a not-default language record, it should be verified the default language
        // @todo: record is *not* set to deleted. Maybe even verify a possible l10n_source chain is not deleted?

        if (!$this->BE_USER->recordEditAccessInternals($table, $record, false, true)) {
            // User misses access permissions to record
            $this->log(
                $table,
                $uid,
                SystemLogDatabaseAction::DELETE,
                0,
                SystemLogErrorClassification::USER_ERROR,
                sprintf('Record "%s:%s" can\'t be restored: Insufficient user permissions.', $table, $uid),
                0,
                [],
                $recordPid
            );
            return;
        }

        // Restore referenced child records
        $this->undeleteRecordRelations($table, $uid, $record);

        // Restore record
        $updateFields[$deleteField] = 0;
        if ($timestampField !== '') {
            $updateFields[$timestampField] = $GLOBALS['EXEC_TIME'];
        }
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)
            ->update(
                $table,
                $updateFields,
                ['uid' => $uid]
            );

        if ($this->enableLogging) {
            $this->log(
                $table,
                $uid,
                SystemLogDatabaseAction::INSERT,
                0,
                SystemLogErrorClassification::MESSAGE,
                sprintf('Record "%s:%s" was restored on page:%s', $table, $uid, $recordPid),
                0,
                [],
                $recordPid
            );
        }

        // Register cache clearing of page, or parent page if a page is restored.
        $this->registerRecordIdForPageCacheClearing($table, $uid, $recordPid);
        // Add history entry
        $this->getRecordHistoryStore()->undeleteRecord($table, $uid, $this->correlationId);
        // Update reference index with table/uid on left side (recuid)
        $this->updateRefIndex($table, $uid);
        // Update reference index with table/uid on right side (ref_uid). Important if children of a relation were restored.
        $this->referenceIndexUpdater->registerUpdateForReferencesToItem($table, $uid, 0);
    }

    /**
     * Check if a to-restore record has inline references and restore them.
     *
     * @param string $table Record table name
     * @param int $uid Record uid
     * @param array $record Record row
     * @todo: Add functional test undelete coverage to verify details, some details seem to be missing.
     */
    protected function undeleteRecordRelations(string $table, int $uid, array $record): void
    {
        foreach ($record as $fieldName => $value) {
            $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config'] ?? [];
            $fieldType = (string)($fieldConfig['type'] ?? '');
            if (empty($fieldConfig) || !is_array($fieldConfig) || $fieldType === '') {
                continue;
            }
            $foreignTable = (string)($fieldConfig['foreign_table'] ?? '');
            if ($fieldType === 'inline') {
                // @todo: Inline MM not handled here, and what about group / select?
                if ($foreignTable === ''
                    || !in_array($this->getInlineFieldType($fieldConfig), ['list', 'field'], true)
                ) {
                    continue;
                }
                $relationHandler = $this->createRelationHandlerInstance();
                $relationHandler->start($value, $foreignTable, '', $uid, $table, $fieldConfig);
                $relationHandler->undeleteRecord = true;
                foreach ($relationHandler->itemArray as $reference) {
                    $this->undeleteRecord($reference['table'], (int)$reference['id']);
                }
            } elseif ($this->isReferenceField($fieldConfig)) {
                $allowedTables = $fieldType === 'group' ? ($fieldConfig['allowed'] ?? '') : $foreignTable;
                $relationHandler = $this->createRelationHandlerInstance();
                $relationHandler->start($value, $allowedTables, $fieldConfig['MM'] ?? '', $uid, $table, $fieldConfig);
                foreach ($relationHandler->itemArray as $reference) {
                    // @todo: Unsure if this is ok / enough. Needs coverage.
                    $this->updateRefIndex($reference['table'], $reference['id']);
                }
            }
        }
    }

    /*********************************************
     *
     * Cmd: Workspace discard & flush
     *
     ********************************************/

    /**
     * Discard a versioned record from this workspace. This deletes records from the database - no soft delete.
     * This main entry method is called recursive for sub pages, localizations, relations and records on a page.
     * The method checks user access and gathers facts about this record to hand the deletion over to detail methods.
     *
     * The incoming $uid or $row can be anything: The workspace of current user is respected and only records
     * of current user workspace are discarded. If giving a live record uid, the versioned overly will be fetched.
     *
     * @param string $table Database table name
     * @param int|null $uid Uid of live or versioned record to be discarded, or null if $record is given
     * @param array|null $record Record row that should be discarded. Used instead of $uid within recursion.
     * @internal should only be used from within DataHandler
     */
    public function discard(string $table, ?int $uid, array $record = null): void
    {
        if ($uid === null && $record === null) {
            throw new \RuntimeException('Either record $uid or $record row must be given', 1600373491);
        }

        // Fetch record we are dealing with if not given
        if ($record === null) {
            $record = BackendUtility::getRecord($table, (int)$uid);
        }
        if (!is_array($record)) {
            return;
        }
        $uid = (int)$record['uid'];

        // Call hook and return if hook took care of the element
        $recordWasDiscarded = false;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'processCmdmap_discardAction')) {
                $hookObj->processCmdmap_discardAction($table, $uid, $record, $recordWasDiscarded);
            }
        }

        $userWorkspace = (int)$this->BE_USER->workspace;
        if ($recordWasDiscarded
            || $userWorkspace === 0
            || !BackendUtility::isTableWorkspaceEnabled($table)
            || $this->hasDeletedRecord($table, $uid)
        ) {
            return;
        }

        // Gather versioned record
        if ((int)$record['t3ver_wsid'] === 0) {
            $record = BackendUtility::getWorkspaceVersionOfRecord($userWorkspace, $table, $uid);
        }
        if (!is_array($record)) {
            return;
        }
        $versionRecord = $record;

        // User access checks
        if ($userWorkspace !== (int)$versionRecord['t3ver_wsid']) {
            $this->log($table, $versionRecord['uid'], SystemLogDatabaseAction::DISCARD, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to discard workspace record {table}:{uid} failed: Different workspace', -1, ['table' => $table, 'uid' => (int)$versionRecord['uid']]);
            return;
        }
        if ($errorCode = $this->workspaceCannotEditOfflineVersion($table, $versionRecord)) {
            $this->log($table, $versionRecord['uid'], SystemLogDatabaseAction::DISCARD, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to discard workspace record {table}:{uid} failed: {reason}', -1, ['table' => $table, 'uid' => (int)$versionRecord['uid'], 'reason' => $errorCode]);
            return;
        }
        if (!$this->checkRecordUpdateAccess($table, $versionRecord['uid'])) {
            $this->log($table, $versionRecord['uid'], SystemLogDatabaseAction::DISCARD, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to discard workspace record {table}:{uid} failed: User has no edit access', -1, ['table' => $table, 'uid' => (int)$versionRecord['uid']]);
            return;
        }
        $fullLanguageAccessCheck = !($table === 'pages' && (int)$versionRecord[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']] !== 0);
        if (!$this->BE_USER->recordEditAccessInternals($table, $versionRecord, false, true, $fullLanguageAccessCheck)) {
            $this->log($table, $versionRecord['uid'], SystemLogDatabaseAction::DISCARD, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to discard workspace record {table}:{uid} failed: User has no delete access', -1, ['table' => $table, 'uid' => (int)$versionRecord['uid']]);
            return;
        }

        // Perform discard operations
        $versionState = VersionState::cast($versionRecord['t3ver_state']);
        if ($table === 'pages' && $versionState->equals(VersionState::NEW_PLACEHOLDER)) {
            // When discarding a new page, there can be new sub pages and new records.
            // Those need to be discarded, otherwise they'd end up as records without parent page.
            $this->discardSubPagesAndRecordsOnPage($versionRecord);
        }

        $this->discardLocalizationOverlayRecords($table, $versionRecord);
        $this->discardRecordRelations($table, $versionRecord);
        $this->discardCsvReferencesToRecord($table, $versionRecord);
        $this->hardDeleteSingleRecord($table, (int)$versionRecord['uid']);
        $this->deletedRecords[$table][] = (int)$versionRecord['uid'];
        $this->registerReferenceIndexRowsForDrop($table, (int)$versionRecord['uid'], $userWorkspace);
        $this->getRecordHistoryStore()->deleteRecord($table, (int)$versionRecord['uid'], $this->correlationId);
        $this->log(
            $table,
            (int)$versionRecord['uid'],
            SystemLogDatabaseAction::DELETE,
            0,
            SystemLogErrorClassification::MESSAGE,
            'Record {table}:{uid} was deleted unrecoverable from page {pageId}',
            0,
            ['table' => $table, 'uid' => $versionRecord['uid'], 'pageId' => $versionRecord['pid']],
            (int)$versionRecord['pid']
        );
    }

    /**
     * Also discard any sub pages and records of a new parent page if this page is discarded.
     * Discarding only in specific localization, if needed.
     *
     * @param array $page Page record row
     */
    protected function discardSubPagesAndRecordsOnPage(array $page): void
    {
        $isLocalizedPage = false;
        $sysLanguageId = (int)$page[$GLOBALS['TCA']['pages']['ctrl']['languageField']];
        $versionState = VersionState::cast($page['t3ver_state']);
        if ($sysLanguageId > 0) {
            // New or moved localized page.
            // Discard records on this page localization, but no sub pages.
            // Records of a translated page have the pid set to the default language page uid. Found in l10n_parent.
            // @todo: Discard other page translations that inherit from this?! (l10n_source field)
            $isLocalizedPage = true;
            $pid = (int)$page[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']];
        } elseif ($versionState->equals(VersionState::NEW_PLACEHOLDER)) {
            // New default language page.
            // Discard any sub pages and all other records of this page, including any page localizations.
            // The t3ver_state=1 record is incoming here. Records on this page have their pid field set to the uid
            // of this record. So, since t3ver_state=1 does not have an online counter-part, the actual UID is used here.
            $pid = (int)$page['uid'];
        } else {
            // Moved default language page.
            // Discard any sub pages and all other records of this page, including any page localizations.
            $pid = (int)$page['t3ver_oid'];
        }
        $tables = $this->compileAdminTables();
        foreach ($tables as $table) {
            if (($isLocalizedPage && $table === 'pages')
                || ($isLocalizedPage && !BackendUtility::isTableLocalizable($table))
                || !BackendUtility::isTableWorkspaceEnabled($table)
            ) {
                continue;
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());
            $queryBuilder->select('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter((int)$this->BE_USER->workspace, \PDO::PARAM_INT)
                    )
                );
            if ($isLocalizedPage) {
                // Add sys_language_uid = x restriction if discarding a localized page
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA'][$table]['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($sysLanguageId, \PDO::PARAM_INT)
                    )
                );
            }
            $statement = $queryBuilder->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                $this->discard($table, null, $row);
            }
        }
    }

    /**
     * Discard record relations like inline and MM of a record.
     *
     * @param string $table Table name of this record
     * @param array $record The record row to handle
     */
    protected function discardRecordRelations(string $table, array $record): void
    {
        foreach ($record as $field => $value) {
            $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'] ?? null;
            if (!isset($fieldConfig['type'])) {
                continue;
            }
            if ($fieldConfig['type'] === 'inline') {
                $foreignTable = $fieldConfig['foreign_table'] ?? null;
                if (!$foreignTable
                     || (isset($fieldConfig['behaviour']['enableCascadingDelete'])
                        && (bool)$fieldConfig['behaviour']['enableCascadingDelete'] === false)
                ) {
                    continue;
                }
                $inlineType = $this->getInlineFieldType($fieldConfig);
                if ($inlineType === 'list' || $inlineType === 'field') {
                    $dbAnalysis = $this->createRelationHandlerInstance();
                    $dbAnalysis->start($value, $fieldConfig['foreign_table'], '', (int)$record['uid'], $table, $fieldConfig);
                    $dbAnalysis->undeleteRecord = true;
                    foreach ($dbAnalysis->itemArray as $relationRecord) {
                        $this->discard($relationRecord['table'], (int)$relationRecord['id']);
                    }
                }
            } elseif ($this->isReferenceField($fieldConfig) && !empty($fieldConfig['MM'])) {
                $this->discardMmRelations($table, $fieldConfig, $record);
            }
            // @todo not inline and not mm - probably not handled correctly and has no proper test coverage yet
        }
    }

    /**
     * When the to-discard record is the target of a CSV group field of another table record,
     * these records need to be updated to no longer point to the discarded record.
     *
     * Those referencing records are not very easy to find with only the to-discard record being available.
     * The solution used here looks up records referencing the to-discard record by fetching a list of
     * references from sys_refindex, where the to-discard record is on the right side (ref_* fields)
     * and in the workspace the to-discard record lives in. The referencing record fields are then updated
     * to drop the to-discard record from the CSV list.
     *
     * Using sys_refindex for this task is a bit risky: This would fail if a DataHandler call
     * adds a reference to the record and requests discarding the record in one call - the refindex
     * is always only updated at the very end of a DataHandler call, the logic below wouldn't catch
     * this since it would be based on an outdated sys_refindex. The scenario however is of little use and
     * not used in core, so it should be fine.
     *
     * @param string $table Table name of this record
     * @param array $record The record row to handle
     */
    protected function discardCsvReferencesToRecord(string $table, array $record): void
    {
        // @see test workspaces Group Discard createContentAndCreateElementRelationAndDiscardElement
        // Records referencing the to-discard record.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        $statement = $queryBuilder->select('tablename', 'recuid', 'field')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('workspace', $queryBuilder->createNamedParameter($record['t3ver_wsid'], \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($record['uid'], \PDO::PARAM_INT))
            )
            ->executeQuery();
        while ($row = $statement->fetchAssociative()) {
            // For each record referencing the to-discard record, see if it is a CSV group field definition.
            // If so, update that record to drop both the possible "uid" and "table_name_uid" variants from the list.
            $fieldTca = $GLOBALS['TCA'][$row['tablename']]['columns'][$row['field']]['config'] ?? [];
            $groupAllowed = GeneralUtility::trimExplode(',', $fieldTca['allowed'] ?? '', true);
            // @todo: "select" may be affected too, but it has no coverage to show this, yet?
            if (($fieldTca['type'] ?? '') === 'group'
                && empty($fieldTca['MM'])
                && (in_array('*', $groupAllowed, true) || in_array($table, $groupAllowed, true))
            ) {
                // Note it would be possible to a) update multiple records with only one DB call, and b) combine the
                // select and update to a single update query by doing the CSV manipulation as string function in sql.
                // That's harder to get right though and probably not *that* beneficial performance-wise since we're
                // most likely dealing with a very small number of records here anyways. Still, an optimization should
                // be considered after we drop TCA 'prepend_tname' handling and always rely only on "table_name_uid"
                // variant for CSV storage.

                // Get that record
                $recordReferencingDiscardedRecord = BackendUtility::getRecord($row['tablename'], $row['recuid'], $row['field']);
                if (!$recordReferencingDiscardedRecord) {
                    continue;
                }
                // Drop "uid" and "table_name_uid" from list
                $listOfRelatedRecords = GeneralUtility::trimExplode(',', $recordReferencingDiscardedRecord[$row['field']], true);
                $listOfRelatedRecordsWithoutDiscardedRecord = array_diff($listOfRelatedRecords, [$record['uid'], $table . '_' . $record['uid']]);
                if ($listOfRelatedRecords !== $listOfRelatedRecordsWithoutDiscardedRecord) {
                    // Update record if list changed
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($row['tablename']);
                    $queryBuilder->update($row['tablename'])
                        ->set($row['field'], implode(',', $listOfRelatedRecordsWithoutDiscardedRecord))
                        ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($row['recuid'], \PDO::PARAM_INT)))
                        ->executeStatement();
                }
            }
        }
    }

    /**
     * When a workspace record row is discarded that has mm relations, existing mm table rows need
     * to be deleted. The method performs the delete operation depending on TCA field configuration.
     *
     * @param string $table Table name of this record
     * @param array $fieldConfig TCA configuration of this field
     * @param array $record The full record of a left- or ride-side relation
     */
    protected function discardMmRelations(string $table, array $fieldConfig, array $record): void
    {
        $recordUid = (int)$record['uid'];
        $mmTableName = $fieldConfig['MM'];
        // left - non foreign - uid_local vs. right - foreign - uid_foreign decision
        $relationUidFieldName = isset($fieldConfig['MM_opposite_field']) ? 'uid_foreign' : 'uid_local';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mmTableName);
        $queryBuilder->delete($mmTableName)->where(
            // uid_local = given uid OR uid_foreign = given uid
            $queryBuilder->expr()->eq($relationUidFieldName, $queryBuilder->createNamedParameter($recordUid, \PDO::PARAM_INT))
        );
        if (!empty($fieldConfig['MM_table_where']) && is_string($fieldConfig['MM_table_where'])) {
            if (GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('runtimeDbQuotingOfTcaConfiguration')) {
                $queryBuilder->andWhere(
                    QueryHelper::stripLogicalOperatorPrefix(str_replace('###THIS_UID###', (string)$recordUid, QueryHelper::quoteDatabaseIdentifiers($queryBuilder->getConnection(), $fieldConfig['MM_table_where'])))
                );
            } else {
                $queryBuilder->andWhere(
                    QueryHelper::stripLogicalOperatorPrefix(str_replace('###THIS_UID###', (string)$recordUid, $fieldConfig['MM_table_where']))
                );
            }
        }
        $mmMatchFields = $fieldConfig['MM_match_fields'] ?? [];
        foreach ($mmMatchFields as $fieldName => $fieldValue) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq($fieldName, $queryBuilder->createNamedParameter($fieldValue, \PDO::PARAM_STR))
            );
        }
        $queryBuilder->executeStatement();

        // refindex treatment for mm relation handling: If the to discard record is foreign side of an mm relation,
        // there may be other refindex rows that become obsolete when that record is discarded. See Modify
        // addCategoryRelation sys_category-29->tt_content-298. We thus register an update for references
        // to this item (right side - ref_table, ref_uid) in reference index updater to catch these.
        if ($relationUidFieldName === 'uid_foreign') {
            $this->referenceIndexUpdater->registerUpdateForReferencesToItem($table, $recordUid, (int)$record['t3ver_wsid']);
        }
    }

    /**
     * Find localization overlays of a record and discard them.
     *
     * @param string $table Table of this record
     * @param array $record Record row
     */
    protected function discardLocalizationOverlayRecords(string $table, array $record): void
    {
        if (!BackendUtility::isTableLocalizable($table)) {
            return;
        }
        $uid = (int)$record['uid'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());
        $statement = $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter((int)$this->BE_USER->workspace, \PDO::PARAM_INT)
                )
            )
            ->executeQuery();
        while ($record = $statement->fetchAssociative()) {
            $this->discard($table, null, $record);
        }
    }

    /*********************************************
     *
     * Cmd: Versioning
     *
     ********************************************/
    /**
     * Creates a new version of a record
     * (Requires support in the table)
     *
     * @param string $table Table name
     * @param int $id Record uid to versionize
     * @param string $label Version label
     * @param bool $delete If TRUE, the version is created to delete the record.
     * @return int|null Returns the id of the new version (if any)
     * @see copyRecord()
     * @internal should only be used from within DataHandler
     */
    public function versionizeRecord($table, $id, $label, $delete = false)
    {
        $id = (int)$id;
        // Stop any actions if the record is marked to be deleted:
        // (this can occur if IRRE elements are versionized and child elements are removed)
        if ($this->isElementToBeDeleted($table, $id)) {
            return null;
        }
        if (!BackendUtility::isTableWorkspaceEnabled($table) || $id <= 0) {
            $this->log($table, $id, SystemLogDatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Versioning is not supported for this table {table}/{uid}', -1, ['table' => $table, 'uid' => (int)$id]);
            return null;
        }

        // Fetch record with permission check
        $row = $this->recordInfoWithPermissionCheck($table, $id, Permission::PAGE_SHOW);

        // This checks if the record can be selected which is all that a copy action requires.
        if ($row === false) {
            $this->log($table, $id, SystemLogDatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'The record does not exist or you don\'t have correct permissions to make a new version (copy) of this record "{table}:{uid}"', -1, ['table' => $table, 'uid' => (int)$id]);
            return null;
        }

        // Record must be online record, otherwise we would create a version of a version
        if (($row['t3ver_oid'] ?? 0) > 0) {
            $this->log($table, $id, SystemLogDatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Record "{table}:{uid}" you wanted to versionize was already a version in archive (record has an online ID)!', -1, ['table' => $table, 'uid' => (int)$id]);
            return null;
        }

        if ($delete && $errorCode = $this->cannotDeleteRecord($table, $id)) {
            $this->log($table, $id, SystemLogDatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Record {table}:{uid} cannot be deleted: {reason}', -1, ['table' => $table, 'uid' => (int)$id, 'reason' => $errorCode]);
            return null;
        }

        // Set up the values to override when making a raw-copy:
        $overrideArray = [
            't3ver_oid' => $id,
            't3ver_wsid' => $this->BE_USER->workspace,
            't3ver_state' => (string)($delete ? new VersionState(VersionState::DELETE_PLACEHOLDER) : new VersionState(VersionState::DEFAULT_STATE)),
            't3ver_stage' => 0,
        ];
        if ($GLOBALS['TCA'][$table]['ctrl']['editlock'] ?? false) {
            $overrideArray[$GLOBALS['TCA'][$table]['ctrl']['editlock']] = 0;
        }
        // Checking if the record already has a version in the current workspace of the backend user
        $versionRecord = ['uid' => null];
        if ($this->BE_USER->workspace !== 0) {
            // Look for version already in workspace:
            $versionRecord = BackendUtility::getWorkspaceVersionOfRecord($this->BE_USER->workspace, $table, $id, 'uid');
        }
        // Create new version of the record and return the new uid
        if (empty($versionRecord['uid'])) {
            // Create raw-copy and return result:
            // The information of the label to be used for the workspace record
            // as well as the information whether the record shall be removed
            // must be forwarded (creating delete placeholders on a workspace are
            // done by copying the record and override several fields).
            $workspaceOptions = [
                'delete' => $delete,
                'label' => $label,
            ];
            return $this->copyRecord_raw($table, $id, (int)$row['pid'], $overrideArray, $workspaceOptions);
        }
        // Reuse the existing record and return its uid
        // (prior to TYPO3 CMS 6.2, an error was thrown here, which
        // did not make much sense since the information is available)
        return $versionRecord['uid'];
    }

    /**
     * Handle MM relations attached to a record when publishing a workspace record.
     *
     * Strategy:
     * * Find all MM tables the record can be attached to by scanning TCA. Handle
     *   flex form "first level" fields too, but skip scanning for MM relations in
     *   container sections, since core does not support that since v7 - FormEngine
     *   throws an exception in this case.
     * * For each found MM table: Delete current MM rows of the live record, and
     *   update MM rows of the workspace record to now point to the live record.
     *
     * @internal should only be used from within DataHandler
     */
    public function versionPublishManyToManyRelations(string $table, array $liveRecord, array $workspaceRecord, int $fromWorkspace): void
    {
        if (!is_array($GLOBALS['TCA'][$table]['columns'])) {
            return;
        }
        $toDeleteRegistry = [];
        $toUpdateRegistry = [];
        foreach ($GLOBALS['TCA'][$table]['columns'] as $dbFieldName => $dbFieldConfig) {
            if (empty($dbFieldConfig['config']['type'])) {
                continue;
            }
            if (!empty($dbFieldConfig['config']['MM']) && $this->isReferenceField($dbFieldConfig['config'])) {
                $toDeleteRegistry[] = $dbFieldConfig['config'];
                $toUpdateRegistry[] = $dbFieldConfig['config'];
            }
            if ($dbFieldConfig['config']['type'] === 'flex') {
                $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
                // Find possible mm tables attached to live record flex from data structures, mark as to delete
                $dataStructureIdentifier = $flexFormTools->getDataStructureIdentifier($dbFieldConfig, $table, $dbFieldName, $liveRecord);
                $dataStructureArray = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
                foreach (($dataStructureArray['sheets'] ?? []) as $flexSheetDefinition) {
                    foreach (($flexSheetDefinition['ROOT']['el'] ?? []) as $flexFieldDefinition) {
                        if (is_array($flexFieldDefinition) && $this->flexFieldDefinitionIsMmRelation($flexFieldDefinition)) {
                            $toDeleteRegistry[] = $flexFieldDefinition['TCEforms']['config'];
                        }
                    }
                }
                // Find possible mm tables attached to workspace record flex from data structures, mark as to update uid
                $dataStructureIdentifier = $flexFormTools->getDataStructureIdentifier($dbFieldConfig, $table, $dbFieldName, $workspaceRecord);
                $dataStructureArray = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
                foreach (($dataStructureArray['sheets'] ?? []) as $flexSheetDefinition) {
                    foreach (($flexSheetDefinition['ROOT']['el'] ?? []) as $flexFieldDefinition) {
                        if (is_array($flexFieldDefinition) && $this->flexFieldDefinitionIsMmRelation($flexFieldDefinition)) {
                            $toUpdateRegistry[] = $flexFieldDefinition['TCEforms']['config'];
                        }
                    }
                }
            }
        }

        // Delete mm table relations of live record
        foreach ($toDeleteRegistry as $config) {
            $uidFieldName = $this->mmRelationIsLocalSide($config) ? 'uid_local' : 'uid_foreign';
            $mmTableName = $config['MM'];
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mmTableName);
            $queryBuilder->delete($mmTableName);
            $queryBuilder->where($queryBuilder->expr()->eq(
                $uidFieldName,
                $queryBuilder->createNamedParameter((int)$liveRecord['uid'], \PDO::PARAM_INT)
            ));
            if ($this->mmQueryShouldUseTablenamesColumn($config)) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter($table)
                ));
            }
            $queryBuilder->executeStatement();
        }

        // Update mm table relations of workspace record to uid of live record
        foreach ($toUpdateRegistry as $config) {
            $mmRelationIsLocalSide = $this->mmRelationIsLocalSide($config);
            $uidFieldName = $mmRelationIsLocalSide ? 'uid_local' : 'uid_foreign';
            $mmTableName = $config['MM'];
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mmTableName);
            $queryBuilder->update($mmTableName);
            $queryBuilder->set($uidFieldName, (int)$liveRecord['uid'], true, \PDO::PARAM_INT);
            $queryBuilder->where($queryBuilder->expr()->eq(
                $uidFieldName,
                $queryBuilder->createNamedParameter((int)$workspaceRecord['uid'], \PDO::PARAM_INT)
            ));
            if ($this->mmQueryShouldUseTablenamesColumn($config)) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter($table)
                ));
            }
            $queryBuilder->executeStatement();

            if (!$mmRelationIsLocalSide) {
                // refindex treatment for mm relation handling: If the to publish record is foreign side of an mm relation, we need
                // to instruct refindex updater to update all local side references for the live record the current workspace record
                // has on foreign side. See ManyToMany Publish addCategoryRelation, this will create the sys_category-31->tt_content-297 entry.
                $this->referenceIndexUpdater->registerUpdateForReferencesToItem($table, (int)$workspaceRecord['uid'], $fromWorkspace, 0);
                // Similar, when in mm foreign side and relations are deleted in live during publish, other relations pointing to the
                // same local side record may need updates due to different sorting, and the former refindex entry of the live record
                // needs updates. See ManyToMany Publish deleteCategoryRelation scenario.
                $this->referenceIndexUpdater->registerUpdateForReferencesToItem($table, (int)$liveRecord['uid'], 0);
            }
        }
    }

    /**
     * Find out if a given flex field definition is a relation with an MM relation.
     * Helper of versionPublishManyToManyRelations().
     */
    private function flexFieldDefinitionIsMmRelation(array $flexFieldDefinition): bool
    {
        return ($flexFieldDefinition['type'] ?? '') !== 'array' // is a field, not a section
            && is_array($flexFieldDefinition['TCEforms']['config'] ?? false) // config array exists
            && $this->isReferenceField($flexFieldDefinition['TCEforms']['config']) // select, group, category
            && !empty($flexFieldDefinition['TCEforms']['config']['MM']); // MM exists
    }

    /**
     * Find out if a query to an MM table should have a "tablenames=myTable" where. This
     * is the case if we're looking at it from the foreign side and if the table must have
     * "tablenames" column due to various TCA combinations.
     * Helper of versionPublishManyToManyRelations().
     */
    private function mmQueryShouldUseTablenamesColumn(array $config): bool
    {
        if ($this->mmRelationIsLocalSide($config)) {
            return false;
        }
        if ($config['type'] === 'group' && !empty($config['prepend_tname'])) {
            // prepend_tname in MM on foreign side forces 'tablenames' column
            // @todo: See if we can get rid of prepend_tname in MM altogether?
            return true;
        }
        if ($config['type'] === 'group' && is_string($config['allowed'] ?? false)
            && (str_contains($config['allowed'], ',') || $config['allowed'] === '*')
        ) {
            // 'allowed' with *, or more than one table
            // @todo: Neither '*' nor 'multiple tables' make sense for MM on foreign side.
            //        There is a hint in the docs about this, too. Sanitize in TCA bootstrap?!
            return true;
        }
        $localSideTableName = $config['type'] === 'group' ? $config['allowed'] ?? '' : $config['foreign_table'] ?? '';
        $localSideFieldName = $config['MM_opposite_field'] ?? '';
        $localSideAllowed = $GLOBALS['TCA'][$localSideTableName]['columns'][$localSideFieldName]['config']['allowed'] ?? '';
        // Local side with 'allowed' = '*' or multiple tables forces 'tablenames' column
        return $localSideAllowed === '*' || str_contains($localSideAllowed, ',');
    }

    /**
     * Find out if we're looking at an MM relation from local or foreign side.
     * Helper of versionPublishManyToManyRelations().
     */
    private function mmRelationIsLocalSide(array $config): bool
    {
        return empty($config['MM_opposite_field']);
    }

    /*********************************************
     *
     * Cmd: Helper functions
     *
     ********************************************/

    /**
     * Returns an instance of DataHandler for handling local datamaps/cmdmaps
     *
     * @return DataHandler
     */
    protected function getLocalTCE()
    {
        $copyTCE = GeneralUtility::makeInstance(DataHandler::class, $this->referenceIndexUpdater);
        $copyTCE->copyTree = $this->copyTree;
        $copyTCE->enableLogging = $this->enableLogging;
        // Transformations should NOT be carried out during copy
        $copyTCE->dontProcessTransformations = true;
        // make sure the isImporting flag is transferred, so all hooks know if
        // the current process is an import process
        $copyTCE->isImporting = $this->isImporting;
        $copyTCE->bypassAccessCheckForRecords = $this->bypassAccessCheckForRecords;
        $copyTCE->bypassWorkspaceRestrictions = $this->bypassWorkspaceRestrictions;
        return $copyTCE;
    }

    /**
     * Processes the fields with references as registered during the copy process. This includes all FlexForm fields which had references.
     * @internal should only be used from within DataHandler
     */
    public function remapListedDBRecords()
    {
        if (!empty($this->registerDBList)) {
            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
            foreach ($this->registerDBList as $table => $records) {
                foreach ($records as $uid => $fields) {
                    $newData = [];
                    $theUidToUpdate = $this->copyMappingArray_merged[$table][$uid] ?? null;
                    $theUidToUpdate_saveTo = BackendUtility::wsMapId($table, $theUidToUpdate);
                    foreach ($fields as $fieldName => $value) {
                        $conf = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config'];
                        switch ($conf['type']) {
                            case 'group':
                            case 'select':
                            case 'category':
                                $vArray = $this->remapListedDBRecords_procDBRefs($conf, $value, $theUidToUpdate, $table);
                                if (is_array($vArray)) {
                                    $newData[$fieldName] = implode(',', $vArray);
                                }
                                break;
                            case 'flex':
                                if ($value === 'FlexForm_reference') {
                                    // This will fetch the new row for the element
                                    $origRecordRow = $this->recordInfo($table, $theUidToUpdate, '*');
                                    if (is_array($origRecordRow)) {
                                        BackendUtility::workspaceOL($table, $origRecordRow);
                                        // Get current data structure and value array:
                                        $dataStructureIdentifier = $flexFormTools->getDataStructureIdentifier(
                                            ['config' => $conf],
                                            $table,
                                            $fieldName,
                                            $origRecordRow
                                        );
                                        $dataStructureArray = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
                                        $currentValueArray = GeneralUtility::xml2array($origRecordRow[$fieldName]);
                                        // Do recursive processing of the XML data:
                                        $currentValueArray['data'] = $this->checkValue_flex_procInData($currentValueArray['data'], [], $dataStructureArray, [$table, $theUidToUpdate, $fieldName], 'remapListedDBRecords_flexFormCallBack');
                                        // The return value should be compiled back into XML, ready to insert directly in the field (as we call updateDB() directly later):
                                        if (is_array($currentValueArray['data'])) {
                                            $newData[$fieldName] = $this->checkValue_flexArray2Xml($currentValueArray, true);
                                        }
                                    }
                                }
                                break;
                            case 'inline':
                                $this->remapListedDBRecords_procInline($conf, $value, $uid, $table);
                                break;
                            default:
                                $this->logger->debug('Field type should not appear here: {type}', ['type' => $conf['type']]);
                        }
                    }
                    // If any fields were changed, those fields are updated!
                    if (!empty($newData)) {
                        $this->updateDB($table, $theUidToUpdate_saveTo, $newData);
                    }
                }
            }
        }
    }

    /**
     * Callback function for traversing the FlexForm structure in relation to creating copied files of file relations inside of flex form structures.
     *
     * @param array $pParams Set of parameters in numeric array: table, uid, field
     * @param array $dsConf TCA config for field (from Data Structure of course)
     * @param string $dataValue Field value (from FlexForm XML)
     * @return array Array where the "value" key carries the value.
     * @see checkValue_flex_procInData_travDS()
     * @see remapListedDBRecords()
     * @internal should only be used from within DataHandler
     */
    public function remapListedDBRecords_flexFormCallBack($pParams, $dsConf, $dataValue)
    {
        // Extract parameters:
        [$table, $uid, $field] = $pParams;
        // If references are set for this field, set flag so they can be corrected later:
        if ($this->isReferenceField($dsConf) && (string)$dataValue !== '') {
            $vArray = $this->remapListedDBRecords_procDBRefs($dsConf, $dataValue, $uid, $table);
            if (is_array($vArray)) {
                $dataValue = implode(',', $vArray);
            }
        }
        // Return
        return ['value' => $dataValue];
    }

    /**
     * Performs remapping of old UID values to NEW uid values for a DB reference field.
     *
     * @param array $conf TCA field config
     * @param string $value Field value
     * @param int $MM_localUid UID of local record (for MM relations - might need to change if support for FlexForms should be done!)
     * @param string $table Table name
     * @return array|null Returns array of items ready to implode for field content.
     * @see remapListedDBRecords()
     * @internal should only be used from within DataHandler
     */
    public function remapListedDBRecords_procDBRefs($conf, $value, $MM_localUid, $table)
    {
        // Initialize variables
        // Will be set TRUE if an upgrade should be done...
        $set = false;
        // Allowed tables for references.
        $allowedTables = $conf['type'] === 'group' ? $conf['allowed'] : $conf['foreign_table'];
        // Table name to prepend the UID
        $prependName = $conf['type'] === 'group' ? ($conf['prepend_tname'] ?? '') : '';
        // Which tables that should possibly not be remapped
        $dontRemapTables = GeneralUtility::trimExplode(',', $conf['dontRemapTablesOnCopy'] ?? '', true);
        // Convert value to list of references:
        $dbAnalysis = $this->createRelationHandlerInstance();
        $dbAnalysis->registerNonTableValues = $conf['type'] === 'select' && ($conf['allowNonIdValues'] ?? false);
        $dbAnalysis->start($value, $allowedTables, $conf['MM'] ?? '', $MM_localUid, $table, $conf);
        // Traverse those references and map IDs:
        foreach ($dbAnalysis->itemArray as $k => $v) {
            $mapID = $this->copyMappingArray_merged[$v['table']][$v['id']] ?? 0;
            if ($mapID && !in_array($v['table'], $dontRemapTables, true)) {
                $dbAnalysis->itemArray[$k]['id'] = $mapID;
                $set = true;
            }
        }
        if (!empty($conf['MM'])) {
            // Purge invalid items (live/version)
            $dbAnalysis->purgeItemArray();
            if ($dbAnalysis->isPurged()) {
                $set = true;
            }

            // If record has been versioned/copied in this process, handle invalid relations of the live record
            $liveId = BackendUtility::getLiveVersionIdOfRecord($table, $MM_localUid);
            $originalId = 0;
            if (!empty($this->copyMappingArray_merged[$table])) {
                $originalId = array_search($MM_localUid, $this->copyMappingArray_merged[$table]);
            }
            if (!empty($liveId) && !empty($originalId) && (int)$liveId === (int)$originalId) {
                $liveRelations = $this->createRelationHandlerInstance();
                $liveRelations->setWorkspaceId(0);
                $liveRelations->start('', $allowedTables, $conf['MM'], $liveId, $table, $conf);
                // Purge invalid relations in the live workspace ("0")
                $liveRelations->purgeItemArray(0);
                if ($liveRelations->isPurged()) {
                    $liveRelations->writeMM($conf['MM'], $liveId, $prependName);
                }
            }
        }
        // If a change has been done, set the new value(s)
        if ($set) {
            if ($conf['MM'] ?? false) {
                $dbAnalysis->writeMM($conf['MM'], $MM_localUid, $prependName);
            } else {
                return $dbAnalysis->getValueArray($prependName);
            }
        }
        return null;
    }

    /**
     * Performs remapping of old UID values to NEW uid values for an inline field.
     *
     * @param array $conf TCA field config
     * @param string $value Field value
     * @param int $uid The uid of the ORIGINAL record
     * @param string $table Table name
     * @internal should only be used from within DataHandler
     */
    public function remapListedDBRecords_procInline($conf, $value, $uid, $table)
    {
        $theUidToUpdate = $this->copyMappingArray_merged[$table][$uid] ?? null;
        if ($conf['foreign_table']) {
            $inlineType = $this->getInlineFieldType($conf);
            if ($inlineType === 'mm') {
                $this->remapListedDBRecords_procDBRefs($conf, $value, $theUidToUpdate, $table);
            } elseif ($inlineType !== false) {
                $dbAnalysis = $this->createRelationHandlerInstance();
                $dbAnalysis->start($value, $conf['foreign_table'], '', 0, $table, $conf);

                $updatePidForRecords = [];
                // Update values for specific versioned records
                foreach ($dbAnalysis->itemArray as &$item) {
                    $updatePidForRecords[$item['table']][] = $item['id'];
                    $versionedId = $this->getAutoVersionId($item['table'], $item['id']);
                    if ($versionedId !== null) {
                        $updatePidForRecords[$item['table']][] = $versionedId;
                        $item['id'] = $versionedId;
                    }
                }

                // Update child records if using pointer fields ('foreign_field'):
                if ($inlineType === 'field') {
                    $dbAnalysis->writeForeignField($conf, $uid, $theUidToUpdate);
                }
                $thePidToUpdate = null;
                // If the current field is set on a page record, update the pid of related child records:
                if ($table === 'pages') {
                    $thePidToUpdate = $theUidToUpdate;
                } elseif (isset($this->registerDBPids[$table][$uid])) {
                    $thePidToUpdate = $this->registerDBPids[$table][$uid];
                    $thePidToUpdate = $this->copyMappingArray_merged['pages'][$thePidToUpdate];
                }

                // Update child records if change to pid is required
                if ($thePidToUpdate && !empty($updatePidForRecords)) {
                    // Ensure that only the default language page is used as PID
                    $thePidToUpdate = $this->getDefaultLanguagePageId($thePidToUpdate);
                    // @todo: this can probably go away
                    // ensure, only live page ids are used as 'pid' values
                    $liveId = BackendUtility::getLiveVersionIdOfRecord('pages', $theUidToUpdate);
                    if ($liveId !== null) {
                        $thePidToUpdate = $liveId;
                    }
                    $updateValues = ['pid' => $thePidToUpdate];
                    foreach ($updatePidForRecords as $tableName => $uids) {
                        if (empty($tableName) || empty($uids)) {
                            continue;
                        }
                        $conn = GeneralUtility::makeInstance(ConnectionPool::class)
                            ->getConnectionForTable($tableName);
                        foreach ($uids as $updateUid) {
                            $conn->update($tableName, $updateValues, ['uid' => $updateUid]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Processes the $this->remapStack at the end of copying, inserting, etc. actions.
     * The remapStack takes care about the correct mapping of new and old uids in case of relational data.
     * @internal should only be used from within DataHandler
     */
    public function processRemapStack()
    {
        // Processes the remap stack:
        if (is_array($this->remapStack)) {
            $remapFlexForms = [];
            $hookPayload = [];

            $newValue = null;
            foreach ($this->remapStack as $remapAction) {
                // If no position index for the arguments was set, skip this remap action:
                if (!is_array($remapAction['pos'])) {
                    continue;
                }
                // Load values from the argument array in remapAction:
                $field = $remapAction['field'];
                $id = $remapAction['args'][$remapAction['pos']['id']];
                $rawId = $id;
                $table = $remapAction['args'][$remapAction['pos']['table']];
                $valueArray = $remapAction['args'][$remapAction['pos']['valueArray']];
                $tcaFieldConf = $remapAction['args'][$remapAction['pos']['tcaFieldConf']];
                $additionalData = $remapAction['additionalData'] ?? [];
                // The record is new and has one or more new ids (in case of versioning/workspaces):
                if (str_contains($id, 'NEW')) {
                    // Replace NEW...-ID with real uid:
                    $id = $this->substNEWwithIDs[$id] ?? '';
                    // If the new parent record is on a non-live workspace or versionized, it has another new id:
                    if (isset($this->autoVersionIdMap[$table][$id])) {
                        $id = $this->autoVersionIdMap[$table][$id];
                    }
                    $remapAction['args'][$remapAction['pos']['id']] = $id;
                }
                // Replace relations to NEW...-IDs in field value (uids of child records):
                if (is_array($valueArray)) {
                    foreach ($valueArray as $key => $value) {
                        if (str_contains($value, 'NEW')) {
                            if (!str_contains($value, '_')) {
                                $affectedTable = $tcaFieldConf['foreign_table'] ?? '';
                                $prependTable = false;
                            } else {
                                $parts = explode('_', $value);
                                $value = array_pop($parts);
                                $affectedTable = implode('_', $parts);
                                $prependTable = true;
                            }
                            $value = $this->substNEWwithIDs[$value] ?? '';
                            // The record is new, but was also auto-versionized and has another new id:
                            if (isset($this->autoVersionIdMap[$affectedTable][$value])) {
                                $value = $this->autoVersionIdMap[$affectedTable][$value];
                            }
                            if ($prependTable) {
                                $value = $affectedTable . '_' . $value;
                            }
                            // Set a hint that this was a new child record:
                            $this->newRelatedIDs[$affectedTable][] = $value;
                            $valueArray[$key] = $value;
                        }
                    }
                    $remapAction['args'][$remapAction['pos']['valueArray']] = $valueArray;
                }
                // Process the arguments with the defined function:
                if (!empty($remapAction['func'])) {
                    $callable = [$this, $remapAction['func']];
                    if (is_callable($callable)) {
                        $newValue = $callable(...$remapAction['args']);
                    }
                }
                // If array is returned, check for maxitems condition, if string is returned this was already done:
                if (is_array($newValue)) {
                    $newValue = implode(',', $this->checkValue_checkMax($tcaFieldConf, $newValue));
                    // The reference casting is only required if
                    // checkValue_group_select_processDBdata() returns an array
                    $newValue = $this->castReferenceValue($newValue, $tcaFieldConf);
                }
                // Update in database (list of children (csv) or number of relations (foreign_field)):
                if (!empty($field)) {
                    $fieldArray = [$field => $newValue];
                    if ($GLOBALS['TCA'][$table]['ctrl']['tstamp'] ?? false) {
                        $fieldArray[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
                    }
                    $this->updateDB($table, $id, $fieldArray);
                } elseif (!empty($additionalData['flexFormId']) && !empty($additionalData['flexFormPath'])) {
                    // Collect data to update FlexForms
                    $flexFormId = $additionalData['flexFormId'];
                    $flexFormPath = $additionalData['flexFormPath'];

                    if (!isset($remapFlexForms[$flexFormId])) {
                        $remapFlexForms[$flexFormId] = [];
                    }

                    $remapFlexForms[$flexFormId][$flexFormPath] = $newValue;
                }

                // Collect elements that shall trigger processDatamap_afterDatabaseOperations
                if (isset($this->remapStackRecords[$table][$rawId]['processDatamap_afterDatabaseOperations'])) {
                    $hookArgs = $this->remapStackRecords[$table][$rawId]['processDatamap_afterDatabaseOperations'];
                    if (!isset($hookPayload[$table][$rawId])) {
                        $hookPayload[$table][$rawId] = [
                            'status' => $hookArgs['status'],
                            'fieldArray' => $hookArgs['fieldArray'],
                            'hookObjects' => $hookArgs['hookObjectsArr'],
                        ];
                    }
                    $hookPayload[$table][$rawId]['fieldArray'][$field] = $newValue;
                }
            }

            if ($remapFlexForms) {
                foreach ($remapFlexForms as $flexFormId => $modifications) {
                    $this->updateFlexFormData((string)$flexFormId, $modifications);
                }
            }

            foreach ($hookPayload as $tableName => $rawIdPayload) {
                foreach ($rawIdPayload as $rawId => $payload) {
                    foreach ($payload['hookObjects'] as $hookObject) {
                        if (!method_exists($hookObject, 'processDatamap_afterDatabaseOperations')) {
                            continue;
                        }
                        $hookObject->processDatamap_afterDatabaseOperations(
                            $payload['status'],
                            $tableName,
                            $rawId,
                            $payload['fieldArray'],
                            $this
                        );
                    }
                }
            }
        }
        // Processes the remap stack actions:
        if ($this->remapStackActions) {
            foreach ($this->remapStackActions as $action) {
                if (isset($action['callback'], $action['arguments'])) {
                    $action['callback'](...$action['arguments']);
                }
            }
        }
        // Reset:
        $this->remapStack = [];
        $this->remapStackRecords = [];
        $this->remapStackActions = [];
    }

    /**
     * Updates FlexForm data.
     *
     * @param string $flexFormId e.g. <table>:<uid>:<field>
     * @param array $modifications Modifications with paths and values (e.g. 'sDEF/lDEV/field/vDEF' => 'TYPO3')
     */
    protected function updateFlexFormData($flexFormId, array $modifications)
    {
        [$table, $uid, $field] = explode(':', $flexFormId, 3);

        if (!MathUtility::canBeInterpretedAsInteger($uid) && !empty($this->substNEWwithIDs[$uid])) {
            $uid = $this->substNEWwithIDs[$uid];
        }

        $record = $this->recordInfo($table, $uid, '*');

        if (!$table || !$uid || !$field || !is_array($record)) {
            return;
        }

        BackendUtility::workspaceOL($table, $record);

        // Get current data structure and value array:
        $valueStructure = GeneralUtility::xml2array($record[$field]);

        // Do recursive processing of the XML data:
        foreach ($modifications as $path => $value) {
            $valueStructure['data'] = ArrayUtility::setValueByPath(
                $valueStructure['data'],
                $path,
                $value
            );
        }

        if (is_array($valueStructure['data'])) {
            // The return value should be compiled back into XML
            $values = [
                $field => $this->checkValue_flexArray2Xml($valueStructure, true),
            ];

            $this->updateDB($table, $uid, $values);
        }
    }

    /**
     * Adds an instruction to the remap action stack (used with IRRE).
     *
     * @param string $table The affected table
     * @param int|string $id The affected ID
     * @param callable $callback The callback information (object and method)
     * @param array $arguments The arguments to be used with the callback
     * @internal should only be used from within DataHandler
     */
    public function addRemapAction($table, $id, callable $callback, array $arguments)
    {
        $this->remapStackActions[] = [
            'affects' => [
                'table' => $table,
                'id' => $id,
            ],
            'callback' => $callback,
            'arguments' => $arguments,
        ];
    }

    /**
     * If a parent record was versionized on a workspace in $this->process_datamap,
     * it might be possible, that child records (e.g. on using IRRE) were affected.
     * This function finds these relations and updates their uids in the $incomingFieldArray.
     * The $incomingFieldArray is updated by reference!
     *
     * @param string $table Table name of the parent record
     * @param int $id Uid of the parent record
     * @param array $incomingFieldArray Reference to the incomingFieldArray of process_datamap
     * @param array $registerDBList Reference to the $registerDBList array that was created/updated by versionizing calls to DataHandler in process_datamap.
     * @internal should only be used from within DataHandler
     */
    public function getVersionizedIncomingFieldArray($table, $id, &$incomingFieldArray, &$registerDBList): void
    {
        if (!isset($registerDBList[$table][$id]) || !is_array($registerDBList[$table][$id])) {
            return;
        }
        foreach ($incomingFieldArray as $field => $value) {
            $foreignTable = $GLOBALS['TCA'][$table]['columns'][$field]['config']['foreign_table'] ?? '';
            if (($registerDBList[$table][$id][$field] ?? false)
                && !empty($foreignTable)
            ) {
                $newValueArray = [];
                $origValueArray = is_array($value) ? $value : explode(',', $value);
                // Update the uids of the copied records, but also take care about new records:
                foreach ($origValueArray as $childId) {
                    $newValueArray[] = $this->autoVersionIdMap[$foreignTable][$childId] ?? $childId;
                }
                // Set the changed value to the $incomingFieldArray
                $incomingFieldArray[$field] = implode(',', $newValueArray);
            }
        }
        // Clean up the $registerDBList array:
        unset($registerDBList[$table][$id]);
        if (empty($registerDBList[$table])) {
            unset($registerDBList[$table]);
        }
    }

    /**
     * Simple helper method to hard delete one row from table ignoring delete TCA field
     *
     * @param string $table A row from this table should be deleted
     * @param int $uid Uid of row to be deleted
     */
    protected function hardDeleteSingleRecord(string $table, int $uid): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->delete($table, ['uid' => $uid], [\PDO::PARAM_INT]);
    }

    /*****************************
     *
     * Access control / Checking functions
     *
     *****************************/
    /**
     * Checking group modify_table access list
     *
     * @param string $table Table name
     * @return bool Returns TRUE if the user has general access to modify the $table
     * @internal should only be used from within DataHandler
     */
    public function checkModifyAccessList($table)
    {
        $res = $this->admin || (!$this->tableAdminOnly($table) && isset($this->BE_USER->groupData['tables_modify']) && GeneralUtility::inList($this->BE_USER->groupData['tables_modify'], $table));
        // Hook 'checkModifyAccessList': Post-processing of the state of access
        foreach ($this->getCheckModifyAccessListHookObjects() as $hookObject) {
            /** @var DataHandlerCheckModifyAccessListHookInterface $hookObject */
            $hookObject->checkModifyAccessList($res, $table, $this);
        }
        return $res;
    }

    /**
     * Checking if a record with uid $id from $table is in the BE_USERS webmounts which is required for editing etc.
     *
     * @param string $table Table name
     * @param int $id UID of record
     * @return bool Returns TRUE if OK. Cached results.
     * @internal should only be used from within DataHandler
     */
    public function isRecordInWebMount($table, $id)
    {
        if (!isset($this->isRecordInWebMount_Cache[$table . ':' . $id])) {
            $recP = $this->getRecordProperties($table, $id);
            $this->isRecordInWebMount_Cache[$table . ':' . $id] = $this->isInWebMount($recP['event_pid']);
        }
        return $this->isRecordInWebMount_Cache[$table . ':' . $id];
    }

    /**
     * Checks if the input page ID is in the BE_USER webmounts
     *
     * @param int $pid Page ID to check
     * @return bool TRUE if OK. Cached results.
     * @internal should only be used from within DataHandler
     */
    public function isInWebMount($pid)
    {
        if (!isset($this->isInWebMount_Cache[$pid])) {
            $this->isInWebMount_Cache[$pid] = $this->BE_USER->isInWebMount($pid);
        }
        return $this->isInWebMount_Cache[$pid];
    }

    /**
     * Checks if user may update a record with uid=$id from $table
     *
     * @param string $table Record table
     * @param int $id Record UID
     * @param array|bool $data Record data
     * @param array $hookObjectsArr Hook objects
     * @return bool Returns TRUE if the user may update the record given by $table and $id
     * @internal should only be used from within DataHandler
     */
    public function checkRecordUpdateAccess($table, $id, $data = false, $hookObjectsArr = null)
    {
        $res = null;
        if (is_array($hookObjectsArr)) {
            foreach ($hookObjectsArr as $hookObj) {
                if (method_exists($hookObj, 'checkRecordUpdateAccess')) {
                    $res = $hookObj->checkRecordUpdateAccess($table, $id, $data, $res, $this);
                }
            }
            if (isset($res)) {
                return (bool)$res;
            }
        }
        $res = false;

        if ($GLOBALS['TCA'][$table] && (int)$id > 0) {
            $cacheId = 'checkRecordUpdateAccess_' . $table . '_' . $id;

            // If information is cached, return it
            $cachedValue = $this->runtimeCache->get($cacheId);
            if (!empty($cachedValue)) {
                return $cachedValue;
            }

            if ($table === 'pages' || ($table === 'sys_file_reference' && array_key_exists('pages', $this->datamap))) {
                // @todo: find a more generic way to handle content relations of a page (without needing content editing access to that page)
                $perms = Permission::PAGE_EDIT;
            } else {
                $perms = Permission::CONTENT_EDIT;
            }
            if ($this->doesRecordExist($table, $id, $perms)) {
                $res = 1;
            }
            // Cache the result
            $this->runtimeCache->set($cacheId, $res);
        }
        return $res;
    }

    /**
     * Checks if user may insert a record from $insertTable on $pid
     *
     * @param string $insertTable Tablename to check
     * @param int $pid Integer PID
     * @param int $action For logging: Action number.
     * @return bool Returns TRUE if the user may insert a record from table $insertTable on page $pid
     * @internal should only be used from within DataHandler
     */
    public function checkRecordInsertAccess($insertTable, $pid, $action = SystemLogDatabaseAction::INSERT)
    {
        $pid = (int)$pid;
        if ($pid < 0) {
            return false;
        }
        // If information is cached, return it
        if (isset($this->recInsertAccessCache[$insertTable][$pid])) {
            return $this->recInsertAccessCache[$insertTable][$pid];
        }

        $res = false;
        if ($insertTable === 'pages') {
            $perms = Permission::PAGE_NEW;
        } elseif (($insertTable === 'sys_file_reference') && array_key_exists('pages', $this->datamap)) {
            // @todo: find a more generic way to handle content relations of a page (without needing content editing access to that page)
            $perms = Permission::PAGE_EDIT;
        } else {
            $perms = Permission::CONTENT_EDIT;
        }
        $pageExists = (bool)$this->doesRecordExist('pages', $pid, $perms);
        // If either admin and root-level or if page record exists and 1) if 'pages' you may create new ones 2) if page-content, new content items may be inserted on the $pid page
        if ($pageExists || $pid === 0 && ($this->admin || BackendUtility::isRootLevelRestrictionIgnored($insertTable))) {
            // Check permissions
            if ($this->isTableAllowedForThisPage($pid, $insertTable)) {
                $res = true;
                // Cache the result
                $this->recInsertAccessCache[$insertTable][$pid] = $res;
            } elseif ($this->enableLogging) {
                $propArr = $this->getRecordProperties('pages', $pid);
                $this->log($insertTable, $pid, $action, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to insert record on page \'%s\' (%s) where this table, %s, is not allowed', 11, [$propArr['header'], $pid, $insertTable], $propArr['event_pid']);
            }
        } elseif ($this->enableLogging) {
            $propArr = $this->getRecordProperties('pages', $pid);
            $this->log($insertTable, $pid, $action, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to insert a record on page \'%s\' (%s) from table \'%s\' without permissions. Or non-existing page.', 12, [$propArr['header'], $pid, $insertTable], $propArr['event_pid']);
        }
        return $res;
    }

    /**
     * Checks if a table is allowed on a certain page id according to allowed tables set for the page "doktype" and its [ctrl][rootLevel]-settings if any.
     *
     * @param int $page_uid Page id for which to check, including 0 (zero) if checking for page tree root.
     * @param string $checkTable Table name to check
     * @return bool TRUE if OK
     * @internal should only be used from within DataHandler
     */
    public function isTableAllowedForThisPage($page_uid, $checkTable)
    {
        $page_uid = (int)$page_uid;
        $rootLevelSetting = (int)($GLOBALS['TCA'][$checkTable]['ctrl']['rootLevel'] ?? 0);
        // Check if rootLevel flag is set and we're trying to insert on rootLevel - and reversed - and that the table is not "pages" which are allowed anywhere.
        if ($checkTable !== 'pages' && $rootLevelSetting !== -1 && ($rootLevelSetting xor !$page_uid)) {
            return false;
        }
        $allowed = false;
        // Check root-level
        if (!$page_uid) {
            if ($this->admin || BackendUtility::isRootLevelRestrictionIgnored($checkTable)) {
                $allowed = true;
            }
        } else {
            // Check non-root-level
            $doktype = $this->pageInfo($page_uid, 'doktype');
            $allowedTableList = $GLOBALS['PAGES_TYPES'][$doktype]['allowedTables'] ?? $GLOBALS['PAGES_TYPES']['default']['allowedTables'];
            $allowedArray = GeneralUtility::trimExplode(',', $allowedTableList, true);
            // If all tables or the table is listed as an allowed type, return TRUE
            if (str_contains($allowedTableList, '*') || in_array($checkTable, $allowedArray, true)) {
                $allowed = true;
            }
        }
        return $allowed;
    }

    /**
     * Checks if record can be selected based on given permission criteria
     *
     * @param string $table Record table name
     * @param int $id Record UID
     * @param int $perms Permission restrictions to observe: integer that will be bitwise AND'ed.
     * @return bool Returns TRUE if the record given by $table, $id and $perms can be selected
     *
     * @throws \RuntimeException
     * @internal should only be used from within DataHandler
     */
    public function doesRecordExist($table, $id, int $perms)
    {
        return $this->recordInfoWithPermissionCheck($table, $id, $perms, 'uid, pid') !== false;
    }

    /**
     * Looks up a page based on permissions.
     *
     * @param int $id Page id
     * @param int $perms Permission integer
     * @param array $columns Columns to select
     * @return bool|array
     * @internal
     * @see doesRecordExist()
     */
    protected function doesRecordExist_pageLookUp($id, $perms, $columns = ['uid'])
    {
        $permission = new Permission($perms);
        $cacheId = md5('doesRecordExist_pageLookUp_' . $id . '_' . $perms . '_' . implode(
            '_',
            $columns
        ) . '_' . (string)$this->admin);

        // If result is cached, return it
        $cachedResult = $this->runtimeCache->get($cacheId);
        if (!empty($cachedResult)) {
            return $cachedResult;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());
        $queryBuilder
            ->select(...$columns)
            ->from('pages')
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
            ));
        if (!$permission->nothingIsGranted() && !$this->admin) {
            $queryBuilder->andWhere($this->BE_USER->getPagePermsClause($perms));
        }
        if (!$this->admin && $GLOBALS['TCA']['pages']['ctrl']['editlock'] &&
            ($permission->editPagePermissionIsGranted() || $permission->deletePagePermissionIsGranted() || $permission->editContentPermissionIsGranted())
        ) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq(
                $GLOBALS['TCA']['pages']['ctrl']['editlock'],
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            ));
        }

        $row = $queryBuilder->executeQuery()->fetchAssociative();
        $this->runtimeCache->set($cacheId, $row);

        return $row;
    }

    /**
     * Checks if a whole branch of pages exists
     *
     * Tests the branch under $pid like doesRecordExist(), but it doesn't test the page with $pid as uid - use doesRecordExist() for this purpose.
     * If $recurse is set, the function will follow subpages. This MUST be set, if we need the id-list for deleting pages or else we get an incomplete list
     *
     * @param string $inList List of page uids, this is added to and returned in the end
     * @param int $pid Page ID to select subpages from.
     * @param int $perms Perms integer to check each page record for.
     * @param bool $recurse Recursion flag: If set, it will go out through the branch.
     * @return string|int List of page IDs in branch, if there are subpages, empty string if there are none or -1 if no permission
     * @internal should only be used from within DataHandler
     */
    public function doesBranchExist($inList, $pid, $perms, $recurse)
    {
        $pid = (int)$pid;
        $perms = (int)$perms;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());
        $result = $queryBuilder
            ->select('uid', 'perms_userid', 'perms_groupid', 'perms_user', 'perms_group', 'perms_everybody')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)))
            ->orderBy('sorting')
            ->executeQuery();
        while ($row = $result->fetchAssociative()) {
            // IF admin, then it's OK
            if ($this->admin || $this->BE_USER->doesUserHaveAccess($row, $perms)) {
                $inList .= $row['uid'] . ',';
                if ($recurse) {
                    // Follow the subpages recursively...
                    $inList = $this->doesBranchExist($inList, $row['uid'], $perms, $recurse);
                    if ($inList === -1) {
                        return -1;
                    }
                }
            } else {
                // No permissions
                return -1;
            }
        }
        return $inList;
    }

    /**
     * Checks if the $table is readOnly
     *
     * @param string $table Table name
     * @return bool TRUE, if readonly
     * @internal should only be used from within DataHandler
     */
    public function tableReadOnly($table)
    {
        // Returns TRUE if table is readonly
        return (bool)($GLOBALS['TCA'][$table]['ctrl']['readOnly'] ?? false);
    }

    /**
     * Checks if the $table is only editable by admin-users
     *
     * @param string $table Table name
     * @return bool TRUE, if readonly
     * @internal should only be used from within DataHandler
     */
    public function tableAdminOnly($table)
    {
        // Returns TRUE if table is admin-only
        return !empty($GLOBALS['TCA'][$table]['ctrl']['adminOnly']);
    }

    /**
     * Checks if page $id is a uid in the rootline of page id $destinationId
     * Used when moving a page
     *
     * @param int $destinationId Destination Page ID to test
     * @param int $id Page ID to test for presence inside Destination
     * @return bool Returns FALSE if ID is inside destination (including equal to)
     * @internal should only be used from within DataHandler
     */
    public function destNotInsideSelf($destinationId, $id)
    {
        $loopCheck = 100;
        $destinationId = (int)$destinationId;
        $id = (int)$id;
        if ($destinationId === $id) {
            return false;
        }
        while ($destinationId !== 0 && $loopCheck > 0) {
            $loopCheck--;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());
            $result = $queryBuilder
                ->select('pid', 'uid', 't3ver_oid', 't3ver_wsid')
                ->from('pages')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($destinationId, \PDO::PARAM_INT)))
                ->executeQuery();
            if ($row = $result->fetchAssociative()) {
                // Ensure that the moved location is used as the PID value
                BackendUtility::workspaceOL('pages', $row, $this->BE_USER->workspace);
                if ($row['pid'] == $id) {
                    return false;
                }
                $destinationId = (int)$row['pid'];
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Generate an array of fields to be excluded from editing for the user. Based on "exclude"-field in TCA and a look up in non_exclude_fields
     * Will also generate this list for admin-users so they must be check for before calling the function
     *
     * @return array Array of [table]-[field] pairs to exclude from editing.
     * @internal should only be used from within DataHandler
     */
    public function getExcludeListArray()
    {
        $list = [];
        if (isset($this->BE_USER->groupData['non_exclude_fields'])) {
            $nonExcludeFieldsArray = array_flip(GeneralUtility::trimExplode(',', $this->BE_USER->groupData['non_exclude_fields']));
            foreach ($GLOBALS['TCA'] as $table => $tableConfiguration) {
                if (isset($tableConfiguration['columns'])) {
                    foreach ($tableConfiguration['columns'] as $field => $config) {
                        $isExcludeField = ($config['exclude'] ?? false);
                        $isOnlyVisibleForAdmins = ($GLOBALS['TCA'][$table]['columns'][$field]['displayCond'] ?? '') === 'HIDE_FOR_NON_ADMINS';
                        $editorHasPermissionForThisField = isset($nonExcludeFieldsArray[$table . ':' . $field]);
                        if ($isOnlyVisibleForAdmins || ($isExcludeField && !$editorHasPermissionForThisField)) {
                            $list[] = $table . '-' . $field;
                        }
                    }
                }
            }
        }

        return $list;
    }

    /**
     * Checks if there are records on a page from tables that are not allowed
     *
     * @param int|string $page_uid Page ID
     * @param int $doktype Page doktype
     * @return bool|array Returns a list of the tables that are 'present' on the page but not allowed with the page_uid/doktype
     * @internal should only be used from within DataHandler
     */
    public function doesPageHaveUnallowedTables($page_uid, $doktype)
    {
        $page_uid = (int)$page_uid;
        if (!$page_uid) {
            // Not a number. Probably a new page
            return false;
        }
        $allowedTableList = $GLOBALS['PAGES_TYPES'][$doktype]['allowedTables'] ?? $GLOBALS['PAGES_TYPES']['default']['allowedTables'];
        // If all tables are allowed, return early
        if (str_contains($allowedTableList, '*')) {
            return false;
        }
        $allowedArray = GeneralUtility::trimExplode(',', $allowedTableList, true);
        $tableList = [];
        $allTableNames = $this->compileAdminTables();
        foreach ($allTableNames as $table) {
            // If the table is not in the allowed list, check if there are records...
            if (in_array($table, $allowedArray, true)) {
                continue;
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $count = $queryBuilder
                ->count('uid')
                ->from($table)
                ->where($queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($page_uid, \PDO::PARAM_INT)
                ))
                ->executeQuery()
                ->fetchOne();
            if ($count) {
                $tableList[] = $table;
            }
        }
        return implode(',', $tableList);
    }

    /*****************************
     *
     * Information lookup
     *
     *****************************/
    /**
     * Returns the value of the $field from page $id
     * NOTICE; the function caches the result for faster delivery next time. You can use this function repeatedly without performance loss since it doesn't look up the same record twice!
     *
     * @param int $id Page uid
     * @param string $field Field name for which to return value
     * @return string Value of the field. Result is cached in $this->pageCache[$id][$field] and returned from there next time!
     * @internal should only be used from within DataHandler
     */
    public function pageInfo($id, $field)
    {
        if (!isset($this->pageCache[$id])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll();
            $row = $queryBuilder
                ->select('*')
                ->from('pages')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)))
                ->executeQuery()
                ->fetchAssociative();
            if ($row) {
                $this->pageCache[$id] = $row;
            }
        }
        return $this->pageCache[$id][$field];
    }

    /**
     * Returns the row of a record given by $table and $id and $fieldList (list of fields, may be '*')
     * NOTICE: No check for deleted or access!
     *
     * @param string $table Table name
     * @param int $id UID of the record from $table
     * @param string $fieldList Field list for the SELECT query, eg. "*" or "uid,pid,...
     * @return array|null Returns the selected record on success, otherwise NULL.
     * @internal should only be used from within DataHandler
     */
    public function recordInfo($table, $id, $fieldList)
    {
        // Skip, if searching for NEW records or there's no TCA table definition
        if ((int)$id === 0 || !isset($GLOBALS['TCA'][$table])) {
            return null;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select(...GeneralUtility::trimExplode(',', $fieldList))
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        return $result ?: null;
    }

    /**
     * Checks if record exists with and without permission check and returns that row
     *
     * @param string $table Record table name
     * @param int $id Record UID
     * @param int $perms Permission restrictions to observe: An integer that will be bitwise AND'ed.
     * @param string $fieldList - fields - default is '*'
     * @throws \RuntimeException
     * @return array<string,mixed>|false Row if exists and accessible, false otherwise
     */
    protected function recordInfoWithPermissionCheck(string $table, int $id, int $perms, string $fieldList = '*')
    {
        if ($this->bypassAccessCheckForRecords) {
            $columns = GeneralUtility::trimExplode(',', $fieldList, true);

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();

            $record = $queryBuilder->select(...$columns)
                ->from($table)
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)))
                ->executeQuery()
                ->fetchAssociative();

            return $record ?: false;
        }
        if (!$perms) {
            throw new \RuntimeException('Internal ERROR: no permissions to check for non-admin user', 1270853920);
        }
        // For all tables: Check if record exists:
        $isWebMountRestrictionIgnored = BackendUtility::isWebMountRestrictionIgnored($table);
        if (is_array($GLOBALS['TCA'][$table]) && $id > 0 && ($this->admin || $isWebMountRestrictionIgnored || $this->isRecordInWebMount($table, $id))) {
            $columns = GeneralUtility::trimExplode(',', $fieldList, true);
            if ($table !== 'pages') {
                // Find record without checking page
                // @todo: This should probably check for editlock
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());
                $output = $queryBuilder
                    ->select(...$columns)
                    ->from($table)
                    ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)))
                    ->executeQuery()
                    ->fetchAssociative();
                // If record found, check page as well:
                if (is_array($output)) {
                    // Looking up the page for record:
                    $pageRec = $this->doesRecordExist_pageLookUp($output['pid'], $perms);
                    // Return TRUE if either a page was found OR if the PID is zero AND the user is ADMIN (in which case the record is at root-level):
                    $isRootLevelRestrictionIgnored = BackendUtility::isRootLevelRestrictionIgnored($table);
                    if (is_array($pageRec) || !$output['pid'] && ($this->admin || $isRootLevelRestrictionIgnored)) {
                        return $output;
                    }
                }
                return false;
            }
            return $this->doesRecordExist_pageLookUp($id, $perms, $columns);
        }
        return false;
    }

    /**
     * Returns an array with record properties, like header and pid
     * No check for deleted or access is done!
     * For versionized records, pid is resolved to its live versions pid.
     * Used for logging
     *
     * @param string $table Table name
     * @param int $id Uid of record
     * @param bool $noWSOL If set, no workspace overlay is performed
     * @return array Properties of record
     * @internal should only be used from within DataHandler
     */
    public function getRecordProperties($table, $id, $noWSOL = false)
    {
        $row = $table === 'pages' && !$id ? ['title' => '[root-level]', 'uid' => 0, 'pid' => 0] : $this->recordInfo($table, $id, '*');
        if (!$noWSOL) {
            BackendUtility::workspaceOL($table, $row);
        }
        return $this->getRecordPropertiesFromRow($table, $row);
    }

    /**
     * Returns an array with record properties, like header and pid, based on the row
     *
     * @param string $table Table name
     * @param array $row Input row
     * @return array|null Output array
     * @internal should only be used from within DataHandler
     */
    public function getRecordPropertiesFromRow($table, $row)
    {
        if ($GLOBALS['TCA'][$table]) {
            $liveUid = ($row['t3ver_oid'] ?? null) ? ($row['t3ver_oid'] ?? null) : ($row['uid'] ?? null);
            return [
                'header' => BackendUtility::getRecordTitle($table, $row),
                'pid' => $row['pid'] ?? null,
                'event_pid' => $this->eventPid($table, (int)$liveUid, $row['pid'] ?? null),
                't3ver_state' => BackendUtility::isTableWorkspaceEnabled($table) ? ($row['t3ver_state'] ?? '') : '',
            ];
        }
        return null;
    }

    /**
     * @param string $table
     * @param int $uid
     * @param int $pid
     * @return int
     * @internal should only be used from within DataHandler
     */
    public function eventPid($table, $uid, $pid)
    {
        return $table === 'pages' ? $uid : $pid;
    }

    /*********************************************
     *
     * Storing data to Database Layer
     *
     ********************************************/
    /**
     * Update database record
     * Does not check permissions but expects them to be verified on beforehand
     *
     * @param string $table Record table name
     * @param int $id Record uid
     * @param array $fieldArray Array of field=>value pairs to insert. FIELDS MUST MATCH the database FIELDS. No check is done.
     * @internal should only be used from within DataHandler
     */
    public function updateDB($table, $id, $fieldArray)
    {
        if (is_array($fieldArray) && is_array($GLOBALS['TCA'][$table]) && (int)$id) {
            // Do NOT update the UID field, ever!
            unset($fieldArray['uid']);
            if (!empty($fieldArray)) {
                $fieldArray = $this->insertUpdateDB_preprocessBasedOnFieldType($table, $fieldArray);

                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

                $types = [];
                $platform = $connection->getDatabasePlatform();
                if ($platform instanceof SQLServerPlatform || $platform instanceof PostgreSQLPlatform) {
                    // mssql and postgres needs to set proper PARAM_LOB and others to update fields.
                    $tableDetails = $connection->createSchemaManager()->listTableDetails($table);
                    foreach ($fieldArray as $columnName => $columnValue) {
                        $types[$columnName] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
                    }
                }

                // Execute the UPDATE query:
                $updateErrorMessage = '';
                try {
                    $connection->update($table, $fieldArray, ['uid' => (int)$id], $types);
                } catch (DBALException $e) {
                    $updateErrorMessage = $e->getPrevious()->getMessage();
                }
                // If succeeds, do...:
                if ($updateErrorMessage === '') {
                    // Update reference index:
                    $this->updateRefIndex($table, $id);
                    // Set History data
                    $historyEntryId = 0;
                    if (isset($this->historyRecords[$table . ':' . $id])) {
                        $historyEntryId = $this->getRecordHistoryStore()->modifyRecord($table, $id, $this->historyRecords[$table . ':' . $id], $this->correlationId);
                    }
                    if ($this->enableLogging) {
                        if ($this->checkStoredRecords) {
                            $newRow = $this->checkStoredRecord($table, $id, $fieldArray, SystemLogDatabaseAction::UPDATE) ?? [];
                        } else {
                            $newRow = $fieldArray;
                            $newRow['uid'] = $id;
                        }
                        // Set log entry:
                        $propArr = $this->getRecordPropertiesFromRow($table, $newRow);
                        $isOfflineVersion = (bool)($newRow['t3ver_oid'] ?? 0);
                        $this->log($table, $id, SystemLogDatabaseAction::UPDATE, $propArr['pid'], SystemLogErrorClassification::MESSAGE, 'Record \'%s\' (%s) was updated.' . ($isOfflineVersion ? ' (Offline version).' : ' (Online).'), 10, [$propArr['header'], $table . ':' . $id, 'history' => $historyEntryId], $propArr['event_pid']);
                    }
                    // Clear cache for relevant pages:
                    $this->registerRecordIdForPageCacheClearing($table, $id);
                    // Unset the pageCache for the id if table was page.
                    if ($table === 'pages') {
                        unset($this->pageCache[$id]);
                    }
                } else {
                    $this->log($table, $id, SystemLogDatabaseAction::UPDATE, 0, SystemLogErrorClassification::SYSTEM_ERROR, 'SQL error: \'%s\' (%s)', 12, [$updateErrorMessage, $table . ':' . $id]);
                }
            }
        }
    }

    /**
     * Insert into database
     * Does not check permissions but expects them to be verified on beforehand
     *
     * @param string $table Record table name
     * @param string $id "NEW...." uid string
     * @param array $fieldArray Array of field=>value pairs to insert. FIELDS MUST MATCH the database FIELDS. No check is done. "pid" must point to the destination of the record!
     * @param bool $newVersion Set to TRUE if new version is created.
     * @param int $suggestedUid Suggested UID value for the inserted record. See the array $this->suggestedInsertUids; Admin-only feature
     * @param bool $dontSetNewIdIndex If TRUE, the ->substNEWwithIDs array is not updated. Only useful in very rare circumstances!
     * @return int|null Returns ID on success.
     * @internal should only be used from within DataHandler
     */
    public function insertDB($table, $id, $fieldArray, $newVersion = false, $suggestedUid = 0, $dontSetNewIdIndex = false)
    {
        if (is_array($fieldArray) && is_array($GLOBALS['TCA'][$table]) && isset($fieldArray['pid'])) {
            // Do NOT insert the UID field, ever!
            unset($fieldArray['uid']);
            if (!empty($fieldArray)) {
                // Check for "suggestedUid".
                // This feature is used by the import functionality to force a new record to have a certain UID value.
                // This is only recommended for use when the destination server is a passive mirror of another server.
                // As a security measure this feature is available only for Admin Users (for now)
                $suggestedUid = (int)$suggestedUid;
                if ($this->BE_USER->isAdmin() && $suggestedUid && $this->suggestedInsertUids[$table . ':' . $suggestedUid]) {
                    // When the value of ->suggestedInsertUids[...] is "DELETE" it will try to remove the previous record
                    if ($this->suggestedInsertUids[$table . ':' . $suggestedUid] === 'DELETE') {
                        $this->hardDeleteSingleRecord($table, (int)$suggestedUid);
                    }
                    $fieldArray['uid'] = $suggestedUid;
                }
                $fieldArray = $this->insertUpdateDB_preprocessBasedOnFieldType($table, $fieldArray);
                $typeArray = [];
                if (!empty($GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField'])
                    && array_key_exists($GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField'], $fieldArray)
                ) {
                    $typeArray[$GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']] = Connection::PARAM_LOB;
                }
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
                $insertErrorMessage = '';
                try {
                    // Execute the INSERT query:
                    $connection->insert(
                        $table,
                        $fieldArray,
                        $typeArray
                    );
                } catch (DBALException $e) {
                    $insertErrorMessage = $e->getPrevious()->getMessage();
                }
                // If succees, do...:
                if ($insertErrorMessage === '') {
                    // Set mapping for NEW... -> real uid:
                    // the NEW_id now holds the 'NEW....' -id
                    $NEW_id = $id;
                    $id = $this->postProcessDatabaseInsert($connection, $table, $suggestedUid);

                    if (!$dontSetNewIdIndex) {
                        $this->substNEWwithIDs[$NEW_id] = $id;
                        $this->substNEWwithIDs_table[$NEW_id] = $table;
                    }
                    $newRow = [];
                    if ($this->enableLogging) {
                        // Checking the record is properly saved if configured
                        if ($this->checkStoredRecords) {
                            $newRow = $this->checkStoredRecord($table, $id, $fieldArray, SystemLogDatabaseAction::INSERT) ?? [];
                        } else {
                            $newRow = $fieldArray;
                            $newRow['uid'] = $id;
                        }
                    }
                    // Update reference index:
                    $this->updateRefIndex($table, $id);

                    // Store in history
                    $this->getRecordHistoryStore()->addRecord($table, $id, $newRow, $this->correlationId);

                    if ($newVersion) {
                        if ($this->enableLogging) {
                            $propArr = $this->getRecordPropertiesFromRow($table, $newRow);
                            $this->log($table, $id, SystemLogDatabaseAction::INSERT, 0, SystemLogErrorClassification::MESSAGE, 'New version created of table \'%s\', uid \'%s\'. UID of new version is \'%s\'', 10, [$table, $fieldArray['t3ver_oid'], $id], $propArr['event_pid'], $NEW_id);
                        }
                    } else {
                        if ($this->enableLogging) {
                            $propArr = $this->getRecordPropertiesFromRow($table, $newRow);
                            $page_propArr = $this->getRecordProperties('pages', $propArr['pid']);
                            $this->log($table, $id, SystemLogDatabaseAction::INSERT, 0, SystemLogErrorClassification::MESSAGE, 'Record \'%s\' (%s) was inserted on page \'%s\' (%s)', 10, [$propArr['header'], $table . ':' . $id, $page_propArr['header'], $newRow['pid']], $newRow['pid'], $NEW_id);
                        }
                        // Clear cache for relevant pages:
                        $this->registerRecordIdForPageCacheClearing($table, $id);
                    }
                    return $id;
                }
                $this->log($table, 0, SystemLogDatabaseAction::INSERT, 0, SystemLogErrorClassification::SYSTEM_ERROR, 'SQL error: \'%s\' (%s)', 12, [$insertErrorMessage, $table . ':' . $id]);
            }
        }
        return null;
    }

    /**
     * Checking stored record to see if the written values are properly updated.
     *
     * @param string $table Record table name
     * @param int $id Record uid
     * @param array $fieldArray Array of field=>value pairs to insert/update
     * @param int $action Action, for logging only.
     * @return array|null Selected row
     * @see insertDB()
     * @see updateDB()
     * @internal should only be used from within DataHandler
     */
    public function checkStoredRecord($table, $id, $fieldArray, $action)
    {
        $id = (int)$id;
        if (is_array($GLOBALS['TCA'][$table]) && $id) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();

            $row = $queryBuilder
                ->select('*')
                ->from($table)
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)))
                ->executeQuery()
                ->fetchAssociative();

            if (!empty($row)) {
                // Traverse array of values that was inserted into the database and compare with the actually stored value:
                $errors = [];
                foreach ($fieldArray as $key => $value) {
                    if (!$this->checkStoredRecords_loose || $value || $row[$key]) {
                        if (is_float($row[$key])) {
                            // if the database returns the value as double, compare it as double
                            if ((double)$value !== (double)$row[$key]) {
                                $errors[] = $key;
                            }
                        } else {
                            $dbType = $GLOBALS['TCA'][$table]['columns'][$key]['config']['dbType'] ?? false;
                            if ($dbType === 'datetime' || $dbType === 'time') {
                                $row[$key] = $this->normalizeTimeFormat($table, $row[$key], $dbType);
                            }
                            if ((string)$value !== (string)$row[$key]) {
                                // The is_numeric check catches cases where we want to store a float/double value
                                // and database returns the field as a string with the least required amount of
                                // significant digits, i.e. "0.00" being saved and "0" being read back.
                                if (is_numeric($value) && is_numeric($row[$key])) {
                                    if ((double)$value === (double)$row[$key]) {
                                        continue;
                                    }
                                }
                                $errors[] = $key;
                            }
                        }
                    }
                }
                // Set log message if there were fields with unmatching values:
                if (!empty($errors)) {
                    $this->log($table, $id, $action, 0, SystemLogErrorClassification::USER_ERROR, 'These fields of record {id} in table "{table}" have not been saved correctly: {fields}! The values might have changed due to type casting of the database.', -1, ['id' => $id, 'table' => $table, 'fields' => implode(', ', $errors)]);
                }
                // Return selected rows:
                return $row;
            }
        }
        return null;
    }

    /**
     * Setting sys_history record, based on content previously set in $this->historyRecords[$table . ':' . $id] (by compareFieldArrayWithCurrentAndUnset())
     *
     * This functionality is now moved into the RecordHistoryStore and can be used instead.
     *
     * @param string $table Table name
     * @param int $id Record ID
     * @internal should only be used from within DataHandler
     */
    public function setHistory($table, $id)
    {
        if (isset($this->historyRecords[$table . ':' . $id])) {
            $this->getRecordHistoryStore()->modifyRecord(
                $table,
                $id,
                $this->historyRecords[$table . ':' . $id],
                $this->correlationId
            );
        }
    }

    /**
     * @return RecordHistoryStore
     */
    protected function getRecordHistoryStore(): RecordHistoryStore
    {
        return GeneralUtility::makeInstance(
            RecordHistoryStore::class,
            RecordHistoryStore::USER_BACKEND,
            $this->BE_USER->user['uid'],
            (int)$this->BE_USER->getOriginalUserIdWhenInSwitchUserMode(),
            $GLOBALS['EXEC_TIME'],
            $this->BE_USER->workspace
        );
    }

    /**
     * Register a table/uid combination in current user workspace for reference updating.
     * Should be called on almost any update to a record which could affect references inside the record.
     *
     * @param string $table Table name
     * @param int $uid Record UID
     * @param int $workspace Workspace the record lives in
     * @internal should only be used from within DataHandler
     */
    public function updateRefIndex($table, $uid, int $workspace = null): void
    {
        if ($workspace === null) {
            $workspace = (int)$this->BE_USER->workspace;
        }
        $this->referenceIndexUpdater->registerForUpdate((string)$table, (int)$uid, $workspace);
    }

    /**
     * Delete rows from sys_refindex a table / uid combination is involved in:
     * Either on left side (tablename + recuid) OR right side (ref_table + ref_uid).
     * Useful in scenarios like workspace-discard where parents or children are hard deleted: The
     * expensive updateRefIndex() does not need to be called since we can just drop straight ahead.
     *
     * @param string $table Table name, used as tablename and ref_table
     * @param int $uid Record uid, used as recuid and ref_uid
     * @param int $workspace Workspace the record lives in
     * @internal should only be used from within DataHandler
     */
    public function registerReferenceIndexRowsForDrop(string $table, int $uid, int $workspace): void
    {
        $this->referenceIndexUpdater->registerForDrop($table, $uid, $workspace);
    }

    /**
     * Helper method to access referenceIndexUpdater->registerUpdateForReferencesToItem()
     * from within workspace DataHandlerHook.
     *
     * @internal Exists only for workspace DataHandlerHook. May vanish any time.
     */
    public function registerReferenceIndexUpdateForReferencesToItem(string $table, int $uid, int $workspace, int $targetWorkspace = null): void
    {
        $this->referenceIndexUpdater->registerUpdateForReferencesToItem($table, $uid, $workspace, $targetWorkspace);
    }

    /*********************************************
     *
     * Misc functions
     *
     ********************************************/
    /**
     * Returning sorting number for tables with a "sortby" column
     * Using when new records are created and existing records are moved around.
     *
     * The strategy is:
     *  - if no record exists: set interval as sorting number
     *  - if inserted before an element: put in the middle of the existing elements
     *  - if inserted behind the last element: add interval to last sorting number
     *  - if collision: move all subsequent records by 2 * interval, insert new record with collision + interval
     *
     * How to calculate the maximum possible inserts for the worst case of adding all records to the top,
     * such that the sorting number stays within INT_MAX
     *
     * i = interval (currently 256)
     * c = number of inserts until collision
     * s = max sorting number to reach (INT_MAX - 32bit)
     * n = number of records (~83 million)
     *
     * c = 2 * g
     * g = log2(i) / 2 + 1
     * n = g * s / i - g + 1
     *
     * The algorithm can be tuned by adjusting the interval value.
     * Higher value means less collisions, but also less inserts are possible to stay within INT_MAX.
     *
     * @param string $table Table name
     * @param int $uid Uid of record to find sorting number for. May be zero in case of new.
     * @param int $pid Positioning PID, either >=0 (pointing to page in which case we find sorting number for first record in page) or <0 (pointing to record in which case to find next sorting number after this record)
     * @return int|array|bool|null Returns integer if PID is >=0, otherwise an array with PID and sorting number. Possibly FALSE in case of error.
     * @internal should only be used from within DataHandler
     */
    public function getSortNumber($table, $uid, $pid)
    {
        $sortColumn = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? '';
        if (!$sortColumn) {
            return null;
        }

        $considerWorkspaces = BackendUtility::isTableWorkspaceEnabled($table);
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
        $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());

        $queryBuilder
            ->select($sortColumn, 'pid', 'uid')
            ->from($table);
        if ($considerWorkspaces) {
            $queryBuilder->addSelect('t3ver_state');
        }

        // find and return the sorting value for the first record on that pid
        if ($pid >= 0) {
            // Fetches the first record (lowest sorting) under this pid
            $queryBuilder
                ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)));

            if ($considerWorkspaces) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq('t3ver_oid', 0),
                        $queryBuilder->expr()->eq('t3ver_state', VersionState::MOVE_POINTER)
                    )
                );
            }
            $row = $queryBuilder
                ->orderBy($sortColumn, 'ASC')
                ->addOrderBy('uid', 'ASC')
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            if (!empty($row)) {
                // The top record was the record itself, so we return its current sorting value
                if ($row['uid'] == $uid) {
                    return $row[$sortColumn];
                }
                // If the record sorting value < 1 we must resort all the records under this pid
                if ($row[$sortColumn] < 1) {
                    $this->increaseSortingOfFollowingRecords($table, (int)$pid);
                    // Lowest sorting value after full resorting is $sortIntervals
                    return $this->sortIntervals;
                }
                // Sorting number between current top element and zero
                return floor($row[$sortColumn] / 2);
            }
            // No records, so we choose the default value as sorting-number
            return $this->sortIntervals;
        }

        // Find and return first possible sorting value AFTER record with given uid ($pid)
        // Fetches the record which is supposed to be the prev record
        $row = $queryBuilder
                ->where($queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(abs($pid), \PDO::PARAM_INT)
                ))
                ->executeQuery()
                ->fetchAssociative();

        // There is a previous record
        if (!empty($row)) {
            $row += [
                't3ver_state' => 0,
                'uid' => 0,
            ];
            // Look if the record UID happens to be a versioned record. If so, find its live version.
            // If this is already a moved record in workspace, this is not needed
            if ((int)$row['t3ver_state'] !== VersionState::MOVE_POINTER && $lookForLiveVersion = BackendUtility::getLiveVersionOfRecord($table, $row['uid'], $sortColumn . ',pid,uid')) {
                $row = $lookForLiveVersion;
            } elseif ($considerWorkspaces && $this->BE_USER->workspace > 0) {
                // In case the previous record is moved in the workspace, we need to fetch the information from this specific record
                $versionedRecord = BackendUtility::getWorkspaceVersionOfRecord($this->BE_USER->workspace, $table, $row['uid'], $sortColumn . ',pid,uid,t3ver_state');
                if (is_array($versionedRecord) && (int)$versionedRecord['t3ver_state'] === VersionState::MOVE_POINTER) {
                    $row = $versionedRecord;
                }
            }
            // If the record should be inserted after itself, keep the current sorting information:
            if ((int)$row['uid'] === (int)$uid) {
                $sortNumber = $row[$sortColumn];
            } else {
                $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
                $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());

                $queryBuilder
                        ->select($sortColumn, 'pid', 'uid')
                        ->from($table)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'pid',
                                $queryBuilder->createNamedParameter($row['pid'], \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->gte(
                                $sortColumn,
                                $queryBuilder->createNamedParameter($row[$sortColumn], \PDO::PARAM_INT)
                            )
                        )
                        ->orderBy($sortColumn, 'ASC')
                        ->addOrderBy('uid', 'DESC')
                        ->setMaxResults(2);

                if ($considerWorkspaces) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->eq('t3ver_oid', 0),
                            $queryBuilder->expr()->eq('t3ver_state', VersionState::MOVE_POINTER)
                        )
                    );
                }

                $subResults = $queryBuilder
                    ->executeQuery()
                    ->fetchAllAssociative();
                // Fetches the next record in order to calculate the in-between sortNumber
                // There was a record afterwards
                if (count($subResults) === 2) {
                    // There was a record afterwards, fetch that
                    $subrow = array_pop($subResults);
                    // The sortNumber is found in between these values
                    $sortNumber = $row[$sortColumn] + floor(($subrow[$sortColumn] - $row[$sortColumn]) / 2);
                    // The sortNumber happened NOT to be between the two surrounding numbers, so we'll have to resort the list
                    if ($sortNumber <= $row[$sortColumn] || $sortNumber >= $subrow[$sortColumn]) {
                        $this->increaseSortingOfFollowingRecords($table, (int)$row['pid'], (int)$row[$sortColumn]);
                        $sortNumber = $row[$sortColumn] + $this->sortIntervals;
                    }
                } else {
                    // If after the last record in the list, we just add the sortInterval to the last sortvalue
                    $sortNumber = $row[$sortColumn] + $this->sortIntervals;
                }
            }
            return ['pid' => $row['pid'], 'sortNumber' => $sortNumber];
        }
        if ($this->enableLogging) {
            $propArr = $this->getRecordProperties($table, $uid);
            // OK, don't insert $propArr['event_pid'] here...
            $this->log($table, $uid, SystemLogDatabaseAction::MOVE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to move record \'%s\' (%s) to after a non-existing record (uid=%s)', 1, [$propArr['header'], $table . ':' . $uid, abs($pid)], $propArr['pid']);
        }
        // There MUST be a previous record or else this cannot work
        return false;
    }

    /**
     * Increases sorting field value of all records with sorting higher than $sortingValue
     *
     * Used internally by getSortNumber() to "make space" in sorting values when inserting new record
     *
     * @param string $table Table name
     * @param int $pid Page Uid in which to resort records
     * @param int $sortingValue All sorting numbers larger than this number will be shifted
     * @see getSortNumber()
     */
    protected function increaseSortingOfFollowingRecords(string $table, int $pid, int $sortingValue = null): void
    {
        $sortBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? '';
        if ($sortBy) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);

            $queryBuilder
                ->update($table)
                ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)))
                ->set($sortBy, $queryBuilder->quoteIdentifier($sortBy) . ' + ' . $this->sortIntervals . ' + ' . $this->sortIntervals, false);
            if ($sortingValue !== null) {
                $queryBuilder->andWhere($queryBuilder->expr()->gt($sortBy, $sortingValue));
            }
            if (BackendUtility::isTableWorkspaceEnabled($table)) {
                $queryBuilder
                    ->andWhere(
                        $queryBuilder->expr()->eq('t3ver_oid', 0)
                    );
            }

            $deleteColumn = $GLOBALS['TCA'][$table]['ctrl']['delete'] ?? '';
            if ($deleteColumn) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($deleteColumn, 0));
            }

            $queryBuilder->executeStatement();
        }
    }

    /**
     * Returning uid of "previous" localized record, if any, for tables with a "sortby" column.
     * Used when records are localized, so that localized records are sorted in the
     * same order as the source language records.
     *
     * The uid of the returned record is later used to create the localized record "after"
     * (higher sorting value) than the one the uid is returned of.
     *
     * There are basically two scenarios:
     * * The localized record is to be placed as the first record of the target pid/language
     *   combination. In this case, there is no "before" record in this language. The method
     *   returns input $uid, saying "insert the localized record with a higher sorting value
     *   than the record the localization is created from".
     * * There is a localized record "before" (lower sorting value) in the target pid/language
     *   combination. For instance because source language element 2 is being translated and
     *   source language element 1 has already been translated. In this case, the uid of the
     *   'element 1' is returned, saying "insert the localized record with a higher sorting
     *   value than the "before" record in this language.
     *
     * The algorithm first fetches the record of given input uid. It then looks if there is a
     * record with a lower sorting value for this pid/language combination. If no, input uid
     * is returned ("place with higher sorting than source language record"). If yes, it looks
     * if there is a localization of that source record in the target language and return the
     * uid of that target language record ("place with higher sorting that this traget language
     * record"). When dealing with table tt_content, colpos is also taken into account.
     *
     * @param string $table Table name
     * @param int $uid Uid of source language record
     * @param int $pid Pid of source language record
     * @param int $targetLanguage Target language id
     * @return int uid of record after which the localized record should be inserted
     */
    protected function getPreviousLocalizedRecordUid($table, $uid, $pid, $targetLanguage)
    {
        $previousLocalizedRecordUid = $uid;
        $sortColumn = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? '';
        if (!$sortColumn) {
            return $previousLocalizedRecordUid;
        }

        // Typically l10n_parent
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        // Typically sys_language_uid
        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];

        $select = [$sortColumn, $languageField, $transOrigPointerField, 'pid', 'uid'];
        // For content elements, we also need the colPos
        if ($table === 'tt_content') {
            $select[] = 'colPos';
        }

        // Get the sort value and some other details of the source language record
        $row = BackendUtility::getRecord($table, $uid, implode(',', $select));
        if (!is_array($row)) {
            // This if may be obsolete ... didn't the callee already check if the source record exists?
            return $previousLocalizedRecordUid;
        }

        // Try to find a "before" record in source language
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());
        $queryBuilder
            ->select(...$select)
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $languageField,
                    $queryBuilder->createNamedParameter($row[$languageField], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->lt(
                    $sortColumn,
                    $queryBuilder->createNamedParameter($row[$sortColumn], \PDO::PARAM_INT)
                )
            )
            ->orderBy($sortColumn, 'DESC')
            ->addOrderBy('uid', 'DESC')
            ->setMaxResults(1);
        if ($table === 'tt_content') {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter($row['colPos'], \PDO::PARAM_INT)
                )
            );
        }
        // If there is a "before" record in source language, see if it is localized to target language.
        // If so, return uid of target language record.
        if ($previousRow = $queryBuilder->executeQuery()->fetchAssociative()) {
            $previousLocalizedRecord = BackendUtility::getRecordLocalization($table, $previousRow['uid'], $targetLanguage, 'pid=' . (int)$pid);
            if (isset($previousLocalizedRecord[0]) && is_array($previousLocalizedRecord[0])) {
                $previousLocalizedRecordUid = $previousLocalizedRecord[0]['uid'];
            }
        }

        return $previousLocalizedRecordUid;
    }

    /**
     * Returns a fieldArray with default values. Values will be picked up from the TCA array looking at the config key "default" for each column. If values are set in ->defaultValues they will overrule though.
     * Used for new records and during copy operations for defaults
     *
     * @param string $table Table name for which to set default values.
     * @return array Array with default values.
     * @internal should only be used from within DataHandler
     */
    public function newFieldArray($table)
    {
        $fieldArray = [];
        if (is_array($GLOBALS['TCA'][$table]['columns'])) {
            foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $content) {
                if (isset($this->defaultValues[$table][$field])) {
                    $fieldArray[$field] = $this->defaultValues[$table][$field];
                } elseif (isset($content['config']['default'])) {
                    $fieldArray[$field] = $content['config']['default'];
                }
            }
        }
        return $fieldArray;
    }

    /**
     * If a "languageField" is specified for $table this function will add a possible value to the incoming array if none is found in there already.
     *
     * @param string $table Table name
     * @param array $incomingFieldArray Incoming array (passed by reference)
     * @param int $pageId the PID of the table (where the record should be inserted)
     * @internal should only be used from within DataHandler
     */
    protected function addDefaultPermittedLanguageIfNotSet(string $table, &$incomingFieldArray, int $pageId): void
    {
        $languageFieldName = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '';
        if (empty($languageFieldName)) {
            return;
        }
        if (isset($incomingFieldArray[$languageFieldName])) {
            return;
        }
        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
            // Checking languages
            foreach ($site->getAvailableLanguages($this->BE_USER, false, $pageId) as $languageId => $language) {
                $incomingFieldArray[$languageFieldName] = $languageId;
                break;
            }
        } catch (SiteNotFoundException $e) {
            // No site found, do not set a default language if nothing was set explicitly
            return;
        }
    }

    /**
     * Find a site language by the given language ID for a specific page, and check for all available sites
     * if the page ID is "0".
     *
     * Note: Currently, the first language matching the given id is used, while
     *       there might be more languages with the same id in additional sites.
     *
     * @param int $pageId
     * @param int $languageId
     * @return SiteLanguage|null
     */
    protected function getSiteLanguageForPage(int $pageId, int $languageId): ?SiteLanguage
    {
        try {
            // Try to fetch the site language from the pages' associated site
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
            return $site->getLanguageById($languageId);
        } catch (SiteNotFoundException | \InvalidArgumentException $e) {
            // In case no site language could be found, we might deal with the root node,
            // we therefore try to fetch the site language from all available sites.
            // NOTE: This has side effects, in case the SAME ID is used for different languages in different sites!
            $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
            foreach ($sites as $site) {
                try {
                    return $site->getLanguageById($languageId);
                } catch (\InvalidArgumentException $e) {
                    // language not found in site, continue
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Returns the $data array from $table overridden in the fields defined in ->overrideValues.
     *
     * @param string $table Table name
     * @param array $data Data array with fields from table. These will be overlaid with values in $this->overrideValues[$table]
     * @return array Data array, processed.
     * @internal should only be used from within DataHandler
     */
    public function overrideFieldArray($table, $data)
    {
        if (isset($this->overrideValues[$table]) && is_array($this->overrideValues[$table])) {
            $data = array_merge($data, $this->overrideValues[$table]);
        }
        return $data;
    }

    /**
     * Compares the incoming field array with the current record and unsets all fields which are the same.
     * Used for existing records being updated
     *
     * @param string $table Record table name
     * @param int $id Record uid
     * @param array $fieldArray Array of field=>value pairs intended to be inserted into the database. All keys with values matching exactly the current value will be unset!
     * @return array Returns $fieldArray. If the returned array is empty, then the record should not be updated!
     * @internal should only be used from within DataHandler
     */
    public function compareFieldArrayWithCurrentAndUnset($table, $id, $fieldArray)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $currentRecord = $queryBuilder->select('*')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        // If the current record exists (which it should...), begin comparison:
        if (is_array($currentRecord)) {
            $tableDetails = $connection->createSchemaManager()->listTableDetails($table);
            $columnRecordTypes = [];
            foreach ($currentRecord as $columnName => $_) {
                $columnRecordTypes[$columnName] = '';
                $type = $tableDetails->getColumn($columnName)->getType();
                if ($type instanceof IntegerType) {
                    $columnRecordTypes[$columnName] = 'int';
                }
            }
            // Unset the fields which are similar:
            foreach ($fieldArray as $col => $val) {
                $fieldConfiguration = $GLOBALS['TCA'][$table]['columns'][$col]['config'] ?? [];
                $isNullField = (!empty($fieldConfiguration['eval']) && GeneralUtility::inList($fieldConfiguration['eval'], 'null'));

                // Unset fields if stored and submitted values are equal - except the current field holds MM relations.
                // In general this avoids to store superfluous data which also will be visualized in the editing history.
                if (empty($fieldConfiguration['MM']) && $this->isSubmittedValueEqualToStoredValue($val, $currentRecord[$col], $columnRecordTypes[$col], $isNullField)) {
                    unset($fieldArray[$col]);
                } else {
                    if (!isset($this->mmHistoryRecords[$table . ':' . $id]['oldRecord'][$col])) {
                        $this->historyRecords[$table . ':' . $id]['oldRecord'][$col] = $currentRecord[$col];
                    } elseif ($this->mmHistoryRecords[$table . ':' . $id]['oldRecord'][$col] != $this->mmHistoryRecords[$table . ':' . $id]['newRecord'][$col]) {
                        $this->historyRecords[$table . ':' . $id]['oldRecord'][$col] = $this->mmHistoryRecords[$table . ':' . $id]['oldRecord'][$col];
                    }
                    if (!isset($this->mmHistoryRecords[$table . ':' . $id]['newRecord'][$col])) {
                        $this->historyRecords[$table . ':' . $id]['newRecord'][$col] = $fieldArray[$col];
                    } elseif ($this->mmHistoryRecords[$table . ':' . $id]['newRecord'][$col] != $this->mmHistoryRecords[$table . ':' . $id]['oldRecord'][$col]) {
                        $this->historyRecords[$table . ':' . $id]['newRecord'][$col] = $this->mmHistoryRecords[$table . ':' . $id]['newRecord'][$col];
                    }
                }
            }
        } else {
            // If the current record does not exist this is an error anyways and we just return an empty array here.
            $fieldArray = [];
        }
        return $fieldArray;
    }

    /**
     * Determines whether submitted values and stored values are equal.
     * This prevents from adding superfluous field changes which would be shown in the record history as well.
     * For NULL fields (see accordant TCA definition 'eval' = 'null'), a special handling is required since
     * (!strcmp(NULL, '')) would be a false-positive.
     *
     * @param mixed $submittedValue Value that has submitted (e.g. from a backend form)
     * @param mixed $storedValue Value that is currently stored in the database
     * @param string $storedType SQL type of the stored value column (see mysql_field_type(), e.g 'int', 'string',  ...)
     * @param bool $allowNull Whether NULL values are allowed by accordant TCA definition ('eval' = 'null')
     * @return bool Whether both values are considered to be equal
     */
    protected function isSubmittedValueEqualToStoredValue($submittedValue, $storedValue, $storedType, $allowNull = false)
    {
        // No NULL values are allowed, this is the regular behaviour.
        // Thus, check whether strings are the same or whether integer values are empty ("0" or "").
        if (!$allowNull) {
            $result = (string)$submittedValue === (string)$storedValue || $storedType === 'int' && (int)$storedValue === (int)$submittedValue;
        // Null values are allowed, but currently there's a real (not NULL) value.
        // Thus, ensure no NULL value was submitted and fallback to the regular behaviour.
        } elseif ($storedValue !== null) {
            $result = (
                $submittedValue !== null
                && $this->isSubmittedValueEqualToStoredValue($submittedValue, $storedValue, $storedType, false)
            );
        // Null values are allowed, and currently there's a NULL value.
        // Thus, check whether a NULL value was submitted.
        } else {
            $result = ($submittedValue === null);
        }

        return $result;
    }

    /**
     * Disables the delete clause for fetching records.
     * In general only undeleted records will be used. If the delete
     * clause is disabled, also deleted records are taken into account.
     */
    public function disableDeleteClause()
    {
        $this->disableDeleteClause = true;
    }

    /**
     * Returns delete-clause for the $table
     *
     * @param string $table Table name
     * @return string Delete clause
     * @internal should only be used from within DataHandler
     */
    public function deleteClause($table)
    {
        // Returns the proper delete-clause if any for a table from TCA
        if (!$this->disableDeleteClause && $GLOBALS['TCA'][$table]['ctrl']['delete']) {
            return ' AND ' . $table . '.' . $GLOBALS['TCA'][$table]['ctrl']['delete'] . '=0';
        }
        return '';
    }

    /**
     * Add delete restriction if not disabled
     *
     * @param QueryRestrictionContainerInterface $restrictions
     */
    protected function addDeleteRestriction(QueryRestrictionContainerInterface $restrictions)
    {
        if (!$this->disableDeleteClause) {
            $restrictions->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
    }

    /**
     * Gets UID of parent record. If record is deleted it will be looked up in
     * an array built before the record was deleted
     *
     * @param string $table Table where record lives/lived
     * @param int $uid Record UID
     * @return int[] Parent UIDs
     */
    protected function getOriginalParentOfRecord($table, $uid)
    {
        if (isset(self::$recordPidsForDeletedRecords[$table][$uid])) {
            return self::$recordPidsForDeletedRecords[$table][$uid];
        }
        [$parentUid] = BackendUtility::getTSCpid($table, $uid, '');
        return [$parentUid];
    }

    /**
     * Extract entries from TSconfig for a specific table. This will merge specific and default configuration together.
     *
     * @param string $table Table name
     * @param array $TSconfig TSconfig for page
     * @return array TSconfig merged
     * @internal should only be used from within DataHandler
     */
    public function getTableEntries($table, $TSconfig)
    {
        $tA = is_array($TSconfig['table.'][$table . '.'] ?? false) ? $TSconfig['table.'][$table . '.'] : [];
        $dA = is_array($TSconfig['default.'] ?? false) ? $TSconfig['default.'] : [];
        ArrayUtility::mergeRecursiveWithOverrule($dA, $tA);
        return $dA;
    }

    /**
     * Returns the pid of a record from $table with $uid
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @return int|false PID value (unless the record did not exist in which case FALSE is returned)
     * @internal should only be used from within DataHandler
     */
    public function getPID($table, $uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll();
        $queryBuilder->select('pid')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)));
        if ($row = $queryBuilder->executeQuery()->fetchAssociative()) {
            return $row['pid'];
        }
        return false;
    }

    /**
     * Executing dbAnalysisStore
     * This will save MM relations for new records but is executed after records are created because we need to know the ID of them
     * @internal should only be used from within DataHandler
     */
    public function dbAnalysisStoreExec()
    {
        foreach ($this->dbAnalysisStore as $action) {
            $idIsInteger = MathUtility::canBeInterpretedAsInteger($action[2]);
            // If NEW id is not found in substitution array (due to errors), continue.
            if (!$idIsInteger && !isset($this->substNEWwithIDs[$action[2]])) {
                continue;
            }
            $id = BackendUtility::wsMapId($action[4], $idIsInteger ? $action[2] : $this->substNEWwithIDs[$action[2]]);
            if ($id) {
                $action[0]->writeMM($action[1], $id, $action[3]);
            }
        }
    }

    /**
     * Returns array, $CPtable, of pages under the $pid going down to $counter levels.
     * Selecting ONLY pages which the user has read-access to!
     *
     * @param array $CPtable Accumulation of page uid=>pid pairs in branch of $pid
     * @param int $pid Page ID for which to find subpages
     * @param int $counter Number of levels to go down.
     * @param int $rootID ID of root point for new copied branch: The idea seems to be that a copy is not made of the already new page!
     * @return array Return array.
     * @internal should only be used from within DataHandler
     */
    public function int_pageTreeInfo($CPtable, $pid, $counter, $rootID)
    {
        if ($counter) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $restrictions = $queryBuilder->getRestrictions()->removeAll();
            $this->addDeleteRestriction($restrictions);
            $queryBuilder
                ->select('uid')
                ->from('pages')
                ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)))
                ->orderBy('sorting', 'DESC');
            if (!$this->admin) {
                $queryBuilder->andWhere($this->BE_USER->getPagePermsClause(Permission::PAGE_SHOW));
            }
            if ((int)$this->BE_USER->workspace === 0) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
                );
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->in(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter([0, $this->BE_USER->workspace], Connection::PARAM_INT_ARRAY)
                ));
            }
            $result = $queryBuilder->executeQuery();

            $pages = [];
            while ($row = $result->fetchAssociative()) {
                $pages[$row['uid']] = $row;
            }

            // Resolve placeholders of workspace versions
            if (!empty($pages) && (int)$this->BE_USER->workspace !== 0) {
                $pages = array_reverse(
                    $this->resolveVersionedRecords(
                        'pages',
                        'uid',
                        'sorting',
                        array_keys($pages)
                    ),
                    true
                );
            }

            foreach ($pages as $page) {
                if ($page['uid'] != $rootID) {
                    $CPtable[$page['uid']] = $pid;
                    // If the uid is NOT the rootID of the copyaction and if we are supposed to walk further down
                    if ($counter - 1) {
                        $CPtable = $this->int_pageTreeInfo($CPtable, $page['uid'], $counter - 1, $rootID);
                    }
                }
            }
        }
        return $CPtable;
    }

    /**
     * List of all tables (those administrators has access to = array_keys of $GLOBALS['TCA'])
     *
     * @return array Array of all TCA table names
     * @internal should only be used from within DataHandler
     */
    public function compileAdminTables()
    {
        return array_keys($GLOBALS['TCA']);
    }

    /**
     * Checks if any uniqueInPid eval input fields are in the record and if so, they are re-written to be correct.
     *
     * @param string $table Table name
     * @param int $uid Record UID
     * @internal should only be used from within DataHandler
     */
    public function fixUniqueInPid($table, $uid)
    {
        if (empty($GLOBALS['TCA'][$table])) {
            return;
        }

        $curData = $this->recordInfo($table, $uid, '*');
        $newData = [];
        foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $conf) {
            if ($conf['config']['type'] === 'input' && (string)$curData[$field] !== '') {
                $evalCodesArray = GeneralUtility::trimExplode(',', $conf['config']['eval'] ?? '', true);
                if (in_array('uniqueInPid', $evalCodesArray, true)) {
                    $newV = $this->getUnique($table, $field, $curData[$field], $uid, $curData['pid']);
                    if ((string)$newV !== (string)$curData[$field]) {
                        $newData[$field] = $newV;
                    }
                }
            }
        }
        // IF there are changed fields, then update the database
        if (!empty($newData)) {
            $this->updateDB($table, $uid, $newData);
        }
    }

    /**
     * Checks if any uniqueInSite eval fields are in the record and if so, they are re-written to be correct.
     *
     * @param string $table Table name
     * @param int $uid Record UID
     * @return bool whether the record had to be fixed or not
     */
    protected function fixUniqueInSite(string $table, int $uid): bool
    {
        $curData = $this->recordInfo($table, $uid, '*');
        $workspaceId = $this->BE_USER->workspace;
        $newData = [];
        foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $conf) {
            if ($conf['config']['type'] === 'slug' && (string)$curData[$field] !== '') {
                $evalCodesArray = GeneralUtility::trimExplode(',', $conf['config']['eval'], true);
                if (in_array('uniqueInSite', $evalCodesArray, true)) {
                    $helper = GeneralUtility::makeInstance(SlugHelper::class, $table, $field, $conf['config'], $workspaceId);
                    $state = RecordStateFactory::forName($table)->fromArray($curData);
                    $newValue = $helper->buildSlugForUniqueInSite($curData[$field], $state);
                    if ((string)$newValue !== (string)$curData[$field]) {
                        $newData[$field] = $newValue;
                    }
                }
            }
        }
        // IF there are changed fields, then update the database
        if (!empty($newData)) {
            $this->updateDB($table, $uid, $newData);
            return true;
        }
        return false;
    }

    /**
     * Check if there are subpages that need an adoption as well
     * @param int $pageId
     */
    protected function fixUniqueInSiteForSubpages(int $pageId)
    {
        // Get ALL subpages to update - read-permissions are respected
        $subPages = $this->int_pageTreeInfo([], $pageId, 99, $pageId);
        // Now fix uniqueInSite for subpages
        foreach ($subPages as $thePageUid => $thePagePid) {
            $recordWasModified = $this->fixUniqueInSite('pages', $thePageUid);
            if ($recordWasModified) {
                // @todo: Add logging and history - but how? we don't know the data that was in the system before
            }
        }
    }

    /**
     * When er record is copied you can specify fields from the previous record which should be copied into the new one
     * This function is also called with new elements. But then $update must be set to zero and $newData containing the data array. In that case data in the incoming array is NOT overridden. (250202)
     *
     * @param string $table Table name
     * @param int $uid Record UID
     * @param int $prevUid UID of previous record
     * @param bool $update If set, updates the record
     * @param array $newData Input array. If fields are already specified AND $update is not set, values are not set in output array.
     * @return array Output array (For when the copying operation needs to get the information instead of updating the info)
     * @internal should only be used from within DataHandler
     */
    public function fixCopyAfterDuplFields($table, $uid, $prevUid, $update, $newData = [])
    {
        if ($GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields'] ?? false) {
            $prevData = $this->recordInfo($table, $prevUid, '*');
            $theFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields'], true);
            foreach ($theFields as $field) {
                if ($GLOBALS['TCA'][$table]['columns'][$field] && ($update || !isset($newData[$field]))) {
                    $newData[$field] = $prevData[$field];
                }
            }
            if ($update && !empty($newData)) {
                $this->updateDB($table, $uid, $newData);
            }
        }
        return $newData;
    }

    /**
     * Casts a reference value. In case MM relations or foreign_field
     * references are used. All other configurations, as well as
     * foreign_table(!) could be stored as comma-separated-values
     * as well. Since the system is not able to determine the default
     * value automatically then, the TCA default value is used if
     * it has been defined.
     *
     * @param int|string $value The value to be casted (e.g. '', '0', '1,2,3')
     * @param array $configuration The TCA configuration of the accordant field
     * @return int|string
     */
    protected function castReferenceValue($value, array $configuration)
    {
        if ((string)$value !== '') {
            return $value;
        }

        if (!empty($configuration['MM']) || !empty($configuration['foreign_field'])) {
            return 0;
        }

        if (array_key_exists('default', $configuration)) {
            return $configuration['default'];
        }

        return $value;
    }

    /**
     * Returns TRUE if the TCA/columns field type is a DB reference field
     *
     * @param array $conf Config array for TCA/columns field
     * @return bool TRUE if DB reference field (group/db or select with foreign-table)
     * @internal should only be used from within DataHandler
     */
    public function isReferenceField($conf)
    {
        if (!isset($conf['type'])) {
            return false;
        }

        return ($conf['type'] === 'group' && ($conf['internal_type'] ?? '') !== 'folder')
            || (($conf['type'] === 'select' || $conf['type'] === 'category') && !empty($conf['foreign_table']));
    }

    /**
     * Returns the subtype as a string of an inline field.
     * If it's not an inline field at all, it returns FALSE.
     *
     * @param array $conf Config array for TCA/columns field
     * @return string|bool string Inline subtype (field|mm|list), boolean: FALSE
     * @internal should only be used from within DataHandler
     */
    public function getInlineFieldType($conf)
    {
        if (empty($conf['type']) || $conf['type'] !== 'inline' || empty($conf['foreign_table'])) {
            return false;
        }
        if ($conf['foreign_field'] ?? false) {
            // The reference to the parent is stored in a pointer field in the child record
            return 'field';
        }
        if ($conf['MM'] ?? false) {
            // Regular MM intermediate table is used to store data
            return 'mm';
        }
        // An item list (separated by comma) is stored (like select type is doing)
        return 'list';
    }

    /**
     * Get modified header for a copied record
     *
     * @param string $table Table name
     * @param int $pid PID value in which other records to test might be
     * @param string $field Field name to get header value for.
     * @param string $value Current field value
     * @param int $count Counter (number of recursions)
     * @param string $prevTitle Previous title we checked for (in previous recursion)
     * @return string The field value, possibly appended with a "copy label
     * @internal should only be used from within DataHandler
     */
    public function getCopyHeader($table, $pid, $field, $value, $count, $prevTitle = '')
    {
        // Set title value to check for:
        $checkTitle = $value;
        if ($count > 0) {
            $checkTitle = $value . rtrim(' ' . sprintf($this->prependLabel($table), $count));
        }
        // Do check:
        if ($prevTitle != $checkTitle || $count < 100) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $this->addDeleteRestriction($queryBuilder->getRestrictions()->removeAll());
            $rowCount = $queryBuilder
                ->count('uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($checkTitle, \PDO::PARAM_STR))
                )
                ->executeQuery()
                ->fetchOne();
            if ($rowCount) {
                return $this->getCopyHeader($table, $pid, $field, $value, $count + 1, $checkTitle);
            }
        }
        // Default is to just return the current input title if no other was returned before:
        return $checkTitle;
    }

    /**
     * Return "copy" label for a table. Although the name is "prepend" it actually APPENDs the label (after ...)
     *
     * @param string $table Table name
     * @return string Label to append, containing "%s" for the number
     * @see getCopyHeader()
     * @internal should only be used from within DataHandler
     */
    public function prependLabel($table)
    {
        return $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['prependAtCopy']);
    }

    /**
     * Get the final pid based on $table and $pid ($destPid type... pos/neg)
     *
     * @param string $table Table name
     * @param int $pid "Destination pid" : If the value is >= 0 it's just returned directly (through (int)though) but if the value is <0 then the method looks up the record with the uid equal to abs($pid) (positive number) and returns the PID of that record! The idea is that negative numbers point to the record AFTER WHICH the position is supposed to be!
     * @return int
     * @internal should only be used from within DataHandler
     */
    public function resolvePid($table, $pid)
    {
        $pid = (int)$pid;
        if ($pid < 0) {
            $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $query->getRestrictions()
                ->removeAll();
            $row = $query
                ->select('pid')
                ->from($table)
                ->where($query->expr()->eq('uid', $query->createNamedParameter(abs($pid), \PDO::PARAM_INT)))
                ->executeQuery()
                ->fetchAssociative();
            $pid = (int)$row['pid'];
        }
        return $pid;
    }

    /**
     * Removes the prependAtCopy prefix on values
     *
     * @param string $table Table name
     * @param string $value The value to fix
     * @return string Clean name
     * @internal should only be used from within DataHandler
     */
    public function clearPrefixFromValue($table, $value)
    {
        $regex = '/\s' . sprintf(preg_quote($this->prependLabel($table)), '[0-9]*') . '$/';
        return @preg_replace($regex, '', $value);
    }

    /**
     * Check if there are records from tables on the pages to be deleted which the current user is not allowed to
     *
     * @param int[] $pageIds IDs of pages which should be checked
     * @return string[]|null Return null, if permission granted, otherwise an array with the tables that are not allowed to be deleted
     * @see canDeletePage()
     */
    protected function checkForRecordsFromDisallowedTables(array $pageIds): ?array
    {
        if ($this->admin) {
            return null;
        }

        $disallowedTables = [];
        if (!empty($pageIds)) {
            $tableNames = $this->compileAdminTables();
            foreach ($tableNames as $table) {
                $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $query->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $count = $query->count('uid')
                    ->from($table)
                    ->where($query->expr()->in(
                        'pid',
                        $query->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                    ))
                    ->executeQuery()
                    ->fetchOne();
                if ($count && ($this->tableReadOnly($table) || !$this->checkModifyAccessList($table))) {
                    $disallowedTables[] = $table;
                }
            }
        }
        return !empty($disallowedTables) ? $disallowedTables : null;
    }

    /**
     * Determine if a record was copied or if a record is the result of a copy action.
     *
     * @param string $table The tablename of the record
     * @param int $uid The uid of the record
     * @return bool Returns TRUE if the record is copied or is the result of a copy action
     * @internal should only be used from within DataHandler
     */
    public function isRecordCopied($table, $uid)
    {
        // If the record was copied:
        if (isset($this->copyMappingArray[$table][$uid])) {
            return true;
        }
        if (isset($this->copyMappingArray[$table]) && in_array($uid, array_values($this->copyMappingArray[$table]))) {
            return true;
        }
        return false;
    }

    /******************************
     *
     * Clearing cache
     *
     ******************************/

    /**
     * Clearing the cache based on a page being updated
     * If the $table is 'pages' then cache is cleared for all pages on the same level (and subsequent?)
     * Else just clear the cache for the parent page of the record.
     *
     * @param string $table Table name of record that was just updated.
     * @param int $uid UID of updated / inserted record
     * @param int $pid REAL PID of page of a deleted/moved record to get TSconfig in ClearCache.
     * @internal This method is not meant to be called directly but only from the core itself or from hooks
     */
    public function registerRecordIdForPageCacheClearing($table, $uid, $pid = null)
    {
        if (!is_array(static::$recordsToClearCacheFor[$table] ?? false)) {
            static::$recordsToClearCacheFor[$table] = [];
        }
        static::$recordsToClearCacheFor[$table][] = (int)$uid;
        if ($pid !== null) {
            if (!isset(static::$recordPidsForDeletedRecords[$table]) || !is_array(static::$recordPidsForDeletedRecords[$table])) {
                static::$recordPidsForDeletedRecords[$table] = [];
            }
            static::$recordPidsForDeletedRecords[$table][$uid][] = (int)$pid;
        }
    }

    /**
     * Do the actual clear cache
     */
    protected function processClearCacheQueue()
    {
        $tagsToClear = [];
        $clearCacheCommands = [];

        foreach (static::$recordsToClearCacheFor as $table => $uids) {
            foreach (array_unique($uids) as $uid) {
                if (!isset($GLOBALS['TCA'][$table]) || $uid <= 0) {
                    return;
                }
                // For move commands we may get more then 1 parent.
                $pageUids = $this->getOriginalParentOfRecord($table, $uid);
                foreach ($pageUids as $originalParent) {
                    [$tagsToClearFromPrepare, $clearCacheCommandsFromPrepare]
                        = $this->prepareCacheFlush($table, $uid, $originalParent);
                    $tagsToClear = array_merge($tagsToClear, $tagsToClearFromPrepare);
                    $clearCacheCommands = array_merge($clearCacheCommands, $clearCacheCommandsFromPrepare);
                }
            }
        }

        $cacheManager = $this->getCacheManager();
        $cacheManager->flushCachesInGroupByTags('pages', array_keys($tagsToClear));

        // Filter duplicate cache commands from cacheQueue
        $clearCacheCommands = array_unique($clearCacheCommands);
        // Execute collected clear cache commands from page TSConfig
        foreach ($clearCacheCommands as $command) {
            $this->clear_cacheCmd($command);
        }

        // Reset the cache clearing array
        static::$recordsToClearCacheFor = [];

        // Reset the original pid array
        static::$recordPidsForDeletedRecords = [];
    }

    /**
     * Prepare the cache clearing
     *
     * @param string $table Table name of record that needs to be cleared
     * @param int $uid UID of record for which the cache needs to be cleared
     * @param int $pid Original pid of the page of the record which the cache needs to be cleared
     * @return array Array with tagsToClear and clearCacheCommands
     * @internal This function is internal only it may be changed/removed also in minor version numbers.
     */
    protected function prepareCacheFlush($table, $uid, $pid)
    {
        $tagsToClear = [];
        $clearCacheCommands = [];
        $pageUid = 0;
        $clearCacheEnabled = true;
        // Get Page TSconfig relevant:
        $TSConfig = BackendUtility::getPagesTSconfig($pid)['TCEMAIN.'] ?? [];

        if (!empty($TSConfig['clearCache_disable'])) {
            $clearCacheEnabled = false;
        }

        if ($clearCacheEnabled && $this->BE_USER->workspace !== 0 && BackendUtility::isTableWorkspaceEnabled($table)) {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $count = $queryBuilder
                ->count('uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('t3ver_oid', 0)
                )
                ->executeQuery()
                ->fetchOne();
            if ($count === 0) {
                $clearCacheEnabled = false;
            }
        }

        if ($clearCacheEnabled) {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            // If table is "pages":
            $pageIdsThatNeedCacheFlush = [];
            if ($table === 'pages') {
                // Find out if the record is a get the original page
                $pageUid = $this->getDefaultLanguagePageId($uid);

                // Builds list of pages on the SAME level as this page (siblings)
                $queryBuilder = $connectionPool->getQueryBuilderForTable('pages');
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $siblings = $queryBuilder
                    ->select('A.pid AS pid', 'B.uid AS uid')
                    ->from('pages', 'A')
                    ->from('pages', 'B')
                    ->where(
                        $queryBuilder->expr()->eq('A.uid', $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)),
                        $queryBuilder->expr()->eq('B.pid', $queryBuilder->quoteIdentifier('A.pid')),
                        $queryBuilder->expr()->gte('A.pid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
                    )
                    ->executeQuery();

                $parentPageId = 0;
                while ($row_tmp = $siblings->fetchAssociative()) {
                    $pageIdsThatNeedCacheFlush[] = (int)$row_tmp['uid'];
                    $parentPageId = (int)$row_tmp['pid'];
                    // Add children as well:
                    if ($TSConfig['clearCache_pageSiblingChildren'] ?? false) {
                        $siblingChildrenQuery = $connectionPool->getQueryBuilderForTable('pages');
                        $siblingChildrenQuery->getRestrictions()
                            ->removeAll()
                            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                        $siblingChildren = $siblingChildrenQuery
                            ->select('uid')
                            ->from('pages')
                            ->where($siblingChildrenQuery->expr()->eq(
                                'pid',
                                $siblingChildrenQuery->createNamedParameter($row_tmp['uid'], \PDO::PARAM_INT)
                            ))
                            ->executeQuery();
                        while ($row_tmp2 = $siblingChildren->fetchAssociative()) {
                            $pageIdsThatNeedCacheFlush[] = (int)$row_tmp2['uid'];
                        }
                    }
                }
                // Finally, add the parent page as well when clearing a specific page
                if ($parentPageId > 0) {
                    $pageIdsThatNeedCacheFlush[] = $parentPageId;
                }
                // Add grand-parent as well if configured
                if ($TSConfig['clearCache_pageGrandParent'] ?? false) {
                    $parentQuery = $connectionPool->getQueryBuilderForTable('pages');
                    $parentQuery->getRestrictions()
                        ->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $row_tmp = $parentQuery
                        ->select('pid')
                        ->from('pages')
                        ->where($parentQuery->expr()->eq(
                            'uid',
                            $parentQuery->createNamedParameter($parentPageId, \PDO::PARAM_INT)
                        ))
                        ->executeQuery()
                        ->fetchAssociative();
                    if (!empty($row_tmp)) {
                        $pageIdsThatNeedCacheFlush[] = (int)$row_tmp['pid'];
                    }
                }
            } else {
                // For other tables than "pages", delete cache for the records "parent page".
                $pageIdsThatNeedCacheFlush[] = $pageUid = (int)$this->getPID($table, $uid);
                // Add the parent page as well
                if ($TSConfig['clearCache_pageGrandParent'] ?? false) {
                    $parentQuery = $connectionPool->getQueryBuilderForTable('pages');
                    $parentQuery->getRestrictions()
                        ->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $parentPageRecord = $parentQuery
                        ->select('pid')
                        ->from('pages')
                        ->where($parentQuery->expr()->eq(
                            'uid',
                            $parentQuery->createNamedParameter($pageUid, \PDO::PARAM_INT)
                        ))
                        ->executeQuery()
                        ->fetchAssociative();
                    if (!empty($parentPageRecord)) {
                        $pageIdsThatNeedCacheFlush[] = (int)$parentPageRecord['pid'];
                    }
                }
            }
            // Call pre-processing function for clearing of cache for page ids:
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'] ?? [] as $funcName) {
                $_params = ['pageIdArray' => &$pageIdsThatNeedCacheFlush, 'table' => $table, 'uid' => $uid, 'functionID' => 'clear_cache()'];
                // Returns the array of ids to clear, FALSE if nothing should be cleared! Never an empty array!
                GeneralUtility::callUserFunction($funcName, $_params, $this);
            }
            // Delete cache for selected pages:
            foreach ($pageIdsThatNeedCacheFlush as $pageId) {
                $tagsToClear['pageId_' . $pageId] = true;
            }
            // Queue delete cache for current table and record
            $tagsToClear[$table] = true;
            $tagsToClear[$table . '_' . $uid] = true;
        }
        // Clear cache for pages entered in TSconfig:
        if (!empty($TSConfig['clearCacheCmd'])) {
            $commands = GeneralUtility::trimExplode(',', $TSConfig['clearCacheCmd'], true);
            $clearCacheCommands = array_unique($commands);
        }
        // Call post processing function for clear-cache:
        $_params = ['table' => $table, 'uid' => $uid, 'uid_page' => $pageUid, 'TSConfig' => $TSConfig, 'tags' => $tagsToClear, 'clearCacheEnabled' => $clearCacheEnabled];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
        return [
            $tagsToClear,
            $clearCacheCommands,
        ];
    }

    /**
     * Clears the cache based on the command $cacheCmd.
     *
     * $cacheCmd='pages'
     * Clears cache for all pages and page-based caches inside the cache manager.
     * Requires admin-flag to be set for BE_USER.
     *
     * $cacheCmd='all'
     * Clears all cache_tables. This is necessary if templates are updated.
     * Requires admin-flag to be set for BE_USER.
     *
     * The following cache_* are intentionally not cleared by 'all'
     *
     * - imagesizes:	Clearing this table would cause a lot of unneeded
     * Imagemagick calls because the size information has
     * to be fetched again after clearing.
     * - all caches inside the cache manager that are inside the group "system"
     * - they are only needed to build up the core system and templates.
     *   If the group of system caches needs to be deleted explicitly, use
     *   flushCachesInGroup('system') of CacheManager directly.
     *
     * $cacheCmd=[integer]
     * Clears cache for the page pointed to by $cacheCmd (an integer).
     *
     * $cacheCmd='cacheTag:[string]'
     * Flush page and pagesection cache by given tag
     *
     * $cacheCmd='cacheId:[string]'
     * Removes cache identifier from page and page section cache
     *
     * Can call a list of post processing functions as defined in
     * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']
     * (numeric array with values being the function references, called by
     * GeneralUtility::callUserFunction()).
     *
     *
     * @param string $cacheCmd The cache command, see above description
     */
    public function clear_cacheCmd($cacheCmd)
    {
        if (is_object($this->BE_USER)) {
            $this->BE_USER->writeLog(SystemLogType::CACHE, SystemLogCacheAction::CLEAR, SystemLogErrorClassification::MESSAGE, 0, 'User %s has cleared the cache (cacheCmd=%s)', [$this->BE_USER->user['username'], $cacheCmd]);
        }
        $userTsConfig = $this->BE_USER->getTSConfig();
        switch (strtolower($cacheCmd)) {
            case 'pages':
                if ($this->admin || ($userTsConfig['options.']['clearCache.']['pages'] ?? false)) {
                    $this->getCacheManager()->flushCachesInGroup('pages');
                }
                break;
            case 'all':
                // allow to clear all caches if the TS config option is enabled or the option is not explicitly
                // disabled for admins (which could clear all caches by default). The latter option is useful
                // for big production sites where it should be possible to restrict the cache clearing for some admins.
                if (($userTsConfig['options.']['clearCache.']['all'] ?? false)
                    || ($this->admin && (bool)($userTsConfig['options.']['clearCache.']['all'] ?? true))
                ) {
                    $this->getCacheManager()->flushCaches();
                    GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getConnectionForTable('cache_treelist')
                        ->truncate('cache_treelist');

                    // Delete Opcode Cache
                    GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive();
                }
                break;
        }

        $tagsToFlush = [];
        // Clear cache for a page ID!
        if (MathUtility::canBeInterpretedAsInteger($cacheCmd)) {
            $list_cache = [$cacheCmd];
            // Call pre-processing function for clearing of cache for page ids:
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'] ?? [] as $funcName) {
                $_params = ['pageIdArray' => &$list_cache, 'cacheCmd' => $cacheCmd, 'functionID' => 'clear_cacheCmd()'];
                // Returns the array of ids to clear, FALSE if nothing should be cleared! Never an empty array!
                GeneralUtility::callUserFunction($funcName, $_params, $this);
            }
            // Delete cache for selected pages:
            if (is_array($list_cache)) {
                foreach ($list_cache as $pageId) {
                    $tagsToFlush[] = 'pageId_' . (int)$pageId;
                }
            }
        }
        // flush cache by tag
        if (str_starts_with(strtolower($cacheCmd), 'cachetag:')) {
            $cacheTag = substr($cacheCmd, 9);
            $tagsToFlush[] = $cacheTag;
        }
        // process caching framework operations
        if (!empty($tagsToFlush)) {
            $this->getCacheManager()->flushCachesInGroupByTags('pages', $tagsToFlush);
        }

        // Call post processing function for clear-cache:
        $_params = ['cacheCmd' => strtolower($cacheCmd), 'tags' => $tagsToFlush];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
    }

    /*****************************
     *
     * Logging
     *
     *****************************/
    /**
     * Logging actions from DataHandler
     *
     * @param string $table Table name the log entry is concerned with. Blank if NA
     * @param int $recuid Record UID. Zero if NA
     * @param int $action Action number: 0=No category, 1=new record, 2=update record, 3= delete record, 4= move record, 5= Check/evaluate
     * @param int|string $recpid Normally 0 (zero). If set, it indicates that this log-entry is used to notify the backend of a record which is moved to another location
     * @param int $error The severity: 0 = message, 1 = error, 2 = System Error, 3 = security notice (admin), 4 warning
     * @param string $details Default error message in english
     * @param int $details_nr This number is unique for every combination of $type and $action. This is the error-message number, which can later be used to translate error messages. 0 if not categorized, -1 if temporary
     * @param array $data Array with special information that may go into $details by '%s' marks / sprintf() when the log is shown
     * @param int $event_pid The page_uid (pid) where the event occurred. Used to select log-content for specific pages.
     * @param string $NEWid NEW id for new records
     * @return int Log entry UID (0 if no log entry was written or logging is disabled)
     * @see \TYPO3\CMS\Core\SysLog\Action\Database for all available values of argument $action
     * @see \TYPO3\CMS\Core\SysLog\Error for all available values of argument $error
     * @internal should only be used from within TYPO3 Core
     */
    public function log($table, $recuid, $action, $recpid, $error, $details, $details_nr = -1, $data = [], $event_pid = -1, $NEWid = '')
    {
        if (!$this->enableLogging) {
            return 0;
        }
        // Type value for DataHandler
        if (!$this->storeLogMessages) {
            $details = '';
        }
        if ($error > 0) {
            $detailMessage = $details;
            if (is_array($data)) {
                $detailMessage = $this->formatLogDetails($detailMessage, $data);
            }
            $this->errorLog[] = '[' . SystemLogType::DB . '.' . $action . '.' . $details_nr . ']: ' . $detailMessage;
        }
        return $this->BE_USER->writelog(SystemLogType::DB, $action, $error, $details_nr, $details, $data, $table, $recuid, $recpid, $event_pid, $NEWid);
    }

    /**
     * Print log error messages from the operations of this script instance
     * @internal should only be used from within TYPO3 Core
     */
    public function printLogErrorMessages()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('*')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(SystemLogType::DB, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq(
                    'userid',
                    $queryBuilder->createNamedParameter($this->BE_USER->user['uid'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tstamp',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->neq('error', $queryBuilder->createNamedParameter(SystemLogErrorClassification::MESSAGE, \PDO::PARAM_INT))
            )
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $msg = $this->formatLogDetails($row['details'], $row['log_data'] ?? '');
            $msg = $row['error'] . ': ' . $msg;
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $msg, '', $row['error'] === SystemLogErrorClassification::WARNING ? FlashMessage::WARNING : FlashMessage::ERROR, true);
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
    }

    /*****************************
     *
     * Internal (do not use outside Core!)
     *
     *****************************/

    /**
     * Find out if the record is a localization. If so, get the uid of the default language page.
     * Always returns the uid of the workspace live record: No explicit workspace overlay is applied.
     *
     * @param int $pageId Page UID, can be the default page record, or a page translation record ID
     * @return int UID of the default page record in live workspace
     */
    protected function getDefaultLanguagePageId(int $pageId): int
    {
        $localizationParentFieldName = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];
        $row = $this->recordInfo('pages', $pageId, $localizationParentFieldName);
        $localizationParent = (int)($row[$localizationParentFieldName] ?? 0);
        if ($localizationParent > 0) {
            return $localizationParent;
        }
        return $pageId;
    }

    /**
     * Preprocesses field array based on field type. Some fields must be adjusted
     * before going to database. This is done on the copy of the field array because
     * original values are used in remap action later.
     *
     * @param string $table	Table name
     * @param array $fieldArray	Field array to check
     * @return array Updated field array
     * @internal should only be used from within TYPO3 Core
     */
    public function insertUpdateDB_preprocessBasedOnFieldType($table, $fieldArray)
    {
        $result = $fieldArray;
        foreach ($fieldArray as $field => $value) {
            if (!MathUtility::canBeInterpretedAsInteger($value)
                && isset($GLOBALS['TCA'][$table]['columns'][$field]['config']['type'])
                && $GLOBALS['TCA'][$table]['columns'][$field]['config']['type'] === 'inline'
                && ($GLOBALS['TCA'][$table]['columns'][$field]['config']['foreign_field'] ?? false)
            ) {
                $result[$field] = count(GeneralUtility::trimExplode(',', $value, true));
            }
        }
        return $result;
    }

    /**
     * Determines whether a particular record has been deleted
     * using DataHandler::deleteRecord() in this instance.
     *
     * @param string $tableName
     * @param int $uid
     * @return bool
     * @internal should only be used from within TYPO3 Core
     */
    public function hasDeletedRecord($tableName, $uid)
    {
        return
            !empty($this->deletedRecords[$tableName])
            && in_array($uid, $this->deletedRecords[$tableName])
        ;
    }

    /**
     * Gets the automatically versionized id of a record.
     *
     * @param string $table Name of the table
     * @param int $id Uid of the record
     * @return int|null
     * @internal should only be used from within TYPO3 Core
     */
    public function getAutoVersionId($table, $id): ?int
    {
        $result = null;
        if (isset($this->autoVersionIdMap[$table][$id])) {
            $result = (int)trim($this->autoVersionIdMap[$table][$id]);
        }
        return $result;
    }

    /**
     * Overlays the automatically versionized id of a record.
     *
     * @param string $table Name of the table
     * @param int $id Uid of the record
     * @return int
     */
    protected function overlayAutoVersionId($table, $id)
    {
        $autoVersionId = $this->getAutoVersionId($table, $id);
        if ($autoVersionId !== null) {
            $id = $autoVersionId;
        }
        return $id;
    }

    /**
     * Adds new values to the remapStackChildIds array.
     *
     * @param array $idValues uid values
     */
    protected function addNewValuesToRemapStackChildIds(array $idValues)
    {
        foreach ($idValues as $idValue) {
            if (strpos($idValue, 'NEW') === 0) {
                $this->remapStackChildIds[$idValue] = true;
            }
        }
    }

    /**
     * Resolves versioned records for the current workspace scope.
     * Delete placeholders are substituted and removed.
     *
     * @param string $tableName Name of the table to be processed
     * @param string $fieldNames List of the field names to be fetched
     * @param string $sortingField Name of the sorting field to be used
     * @param array $liveIds Flat array of (live) record ids
     * @return array
     */
    protected function resolveVersionedRecords($tableName, $fieldNames, $sortingField, array $liveIds)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($tableName);
        $sortingStatement = !empty($sortingField)
            ? [$connection->quoteIdentifier($sortingField)]
            : null;
        $resolver = GeneralUtility::makeInstance(
            PlainDataResolver::class,
            $tableName,
            $liveIds,
            $sortingStatement
        );

        $resolver->setWorkspaceId($this->BE_USER->workspace);
        $resolver->setKeepDeletePlaceholder(false);
        $resolver->setKeepMovePlaceholder(false);
        $resolver->setKeepLiveIds(true);
        $recordIds = $resolver->get();

        $records = [];
        foreach ($recordIds as $recordId) {
            $records[$recordId] = BackendUtility::getRecord($tableName, $recordId, $fieldNames);
        }

        return $records;
    }

    /**
     * Evaluates if auto creation of a version of a record is allowed.
     * Auto-creation of version: In offline workspace, test if versioning is
     * enabled and look for workspace version of input record.
     * If there is no versionized record found we will create one and save to that.
     *
     * @param string $table Table of the record
     * @param int $id UID of record
     * @param int|null $recpid PID of record
     * @return bool TRUE if ok.
     * @internal should only be used from within TYPO3 Core
     */
    protected function workspaceAllowAutoCreation(string $table, $id, $recpid): bool
    {
        // No version can be created in live workspace
        if ($this->BE_USER->workspace === 0) {
            return false;
        }
        // No versioning support for this table, so no version can be created
        if (!BackendUtility::isTableWorkspaceEnabled($table)) {
            return false;
        }
        if ($recpid < 0) {
            return false;
        }
        // There must be no existing version of this record in workspace
        if (BackendUtility::getWorkspaceVersionOfRecord($this->BE_USER->workspace, $table, $id, 'uid')) {
            return false;
        }
        return true;
    }

    /**
     * Evaluates if a user is allowed to edit the offline version
     *
     * @param string $table Table of record
     * @param array $record array where fields are at least: pid, t3ver_wsid, t3ver_stage (if versioningWS is set)
     * @return string String error code, telling the failure state. FALSE=All ok
     * @see workspaceCannotEditRecord()
     * @internal this method will be moved to EXT:workspaces
     */
    public function workspaceCannotEditOfflineVersion(string $table, array $record)
    {
        if (!BackendUtility::isTableWorkspaceEnabled($table)) {
            return 'Table does not support versioning.';
        }
        $versionState = new VersionState($record['t3ver_state']);
        if ($versionState->equals(VersionState::NEW_PLACEHOLDER) || (int)$record['t3ver_oid'] > 0) {
            return $this->workspaceCannotEditRecord($table, $record);
        }
        return 'Not an offline version';
    }

    /**
     * Checking if editing of an existing record is allowed in current workspace if that is offline.
     * Rules for editing in offline mode:
     * - record supports versioning and is an offline version from workspace and has the current stage
     * - or record (any) is in a branch where there is a page which is a version from the workspace
     *   and where the stage is not preventing records
     *
     * @param string $table Table of record
     * @param array|int $recData Integer (record uid) or array where fields are at least: pid, t3ver_wsid, t3ver_oid, t3ver_stage (if versioningWS is set)
     * @return string String error code, telling the failure state. FALSE=All ok
     * @internal should only be used from within TYPO3 Core
     */
    public function workspaceCannotEditRecord($table, $recData)
    {
        // Only test if the user is in a workspace
        if ($this->BE_USER->workspace === 0) {
            return false;
        }
        $tableSupportsVersioning = BackendUtility::isTableWorkspaceEnabled($table);
        if (!is_array($recData)) {
            $recData = BackendUtility::getRecord(
                $table,
                $recData,
                'pid' . ($tableSupportsVersioning ? ',t3ver_oid,t3ver_wsid,t3ver_state,t3ver_stage' : '')
            );
        }
        if (is_array($recData)) {
            // We are testing a "version" (identified by having a t3ver_oid): it can be edited provided
            // that workspace matches and versioning is enabled for the table.
            $versionState = new VersionState($recData['t3ver_state'] ?? 0);
            if ($tableSupportsVersioning
                && (
                    $versionState->equals(VersionState::NEW_PLACEHOLDER) || (int)(($recData['t3ver_oid'] ?? 0) > 0)
                )
            ) {
                if ((int)$recData['t3ver_wsid'] !== $this->BE_USER->workspace) {
                    // So does workspace match?
                    return 'Workspace ID of record didn\'t match current workspace';
                }
                // So is the user allowed to "use" the edit stage within the workspace?
                return $this->BE_USER->workspaceCheckStageForCurrent(0)
                    ? false
                    : 'User\'s access level did not allow for editing';
            }
            // Check if we are testing a "live" record
            if ($this->BE_USER->workspaceAllowsLiveEditingInTable($table)) {
                // Live records are OK in the current workspace
                return false;
            }
            // If not offline, output error
            return 'Online record was not in a workspace!';
        }
        return 'No record';
    }

    /**
     * Gets the outer most instance of \TYPO3\CMS\Core\DataHandling\DataHandler
     * Since \TYPO3\CMS\Core\DataHandling\DataHandler can create nested objects of itself,
     * this method helps to determine the first (= outer most) one.
     *
     * @return DataHandler
     */
    protected function getOuterMostInstance()
    {
        if (!isset($this->outerMostInstance)) {
            $stack = array_reverse(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS));
            foreach ($stack as $stackItem) {
                if (isset($stackItem['object']) && $stackItem['object'] instanceof self) {
                    $this->outerMostInstance = $stackItem['object'];
                    break;
                }
            }
        }
        return $this->outerMostInstance;
    }

    /**
     * Determines whether the this object is the outer most instance of itself
     * Since DataHandler can create nested objects of itself,
     * this method helps to determine the first (= outer most) one.
     *
     * @return bool
     */
    public function isOuterMostInstance()
    {
        return $this->getOuterMostInstance() === $this;
    }

    /**
     * Gets an instance of the runtime cache.
     *
     * @return FrontendInterface
     */
    protected function getRuntimeCache()
    {
        return $this->getCacheManager()->getCache('runtime');
    }

    /**
     * Determines nested element calls.
     *
     * @param string $table Name of the table
     * @param int $id Uid of the record
     * @param string $identifier Name of the action to be checked
     * @return bool
     */
    protected function isNestedElementCallRegistered($table, $id, $identifier)
    {
        $nestedElementCalls = (array)$this->runtimeCache->get($this->cachePrefixNestedElementCalls);
        return isset($nestedElementCalls[$identifier][$table][$id]);
    }

    /**
     * Registers nested elements calls.
     * This is used to track nested calls (e.g. for following m:n relations).
     *
     * @param string $table Name of the table
     * @param int $id Uid of the record
     * @param string $identifier Name of the action to be tracked
     */
    protected function registerNestedElementCall($table, $id, $identifier)
    {
        $nestedElementCalls = (array)$this->runtimeCache->get($this->cachePrefixNestedElementCalls);
        $nestedElementCalls[$identifier][$table][$id] = true;
        $this->runtimeCache->set($this->cachePrefixNestedElementCalls, $nestedElementCalls);
    }

    /**
     * Resets the nested element calls.
     */
    protected function resetNestedElementCalls()
    {
        $this->runtimeCache->remove($this->cachePrefixNestedElementCalls);
    }

    /**
     * Determines whether an element was registered to be deleted in the registry.
     *
     * @param string $table Name of the table
     * @param int $id Uid of the record
     * @return bool
     * @see registerElementsToBeDeleted
     * @see resetElementsToBeDeleted
     * @see copyRecord_raw
     * @see versionizeRecord
     */
    protected function isElementToBeDeleted($table, $id)
    {
        $elementsToBeDeleted = (array)$this->runtimeCache->get('core-datahandler-elementsToBeDeleted');
        return isset($elementsToBeDeleted[$table][$id]);
    }

    /**
     * Registers elements to be deleted in the registry.
     *
     * @see process_datamap
     */
    protected function registerElementsToBeDeleted()
    {
        $elementsToBeDeleted = (array)$this->runtimeCache->get('core-datahandler-elementsToBeDeleted');
        $this->runtimeCache->set('core-datahandler-elementsToBeDeleted', array_merge($elementsToBeDeleted, $this->getCommandMapElements('delete')));
    }

    /**
     * Resets the elements to be deleted in the registry.
     *
     * @see process_datamap
     */
    protected function resetElementsToBeDeleted()
    {
        $this->runtimeCache->remove('core-datahandler-elementsToBeDeleted');
    }

    /**
     * Unsets elements (e.g. of the data map) that shall be deleted.
     * This avoids to modify records that will be deleted later on.
     *
     * @param array $elements Elements to be modified
     * @return array
     */
    protected function unsetElementsToBeDeleted(array $elements)
    {
        $elements = ArrayUtility::arrayDiffKeyRecursive($elements, $this->getCommandMapElements('delete'));
        foreach ($elements as $key => $value) {
            if (empty($value)) {
                unset($elements[$key]);
            }
        }
        return $elements;
    }

    /**
     * Gets elements of the command map that match a particular command.
     *
     * @param string $needle The command to be matched
     * @return array
     */
    protected function getCommandMapElements($needle)
    {
        $elements = [];
        foreach ($this->cmdmap as $tableName => $idArray) {
            foreach ($idArray as $id => $commandArray) {
                foreach ($commandArray as $command => $value) {
                    if ($value && $command == $needle) {
                        $elements[$tableName][$id] = true;
                    }
                }
            }
        }
        return $elements;
    }

    /**
     * Controls active elements and sets NULL values if not active.
     * Datamap is modified accordant to submitted control values.
     */
    protected function controlActiveElements()
    {
        if (!empty($this->control['active'])) {
            $this->setNullValues(
                $this->control['active'],
                $this->datamap
            );
        }
    }

    /**
     * Sets NULL values in haystack array.
     * The general behaviour in the user interface is to enable/activate fields.
     * Thus, this method uses NULL as value to be stored if a field is not active.
     *
     * @param array $active hierarchical array with active elements
     * @param array $haystack hierarchical array with haystack to be modified
     */
    protected function setNullValues(array $active, array &$haystack)
    {
        foreach ($active as $key => $value) {
            // Nested data is processes recursively
            if (is_array($value)) {
                $this->setNullValues(
                    $value,
                    $haystack[$key]
                );
            } elseif ($value == 0) {
                // Field has not been activated in the user interface,
                // thus a NULL value shall be stored in the database
                $haystack[$key] = null;
            }
        }
    }

    /**
     * @param CorrelationId $correlationId
     */
    public function setCorrelationId(CorrelationId $correlationId): void
    {
        $this->correlationId = $correlationId;
    }

    /**
     * @return CorrelationId|null
     */
    public function getCorrelationId(): ?CorrelationId
    {
        return $this->correlationId;
    }

    /**
     * Entry point to post process a database insert. Currently bails early unless a UID has been forced
     * and the database platform is not MySQL.
     *
     * @param \TYPO3\CMS\Core\Database\Connection $connection
     * @param string $tableName
     * @param int $suggestedUid
     * @return int
     */
    protected function postProcessDatabaseInsert(Connection $connection, string $tableName, int $suggestedUid): int
    {
        if ($suggestedUid !== 0 && $connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $this->postProcessPostgresqlInsert($connection, $tableName);
            // The last inserted id on postgresql is actually the last value generated by the sequence.
            // On a forced UID insert this might not be the actual value or the sequence might not even
            // have generated a value yet.
            // Return the actual ID we forced on insert as a surrogate.
            return $suggestedUid;
        }
        if ($connection->getDatabasePlatform() instanceof SQLServerPlatform) {
            return $this->postProcessSqlServerInsert($connection, $tableName);
        }
        $id = $connection->lastInsertId($tableName);
        return (int)$id;
    }

    /**
     * Get the last insert ID from sql server
     *
     * - first checks whether doctrine might be able to fetch the ID from the
     * sequence table
     * - if that does not succeed it manually selects the current IDENTITY value
     * from a table
     * - returns 0 if both fail
     *
     * @param \TYPO3\CMS\Core\Database\Connection $connection
     * @param string $tableName
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    protected function postProcessSqlServerInsert(Connection $connection, string $tableName): int
    {
        $id = $connection->lastInsertId($tableName);
        if (!((int)$id > 0)) {
            $table = $connection->quoteIdentifier($tableName);
            $result = $connection->executeQuery('SELECT IDENT_CURRENT(\'' . $table . '\') AS id')->fetchAssociative();
            if (isset($result['id']) && $result['id'] > 0) {
                $id = $result['id'];
            }
        }
        return (int)$id;
    }

    /**
     * PostgreSQL works with sequences for auto increment columns. A sequence is not updated when a value is
     * written to such a column. To avoid clashes when the sequence returns an existing ID this helper will
     * update the sequence to the current max value of the column.
     *
     * @param \TYPO3\CMS\Core\Database\Connection $connection
     * @param string $tableName
     */
    protected function postProcessPostgresqlInsert(Connection $connection, string $tableName)
    {
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder->select('PGT.schemaname', 'S.relname', 'C.attname', 'T.relname AS tablename')
            ->from('pg_class', 'S')
            ->from('pg_depend', 'D')
            ->from('pg_class', 'T')
            ->from('pg_attribute', 'C')
            ->from('pg_tables', 'PGT')
            ->where(
                $queryBuilder->expr()->eq('S.relkind', $queryBuilder->quote('S')),
                $queryBuilder->expr()->eq('S.oid', $queryBuilder->quoteIdentifier('D.objid')),
                $queryBuilder->expr()->eq('D.refobjid', $queryBuilder->quoteIdentifier('T.oid')),
                $queryBuilder->expr()->eq('D.refobjid', $queryBuilder->quoteIdentifier('C.attrelid')),
                $queryBuilder->expr()->eq('D.refobjsubid', $queryBuilder->quoteIdentifier('C.attnum')),
                $queryBuilder->expr()->eq('T.relname', $queryBuilder->quoteIdentifier('PGT.tablename')),
                $queryBuilder->expr()->eq('PGT.tablename', $queryBuilder->quote($tableName))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row !== false) {
            $connection->executeStatement(
                sprintf(
                    'SELECT SETVAL(%s, COALESCE(MAX(%s), 0)+1, FALSE) FROM %s',
                    $connection->quote($row['schemaname'] . '.' . $row['relname']),
                    $connection->quoteIdentifier($row['attname']),
                    $connection->quoteIdentifier($row['schemaname'] . '.' . $row['tablename'])
                )
            );
        }
    }

    /**
     * Return the cache entry identifier for field evals
     *
     * @param string $additionalIdentifier
     * @return string
     */
    protected function getFieldEvalCacheIdentifier($additionalIdentifier)
    {
        return 'core-datahandler-eval-' . md5($additionalIdentifier);
    }

    /**
     * @return RelationHandler
     */
    protected function createRelationHandlerInstance()
    {
        $isWorkspacesLoaded = ExtensionManagementUtility::isLoaded('workspaces');
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->setWorkspaceId($this->BE_USER->workspace);
        $relationHandler->setUseLiveReferenceIds($isWorkspacesLoaded);
        $relationHandler->setUseLiveParentIds($isWorkspacesLoaded);
        $relationHandler->setReferenceIndexUpdater($this->referenceIndexUpdater);
        return $relationHandler;
    }

    /**
     * Create and returns an instance of the CacheManager
     *
     * @return CacheManager
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }

    /**
     * Gets the resourceFactory
     *
     * @return ResourceFactory
     */
    protected function getResourceFactory()
    {
        return GeneralUtility::makeInstance(ResourceFactory::class);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @internal should only be used from within TYPO3 Core
     * @return array
     */
    public function getHistoryRecords(): array
    {
        return $this->historyRecords;
    }
}
