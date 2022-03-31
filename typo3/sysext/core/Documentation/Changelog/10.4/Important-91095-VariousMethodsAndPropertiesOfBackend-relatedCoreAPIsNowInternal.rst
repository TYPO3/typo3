.. include:: /Includes.rst.txt

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

:php:`DataHandler` class properties and methods: Except for the public methods
that are still available, it is highly recommended to use DataHandler as defined in the official documentation.

The following properties and methods are now marked as internal:

* :php:`DataHandler->checkSimilar`
* :php:`DataHandler->bypassWorkspaceRestrictions`
* :php:`DataHandler->copyWhichTables`
* :php:`DataHandler->defaultValues`
* :php:`DataHandler->overrideValues`
* :php:`DataHandler->data_disableFields`
* :php:`DataHandler->callBackObj`
* :php:`DataHandler->autoVersionIdMap`
* :php:`DataHandler->substNEWwithIDs_table`
* :php:`DataHandler->newRelatedIDs`
* :php:`DataHandler->copyMappingArray_merged`
* :php:`DataHandler->errorLog`
* :php:`DataHandler->pagetreeRefreshFieldsFromPages`
* :php:`DataHandler->pagetreeNeedsRefresh`
* :php:`DataHandler->userid`
* :php:`DataHandler->username`
* :php:`DataHandler->admin`
* :php:`DataHandler->sortIntervals`
* :php:`DataHandler->dbAnalysisStore`
* :php:`DataHandler->registerDBList`
* :php:`DataHandler->registerDBPids`
* :php:`DataHandler->copyMappingArray`
* :php:`DataHandler->remapStack`
* :php:`DataHandler->remapStackRecords`
* :php:`DataHandler->updateRefIndexStack`
* :php:`DataHandler->callFromImpExp`
* :php:`DataHandler->checkValue_currentRecord`
* :php:`DataHandler->setControl()`
* :php:`DataHandler->setMirror()`
* :php:`DataHandler->setDefaultsFromUserTS()`
* :php:`DataHandler->hook_processDatamap_afterDatabaseOperations()`
* :php:`DataHandler->placeholderShadowing()`
* :php:`DataHandler->getPlaceholderTitleForTableLabel()`
* :php:`DataHandler->fillInFieldArray()`
* :php:`DataHandler->checkValue()`
* :php:`DataHandler->checkValue_SW()`
* :php:`DataHandler->checkValue_flexArray2Xml()`
* :php:`DataHandler->checkValue_inline()`
* :php:`DataHandler->checkValueForInline()`
* :php:`DataHandler->checkValue_checkMax()`
* :php:`DataHandler->getUnique()`
* :php:`DataHandler->getRecordsWithSameValue()`
* :php:`DataHandler->checkValue_text_Eval()`
* :php:`DataHandler->checkValue_input_Eval()`
* :php:`DataHandler->checkValue_group_select_processDBdata()`
* :php:`DataHandler->checkValue_group_select_explodeSelectGroupValue()`
* :php:`DataHandler->checkValue_flex_procInData()`
* :php:`DataHandler->checkValue_flex_procInData_travDS()`
* :php:`DataHandler->copyRecord()`
* :php:`DataHandler->copyPages()`
* :php:`DataHandler->copySpecificPage()`
* :php:`DataHandler->copyRecord_raw()`
* :php:`DataHandler->insertNewCopyVersion()`
* :php:`DataHandler->copyRecord_flexFormCallBack()`
* :php:`DataHandler->copyL10nOverlayRecords()`
* :php:`DataHandler->moveRecord()`
* :php:`DataHandler->moveRecord_raw()`
* :php:`DataHandler->moveRecord_procFields()`
* :php:`DataHandler->moveRecord_procBasedOnFieldType()`
* :php:`DataHandler->moveL10nOverlayRecords()`
* :php:`DataHandler->localize()`
* :php:`DataHandler->deleteAction()`
* :php:`DataHandler->deleteEl()`
* :php:`DataHandler->deleteVersionsForRecord()`
* :php:`DataHandler->undeleteRecord()`
* :php:`DataHandler->deleteRecord()`
* :php:`DataHandler->deletePages()`
* :php:`DataHandler->canDeletePage()`
* :php:`DataHandler->cannotDeleteRecord()`
* :php:`DataHandler->isRecordUndeletable()`
* :php:`DataHandler->deleteRecord_procFields()`
* :php:`DataHandler->deleteRecord_procBasedOnFieldType()`
* :php:`DataHandler->deleteL10nOverlayRecords()`
* :php:`DataHandler->versionizeRecord()`
* :php:`DataHandler->version_remapMMForVersionSwap()`
* :php:`DataHandler->version_remapMMForVersionSwap_flexFormCallBack()`
* :php:`DataHandler->version_remapMMForVersionSwap_execSwap()`
* :php:`DataHandler->remapListedDBRecords()`
* :php:`DataHandler->remapListedDBRecords_flexFormCallBack()`
* :php:`DataHandler->remapListedDBRecords_procDBRefs()`
* :php:`DataHandler->remapListedDBRecords_procInline()`
* :php:`DataHandler->processRemapStack()`
* :php:`DataHandler->addRemapAction()`
* :php:`DataHandler->addRemapStackRefIndex()`
* :php:`DataHandler->getVersionizedIncomingFieldArray()`
* :php:`DataHandler->checkModifyAccessList()`
* :php:`DataHandler->isRecordInWebMount()`
* :php:`DataHandler->isInWebMount()`
* :php:`DataHandler->checkRecordUpdateAccess()`
* :php:`DataHandler->checkRecordInsertAccess()`
* :php:`DataHandler->isTableAllowedForThisPage()`
* :php:`DataHandler->doesRecordExist()`
* :php:`DataHandler->doesBranchExist()`
* :php:`DataHandler->tableReadOnly()`
* :php:`DataHandler->tableAdminOnly()`
* :php:`DataHandler->destNotInsideSelf()`
* :php:`DataHandler->getExcludeListArray()`
* :php:`DataHandler->doesPageHaveUnallowedTables()`
* :php:`DataHandler->pageInfo()`
* :php:`DataHandler->recordInfo()`
* :php:`DataHandler->getRecordProperties()`
* :php:`DataHandler->getRecordPropertiesFromRow()`
* :php:`DataHandler->eventPid()`
* :php:`DataHandler->updateDB()`
* :php:`DataHandler->insertDB()`
* :php:`DataHandler->checkStoredRecord()`
* :php:`DataHandler->setHistory()`
* :php:`DataHandler->updateRefIndex()`
* :php:`DataHandler->getSortNumber()`
* :php:`DataHandler->newFieldArray()`
* :php:`DataHandler->addDefaultPermittedLanguageIfNotSet()`
* :php:`DataHandler->overrideFieldArray()`
* :php:`DataHandler->compareFieldArrayWithCurrentAndUnset()`
* :php:`DataHandler->convNumEntityToByteValue()`
* :php:`DataHandler->deleteClause()`
* :php:`DataHandler->getTableEntries()`
* :php:`DataHandler->getPID()`
* :php:`DataHandler->dbAnalysisStoreExec()`
* :php:`DataHandler->int_pageTreeInfo()`
* :php:`DataHandler->compileAdminTables()`
* :php:`DataHandler->fixUniqueInPid()`
* :php:`DataHandler->fixCopyAfterDuplFields()`
* :php:`DataHandler->isReferenceField()`
* :php:`DataHandler->getInlineFieldType()`
* :php:`DataHandler->getCopyHeader()`
* :php:`DataHandler->prependLabel()`
* :php:`DataHandler->resolvePid()`
* :php:`DataHandler->clearPrefixFromValue()`
* :php:`DataHandler->isRecordCopied()`
* :php:`DataHandler->log()`
* :php:`DataHandler->newlog()`
* :php:`DataHandler->printLogErrorMessages()`
* :php:`DataHandler->insertUpdateDB_preprocessBasedOnFieldType()`
* :php:`DataHandler->hasDeletedRecord()`
* :php:`DataHandler->getAutoVersionId()`
* :php:`DataHandler->getHistoryRecords()`

The reason for this long list is this: If the DataHandler API is
not called via :php:`start()` and the :php:`process_*`  methods, but rather
the methods would be called directly, certain hooks would be disabled completely, resulting in a huge data inconsistency.

At this point, it is highly recommended to use the official API
of :php:`DataHandler` as written in the main documentation.

Various :php:`BackendUtility` class methods are called statically, but cannot
guarantee any Context. Short-hand functions for TCA or Database
Queries are now better suited by using the appropriate Database
Restrictions.

* :php:`BackendUtility::purgeComputedPropertiesFromRecord()`
* :php:`BackendUtility::purgeComputedPropertyNames()`
* :php:`BackendUtility::splitTable_Uid()`
* :php:`BackendUtility::BEenableFields()`
* :php:`BackendUtility::openPageTree()`
* :php:`BackendUtility::getUserNames()`
* :php:`BackendUtility::getGroupNames()`
* :php:`BackendUtility::blindUserNames()`
* :php:`BackendUtility::blindGroupNames()`
* :php:`BackendUtility::getCommonSelectFields()`
* :php:`BackendUtility::helpTextArray()`
* :php:`BackendUtility::helpText()`
* :php:`BackendUtility::wrapInHelp()`
* :php:`BackendUtility::softRefParserObj()`
* :php:`BackendUtility::explodeSoftRefParserList()`
* :php:`BackendUtility::selectVersionsOfRecord()`
* :php:`BackendUtility::fixVersioningPid()`
* :php:`BackendUtility::movePlhOL()`
* :php:`BackendUtility::getLiveVersionIdOfRecord()`
* :php:`BackendUtility::versioningPlaceholderClause()`
* :php:`BackendUtility::getWorkspaceWhereClause()`
* :php:`BackendUtility::wsMapId()`
* :php:`BackendUtility::getMovePlaceholder()`
* :php:`BackendUtility::getBackendScript()`
* :php:`BackendUtility::getWorkspaceWhereClause()`


:php:`BackendUserAuthentication` a.k.a. :php:`$GLOBALS['BE_USER']` contains a lot of internal calls and properties which are only
used for within TYPO3 Core or to keep state. This should not
be exposed in the future anymore, especially when a more flexible
permission system might get introduced. The affected properties
and methods are:

* :php:`BackendUserAuthentication->includeGroupArray`
* :php:`BackendUserAuthentication->errorMsg`
* :php:`BackendUserAuthentication->sessionTimeout`
* :php:`BackendUserAuthentication->firstMainGroup`
* :php:`BackendUserAuthentication->uc_default`
* :php:`BackendUserAuthentication->isMemberOfGroup()`
* :php:`BackendUserAuthentication->getPagePermsClause()`
* :php:`BackendUserAuthentication->isRTE()`
* :php:`BackendUserAuthentication->recordEditAccessInternals()`
* :php:`BackendUserAuthentication->workspaceCannotEditRecord()`
* :php:`BackendUserAuthentication->workspaceAllowLiveRecordsInPID()`
* :php:`BackendUserAuthentication->workspaceAllowsLiveEditingInTable()`
* :php:`BackendUserAuthentication->workspaceCreateNewRecord()`
* :php:`BackendUserAuthentication->workspaceCanCreateNewRecord()`
* :php:`BackendUserAuthentication->workspaceAllowAutoCreation()`
* :php:`BackendUserAuthentication->workspaceCheckStageForCurrent()`
* :php:`BackendUserAuthentication->workspaceInit()`
* :php:`BackendUserAuthentication->checkWorkspace()`
* :php:`BackendUserAuthentication->checkWorkspaceCurrent()`
* :php:`BackendUserAuthentication->setWorkspace()`
* :php:`BackendUserAuthentication->setTemporaryWorkspace()`
* :php:`BackendUserAuthentication->setDefaultWorkspace()`
* :php:`BackendUserAuthentication->getDefaultWorkspace()`
* :php:`BackendUserAuthentication->checkLockToIP()`

.. index:: Backend, PHP-API, ext:backend
