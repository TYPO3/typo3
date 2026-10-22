.. include:: /Includes.rst.txt

.. _important-91095:

============================================================================================
Important: #91095 - Various methods and properties of Backend-related Core APIs now internal
============================================================================================

See :issue:`91095`

Description
===========

Some cornerstones of TYPO3 Core have been kept and migrated since before TYPO3 v4.0. This was when PHP 5 and class visibility was not
even available.

Most classes contain various methods which have been marked as
"public", making it public API for TYPO3, even though their usages
should only be available for TYPO3 Core.

All methods are now marked as "@internal", as official Core API
should be used instead.

:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` class properties and methods: Except for the public methods
that are still available, it is highly recommended to use DataHandler as defined in the official documentation.

The following properties and methods are now marked as internal:

* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkSimilar`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->bypassWorkspaceRestrictions`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->copyWhichTables`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->defaultValues`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->overrideValues`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->data_disableFields`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->callBackObj`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->autoVersionIdMap`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->substNEWwithIDs_table`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->newRelatedIDs`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->copyMappingArray_merged`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->errorLog`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->pagetreeRefreshFieldsFromPages`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->pagetreeNeedsRefresh`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->userid`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->username`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->admin`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->sortIntervals`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->dbAnalysisStore`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->registerDBList`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->registerDBPids`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->copyMappingArray`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->remapStack`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->remapStackRecords`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->updateRefIndexStack`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->callFromImpExp`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_currentRecord`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->setControl()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->setMirror()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->setDefaultsFromUserTS()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->hook_processDatamap_afterDatabaseOperations()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->placeholderShadowing()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getPlaceholderTitleForTableLabel()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->fillInFieldArray()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_SW()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_flexArray2Xml()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_inline()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValueForInline()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_checkMax()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getUnique()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getRecordsWithSameValue()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_text_Eval()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_input_Eval()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_group_select_processDBdata()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_group_select_explodeSelectGroupValue()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_flex_procInData()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_flex_procInData_travDS()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->copyRecord()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->copyPages()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->copySpecificPage()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->copyRecord_raw()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->insertNewCopyVersion()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->copyRecord_flexFormCallBack()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->copyL10nOverlayRecords()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->moveRecord()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->moveRecord_raw()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->moveRecord_procFields()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->moveRecord_procBasedOnFieldType()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->moveL10nOverlayRecords()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->localize()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->deleteAction()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->deleteEl()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->deleteVersionsForRecord()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->undeleteRecord()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->deleteRecord()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->deletePages()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->canDeletePage()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->cannotDeleteRecord()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->isRecordUndeletable()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->deleteRecord_procFields()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->deleteRecord_procBasedOnFieldType()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->deleteL10nOverlayRecords()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->versionizeRecord()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->version_remapMMForVersionSwap()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->version_remapMMForVersionSwap_flexFormCallBack()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->version_remapMMForVersionSwap_execSwap()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->remapListedDBRecords()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->remapListedDBRecords_flexFormCallBack()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->remapListedDBRecords_procDBRefs()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->remapListedDBRecords_procInline()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->processRemapStack()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->addRemapAction()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->addRemapStackRefIndex()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getVersionizedIncomingFieldArray()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkModifyAccessList()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->isRecordInWebMount()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->isInWebMount()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkRecordUpdateAccess()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkRecordInsertAccess()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->isTableAllowedForThisPage()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->doesRecordExist()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->doesBranchExist()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->tableReadOnly()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->tableAdminOnly()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->destNotInsideSelf()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getExcludeListArray()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->doesPageHaveUnallowedTables()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->pageInfo()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->recordInfo()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getRecordProperties()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getRecordPropertiesFromRow()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->eventPid()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->updateDB()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->insertDB()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkStoredRecord()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->setHistory()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->updateRefIndex()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getSortNumber()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->newFieldArray()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->addDefaultPermittedLanguageIfNotSet()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->overrideFieldArray()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->compareFieldArrayWithCurrentAndUnset()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->convNumEntityToByteValue()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->deleteClause()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getTableEntries()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getPID()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->dbAnalysisStoreExec()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->int_pageTreeInfo()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->compileAdminTables()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->fixUniqueInPid()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->fixCopyAfterDuplFields()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->isReferenceField()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getInlineFieldType()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getCopyHeader()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->prependLabel()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->resolvePid()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->clearPrefixFromValue()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->isRecordCopied()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->log()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->newlog()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->printLogErrorMessages()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->insertUpdateDB_preprocessBasedOnFieldType()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->hasDeletedRecord()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getAutoVersionId()`
* :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler->getHistoryRecords()`

The reason for this long list is this: If the DataHandler API is
not called via :php:`start()` and the :php:`process_*`  methods, but rather
the methods would be called directly, certain hooks would be disabled completely, resulting in a huge data inconsistency.

At this point, it is highly recommended to use the official API
of :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` as written in the main documentation.

Various :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility` class methods are called statically, but cannot
guarantee any Context. Short-hand functions for TCA or Database
Queries are now better suited by using the appropriate Database
Restrictions.

* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::purgeComputedPropertiesFromRecord()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::purgeComputedPropertyNames()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::splitTable_Uid()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::openPageTree()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::getGroupNames()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::blindUserNames()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::blindGroupNames()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::getCommonSelectFields()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::helpTextArray()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::helpText()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::softRefParserObj()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::explodeSoftRefParserList()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::selectVersionsOfRecord()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::movePlhOL()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::getLiveVersionIdOfRecord()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::getWorkspaceWhereClause()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::wsMapId()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::getMovePlaceholder()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::getBackendScript()`
* :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::getWorkspaceWhereClause()`


:php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication` a.k.a. :php:`$GLOBALS['BE_USER']` contains a lot of internal calls and properties which are only
used for within TYPO3 Core or to keep state. This should not
be exposed in the future anymore, especially when a more flexible
permission system might get introduced. The affected properties
and methods are:

* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->includeGroupArray`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->errorMsg`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->sessionTimeout`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->firstMainGroup`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->uc_default`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->isMemberOfGroup()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->getPagePermsClause()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->isRTE()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->recordEditAccessInternals()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->workspaceCannotEditRecord()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->workspaceAllowLiveRecordsInPID()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->workspaceAllowsLiveEditingInTable()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->workspaceCreateNewRecord()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->workspaceCanCreateNewRecord()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->workspaceAllowAutoCreation()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->workspaceCheckStageForCurrent()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->workspaceInit()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->checkWorkspace()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->checkWorkspaceCurrent()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->setWorkspace()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->setTemporaryWorkspace()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->setDefaultWorkspace()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->getDefaultWorkspace()`
* :php-short:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->checkLockToIP()`

.. index:: Backend, PHP-API, ext:backend
