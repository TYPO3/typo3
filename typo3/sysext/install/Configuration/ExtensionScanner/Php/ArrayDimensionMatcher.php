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
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'postProcContent\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'forgotPasswordMail\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'password_changed\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'login_confirmed\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'login_error\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'logout_confirmed\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'felogin\'][\'loginFormOnSubmitFuncs\']' => [
        'restFiles' => [
            'Deprecation-88740-ExtFeloginPibasePlugin.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'cObjTypeAndClass\']' => [
        'restFiles' => [
            'Deprecation-90937-VariousHooksInContentObjectRenderer.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'cObjTypeAndClassDefault\']' => [
        'restFiles' => [
            'Deprecation-90937-VariousHooksInContentObjectRenderer.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'extLinkATagParamsHandler\']' => [
        'restFiles' => [
            'Deprecation-90937-VariousHooksInContentObjectRenderer.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_content.php\'][\'typolinkLinkHandler\']' => [
        'restFiles' => [
            'Deprecation-90937-VariousHooksInContentObjectRenderer.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'pageIndexing\']' => [
        'restFiles' => [
            'Deprecation-91012-VariousHooksRelatedToTypoScriptFrontendController.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'isOutputting\']' => [
        'restFiles' => [
            'Deprecation-91012-VariousHooksRelatedToTypoScriptFrontendController.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'tslib_fe-contentStrReplace\']' => [
        'restFiles' => [
            'Deprecation-91012-VariousHooksRelatedToTypoScriptFrontendController.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'contentPostProc-output\']' => [
        'restFiles' => [
            'Deprecation-91012-VariousHooksRelatedToTypoScriptFrontendController.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_fe.php\'][\'hook_eofe\']' => [
        'restFiles' => [
            'Deprecation-91012-VariousHooksRelatedToTypoScriptFrontendController.rst',
        ],
    ],
    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXT\'][\'runtimeActivatedPackages\']' => [
        'restFiles' => [
            'Deprecation-91030-Runtime-ActivatedPackages.rst',
        ],
    ],
];
