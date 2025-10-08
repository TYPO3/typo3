<?php

return [
    // LocalConfiguration / AdditionalConfiguration settings
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'GLOBAL\'][\'cliKeys\']' => [
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80468-CommandLineInterfaceCliKeysAndCli_dispatchphpsh.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'noPHPscriptInclude\']' => [
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'maxSessionDataSize\']' => [
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-70316-FrontendBasketWithRecs.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS_extensionAdded\']' => [
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80583-TYPO3_CONF_VARS_extensionAdded.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'enable_errorDLOG\']' => [
        'restFiles' => [
            'Breaking-82162-GlobalErrorConstantsRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'enable_exceptionDLOG\']' => [
        'restFiles' => [
            'Breaking-82162-GlobalErrorConstantsRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'sqlDebug\']' => [
        'restFiles' => [
            'Breaking-82421-DroppedOldDBRelatedConfigurationOptions.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'setDBinit\']' => [
        'restFiles' => [
            'Breaking-82421-DroppedOldDBRelatedConfigurationOptions.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'no_pconnect\']' => [
        'restFiles' => [
            'Breaking-82421-DroppedOldDBRelatedConfigurationOptions.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'dbClientCompress\']' => [
        'restFiles' => [
            'Breaking-82421-DroppedOldDBRelatedConfigurationOptions.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'enable_DLOG\']' => [
        'restFiles' => [
            'Breaking-82639-LoggingActivatedForAuthenticationAndServiceClasses.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauth.php\'][\'writeDevLog\']' => [
        'restFiles' => [
            'Breaking-82639-LoggingActivatedForAuthenticationAndServiceClasses.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauth.php\'][\'writeDevLogFE\']' => [
        'restFiles' => [
            'Breaking-82639-LoggingActivatedForAuthenticationAndServiceClasses.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauth.php\'][\'writeDevLogBE\']' => [
        'restFiles' => [
            'Breaking-82639-LoggingActivatedForAuthenticationAndServiceClasses.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'doNotCheckReferer\']' => [
        'restFiles' => [
            'Important-83768-RemoveReferrerCheck.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'caching\'][\'cacheConfigurations\'][\'extbase_reflection\']' => [
        'restFiles' => [
            'Breaking-87558-ConsolidateExtbaseCaches.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'caching\'][\'cacheConfigurations\'][\'extbase_datamapfactory_datamap\']' => [
        'restFiles' => [
            'Breaking-87558-ConsolidateExtbaseCaches.rst',
        ],
    ],

    // Hooks
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_befunc.php\'][\'getFlexFormDSClass\']' => [
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78581-FlexFormRelatedParsing.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/div/class.t3lib_utility_client.php\'][\'getDeviceType\']' => [
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79560-DeprecateClientUtilitygetDeviceType.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typo3/class.db_list.inc\'][\'makeQueryArray\']' => [
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-76259-DeprecateMethodMakeQueryArrayOfAbstractDatabaseRecordList.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/tstemplate_info/class.tx_tstemplateinfo.php\'][\'postTCEProcessingHook\']' => [
        'restFiles' => [
            'Breaking-81171-EditAbilityOfTypoScriptTemplateInEXTtstemplateRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/tstemplate_info/class.tx_tstemplateinfo.php\'][\'postOutputProcessingHook\']' => [
        'restFiles' => [
            'Breaking-81171-EditAbilityOfTypoScriptTemplateInEXTtstemplateRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXT\'][\'extConf\']' => [
        'restFiles' => [
            'Deprecation-82254-DeprecateGLOBALSTYPO3_CONF_VARSEXTextConf.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXT\'][\'allowSystemInstall\']' => [
        'restFiles' => [
            'Breaking-82377-OptionToAllowUploadingSystemExtensionsRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_div.php\'][\'devLog\']' => [
        'restFiles' => [
            'Deprecation-52694-DeprecatedGeneralUtilitydevLog.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'enableDeprecationLog\']' => [
        'restFiles' => [
            'Deprecation-82438-DeprecationMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'fileExtensions\'][\'webspace\'][\'allow\']' => [
        'restFiles' => [
            'Breaking-83081-RemovedConfigurationOptionBeFileExtensionsWebspace.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'fileExtensions\'][\'webspace\'][\'deny\']' => [
        'restFiles' => [
            'Breaking-83081-RemovedConfigurationOptionBeFileExtensionsWebspace.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_parsehtml_proc.php\'][\'modifyParams_LinksRte_PostProc\']' => [
        'restFiles' => [
            'Deprecation-83252-Link-tagSyntaxProcesssing.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_parsehtml_proc.php\'][\'modifyParams_LinksDb_PostProc\']' => [
        'restFiles' => [
            'Deprecation-83252-Link-tagSyntaxProcesssing.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList\'][\'buildQueryParameters\']' => [
        'restFiles' => [
            'Deprecation-83740-CleanupOfAbstractRecordListBreaksHook.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_adminpanel.php\'][\'extendAdminPanel\']' => [
        'restFiles' => [
            'Deprecation-84045-AdminPanelHookDeprecated.rst',
            'Feature-84045-NewAdminPanelModuleAPI.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'urlProcessing\'][\'urlHandlers\']' => [
        'restFiles' => [
            'Deprecation-85124-RedirectingUrlHandlerHookConcept.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/saltedpasswords\'][\'saltMethods\']' => [
        'restFiles' => [
            'Deprecation-85804-SaltedPasswordHashClassDeprecations.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'hook_previewInfo\']' => [
        'restFiles' => [
            'Deprecation-85878-EidUtilityAndVariousTSFEMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'extbase\'][\'commandControllers\']' => [
        'restFiles' => [
            'Deprecation-85977-ExtbaseCommandControllersAndCliAnnotation.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'tslib_fe-PostProc\']' => [
        'restFiles' => [
            'Deprecation-86279-VariousHooksAndPSR-15Middlewares.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'connectToDB\']' => [
        'restFiles' => [
            'Deprecation-86279-VariousHooksAndPSR-15Middlewares.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'initFEuser\']' => [
        'restFiles' => [
            'Deprecation-86279-VariousHooksAndPSR-15Middlewares.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/index_ts.php\'][\'preBeUser\']' => [
        'restFiles' => [
            'Deprecation-86279-VariousHooksAndPSR-15Middlewares.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/index_ts.php\'][\'postBeUser\']' => [
        'restFiles' => [
            'Deprecation-86279-VariousHooksAndPSR-15Middlewares.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'checkAlternativeIdMethods-PostProc\']' => [
        'restFiles' => [
            'Deprecation-86279-VariousHooksAndPSR-15Middlewares.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/index_ts.php\'][\'preprocessRequest\']' => [
        'restFiles' => [
            'Deprecation-86279-VariousHooksAndPSR-15Middlewares.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'checkDataSubmission\']' => [
        'restFiles' => [
            'Deprecation-86279-VariousHooksAndPSR-15Middlewares.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_tstemplate.php\'][\'linkData-PostProc\']' => [
        'restFiles' => [
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'pageNotFound_handling\']' => [
        'restFiles' => [
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
            'Breaking-88376-RemovedObsoletePageNotFound_handlingSettings.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'pageNotFound_handling_statheader\']' => [
        'restFiles' => [
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
            'Breaking-88376-RemovedObsoletePageNotFound_handlingSettings.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'pageNotFound_handling_accessdeniedheader\']' => [
        'restFiles' => [
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
            'Breaking-88376-RemovedObsoletePageNotFound_handlingSettings.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'pageUnavailable_handling\']' => [
        'restFiles' => [
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
            'Breaking-88376-RemovedObsoletePageNotFound_handlingSettings.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'pageUnavailable_handling_statheader\']' => [
        'restFiles' => [
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
            'Breaking-88376-RemovedObsoletePageNotFound_handlingSettings.rst',
        ],
    ],
    '$GLOBALS[\'TCA\'][\'sys_history\']' => [
        'restFiles' => [
            'Breaking-87936-TCAForSysHistoryRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'get_url_id_token\']' => [
        'restFiles' => [
            'Breaking-88458-RemovedFrontendTrackUserFtuFunctionality.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_MISC\'][\'microtime_start\']' => [
        'restFiles' => [
            'Breaking-88498-GlobalDataForTimeTrackerStatisticsRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_MISC\'][\'microtime_end\']' => [
        'restFiles' => [
            'Breaking-88498-GlobalDataForTimeTrackerStatisticsRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_MISC\'][\'microtime_BE_USER_start\']' => [
        'restFiles' => [
            'Breaking-88498-GlobalDataForTimeTrackerStatisticsRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_MISC\'][\'microtime_BE_USER_end\']' => [
        'restFiles' => [
            'Breaking-88498-GlobalDataForTimeTrackerStatisticsRemoved.rst',
        ],
    ],
    '$GLOBALS[\'T3_VAR\'][\'softRefParser\']' => [
        'restFiles' => [
            'Breaking-88638-StreamlinedSoftRefParserReferenceLookup.rst',
        ],
    ],
    '$GLOBALS[\'T3_VAR\'][\'ext\'][\'indexed_search\'][\'indexLocalFiles\']' => [
        'restFiles' => [
            'Breaking-88660-GLOBALST3_VARRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'systemLog\']' => [
        'restFiles' => [
            'Important-89645-RemovedSystemLogOptions.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'systemLogLevel\']' => [
        'restFiles' => [
            'Important-89645-RemovedSystemLogOptions.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'beforeRedirect\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'postProcContent\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'forgotPasswordMail\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'password_changed\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'login_confirmed\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'login_error\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'logout_confirmed\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'loginFormOnSubmitFuncs\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'cObjTypeAndClass\']' => [
        'restFiles' => [
            'Deprecation-90937-VariousHooksInContentObjectRenderer.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'cObjTypeAndClassDefault\']' => [
        'restFiles' => [
            'Deprecation-90937-VariousHooksInContentObjectRenderer.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'extLinkATagParamsHandler\']' => [
        'restFiles' => [
            'Deprecation-90937-VariousHooksInContentObjectRenderer.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'typolinkLinkHandler\']' => [
        'restFiles' => [
            'Deprecation-90937-VariousHooksInContentObjectRenderer.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'pageIndexing\']' => [
        'restFiles' => [
            'Deprecation-91012-VariousHooksRelatedToTypoScriptFrontendController.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'isOutputting\']' => [
        'restFiles' => [
            'Deprecation-91012-VariousHooksRelatedToTypoScriptFrontendController.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'tslib_fe-contentStrReplace\']' => [
        'restFiles' => [
            'Deprecation-91012-VariousHooksRelatedToTypoScriptFrontendController.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'contentPostProc-output\']' => [
        'restFiles' => [
            'Deprecation-91012-VariousHooksRelatedToTypoScriptFrontendController.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'hook_eofe\']' => [
        'restFiles' => [
            'Deprecation-91012-VariousHooksRelatedToTypoScriptFrontendController.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXT\'][\'runtimeActivatedPackages\']' => [
        'restFiles' => [
            'Deprecation-91030-Runtime-ActivatedPackages.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'mod_list\'][\'getSearchFieldList\']' => [
        'restFiles' => [
            'Breaking-92128-DatabaseRecordListDropHookToModifySearchFields.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'recordlist/Modules/Recordlist/index.php\'][\'drawHeaderHook\']' => [
        'restFiles' => [
            'Deprecation-92062-MigrateRecordListControllerHooksToAnPSR-14Event.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'recordlist/Modules/Recordlist/index.php\'][\'drawFooterHook\']' => [
        'restFiles' => [
            'Deprecation-92062-MigrateRecordListControllerHooksToAnPSR-14Event.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'lockBeUserToDBmounts\']' => [
        'restFiles' => [
            'Breaking-92940-GlobalOptionLockBeUserToDBmountsRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'enabledBeUserIPLock\']' => [
        'restFiles' => [
            'Breaking-92941-LockToIPUserTsConfigOptionRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauthgroup.php\'][\'fetchGroupQuery\']' => [
        'restFiles' => [
            'Breaking-93056-RemovedHooksWhenRetrievingBackendUserGroups.rst',
            'Feature-93056-NewEventAfterRetrievingUserGroupsRecursively.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauthgroup.php\'][\'fetchGroups_postProcessing\']' => [
        'restFiles' => [
            'Breaking-93056-RemovedHooksWhenRetrievingBackendUserGroups.rst',
            'Feature-93056-NewEventAfterRetrievingUserGroupsRecursively.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'loginSecurityLevel\']' => [
        'restFiles' => [
            'Important-94312-RemovedBEloginSecurityLevelAndFEloginSecurityLevelOptions.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'loginSecurityLevel\']' => [
        'restFiles' => [
            'Important-94312-RemovedBEloginSecurityLevelAndFEloginSecurityLevelOptions.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'defaultCategorizedTables\']' => [
        'restFiles' => [
            'Deprecation-85613-CategoryRegistry.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typo3/classes/class.frontendedit.php\']' => [
        'restFiles' => [
            'Deprecation-94953-EditPanelRelatedFrontendFunctionality.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'GLOBAL\'][\'extTablesInclusion-PostProcessing\']' => [
        'restFiles' => [
            'Deprecation-95065-HookExtTablesInclusion-PostProcessing.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'fileList\'][\'editIconsHook\']' => [
        'restFiles' => [
            'Deprecation-95077-FilelistEditIconsHook.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'FileDumpEID.php\'][\'checkFileAccess\']' => [
        'restFiles' => [
            'Deprecation-95080-FileDumpCheckFileAccessHook.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_extfilefunc.php\'][\'processData\']' => [
        'restFiles' => [
            'Deprecation-95089-ExtendedFileUtilityProcessDataHook.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'additionalBackendItems\'][\'cacheActions\']' => [
        'restFiles' => [
            'Deprecation-95083-BackendToolbarCacheActionsHook.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typo3/class.db_list_extra.inc\'][\'actions\']' => [
        'restFiles' => [
            'Deprecation-95105-DatabaseRecordListHooks.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typo3/browse_links.php\'][\'browserRendering\']' => [
        'restFiles' => [
            'Deprecation-95322-LegacyElementBrowserLogic.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TBE_MODULES_EXT\'][\'xMOD_db_new_content_el\'][\'addElClasses\']' => [
        'restFiles' => [
            'Deprecation-95343-LegacyHookForNewContentElementWizard.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'toolbarItems\']' => [
        'restFiles' => [
            'Breaking-96041-ToolbarItemsRegisterByTag.rst',
            'Feature-96041-ImproveBackendToolbarRegistration.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_parsehtml_proc.php\'][\'transformation\']' => [
        'restFiles' => [
            'Deprecation-92992-HookT3libclasst3lib_parsehtml_procphptransformation.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'ContextMenu\'][\'ItemProviders\']' => [
        'restFiles' => [
            'Breaking-96333-AutoConfigurationOfContextMenuItemProviders.rst',
            'Feature-96333-ImproveContextMenuItemProviderRegistration.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'cms/tslib/class.tslib_menu.php\'][\'filterMenuPages\']' => [
        'restFiles' => [
            'Breaking-92508-RemovedHookForFilteringHMENUItems.rst',
            'Feature-92508-PSR-14EventForModifyingMenuItems.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'cms/layout/db_layout.php\'][\'drawHeaderHook\']' => [
        'restFiles' => [
            'Breaking-96526-RemovedHooksForModifyingPageModuleContent.rst',
            'Feature-96526-PSR-14EventForModifyingPageModuleContent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'cms/layout/db_layout.php\'][\'drawFooterHook\']' => [
        'restFiles' => [
            'Breaking-96526-RemovedHooksForModifyingPageModuleContent.rst',
            'Feature-96526-PSR-14EventForModifyingPageModuleContent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typolinkProcessing\'][\'typolinkModifyParameterForPageLinks\']' => [
        'restFiles' => [
            'Breaking-87616-RemovedHookForAlteringPageLinks.rst',
            'Feature-87616-PSR-14EventForModifyingPageLinkGeneration.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'USdateFormat\']' => [
        'restFiles' => [
            'Breaking-96550-TYPO3_CONF_VARSSYSUSdateFormatRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'ContentObjects\']' => [
        'restFiles' => [
            'Breaking-96659-RegistrationOfCObjectsViaTYPO3_CONF_VARS.rst',
            'Feature-96659-ContentObjectRegistrationViaServiceConfiguration.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'Backend\Template\Components\ButtonBar\'][\'getButtonsHook\']' => [
        'restFiles' => [
            'Breaking-96806-RemovedHookForModifyingButtonBar.rst',
            'Feature-96806-PSR-14EventForModifyingButtonBar.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'get_cache_timeout\']' => [
        'restFiles' => [
            'Breaking-96879-RemovedHookGetCacheTimeout.rst',
            'Feature-96879-NewPSR-14EventModifyCacheLifetimeForPageEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_befunc.php\'][\'displayWarningMessages\']' => [
        'restFiles' => [
            'Breaking-96899-DisplayWarningMessagesHookRemoved.rst',
            'Feature-96899-NewPSR-14EventModifyGenericBackendMessagesEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'linkvalidator\'][\'checkLinks\']' => [
        'restFiles' => [
            'Breaking-96935-RegisterLinkvalidatorLinktypesViaServiceConfiguration.rst',
            'Feature-96935-NewRegistrationForLinkvalidatorLinktype.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'headerNoCache\']' => [
        'restFiles' => [
            'Breaking-96968-HookHeaderNoCacheRemoved.rst',
            'Feature-96996-PSR-14EventForModifyingRecordAccessEvaluation.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'hook_checkEnableFields\']' => [
        'restFiles' => [
            'Breaking-96996-HookCheckEnableFieldsRemoved.rst',
            'Feature-96996-PSR-14EventForModifyingRecordAccessEvaluation.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController\'][\'newStandardTemplateView\']' => [
        'restFiles' => [
            'Breaking-97135-RemovedSupportForModuleHandlingBasedOnTBE_MODULES_EXT.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController\'][\'newStandardTemplateHandler\']' => [
        'restFiles' => [
            'Breaking-97135-RemovedSupportForModuleHandlingBasedOnTBE_MODULES_EXT.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'cms/web_info/class.tx_cms_webinfo.php\'][\'drawFooterHook\']' => [
        'restFiles' => [
            'Breaking-97174-RemovedHookForModifyingInfoModuleFooterContent.rst',
            'Feature-97174-PSR-14EventForModifyingInfoModuleContent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'cms\'][\'db_new_content_el\'][\'wizardItemsHook\']' => [
        'restFiles' => [
            'Breaking-97201-RemovedHookForNewContentElementWizard.rst',
            'Feature-97201-PSR-14EventForModifyingNewContentElementWizardItems.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'formEngine\'][\'linkHandler\']' => [
        'restFiles' => [
            'Breaking-97187-RemovedHookForModifyingLinkExplanation.rst',
            'Feature-97187-PSR-14EventForModifyingLinkExplanation.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ElementBrowsers\']' => [
        'restFiles' => [
            'Breaking-97188-RegisterElementBrowsersViaServiceConfiguration.rst',
            'Feature-97188-NewRegistrationForElementBrowsers.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_tceforms_inline.php\'][\'tceformsInlineHook\']' => [
        'restFiles' => [
            'Breaking-97231-RemovedHookForManipulatingInlineElementControls.rst',
            'Feature-97231-PSR-14EventsForModifyingInlineElementControls.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'Backend/Form/Element/ImageManipulationElement\'][\'previewUrl\']' => [
        'restFiles' => [
            'Breaking-97230-RemovedHookForModifyingImageManipulationPreviewUrl.rst',
            'Feature-97230-PSR-14EventForModifyingImageManipulationPreviewUrl.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'explicitADmode\']' => [
        'restFiles' => [
            'Breaking-97265-SimplifiedAccessModeSystem.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'typoLink_PostProc\']' => [
        'restFiles' => [
            'Breaking-96641-TypoLinkRelatedHooksRemoved.rst',
            'Feature-96641-NewPSR-14EventForModifyingLinks.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'getATagParamsPostProc\']' => [
        'restFiles' => [
            'Breaking-96641-TypoLinkRelatedHooksRemoved.rst',
            'Feature-96641-NewPSR-14EventForModifyingLinks.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'urlProcessing\'][\'urlProcessors\']' => [
        'restFiles' => [
            'Breaking-96641-TypoLinkRelatedHooksRemoved.rst',
            'Feature-96641-NewPSR-14EventForModifyingLinks.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'workspaces\'][\'modifyDifferenceArray\']' => [
        'restFiles' => [
            'Breaking-97450-RemovedHookForModifyingVersionDifferences.rst',
            'Feature-97450-PSR-14EventForModifyingVersionDifferences.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'reports\']' => [
        'restFiles' => [
            'Breaking-97320-RegisterReportAndStatusViaServiceConfiguration.rst',
            'Feature-97320-NewRegistrationForReportsAndStatus.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_befunc.php\'][\'viewOnClickClass\']' => [
        'restFiles' => [
            'Deprecation-97544-PreviewURIGenerationRelatedFunctionalityInBackendUtility.rst',
            'Feature-97544-PSR-14EventsForModifyingPreviewURIs.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'determineId-PreProcessing\']' => [
        'restFiles' => [
            'Breaking-97737-Page-relatedHooksInTSFERemoved.rst',
            'Feature-97737-PSR-14EventsWhenPageRootlineInFrontendIsResolved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'fetchPageId-PostProcessing\']' => [
        'restFiles' => [
            'Breaking-97737-Page-relatedHooksInTSFERemoved.rst',
            'Feature-97737-PSR-14EventsWhenPageRootlineInFrontendIsResolved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'settingLanguage_preProcess\']' => [
        'restFiles' => [
            'Breaking-97737-Page-relatedHooksInTSFERemoved.rst',
            'Feature-97737-PSR-14EventsWhenPageRootlineInFrontendIsResolved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'determineId-PostProc\']' => [
        'restFiles' => [
            'Breaking-97737-Page-relatedHooksInTSFERemoved.rst',
            'Feature-97737-PSR-14EventsWhenPageRootlineInFrontendIsResolved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'settingLanguage_postProcess\']' => [
        'restFiles' => [
            'Breaking-97737-Page-relatedHooksInTSFERemoved.rst',
            'Feature-97737-PSR-14EventsWhenPageRootlineInFrontendIsResolved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'processor_path_lzw\']' => [
        'restFiles' => [
            'Breaking-97797-GFXSettingProcessor_path_lzwRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'contentPostProc-cached\']' => [
        'restFiles' => [
            'Breaking-97862-HooksRelatedToGeneratingPageContentRemoved.rst',
            'Feature-97862-NewPSR-14EventsForManipulatingFrontendPageGenerationAndCacheBehaviour.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'contentPostProc-all\']' => [
        'restFiles' => [
            'Breaking-97862-HooksRelatedToGeneratingPageContentRemoved.rst',
            'Feature-97862-NewPSR-14EventsForManipulatingFrontendPageGenerationAndCacheBehaviour.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'usePageCache\']' => [
        'restFiles' => [
            'Breaking-97862-HooksRelatedToGeneratingPageContentRemoved.rst',
            'Feature-97862-NewPSR-14EventsForManipulatingFrontendPageGenerationAndCacheBehaviour.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'insertPageIncache\']' => [
        'restFiles' => [
            'Breaking-97862-HooksRelatedToGeneratingPageContentRemoved.rst',
            'Feature-97862-NewPSR-14EventsForManipulatingFrontendPageGenerationAndCacheBehaviour.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typo3/backend.php\'][\'constructPostProcess\']' => [
        'restFiles' => [
            'Breaking-97451-RemoveBackendControllerPageHooks.rst',
            'Feature-97451-PSR-14EventsForBackendPageController.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typo3/backend.php\'][\'renderPreProcess\']' => [
        'restFiles' => [
            'Breaking-97451-RemoveBackendControllerPageHooks.rst',
            'Feature-97451-PSR-14EventsForBackendPageController.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typo3/backend.php\'][\'renderPostProcess\']' => [
        'restFiles' => [
            'Breaking-97451-RemoveBackendControllerPageHooks.rst',
            'Feature-97451-PSR-14EventsForBackendPageController.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'LinkBrowser\'][\'hooks\']' => [
        'restFiles' => [
            'Breaking-97454-RemoveLinkBrowserHooks.rst',
            'Feature-97454-PSR14EventsForLinkBrowserLifecycle.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Workspaces\Service\WorkspaceService\'][\'hasPageRecordVersions\']' => [
        'restFiles' => [
            'Breaking-97945-RemovedWorkspaceServiceHooks.rst',
            'Feature-97945-PSR14AfterPageTreeItemsPreparedEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Workspaces\Service\WorkspaceService\'][\'fetchPagesWithVersionsInTable\']' => [
        'restFiles' => [
            'Breaking-97945-RemovedWorkspaceServiceHooks.rst',
            'Feature-97945-PSR14AfterPageTreeItemsPreparedEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_tsparser.php\'][\'preParseFunc\']' => [
        'restFiles' => [
            'Breaking-98016-RemovedTypoScriptFunctionHook.rst',
            'Feature-98016-PSR-14EvaluateModifierFunctionEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'jsConcatenateHandler\']' => [
        'restFiles' => [
            'Breaking-98100-CompressionAndConcatenationOfJavaScriptAndCSSFilesForBackendRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'jsCompressHandler\']' => [
        'restFiles' => [
            'Breaking-98100-CompressionAndConcatenationOfJavaScriptAndCSSFilesForBackendRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'cssConcatenateHandler\']' => [
        'restFiles' => [
            'Breaking-98100-CompressionAndConcatenationOfJavaScriptAndCSSFilesForBackendRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'cssCompressHandler\']' => [
        'restFiles' => [
            'Breaking-98100-CompressionAndConcatenationOfJavaScriptAndCSSFilesForBackendRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'interfaces\']' => [
        'restFiles' => [
            'Breaking-98179-RemoveBackendInterfaceSelectorAndConfigurableRedirect.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools\'][\'flexParsing\']' => [
        'restFiles' => [
            'Breaking-97449-RemovedHookForModifyingFlexFormParsing.rst',
            'Feature-97449-PSR-14EventsForModifyingFlexFormParsing.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_page.php\'][\'getRecordOverlay\']' => [
        'restFiles' => [
            'Breaking-98303-RemovedHooksForLanguageOverlaysInPageRepository.rst',
            'Deprecation-98303-InterfacesForPageRepositoryLanguageOverlayHooks.rst',
            'Feature-98303-PSR-14EventsForModifyingLanguageOverlays.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_page.php\'][\'getPageOverlay\']' => [
        'restFiles' => [
            'Breaking-98303-RemovedHooksForLanguageOverlaysInPageRepository.rst',
            'Deprecation-98303-InterfacesForPageRepositoryLanguageOverlayHooks.rst',
            'Feature-98303-PSR-14EventsForModifyingLanguageOverlays.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'cms/layout/class.tx_cms_layout.php\'][\'record_is_used\']' => [
        'restFiles' => [
            'Breaking-98375-RemovedHooksInPageModule.rst',
            'Feature-98375-PSR-14EventsInPageModule.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Backend\View\PageLayoutView\'][\'modifyQuery\']' => [
        'restFiles' => [
            'Breaking-98375-RemovedHooksInPageModule.rst',
            'Feature-98375-PSR-14EventsInPageModule.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'cms/layout/class.tx_cms_layout.php\'][\'tt_content_drawItem\']' => [
        'restFiles' => [
            'Breaking-98375-RemovedHooksInPageModule.rst',
            'Feature-98375-PSR-14EventsInPageModule.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'cms/layout/class.tx_cms_layout.php\'][\'list_type_Info\']' => [
        'restFiles' => [
            'Breaking-98375-RemovedHooksInPageModule.rst',
            'Feature-98375-PSR-14EventsInPageModule.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'cms/layout/class.tx_cms_layout.php\'][\'tt_content_drawFooter\']' => [
        'restFiles' => [
            'Breaking-98375-RemovedHooksInPageModule.rst',
            'Feature-98375-PSR-14EventsInPageModule.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typo3/file_edit.php\'][\'preOutputProcessingHook\']' => [
        'restFiles' => [
            'Breaking-97452-RemovedEditFileControllerHooks.rst',
            'Feature-98521-PSR-14EventToModifyFormDataForEditFileForm.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typo3/file_edit.php\'][\'postOutputProcessingHook\']' => [
        'restFiles' => [
            'Breaking-97452-RemovedEditFileControllerHooks.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typo3/alt_doc.php\'][\'makeEditForm_accessCheck\']' => [
        'restFiles' => [
            'Breaking-98304-RemovedHookForModifyingEditFormUserAccess.rst',
            'Feature-98304-PSR-14EventForModifyingEditFormUserAccess.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'GLOBAL\'][\'recStatInfoHooks\']' => [
        'restFiles' => [
            'Breaking-98441-HookRecStatInfoHooksRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTENSIONS\'][\'scheduler\'][\'showSampleTasks\']' => [
        'restFiles' => [
            'Breaking-98489-RemovalOfSleepTaskAndTestTask.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'typo3/class.db_list_extra.inc\'][\'getTable\']' => [
        'restFiles' => [
            'Feature-98490-PSR-14EventToAlterTheRecordsRenderedInRecordListings.rst',
            'Breaking-98490-VariousHooksAndMethodsChangedInDatabaseRecordList.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList\'][\'modifyQuery\']' => [
        'restFiles' => [
            'Feature-98490-PSR-14EventToAlterTheRecordsRenderedInRecordListings.rst',
            'Breaking-98490-VariousHooksAndMethodsChangedInDatabaseRecordList.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList\'][\'makeSearchStringConstraints\']' => [
        'restFiles' => [
            'Feature-98490-PSR-14EventToAlterTheRecordsRenderedInRecordListings.rst',
            'Breaking-98490-VariousHooksAndMethodsChangedInDatabaseRecordList.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'defaultUserTSconfig\']' => [
        'restFiles' => [
            'Deprecation-99075-Fe_usersAndFe_groupsTSconfig.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/install\'][\'update\']' => [
        'restFiles' => [
            'Deprecation-99586-RegistrationOfUpgradeWizardsViaGLOBALS.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/cache/frontend/class.t3lib_cache_frontend_abstractfrontend.php\'][\'flushByTag\']' => [
        'restFiles' => [
            'Deprecation-99592-DeprecatedFlushByTagHook.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Lowlevel\Controller\ConfigurationController\'][\'modifyBlindedConfigurationOptions\']' => [
        'restFiles' => [
            'Deprecation-99717-DeprecatedModifyBlindedConfigurationOptionsHook.rst',
            'Feature-99717-NewPSR-14ModifyBlindedConfigurationOptionsEvent.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TBE_STYLES\'][\'stylesheet\']' => [
        'restFiles' => [
            'Deprecation-100033-TBE_STYLESStylesheetAndStylesheet2.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TBE_STYLES\'][\'stylesheet2\']' => [
        'restFiles' => [
            'Deprecation-100033-TBE_STYLESStylesheetAndStylesheet2.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TBE_STYLES\'][\'skins\']' => [
        'restFiles' => [
            'Deprecation-100232-TBE_STYLESSkinningFunctionality.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TBE_STYLES\'][\'admPanel\']' => [
        'restFiles' => [
            'Deprecation-100232-TBE_STYLESSkinningFunctionality.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauthgroup.php\'][\'getDefaultUploadFolder\']' => [
        'restFiles' => [
            'Deprecation-83608-BackendUsersGetDefaultUploadFolderHook.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauth.php\'][\'postLoginFailureProcessing\']' => [
        'restFiles' => [
            'Deprecation-100278-PostLoginFailureProcessingHook.rst',
            'Feature-100278-PSR-14EventAfterFailedLoginsInBackendOrFrontendUsers.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauth.php\'][\'logoff_pre_processing\']' => [
        'restFiles' => [
            'Deprecation-100307-VariousHooksRelatedToAuthenticationUsers.rst',
            'Feature-100307-PSR-14EventsForUserLoginLogout.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauth.php\'][\'logoff_post_processing\']' => [
        'restFiles' => [
            'Deprecation-100307-VariousHooksRelatedToAuthenticationUsers.rst',
            'Feature-100307-PSR-14EventsForUserLoginLogout.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauthgroup.php\'][\'backendUserLogin\']' => [
        'restFiles' => [
            'Deprecation-100307-VariousHooksRelatedToAuthenticationUsers.rst',
            'Feature-100307-PSR-14EventsForUserLoginLogout.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Core\Imaging\IconFactory\'][\'overrideIconOverlay\']' => [
        'restFiles' => [
            'Breaking-101603-RemovedHookForOverridingIconOverlayIdentifier.rst',
            'Feature-101603-PSR-14EventForModifyingRecordOverlayIconIdentifier.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'defaultPageTSconfig\']' => [
        'restFiles' => [
            'Deprecation-101799-ExtensionManagementUtilityaddPageTSConfig.rst',
            'Breaking-105377-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'defaultUserTSconfig\']' => [
        'restFiles' => [
            'Deprecation-101807-ExtensionManagementUtilityaddUserTSConfig.rst',
            'Breaking-105377-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TCA\'][\'someTable\'][\'types\'][\'bitmask_excludelist_bits\']' => [
        'restFiles' => [
            'Breaking-102108-TCATypesbitmask_Settings.rst',
        ],
    ],
    '$GLOBALS[\'TCA\'][\'someTable\'][\'types\'][\'bitmask_value_field\']' => [
        'restFiles' => [
            'Breaking-102108-TCATypesbitmask_Settings.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'postInit\']' => [
        'restFiles' => [
            'Breaking-102581-RemovedHookForManipulatingContentObjectRenderer.rst',
            'Feature-102581-PSR-14EventForModifyingContentObjectRenderer.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'getData\']' => [
        'restFiles' => [
            'Breaking-102614-RemovedHookForManipulatingGetDataResult.rst',
            'Feature-102614-PSR-14EventForModifyingGetDataResult.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'getImageSourceCollection\']' => [
        'restFiles' => [
            'Breaking-102624-RemovedHookForManipulatingImageSourceCollection.rst',
            'Feature-102624-PSR-14EventForModifyingImageSourceCollection.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'getImgResource\']' => [
        'restFiles' => [
            'Breaking-102755-ImprovedGetImageResourceFunctionality.rst',
            'Feature-102755-PSR-14EventForModifyingGetImageResourceResult.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_page.php\'][\'addEnableColumns\']' => [
        'restFiles' => [
            'Breaking-102793-PageRepository-enableFieldsHookRemoved.rst',
            'Feature-102793-PSR-14EventForModifyingDefaultConstraintsInPageRepository.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_page.php\'][\'getPage\']' => [
        'restFiles' => [
            'Breaking-102806-HooksInPageRepositoryRemoved.rst',
            'Deprecation-102806-InterfacesForPageRepositoryHooks.rst',
            'Feature-102806-BeforePageIsRetrievedEventInPageRepository.rst',
            'Breaking-105377-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\TYPO3\CMS\Core\Domain\PageRepository::class][\'init\']' => [
        'restFiles' => [
            'Breaking-102806-HooksInPageRepositoryRemoved.rst',
            'Deprecation-102806-InterfacesForPageRepositoryHooks.rst',
            'Feature-102806-BeforePageIsRetrievedEventInPageRepository.rst',
            'Breaking-105377-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content_content.php\'][\'modifyDBRow\']' => [
        'restFiles' => [
            'Breaking-99323-RemovedHookForModifyingRecordsAfterFetchingContent.rst',
            'Feature-99323-AddModifyRecordsAfterFetchingContentEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'stdWrap\']' => [
        'restFiles' => [
            'Breaking-102745-RemovedContentObjectStdWrapHook.rst',
            'Feature-102745-PSR-14EventsForModifyingContentObjectStdWrapFunctionality.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'stdWrap_cacheStore\']' => [
        'restFiles' => [
            'Breaking-102849-RemovedContentObjectStdWrapCacheStoreHook.rst',
            'Feature-102849-PSR-14EventForManipulatingStoreCacheFunctionalityOfStdWrap.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'Link\'][\'resolveByStringRepresentation\']' => [
        'restFiles' => [
            'Breaking-102855-RemovedLinkServiceResolveByStringRepresentationHook.rst',
            'Feature-102855-PSR-14EventForModifyingResolvedLinkResultData.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'indexed_search\'][\'metaphone\']' => [
        'restFiles' => [
            'Breaking-102900-MetaphoneSearchRemovedFromIndexed_search.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'indexed_search\'][\'pi1_hooks\'][\'initialize_postProc\']' => [
        'restFiles' => [
            'Breaking-102937-Pi1_hooksHookRemovedFromIndexedSearch.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'indexed_search\'][\'pi1_hooks\'][\'getResultRows\']' => [
        'restFiles' => [
            'Breaking-102937-Pi1_hooksHookRemovedFromIndexedSearch.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'indexed_search\'][\'pi1_hooks\'][\'getDisplayResults\']' => [
        'restFiles' => [
            'Breaking-102937-Pi1_hooksHookRemovedFromIndexedSearch.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'indexed_search\'][\'pi1_hooks\'][\'getDisplayResults_postProc\']' => [
        'restFiles' => [
            'Breaking-102937-Pi1_hooksHookRemovedFromIndexedSearch.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'indexed_search\'][\'pi1_hooks\'][\'getSearchWords\']' => [
        'restFiles' => [
            'Breaking-102937-Pi1_hooksHookRemovedFromIndexedSearch.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'indexed_search\'][\'pi1_hooks\'][\'getResultRows_SQLpointer\']' => [
        'restFiles' => [
            'Breaking-102937-Pi1_hooksHookRemovedFromIndexedSearch.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'indexed_search\'][\'pi1_hooks\'][\'execFinalQuery_idList\']' => [
        'restFiles' => [
            'Breaking-102937-Pi1_hooksHookRemovedFromIndexedSearch.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_gifbuilder.php\'][\'gifbuilder-ConfPreProcess\']' => [
        'restFiles' => [
            'Breaking-102931-GifBuilderHookRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'configArrayPostProc\']' => [
        'restFiles' => [
            'Breaking-102932-RemovedTypoScriptFrontendControllerHooks.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'pageLoadedFromCache\']' => [
        'restFiles' => [
            'Breaking-102932-RemovedTypoScriptFrontendControllerHooks.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'createHashBase\']' => [
        'restFiles' => [
            'Breaking-102932-RemovedTypoScriptFrontendControllerHooks.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'addRootLineFields\']' => [
        'restFiles' => [
            'Deprecation-103752-ObsoleteGLOBALSTYPO3_CONF_VARSFEaddRootLineFields.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList\'][\'customizeCsvHeader\']' => [
        'restFiles' => [
            'Deprecation-102337-DeprecateHooksForRecordDownload.rst',
            'Feature-102337-IntroducePSR14EventModifyRecordListDownloadData.rst',
            'Breaking-105377-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList\'][\'customizeCsvRow\']' => [
        'restFiles' => [
            'Deprecation-102337-DeprecateHooksForRecordDownload.rst',
            'Feature-102337-IntroducePSR14EventModifyRecordListDownloadData.rst',
            'Breaking-105377-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/form\'][\'beforeFormCreate\']' => [
        'restFiles' => [
            'Breaking-107343-RemovedBeforeFormCreateHook.rst',
            'Feature-107343-IntroducePSR14BeforeFormIsCreatedEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/form\'][\'beforeFormSave\']' => [
        'restFiles' => [
            'Breaking-107388-RemovedBeforeFormSaveHook.rst',
            'Feature-107388-IntroducePSR14BeforeFormIsSavedEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/form\'][\'beforeFormDelete\']' => [
        'restFiles' => [
            'Breaking-107382-RemovedBeforeFormDeleteHook.rst',
            'Feature-107382-IntroducePSR14BeforeFormIsDeletedEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/form\'][\'beforeFormDuplicate\']' => [
        'restFiles' => [
            'Breaking-107380-RemovedBeforeFormDuplicateHook.rst',
            'Feature-107380-IntroducePSR14BeforeFormIsDuplicatedEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'lang\'][\'parser\']' => [
        'restFiles' => [
            'Breaking-107436-LocalizationSystemChanges.rst',
            'Deprecation-107436-LocalizationParsers.rst',
            'Feature-107436-SymfonyTranslationIntegration.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'lang\'][\'requireApprovedLocalizations\']' => [
        'restFiles' => [
            'Breaking-107436-LocalizationSystemChanges.rst',
            'Deprecation-107436-LocalizationParsers.rst',
            'Feature-107436-SymfonyTranslationIntegration.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'lang\'][\'format\']' => [
        'restFiles' => [
            'Breaking-107436-LocalizationSystemChanges.rst',
            'Deprecation-107436-LocalizationParsers.rst',
            'Feature-107436-SymfonyTranslationIntegration.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'locallangXMLOverride\']' => [
        'restFiles' => [
            'Breaking-107436-LocalizationSystemChanges.rst',
            'Deprecation-107436-LocalizationParsers.rst',
            'Feature-107436-SymfonyTranslationIntegration.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'lang\'][\'availableLanguages\']' => [
        'restFiles' => [
            'Breaking-107436-LocalizationSystemChanges.rst',
            'Deprecation-107436-LocalizationParsers.rst',
            'Feature-107436-SymfonyTranslationIntegration.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/form\'][\'initializeFormElement\']' => [
        'restFiles' => [
            'Breaking-107518-RemovedInitializeFormElementHook.rst',
            'Feature-107518-IntroducePSR14BeforeRenderableIsAddedToFormEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/form\'][\'beforeRemoveFromParentRenderable\']' => [
        'restFiles' => [
            'Breaking-107528-RemovedBeforeRemoveFromParentRenderableHook.rst',
            'Feature-107528-IntroducePSR14BeforeRenderableIsRemovedFromFormEvent.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'scheduler\'][\'tasks\'][\'TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask\'][\'options\'][\'tables\']' => [
        'restFiles' => [
            'Deprecation-107550-TableGarbageCollectionTaskConfigurationViaGlobals.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'scheduler\'][\'tasks\'][\'TYPO3\CMS\Scheduler\Task\IpAnonymizationTask\'][\'options\'][\'tables\']' => [
        'restFiles' => [
            'Deprecation-107562-IpAnonymizationTaskConfigurationViaGlobals.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'scheduler\'][\'tasks\']' => [
        'restFiles' => [
            'Deprecation-98453-SchedulerTaskRegistrationViaSCOPTIONS.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/form\'][\'beforeRendering\']' => [
        'restFiles' => [
            'Breaking-107569-RemovedBeforeRenderingHook.rst',
            'Feature-107569-IntroducePSR14BeforeRenderableIsRenderedEvent.rst',
        ],
    ],
];
