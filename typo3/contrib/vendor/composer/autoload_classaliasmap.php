<?php
return array (
  'aliasToClassNameMapping' => 
  array (
    'ajaxlogin' => 'TYPO3\\CMS\\Backend\\AjaxLoginHandler',
    'clickmenu' => 'TYPO3\\CMS\\Backend\\ClickMenu\\ClickMenu',
    't3lib_clipboard' => 'TYPO3\\CMS\\Backend\\Clipboard\\Clipboard',
    't3lib_transl8tools' => 'TYPO3\\CMS\\Backend\\Configuration\\TranslationConfigurationProvider',
    't3lib_tsparser_tsconfig' => 'TYPO3\\CMS\\Backend\\Configuration\\TsConfigParser',
    't3lib_matchcondition_backend' => 'TYPO3\\CMS\\Backend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher',
    't3lib_contextmenu_abstractcontextmenu' => 'TYPO3\\CMS\\Backend\\ContextMenu\\AbstractContextMenu',
    't3lib_contextmenu_abstractdataprovider' => 'TYPO3\\CMS\\Backend\\ContextMenu\\AbstractContextMenuDataProvider',
    't3lib_contextmenu_action' => 'TYPO3\\CMS\\Backend\\ContextMenu\\ContextMenuAction',
    't3lib_contextmenu_actioncollection' => 'TYPO3\\CMS\\Backend\\ContextMenu\\ContextMenuActionCollection',
    't3lib_contextmenu_extdirect_contextmenu' => 'TYPO3\\CMS\\Backend\\ContextMenu\\Extdirect\\AbstractExtdirectContextMenu',
    't3lib_contextmenu_pagetree_dataprovider' => 'TYPO3\\CMS\\Backend\\ContextMenu\\Pagetree\\ContextMenuDataProvider',
    't3lib_contextmenu_pagetree_extdirect_contextmenu' => 'TYPO3\\CMS\\Backend\\ContextMenu\\Pagetree\\Extdirect\\ContextMenuConfiguration',
    't3lib_contextmenu_renderer_abstract' => 'TYPO3\\CMS\\Backend\\ContextMenu\\Renderer\\AbstractContextMenuRenderer',
    'typo3backend' => 'TYPO3\\CMS\\Backend\\Controller\\BackendController',
    'sc_wizard_backend_layout' => 'TYPO3\\CMS\\Backend\\Controller\\BackendLayoutWizardController',
    'sc_alt_clickmenu' => 'TYPO3\\CMS\\Backend\\Controller\\ClickMenuController',
    'sc_show_rechis' => 'TYPO3\\CMS\\Backend\\Controller\\ContentElement\\ElementHistoryController',
    'sc_show_item' => 'TYPO3\\CMS\\Backend\\Controller\\ContentElement\\ElementInformationController',
    'sc_move_el' => 'TYPO3\\CMS\\Backend\\Controller\\ContentElement\\MoveElementController',
    'sc_db_new_content_el' => 'TYPO3\\CMS\\Backend\\Controller\\ContentElement\\NewContentElementController',
    'sc_dummy' => 'TYPO3\\CMS\\Backend\\Controller\\DummyController',
    'sc_alt_doc' => 'TYPO3\\CMS\\Backend\\Controller\\EditDocumentController',
    'sc_file_newfolder' => 'TYPO3\\CMS\\Backend\\Controller\\File\\CreateFolderController',
    'sc_file_edit' => 'TYPO3\\CMS\\Backend\\Controller\\File\\EditFileController',
    'typo3_tcefile' => 'TYPO3\\CMS\\Backend\\Controller\\File\\FileController',
    'sc_file_upload' => 'TYPO3\\CMS\\Backend\\Controller\\File\\FileUploadController',
    'sc_file_rename' => 'TYPO3\\CMS\\Backend\\Controller\\File\\RenameFileController',
    'sc_alt_file_navframe' => 'TYPO3\\CMS\\Backend\\Controller\\FileSystemNavigationFrameController',
    'sc_listframe_loader' => 'TYPO3\\CMS\\Backend\\Controller\\ListFrameLoaderController',
    'sc_index' => 'TYPO3\\CMS\\Backend\\Controller\\LoginController',
    'sc_login_frameset' => 'TYPO3\\CMS\\Backend\\Controller\\LoginFramesetController',
    'sc_logout' => 'TYPO3\\CMS\\Backend\\Controller\\LogoutController',
    'sc_db_new' => 'TYPO3\\CMS\\Backend\\Controller\\NewRecordController',
    'sc_alt_doc_nodoc' => 'TYPO3\\CMS\\Backend\\Controller\\NoDocumentsOpenController',
    'sc_db_layout' => 'TYPO3\\CMS\\Backend\\Controller\\PageLayoutController',
    'sc_alt_db_navframe' => 'TYPO3\\CMS\\Backend\\Controller\\PageTreeNavigationController',
    'sc_tce_db' => 'TYPO3\\CMS\\Backend\\Controller\\SimpleDataHandlerController',
    'sc_wizard_add' => 'TYPO3\\CMS\\Backend\\Controller\\Wizard\\AddController',
    'sc_wizard_colorpicker' => 'TYPO3\\CMS\\Backend\\Controller\\Wizard\\ColorpickerController',
    'sc_wizard_edit' => 'TYPO3\\CMS\\Backend\\Controller\\Wizard\\EditController',
    'sc_wizard_forms' => 'TYPO3\\CMS\\Backend\\Controller\\Wizard\\FormsController',
    'sc_wizard_list' => 'TYPO3\\CMS\\Backend\\Controller\\Wizard\\ListController',
    'sc_wizard_rte' => 'TYPO3\\CMS\\Backend\\Controller\\Wizard\\RteController',
    'sc_wizard_table' => 'TYPO3\\CMS\\Backend\\Controller\\Wizard\\TableController',
    't3lib_transferdata' => 'TYPO3\\CMS\\Backend\\Form\\DataPreprocessor',
    't3lib_tceforms_inline' => 'TYPO3\\CMS\\Backend\\Form\\Element\\InlineElement',
    't3lib_tceformsinlinehook' => 'TYPO3\\CMS\\Backend\\Form\\Element\\InlineElementHookInterface',
    't3lib_tceforms_fe' => 'TYPO3\\CMS\\Backend\\Form\\FrontendFormEngine',
    't3lib_tceforms_dbfileiconshook' => 'TYPO3\\CMS\\Backend\\Form\\DatabaseFileIconsHookInterface',
    't3lib_tceforms_suggest_defaultreceiver' => 'TYPO3\\CMS\\Backend\\Form\\Element\\SuggestDefaultReceiver',
    't3lib_tceforms_suggest' => 'TYPO3\\CMS\\Backend\\Form\\Element\\SuggestElement',
    't3lib_tceforms_tree' => 'TYPO3\\CMS\\Backend\\Form\\Element\\TreeElement',
    't3lib_tceforms_valueslider' => 'TYPO3\\CMS\\Backend\\Form\\Element\\ValueSlider',
    't3lib_tceforms_flexforms' => 'TYPO3\\CMS\\Backend\\Form\\FlexFormsHelper',
    't3lib_tceforms' => 'TYPO3\\CMS\\Backend\\Form\\FormEngine',
    't3lib_tsfebeuserauth' => 'TYPO3\\CMS\\Backend\\FrontendBackendUserAuthentication',
    'recordhistory' => 'TYPO3\\CMS\\Backend\\History\\RecordHistory',
    'extdirect_dataprovider_state' => 'TYPO3\\CMS\\Backend\\InterfaceState\\ExtDirect\\DataProvider',
    't3lib_extobjbase' => 'TYPO3\\CMS\\Backend\\Module\\AbstractFunctionModule',
    't3lib_scbase' => 'TYPO3\\CMS\\Backend\\Module\\BaseScriptClass',
    't3lib_loadmodules' => 'TYPO3\\CMS\\Backend\\Module\\ModuleLoader',
    'typo3_modulestorage' => 'TYPO3\\CMS\\Backend\\Module\\ModuleStorage',
    't3lib_modsettings' => 'TYPO3\\CMS\\Backend\\ModuleSettings',
    't3lib_recordlist' => 'TYPO3\\CMS\\Backend\\RecordList\\AbstractRecordList',
    'tbe_browser_recordlist' => 'TYPO3\\CMS\\Backend\\RecordList\\ElementBrowserRecordList',
    't3lib_localrecordlistgettablehook' => 'TYPO3\\CMS\\Backend\\RecordList\\RecordListGetTableHookInterface',
    't3lib_rteapi' => 'TYPO3\\CMS\\Backend\\Rte\\AbstractRte',
    'extdirect_dataprovider_backendlivesearch' => 'TYPO3\\CMS\\Backend\\Search\\LiveSearch\\ExtDirect\\LiveSearchDataProvider',
    't3lib_search_livesearch' => 'TYPO3\\CMS\\Backend\\Search\\LiveSearch\\LiveSearch',
    't3lib_search_livesearch_queryparser' => 'TYPO3\\CMS\\Backend\\Search\\LiveSearch\\QueryParser',
    't3lib_spritemanager_abstracthandler' => 'TYPO3\\CMS\\Backend\\Sprite\\AbstractSpriteHandler',
    't3lib_spritemanager_simplehandler' => 'TYPO3\\CMS\\Backend\\Sprite\\SimpleSpriteHandler',
    't3lib_spritemanager_spritebuildinghandler' => 'TYPO3\\CMS\\Backend\\Sprite\\SpriteBuildingHandler',
    't3lib_spritemanager_spritegenerator' => 'TYPO3\\CMS\\Backend\\Sprite\\SpriteGenerator',
    't3lib_spritemanager_spriteicongenerator' => 'TYPO3\\CMS\\Backend\\Sprite\\SpriteIconGeneratorInterface',
    't3lib_spritemanager' => 'TYPO3\\CMS\\Backend\\Sprite\\SpriteManager',
    'bigdoc' => 'TYPO3\\CMS\\Backend\\Template\\BigDocumentTemplate',
    'template' => 'TYPO3\\CMS\\Backend\\Template\\DocumentTemplate',
    'frontenddoc' => 'TYPO3\\CMS\\Backend\\Template\\FrontendDocumentTemplate',
    'mediumdoc' => 'TYPO3\\CMS\\Backend\\Template\\MediumDocumentTemplate',
    'smalldoc' => 'TYPO3\\CMS\\Backend\\Template\\SmallDocumentTemplate',
    'nodoc' => 'TYPO3\\CMS\\Backend\\Template\\StandardDocumentTemplate',
    'backend_cacheactionshook' => 'TYPO3\\CMS\\Backend\\Toolbar\\ClearCacheActionsHookInterface',
    'clearcachemenu' => 'TYPO3\\CMS\\Backend\\Toolbar\\ClearCacheToolbarItem',
    'livesearch' => 'TYPO3\\CMS\\Backend\\Toolbar\\LiveSearchToolbarItem',
    'shortcutmenu' => 'TYPO3\\CMS\\Backend\\Toolbar\\ShortcutToolbarItem',
    'backend_toolbaritem' => 'TYPO3\\CMS\\Backend\\Toolbar\\ToolbarItemHookInterface',
    't3lib_tree_extdirect_abstractextjstree' => 'TYPO3\\CMS\\Backend\\Tree\\AbstractExtJsTree',
    't3lib_tree_abstracttree' => 'TYPO3\\CMS\\Backend\\Tree\\AbstractTree',
    't3lib_tree_abstractdataprovider' => 'TYPO3\\CMS\\Backend\\Tree\\AbstractTreeDataProvider',
    't3lib_tree_abstractstateprovider' => 'TYPO3\\CMS\\Backend\\Tree\\AbstractTreeStateProvider',
    't3lib_tree_comparablenode' => 'TYPO3\\CMS\\Backend\\Tree\\ComparableNodeInterface',
    't3lib_tree_draggableanddropable' => 'TYPO3\\CMS\\Backend\\Tree\\DraggableAndDropableNodeInterface',
    't3lib_tree_labeleditable' => 'TYPO3\\CMS\\Backend\\Tree\\EditableNodeLabelInterface',
    't3lib_tree_extdirect_node' => 'TYPO3\\CMS\\Backend\\Tree\\ExtDirectNode',
    't3lib_tree_pagetree_interfaces_collectionprocessor' => 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\CollectionProcessorInterface',
    't3lib_tree_pagetree_commands' => 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\Commands',
    't3lib_tree_pagetree_dataprovider' => 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\DataProvider',
    't3lib_tree_pagetree_extdirect_commands' => 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\ExtdirectTreeCommands',
    't3lib_tree_pagetree_extdirect_tree' => 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\ExtdirectTreeDataProvider',
    't3lib_tree_pagetree_indicator' => 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\Indicator',
    't3lib_tree_pagetree_interfaces_indicatorprovider' => 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\IndicatorProviderInterface',
    't3lib_tree_pagetree_node' => 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNode',
    't3lib_tree_pagetree_nodecollection' => 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection',
    't3lib_tree_renderer_abstract' => 'TYPO3\\CMS\\Backend\\Tree\\Renderer\\AbstractTreeRenderer',
    't3lib_tree_renderer_extjsjson' => 'TYPO3\\CMS\\Backend\\Tree\\Renderer\\ExtJsJsonTreeRenderer',
    't3lib_tree_renderer_unorderedlist' => 'TYPO3\\CMS\\Backend\\Tree\\Renderer\\UnorderedListTreeRenderer',
    't3lib_tree_sortednodecollection' => 'TYPO3\\CMS\\Backend\\Tree\\SortedTreeNodeCollection',
    't3lib_tree_node' => 'TYPO3\\CMS\\Backend\\Tree\\TreeNode',
    't3lib_tree_nodecollection' => 'TYPO3\\CMS\\Backend\\Tree\\TreeNodeCollection',
    't3lib_tree_representationnode' => 'TYPO3\\CMS\\Backend\\Tree\\TreeRepresentationNode',
    't3lib_treeview' => 'TYPO3\\CMS\\Backend\\Tree\\View\\AbstractTreeView',
    't3lib_browsetree' => 'TYPO3\\CMS\\Backend\\Tree\\View\\BrowseTreeView',
    't3lib_foldertree' => 'TYPO3\\CMS\\Backend\\Tree\\View\\FolderTreeView',
    't3lib_positionmap' => 'TYPO3\\CMS\\Backend\\Tree\\View\\PagePositionMap',
    't3lib_pagetree' => 'TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView',
    'extdirect_dataprovider_backendusersettings' => 'TYPO3\\CMS\\Backend\\User\\ExtDirect\\BackendUserSettingsDataProvider',
    't3lib_befunc' => 'TYPO3\\CMS\\Backend\\Utility\\BackendUtility',
    't3lib_iconworks' => 'TYPO3\\CMS\\Backend\\Utility\\IconUtility',
    'tx_cms_backendlayout' => 'TYPO3\\CMS\\Backend\\View\\BackendLayoutView',
    'modulemenu' => 'TYPO3\\CMS\\Backend\\View\\ModuleMenuView',
    'tx_cms_layout' => 'TYPO3\\CMS\\Backend\\View\\PageLayoutView',
    'tx_cms_layout_tt_content_drawitemhook' => 'TYPO3\\CMS\\Backend\\View\\PageLayoutViewDrawItemHookInterface',
    'webpagetree' => 'TYPO3\\CMS\\Backend\\View\\PageTreeView',
    'sc_t3lib_thumbs' => 'TYPO3\\CMS\\Backend\\View\\ThumbnailView',
    'typo3logo' => 'TYPO3\\CMS\\Backend\\View\\LogoView',
    'cms_newcontentelementwizardshook' => 'TYPO3\\CMS\\Backend\\Wizard\\NewContentElementWizardHookInterface',
    't3lib_extjs_extdirectrouter' => 'TYPO3\\CMS\\Core\\ExtDirect\\ExtDirectRouter',
    't3lib_extjs_extdirectapi' => 'TYPO3\\CMS\\Core\\ExtDirect\\ExtDirectApi',
    't3lib_extjs_extdirectdebug' => 'TYPO3\\CMS\\Core\\ExtDirect\\ExtDirectDebug',
    't3lib_cli' => 'TYPO3\\CMS\\Core\\Controller\\CommandLineController',
    'extdirect_dataprovider_contexthelp' => 'TYPO3\\CMS\\ContextHelp\\ExtDirect\\ContextHelpDataProvider',
    't3lib_userauth' => 'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication',
    't3lib_beuserauth' => 'TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication',
    't3lib_autoloader' => 'TYPO3\\CMS\\Core\\Core\\ClassLoader',
    't3lib_cache_backend_abstractbackend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend',
    't3lib_cache_backend_apcbackend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\ApcBackend',
    't3lib_cache_backend_backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\BackendInterface',
    't3lib_cache_backend_filebackend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\FileBackend',
    't3lib_cache_backend_memcachedbackend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\MemcachedBackend',
    't3lib_cache_backend_nullbackend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend',
    't3lib_cache_backend_pdobackend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\PdoBackend',
    't3lib_cache_backend_phpcapablebackend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\PhpCapableBackendInterface',
    't3lib_cache_backend_redisbackend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\RedisBackend',
    't3lib_cache_backend_transientmemorybackend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend',
    't3lib_cache_backend_dbbackend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
    't3lib_cache' => 'TYPO3\\CMS\\Core\\Cache\\Cache',
    't3lib_cache_factory' => 'TYPO3\\CMS\\Core\\Cache\\CacheFactory',
    't3lib_cache_manager' => 'TYPO3\\CMS\\Core\\Cache\\CacheManager',
    't3lib_cache_exception' => 'TYPO3\\CMS\\Core\\Cache\\Exception',
    't3lib_cache_exception_classalreadyloaded' => 'TYPO3\\CMS\\Core\\Cache\\Exception\\ClassAlreadyLoadedException',
    't3lib_cache_exception_duplicateidentifier' => 'TYPO3\\CMS\\Core\\Cache\\Exception\\DuplicateIdentifierException',
    't3lib_cache_exception_invalidbackend' => 'TYPO3\\CMS\\Core\\Cache\\Exception\\InvalidBackendException',
    't3lib_cache_exception_invalidcache' => 'TYPO3\\CMS\\Core\\Cache\\Exception\\InvalidCacheException',
    't3lib_cache_exception_invaliddata' => 'TYPO3\\CMS\\Core\\Cache\\Exception\\InvalidDataException',
    't3lib_cache_exception_nosuchcache' => 'TYPO3\\CMS\\Core\\Cache\\Exception\\NoSuchCacheException',
    't3lib_cache_frontend_abstractfrontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
    't3lib_cache_frontend_frontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface',
    't3lib_cache_frontend_phpfrontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\PhpFrontend',
    't3lib_cache_frontend_stringfrontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend',
    't3lib_cache_frontend_variablefrontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend',
    't3lib_cs' => 'TYPO3\\CMS\\Core\\Charset\\CharsetConverter',
    't3lib_collection_abstractrecordcollection' => 'TYPO3\\CMS\\Core\\Collection\\AbstractRecordCollection',
    't3lib_collection_collection' => 'TYPO3\\CMS\\Core\\Collection\\CollectionInterface',
    't3lib_collection_editable' => 'TYPO3\\CMS\\Core\\Collection\\EditableCollectionInterface',
    't3lib_collection_nameable' => 'TYPO3\\CMS\\Core\\Collection\\NameableCollectionInterface',
    't3lib_collection_persistable' => 'TYPO3\\CMS\\Core\\Collection\\PersistableCollectionInterface',
    't3lib_collection_recordcollection' => 'TYPO3\\CMS\\Core\\Collection\\RecordCollectionInterface',
    't3lib_collection_recordcollectionrepository' => 'TYPO3\\CMS\\Core\\Collection\\RecordCollectionRepository',
    't3lib_collection_sortable' => 'TYPO3\\CMS\\Core\\Collection\\SortableCollectionInterface',
    't3lib_collection_staticrecordcollection' => 'TYPO3\\CMS\\Core\\Collection\\StaticRecordCollection',
    't3lib_flexformtools' => 'TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools',
    't3lib_matchcondition_abstract' => 'TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher',
    't3lib_db' => 'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
    't3lib_pdohelper' => 'TYPO3\\CMS\\Core\\Database\\PdoHelper',
    't3lib_db_postprocessqueryhook' => 'TYPO3\\CMS\\Core\\Database\\PostProcessQueryHookInterface',
    't3lib_db_preparedstatement' => 'TYPO3\\CMS\\Core\\Database\\PreparedStatement',
    't3lib_db_preprocessqueryhook' => 'TYPO3\\CMS\\Core\\Database\\PreProcessQueryHookInterface',
    't3lib_querygenerator' => 'TYPO3\\CMS\\Core\\Database\\QueryGenerator',
    't3lib_fullsearch' => 'TYPO3\\CMS\\Core\\Database\\QueryView',
    't3lib_refindex' => 'TYPO3\\CMS\\Core\\Database\\ReferenceIndex',
    't3lib_loaddbgroup' => 'TYPO3\\CMS\\Core\\Database\\RelationHandler',
    't3lib_softrefproc' => 'TYPO3\\CMS\\Core\\Database\\SoftReferenceIndex',
    't3lib_sqlparser' => 'TYPO3\\CMS\\Core\\Database\\SqlParser',
    't3lib_exttables_postprocessinghook' => 'TYPO3\\CMS\\Core\\Database\\TableConfigurationPostProcessingHookInterface',
    't3lib_tcemain' => 'TYPO3\\CMS\\Core\\DataHandling\\DataHandler',
    't3lib_tcemain_checkmodifyaccesslisthook' => 'TYPO3\\CMS\\Core\\DataHandling\\DataHandlerCheckModifyAccessListHookInterface',
    't3lib_tcemain_processuploadhook' => 'TYPO3\\CMS\\Core\\DataHandling\\DataHandlerProcessUploadHookInterface',
    't3lib_browselinkshook' => 'TYPO3\\CMS\\Core\\ElementBrowser\\ElementBrowserHookInterface',
    't3lib_codec_javascriptencoder' => 'TYPO3\\CMS\\Core\\Encoder\\JavaScriptEncoder',
    't3lib_error_abstractexceptionhandler' => 'TYPO3\\CMS\\Core\\Error\\AbstractExceptionHandler',
    't3lib_error_debugexceptionhandler' => 'TYPO3\\CMS\\Core\\Error\\DebugExceptionHandler',
    't3lib_error_errorhandler' => 'TYPO3\\CMS\\Core\\Error\\ErrorHandler',
    't3lib_error_errorhandlerinterface' => 'TYPO3\\CMS\\Core\\Error\\ErrorHandlerInterface',
    't3lib_error_exception' => 'TYPO3\\CMS\\Core\\Error\\Exception',
    't3lib_error_exceptionhandlerinterface' => 'TYPO3\\CMS\\Core\\Error\\ExceptionHandlerInterface',
    't3lib_error_http_abstractclienterrorexception' => 'TYPO3\\CMS\\Core\\Error\\Http\\AbstractClientErrorException',
    't3lib_error_http_abstractservererrorexception' => 'TYPO3\\CMS\\Core\\Error\\Http\\AbstractServerErrorException',
    't3lib_error_http_badrequestexception' => 'TYPO3\\CMS\\Core\\Error\\Http\\BadRequestException',
    't3lib_error_http_forbiddenexception' => 'TYPO3\\CMS\\Core\\Error\\Http\\ForbiddenException',
    't3lib_error_http_pagenotfoundexception' => 'TYPO3\\CMS\\Core\\Error\\Http\\PageNotFoundException',
    't3lib_error_http_serviceunavailableexception' => 'TYPO3\\CMS\\Core\\Error\\Http\\ServiceUnavailableException',
    't3lib_error_http_statusexception' => 'TYPO3\\CMS\\Core\\Error\\Http\\StatusException',
    't3lib_error_http_unauthorizedexception' => 'TYPO3\\CMS\\Core\\Error\\Http\\UnauthorizedException',
    't3lib_error_productionexceptionhandler' => 'TYPO3\\CMS\\Core\\Error\\ProductionExceptionHandler',
    't3lib_exception' => 'TYPO3\\CMS\\Core\\Exception',
    't3lib_extmgm' => 'TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility',
    't3lib_formprotection_abstract' => 'TYPO3\\CMS\\Core\\FormProtection\\AbstractFormProtection',
    't3lib_formprotection_backendformprotection' => 'TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection',
    't3lib_formprotection_disabledformprotection' => 'TYPO3\\CMS\\Core\\FormProtection\\DisabledFormProtection',
    't3lib_formprotection_invalidtokenexception' => 'TYPO3\\CMS\\Core\\FormProtection\\Exception',
    't3lib_formprotection_factory' => 'TYPO3\\CMS\\Core\\FormProtection\\FormProtectionFactory',
    't3lib_formprotection_installtoolformprotection' => 'TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection',
    't3lib_frontendedit' => 'TYPO3\\CMS\\Core\\FrontendEditing\\FrontendEditingController',
    't3lib_parsehtml' => 'TYPO3\\CMS\\Core\\Html\\HtmlParser',
    't3lib_parsehtml_proc' => 'TYPO3\\CMS\\Core\\Html\\RteHtmlParser',
    'typo3ajax' => 'TYPO3\\CMS\\Core\\Http\\AjaxRequestHandler',
    't3lib_http_request' => 'TYPO3\\CMS\\Core\\Http\\HttpRequest',
    't3lib_http_observer_download' => 'TYPO3\\CMS\\Core\\Http\\Observer\\Download',
    't3lib_stdgraphic' => 'TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions',
    't3lib_admin' => 'TYPO3\\CMS\\Core\\Integrity\\DatabaseIntegrityCheck',
    't3lib_l10n_exception_filenotfound' => 'TYPO3\\CMS\\Core\\Localization\\Exception\\FileNotFoundException',
    't3lib_l10n_exception_invalidparser' => 'TYPO3\\CMS\\Core\\Localization\\Exception\\InvalidParserException',
    't3lib_l10n_exception_invalidxmlfile' => 'TYPO3\\CMS\\Core\\Localization\\Exception\\InvalidXmlFileException',
    't3lib_l10n_store' => 'TYPO3\\CMS\\Core\\Localization\\LanguageStore',
    't3lib_l10n_locales' => 'TYPO3\\CMS\\Core\\Localization\\Locales',
    't3lib_l10n_factory' => 'TYPO3\\CMS\\Core\\Localization\\LocalizationFactory',
    't3lib_l10n_parser_abstractxml' => 'TYPO3\\CMS\\Core\\Localization\\Parser\\AbstractXmlParser',
    't3lib_l10n_parser' => 'TYPO3\\CMS\\Core\\Localization\\Parser\\LocalizationParserInterface',
    't3lib_l10n_parser_llphp' => 'TYPO3\\CMS\\Core\\Localization\\Parser\\LocallangArrayParser',
    't3lib_l10n_parser_llxml' => 'TYPO3\\CMS\\Core\\Localization\\Parser\\LocallangXmlParser',
    't3lib_l10n_parser_xliff' => 'TYPO3\\CMS\\Core\\Localization\\Parser\\XliffParser',
    't3lib_lock' => 'TYPO3\\CMS\\Core\\Locking\\Locker',
    't3lib_mail_mailer' => 'TYPO3\\CMS\\Core\\Mail\\Mailer',
    't3lib_mail_maileradapter' => 'TYPO3\\CMS\\Core\\Mail\\MailerAdapterInterface',
    't3lib_mail_message' => 'TYPO3\\CMS\\Core\\Mail\\MailMessage',
    't3lib_mail_mboxtransport' => 'TYPO3\\CMS\\Core\\Mail\\MboxTransport',
    't3lib_mail_rfc822addressesparser' => 'TYPO3\\CMS\\Core\\Mail\\Rfc822AddressesParser',
    't3lib_mail_swiftmaileradapter' => 'TYPO3\\CMS\\Core\\Mail\\SwiftMailerAdapter',
    't3lib_message_abstractmessage' => 'TYPO3\\CMS\\Core\\Messaging\\AbstractMessage',
    't3lib_message_abstractstandalonemessage' => 'TYPO3\\CMS\\Core\\Messaging\\AbstractStandaloneMessage',
    't3lib_message_errorpagemessage' => 'TYPO3\\CMS\\Core\\Messaging\\ErrorpageMessage',
    't3lib_flashmessage' => 'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
    't3lib_flashmessagequeue' => 'TYPO3\\CMS\\Core\\Messaging\\FlashMessageQueue',
    't3lib_pagerenderer' => 'TYPO3\\CMS\\Core\\Page\\PageRenderer',
    't3lib_registry' => 'TYPO3\\CMS\\Core\\Registry',
    't3lib_compressor' => 'TYPO3\\CMS\\Core\\Resource\\ResourceCompressor',
    't3lib_svbase' => 'TYPO3\\CMS\\Core\\Service\\AbstractService',
    't3lib_singleton' => 'TYPO3\\CMS\\Core\\SingletonInterface',
    't3lib_timetracknull' => 'TYPO3\\CMS\\Core\\TimeTracker\\NullTimeTracker',
    't3lib_timetrack' => 'TYPO3\\CMS\\Core\\TimeTracker\\TimeTracker',
    't3lib_tree_tca_abstracttcatreedataprovider' => 'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\AbstractTableConfigurationTreeDataProvider',
    't3lib_tree_tca_databasetreedataprovider' => 'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\DatabaseTreeDataProvider',
    't3lib_tree_tca_databasenode' => 'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\DatabaseTreeNode',
    't3lib_tree_tca_extjsarrayrenderer' => 'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\ExtJsArrayTreeRenderer',
    't3lib_tree_tca_tcatree' => 'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\TableConfigurationTree',
    't3lib_tree_tca_dataproviderfactory' => 'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\TreeDataProviderFactory',
    't3lib_tsstyleconfig' => 'TYPO3\\CMS\\Core\\TypoScript\\ConfigurationForm',
    't3lib_tsparser_ext' => 'TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService',
    't3lib_tsparser' => 'TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser',
    't3lib_tstemplate' => 'TYPO3\\CMS\\Core\\TypoScript\\TemplateService',
    't3lib_utility_array' => 'TYPO3\\CMS\\Core\\Utility\\ArrayUtility',
    't3lib_utility_client' => 'TYPO3\\CMS\\Core\\Utility\\ClientUtility',
    't3lib_exec' => 'TYPO3\\CMS\\Core\\Utility\\CommandUtility',
    't3lib_utility_command' => 'TYPO3\\CMS\\Core\\Utility\\CommandUtility',
    't3lib_utility_debug' => 'TYPO3\\CMS\\Core\\Utility\\DebugUtility',
    't3lib_diff' => 'TYPO3\\CMS\\Core\\Utility\\DiffUtility',
    't3lib_basicfilefunctions' => 'TYPO3\\CMS\\Core\\Utility\\File\\BasicFileUtility',
    't3lib_extfilefunctions' => 'TYPO3\\CMS\\Core\\Utility\\File\\ExtendedFileUtility',
    't3lib_extfilefunctions_processdatahook' => 'TYPO3\\CMS\\Core\\Utility\\File\\ExtendedFileUtilityProcessDataHookInterface',
    't3lib_div' => 'TYPO3\\CMS\\Core\\Utility\\GeneralUtility',
    't3lib_utility_http' => 'TYPO3\\CMS\\Core\\Utility\\HttpUtility',
    't3lib_utility_mail' => 'TYPO3\\CMS\\Core\\Utility\\MailUtility',
    't3lib_utility_math' => 'TYPO3\\CMS\\Core\\Utility\\MathUtility',
    't3lib_utility_monitor' => 'TYPO3\\CMS\\Core\\Utility\\MonitorUtility',
    't3lib_utility_path' => 'TYPO3\\CMS\\Core\\Utility\\PathUtility',
    't3lib_utility_phpoptions' => 'TYPO3\\CMS\\Core\\Utility\\PhpOptionsUtility',
    't3lib_utility_versionnumber' => 'TYPO3\\CMS\\Core\\Utility\\VersionNumberUtility',
    'sc_view_help' => 'TYPO3\\CMS\\Cshmanual\\Controller\\HelpModuleController',
    'tx_extbase_command_helpcommandcontroller' => 'TYPO3\\CMS\\Extbase\\Command\\HelpCommandController',
    'tx_extbase_configuration_abstractconfigurationmanager' => 'TYPO3\\CMS\\Extbase\\Configuration\\AbstractConfigurationManager',
    'tx_extbase_configuration_backendconfigurationmanager' => 'TYPO3\\CMS\\Extbase\\Configuration\\BackendConfigurationManager',
    'tx_extbase_configuration_configurationmanager' => 'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
    'tx_extbase_configuration_configurationmanagerinterface' => 'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface',
    'tx_extbase_configuration_exception' => 'TYPO3\\CMS\\Extbase\\Configuration\\Exception',
    'tx_extbase_configuration_exception_containerislocked' => 'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\ContainerIsLockedException',
    'tx_extbase_configuration_exception_invalidconfigurationtype' => 'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\InvalidConfigurationTypeException',
    'tx_extbase_configuration_exception_nosuchfile' => 'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\NoSuchFileException',
    'tx_extbase_configuration_exception_nosuchoption' => 'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\NoSuchOptionException',
    'tx_extbase_configuration_exception_parseerror' => 'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\ParseErrorException',
    'tx_extbase_configuration_frontendconfigurationmanager' => 'TYPO3\\CMS\\Extbase\\Configuration\\FrontendConfigurationManager',
    'tx_extbase_core_bootstrap' => 'TYPO3\\CMS\\Extbase\\Core\\Bootstrap',
    'tx_extbase_core_bootstrapinterface' => 'TYPO3\\CMS\\Extbase\\Core\\BootstrapInterface',
    'tx_extbase_domain_model_abstractfilecollection' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\AbstractFileCollection',
    'tx_extbase_domain_model_abstractfilefolder' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\AbstractFileFolder',
    'tx_extbase_domain_model_backenduser' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\BackendUser',
    'tx_extbase_domain_model_backendusergroup' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\BackendUserGroup',
    'tx_extbase_domain_model_category' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\Category',
    'tx_extbase_domain_model_file' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\File',
    'tx_extbase_domain_model_filemount' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FileMount',
    'tx_extbase_domain_model_filereference' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FileReference',
    'tx_extbase_domain_model_folder' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\Folder',
    'tx_extbase_domain_model_folderbasedfilecollection' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FolderBasedFileCollection',
    'tx_extbase_domain_model_frontenduser' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUser',
    'tx_extbase_domain_model_frontendusergroup' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup',
    'tx_extbase_domain_model_staticfilecollection' => 'TYPO3\\CMS\\Extbase\\Domain\\Model\\StaticFileCollection',
    'tx_extbase_domain_repository_backenduserrepository' => 'TYPO3\\CMS\\Extbase\\Domain\\Repository\\BackendUserGroupRepository',
    'tx_extbase_domain_repository_backendusergrouprepository' => 'TYPO3\\CMS\\Extbase\\Domain\\Repository\\BackendUserGroupRepository',
    'tx_extbase_domain_repository_categoryrepository' => 'TYPO3\\CMS\\Extbase\\Domain\\Repository\\CategoryRepository',
    'tx_extbase_domain_repository_filemountrepository' => 'TYPO3\\CMS\\Extbase\\Domain\\Repository\\FileMountRepository',
    'tx_extbase_domain_repository_frontendusergrouprepository' => 'TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserGroupRepository',
    'tx_extbase_domain_repository_frontenduserrepository' => 'TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserRepository',
    'tx_extbase_domainobject_abstractdomainobject' => 'TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject',
    'tx_extbase_domainobject_abstractentity' => 'TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity',
    'tx_extbase_domainobject_abstractvalueobject' => 'TYPO3\\CMS\\Extbase\\DomainObject\\AbstractValueObject',
    'tx_extbase_domainobject_domainobjectinterface' => 'TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface',
    'tx_extbase_error_error' => 'TYPO3\\CMS\\Extbase\\Error\\Error',
    'tx_extbase_error_message' => 'TYPO3\\CMS\\Extbase\\Error\\Message',
    'tx_extbase_error_notice' => 'TYPO3\\CMS\\Extbase\\Error\\Notice',
    'tx_extbase_error_result' => 'TYPO3\\CMS\\Extbase\\Error\\Result',
    'tx_extbase_error_warning' => 'TYPO3\\CMS\\Extbase\\Error\\Warning',
    'tx_extbase_exception' => 'TYPO3\\CMS\\Extbase\\Exception',
    'tx_extbase_mvc_cli_command' => 'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command',
    'tx_extbase_mvc_cli_commandargumentdefinition' => 'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\CommandArgumentDefinition',
    'tx_extbase_mvc_cli_commandmanager' => 'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\CommandManager',
    'tx_extbase_mvc_cli_request' => 'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Request',
    'tx_extbase_mvc_cli_requestbuilder' => 'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\RequestBuilder',
    'tx_extbase_mvc_cli_requesthandler' => 'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\RequestHandler',
    'tx_extbase_mvc_cli_response' => 'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Response',
    'tx_extbase_mvc_controller_abstractcontroller' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\AbstractController',
    'tx_extbase_mvc_controller_actioncontroller' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ActionController',
    'tx_extbase_mvc_controller_argument' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Argument',
    'tx_extbase_mvc_controller_argumenterror' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ArgumentError',
    'tx_extbase_mvc_controller_arguments' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Arguments',
    'tx_extbase_mvc_controller_argumentsvalidator' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ArgumentsValidator',
    'tx_extbase_mvc_controller_commandcontroller' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\CommandController',
    'tx_extbase_mvc_controller_commandcontrollerinterface' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\CommandControllerInterface',
    'tx_extbase_mvc_controller_controllercontext' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerContext',
    'tx_extbase_mvc_controller_controllerinterface' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerInterface',
    'tx_extbase_mvc_controller_exception_requiredargumentmissingexception' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Exception\\RequiredArgumentMissingException',
    'tx_extbase_mvc_controller_flashmessages' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\FlashMessageContainer',
    'tx_extbase_mvc_controller_mvcpropertymappingconfiguration' => 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\MvcPropertyMappingConfiguration',
    'tx_extbase_mvc_dispatcher' => 'TYPO3\\CMS\\Extbase\\Mvc\\Dispatcher',
    'tx_extbase_mvc_exception' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception',
    'tx_extbase_mvc_exception_ambiguouscommandidentifier' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\AmbiguousCommandIdentifierException',
    'tx_extbase_mvc_exception_command' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\CommandException',
    'tx_extbase_mvc_exception_infiniteloop' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InfiniteLoopException',
    'tx_extbase_mvc_exception_invalidactionname' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidActionNameException',
    'tx_extbase_mvc_exception_invalidargumentmixing' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidArgumentMixingException',
    'tx_extbase_mvc_exception_invalidargumentname' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidArgumentNameException',
    'tx_extbase_mvc_exception_invalidargumenttype' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidArgumentTypeException',
    'tx_extbase_mvc_exception_invalidargumentvalue' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidArgumentValueException',
    'tx_extbase_mvc_exception_invalidcommandidentifier' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidCommandIdentifierException',
    'tx_extbase_mvc_exception_invalidcontroller' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidControllerException',
    'tx_extbase_mvc_exception_invalidcontrollername' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidControllerNameException',
    'tx_extbase_mvc_exception_invalidextensionname' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidExtensionNameException',
    'tx_extbase_mvc_exception_invalidmarker' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidMarkerException',
    'tx_extbase_mvc_exception_invalidornorequesthash' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidOrNoRequestHashException',
    'tx_extbase_mvc_exception_invalidrequestmethod' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidRequestMethodException',
    'tx_extbase_mvc_exception_invalidrequesttype' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidRequestTypeException',
    'tx_extbase_mvc_exception_invalidtemplateresource' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidTemplateResourceException',
    'tx_extbase_mvc_exception_invaliduripattern' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidUriPatternException',
    'tx_extbase_mvc_exception_invalidviewhelper' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidViewHelperException',
    'tx_extbase_mvc_exception_nosuchaction' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\NoSuchActionException',
    'tx_extbase_mvc_exception_nosuchargument' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\NoSuchArgumentException',
    'tx_extbase_mvc_exception_nosuchcommand' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\NoSuchCommandException',
    'tx_extbase_mvc_exception_nosuchcontroller' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\NoSuchControllerException',
    'tx_extbase_mvc_exception_requiredargumentmissing' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\RequiredArgumentMissingException',
    'tx_extbase_mvc_exception_stopaction' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\StopActionException',
    'tx_extbase_mvc_exception_unsupportedrequesttype' => 'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\UnsupportedRequestTypeException',
    'tx_extbase_mvc_request' => 'TYPO3\\CMS\\Extbase\\Mvc\\Request',
    'tx_extbase_mvc_requesthandlerinterface' => 'TYPO3\\CMS\\Extbase\\Mvc\\RequestHandlerInterface',
    'tx_extbase_mvc_requesthandlerresolver' => 'TYPO3\\CMS\\Extbase\\Mvc\\RequestHandlerResolver',
    'tx_extbase_mvc_requestinterface' => 'TYPO3\\CMS\\Extbase\\Mvc\\RequestInterface',
    'tx_extbase_mvc_response' => 'TYPO3\\CMS\\Extbase\\Mvc\\Response',
    'tx_extbase_mvc_responseinterface' => 'TYPO3\\CMS\\Extbase\\Mvc\\ResponseInterface',
    'tx_extbase_mvc_view_abstractview' => 'TYPO3\\CMS\\Extbase\\Mvc\\View\\AbstractView',
    'tx_extbase_mvc_view_emptyview' => 'TYPO3\\CMS\\Extbase\\Mvc\\View\\EmptyView',
    'tx_extbase_mvc_view_notfoundview' => 'TYPO3\\CMS\\Extbase\\Mvc\\View\\NotFoundView',
    'tx_extbase_mvc_view_viewinterface' => 'TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface',
    'tx_extbase_mvc_web_abstractrequesthandler' => 'TYPO3\\CMS\\Extbase\\Mvc\\Web\\AbstractRequestHandler',
    'tx_extbase_mvc_web_backendrequesthandler' => 'TYPO3\\CMS\\Extbase\\Mvc\\Web\\BackendRequestHandler',
    'tx_extbase_mvc_web_frontendrequesthandler' => 'TYPO3\\CMS\\Extbase\\Mvc\\Web\\FrontendRequestHandler',
    'tx_extbase_mvc_web_request' => 'TYPO3\\CMS\\Extbase\\Mvc\\Web\\Request',
    'tx_extbase_mvc_web_requestbuilder' => 'TYPO3\\CMS\\Extbase\\Mvc\\Web\\RequestBuilder',
    'tx_extbase_mvc_web_response' => 'TYPO3\\CMS\\Extbase\\Mvc\\Web\\Response',
    'tx_extbase_mvc_web_routing_uribuilder' => 'TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder',
    'tx_extbase_object_container_classinfo' => 'TYPO3\\CMS\\Extbase\\Object\\Container\\ClassInfo',
    'tx_extbase_object_container_classinfocache' => 'TYPO3\\CMS\\Extbase\\Object\\Container\\ClassInfoCache',
    'tx_extbase_object_container_classinfofactory' => 'TYPO3\\CMS\\Extbase\\Object\\Container\\ClassInfoFactory',
    'tx_extbase_object_container_container' => 'TYPO3\\CMS\\Extbase\\Object\\Container\\Container',
    'tx_extbase_object_container_exception_cannotinitializecacheexception' => 'TYPO3\\CMS\\Extbase\\Object\\Container\\Exception\\CannotInitializeCacheException',
    'tx_extbase_object_container_exception_toomanyrecursionlevelsexception' => 'TYPO3\\CMS\\Extbase\\Object\\Container\\Exception\\TooManyRecursionLevelsException',
    'tx_extbase_object_container_exception_unknownobjectexception' => 'TYPO3\\CMS\\Extbase\\Object\\Container\\Exception\\UnknownObjectException',
    'tx_extbase_object_exception' => 'TYPO3\\CMS\\Extbase\\Object\\Exception',
    'tx_extbase_object_exception_cannotbuildobject' => 'TYPO3\\CMS\\Extbase\\Object\\Exception\\CannotBuildObjectException',
    'tx_extbase_object_exception_cannotreconstituteobject' => 'TYPO3\\CMS\\Extbase\\Object\\Exception\\CannotReconstituteObjectException',
    'tx_extbase_object_exception_wrongscope' => 'TYPO3\\CMS\\Extbase\\Object\\Exception\\WrongScopeException',
    'tx_extbase_object_invalidclass' => 'TYPO3\\CMS\\Extbase\\Object\\InvalidClassException',
    'tx_extbase_object_invalidobjectconfiguration' => 'TYPO3\\CMS\\Extbase\\Object\\InvalidObjectConfigurationException',
    'tx_extbase_object_invalidobject' => 'TYPO3\\CMS\\Extbase\\Object\\InvalidObjectException',
    'tx_extbase_object_objectalreadyregistered' => 'TYPO3\\CMS\\Extbase\\Object\\ObjectAlreadyRegisteredException',
    'tx_extbase_object_objectmanager' => 'TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
    'tx_extbase_object_objectmanagerinterface' => 'TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface',
    'tx_extbase_object_unknownclass' => 'TYPO3\\CMS\\Extbase\\Object\\UnknownClassException',
    'tx_extbase_object_unknowninterface' => 'TYPO3\\CMS\\Extbase\\Object\\UnknownInterfaceException',
    'tx_extbase_object_unresolveddependencies' => 'TYPO3\\CMS\\Extbase\\Object\\UnresolvedDependenciesException',
    'tx_extbase_persistence_backend' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend',
    'tx_extbase_persistence_backendinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\BackendInterface',
    'tx_extbase_persistence_exception' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception',
    'tx_extbase_persistence_exception_cleanstatenotmemorized' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\CleanStateNotMemorizedException',
    'tx_extbase_persistence_exception_illegalobjecttype' => 'TYPO3\\CMS\\Extbase\\Persistence\\Exception\\IllegalObjectTypeException',
    'tx_extbase_persistence_exception_invalidclass' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\InvalidClassException',
    'tx_extbase_persistence_exception_invalidnumberofconstraints' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\InvalidNumberOfConstraintsException',
    'tx_extbase_persistence_exception_invalidpropertytype' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\InvalidPropertyTypeException',
    'tx_extbase_persistence_exception_missingbackend' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\MissingBackendException',
    'tx_extbase_persistence_exception_repositoryexception' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\RepositoryException',
    'tx_extbase_persistence_exception_toodirty' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\TooDirtyException',
    'tx_extbase_persistence_exception_unexpectedtypeexception' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\UnexpectedTypeException',
    'tx_extbase_persistence_exception_unknownobject' => 'TYPO3\\CMS\\Extbase\\Persistence\\Exception\\UnknownObjectException',
    'tx_extbase_persistence_exception_unsupportedmethod' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\UnsupportedMethodException',
    'tx_extbase_persistence_exception_unsupportedorder' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\UnsupportedOrderException',
    'tx_extbase_persistence_exception_unsupportedrelation' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\UnsupportedRelationException',
    'tx_extbase_persistence_generic_exception_inconsistentquerysettings' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\InconsistentQuerySettingsException',
    'tx_extbase_persistence_identitymap' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\IdentityMap',
    'tx_extbase_persistence_lazyloadingproxy' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LazyLoadingProxy',
    'tx_extbase_persistence_lazyobjectstorage' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LazyObjectStorage',
    'tx_extbase_persistence_loadingstrategyinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LoadingStrategyInterface',
    'tx_extbase_persistence_mapper_columnmap' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap',
    'tx_extbase_persistence_mapper_datamap' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMap',
    'tx_extbase_persistence_mapper_datamapfactory' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory',
    'tx_extbase_persistence_mapper_datamapper' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper',
    'tx_extbase_persistence_objectmonitoringinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectMonitoringInterface',
    'tx_extbase_persistence_objectstorage' => 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage',
    'tx_extbase_persistence_manager' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager',
    'tx_extbase_persistence_persistencemanagerinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface',
    'tx_extbase_persistence_managerinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface',
    'tx_extbase_persistence_propertytype' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PropertyType',
    'tx_extbase_persistence_qom_andinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\AndInterface',
    'tx_extbase_persistence_qom_bindvariablevalue' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\BindVariableValue',
    'tx_extbase_persistence_qom_bindvariablevalueinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\BindVariableValueInterface',
    'tx_extbase_persistence_qom_comparison' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Comparison',
    'tx_extbase_persistence_qom_comparisoninterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\ComparisonInterface',
    'tx_extbase_persistence_qom_constraint' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Constraint',
    'tx_extbase_persistence_qom_constraintinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\ConstraintInterface',
    'tx_extbase_persistence_qom_dynamicoperand' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\DynamicOperand',
    'tx_extbase_persistence_qom_dynamicoperandinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\DynamicOperandInterface',
    'tx_extbase_persistence_qom_equijoincondition' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\EquiJoinCondition',
    'tx_extbase_persistence_qom_equijoinconditioninterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\EquiJoinConditionInterface',
    'tx_extbase_persistence_qom_join' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Join',
    'tx_extbase_persistence_qom_joinconditioninterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\JoinConditionInterface',
    'tx_extbase_persistence_qom_joininterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\JoinInterface',
    'tx_extbase_persistence_qom_logicaland' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LogicalAnd',
    'tx_extbase_persistence_qom_logicalnot' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LogicalNot',
    'tx_extbase_persistence_qom_logicalor' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LogicalOr',
    'tx_extbase_persistence_qom_lowercase' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LowerCase',
    'tx_extbase_persistence_qom_lowercaseinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LowerCaseInterface',
    'tx_extbase_persistence_qom_notinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\NotInterface',
    'tx_extbase_persistence_qom_operand' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Operand',
    'tx_extbase_persistence_qom_operandinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\OperandInterface',
    'tx_extbase_persistence_qom_ordering' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Ordering',
    'tx_extbase_persistence_qom_orderinginterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\OrderingInterface',
    'tx_extbase_persistence_qom_orinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\OrInterface',
    'tx_extbase_persistence_qom_propertyvalue' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\PropertyValue',
    'tx_extbase_persistence_qom_propertyvalueinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\PropertyValueInterface',
    'tx_extbase_persistence_qom_queryobjectmodelconstantsinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\QueryObjectModelConstantsInterface',
    'tx_extbase_persistence_qom_queryobjectmodelfactory' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\QueryObjectModelFactory',
    'tx_extbase_persistence_qom_queryobjectmodelfactoryinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\QueryObjectModelFactoryInterface',
    'tx_extbase_persistence_qom_selector' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Selector',
    'tx_extbase_persistence_qom_selectorinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\SelectorInterface',
    'tx_extbase_persistence_qom_sourceinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\SourceInterface',
    'tx_extbase_persistence_qom_statement' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Statement',
    'tx_extbase_persistence_qom_staticoperand' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\StaticOperand',
    'tx_extbase_persistence_qom_staticoperandinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\StaticOperandInterface',
    'tx_extbase_persistence_qom_uppercase' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\UpperCase',
    'tx_extbase_persistence_qom_uppercaseinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\UpperCaseInterface',
    'tx_extbase_persistence_query' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Query',
    'tx_extbase_persistence_queryfactory' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryFactory',
    'tx_extbase_persistence_queryfactoryinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryFactoryInterface',
    'tx_extbase_persistence_queryinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\QueryInterface',
    'tx_extbase_persistence_queryresult' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryResult',
    'tx_extbase_persistence_queryresultinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface',
    'tx_extbase_persistence_querysettingsinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface',
    'tx_extbase_persistence_repository' => 'TYPO3\\CMS\\Extbase\\Persistence\\Repository',
    'tx_extbase_persistence_repositoryinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\RepositoryInterface',
    'tx_extbase_persistence_session' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Session',
    'tx_extbase_persistence_storage_backendinterface' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\BackendInterface',
    'tx_extbase_persistence_storage_exception_badconstraint' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Exception\\BadConstraintException',
    'tx_extbase_persistence_storage_exception_sqlerror' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Exception\\SqlErrorException',
    'tx_extbase_persistence_storage_typo3dbbackend' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend',
    'tx_extbase_persistence_typo3querysettings' => 'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings',
    'tx_extbase_property_exception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception',
    'tx_extbase_property_exception_duplicateobjectexception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\DuplicateObjectException',
    'tx_extbase_property_exception_duplicatetypeconverterexception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\DuplicateTypeConverterException',
    'tx_extbase_property_exception_formatnotsupportedexception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\FormatNotSupportedException',
    'tx_extbase_property_exception_invaliddatatypeexception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidDataTypeException',
    'tx_extbase_property_exception_invalidformatexception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidFormatException',
    'tx_extbase_property_exception_invalidpropertyexception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidPropertyException',
    'tx_extbase_property_exception_invalidpropertymappingconfigurationexception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidPropertyMappingConfigurationException',
    'tx_extbase_property_exception_invalidsource' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidSourceException',
    'tx_extbase_property_exception_invalidsourceexception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidSourceException',
    'tx_extbase_property_exception_invalidtarget' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidTargetException',
    'tx_extbase_property_exception_invalidtargetexception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidTargetException',
    'tx_extbase_property_exception_targetnotfoundexception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\TargetNotFoundException',
    'tx_extbase_property_exception_typeconverterexception' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\TypeConverterException',
    'tx_extbase_property_mapper' => 'TYPO3\\CMS\\Extbase\\Property\\Mapper',
    'tx_extbase_property_mappingresults' => 'TYPO3\\CMS\\Extbase\\Property\\MappingResults',
    'tx_extbase_property_propertymapper' => 'TYPO3\\CMS\\Extbase\\Property\\PropertyMapper',
    'tx_extbase_property_propertymappingconfiguration' => 'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration',
    'tx_extbase_property_propertymappingconfigurationbuilder' => 'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfigurationBuilder',
    'tx_extbase_property_propertymappingconfigurationinterface' => 'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfigurationInterface',
    'tx_extbase_property_typeconverter_abstractfilecollectionconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\AbstractFileCollectionConverter',
    'tx_extbase_property_typeconverter_abstractfilefolderconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\AbstractFileFolderConverter',
    'tx_extbase_property_typeconverter_abstracttypeconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\AbstractTypeConverter',
    'tx_extbase_property_typeconverter_arrayconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\ArrayConverter',
    'tx_extbase_property_typeconverter_booleanconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\BooleanConverter',
    'tx_extbase_property_typeconverter_datetimeconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter',
    'tx_extbase_property_typeconverter_fileconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\FileConverter',
    'tx_extbase_property_typeconverter_filereferenceconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\FileReferenceConverter',
    'tx_extbase_property_typeconverter_floatconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\FloatConverter',
    'tx_extbase_property_typeconverter_folderbasedfilecollectionconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\FolderBasedFileCollectionConverter',
    'tx_extbase_property_typeconverter_folderconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\FolderConverter',
    'tx_extbase_property_typeconverter_integerconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\IntegerConverter',
    'tx_extbase_property_typeconverter_objectstorageconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\ObjectStorageConverter',
    'tx_extbase_property_typeconverter_persistentobjectconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter',
    'tx_extbase_property_typeconverter_staticfilecollectionconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\StaticFileCollectionConverter',
    'tx_extbase_property_typeconverter_stringconverter' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\StringConverter',
    'tx_extbase_property_typeconverterinterface' => 'TYPO3\\CMS\\Extbase\\Property\\TypeConverterInterface',
    'tx_extbase_reflection_classreflection' => 'TYPO3\\CMS\\Extbase\\Reflection\\ClassReflection',
    'tx_extbase_reflection_classschema' => 'TYPO3\\CMS\\Extbase\\Reflection\\ClassSchema',
    'tx_extbase_reflection_doccommentparser' => 'TYPO3\\CMS\\Extbase\\Reflection\\DocCommentParser',
    'tx_extbase_reflection_exception' => 'TYPO3\\CMS\\Extbase\\Reflection\\Exception',
    'tx_extbase_reflection_exception_invalidpropertytype' => 'TYPO3\\CMS\\Extbase\\Reflection\\Exception\\InvalidPropertyTypeException',
    'tx_extbase_reflection_exception_propertynotaccessibleexception' => 'TYPO3\\CMS\\Extbase\\Reflection\\Exception\\PropertyNotAccessibleException',
    'tx_extbase_reflection_exception_unknownclass' => 'TYPO3\\CMS\\Extbase\\Reflection\\Exception\\UnknownClassException',
    'tx_extbase_reflection_methodreflection' => 'TYPO3\\CMS\\Extbase\\Reflection\\MethodReflection',
    'tx_extbase_reflection_objectaccess' => 'TYPO3\\CMS\\Extbase\\Reflection\\ObjectAccess',
    'tx_extbase_reflection_parameterreflection' => 'TYPO3\\CMS\\Extbase\\Reflection\\ParameterReflection',
    'tx_extbase_reflection_propertyreflection' => 'TYPO3\\CMS\\Extbase\\Reflection\\PropertyReflection',
    'tx_extbase_reflection_service' => 'TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService',
    'tx_extbase_scheduler_fieldprovider' => 'TYPO3\\CMS\\Extbase\\Scheduler\\FieldProvider',
    'tx_extbase_scheduler_task' => 'TYPO3\\CMS\\Extbase\\Scheduler\\Task',
    'tx_extbase_scheduler_taskexecutor' => 'TYPO3\\CMS\\Extbase\\Scheduler\\TaskExecutor',
    'tx_extbase_security_channel_requesthashservice' => 'TYPO3\\CMS\\Extbase\\Security\\Channel\\RequestHashService',
    'tx_extbase_security_cryptography_hashservice' => 'TYPO3\\CMS\\Extbase\\Security\\Cryptography\\HashService',
    'tx_extbase_security_exception' => 'TYPO3\\CMS\\Extbase\\Security\\Exception',
    'tx_extbase_security_exception_invalidargumentforhashgeneration' => 'TYPO3\\CMS\\Extbase\\Security\\Exception\\InvalidArgumentForHashGenerationException',
    'tx_extbase_security_exception_invalidargumentforrequesthashgeneration' => 'TYPO3\\CMS\\Extbase\\Security\\Exception\\InvalidArgumentForRequestHashGenerationException',
    'tx_extbase_security_exception_invalidhash' => 'TYPO3\\CMS\\Extbase\\Security\\Exception\\InvalidHashException',
    'tx_extbase_security_exception_syntacticallywrongrequesthash' => 'TYPO3\\CMS\\Extbase\\Security\\Exception\\SyntacticallyWrongRequestHashException',
    'tx_extbase_service_cacheservice' => 'TYPO3\\CMS\\Extbase\\Service\\CacheService',
    'tx_extbase_service_extensionservice' => 'TYPO3\\CMS\\Extbase\\Service\\ExtensionService',
    'tx_extbase_service_flexformservice' => 'TYPO3\\CMS\\Extbase\\Service\\FlexFormService',
    'tx_extbase_service_typehandlingservice' => 'TYPO3\\CMS\\Extbase\\Service\\TypeHandlingService',
    'tx_extbase_service_typoscriptservice' => 'TYPO3\\CMS\\Extbase\\Service\\TypoScriptService',
    'tx_extbase_signalslot_dispatcher' => 'TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher',
    'tx_extbase_signalslot_exception_invalidslotexception' => 'TYPO3\\CMS\\Extbase\\SignalSlot\\Exception\\InvalidSlotException',
    'tx_extbase_tests_unit_basetestcase' => 'TYPO3\\CMS\\Core\\Tests\\UnitTestCase',
    'typo3\\cms\\extbase\\tests\\unit\\basetestcase' => 'TYPO3\\CMS\\Core\\Tests\\UnitTestCase',
    'tx_extbase_utility_arrays' => 'TYPO3\\CMS\\Extbase\\Utility\\ArrayUtility',
    'tx_extbase_utility_debugger' => 'TYPO3\\CMS\\Extbase\\Utility\\DebuggerUtility',
    'tx_extbase_utility_extbaserequirementscheck' => 'TYPO3\\CMS\\Extbase\\Utility\\ExtbaseRequirementsCheckUtility',
    'tx_extbase_utility_extension' => 'TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility',
    'tx_extbase_utility_frontendsimulator' => 'TYPO3\\CMS\\Extbase\\Utility\\FrontendSimulatorUtility',
    'tx_extbase_utility_localization' => 'TYPO3\\CMS\\Extbase\\Utility\\LocalizationUtility',
    'tx_extbase_validation_error' => 'TYPO3\\CMS\\Extbase\\Validation\\Error',
    'tx_extbase_validation_exception' => 'TYPO3\\CMS\\Extbase\\Validation\\Exception',
    'tx_extbase_validation_exception_invalidsubject' => 'TYPO3\\CMS\\Extbase\\Validation\\Exception\\InvalidSubjectException',
    'tx_extbase_validation_exception_invalidvalidationconfiguration' => 'TYPO3\\CMS\\Extbase\\Validation\\Exception\\InvalidValidationConfigurationException',
    'tx_extbase_validation_exception_invalidvalidationoptions' => 'TYPO3\\CMS\\Extbase\\Validation\\Exception\\InvalidValidationOptionsException',
    'tx_extbase_validation_exception_nosuchvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Exception\\NoSuchValidatorException',
    'tx_extbase_validation_exception_novalidatorfound' => 'TYPO3\\CMS\\Extbase\\Validation\\Exception\\NoValidatorFoundException',
    'tx_extbase_validation_propertyerror' => 'TYPO3\\CMS\\Extbase\\Validation\\PropertyError',
    'tx_extbase_validation_validator_abstractcompositevalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\AbstractCompositeValidator',
    'tx_extbase_validation_validator_abstractobjectvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\AbstractObjectValidator',
    'tx_extbase_validation_validator_abstractvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\AbstractValidator',
    'tx_extbase_validation_validator_alphanumericvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\AlphanumericValidator',
    'tx_extbase_validation_validator_conjunctionvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator',
    'tx_extbase_validation_validator_datetimevalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\DateTimeValidator',
    'tx_extbase_validation_validator_disjunctionvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\DisjunctionValidator',
    'tx_extbase_validation_validator_emailaddressvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\EmailAddressValidator',
    'tx_extbase_validation_validator_floatvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\FloatValidator',
    'tx_extbase_validation_validator_genericobjectvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\GenericObjectValidator',
    'tx_extbase_validation_validator_integervalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\IntegerValidator',
    'tx_extbase_validation_validator_notemptyvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\NotEmptyValidator',
    'tx_extbase_validation_validator_numberrangevalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator',
    'tx_extbase_validation_validator_numbervalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberValidator',
    'tx_extbase_validation_validator_objectvalidatorinterface' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\ObjectValidatorInterface',
    'tx_extbase_validation_validator_rawvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\RawValidator',
    'tx_extbase_validation_validator_regularexpressionvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\RegularExpressionValidator',
    'tx_extbase_validation_validator_stringlengthvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator',
    'tx_extbase_validation_validator_stringvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringValidator',
    'tx_extbase_validation_validator_textvalidator' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator',
    'tx_extbase_validation_validator_validatorinterface' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface',
    'tx_extbase_validation_validatorresolver' => 'TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver',
    'tx_em_tasks_updateextensionlist' => 'TYPO3\\CMS\\Extensionmanager\\Task\\UpdateExtensionListTask',
    'tx_fluid_compatibility_docbookgeneratorservice' => 'TYPO3\\CMS\\Fluid\\Compatibility\\DocbookGeneratorService',
    'tx_fluid_compatibility_templateparserbuilder' => 'TYPO3\\CMS\\Fluid\\Compatibility\\TemplateParserBuilder',
    'tx_fluid_core_compiler_abstractcompiledtemplate' => 'TYPO3\\CMS\\Fluid\\Core\\Compiler\\AbstractCompiledTemplate',
    'tx_fluid_core_compiler_templatecompiler' => 'TYPO3\\CMS\\Fluid\\Core\\Compiler\\TemplateCompiler',
    'tx_fluid_core_exception' => 'TYPO3\\CMS\\Fluid\\Core\\Exception',
    'tx_fluid_core_parser_configuration' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\Configuration',
    'tx_fluid_core_parser_exception' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\Exception',
    'tx_fluid_core_parser_interceptor_escape' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\Interceptor\\Escape',
    'tx_fluid_core_parser_interceptorinterface' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\InterceptorInterface',
    'tx_fluid_core_parser_parsedtemplateinterface' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsedTemplateInterface',
    'tx_fluid_core_parser_parsingstate' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState',
    'tx_fluid_core_parser_syntaxtree_abstractnode' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\AbstractNode',
    'tx_fluid_core_parser_syntaxtree_arraynode' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ArrayNode',
    'tx_fluid_core_parser_syntaxtree_booleannode' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\BooleanNode',
    'tx_fluid_core_parser_syntaxtree_nodeinterface' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\NodeInterface',
    'tx_fluid_core_parser_syntaxtree_objectaccessornode' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ObjectAccessorNode',
    'tx_fluid_core_parser_syntaxtree_renderingcontextawareinterface' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RenderingContextAwareInterface',
    'tx_fluid_core_parser_syntaxtree_rootnode' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RootNode',
    'tx_fluid_core_parser_syntaxtree_textnode' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\TextNode',
    'tx_fluid_core_parser_syntaxtree_viewhelpernode' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode',
    'tx_fluid_core_parser_templateparser' => 'TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser',
    'tx_fluid_core_rendering_renderingcontext' => 'TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContext',
    'tx_fluid_core_rendering_renderingcontextinterface' => 'TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContextInterface',
    'tx_fluid_core_viewhelper_abstractconditionviewhelper' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractConditionViewHelper',
    'tx_fluid_core_viewhelper_abstracttagbasedviewhelper' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractTagBasedViewHelper',
    'tx_fluid_core_viewhelper_abstractviewhelper' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractViewHelper',
    'tx_fluid_core_viewhelper_argumentdefinition' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\ArgumentDefinition',
    'tx_fluid_core_viewhelper_arguments' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Arguments',
    'tx_fluid_core_viewhelper_exception' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Exception',
    'tx_fluid_core_viewhelper_exception_invalidvariableexception' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Exception\\InvalidVariableException',
    'tx_fluid_core_viewhelper_exception_renderingcontextnotaccessibleexception' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Exception\\RenderingContextNotAccessibleException',
    'tx_fluid_core_viewhelper_facets_childnodeaccessinterface' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Facets\\ChildNodeAccessInterface',
    'tx_fluid_core_viewhelper_facets_compilableinterface' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Facets\\CompilableInterface',
    'tx_fluid_core_viewhelper_facets_postparseinterface' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Facets\\PostParseInterface',
    'tx_fluid_core_viewhelper_tagbuilder' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder',
    'tx_fluid_core_viewhelper_templatevariablecontainer' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TemplateVariableContainer',
    'tx_fluid_core_viewhelper_viewhelperinterface' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\ViewHelperInterface',
    'tx_fluid_core_viewhelper_viewhelpervariablecontainer' => 'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\ViewHelperVariableContainer',
    'tx_fluid_core_widget_abstractwidgetcontroller' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\AbstractWidgetController',
    'tx_fluid_core_widget_abstractwidgetviewhelper' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\AbstractWidgetViewHelper',
    'tx_fluid_core_widget_ajaxwidgetcontextholder' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\AjaxWidgetContextHolder',
    'tx_fluid_core_widget_bootstrap' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\Bootstrap',
    'tx_fluid_core_widget_exception' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\Exception',
    'tx_fluid_core_widget_exception_missingcontrollerexception' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\Exception\\MissingControllerException',
    'tx_fluid_core_widget_exception_renderingcontextnotfoundexception' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\Exception\\RenderingContextNotFoundException',
    'tx_fluid_core_widget_exception_widgetcontextnotfoundexception' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\Exception\\WidgetContextNotFoundException',
    'tx_fluid_core_widget_exception_widgetrequestnotfoundexception' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\Exception\\WidgetRequestNotFoundException',
    'tx_fluid_core_widget_widgetcontext' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetContext',
    'tx_fluid_core_widget_widgetrequest' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequest',
    'tx_fluid_core_widget_widgetrequestbuilder' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequestBuilder',
    'tx_fluid_core_widget_widgetrequesthandler' => 'TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequestHandler',
    'tx_fluid_exception' => 'TYPO3\\CMS\\Fluid\\Exception',
    'tx_fluid_fluid' => 'TYPO3\\CMS\\Fluid\\Fluid',
    'tx_fluid_service_docbookgenerator' => 'TYPO3\\CMS\\Fluid\\Service\\DocbookGenerator',
    'tx_fluid_view_abstracttemplateview' => 'TYPO3\\CMS\\Fluid\\View\\AbstractTemplateView',
    'tx_fluid_view_exception' => 'TYPO3\\CMS\\Fluid\\View\\Exception',
    'tx_fluid_view_exception_invalidsectionexception' => 'TYPO3\\CMS\\Fluid\\View\\Exception\\InvalidSectionException',
    'tx_fluid_view_exception_invalidtemplateresourceexception' => 'TYPO3\\CMS\\Fluid\\View\\Exception\\InvalidTemplateResourceException',
    'tx_fluid_view_standaloneview' => 'TYPO3\\CMS\\Fluid\\View\\StandaloneView',
    'tx_fluid_view_templateview' => 'TYPO3\\CMS\\Fluid\\View\\TemplateView',
    'tx_fluid_viewhelpers_aliasviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\AliasViewHelper',
    'tx_fluid_viewhelpers_baseviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\BaseViewHelper',
    'tx_fluid_viewhelpers_be_abstractbackendviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\AbstractBackendViewHelper',
    'tx_fluid_viewhelpers_be_buttons_cshviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Buttons\\CshViewHelper',
    'tx_fluid_viewhelpers_be_buttons_iconviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Buttons\\IconViewHelper',
    'tx_fluid_viewhelpers_be_buttons_shortcutviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Buttons\\ShortcutViewHelper',
    'tx_fluid_viewhelpers_be_containerviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\ContainerViewHelper',
    'tx_fluid_viewhelpers_be_menus_actionmenuitemviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Menus\\ActionMenuItemViewHelper',
    'tx_fluid_viewhelpers_be_menus_actionmenuviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Menus\\ActionMenuViewHelper',
    'tx_fluid_viewhelpers_be_pageinfoviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\PageInfoViewHelper',
    'tx_fluid_viewhelpers_be_pagepathviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\PagePathViewHelper',
    'tx_fluid_viewhelpers_be_security_ifauthenticatedviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Security\\IfAuthenticatedViewHelper',
    'tx_fluid_viewhelpers_be_security_ifhasroleviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Security\\IfHasRoleViewHelper',
    'tx_fluid_viewhelpers_be_tablelistviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\TableListViewHelper',
    'tx_fluid_viewhelpers_cobjectviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\CObjectViewHelper',
    'tx_fluid_viewhelpers_commentviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\CommentViewHelper',
    'tx_fluid_viewhelpers_countviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\CountViewHelper',
    'tx_fluid_viewhelpers_cycleviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\CycleViewHelper',
    'tx_fluid_viewhelpers_debugviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\DebugViewHelper',
    'tx_fluid_viewhelpers_elseviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\ElseViewHelper',
    'tx_fluid_viewhelpers_flashmessagesviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\FlashMessagesViewHelper',
    'tx_fluid_viewhelpers_form_abstractformfieldviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\AbstractFormFieldViewHelper',
    'tx_fluid_viewhelpers_form_abstractformviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\AbstractFormViewHelper',
    'tx_fluid_viewhelpers_form_checkboxviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\CheckboxViewHelper',
    'tx_fluid_viewhelpers_form_errorsviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\ErrorsViewHelper',
    'tx_fluid_viewhelpers_form_hiddenviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\HiddenViewHelper',
    'tx_fluid_viewhelpers_form_passwordviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\PasswordViewHelper',
    'tx_fluid_viewhelpers_form_radioviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\RadioViewHelper',
    'tx_fluid_viewhelpers_form_selectviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\SelectViewHelper',
    'tx_fluid_viewhelpers_form_submitviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\SubmitViewHelper',
    'tx_fluid_viewhelpers_form_textareaviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\TextareaViewHelper',
    'tx_fluid_viewhelpers_form_textfieldviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\TextfieldViewHelper',
    'tx_fluid_viewhelpers_form_uploadviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\UploadViewHelper',
    'tx_fluid_viewhelpers_form_validationresultsviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\ValidationResultsViewHelper',
    'tx_fluid_viewhelpers_format_abstractencodingviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\AbstractEncodingViewHelper',
    'tx_fluid_viewhelpers_format_cdataviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CdataViewHelper',
    'tx_fluid_viewhelpers_format_cropviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CropViewHelper',
    'tx_fluid_viewhelpers_format_currencyviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper',
    'tx_fluid_viewhelpers_format_dateviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\DateViewHelper',
    'tx_fluid_viewhelpers_format_htmlentitiesdecodeviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlentitiesDecodeViewHelper',
    'tx_fluid_viewhelpers_format_htmlentitiesviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlentitiesViewHelper',
    'tx_fluid_viewhelpers_format_htmlspecialcharsviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlspecialcharsViewHelper',
    'tx_fluid_viewhelpers_format_htmlviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlViewHelper',
    'tx_fluid_viewhelpers_format_nl2brviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\Nl2brViewHelper',
    'tx_fluid_viewhelpers_format_numberviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\NumberViewHelper',
    'tx_fluid_viewhelpers_format_paddingviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\PaddingViewHelper',
    'tx_fluid_viewhelpers_format_printfviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\PrintfViewHelper',
    'tx_fluid_viewhelpers_format_rawviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\RawViewHelper',
    'tx_fluid_viewhelpers_format_striptagsviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\StripTagsViewHelper',
    'tx_fluid_viewhelpers_format_urlencodeviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\UrlencodeViewHelper',
    'tx_fluid_viewhelpers_formviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper',
    'tx_fluid_viewhelpers_forviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\ForViewHelper',
    'tx_fluid_viewhelpers_groupedforviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\GroupedForViewHelper',
    'tx_fluid_viewhelpers_ifviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\IfViewHelper',
    'tx_fluid_viewhelpers_imageviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\ImageViewHelper',
    'tx_fluid_viewhelpers_layoutviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\LayoutViewHelper',
    'tx_fluid_viewhelpers_link_actionviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Link\\ActionViewHelper',
    'tx_fluid_viewhelpers_link_emailviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Link\\EmailViewHelper',
    'tx_fluid_viewhelpers_link_externalviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Link\\ExternalViewHelper',
    'tx_fluid_viewhelpers_link_pageviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Link\\PageViewHelper',
    'tx_fluid_viewhelpers_renderchildrenviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\RenderChildrenViewHelper',
    'tx_fluid_viewhelpers_renderviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\RenderViewHelper',
    'tx_fluid_viewhelpers_sectionviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\SectionViewHelper',
    'tx_fluid_viewhelpers_security_ifauthenticatedviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Security\\IfAuthenticatedViewHelper',
    'tx_fluid_viewhelpers_security_ifhasroleviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Security\\IfHasRoleViewHelper',
    'tx_fluid_viewhelpers_thenviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\ThenViewHelper',
    'tx_fluid_viewhelpers_translateviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\TranslateViewHelper',
    'tx_fluid_viewhelpers_uri_actionviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\ActionViewHelper',
    'tx_fluid_viewhelpers_uri_emailviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\EmailViewHelper',
    'tx_fluid_viewhelpers_uri_externalviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\ExternalViewHelper',
    'tx_fluid_viewhelpers_uri_imageviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\ImageViewHelper',
    'tx_fluid_viewhelpers_uri_pageviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\PageViewHelper',
    'tx_fluid_viewhelpers_uri_resourceviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\ResourceViewHelper',
    'tx_fluid_viewhelpers_widget_autocompleteviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\AutocompleteViewHelper',
    'tx_fluid_viewhelpers_widget_controller_autocompletecontroller' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\Controller\\AutocompleteController',
    'tx_fluid_viewhelpers_widget_controller_paginatecontroller' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\Controller\\PaginateController',
    'tx_fluid_viewhelpers_widget_linkviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\LinkViewHelper',
    'tx_fluid_viewhelpers_widget_paginateviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\PaginateViewHelper',
    'tx_fluid_viewhelpers_widget_uriviewhelper' => 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\UriViewHelper',
    'tslib_feuserauth' => 'TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication',
    't3lib_matchcondition_frontend' => 'TYPO3\\CMS\\Frontend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher',
    't3lib_formmail' => 'TYPO3\\CMS\\Frontend\\Controller\\DataSubmissionController',
    'tslib_content_abstract' => 'TYPO3\\CMS\\Frontend\\ContentObject\\AbstractContentObject',
    'tslib_content_case' => 'TYPO3\\CMS\\Frontend\\ContentObject\\CaseContentObject',
    'tslib_content_cleargif' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ClearGifContentObject',
    'tslib_content_columns' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ColumnsContentObject',
    'tslib_content_content' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ContentContentObject',
    'tslib_content_contentobjectarray' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectArrayContentObject',
    'tslib_content_contentobjectarrayinternal' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectArrayInternalContentObject',
    'tslib_content_getdatahook' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectGetDataHookInterface',
    'tslib_cobj_getimgresourcehook' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectGetImageResourceHookInterface',
    'tslib_content_getpublicurlforfilehook' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectGetPublicUrlForFileHookInterface',
    'tslib_content_cobjgetsinglehook' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectGetSingleHookInterface',
    'tslib_content_postinithook' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectPostInitHookInterface',
    'tslib_cobj' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer',
    'tslib_content_stdwraphook' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectStdWrapHookInterface',
    'tslib_content_contenttable' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ContentTableContentObject',
    'tslib_content_editpanel' => 'TYPO3\\CMS\\Frontend\\ContentObject\\EditPanelContentObject',
    'tslib_content_file' => 'TYPO3\\CMS\\Frontend\\ContentObject\\FileContentObject',
    'tslib_content_filelinkhook' => 'TYPO3\\CMS\\Frontend\\ContentObject\\FileLinkHookInterface',
    'tslib_content_files' => 'TYPO3\\CMS\\Frontend\\ContentObject\\FilesContentObject',
    'tslib_content_flowplayer' => 'TYPO3\\CMS\\Frontend\\ContentObject\\FlowPlayerContentObject',
    'tslib_content_fluidtemplate' => 'TYPO3\\CMS\\Frontend\\ContentObject\\FluidTemplateContentObject',
    'tslib_content_form' => 'TYPO3\\CMS\\Frontend\\ContentObject\\FormContentObject',
    'tslib_content_hierarchicalmenu' => 'TYPO3\\CMS\\Frontend\\ContentObject\\HierarchicalMenuContentObject',
    'tslib_content_horizontalruler' => 'TYPO3\\CMS\\Frontend\\ContentObject\\HorizontalRulerContentObject',
    'tslib_content_image' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ImageContentObject',
    'tslib_content_imageresource' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ImageResourceContentObject',
    'tslib_content_imagetext' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ImageTextContentObject',
    'tslib_content_loadregister' => 'TYPO3\\CMS\\Frontend\\ContentObject\\LoadRegisterContentObject',
    'tslib_content_media' => 'TYPO3\\CMS\\Frontend\\ContentObject\\MediaContentObject',
    'tslib_menu' => 'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\AbstractMenuContentObject',
    'tslib_menu_filtermenupageshook' => 'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\AbstractMenuFilterPagesHookInterface',
    'tslib_gmenu' => 'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\GraphicalMenuContentObject',
    'tslib_imgmenu' => 'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\ImageMenuContentObject',
    'tslib_jsmenu' => 'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\JavaScriptMenuContentObject',
    'tslib_tmenu' => 'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\TextMenuContentObject',
    'tslib_content_multimedia' => 'TYPO3\\CMS\\Frontend\\ContentObject\\MultimediaContentObject',
    'tslib_tableoffset' => 'TYPO3\\CMS\\Frontend\\ContentObject\\OffsetTableContentObject',
    'tslib_content_offsettable' => 'TYPO3\\CMS\\Frontend\\ContentObject\\OffsetTableContentObject',
    'tslib_content_quicktimeobject' => 'TYPO3\\CMS\\Frontend\\ContentObject\\QuicktimeObjectContentObject',
    'tslib_content_records' => 'TYPO3\\CMS\\Frontend\\ContentObject\\RecordsContentObject',
    'tslib_content_restoreregister' => 'TYPO3\\CMS\\Frontend\\ContentObject\\RestoreRegisterContentObject',
    'tslib_content_scalablevectorgraphics' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ScalableVectorGraphicsContentObject',
    'tslib_search' => 'TYPO3\\CMS\\Frontend\\ContentObject\\SearchResultContentObject',
    'tslib_content_searchresult' => 'TYPO3\\CMS\\Frontend\\ContentObject\\SearchResultContentObject',
    'tslib_content_shockwaveflashobject' => 'TYPO3\\CMS\\Frontend\\ContentObject\\ShockwaveFlashObjectContentObject',
    'tslib_controltable' => 'TYPO3\\CMS\\Frontend\\ContentObject\\TableRenderer',
    'tslib_content_template' => 'TYPO3\\CMS\\Frontend\\ContentObject\\TemplateContentObject',
    'tslib_content_text' => 'TYPO3\\CMS\\Frontend\\ContentObject\\TextContentObject',
    'tslib_content_user' => 'TYPO3\\CMS\\Frontend\\ContentObject\\UserContentObject',
    'tslib_content_userinternal' => 'TYPO3\\CMS\\Frontend\\ContentObject\\UserInternalContentObject',
    'tslib_extdirecteid' => 'TYPO3\\CMS\\Frontend\\Controller\\ExtDirectEidController',
    'tx_cms_webinfo_page' => 'TYPO3\\CMS\\Frontend\\Controller\\PageInformationController',
    'sc_tslib_showpic' => 'TYPO3\\CMS\\Frontend\\Controller\\ShowImageController',
    'tx_cms_webinfo_lang' => 'TYPO3\\CMS\\Frontend\\Controller\\TranslationStatusController',
    'tslib_fe' => 'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
    'tx_cms_fehooks' => 'TYPO3\\CMS\\Frontend\\Hooks\\FrontendHooks',
    'tx_cms_mediaitems' => 'TYPO3\\CMS\\Frontend\\Hooks\\MediaItemHooks',
    'tx_cms_treelistcacheupdate' => 'TYPO3\\CMS\\Frontend\\Hooks\\TreelistCacheUpdateHooks',
    'tslib_gifbuilder' => 'TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder',
    'tslib_mediawizardcoreprovider' => 'TYPO3\\CMS\\Frontend\\MediaWizard\\MediaWizardProvider',
    'tslib_mediawizardprovider' => 'TYPO3\\CMS\\Frontend\\MediaWizard\\MediaWizardProviderInterface',
    'tslib_mediawizardmanager' => 'TYPO3\\CMS\\Frontend\\MediaWizard\\MediaWizardProviderManager',
    't3lib_cachehash' => 'TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator',
    'tslib_frameset' => 'TYPO3\\CMS\\Frontend\\Page\\FramesetRenderer',
    'tspagegen' => 'TYPO3\\CMS\\Frontend\\Page\\PageGenerator',
    't3lib_pageselect' => 'TYPO3\\CMS\\Frontend\\Page\\PageRepository',
    't3lib_pageselect_getpagehook' => 'TYPO3\\CMS\\Frontend\\Page\\PageRepositoryGetPageHookInterface',
    't3lib_pageselect_getpageoverlayhook' => 'TYPO3\\CMS\\Frontend\\Page\\PageRepositoryGetPageOverlayHookInterface',
    't3lib_pageselect_getrecordoverlayhook' => 'TYPO3\\CMS\\Frontend\\Page\\PageRepositoryGetRecordOverlayHookInterface',
    'tslib_pibase' => 'TYPO3\\CMS\\Frontend\\Plugin\\AbstractPlugin',
    'tslib_fecompression' => 'TYPO3\\CMS\\Frontend\\Utility\\CompressionUtility',
    'tslib_eidtools' => 'TYPO3\\CMS\\Frontend\\Utility\\EidUtility',
    'tslib_adminpanel' => 'TYPO3\\CMS\\Frontend\\View\\AdminPanelView',
    'tslib_adminpanelhook' => 'TYPO3\\CMS\\Frontend\\View\\AdminPanelViewHookInterface',
    'tx_coreupdates_addflexformstoacl' => 'TYPO3\\CMS\\Install\\CoreUpdates\\AddFlexFormsToAclUpdate',
    'tx_coreupdates_charsetdefaults' => 'TYPO3\\CMS\\Install\\CoreUpdates\\CharsetDefaultsUpdate',
    'tx_coreupdates_compatversion' => 'TYPO3\\CMS\\Install\\CoreUpdates\\CompatVersionUpdate',
    'tx_coreupdates_compressionlevel' => 'TYPO3\\CMS\\Install\\CoreUpdates\\CompressionLevelUpdate',
    'tx_coreupdates_cscsplit' => 'TYPO3\\CMS\\Install\\CoreUpdates\\CscSplitUpdate',
    'tx_coreupdates_flagsfromsprite' => 'TYPO3\\CMS\\Install\\CoreUpdates\\FlagsFromSpriteUpdate',
    'tx_coreupdates_imagecols' => 'TYPO3\\CMS\\Install\\CoreUpdates\\ImagecolsUpdate',
    'tx_coreupdates_imagelink' => 'TYPO3\\CMS\\Install\\CoreUpdates\\ImagelinkUpdate',
    'tx_coreupdates_installsysexts' => 'TYPO3\\CMS\\Install\\CoreUpdates\\InstallSysExtsUpdate',
    'tx_coreupdates_mediaflexform' => 'TYPO3\\CMS\\Install\\CoreUpdates\\MediaFlexformUpdate',
    'tx_coreupdates_mergeadvanced' => 'TYPO3\\CMS\\Install\\CoreUpdates\\MergeAdvancedUpdate',
    'tx_coreupdates_migrateworkspaces' => 'TYPO3\\CMS\\Install\\CoreUpdates\\MigrateWorkspacesUpdate',
    'tx_coreupdates_notinmenu' => 'TYPO3\\CMS\\Install\\CoreUpdates\\NotInMenuUpdate',
    'tx_coreupdates_t3skin' => 'TYPO3\\CMS\\Install\\CoreUpdates\\T3skinUpdate',
    'tx_install_service_basicservice' => 'TYPO3\\CMS\\Install\\Service\\EnableFileService',
    'typo3\\cms\\install\\enablefileservice' => 'TYPO3\\CMS\\Install\\Service\\EnableFileService',
    'tx_install_report_installstatus' => 'TYPO3\\CMS\\Install\\Report\\InstallStatusReport',
    'tx_install_session' => 'TYPO3\\CMS\\Install\\Service\\SessionService',
    'typo3\\cms\\install\\session' => 'TYPO3\\CMS\\Install\\Service\\SessionService',
    't3lib_install_sql' => 'TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService',
    'typo3\\cms\\install\\sql\\schemamigrator' => 'TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService',
    'tx_install_updates_base' => 'TYPO3\\CMS\\Install\\Updates\\AbstractUpdate',
    'tx_install_updates_file_filemountupdatewizard' => 'TYPO3\\CMS\\Install\\Updates\\FilemountUpdateWizard',
    'tx_install_updates_file_initupdatewizard' => 'TYPO3\\CMS\\Install\\Updates\\InitUpdateWizard',
    'tx_install_updates_file_tceformsupdatewizard' => 'TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard',
    'tx_install_updates_file_ttcontentuploadsupdatewizard' => 'TYPO3\\CMS\\Install\\Updates\\TtContentUploadsUpdateWizard',
    'language' => 'TYPO3\\CMS\\Lang\\LanguageService',
    'browse_links' => 'TYPO3\\CMS\\Recordlist\\Browser\\ElementBrowser',
    'sc_browse_links' => 'TYPO3\\CMS\\Recordlist\\Controller\\ElementBrowserController',
    'sc_browser' => 'TYPO3\\CMS\\Recordlist\\Controller\\ElementBrowserFramesetController',
    'sc_db_list' => 'TYPO3\\CMS\\Recordlist\\RecordList',
    'recordlist' => 'TYPO3\\CMS\\Recordlist\\RecordList\\AbstractDatabaseRecordList',
    'localrecordlist' => 'TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList',
    'localrecordlist_actionshook' => 'TYPO3\\CMS\\Recordlist\\RecordList\\RecordListHookInterface',
    'tx_saltedpasswords_eval_be' => 'TYPO3\\CMS\\Saltedpasswords\\Evaluation\\BackendEvaluator',
    'tx_saltedpasswords_eval' => 'TYPO3\\CMS\\Saltedpasswords\\Evaluation\\Evaluator',
    'tx_saltedpasswords_eval_fe' => 'TYPO3\\CMS\\Saltedpasswords\\Evaluation\\FrontendEvaluator',
    'tx_saltedpasswords_abstract_salts' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\AbstractSalt',
    'tx_saltedpasswords_salts_blowfish' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\BlowfishSalt',
    'tx_saltedpasswords_salts_md5' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt',
    'tx_saltedpasswords_salts_phpass' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt',
    'tx_saltedpasswords_salts_factory' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\SaltFactory',
    'tx_saltedpasswords_salts' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\SaltInterface',
    'tx_saltedpasswords_sv1' => 'TYPO3\\CMS\\Saltedpasswords\\SaltedPasswordService',
    'tx_saltedpasswords_tasks_bulkupdate_additionalfieldprovider' => 'TYPO3\\CMS\\Saltedpasswords\\Task\\BulkUpdateFieldProvider',
    'tx_saltedpasswords_tasks_bulkupdate' => 'TYPO3\\CMS\\Saltedpasswords\\Task\\BulkUpdateTask',
    'tx_saltedpasswords_emconfhelper' => 'TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility',
    'tx_saltedpasswords_div' => 'TYPO3\\CMS\\Saltedpasswords\\Utility\\SaltedPasswordsUtility',
    'tx_sv_authbase' => 'TYPO3\\CMS\\Sv\\AbstractAuthenticationService',
    'tx_sv_auth' => 'TYPO3\\CMS\\Sv\\AuthenticationService',
    'tx_sv_loginformhook' => 'TYPO3\\CMS\\Sv\\LoginFormHook',
    'tx_sv_reports_serviceslist' => 'TYPO3\\CMS\\Sv\\Report\\ServicesListReport',
  ),
  'classNameToAliasMapping' => 
  array (
    'TYPO3\\CMS\\Backend\\AjaxLoginHandler' => 
    array (
      'ajaxlogin' => 'ajaxlogin',
    ),
    'TYPO3\\CMS\\Backend\\ClickMenu\\ClickMenu' => 
    array (
      'clickmenu' => 'clickmenu',
    ),
    'TYPO3\\CMS\\Backend\\Clipboard\\Clipboard' => 
    array (
      't3lib_clipboard' => 't3lib_clipboard',
    ),
    'TYPO3\\CMS\\Backend\\Configuration\\TranslationConfigurationProvider' => 
    array (
      't3lib_transl8tools' => 't3lib_transl8tools',
    ),
    'TYPO3\\CMS\\Backend\\Configuration\\TsConfigParser' => 
    array (
      't3lib_tsparser_tsconfig' => 't3lib_tsparser_tsconfig',
    ),
    'TYPO3\\CMS\\Backend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher' => 
    array (
      't3lib_matchcondition_backend' => 't3lib_matchcondition_backend',
    ),
    'TYPO3\\CMS\\Backend\\ContextMenu\\AbstractContextMenu' => 
    array (
      't3lib_contextmenu_abstractcontextmenu' => 't3lib_contextmenu_abstractcontextmenu',
    ),
    'TYPO3\\CMS\\Backend\\ContextMenu\\AbstractContextMenuDataProvider' => 
    array (
      't3lib_contextmenu_abstractdataprovider' => 't3lib_contextmenu_abstractdataprovider',
    ),
    'TYPO3\\CMS\\Backend\\ContextMenu\\ContextMenuAction' => 
    array (
      't3lib_contextmenu_action' => 't3lib_contextmenu_action',
    ),
    'TYPO3\\CMS\\Backend\\ContextMenu\\ContextMenuActionCollection' => 
    array (
      't3lib_contextmenu_actioncollection' => 't3lib_contextmenu_actioncollection',
    ),
    'TYPO3\\CMS\\Backend\\ContextMenu\\Extdirect\\AbstractExtdirectContextMenu' => 
    array (
      't3lib_contextmenu_extdirect_contextmenu' => 't3lib_contextmenu_extdirect_contextmenu',
    ),
    'TYPO3\\CMS\\Backend\\ContextMenu\\Pagetree\\ContextMenuDataProvider' => 
    array (
      't3lib_contextmenu_pagetree_dataprovider' => 't3lib_contextmenu_pagetree_dataprovider',
    ),
    'TYPO3\\CMS\\Backend\\ContextMenu\\Pagetree\\Extdirect\\ContextMenuConfiguration' => 
    array (
      't3lib_contextmenu_pagetree_extdirect_contextmenu' => 't3lib_contextmenu_pagetree_extdirect_contextmenu',
    ),
    'TYPO3\\CMS\\Backend\\ContextMenu\\Renderer\\AbstractContextMenuRenderer' => 
    array (
      't3lib_contextmenu_renderer_abstract' => 't3lib_contextmenu_renderer_abstract',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\BackendController' => 
    array (
      'typo3backend' => 'typo3backend',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\BackendLayoutWizardController' => 
    array (
      'sc_wizard_backend_layout' => 'sc_wizard_backend_layout',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\ClickMenuController' => 
    array (
      'sc_alt_clickmenu' => 'sc_alt_clickmenu',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\ContentElement\\ElementHistoryController' => 
    array (
      'sc_show_rechis' => 'sc_show_rechis',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\ContentElement\\ElementInformationController' => 
    array (
      'sc_show_item' => 'sc_show_item',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\ContentElement\\MoveElementController' => 
    array (
      'sc_move_el' => 'sc_move_el',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\ContentElement\\NewContentElementController' => 
    array (
      'sc_db_new_content_el' => 'sc_db_new_content_el',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\DummyController' => 
    array (
      'sc_dummy' => 'sc_dummy',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\EditDocumentController' => 
    array (
      'sc_alt_doc' => 'sc_alt_doc',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\File\\CreateFolderController' => 
    array (
      'sc_file_newfolder' => 'sc_file_newfolder',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\File\\EditFileController' => 
    array (
      'sc_file_edit' => 'sc_file_edit',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\File\\FileController' => 
    array (
      'typo3_tcefile' => 'typo3_tcefile',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\File\\FileUploadController' => 
    array (
      'sc_file_upload' => 'sc_file_upload',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\File\\RenameFileController' => 
    array (
      'sc_file_rename' => 'sc_file_rename',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\FileSystemNavigationFrameController' => 
    array (
      'sc_alt_file_navframe' => 'sc_alt_file_navframe',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\ListFrameLoaderController' => 
    array (
      'sc_listframe_loader' => 'sc_listframe_loader',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\LoginController' => 
    array (
      'sc_index' => 'sc_index',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\LoginFramesetController' => 
    array (
      'sc_login_frameset' => 'sc_login_frameset',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\LogoutController' => 
    array (
      'sc_logout' => 'sc_logout',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\NewRecordController' => 
    array (
      'sc_db_new' => 'sc_db_new',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\NoDocumentsOpenController' => 
    array (
      'sc_alt_doc_nodoc' => 'sc_alt_doc_nodoc',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\PageLayoutController' => 
    array (
      'sc_db_layout' => 'sc_db_layout',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\PageTreeNavigationController' => 
    array (
      'sc_alt_db_navframe' => 'sc_alt_db_navframe',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\SimpleDataHandlerController' => 
    array (
      'sc_tce_db' => 'sc_tce_db',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\Wizard\\AddController' => 
    array (
      'sc_wizard_add' => 'sc_wizard_add',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\Wizard\\ColorpickerController' => 
    array (
      'sc_wizard_colorpicker' => 'sc_wizard_colorpicker',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\Wizard\\EditController' => 
    array (
      'sc_wizard_edit' => 'sc_wizard_edit',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\Wizard\\FormsController' => 
    array (
      'sc_wizard_forms' => 'sc_wizard_forms',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\Wizard\\ListController' => 
    array (
      'sc_wizard_list' => 'sc_wizard_list',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\Wizard\\RteController' => 
    array (
      'sc_wizard_rte' => 'sc_wizard_rte',
    ),
    'TYPO3\\CMS\\Backend\\Controller\\Wizard\\TableController' => 
    array (
      'sc_wizard_table' => 'sc_wizard_table',
    ),
    'TYPO3\\CMS\\Backend\\Form\\DataPreprocessor' => 
    array (
      't3lib_transferdata' => 't3lib_transferdata',
    ),
    'TYPO3\\CMS\\Backend\\Form\\Element\\InlineElement' => 
    array (
      't3lib_tceforms_inline' => 't3lib_tceforms_inline',
    ),
    'TYPO3\\CMS\\Backend\\Form\\Element\\InlineElementHookInterface' => 
    array (
      't3lib_tceformsinlinehook' => 't3lib_tceformsinlinehook',
    ),
    'TYPO3\\CMS\\Backend\\Form\\FrontendFormEngine' => 
    array (
      't3lib_tceforms_fe' => 't3lib_tceforms_fe',
    ),
    'TYPO3\\CMS\\Backend\\Form\\DatabaseFileIconsHookInterface' => 
    array (
      't3lib_tceforms_dbfileiconshook' => 't3lib_tceforms_dbfileiconshook',
    ),
    'TYPO3\\CMS\\Backend\\Form\\Element\\SuggestDefaultReceiver' => 
    array (
      't3lib_tceforms_suggest_defaultreceiver' => 't3lib_tceforms_suggest_defaultreceiver',
    ),
    'TYPO3\\CMS\\Backend\\Form\\Element\\SuggestElement' => 
    array (
      't3lib_tceforms_suggest' => 't3lib_tceforms_suggest',
    ),
    'TYPO3\\CMS\\Backend\\Form\\Element\\TreeElement' => 
    array (
      't3lib_tceforms_tree' => 't3lib_tceforms_tree',
    ),
    'TYPO3\\CMS\\Backend\\Form\\Element\\ValueSlider' => 
    array (
      't3lib_tceforms_valueslider' => 't3lib_tceforms_valueslider',
    ),
    'TYPO3\\CMS\\Backend\\Form\\FlexFormsHelper' => 
    array (
      't3lib_tceforms_flexforms' => 't3lib_tceforms_flexforms',
    ),
    'TYPO3\\CMS\\Backend\\Form\\FormEngine' => 
    array (
      't3lib_tceforms' => 't3lib_tceforms',
    ),
    'TYPO3\\CMS\\Backend\\FrontendBackendUserAuthentication' => 
    array (
      't3lib_tsfebeuserauth' => 't3lib_tsfebeuserauth',
    ),
    'TYPO3\\CMS\\Backend\\History\\RecordHistory' => 
    array (
      'recordhistory' => 'recordhistory',
    ),
    'TYPO3\\CMS\\Backend\\InterfaceState\\ExtDirect\\DataProvider' => 
    array (
      'extdirect_dataprovider_state' => 'extdirect_dataprovider_state',
    ),
    'TYPO3\\CMS\\Backend\\Module\\AbstractFunctionModule' => 
    array (
      't3lib_extobjbase' => 't3lib_extobjbase',
    ),
    'TYPO3\\CMS\\Backend\\Module\\BaseScriptClass' => 
    array (
      't3lib_scbase' => 't3lib_scbase',
    ),
    'TYPO3\\CMS\\Backend\\Module\\ModuleLoader' => 
    array (
      't3lib_loadmodules' => 't3lib_loadmodules',
    ),
    'TYPO3\\CMS\\Backend\\Module\\ModuleStorage' => 
    array (
      'typo3_modulestorage' => 'typo3_modulestorage',
    ),
    'TYPO3\\CMS\\Backend\\ModuleSettings' => 
    array (
      't3lib_modsettings' => 't3lib_modsettings',
    ),
    'TYPO3\\CMS\\Backend\\RecordList\\AbstractRecordList' => 
    array (
      't3lib_recordlist' => 't3lib_recordlist',
    ),
    'TYPO3\\CMS\\Backend\\RecordList\\ElementBrowserRecordList' => 
    array (
      'tbe_browser_recordlist' => 'tbe_browser_recordlist',
    ),
    'TYPO3\\CMS\\Backend\\RecordList\\RecordListGetTableHookInterface' => 
    array (
      't3lib_localrecordlistgettablehook' => 't3lib_localrecordlistgettablehook',
    ),
    'TYPO3\\CMS\\Backend\\Rte\\AbstractRte' => 
    array (
      't3lib_rteapi' => 't3lib_rteapi',
    ),
    'TYPO3\\CMS\\Backend\\Search\\LiveSearch\\ExtDirect\\LiveSearchDataProvider' => 
    array (
      'extdirect_dataprovider_backendlivesearch' => 'extdirect_dataprovider_backendlivesearch',
    ),
    'TYPO3\\CMS\\Backend\\Search\\LiveSearch\\LiveSearch' => 
    array (
      't3lib_search_livesearch' => 't3lib_search_livesearch',
    ),
    'TYPO3\\CMS\\Backend\\Search\\LiveSearch\\QueryParser' => 
    array (
      't3lib_search_livesearch_queryparser' => 't3lib_search_livesearch_queryparser',
    ),
    'TYPO3\\CMS\\Backend\\Sprite\\AbstractSpriteHandler' => 
    array (
      't3lib_spritemanager_abstracthandler' => 't3lib_spritemanager_abstracthandler',
    ),
    'TYPO3\\CMS\\Backend\\Sprite\\SimpleSpriteHandler' => 
    array (
      't3lib_spritemanager_simplehandler' => 't3lib_spritemanager_simplehandler',
    ),
    'TYPO3\\CMS\\Backend\\Sprite\\SpriteBuildingHandler' => 
    array (
      't3lib_spritemanager_spritebuildinghandler' => 't3lib_spritemanager_spritebuildinghandler',
    ),
    'TYPO3\\CMS\\Backend\\Sprite\\SpriteGenerator' => 
    array (
      't3lib_spritemanager_spritegenerator' => 't3lib_spritemanager_spritegenerator',
    ),
    'TYPO3\\CMS\\Backend\\Sprite\\SpriteIconGeneratorInterface' => 
    array (
      't3lib_spritemanager_spriteicongenerator' => 't3lib_spritemanager_spriteicongenerator',
    ),
    'TYPO3\\CMS\\Backend\\Sprite\\SpriteManager' => 
    array (
      't3lib_spritemanager' => 't3lib_spritemanager',
    ),
    'TYPO3\\CMS\\Backend\\Template\\BigDocumentTemplate' => 
    array (
      'bigdoc' => 'bigdoc',
    ),
    'TYPO3\\CMS\\Backend\\Template\\DocumentTemplate' => 
    array (
      'template' => 'template',
    ),
    'TYPO3\\CMS\\Backend\\Template\\FrontendDocumentTemplate' => 
    array (
      'frontenddoc' => 'frontenddoc',
    ),
    'TYPO3\\CMS\\Backend\\Template\\MediumDocumentTemplate' => 
    array (
      'mediumdoc' => 'mediumdoc',
    ),
    'TYPO3\\CMS\\Backend\\Template\\SmallDocumentTemplate' => 
    array (
      'smalldoc' => 'smalldoc',
    ),
    'TYPO3\\CMS\\Backend\\Template\\StandardDocumentTemplate' => 
    array (
      'nodoc' => 'nodoc',
    ),
    'TYPO3\\CMS\\Backend\\Toolbar\\ClearCacheActionsHookInterface' => 
    array (
      'backend_cacheactionshook' => 'backend_cacheactionshook',
    ),
    'TYPO3\\CMS\\Backend\\Toolbar\\ClearCacheToolbarItem' => 
    array (
      'clearcachemenu' => 'clearcachemenu',
    ),
    'TYPO3\\CMS\\Backend\\Toolbar\\LiveSearchToolbarItem' => 
    array (
      'livesearch' => 'livesearch',
    ),
    'TYPO3\\CMS\\Backend\\Toolbar\\ShortcutToolbarItem' => 
    array (
      'shortcutmenu' => 'shortcutmenu',
    ),
    'TYPO3\\CMS\\Backend\\Toolbar\\ToolbarItemHookInterface' => 
    array (
      'backend_toolbaritem' => 'backend_toolbaritem',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\AbstractExtJsTree' => 
    array (
      't3lib_tree_extdirect_abstractextjstree' => 't3lib_tree_extdirect_abstractextjstree',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\AbstractTree' => 
    array (
      't3lib_tree_abstracttree' => 't3lib_tree_abstracttree',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\AbstractTreeDataProvider' => 
    array (
      't3lib_tree_abstractdataprovider' => 't3lib_tree_abstractdataprovider',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\AbstractTreeStateProvider' => 
    array (
      't3lib_tree_abstractstateprovider' => 't3lib_tree_abstractstateprovider',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\ComparableNodeInterface' => 
    array (
      't3lib_tree_comparablenode' => 't3lib_tree_comparablenode',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\DraggableAndDropableNodeInterface' => 
    array (
      't3lib_tree_draggableanddropable' => 't3lib_tree_draggableanddropable',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\EditableNodeLabelInterface' => 
    array (
      't3lib_tree_labeleditable' => 't3lib_tree_labeleditable',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\ExtDirectNode' => 
    array (
      't3lib_tree_extdirect_node' => 't3lib_tree_extdirect_node',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\CollectionProcessorInterface' => 
    array (
      't3lib_tree_pagetree_interfaces_collectionprocessor' => 't3lib_tree_pagetree_interfaces_collectionprocessor',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\Commands' => 
    array (
      't3lib_tree_pagetree_commands' => 't3lib_tree_pagetree_commands',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\DataProvider' => 
    array (
      't3lib_tree_pagetree_dataprovider' => 't3lib_tree_pagetree_dataprovider',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\ExtdirectTreeCommands' => 
    array (
      't3lib_tree_pagetree_extdirect_commands' => 't3lib_tree_pagetree_extdirect_commands',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\ExtdirectTreeDataProvider' => 
    array (
      't3lib_tree_pagetree_extdirect_tree' => 't3lib_tree_pagetree_extdirect_tree',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\Indicator' => 
    array (
      't3lib_tree_pagetree_indicator' => 't3lib_tree_pagetree_indicator',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\IndicatorProviderInterface' => 
    array (
      't3lib_tree_pagetree_interfaces_indicatorprovider' => 't3lib_tree_pagetree_interfaces_indicatorprovider',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNode' => 
    array (
      't3lib_tree_pagetree_node' => 't3lib_tree_pagetree_node',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNodeCollection' => 
    array (
      't3lib_tree_pagetree_nodecollection' => 't3lib_tree_pagetree_nodecollection',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Renderer\\AbstractTreeRenderer' => 
    array (
      't3lib_tree_renderer_abstract' => 't3lib_tree_renderer_abstract',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Renderer\\ExtJsJsonTreeRenderer' => 
    array (
      't3lib_tree_renderer_extjsjson' => 't3lib_tree_renderer_extjsjson',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\Renderer\\UnorderedListTreeRenderer' => 
    array (
      't3lib_tree_renderer_unorderedlist' => 't3lib_tree_renderer_unorderedlist',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\SortedTreeNodeCollection' => 
    array (
      't3lib_tree_sortednodecollection' => 't3lib_tree_sortednodecollection',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\TreeNode' => 
    array (
      't3lib_tree_node' => 't3lib_tree_node',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\TreeNodeCollection' => 
    array (
      't3lib_tree_nodecollection' => 't3lib_tree_nodecollection',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\TreeRepresentationNode' => 
    array (
      't3lib_tree_representationnode' => 't3lib_tree_representationnode',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\View\\AbstractTreeView' => 
    array (
      't3lib_treeview' => 't3lib_treeview',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\View\\BrowseTreeView' => 
    array (
      't3lib_browsetree' => 't3lib_browsetree',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\View\\FolderTreeView' => 
    array (
      't3lib_foldertree' => 't3lib_foldertree',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\View\\PagePositionMap' => 
    array (
      't3lib_positionmap' => 't3lib_positionmap',
    ),
    'TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView' => 
    array (
      't3lib_pagetree' => 't3lib_pagetree',
    ),
    'TYPO3\\CMS\\Backend\\User\\ExtDirect\\BackendUserSettingsDataProvider' => 
    array (
      'extdirect_dataprovider_backendusersettings' => 'extdirect_dataprovider_backendusersettings',
    ),
    'TYPO3\\CMS\\Backend\\Utility\\BackendUtility' => 
    array (
      't3lib_befunc' => 't3lib_befunc',
    ),
    'TYPO3\\CMS\\Backend\\Utility\\IconUtility' => 
    array (
      't3lib_iconworks' => 't3lib_iconworks',
    ),
    'TYPO3\\CMS\\Backend\\View\\BackendLayoutView' => 
    array (
      'tx_cms_backendlayout' => 'tx_cms_backendlayout',
    ),
    'TYPO3\\CMS\\Backend\\View\\ModuleMenuView' => 
    array (
      'modulemenu' => 'modulemenu',
    ),
    'TYPO3\\CMS\\Backend\\View\\PageLayoutView' => 
    array (
      'tx_cms_layout' => 'tx_cms_layout',
    ),
    'TYPO3\\CMS\\Backend\\View\\PageLayoutViewDrawItemHookInterface' => 
    array (
      'tx_cms_layout_tt_content_drawitemhook' => 'tx_cms_layout_tt_content_drawitemhook',
    ),
    'TYPO3\\CMS\\Backend\\View\\PageTreeView' => 
    array (
      'webpagetree' => 'webpagetree',
    ),
    'TYPO3\\CMS\\Backend\\View\\ThumbnailView' => 
    array (
      'sc_t3lib_thumbs' => 'sc_t3lib_thumbs',
    ),
    'TYPO3\\CMS\\Backend\\View\\LogoView' => 
    array (
      'typo3logo' => 'typo3logo',
    ),
    'TYPO3\\CMS\\Backend\\Wizard\\NewContentElementWizardHookInterface' => 
    array (
      'cms_newcontentelementwizardshook' => 'cms_newcontentelementwizardshook',
    ),
    'TYPO3\\CMS\\Core\\ExtDirect\\ExtDirectRouter' => 
    array (
      't3lib_extjs_extdirectrouter' => 't3lib_extjs_extdirectrouter',
    ),
    'TYPO3\\CMS\\Core\\ExtDirect\\ExtDirectApi' => 
    array (
      't3lib_extjs_extdirectapi' => 't3lib_extjs_extdirectapi',
    ),
    'TYPO3\\CMS\\Core\\ExtDirect\\ExtDirectDebug' => 
    array (
      't3lib_extjs_extdirectdebug' => 't3lib_extjs_extdirectdebug',
    ),
    'TYPO3\\CMS\\Core\\Controller\\CommandLineController' => 
    array (
      't3lib_cli' => 't3lib_cli',
    ),
    'TYPO3\\CMS\\ContextHelp\\ExtDirect\\ContextHelpDataProvider' => 
    array (
      'extdirect_dataprovider_contexthelp' => 'extdirect_dataprovider_contexthelp',
    ),
    'TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication' => 
    array (
      't3lib_userauth' => 't3lib_userauth',
    ),
    'TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication' => 
    array (
      't3lib_beuserauth' => 't3lib_beuserauth',
    ),
    'TYPO3\\CMS\\Core\\Core\\ClassLoader' => 
    array (
      't3lib_autoloader' => 't3lib_autoloader',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend' => 
    array (
      't3lib_cache_backend_abstractbackend' => 't3lib_cache_backend_abstractbackend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Backend\\ApcBackend' => 
    array (
      't3lib_cache_backend_apcbackend' => 't3lib_cache_backend_apcbackend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Backend\\BackendInterface' => 
    array (
      't3lib_cache_backend_backend' => 't3lib_cache_backend_backend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Backend\\FileBackend' => 
    array (
      't3lib_cache_backend_filebackend' => 't3lib_cache_backend_filebackend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Backend\\MemcachedBackend' => 
    array (
      't3lib_cache_backend_memcachedbackend' => 't3lib_cache_backend_memcachedbackend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend' => 
    array (
      't3lib_cache_backend_nullbackend' => 't3lib_cache_backend_nullbackend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Backend\\PdoBackend' => 
    array (
      't3lib_cache_backend_pdobackend' => 't3lib_cache_backend_pdobackend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Backend\\PhpCapableBackendInterface' => 
    array (
      't3lib_cache_backend_phpcapablebackend' => 't3lib_cache_backend_phpcapablebackend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Backend\\RedisBackend' => 
    array (
      't3lib_cache_backend_redisbackend' => 't3lib_cache_backend_redisbackend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend' => 
    array (
      't3lib_cache_backend_transientmemorybackend' => 't3lib_cache_backend_transientmemorybackend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend' => 
    array (
      't3lib_cache_backend_dbbackend' => 't3lib_cache_backend_dbbackend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Cache' => 
    array (
      't3lib_cache' => 't3lib_cache',
    ),
    'TYPO3\\CMS\\Core\\Cache\\CacheFactory' => 
    array (
      't3lib_cache_factory' => 't3lib_cache_factory',
    ),
    'TYPO3\\CMS\\Core\\Cache\\CacheManager' => 
    array (
      't3lib_cache_manager' => 't3lib_cache_manager',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Exception' => 
    array (
      't3lib_cache_exception' => 't3lib_cache_exception',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Exception\\ClassAlreadyLoadedException' => 
    array (
      't3lib_cache_exception_classalreadyloaded' => 't3lib_cache_exception_classalreadyloaded',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Exception\\DuplicateIdentifierException' => 
    array (
      't3lib_cache_exception_duplicateidentifier' => 't3lib_cache_exception_duplicateidentifier',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Exception\\InvalidBackendException' => 
    array (
      't3lib_cache_exception_invalidbackend' => 't3lib_cache_exception_invalidbackend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Exception\\InvalidCacheException' => 
    array (
      't3lib_cache_exception_invalidcache' => 't3lib_cache_exception_invalidcache',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Exception\\InvalidDataException' => 
    array (
      't3lib_cache_exception_invaliddata' => 't3lib_cache_exception_invaliddata',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Exception\\NoSuchCacheException' => 
    array (
      't3lib_cache_exception_nosuchcache' => 't3lib_cache_exception_nosuchcache',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend' => 
    array (
      't3lib_cache_frontend_abstractfrontend' => 't3lib_cache_frontend_abstractfrontend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface' => 
    array (
      't3lib_cache_frontend_frontend' => 't3lib_cache_frontend_frontend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Frontend\\PhpFrontend' => 
    array (
      't3lib_cache_frontend_phpfrontend' => 't3lib_cache_frontend_phpfrontend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend' => 
    array (
      't3lib_cache_frontend_stringfrontend' => 't3lib_cache_frontend_stringfrontend',
    ),
    'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend' => 
    array (
      't3lib_cache_frontend_variablefrontend' => 't3lib_cache_frontend_variablefrontend',
    ),
    'TYPO3\\CMS\\Core\\Charset\\CharsetConverter' => 
    array (
      't3lib_cs' => 't3lib_cs',
    ),
    'TYPO3\\CMS\\Core\\Collection\\AbstractRecordCollection' => 
    array (
      't3lib_collection_abstractrecordcollection' => 't3lib_collection_abstractrecordcollection',
    ),
    'TYPO3\\CMS\\Core\\Collection\\CollectionInterface' => 
    array (
      't3lib_collection_collection' => 't3lib_collection_collection',
    ),
    'TYPO3\\CMS\\Core\\Collection\\EditableCollectionInterface' => 
    array (
      't3lib_collection_editable' => 't3lib_collection_editable',
    ),
    'TYPO3\\CMS\\Core\\Collection\\NameableCollectionInterface' => 
    array (
      't3lib_collection_nameable' => 't3lib_collection_nameable',
    ),
    'TYPO3\\CMS\\Core\\Collection\\PersistableCollectionInterface' => 
    array (
      't3lib_collection_persistable' => 't3lib_collection_persistable',
    ),
    'TYPO3\\CMS\\Core\\Collection\\RecordCollectionInterface' => 
    array (
      't3lib_collection_recordcollection' => 't3lib_collection_recordcollection',
    ),
    'TYPO3\\CMS\\Core\\Collection\\RecordCollectionRepository' => 
    array (
      't3lib_collection_recordcollectionrepository' => 't3lib_collection_recordcollectionrepository',
    ),
    'TYPO3\\CMS\\Core\\Collection\\SortableCollectionInterface' => 
    array (
      't3lib_collection_sortable' => 't3lib_collection_sortable',
    ),
    'TYPO3\\CMS\\Core\\Collection\\StaticRecordCollection' => 
    array (
      't3lib_collection_staticrecordcollection' => 't3lib_collection_staticrecordcollection',
    ),
    'TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools' => 
    array (
      't3lib_flexformtools' => 't3lib_flexformtools',
    ),
    'TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher' => 
    array (
      't3lib_matchcondition_abstract' => 't3lib_matchcondition_abstract',
    ),
    'TYPO3\\CMS\\Core\\Database\\DatabaseConnection' => 
    array (
      't3lib_db' => 't3lib_db',
    ),
    'TYPO3\\CMS\\Core\\Database\\PdoHelper' => 
    array (
      't3lib_pdohelper' => 't3lib_pdohelper',
    ),
    'TYPO3\\CMS\\Core\\Database\\PostProcessQueryHookInterface' => 
    array (
      't3lib_db_postprocessqueryhook' => 't3lib_db_postprocessqueryhook',
    ),
    'TYPO3\\CMS\\Core\\Database\\PreparedStatement' => 
    array (
      't3lib_db_preparedstatement' => 't3lib_db_preparedstatement',
    ),
    'TYPO3\\CMS\\Core\\Database\\PreProcessQueryHookInterface' => 
    array (
      't3lib_db_preprocessqueryhook' => 't3lib_db_preprocessqueryhook',
    ),
    'TYPO3\\CMS\\Core\\Database\\QueryGenerator' => 
    array (
      't3lib_querygenerator' => 't3lib_querygenerator',
    ),
    'TYPO3\\CMS\\Core\\Database\\QueryView' => 
    array (
      't3lib_fullsearch' => 't3lib_fullsearch',
    ),
    'TYPO3\\CMS\\Core\\Database\\ReferenceIndex' => 
    array (
      't3lib_refindex' => 't3lib_refindex',
    ),
    'TYPO3\\CMS\\Core\\Database\\RelationHandler' => 
    array (
      't3lib_loaddbgroup' => 't3lib_loaddbgroup',
    ),
    'TYPO3\\CMS\\Core\\Database\\SoftReferenceIndex' => 
    array (
      't3lib_softrefproc' => 't3lib_softrefproc',
    ),
    'TYPO3\\CMS\\Core\\Database\\SqlParser' => 
    array (
      't3lib_sqlparser' => 't3lib_sqlparser',
    ),
    'TYPO3\\CMS\\Core\\Database\\TableConfigurationPostProcessingHookInterface' => 
    array (
      't3lib_exttables_postprocessinghook' => 't3lib_exttables_postprocessinghook',
    ),
    'TYPO3\\CMS\\Core\\DataHandling\\DataHandler' => 
    array (
      't3lib_tcemain' => 't3lib_tcemain',
    ),
    'TYPO3\\CMS\\Core\\DataHandling\\DataHandlerCheckModifyAccessListHookInterface' => 
    array (
      't3lib_tcemain_checkmodifyaccesslisthook' => 't3lib_tcemain_checkmodifyaccesslisthook',
    ),
    'TYPO3\\CMS\\Core\\DataHandling\\DataHandlerProcessUploadHookInterface' => 
    array (
      't3lib_tcemain_processuploadhook' => 't3lib_tcemain_processuploadhook',
    ),
    'TYPO3\\CMS\\Core\\ElementBrowser\\ElementBrowserHookInterface' => 
    array (
      't3lib_browselinkshook' => 't3lib_browselinkshook',
    ),
    'TYPO3\\CMS\\Core\\Encoder\\JavaScriptEncoder' => 
    array (
      't3lib_codec_javascriptencoder' => 't3lib_codec_javascriptencoder',
    ),
    'TYPO3\\CMS\\Core\\Error\\AbstractExceptionHandler' => 
    array (
      't3lib_error_abstractexceptionhandler' => 't3lib_error_abstractexceptionhandler',
    ),
    'TYPO3\\CMS\\Core\\Error\\DebugExceptionHandler' => 
    array (
      't3lib_error_debugexceptionhandler' => 't3lib_error_debugexceptionhandler',
    ),
    'TYPO3\\CMS\\Core\\Error\\ErrorHandler' => 
    array (
      't3lib_error_errorhandler' => 't3lib_error_errorhandler',
    ),
    'TYPO3\\CMS\\Core\\Error\\ErrorHandlerInterface' => 
    array (
      't3lib_error_errorhandlerinterface' => 't3lib_error_errorhandlerinterface',
    ),
    'TYPO3\\CMS\\Core\\Error\\Exception' => 
    array (
      't3lib_error_exception' => 't3lib_error_exception',
    ),
    'TYPO3\\CMS\\Core\\Error\\ExceptionHandlerInterface' => 
    array (
      't3lib_error_exceptionhandlerinterface' => 't3lib_error_exceptionhandlerinterface',
    ),
    'TYPO3\\CMS\\Core\\Error\\Http\\AbstractClientErrorException' => 
    array (
      't3lib_error_http_abstractclienterrorexception' => 't3lib_error_http_abstractclienterrorexception',
    ),
    'TYPO3\\CMS\\Core\\Error\\Http\\AbstractServerErrorException' => 
    array (
      't3lib_error_http_abstractservererrorexception' => 't3lib_error_http_abstractservererrorexception',
    ),
    'TYPO3\\CMS\\Core\\Error\\Http\\BadRequestException' => 
    array (
      't3lib_error_http_badrequestexception' => 't3lib_error_http_badrequestexception',
    ),
    'TYPO3\\CMS\\Core\\Error\\Http\\ForbiddenException' => 
    array (
      't3lib_error_http_forbiddenexception' => 't3lib_error_http_forbiddenexception',
    ),
    'TYPO3\\CMS\\Core\\Error\\Http\\PageNotFoundException' => 
    array (
      't3lib_error_http_pagenotfoundexception' => 't3lib_error_http_pagenotfoundexception',
    ),
    'TYPO3\\CMS\\Core\\Error\\Http\\ServiceUnavailableException' => 
    array (
      't3lib_error_http_serviceunavailableexception' => 't3lib_error_http_serviceunavailableexception',
    ),
    'TYPO3\\CMS\\Core\\Error\\Http\\StatusException' => 
    array (
      't3lib_error_http_statusexception' => 't3lib_error_http_statusexception',
    ),
    'TYPO3\\CMS\\Core\\Error\\Http\\UnauthorizedException' => 
    array (
      't3lib_error_http_unauthorizedexception' => 't3lib_error_http_unauthorizedexception',
    ),
    'TYPO3\\CMS\\Core\\Error\\ProductionExceptionHandler' => 
    array (
      't3lib_error_productionexceptionhandler' => 't3lib_error_productionexceptionhandler',
    ),
    'TYPO3\\CMS\\Core\\Exception' => 
    array (
      't3lib_exception' => 't3lib_exception',
    ),
    'TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility' => 
    array (
      't3lib_extmgm' => 't3lib_extmgm',
    ),
    'TYPO3\\CMS\\Core\\FormProtection\\AbstractFormProtection' => 
    array (
      't3lib_formprotection_abstract' => 't3lib_formprotection_abstract',
    ),
    'TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection' => 
    array (
      't3lib_formprotection_backendformprotection' => 't3lib_formprotection_backendformprotection',
    ),
    'TYPO3\\CMS\\Core\\FormProtection\\DisabledFormProtection' => 
    array (
      't3lib_formprotection_disabledformprotection' => 't3lib_formprotection_disabledformprotection',
    ),
    'TYPO3\\CMS\\Core\\FormProtection\\Exception' => 
    array (
      't3lib_formprotection_invalidtokenexception' => 't3lib_formprotection_invalidtokenexception',
    ),
    'TYPO3\\CMS\\Core\\FormProtection\\FormProtectionFactory' => 
    array (
      't3lib_formprotection_factory' => 't3lib_formprotection_factory',
    ),
    'TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection' => 
    array (
      't3lib_formprotection_installtoolformprotection' => 't3lib_formprotection_installtoolformprotection',
    ),
    'TYPO3\\CMS\\Core\\FrontendEditing\\FrontendEditingController' => 
    array (
      't3lib_frontendedit' => 't3lib_frontendedit',
    ),
    'TYPO3\\CMS\\Core\\Html\\HtmlParser' => 
    array (
      't3lib_parsehtml' => 't3lib_parsehtml',
    ),
    'TYPO3\\CMS\\Core\\Html\\RteHtmlParser' => 
    array (
      't3lib_parsehtml_proc' => 't3lib_parsehtml_proc',
    ),
    'TYPO3\\CMS\\Core\\Http\\AjaxRequestHandler' => 
    array (
      'typo3ajax' => 'typo3ajax',
    ),
    'TYPO3\\CMS\\Core\\Http\\HttpRequest' => 
    array (
      't3lib_http_request' => 't3lib_http_request',
    ),
    'TYPO3\\CMS\\Core\\Http\\Observer\\Download' => 
    array (
      't3lib_http_observer_download' => 't3lib_http_observer_download',
    ),
    'TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions' => 
    array (
      't3lib_stdgraphic' => 't3lib_stdgraphic',
    ),
    'TYPO3\\CMS\\Core\\Integrity\\DatabaseIntegrityCheck' => 
    array (
      't3lib_admin' => 't3lib_admin',
    ),
    'TYPO3\\CMS\\Core\\Localization\\Exception\\FileNotFoundException' => 
    array (
      't3lib_l10n_exception_filenotfound' => 't3lib_l10n_exception_filenotfound',
    ),
    'TYPO3\\CMS\\Core\\Localization\\Exception\\InvalidParserException' => 
    array (
      't3lib_l10n_exception_invalidparser' => 't3lib_l10n_exception_invalidparser',
    ),
    'TYPO3\\CMS\\Core\\Localization\\Exception\\InvalidXmlFileException' => 
    array (
      't3lib_l10n_exception_invalidxmlfile' => 't3lib_l10n_exception_invalidxmlfile',
    ),
    'TYPO3\\CMS\\Core\\Localization\\LanguageStore' => 
    array (
      't3lib_l10n_store' => 't3lib_l10n_store',
    ),
    'TYPO3\\CMS\\Core\\Localization\\Locales' => 
    array (
      't3lib_l10n_locales' => 't3lib_l10n_locales',
    ),
    'TYPO3\\CMS\\Core\\Localization\\LocalizationFactory' => 
    array (
      't3lib_l10n_factory' => 't3lib_l10n_factory',
    ),
    'TYPO3\\CMS\\Core\\Localization\\Parser\\AbstractXmlParser' => 
    array (
      't3lib_l10n_parser_abstractxml' => 't3lib_l10n_parser_abstractxml',
    ),
    'TYPO3\\CMS\\Core\\Localization\\Parser\\LocalizationParserInterface' => 
    array (
      't3lib_l10n_parser' => 't3lib_l10n_parser',
    ),
    'TYPO3\\CMS\\Core\\Localization\\Parser\\LocallangArrayParser' => 
    array (
      't3lib_l10n_parser_llphp' => 't3lib_l10n_parser_llphp',
    ),
    'TYPO3\\CMS\\Core\\Localization\\Parser\\LocallangXmlParser' => 
    array (
      't3lib_l10n_parser_llxml' => 't3lib_l10n_parser_llxml',
    ),
    'TYPO3\\CMS\\Core\\Localization\\Parser\\XliffParser' => 
    array (
      't3lib_l10n_parser_xliff' => 't3lib_l10n_parser_xliff',
    ),
    'TYPO3\\CMS\\Core\\Locking\\Locker' => 
    array (
      't3lib_lock' => 't3lib_lock',
    ),
    'TYPO3\\CMS\\Core\\Mail\\Mailer' => 
    array (
      't3lib_mail_mailer' => 't3lib_mail_mailer',
    ),
    'TYPO3\\CMS\\Core\\Mail\\MailerAdapterInterface' => 
    array (
      't3lib_mail_maileradapter' => 't3lib_mail_maileradapter',
    ),
    'TYPO3\\CMS\\Core\\Mail\\MailMessage' => 
    array (
      't3lib_mail_message' => 't3lib_mail_message',
    ),
    'TYPO3\\CMS\\Core\\Mail\\MboxTransport' => 
    array (
      't3lib_mail_mboxtransport' => 't3lib_mail_mboxtransport',
    ),
    'TYPO3\\CMS\\Core\\Mail\\Rfc822AddressesParser' => 
    array (
      't3lib_mail_rfc822addressesparser' => 't3lib_mail_rfc822addressesparser',
    ),
    'TYPO3\\CMS\\Core\\Mail\\SwiftMailerAdapter' => 
    array (
      't3lib_mail_swiftmaileradapter' => 't3lib_mail_swiftmaileradapter',
    ),
    'TYPO3\\CMS\\Core\\Messaging\\AbstractMessage' => 
    array (
      't3lib_message_abstractmessage' => 't3lib_message_abstractmessage',
    ),
    'TYPO3\\CMS\\Core\\Messaging\\AbstractStandaloneMessage' => 
    array (
      't3lib_message_abstractstandalonemessage' => 't3lib_message_abstractstandalonemessage',
    ),
    'TYPO3\\CMS\\Core\\Messaging\\ErrorpageMessage' => 
    array (
      't3lib_message_errorpagemessage' => 't3lib_message_errorpagemessage',
    ),
    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage' => 
    array (
      't3lib_flashmessage' => 't3lib_flashmessage',
    ),
    'TYPO3\\CMS\\Core\\Messaging\\FlashMessageQueue' => 
    array (
      't3lib_flashmessagequeue' => 't3lib_flashmessagequeue',
    ),
    'TYPO3\\CMS\\Core\\Page\\PageRenderer' => 
    array (
      't3lib_pagerenderer' => 't3lib_pagerenderer',
    ),
    'TYPO3\\CMS\\Core\\Registry' => 
    array (
      't3lib_registry' => 't3lib_registry',
    ),
    'TYPO3\\CMS\\Core\\Resource\\ResourceCompressor' => 
    array (
      't3lib_compressor' => 't3lib_compressor',
    ),
    'TYPO3\\CMS\\Core\\Service\\AbstractService' => 
    array (
      't3lib_svbase' => 't3lib_svbase',
    ),
    'TYPO3\\CMS\\Core\\SingletonInterface' => 
    array (
      't3lib_singleton' => 't3lib_singleton',
    ),
    'TYPO3\\CMS\\Core\\TimeTracker\\NullTimeTracker' => 
    array (
      't3lib_timetracknull' => 't3lib_timetracknull',
    ),
    'TYPO3\\CMS\\Core\\TimeTracker\\TimeTracker' => 
    array (
      't3lib_timetrack' => 't3lib_timetrack',
    ),
    'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\AbstractTableConfigurationTreeDataProvider' => 
    array (
      't3lib_tree_tca_abstracttcatreedataprovider' => 't3lib_tree_tca_abstracttcatreedataprovider',
    ),
    'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\DatabaseTreeDataProvider' => 
    array (
      't3lib_tree_tca_databasetreedataprovider' => 't3lib_tree_tca_databasetreedataprovider',
    ),
    'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\DatabaseTreeNode' => 
    array (
      't3lib_tree_tca_databasenode' => 't3lib_tree_tca_databasenode',
    ),
    'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\ExtJsArrayTreeRenderer' => 
    array (
      't3lib_tree_tca_extjsarrayrenderer' => 't3lib_tree_tca_extjsarrayrenderer',
    ),
    'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\TableConfigurationTree' => 
    array (
      't3lib_tree_tca_tcatree' => 't3lib_tree_tca_tcatree',
    ),
    'TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\TreeDataProviderFactory' => 
    array (
      't3lib_tree_tca_dataproviderfactory' => 't3lib_tree_tca_dataproviderfactory',
    ),
    'TYPO3\\CMS\\Core\\TypoScript\\ConfigurationForm' => 
    array (
      't3lib_tsstyleconfig' => 't3lib_tsstyleconfig',
    ),
    'TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService' => 
    array (
      't3lib_tsparser_ext' => 't3lib_tsparser_ext',
    ),
    'TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser' => 
    array (
      't3lib_tsparser' => 't3lib_tsparser',
    ),
    'TYPO3\\CMS\\Core\\TypoScript\\TemplateService' => 
    array (
      't3lib_tstemplate' => 't3lib_tstemplate',
    ),
    'TYPO3\\CMS\\Core\\Utility\\ArrayUtility' => 
    array (
      't3lib_utility_array' => 't3lib_utility_array',
    ),
    'TYPO3\\CMS\\Core\\Utility\\ClientUtility' => 
    array (
      't3lib_utility_client' => 't3lib_utility_client',
    ),
    'TYPO3\\CMS\\Core\\Utility\\CommandUtility' => 
    array (
      't3lib_exec' => 't3lib_exec',
      't3lib_utility_command' => 't3lib_utility_command',
    ),
    'TYPO3\\CMS\\Core\\Utility\\DebugUtility' => 
    array (
      't3lib_utility_debug' => 't3lib_utility_debug',
    ),
    'TYPO3\\CMS\\Core\\Utility\\DiffUtility' => 
    array (
      't3lib_diff' => 't3lib_diff',
    ),
    'TYPO3\\CMS\\Core\\Utility\\File\\BasicFileUtility' => 
    array (
      't3lib_basicfilefunctions' => 't3lib_basicfilefunctions',
    ),
    'TYPO3\\CMS\\Core\\Utility\\File\\ExtendedFileUtility' => 
    array (
      't3lib_extfilefunctions' => 't3lib_extfilefunctions',
    ),
    'TYPO3\\CMS\\Core\\Utility\\File\\ExtendedFileUtilityProcessDataHookInterface' => 
    array (
      't3lib_extfilefunctions_processdatahook' => 't3lib_extfilefunctions_processdatahook',
    ),
    'TYPO3\\CMS\\Core\\Utility\\GeneralUtility' => 
    array (
      't3lib_div' => 't3lib_div',
    ),
    'TYPO3\\CMS\\Core\\Utility\\HttpUtility' => 
    array (
      't3lib_utility_http' => 't3lib_utility_http',
    ),
    'TYPO3\\CMS\\Core\\Utility\\MailUtility' => 
    array (
      't3lib_utility_mail' => 't3lib_utility_mail',
    ),
    'TYPO3\\CMS\\Core\\Utility\\MathUtility' => 
    array (
      't3lib_utility_math' => 't3lib_utility_math',
    ),
    'TYPO3\\CMS\\Core\\Utility\\MonitorUtility' => 
    array (
      't3lib_utility_monitor' => 't3lib_utility_monitor',
    ),
    'TYPO3\\CMS\\Core\\Utility\\PathUtility' => 
    array (
      't3lib_utility_path' => 't3lib_utility_path',
    ),
    'TYPO3\\CMS\\Core\\Utility\\PhpOptionsUtility' => 
    array (
      't3lib_utility_phpoptions' => 't3lib_utility_phpoptions',
    ),
    'TYPO3\\CMS\\Core\\Utility\\VersionNumberUtility' => 
    array (
      't3lib_utility_versionnumber' => 't3lib_utility_versionnumber',
    ),
    'TYPO3\\CMS\\Cshmanual\\Controller\\HelpModuleController' => 
    array (
      'sc_view_help' => 'sc_view_help',
    ),
    'TYPO3\\CMS\\Extbase\\Command\\HelpCommandController' => 
    array (
      'tx_extbase_command_helpcommandcontroller' => 'tx_extbase_command_helpcommandcontroller',
    ),
    'TYPO3\\CMS\\Extbase\\Configuration\\AbstractConfigurationManager' => 
    array (
      'tx_extbase_configuration_abstractconfigurationmanager' => 'tx_extbase_configuration_abstractconfigurationmanager',
    ),
    'TYPO3\\CMS\\Extbase\\Configuration\\BackendConfigurationManager' => 
    array (
      'tx_extbase_configuration_backendconfigurationmanager' => 'tx_extbase_configuration_backendconfigurationmanager',
    ),
    'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager' => 
    array (
      'tx_extbase_configuration_configurationmanager' => 'tx_extbase_configuration_configurationmanager',
    ),
    'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface' => 
    array (
      'tx_extbase_configuration_configurationmanagerinterface' => 'tx_extbase_configuration_configurationmanagerinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Configuration\\Exception' => 
    array (
      'tx_extbase_configuration_exception' => 'tx_extbase_configuration_exception',
    ),
    'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\ContainerIsLockedException' => 
    array (
      'tx_extbase_configuration_exception_containerislocked' => 'tx_extbase_configuration_exception_containerislocked',
    ),
    'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\InvalidConfigurationTypeException' => 
    array (
      'tx_extbase_configuration_exception_invalidconfigurationtype' => 'tx_extbase_configuration_exception_invalidconfigurationtype',
    ),
    'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\NoSuchFileException' => 
    array (
      'tx_extbase_configuration_exception_nosuchfile' => 'tx_extbase_configuration_exception_nosuchfile',
    ),
    'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\NoSuchOptionException' => 
    array (
      'tx_extbase_configuration_exception_nosuchoption' => 'tx_extbase_configuration_exception_nosuchoption',
    ),
    'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\ParseErrorException' => 
    array (
      'tx_extbase_configuration_exception_parseerror' => 'tx_extbase_configuration_exception_parseerror',
    ),
    'TYPO3\\CMS\\Extbase\\Configuration\\FrontendConfigurationManager' => 
    array (
      'tx_extbase_configuration_frontendconfigurationmanager' => 'tx_extbase_configuration_frontendconfigurationmanager',
    ),
    'TYPO3\\CMS\\Extbase\\Core\\Bootstrap' => 
    array (
      'tx_extbase_core_bootstrap' => 'tx_extbase_core_bootstrap',
    ),
    'TYPO3\\CMS\\Extbase\\Core\\BootstrapInterface' => 
    array (
      'tx_extbase_core_bootstrapinterface' => 'tx_extbase_core_bootstrapinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\AbstractFileCollection' => 
    array (
      'tx_extbase_domain_model_abstractfilecollection' => 'tx_extbase_domain_model_abstractfilecollection',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\AbstractFileFolder' => 
    array (
      'tx_extbase_domain_model_abstractfilefolder' => 'tx_extbase_domain_model_abstractfilefolder',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\BackendUser' => 
    array (
      'tx_extbase_domain_model_backenduser' => 'tx_extbase_domain_model_backenduser',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\BackendUserGroup' => 
    array (
      'tx_extbase_domain_model_backendusergroup' => 'tx_extbase_domain_model_backendusergroup',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\Category' => 
    array (
      'tx_extbase_domain_model_category' => 'tx_extbase_domain_model_category',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\File' => 
    array (
      'tx_extbase_domain_model_file' => 'tx_extbase_domain_model_file',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\FileMount' => 
    array (
      'tx_extbase_domain_model_filemount' => 'tx_extbase_domain_model_filemount',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\FileReference' => 
    array (
      'tx_extbase_domain_model_filereference' => 'tx_extbase_domain_model_filereference',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\Folder' => 
    array (
      'tx_extbase_domain_model_folder' => 'tx_extbase_domain_model_folder',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\FolderBasedFileCollection' => 
    array (
      'tx_extbase_domain_model_folderbasedfilecollection' => 'tx_extbase_domain_model_folderbasedfilecollection',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUser' => 
    array (
      'tx_extbase_domain_model_frontenduser' => 'tx_extbase_domain_model_frontenduser',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup' => 
    array (
      'tx_extbase_domain_model_frontendusergroup' => 'tx_extbase_domain_model_frontendusergroup',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Model\\StaticFileCollection' => 
    array (
      'tx_extbase_domain_model_staticfilecollection' => 'tx_extbase_domain_model_staticfilecollection',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Repository\\BackendUserGroupRepository' => 
    array (
      'tx_extbase_domain_repository_backenduserrepository' => 'tx_extbase_domain_repository_backenduserrepository',
      'tx_extbase_domain_repository_backendusergrouprepository' => 'tx_extbase_domain_repository_backendusergrouprepository',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Repository\\CategoryRepository' => 
    array (
      'tx_extbase_domain_repository_categoryrepository' => 'tx_extbase_domain_repository_categoryrepository',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Repository\\FileMountRepository' => 
    array (
      'tx_extbase_domain_repository_filemountrepository' => 'tx_extbase_domain_repository_filemountrepository',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserGroupRepository' => 
    array (
      'tx_extbase_domain_repository_frontendusergrouprepository' => 'tx_extbase_domain_repository_frontendusergrouprepository',
    ),
    'TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserRepository' => 
    array (
      'tx_extbase_domain_repository_frontenduserrepository' => 'tx_extbase_domain_repository_frontenduserrepository',
    ),
    'TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject' => 
    array (
      'tx_extbase_domainobject_abstractdomainobject' => 'tx_extbase_domainobject_abstractdomainobject',
    ),
    'TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity' => 
    array (
      'tx_extbase_domainobject_abstractentity' => 'tx_extbase_domainobject_abstractentity',
    ),
    'TYPO3\\CMS\\Extbase\\DomainObject\\AbstractValueObject' => 
    array (
      'tx_extbase_domainobject_abstractvalueobject' => 'tx_extbase_domainobject_abstractvalueobject',
    ),
    'TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface' => 
    array (
      'tx_extbase_domainobject_domainobjectinterface' => 'tx_extbase_domainobject_domainobjectinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Error\\Error' => 
    array (
      'tx_extbase_error_error' => 'tx_extbase_error_error',
    ),
    'TYPO3\\CMS\\Extbase\\Error\\Message' => 
    array (
      'tx_extbase_error_message' => 'tx_extbase_error_message',
    ),
    'TYPO3\\CMS\\Extbase\\Error\\Notice' => 
    array (
      'tx_extbase_error_notice' => 'tx_extbase_error_notice',
    ),
    'TYPO3\\CMS\\Extbase\\Error\\Result' => 
    array (
      'tx_extbase_error_result' => 'tx_extbase_error_result',
    ),
    'TYPO3\\CMS\\Extbase\\Error\\Warning' => 
    array (
      'tx_extbase_error_warning' => 'tx_extbase_error_warning',
    ),
    'TYPO3\\CMS\\Extbase\\Exception' => 
    array (
      'tx_extbase_exception' => 'tx_extbase_exception',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command' => 
    array (
      'tx_extbase_mvc_cli_command' => 'tx_extbase_mvc_cli_command',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\CommandArgumentDefinition' => 
    array (
      'tx_extbase_mvc_cli_commandargumentdefinition' => 'tx_extbase_mvc_cli_commandargumentdefinition',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\CommandManager' => 
    array (
      'tx_extbase_mvc_cli_commandmanager' => 'tx_extbase_mvc_cli_commandmanager',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Request' => 
    array (
      'tx_extbase_mvc_cli_request' => 'tx_extbase_mvc_cli_request',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\RequestBuilder' => 
    array (
      'tx_extbase_mvc_cli_requestbuilder' => 'tx_extbase_mvc_cli_requestbuilder',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\RequestHandler' => 
    array (
      'tx_extbase_mvc_cli_requesthandler' => 'tx_extbase_mvc_cli_requesthandler',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Response' => 
    array (
      'tx_extbase_mvc_cli_response' => 'tx_extbase_mvc_cli_response',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\AbstractController' => 
    array (
      'tx_extbase_mvc_controller_abstractcontroller' => 'tx_extbase_mvc_controller_abstractcontroller',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ActionController' => 
    array (
      'tx_extbase_mvc_controller_actioncontroller' => 'tx_extbase_mvc_controller_actioncontroller',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Argument' => 
    array (
      'tx_extbase_mvc_controller_argument' => 'tx_extbase_mvc_controller_argument',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ArgumentError' => 
    array (
      'tx_extbase_mvc_controller_argumenterror' => 'tx_extbase_mvc_controller_argumenterror',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Arguments' => 
    array (
      'tx_extbase_mvc_controller_arguments' => 'tx_extbase_mvc_controller_arguments',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ArgumentsValidator' => 
    array (
      'tx_extbase_mvc_controller_argumentsvalidator' => 'tx_extbase_mvc_controller_argumentsvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\CommandController' => 
    array (
      'tx_extbase_mvc_controller_commandcontroller' => 'tx_extbase_mvc_controller_commandcontroller',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\CommandControllerInterface' => 
    array (
      'tx_extbase_mvc_controller_commandcontrollerinterface' => 'tx_extbase_mvc_controller_commandcontrollerinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerContext' => 
    array (
      'tx_extbase_mvc_controller_controllercontext' => 'tx_extbase_mvc_controller_controllercontext',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerInterface' => 
    array (
      'tx_extbase_mvc_controller_controllerinterface' => 'tx_extbase_mvc_controller_controllerinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Exception\\RequiredArgumentMissingException' => 
    array (
      'tx_extbase_mvc_controller_exception_requiredargumentmissingexception' => 'tx_extbase_mvc_controller_exception_requiredargumentmissingexception',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\FlashMessageContainer' => 
    array (
      'tx_extbase_mvc_controller_flashmessages' => 'tx_extbase_mvc_controller_flashmessages',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\MvcPropertyMappingConfiguration' => 
    array (
      'tx_extbase_mvc_controller_mvcpropertymappingconfiguration' => 'tx_extbase_mvc_controller_mvcpropertymappingconfiguration',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Dispatcher' => 
    array (
      'tx_extbase_mvc_dispatcher' => 'tx_extbase_mvc_dispatcher',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception' => 
    array (
      'tx_extbase_mvc_exception' => 'tx_extbase_mvc_exception',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\AmbiguousCommandIdentifierException' => 
    array (
      'tx_extbase_mvc_exception_ambiguouscommandidentifier' => 'tx_extbase_mvc_exception_ambiguouscommandidentifier',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\CommandException' => 
    array (
      'tx_extbase_mvc_exception_command' => 'tx_extbase_mvc_exception_command',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InfiniteLoopException' => 
    array (
      'tx_extbase_mvc_exception_infiniteloop' => 'tx_extbase_mvc_exception_infiniteloop',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidActionNameException' => 
    array (
      'tx_extbase_mvc_exception_invalidactionname' => 'tx_extbase_mvc_exception_invalidactionname',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidArgumentMixingException' => 
    array (
      'tx_extbase_mvc_exception_invalidargumentmixing' => 'tx_extbase_mvc_exception_invalidargumentmixing',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidArgumentNameException' => 
    array (
      'tx_extbase_mvc_exception_invalidargumentname' => 'tx_extbase_mvc_exception_invalidargumentname',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidArgumentTypeException' => 
    array (
      'tx_extbase_mvc_exception_invalidargumenttype' => 'tx_extbase_mvc_exception_invalidargumenttype',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidArgumentValueException' => 
    array (
      'tx_extbase_mvc_exception_invalidargumentvalue' => 'tx_extbase_mvc_exception_invalidargumentvalue',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidCommandIdentifierException' => 
    array (
      'tx_extbase_mvc_exception_invalidcommandidentifier' => 'tx_extbase_mvc_exception_invalidcommandidentifier',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidControllerException' => 
    array (
      'tx_extbase_mvc_exception_invalidcontroller' => 'tx_extbase_mvc_exception_invalidcontroller',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidControllerNameException' => 
    array (
      'tx_extbase_mvc_exception_invalidcontrollername' => 'tx_extbase_mvc_exception_invalidcontrollername',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidExtensionNameException' => 
    array (
      'tx_extbase_mvc_exception_invalidextensionname' => 'tx_extbase_mvc_exception_invalidextensionname',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidMarkerException' => 
    array (
      'tx_extbase_mvc_exception_invalidmarker' => 'tx_extbase_mvc_exception_invalidmarker',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidOrNoRequestHashException' => 
    array (
      'tx_extbase_mvc_exception_invalidornorequesthash' => 'tx_extbase_mvc_exception_invalidornorequesthash',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidRequestMethodException' => 
    array (
      'tx_extbase_mvc_exception_invalidrequestmethod' => 'tx_extbase_mvc_exception_invalidrequestmethod',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidRequestTypeException' => 
    array (
      'tx_extbase_mvc_exception_invalidrequesttype' => 'tx_extbase_mvc_exception_invalidrequesttype',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidTemplateResourceException' => 
    array (
      'tx_extbase_mvc_exception_invalidtemplateresource' => 'tx_extbase_mvc_exception_invalidtemplateresource',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidUriPatternException' => 
    array (
      'tx_extbase_mvc_exception_invaliduripattern' => 'tx_extbase_mvc_exception_invaliduripattern',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidViewHelperException' => 
    array (
      'tx_extbase_mvc_exception_invalidviewhelper' => 'tx_extbase_mvc_exception_invalidviewhelper',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\NoSuchActionException' => 
    array (
      'tx_extbase_mvc_exception_nosuchaction' => 'tx_extbase_mvc_exception_nosuchaction',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\NoSuchArgumentException' => 
    array (
      'tx_extbase_mvc_exception_nosuchargument' => 'tx_extbase_mvc_exception_nosuchargument',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\NoSuchCommandException' => 
    array (
      'tx_extbase_mvc_exception_nosuchcommand' => 'tx_extbase_mvc_exception_nosuchcommand',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\NoSuchControllerException' => 
    array (
      'tx_extbase_mvc_exception_nosuchcontroller' => 'tx_extbase_mvc_exception_nosuchcontroller',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\RequiredArgumentMissingException' => 
    array (
      'tx_extbase_mvc_exception_requiredargumentmissing' => 'tx_extbase_mvc_exception_requiredargumentmissing',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\StopActionException' => 
    array (
      'tx_extbase_mvc_exception_stopaction' => 'tx_extbase_mvc_exception_stopaction',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\UnsupportedRequestTypeException' => 
    array (
      'tx_extbase_mvc_exception_unsupportedrequesttype' => 'tx_extbase_mvc_exception_unsupportedrequesttype',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Request' => 
    array (
      'tx_extbase_mvc_request' => 'tx_extbase_mvc_request',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\RequestHandlerInterface' => 
    array (
      'tx_extbase_mvc_requesthandlerinterface' => 'tx_extbase_mvc_requesthandlerinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\RequestHandlerResolver' => 
    array (
      'tx_extbase_mvc_requesthandlerresolver' => 'tx_extbase_mvc_requesthandlerresolver',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\RequestInterface' => 
    array (
      'tx_extbase_mvc_requestinterface' => 'tx_extbase_mvc_requestinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Response' => 
    array (
      'tx_extbase_mvc_response' => 'tx_extbase_mvc_response',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\ResponseInterface' => 
    array (
      'tx_extbase_mvc_responseinterface' => 'tx_extbase_mvc_responseinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\View\\AbstractView' => 
    array (
      'tx_extbase_mvc_view_abstractview' => 'tx_extbase_mvc_view_abstractview',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\View\\EmptyView' => 
    array (
      'tx_extbase_mvc_view_emptyview' => 'tx_extbase_mvc_view_emptyview',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\View\\NotFoundView' => 
    array (
      'tx_extbase_mvc_view_notfoundview' => 'tx_extbase_mvc_view_notfoundview',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface' => 
    array (
      'tx_extbase_mvc_view_viewinterface' => 'tx_extbase_mvc_view_viewinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Web\\AbstractRequestHandler' => 
    array (
      'tx_extbase_mvc_web_abstractrequesthandler' => 'tx_extbase_mvc_web_abstractrequesthandler',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Web\\BackendRequestHandler' => 
    array (
      'tx_extbase_mvc_web_backendrequesthandler' => 'tx_extbase_mvc_web_backendrequesthandler',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Web\\FrontendRequestHandler' => 
    array (
      'tx_extbase_mvc_web_frontendrequesthandler' => 'tx_extbase_mvc_web_frontendrequesthandler',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Web\\Request' => 
    array (
      'tx_extbase_mvc_web_request' => 'tx_extbase_mvc_web_request',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Web\\RequestBuilder' => 
    array (
      'tx_extbase_mvc_web_requestbuilder' => 'tx_extbase_mvc_web_requestbuilder',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Web\\Response' => 
    array (
      'tx_extbase_mvc_web_response' => 'tx_extbase_mvc_web_response',
    ),
    'TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder' => 
    array (
      'tx_extbase_mvc_web_routing_uribuilder' => 'tx_extbase_mvc_web_routing_uribuilder',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\Container\\ClassInfo' => 
    array (
      'tx_extbase_object_container_classinfo' => 'tx_extbase_object_container_classinfo',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\Container\\ClassInfoCache' => 
    array (
      'tx_extbase_object_container_classinfocache' => 'tx_extbase_object_container_classinfocache',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\Container\\ClassInfoFactory' => 
    array (
      'tx_extbase_object_container_classinfofactory' => 'tx_extbase_object_container_classinfofactory',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\Container\\Container' => 
    array (
      'tx_extbase_object_container_container' => 'tx_extbase_object_container_container',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\Container\\Exception\\CannotInitializeCacheException' => 
    array (
      'tx_extbase_object_container_exception_cannotinitializecacheexception' => 'tx_extbase_object_container_exception_cannotinitializecacheexception',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\Container\\Exception\\TooManyRecursionLevelsException' => 
    array (
      'tx_extbase_object_container_exception_toomanyrecursionlevelsexception' => 'tx_extbase_object_container_exception_toomanyrecursionlevelsexception',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\Container\\Exception\\UnknownObjectException' => 
    array (
      'tx_extbase_object_container_exception_unknownobjectexception' => 'tx_extbase_object_container_exception_unknownobjectexception',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\Exception' => 
    array (
      'tx_extbase_object_exception' => 'tx_extbase_object_exception',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\Exception\\CannotBuildObjectException' => 
    array (
      'tx_extbase_object_exception_cannotbuildobject' => 'tx_extbase_object_exception_cannotbuildobject',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\Exception\\CannotReconstituteObjectException' => 
    array (
      'tx_extbase_object_exception_cannotreconstituteobject' => 'tx_extbase_object_exception_cannotreconstituteobject',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\Exception\\WrongScopeException' => 
    array (
      'tx_extbase_object_exception_wrongscope' => 'tx_extbase_object_exception_wrongscope',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\InvalidClassException' => 
    array (
      'tx_extbase_object_invalidclass' => 'tx_extbase_object_invalidclass',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\InvalidObjectConfigurationException' => 
    array (
      'tx_extbase_object_invalidobjectconfiguration' => 'tx_extbase_object_invalidobjectconfiguration',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\InvalidObjectException' => 
    array (
      'tx_extbase_object_invalidobject' => 'tx_extbase_object_invalidobject',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\ObjectAlreadyRegisteredException' => 
    array (
      'tx_extbase_object_objectalreadyregistered' => 'tx_extbase_object_objectalreadyregistered',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\ObjectManager' => 
    array (
      'tx_extbase_object_objectmanager' => 'tx_extbase_object_objectmanager',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface' => 
    array (
      'tx_extbase_object_objectmanagerinterface' => 'tx_extbase_object_objectmanagerinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\UnknownClassException' => 
    array (
      'tx_extbase_object_unknownclass' => 'tx_extbase_object_unknownclass',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\UnknownInterfaceException' => 
    array (
      'tx_extbase_object_unknowninterface' => 'tx_extbase_object_unknowninterface',
    ),
    'TYPO3\\CMS\\Extbase\\Object\\UnresolvedDependenciesException' => 
    array (
      'tx_extbase_object_unresolveddependencies' => 'tx_extbase_object_unresolveddependencies',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend' => 
    array (
      'tx_extbase_persistence_backend' => 'tx_extbase_persistence_backend',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\BackendInterface' => 
    array (
      'tx_extbase_persistence_backendinterface' => 'tx_extbase_persistence_backendinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception' => 
    array (
      'tx_extbase_persistence_exception' => 'tx_extbase_persistence_exception',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\CleanStateNotMemorizedException' => 
    array (
      'tx_extbase_persistence_exception_cleanstatenotmemorized' => 'tx_extbase_persistence_exception_cleanstatenotmemorized',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Exception\\IllegalObjectTypeException' => 
    array (
      'tx_extbase_persistence_exception_illegalobjecttype' => 'tx_extbase_persistence_exception_illegalobjecttype',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\InvalidClassException' => 
    array (
      'tx_extbase_persistence_exception_invalidclass' => 'tx_extbase_persistence_exception_invalidclass',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\InvalidNumberOfConstraintsException' => 
    array (
      'tx_extbase_persistence_exception_invalidnumberofconstraints' => 'tx_extbase_persistence_exception_invalidnumberofconstraints',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\InvalidPropertyTypeException' => 
    array (
      'tx_extbase_persistence_exception_invalidpropertytype' => 'tx_extbase_persistence_exception_invalidpropertytype',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\MissingBackendException' => 
    array (
      'tx_extbase_persistence_exception_missingbackend' => 'tx_extbase_persistence_exception_missingbackend',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\RepositoryException' => 
    array (
      'tx_extbase_persistence_exception_repositoryexception' => 'tx_extbase_persistence_exception_repositoryexception',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\TooDirtyException' => 
    array (
      'tx_extbase_persistence_exception_toodirty' => 'tx_extbase_persistence_exception_toodirty',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\UnexpectedTypeException' => 
    array (
      'tx_extbase_persistence_exception_unexpectedtypeexception' => 'tx_extbase_persistence_exception_unexpectedtypeexception',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Exception\\UnknownObjectException' => 
    array (
      'tx_extbase_persistence_exception_unknownobject' => 'tx_extbase_persistence_exception_unknownobject',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\UnsupportedMethodException' => 
    array (
      'tx_extbase_persistence_exception_unsupportedmethod' => 'tx_extbase_persistence_exception_unsupportedmethod',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\UnsupportedOrderException' => 
    array (
      'tx_extbase_persistence_exception_unsupportedorder' => 'tx_extbase_persistence_exception_unsupportedorder',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\UnsupportedRelationException' => 
    array (
      'tx_extbase_persistence_exception_unsupportedrelation' => 'tx_extbase_persistence_exception_unsupportedrelation',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\InconsistentQuerySettingsException' => 
    array (
      'tx_extbase_persistence_generic_exception_inconsistentquerysettings' => 'tx_extbase_persistence_generic_exception_inconsistentquerysettings',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\IdentityMap' => 
    array (
      'tx_extbase_persistence_identitymap' => 'tx_extbase_persistence_identitymap',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LazyLoadingProxy' => 
    array (
      'tx_extbase_persistence_lazyloadingproxy' => 'tx_extbase_persistence_lazyloadingproxy',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LazyObjectStorage' => 
    array (
      'tx_extbase_persistence_lazyobjectstorage' => 'tx_extbase_persistence_lazyobjectstorage',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LoadingStrategyInterface' => 
    array (
      'tx_extbase_persistence_loadingstrategyinterface' => 'tx_extbase_persistence_loadingstrategyinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap' => 
    array (
      'tx_extbase_persistence_mapper_columnmap' => 'tx_extbase_persistence_mapper_columnmap',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMap' => 
    array (
      'tx_extbase_persistence_mapper_datamap' => 'tx_extbase_persistence_mapper_datamap',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapFactory' => 
    array (
      'tx_extbase_persistence_mapper_datamapfactory' => 'tx_extbase_persistence_mapper_datamapfactory',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper' => 
    array (
      'tx_extbase_persistence_mapper_datamapper' => 'tx_extbase_persistence_mapper_datamapper',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\ObjectMonitoringInterface' => 
    array (
      'tx_extbase_persistence_objectmonitoringinterface' => 'tx_extbase_persistence_objectmonitoringinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage' => 
    array (
      'tx_extbase_persistence_objectstorage' => 'tx_extbase_persistence_objectstorage',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager' => 
    array (
      'tx_extbase_persistence_manager' => 'tx_extbase_persistence_manager',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface' => 
    array (
      'tx_extbase_persistence_persistencemanagerinterface' => 'tx_extbase_persistence_persistencemanagerinterface',
      'tx_extbase_persistence_managerinterface' => 'tx_extbase_persistence_managerinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PropertyType' => 
    array (
      'tx_extbase_persistence_propertytype' => 'tx_extbase_persistence_propertytype',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\AndInterface' => 
    array (
      'tx_extbase_persistence_qom_andinterface' => 'tx_extbase_persistence_qom_andinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\BindVariableValue' => 
    array (
      'tx_extbase_persistence_qom_bindvariablevalue' => 'tx_extbase_persistence_qom_bindvariablevalue',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\BindVariableValueInterface' => 
    array (
      'tx_extbase_persistence_qom_bindvariablevalueinterface' => 'tx_extbase_persistence_qom_bindvariablevalueinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Comparison' => 
    array (
      'tx_extbase_persistence_qom_comparison' => 'tx_extbase_persistence_qom_comparison',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\ComparisonInterface' => 
    array (
      'tx_extbase_persistence_qom_comparisoninterface' => 'tx_extbase_persistence_qom_comparisoninterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Constraint' => 
    array (
      'tx_extbase_persistence_qom_constraint' => 'tx_extbase_persistence_qom_constraint',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\ConstraintInterface' => 
    array (
      'tx_extbase_persistence_qom_constraintinterface' => 'tx_extbase_persistence_qom_constraintinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\DynamicOperand' => 
    array (
      'tx_extbase_persistence_qom_dynamicoperand' => 'tx_extbase_persistence_qom_dynamicoperand',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\DynamicOperandInterface' => 
    array (
      'tx_extbase_persistence_qom_dynamicoperandinterface' => 'tx_extbase_persistence_qom_dynamicoperandinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\EquiJoinCondition' => 
    array (
      'tx_extbase_persistence_qom_equijoincondition' => 'tx_extbase_persistence_qom_equijoincondition',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\EquiJoinConditionInterface' => 
    array (
      'tx_extbase_persistence_qom_equijoinconditioninterface' => 'tx_extbase_persistence_qom_equijoinconditioninterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Join' => 
    array (
      'tx_extbase_persistence_qom_join' => 'tx_extbase_persistence_qom_join',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\JoinConditionInterface' => 
    array (
      'tx_extbase_persistence_qom_joinconditioninterface' => 'tx_extbase_persistence_qom_joinconditioninterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\JoinInterface' => 
    array (
      'tx_extbase_persistence_qom_joininterface' => 'tx_extbase_persistence_qom_joininterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LogicalAnd' => 
    array (
      'tx_extbase_persistence_qom_logicaland' => 'tx_extbase_persistence_qom_logicaland',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LogicalNot' => 
    array (
      'tx_extbase_persistence_qom_logicalnot' => 'tx_extbase_persistence_qom_logicalnot',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LogicalOr' => 
    array (
      'tx_extbase_persistence_qom_logicalor' => 'tx_extbase_persistence_qom_logicalor',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LowerCase' => 
    array (
      'tx_extbase_persistence_qom_lowercase' => 'tx_extbase_persistence_qom_lowercase',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LowerCaseInterface' => 
    array (
      'tx_extbase_persistence_qom_lowercaseinterface' => 'tx_extbase_persistence_qom_lowercaseinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\NotInterface' => 
    array (
      'tx_extbase_persistence_qom_notinterface' => 'tx_extbase_persistence_qom_notinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Operand' => 
    array (
      'tx_extbase_persistence_qom_operand' => 'tx_extbase_persistence_qom_operand',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\OperandInterface' => 
    array (
      'tx_extbase_persistence_qom_operandinterface' => 'tx_extbase_persistence_qom_operandinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Ordering' => 
    array (
      'tx_extbase_persistence_qom_ordering' => 'tx_extbase_persistence_qom_ordering',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\OrderingInterface' => 
    array (
      'tx_extbase_persistence_qom_orderinginterface' => 'tx_extbase_persistence_qom_orderinginterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\OrInterface' => 
    array (
      'tx_extbase_persistence_qom_orinterface' => 'tx_extbase_persistence_qom_orinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\PropertyValue' => 
    array (
      'tx_extbase_persistence_qom_propertyvalue' => 'tx_extbase_persistence_qom_propertyvalue',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\PropertyValueInterface' => 
    array (
      'tx_extbase_persistence_qom_propertyvalueinterface' => 'tx_extbase_persistence_qom_propertyvalueinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\QueryObjectModelConstantsInterface' => 
    array (
      'tx_extbase_persistence_qom_queryobjectmodelconstantsinterface' => 'tx_extbase_persistence_qom_queryobjectmodelconstantsinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\QueryObjectModelFactory' => 
    array (
      'tx_extbase_persistence_qom_queryobjectmodelfactory' => 'tx_extbase_persistence_qom_queryobjectmodelfactory',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\QueryObjectModelFactoryInterface' => 
    array (
      'tx_extbase_persistence_qom_queryobjectmodelfactoryinterface' => 'tx_extbase_persistence_qom_queryobjectmodelfactoryinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Selector' => 
    array (
      'tx_extbase_persistence_qom_selector' => 'tx_extbase_persistence_qom_selector',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\SelectorInterface' => 
    array (
      'tx_extbase_persistence_qom_selectorinterface' => 'tx_extbase_persistence_qom_selectorinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\SourceInterface' => 
    array (
      'tx_extbase_persistence_qom_sourceinterface' => 'tx_extbase_persistence_qom_sourceinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Statement' => 
    array (
      'tx_extbase_persistence_qom_statement' => 'tx_extbase_persistence_qom_statement',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\StaticOperand' => 
    array (
      'tx_extbase_persistence_qom_staticoperand' => 'tx_extbase_persistence_qom_staticoperand',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\StaticOperandInterface' => 
    array (
      'tx_extbase_persistence_qom_staticoperandinterface' => 'tx_extbase_persistence_qom_staticoperandinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\UpperCase' => 
    array (
      'tx_extbase_persistence_qom_uppercase' => 'tx_extbase_persistence_qom_uppercase',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\UpperCaseInterface' => 
    array (
      'tx_extbase_persistence_qom_uppercaseinterface' => 'tx_extbase_persistence_qom_uppercaseinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Query' => 
    array (
      'tx_extbase_persistence_query' => 'tx_extbase_persistence_query',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryFactory' => 
    array (
      'tx_extbase_persistence_queryfactory' => 'tx_extbase_persistence_queryfactory',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryFactoryInterface' => 
    array (
      'tx_extbase_persistence_queryfactoryinterface' => 'tx_extbase_persistence_queryfactoryinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\QueryInterface' => 
    array (
      'tx_extbase_persistence_queryinterface' => 'tx_extbase_persistence_queryinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryResult' => 
    array (
      'tx_extbase_persistence_queryresult' => 'tx_extbase_persistence_queryresult',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface' => 
    array (
      'tx_extbase_persistence_queryresultinterface' => 'tx_extbase_persistence_queryresultinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface' => 
    array (
      'tx_extbase_persistence_querysettingsinterface' => 'tx_extbase_persistence_querysettingsinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Repository' => 
    array (
      'tx_extbase_persistence_repository' => 'tx_extbase_persistence_repository',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\RepositoryInterface' => 
    array (
      'tx_extbase_persistence_repositoryinterface' => 'tx_extbase_persistence_repositoryinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Session' => 
    array (
      'tx_extbase_persistence_session' => 'tx_extbase_persistence_session',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\BackendInterface' => 
    array (
      'tx_extbase_persistence_storage_backendinterface' => 'tx_extbase_persistence_storage_backendinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Exception\\BadConstraintException' => 
    array (
      'tx_extbase_persistence_storage_exception_badconstraint' => 'tx_extbase_persistence_storage_exception_badconstraint',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Exception\\SqlErrorException' => 
    array (
      'tx_extbase_persistence_storage_exception_sqlerror' => 'tx_extbase_persistence_storage_exception_sqlerror',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend' => 
    array (
      'tx_extbase_persistence_storage_typo3dbbackend' => 'tx_extbase_persistence_storage_typo3dbbackend',
    ),
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings' => 
    array (
      'tx_extbase_persistence_typo3querysettings' => 'tx_extbase_persistence_typo3querysettings',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception' => 
    array (
      'tx_extbase_property_exception' => 'tx_extbase_property_exception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\DuplicateObjectException' => 
    array (
      'tx_extbase_property_exception_duplicateobjectexception' => 'tx_extbase_property_exception_duplicateobjectexception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\DuplicateTypeConverterException' => 
    array (
      'tx_extbase_property_exception_duplicatetypeconverterexception' => 'tx_extbase_property_exception_duplicatetypeconverterexception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\FormatNotSupportedException' => 
    array (
      'tx_extbase_property_exception_formatnotsupportedexception' => 'tx_extbase_property_exception_formatnotsupportedexception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidDataTypeException' => 
    array (
      'tx_extbase_property_exception_invaliddatatypeexception' => 'tx_extbase_property_exception_invaliddatatypeexception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidFormatException' => 
    array (
      'tx_extbase_property_exception_invalidformatexception' => 'tx_extbase_property_exception_invalidformatexception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidPropertyException' => 
    array (
      'tx_extbase_property_exception_invalidpropertyexception' => 'tx_extbase_property_exception_invalidpropertyexception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidPropertyMappingConfigurationException' => 
    array (
      'tx_extbase_property_exception_invalidpropertymappingconfigurationexception' => 'tx_extbase_property_exception_invalidpropertymappingconfigurationexception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidSourceException' => 
    array (
      'tx_extbase_property_exception_invalidsource' => 'tx_extbase_property_exception_invalidsource',
      'tx_extbase_property_exception_invalidsourceexception' => 'tx_extbase_property_exception_invalidsourceexception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidTargetException' => 
    array (
      'tx_extbase_property_exception_invalidtarget' => 'tx_extbase_property_exception_invalidtarget',
      'tx_extbase_property_exception_invalidtargetexception' => 'tx_extbase_property_exception_invalidtargetexception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\TargetNotFoundException' => 
    array (
      'tx_extbase_property_exception_targetnotfoundexception' => 'tx_extbase_property_exception_targetnotfoundexception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\TypeConverterException' => 
    array (
      'tx_extbase_property_exception_typeconverterexception' => 'tx_extbase_property_exception_typeconverterexception',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\Mapper' => 
    array (
      'tx_extbase_property_mapper' => 'tx_extbase_property_mapper',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\MappingResults' => 
    array (
      'tx_extbase_property_mappingresults' => 'tx_extbase_property_mappingresults',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\PropertyMapper' => 
    array (
      'tx_extbase_property_propertymapper' => 'tx_extbase_property_propertymapper',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfiguration' => 
    array (
      'tx_extbase_property_propertymappingconfiguration' => 'tx_extbase_property_propertymappingconfiguration',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfigurationBuilder' => 
    array (
      'tx_extbase_property_propertymappingconfigurationbuilder' => 'tx_extbase_property_propertymappingconfigurationbuilder',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfigurationInterface' => 
    array (
      'tx_extbase_property_propertymappingconfigurationinterface' => 'tx_extbase_property_propertymappingconfigurationinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\AbstractFileCollectionConverter' => 
    array (
      'tx_extbase_property_typeconverter_abstractfilecollectionconverter' => 'tx_extbase_property_typeconverter_abstractfilecollectionconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\AbstractFileFolderConverter' => 
    array (
      'tx_extbase_property_typeconverter_abstractfilefolderconverter' => 'tx_extbase_property_typeconverter_abstractfilefolderconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\AbstractTypeConverter' => 
    array (
      'tx_extbase_property_typeconverter_abstracttypeconverter' => 'tx_extbase_property_typeconverter_abstracttypeconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\ArrayConverter' => 
    array (
      'tx_extbase_property_typeconverter_arrayconverter' => 'tx_extbase_property_typeconverter_arrayconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\BooleanConverter' => 
    array (
      'tx_extbase_property_typeconverter_booleanconverter' => 'tx_extbase_property_typeconverter_booleanconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter' => 
    array (
      'tx_extbase_property_typeconverter_datetimeconverter' => 'tx_extbase_property_typeconverter_datetimeconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\FileConverter' => 
    array (
      'tx_extbase_property_typeconverter_fileconverter' => 'tx_extbase_property_typeconverter_fileconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\FileReferenceConverter' => 
    array (
      'tx_extbase_property_typeconverter_filereferenceconverter' => 'tx_extbase_property_typeconverter_filereferenceconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\FloatConverter' => 
    array (
      'tx_extbase_property_typeconverter_floatconverter' => 'tx_extbase_property_typeconverter_floatconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\FolderBasedFileCollectionConverter' => 
    array (
      'tx_extbase_property_typeconverter_folderbasedfilecollectionconverter' => 'tx_extbase_property_typeconverter_folderbasedfilecollectionconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\FolderConverter' => 
    array (
      'tx_extbase_property_typeconverter_folderconverter' => 'tx_extbase_property_typeconverter_folderconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\IntegerConverter' => 
    array (
      'tx_extbase_property_typeconverter_integerconverter' => 'tx_extbase_property_typeconverter_integerconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\ObjectStorageConverter' => 
    array (
      'tx_extbase_property_typeconverter_objectstorageconverter' => 'tx_extbase_property_typeconverter_objectstorageconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter' => 
    array (
      'tx_extbase_property_typeconverter_persistentobjectconverter' => 'tx_extbase_property_typeconverter_persistentobjectconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\StaticFileCollectionConverter' => 
    array (
      'tx_extbase_property_typeconverter_staticfilecollectionconverter' => 'tx_extbase_property_typeconverter_staticfilecollectionconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\StringConverter' => 
    array (
      'tx_extbase_property_typeconverter_stringconverter' => 'tx_extbase_property_typeconverter_stringconverter',
    ),
    'TYPO3\\CMS\\Extbase\\Property\\TypeConverterInterface' => 
    array (
      'tx_extbase_property_typeconverterinterface' => 'tx_extbase_property_typeconverterinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\ClassReflection' => 
    array (
      'tx_extbase_reflection_classreflection' => 'tx_extbase_reflection_classreflection',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\ClassSchema' => 
    array (
      'tx_extbase_reflection_classschema' => 'tx_extbase_reflection_classschema',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\DocCommentParser' => 
    array (
      'tx_extbase_reflection_doccommentparser' => 'tx_extbase_reflection_doccommentparser',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\Exception' => 
    array (
      'tx_extbase_reflection_exception' => 'tx_extbase_reflection_exception',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\Exception\\InvalidPropertyTypeException' => 
    array (
      'tx_extbase_reflection_exception_invalidpropertytype' => 'tx_extbase_reflection_exception_invalidpropertytype',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\Exception\\PropertyNotAccessibleException' => 
    array (
      'tx_extbase_reflection_exception_propertynotaccessibleexception' => 'tx_extbase_reflection_exception_propertynotaccessibleexception',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\Exception\\UnknownClassException' => 
    array (
      'tx_extbase_reflection_exception_unknownclass' => 'tx_extbase_reflection_exception_unknownclass',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\MethodReflection' => 
    array (
      'tx_extbase_reflection_methodreflection' => 'tx_extbase_reflection_methodreflection',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\ObjectAccess' => 
    array (
      'tx_extbase_reflection_objectaccess' => 'tx_extbase_reflection_objectaccess',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\ParameterReflection' => 
    array (
      'tx_extbase_reflection_parameterreflection' => 'tx_extbase_reflection_parameterreflection',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\PropertyReflection' => 
    array (
      'tx_extbase_reflection_propertyreflection' => 'tx_extbase_reflection_propertyreflection',
    ),
    'TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService' => 
    array (
      'tx_extbase_reflection_service' => 'tx_extbase_reflection_service',
    ),
    'TYPO3\\CMS\\Extbase\\Scheduler\\FieldProvider' => 
    array (
      'tx_extbase_scheduler_fieldprovider' => 'tx_extbase_scheduler_fieldprovider',
    ),
    'TYPO3\\CMS\\Extbase\\Scheduler\\Task' => 
    array (
      'tx_extbase_scheduler_task' => 'tx_extbase_scheduler_task',
    ),
    'TYPO3\\CMS\\Extbase\\Scheduler\\TaskExecutor' => 
    array (
      'tx_extbase_scheduler_taskexecutor' => 'tx_extbase_scheduler_taskexecutor',
    ),
    'TYPO3\\CMS\\Extbase\\Security\\Channel\\RequestHashService' => 
    array (
      'tx_extbase_security_channel_requesthashservice' => 'tx_extbase_security_channel_requesthashservice',
    ),
    'TYPO3\\CMS\\Extbase\\Security\\Cryptography\\HashService' => 
    array (
      'tx_extbase_security_cryptography_hashservice' => 'tx_extbase_security_cryptography_hashservice',
    ),
    'TYPO3\\CMS\\Extbase\\Security\\Exception' => 
    array (
      'tx_extbase_security_exception' => 'tx_extbase_security_exception',
    ),
    'TYPO3\\CMS\\Extbase\\Security\\Exception\\InvalidArgumentForHashGenerationException' => 
    array (
      'tx_extbase_security_exception_invalidargumentforhashgeneration' => 'tx_extbase_security_exception_invalidargumentforhashgeneration',
    ),
    'TYPO3\\CMS\\Extbase\\Security\\Exception\\InvalidArgumentForRequestHashGenerationException' => 
    array (
      'tx_extbase_security_exception_invalidargumentforrequesthashgeneration' => 'tx_extbase_security_exception_invalidargumentforrequesthashgeneration',
    ),
    'TYPO3\\CMS\\Extbase\\Security\\Exception\\InvalidHashException' => 
    array (
      'tx_extbase_security_exception_invalidhash' => 'tx_extbase_security_exception_invalidhash',
    ),
    'TYPO3\\CMS\\Extbase\\Security\\Exception\\SyntacticallyWrongRequestHashException' => 
    array (
      'tx_extbase_security_exception_syntacticallywrongrequesthash' => 'tx_extbase_security_exception_syntacticallywrongrequesthash',
    ),
    'TYPO3\\CMS\\Extbase\\Service\\CacheService' => 
    array (
      'tx_extbase_service_cacheservice' => 'tx_extbase_service_cacheservice',
    ),
    'TYPO3\\CMS\\Extbase\\Service\\ExtensionService' => 
    array (
      'tx_extbase_service_extensionservice' => 'tx_extbase_service_extensionservice',
    ),
    'TYPO3\\CMS\\Extbase\\Service\\FlexFormService' => 
    array (
      'tx_extbase_service_flexformservice' => 'tx_extbase_service_flexformservice',
    ),
    'TYPO3\\CMS\\Extbase\\Service\\TypeHandlingService' => 
    array (
      'tx_extbase_service_typehandlingservice' => 'tx_extbase_service_typehandlingservice',
    ),
    'TYPO3\\CMS\\Extbase\\Service\\TypoScriptService' => 
    array (
      'tx_extbase_service_typoscriptservice' => 'tx_extbase_service_typoscriptservice',
    ),
    'TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher' => 
    array (
      'tx_extbase_signalslot_dispatcher' => 'tx_extbase_signalslot_dispatcher',
    ),
    'TYPO3\\CMS\\Extbase\\SignalSlot\\Exception\\InvalidSlotException' => 
    array (
      'tx_extbase_signalslot_exception_invalidslotexception' => 'tx_extbase_signalslot_exception_invalidslotexception',
    ),
    'TYPO3\\CMS\\Core\\Tests\\UnitTestCase' => 
    array (
      'tx_extbase_tests_unit_basetestcase' => 'tx_extbase_tests_unit_basetestcase',
      'typo3\\cms\\extbase\\tests\\unit\\basetestcase' => 'typo3\\cms\\extbase\\tests\\unit\\basetestcase',
    ),
    'TYPO3\\CMS\\Extbase\\Utility\\ArrayUtility' => 
    array (
      'tx_extbase_utility_arrays' => 'tx_extbase_utility_arrays',
    ),
    'TYPO3\\CMS\\Extbase\\Utility\\DebuggerUtility' => 
    array (
      'tx_extbase_utility_debugger' => 'tx_extbase_utility_debugger',
    ),
    'TYPO3\\CMS\\Extbase\\Utility\\ExtbaseRequirementsCheckUtility' => 
    array (
      'tx_extbase_utility_extbaserequirementscheck' => 'tx_extbase_utility_extbaserequirementscheck',
    ),
    'TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility' => 
    array (
      'tx_extbase_utility_extension' => 'tx_extbase_utility_extension',
    ),
    'TYPO3\\CMS\\Extbase\\Utility\\FrontendSimulatorUtility' => 
    array (
      'tx_extbase_utility_frontendsimulator' => 'tx_extbase_utility_frontendsimulator',
    ),
    'TYPO3\\CMS\\Extbase\\Utility\\LocalizationUtility' => 
    array (
      'tx_extbase_utility_localization' => 'tx_extbase_utility_localization',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Error' => 
    array (
      'tx_extbase_validation_error' => 'tx_extbase_validation_error',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Exception' => 
    array (
      'tx_extbase_validation_exception' => 'tx_extbase_validation_exception',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Exception\\InvalidSubjectException' => 
    array (
      'tx_extbase_validation_exception_invalidsubject' => 'tx_extbase_validation_exception_invalidsubject',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Exception\\InvalidValidationConfigurationException' => 
    array (
      'tx_extbase_validation_exception_invalidvalidationconfiguration' => 'tx_extbase_validation_exception_invalidvalidationconfiguration',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Exception\\InvalidValidationOptionsException' => 
    array (
      'tx_extbase_validation_exception_invalidvalidationoptions' => 'tx_extbase_validation_exception_invalidvalidationoptions',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Exception\\NoSuchValidatorException' => 
    array (
      'tx_extbase_validation_exception_nosuchvalidator' => 'tx_extbase_validation_exception_nosuchvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Exception\\NoValidatorFoundException' => 
    array (
      'tx_extbase_validation_exception_novalidatorfound' => 'tx_extbase_validation_exception_novalidatorfound',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\PropertyError' => 
    array (
      'tx_extbase_validation_propertyerror' => 'tx_extbase_validation_propertyerror',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\AbstractCompositeValidator' => 
    array (
      'tx_extbase_validation_validator_abstractcompositevalidator' => 'tx_extbase_validation_validator_abstractcompositevalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\AbstractObjectValidator' => 
    array (
      'tx_extbase_validation_validator_abstractobjectvalidator' => 'tx_extbase_validation_validator_abstractobjectvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\AbstractValidator' => 
    array (
      'tx_extbase_validation_validator_abstractvalidator' => 'tx_extbase_validation_validator_abstractvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\AlphanumericValidator' => 
    array (
      'tx_extbase_validation_validator_alphanumericvalidator' => 'tx_extbase_validation_validator_alphanumericvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator' => 
    array (
      'tx_extbase_validation_validator_conjunctionvalidator' => 'tx_extbase_validation_validator_conjunctionvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\DateTimeValidator' => 
    array (
      'tx_extbase_validation_validator_datetimevalidator' => 'tx_extbase_validation_validator_datetimevalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\DisjunctionValidator' => 
    array (
      'tx_extbase_validation_validator_disjunctionvalidator' => 'tx_extbase_validation_validator_disjunctionvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\EmailAddressValidator' => 
    array (
      'tx_extbase_validation_validator_emailaddressvalidator' => 'tx_extbase_validation_validator_emailaddressvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\FloatValidator' => 
    array (
      'tx_extbase_validation_validator_floatvalidator' => 'tx_extbase_validation_validator_floatvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\GenericObjectValidator' => 
    array (
      'tx_extbase_validation_validator_genericobjectvalidator' => 'tx_extbase_validation_validator_genericobjectvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\IntegerValidator' => 
    array (
      'tx_extbase_validation_validator_integervalidator' => 'tx_extbase_validation_validator_integervalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\NotEmptyValidator' => 
    array (
      'tx_extbase_validation_validator_notemptyvalidator' => 'tx_extbase_validation_validator_notemptyvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator' => 
    array (
      'tx_extbase_validation_validator_numberrangevalidator' => 'tx_extbase_validation_validator_numberrangevalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberValidator' => 
    array (
      'tx_extbase_validation_validator_numbervalidator' => 'tx_extbase_validation_validator_numbervalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\ObjectValidatorInterface' => 
    array (
      'tx_extbase_validation_validator_objectvalidatorinterface' => 'tx_extbase_validation_validator_objectvalidatorinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\RawValidator' => 
    array (
      'tx_extbase_validation_validator_rawvalidator' => 'tx_extbase_validation_validator_rawvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\RegularExpressionValidator' => 
    array (
      'tx_extbase_validation_validator_regularexpressionvalidator' => 'tx_extbase_validation_validator_regularexpressionvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator' => 
    array (
      'tx_extbase_validation_validator_stringlengthvalidator' => 'tx_extbase_validation_validator_stringlengthvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringValidator' => 
    array (
      'tx_extbase_validation_validator_stringvalidator' => 'tx_extbase_validation_validator_stringvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator' => 
    array (
      'tx_extbase_validation_validator_textvalidator' => 'tx_extbase_validation_validator_textvalidator',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface' => 
    array (
      'tx_extbase_validation_validator_validatorinterface' => 'tx_extbase_validation_validator_validatorinterface',
    ),
    'TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver' => 
    array (
      'tx_extbase_validation_validatorresolver' => 'tx_extbase_validation_validatorresolver',
    ),
    'TYPO3\\CMS\\Extensionmanager\\Task\\UpdateExtensionListTask' => 
    array (
      'tx_em_tasks_updateextensionlist' => 'tx_em_tasks_updateextensionlist',
    ),
    'TYPO3\\CMS\\Fluid\\Compatibility\\DocbookGeneratorService' => 
    array (
      'tx_fluid_compatibility_docbookgeneratorservice' => 'tx_fluid_compatibility_docbookgeneratorservice',
    ),
    'TYPO3\\CMS\\Fluid\\Compatibility\\TemplateParserBuilder' => 
    array (
      'tx_fluid_compatibility_templateparserbuilder' => 'tx_fluid_compatibility_templateparserbuilder',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Compiler\\AbstractCompiledTemplate' => 
    array (
      'tx_fluid_core_compiler_abstractcompiledtemplate' => 'tx_fluid_core_compiler_abstractcompiledtemplate',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Compiler\\TemplateCompiler' => 
    array (
      'tx_fluid_core_compiler_templatecompiler' => 'tx_fluid_core_compiler_templatecompiler',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Exception' => 
    array (
      'tx_fluid_core_exception' => 'tx_fluid_core_exception',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\Configuration' => 
    array (
      'tx_fluid_core_parser_configuration' => 'tx_fluid_core_parser_configuration',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\Exception' => 
    array (
      'tx_fluid_core_parser_exception' => 'tx_fluid_core_parser_exception',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\Interceptor\\Escape' => 
    array (
      'tx_fluid_core_parser_interceptor_escape' => 'tx_fluid_core_parser_interceptor_escape',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\InterceptorInterface' => 
    array (
      'tx_fluid_core_parser_interceptorinterface' => 'tx_fluid_core_parser_interceptorinterface',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsedTemplateInterface' => 
    array (
      'tx_fluid_core_parser_parsedtemplateinterface' => 'tx_fluid_core_parser_parsedtemplateinterface',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState' => 
    array (
      'tx_fluid_core_parser_parsingstate' => 'tx_fluid_core_parser_parsingstate',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\AbstractNode' => 
    array (
      'tx_fluid_core_parser_syntaxtree_abstractnode' => 'tx_fluid_core_parser_syntaxtree_abstractnode',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ArrayNode' => 
    array (
      'tx_fluid_core_parser_syntaxtree_arraynode' => 'tx_fluid_core_parser_syntaxtree_arraynode',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\BooleanNode' => 
    array (
      'tx_fluid_core_parser_syntaxtree_booleannode' => 'tx_fluid_core_parser_syntaxtree_booleannode',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\NodeInterface' => 
    array (
      'tx_fluid_core_parser_syntaxtree_nodeinterface' => 'tx_fluid_core_parser_syntaxtree_nodeinterface',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ObjectAccessorNode' => 
    array (
      'tx_fluid_core_parser_syntaxtree_objectaccessornode' => 'tx_fluid_core_parser_syntaxtree_objectaccessornode',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RenderingContextAwareInterface' => 
    array (
      'tx_fluid_core_parser_syntaxtree_renderingcontextawareinterface' => 'tx_fluid_core_parser_syntaxtree_renderingcontextawareinterface',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RootNode' => 
    array (
      'tx_fluid_core_parser_syntaxtree_rootnode' => 'tx_fluid_core_parser_syntaxtree_rootnode',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\TextNode' => 
    array (
      'tx_fluid_core_parser_syntaxtree_textnode' => 'tx_fluid_core_parser_syntaxtree_textnode',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode' => 
    array (
      'tx_fluid_core_parser_syntaxtree_viewhelpernode' => 'tx_fluid_core_parser_syntaxtree_viewhelpernode',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser' => 
    array (
      'tx_fluid_core_parser_templateparser' => 'tx_fluid_core_parser_templateparser',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContext' => 
    array (
      'tx_fluid_core_rendering_renderingcontext' => 'tx_fluid_core_rendering_renderingcontext',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContextInterface' => 
    array (
      'tx_fluid_core_rendering_renderingcontextinterface' => 'tx_fluid_core_rendering_renderingcontextinterface',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractConditionViewHelper' => 
    array (
      'tx_fluid_core_viewhelper_abstractconditionviewhelper' => 'tx_fluid_core_viewhelper_abstractconditionviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractTagBasedViewHelper' => 
    array (
      'tx_fluid_core_viewhelper_abstracttagbasedviewhelper' => 'tx_fluid_core_viewhelper_abstracttagbasedviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractViewHelper' => 
    array (
      'tx_fluid_core_viewhelper_abstractviewhelper' => 'tx_fluid_core_viewhelper_abstractviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\ArgumentDefinition' => 
    array (
      'tx_fluid_core_viewhelper_argumentdefinition' => 'tx_fluid_core_viewhelper_argumentdefinition',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Arguments' => 
    array (
      'tx_fluid_core_viewhelper_arguments' => 'tx_fluid_core_viewhelper_arguments',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Exception' => 
    array (
      'tx_fluid_core_viewhelper_exception' => 'tx_fluid_core_viewhelper_exception',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Exception\\InvalidVariableException' => 
    array (
      'tx_fluid_core_viewhelper_exception_invalidvariableexception' => 'tx_fluid_core_viewhelper_exception_invalidvariableexception',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Exception\\RenderingContextNotAccessibleException' => 
    array (
      'tx_fluid_core_viewhelper_exception_renderingcontextnotaccessibleexception' => 'tx_fluid_core_viewhelper_exception_renderingcontextnotaccessibleexception',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Facets\\ChildNodeAccessInterface' => 
    array (
      'tx_fluid_core_viewhelper_facets_childnodeaccessinterface' => 'tx_fluid_core_viewhelper_facets_childnodeaccessinterface',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Facets\\CompilableInterface' => 
    array (
      'tx_fluid_core_viewhelper_facets_compilableinterface' => 'tx_fluid_core_viewhelper_facets_compilableinterface',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Facets\\PostParseInterface' => 
    array (
      'tx_fluid_core_viewhelper_facets_postparseinterface' => 'tx_fluid_core_viewhelper_facets_postparseinterface',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder' => 
    array (
      'tx_fluid_core_viewhelper_tagbuilder' => 'tx_fluid_core_viewhelper_tagbuilder',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TemplateVariableContainer' => 
    array (
      'tx_fluid_core_viewhelper_templatevariablecontainer' => 'tx_fluid_core_viewhelper_templatevariablecontainer',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\ViewHelperInterface' => 
    array (
      'tx_fluid_core_viewhelper_viewhelperinterface' => 'tx_fluid_core_viewhelper_viewhelperinterface',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\ViewHelperVariableContainer' => 
    array (
      'tx_fluid_core_viewhelper_viewhelpervariablecontainer' => 'tx_fluid_core_viewhelper_viewhelpervariablecontainer',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\AbstractWidgetController' => 
    array (
      'tx_fluid_core_widget_abstractwidgetcontroller' => 'tx_fluid_core_widget_abstractwidgetcontroller',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\AbstractWidgetViewHelper' => 
    array (
      'tx_fluid_core_widget_abstractwidgetviewhelper' => 'tx_fluid_core_widget_abstractwidgetviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\AjaxWidgetContextHolder' => 
    array (
      'tx_fluid_core_widget_ajaxwidgetcontextholder' => 'tx_fluid_core_widget_ajaxwidgetcontextholder',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\Bootstrap' => 
    array (
      'tx_fluid_core_widget_bootstrap' => 'tx_fluid_core_widget_bootstrap',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\Exception' => 
    array (
      'tx_fluid_core_widget_exception' => 'tx_fluid_core_widget_exception',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\Exception\\MissingControllerException' => 
    array (
      'tx_fluid_core_widget_exception_missingcontrollerexception' => 'tx_fluid_core_widget_exception_missingcontrollerexception',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\Exception\\RenderingContextNotFoundException' => 
    array (
      'tx_fluid_core_widget_exception_renderingcontextnotfoundexception' => 'tx_fluid_core_widget_exception_renderingcontextnotfoundexception',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\Exception\\WidgetContextNotFoundException' => 
    array (
      'tx_fluid_core_widget_exception_widgetcontextnotfoundexception' => 'tx_fluid_core_widget_exception_widgetcontextnotfoundexception',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\Exception\\WidgetRequestNotFoundException' => 
    array (
      'tx_fluid_core_widget_exception_widgetrequestnotfoundexception' => 'tx_fluid_core_widget_exception_widgetrequestnotfoundexception',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetContext' => 
    array (
      'tx_fluid_core_widget_widgetcontext' => 'tx_fluid_core_widget_widgetcontext',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequest' => 
    array (
      'tx_fluid_core_widget_widgetrequest' => 'tx_fluid_core_widget_widgetrequest',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequestBuilder' => 
    array (
      'tx_fluid_core_widget_widgetrequestbuilder' => 'tx_fluid_core_widget_widgetrequestbuilder',
    ),
    'TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequestHandler' => 
    array (
      'tx_fluid_core_widget_widgetrequesthandler' => 'tx_fluid_core_widget_widgetrequesthandler',
    ),
    'TYPO3\\CMS\\Fluid\\Exception' => 
    array (
      'tx_fluid_exception' => 'tx_fluid_exception',
    ),
    'TYPO3\\CMS\\Fluid\\Fluid' => 
    array (
      'tx_fluid_fluid' => 'tx_fluid_fluid',
    ),
    'TYPO3\\CMS\\Fluid\\Service\\DocbookGenerator' => 
    array (
      'tx_fluid_service_docbookgenerator' => 'tx_fluid_service_docbookgenerator',
    ),
    'TYPO3\\CMS\\Fluid\\View\\AbstractTemplateView' => 
    array (
      'tx_fluid_view_abstracttemplateview' => 'tx_fluid_view_abstracttemplateview',
    ),
    'TYPO3\\CMS\\Fluid\\View\\Exception' => 
    array (
      'tx_fluid_view_exception' => 'tx_fluid_view_exception',
    ),
    'TYPO3\\CMS\\Fluid\\View\\Exception\\InvalidSectionException' => 
    array (
      'tx_fluid_view_exception_invalidsectionexception' => 'tx_fluid_view_exception_invalidsectionexception',
    ),
    'TYPO3\\CMS\\Fluid\\View\\Exception\\InvalidTemplateResourceException' => 
    array (
      'tx_fluid_view_exception_invalidtemplateresourceexception' => 'tx_fluid_view_exception_invalidtemplateresourceexception',
    ),
    'TYPO3\\CMS\\Fluid\\View\\StandaloneView' => 
    array (
      'tx_fluid_view_standaloneview' => 'tx_fluid_view_standaloneview',
    ),
    'TYPO3\\CMS\\Fluid\\View\\TemplateView' => 
    array (
      'tx_fluid_view_templateview' => 'tx_fluid_view_templateview',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\AliasViewHelper' => 
    array (
      'tx_fluid_viewhelpers_aliasviewhelper' => 'tx_fluid_viewhelpers_aliasviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\BaseViewHelper' => 
    array (
      'tx_fluid_viewhelpers_baseviewhelper' => 'tx_fluid_viewhelpers_baseviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\AbstractBackendViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_abstractbackendviewhelper' => 'tx_fluid_viewhelpers_be_abstractbackendviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Buttons\\CshViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_buttons_cshviewhelper' => 'tx_fluid_viewhelpers_be_buttons_cshviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Buttons\\IconViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_buttons_iconviewhelper' => 'tx_fluid_viewhelpers_be_buttons_iconviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Buttons\\ShortcutViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_buttons_shortcutviewhelper' => 'tx_fluid_viewhelpers_be_buttons_shortcutviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\ContainerViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_containerviewhelper' => 'tx_fluid_viewhelpers_be_containerviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Menus\\ActionMenuItemViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_menus_actionmenuitemviewhelper' => 'tx_fluid_viewhelpers_be_menus_actionmenuitemviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Menus\\ActionMenuViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_menus_actionmenuviewhelper' => 'tx_fluid_viewhelpers_be_menus_actionmenuviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\PageInfoViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_pageinfoviewhelper' => 'tx_fluid_viewhelpers_be_pageinfoviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\PagePathViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_pagepathviewhelper' => 'tx_fluid_viewhelpers_be_pagepathviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Security\\IfAuthenticatedViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_security_ifauthenticatedviewhelper' => 'tx_fluid_viewhelpers_be_security_ifauthenticatedviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Security\\IfHasRoleViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_security_ifhasroleviewhelper' => 'tx_fluid_viewhelpers_be_security_ifhasroleviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\TableListViewHelper' => 
    array (
      'tx_fluid_viewhelpers_be_tablelistviewhelper' => 'tx_fluid_viewhelpers_be_tablelistviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\CObjectViewHelper' => 
    array (
      'tx_fluid_viewhelpers_cobjectviewhelper' => 'tx_fluid_viewhelpers_cobjectviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\CommentViewHelper' => 
    array (
      'tx_fluid_viewhelpers_commentviewhelper' => 'tx_fluid_viewhelpers_commentviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\CountViewHelper' => 
    array (
      'tx_fluid_viewhelpers_countviewhelper' => 'tx_fluid_viewhelpers_countviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\CycleViewHelper' => 
    array (
      'tx_fluid_viewhelpers_cycleviewhelper' => 'tx_fluid_viewhelpers_cycleviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\DebugViewHelper' => 
    array (
      'tx_fluid_viewhelpers_debugviewhelper' => 'tx_fluid_viewhelpers_debugviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\ElseViewHelper' => 
    array (
      'tx_fluid_viewhelpers_elseviewhelper' => 'tx_fluid_viewhelpers_elseviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\FlashMessagesViewHelper' => 
    array (
      'tx_fluid_viewhelpers_flashmessagesviewhelper' => 'tx_fluid_viewhelpers_flashmessagesviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\AbstractFormFieldViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_abstractformfieldviewhelper' => 'tx_fluid_viewhelpers_form_abstractformfieldviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\AbstractFormViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_abstractformviewhelper' => 'tx_fluid_viewhelpers_form_abstractformviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\CheckboxViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_checkboxviewhelper' => 'tx_fluid_viewhelpers_form_checkboxviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\ErrorsViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_errorsviewhelper' => 'tx_fluid_viewhelpers_form_errorsviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\HiddenViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_hiddenviewhelper' => 'tx_fluid_viewhelpers_form_hiddenviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\PasswordViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_passwordviewhelper' => 'tx_fluid_viewhelpers_form_passwordviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\RadioViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_radioviewhelper' => 'tx_fluid_viewhelpers_form_radioviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\SelectViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_selectviewhelper' => 'tx_fluid_viewhelpers_form_selectviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\SubmitViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_submitviewhelper' => 'tx_fluid_viewhelpers_form_submitviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\TextareaViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_textareaviewhelper' => 'tx_fluid_viewhelpers_form_textareaviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\TextfieldViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_textfieldviewhelper' => 'tx_fluid_viewhelpers_form_textfieldviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\UploadViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_uploadviewhelper' => 'tx_fluid_viewhelpers_form_uploadviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\ValidationResultsViewHelper' => 
    array (
      'tx_fluid_viewhelpers_form_validationresultsviewhelper' => 'tx_fluid_viewhelpers_form_validationresultsviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\AbstractEncodingViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_abstractencodingviewhelper' => 'tx_fluid_viewhelpers_format_abstractencodingviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CdataViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_cdataviewhelper' => 'tx_fluid_viewhelpers_format_cdataviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CropViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_cropviewhelper' => 'tx_fluid_viewhelpers_format_cropviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\CurrencyViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_currencyviewhelper' => 'tx_fluid_viewhelpers_format_currencyviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\DateViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_dateviewhelper' => 'tx_fluid_viewhelpers_format_dateviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlentitiesDecodeViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_htmlentitiesdecodeviewhelper' => 'tx_fluid_viewhelpers_format_htmlentitiesdecodeviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlentitiesViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_htmlentitiesviewhelper' => 'tx_fluid_viewhelpers_format_htmlentitiesviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlspecialcharsViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_htmlspecialcharsviewhelper' => 'tx_fluid_viewhelpers_format_htmlspecialcharsviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_htmlviewhelper' => 'tx_fluid_viewhelpers_format_htmlviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\Nl2brViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_nl2brviewhelper' => 'tx_fluid_viewhelpers_format_nl2brviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\NumberViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_numberviewhelper' => 'tx_fluid_viewhelpers_format_numberviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\PaddingViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_paddingviewhelper' => 'tx_fluid_viewhelpers_format_paddingviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\PrintfViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_printfviewhelper' => 'tx_fluid_viewhelpers_format_printfviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\RawViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_rawviewhelper' => 'tx_fluid_viewhelpers_format_rawviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\StripTagsViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_striptagsviewhelper' => 'tx_fluid_viewhelpers_format_striptagsviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\UrlencodeViewHelper' => 
    array (
      'tx_fluid_viewhelpers_format_urlencodeviewhelper' => 'tx_fluid_viewhelpers_format_urlencodeviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper' => 
    array (
      'tx_fluid_viewhelpers_formviewhelper' => 'tx_fluid_viewhelpers_formviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\ForViewHelper' => 
    array (
      'tx_fluid_viewhelpers_forviewhelper' => 'tx_fluid_viewhelpers_forviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\GroupedForViewHelper' => 
    array (
      'tx_fluid_viewhelpers_groupedforviewhelper' => 'tx_fluid_viewhelpers_groupedforviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\IfViewHelper' => 
    array (
      'tx_fluid_viewhelpers_ifviewhelper' => 'tx_fluid_viewhelpers_ifviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\ImageViewHelper' => 
    array (
      'tx_fluid_viewhelpers_imageviewhelper' => 'tx_fluid_viewhelpers_imageviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\LayoutViewHelper' => 
    array (
      'tx_fluid_viewhelpers_layoutviewhelper' => 'tx_fluid_viewhelpers_layoutviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Link\\ActionViewHelper' => 
    array (
      'tx_fluid_viewhelpers_link_actionviewhelper' => 'tx_fluid_viewhelpers_link_actionviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Link\\EmailViewHelper' => 
    array (
      'tx_fluid_viewhelpers_link_emailviewhelper' => 'tx_fluid_viewhelpers_link_emailviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Link\\ExternalViewHelper' => 
    array (
      'tx_fluid_viewhelpers_link_externalviewhelper' => 'tx_fluid_viewhelpers_link_externalviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Link\\PageViewHelper' => 
    array (
      'tx_fluid_viewhelpers_link_pageviewhelper' => 'tx_fluid_viewhelpers_link_pageviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\RenderChildrenViewHelper' => 
    array (
      'tx_fluid_viewhelpers_renderchildrenviewhelper' => 'tx_fluid_viewhelpers_renderchildrenviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\RenderViewHelper' => 
    array (
      'tx_fluid_viewhelpers_renderviewhelper' => 'tx_fluid_viewhelpers_renderviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\SectionViewHelper' => 
    array (
      'tx_fluid_viewhelpers_sectionviewhelper' => 'tx_fluid_viewhelpers_sectionviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Security\\IfAuthenticatedViewHelper' => 
    array (
      'tx_fluid_viewhelpers_security_ifauthenticatedviewhelper' => 'tx_fluid_viewhelpers_security_ifauthenticatedviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Security\\IfHasRoleViewHelper' => 
    array (
      'tx_fluid_viewhelpers_security_ifhasroleviewhelper' => 'tx_fluid_viewhelpers_security_ifhasroleviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\ThenViewHelper' => 
    array (
      'tx_fluid_viewhelpers_thenviewhelper' => 'tx_fluid_viewhelpers_thenviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\TranslateViewHelper' => 
    array (
      'tx_fluid_viewhelpers_translateviewhelper' => 'tx_fluid_viewhelpers_translateviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\ActionViewHelper' => 
    array (
      'tx_fluid_viewhelpers_uri_actionviewhelper' => 'tx_fluid_viewhelpers_uri_actionviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\EmailViewHelper' => 
    array (
      'tx_fluid_viewhelpers_uri_emailviewhelper' => 'tx_fluid_viewhelpers_uri_emailviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\ExternalViewHelper' => 
    array (
      'tx_fluid_viewhelpers_uri_externalviewhelper' => 'tx_fluid_viewhelpers_uri_externalviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\ImageViewHelper' => 
    array (
      'tx_fluid_viewhelpers_uri_imageviewhelper' => 'tx_fluid_viewhelpers_uri_imageviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\PageViewHelper' => 
    array (
      'tx_fluid_viewhelpers_uri_pageviewhelper' => 'tx_fluid_viewhelpers_uri_pageviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\ResourceViewHelper' => 
    array (
      'tx_fluid_viewhelpers_uri_resourceviewhelper' => 'tx_fluid_viewhelpers_uri_resourceviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\AutocompleteViewHelper' => 
    array (
      'tx_fluid_viewhelpers_widget_autocompleteviewhelper' => 'tx_fluid_viewhelpers_widget_autocompleteviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\Controller\\AutocompleteController' => 
    array (
      'tx_fluid_viewhelpers_widget_controller_autocompletecontroller' => 'tx_fluid_viewhelpers_widget_controller_autocompletecontroller',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\Controller\\PaginateController' => 
    array (
      'tx_fluid_viewhelpers_widget_controller_paginatecontroller' => 'tx_fluid_viewhelpers_widget_controller_paginatecontroller',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\LinkViewHelper' => 
    array (
      'tx_fluid_viewhelpers_widget_linkviewhelper' => 'tx_fluid_viewhelpers_widget_linkviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\PaginateViewHelper' => 
    array (
      'tx_fluid_viewhelpers_widget_paginateviewhelper' => 'tx_fluid_viewhelpers_widget_paginateviewhelper',
    ),
    'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\UriViewHelper' => 
    array (
      'tx_fluid_viewhelpers_widget_uriviewhelper' => 'tx_fluid_viewhelpers_widget_uriviewhelper',
    ),
    'TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication' => 
    array (
      'tslib_feuserauth' => 'tslib_feuserauth',
    ),
    'TYPO3\\CMS\\Frontend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher' => 
    array (
      't3lib_matchcondition_frontend' => 't3lib_matchcondition_frontend',
    ),
    'TYPO3\\CMS\\Frontend\\Controller\\DataSubmissionController' => 
    array (
      't3lib_formmail' => 't3lib_formmail',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\AbstractContentObject' => 
    array (
      'tslib_content_abstract' => 'tslib_content_abstract',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\CaseContentObject' => 
    array (
      'tslib_content_case' => 'tslib_content_case',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ClearGifContentObject' => 
    array (
      'tslib_content_cleargif' => 'tslib_content_cleargif',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ColumnsContentObject' => 
    array (
      'tslib_content_columns' => 'tslib_content_columns',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentContentObject' => 
    array (
      'tslib_content_content' => 'tslib_content_content',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectArrayContentObject' => 
    array (
      'tslib_content_contentobjectarray' => 'tslib_content_contentobjectarray',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectArrayInternalContentObject' => 
    array (
      'tslib_content_contentobjectarrayinternal' => 'tslib_content_contentobjectarrayinternal',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectGetDataHookInterface' => 
    array (
      'tslib_content_getdatahook' => 'tslib_content_getdatahook',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectGetImageResourceHookInterface' => 
    array (
      'tslib_cobj_getimgresourcehook' => 'tslib_cobj_getimgresourcehook',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectGetPublicUrlForFileHookInterface' => 
    array (
      'tslib_content_getpublicurlforfilehook' => 'tslib_content_getpublicurlforfilehook',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectGetSingleHookInterface' => 
    array (
      'tslib_content_cobjgetsinglehook' => 'tslib_content_cobjgetsinglehook',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectPostInitHookInterface' => 
    array (
      'tslib_content_postinithook' => 'tslib_content_postinithook',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer' => 
    array (
      'tslib_cobj' => 'tslib_cobj',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectStdWrapHookInterface' => 
    array (
      'tslib_content_stdwraphook' => 'tslib_content_stdwraphook',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ContentTableContentObject' => 
    array (
      'tslib_content_contenttable' => 'tslib_content_contenttable',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\EditPanelContentObject' => 
    array (
      'tslib_content_editpanel' => 'tslib_content_editpanel',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\FileContentObject' => 
    array (
      'tslib_content_file' => 'tslib_content_file',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\FileLinkHookInterface' => 
    array (
      'tslib_content_filelinkhook' => 'tslib_content_filelinkhook',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\FilesContentObject' => 
    array (
      'tslib_content_files' => 'tslib_content_files',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\FlowPlayerContentObject' => 
    array (
      'tslib_content_flowplayer' => 'tslib_content_flowplayer',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\FluidTemplateContentObject' => 
    array (
      'tslib_content_fluidtemplate' => 'tslib_content_fluidtemplate',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\FormContentObject' => 
    array (
      'tslib_content_form' => 'tslib_content_form',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\HierarchicalMenuContentObject' => 
    array (
      'tslib_content_hierarchicalmenu' => 'tslib_content_hierarchicalmenu',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\HorizontalRulerContentObject' => 
    array (
      'tslib_content_horizontalruler' => 'tslib_content_horizontalruler',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ImageContentObject' => 
    array (
      'tslib_content_image' => 'tslib_content_image',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ImageResourceContentObject' => 
    array (
      'tslib_content_imageresource' => 'tslib_content_imageresource',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ImageTextContentObject' => 
    array (
      'tslib_content_imagetext' => 'tslib_content_imagetext',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\LoadRegisterContentObject' => 
    array (
      'tslib_content_loadregister' => 'tslib_content_loadregister',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\MediaContentObject' => 
    array (
      'tslib_content_media' => 'tslib_content_media',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\AbstractMenuContentObject' => 
    array (
      'tslib_menu' => 'tslib_menu',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\AbstractMenuFilterPagesHookInterface' => 
    array (
      'tslib_menu_filtermenupageshook' => 'tslib_menu_filtermenupageshook',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\GraphicalMenuContentObject' => 
    array (
      'tslib_gmenu' => 'tslib_gmenu',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\ImageMenuContentObject' => 
    array (
      'tslib_imgmenu' => 'tslib_imgmenu',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\JavaScriptMenuContentObject' => 
    array (
      'tslib_jsmenu' => 'tslib_jsmenu',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\Menu\\TextMenuContentObject' => 
    array (
      'tslib_tmenu' => 'tslib_tmenu',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\MultimediaContentObject' => 
    array (
      'tslib_content_multimedia' => 'tslib_content_multimedia',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\OffsetTableContentObject' => 
    array (
      'tslib_tableoffset' => 'tslib_tableoffset',
      'tslib_content_offsettable' => 'tslib_content_offsettable',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\QuicktimeObjectContentObject' => 
    array (
      'tslib_content_quicktimeobject' => 'tslib_content_quicktimeobject',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\RecordsContentObject' => 
    array (
      'tslib_content_records' => 'tslib_content_records',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\RestoreRegisterContentObject' => 
    array (
      'tslib_content_restoreregister' => 'tslib_content_restoreregister',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ScalableVectorGraphicsContentObject' => 
    array (
      'tslib_content_scalablevectorgraphics' => 'tslib_content_scalablevectorgraphics',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\SearchResultContentObject' => 
    array (
      'tslib_search' => 'tslib_search',
      'tslib_content_searchresult' => 'tslib_content_searchresult',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\ShockwaveFlashObjectContentObject' => 
    array (
      'tslib_content_shockwaveflashobject' => 'tslib_content_shockwaveflashobject',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\TableRenderer' => 
    array (
      'tslib_controltable' => 'tslib_controltable',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\TemplateContentObject' => 
    array (
      'tslib_content_template' => 'tslib_content_template',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\TextContentObject' => 
    array (
      'tslib_content_text' => 'tslib_content_text',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\UserContentObject' => 
    array (
      'tslib_content_user' => 'tslib_content_user',
    ),
    'TYPO3\\CMS\\Frontend\\ContentObject\\UserInternalContentObject' => 
    array (
      'tslib_content_userinternal' => 'tslib_content_userinternal',
    ),
    'TYPO3\\CMS\\Frontend\\Controller\\ExtDirectEidController' => 
    array (
      'tslib_extdirecteid' => 'tslib_extdirecteid',
    ),
    'TYPO3\\CMS\\Frontend\\Controller\\PageInformationController' => 
    array (
      'tx_cms_webinfo_page' => 'tx_cms_webinfo_page',
    ),
    'TYPO3\\CMS\\Frontend\\Controller\\ShowImageController' => 
    array (
      'sc_tslib_showpic' => 'sc_tslib_showpic',
    ),
    'TYPO3\\CMS\\Frontend\\Controller\\TranslationStatusController' => 
    array (
      'tx_cms_webinfo_lang' => 'tx_cms_webinfo_lang',
    ),
    'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController' => 
    array (
      'tslib_fe' => 'tslib_fe',
    ),
    'TYPO3\\CMS\\Frontend\\Hooks\\FrontendHooks' => 
    array (
      'tx_cms_fehooks' => 'tx_cms_fehooks',
    ),
    'TYPO3\\CMS\\Frontend\\Hooks\\MediaItemHooks' => 
    array (
      'tx_cms_mediaitems' => 'tx_cms_mediaitems',
    ),
    'TYPO3\\CMS\\Frontend\\Hooks\\TreelistCacheUpdateHooks' => 
    array (
      'tx_cms_treelistcacheupdate' => 'tx_cms_treelistcacheupdate',
    ),
    'TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder' => 
    array (
      'tslib_gifbuilder' => 'tslib_gifbuilder',
    ),
    'TYPO3\\CMS\\Frontend\\MediaWizard\\MediaWizardProvider' => 
    array (
      'tslib_mediawizardcoreprovider' => 'tslib_mediawizardcoreprovider',
    ),
    'TYPO3\\CMS\\Frontend\\MediaWizard\\MediaWizardProviderInterface' => 
    array (
      'tslib_mediawizardprovider' => 'tslib_mediawizardprovider',
    ),
    'TYPO3\\CMS\\Frontend\\MediaWizard\\MediaWizardProviderManager' => 
    array (
      'tslib_mediawizardmanager' => 'tslib_mediawizardmanager',
    ),
    'TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator' => 
    array (
      't3lib_cachehash' => 't3lib_cachehash',
    ),
    'TYPO3\\CMS\\Frontend\\Page\\FramesetRenderer' => 
    array (
      'tslib_frameset' => 'tslib_frameset',
    ),
    'TYPO3\\CMS\\Frontend\\Page\\PageGenerator' => 
    array (
      'tspagegen' => 'tspagegen',
    ),
    'TYPO3\\CMS\\Frontend\\Page\\PageRepository' => 
    array (
      't3lib_pageselect' => 't3lib_pageselect',
    ),
    'TYPO3\\CMS\\Frontend\\Page\\PageRepositoryGetPageHookInterface' => 
    array (
      't3lib_pageselect_getpagehook' => 't3lib_pageselect_getpagehook',
    ),
    'TYPO3\\CMS\\Frontend\\Page\\PageRepositoryGetPageOverlayHookInterface' => 
    array (
      't3lib_pageselect_getpageoverlayhook' => 't3lib_pageselect_getpageoverlayhook',
    ),
    'TYPO3\\CMS\\Frontend\\Page\\PageRepositoryGetRecordOverlayHookInterface' => 
    array (
      't3lib_pageselect_getrecordoverlayhook' => 't3lib_pageselect_getrecordoverlayhook',
    ),
    'TYPO3\\CMS\\Frontend\\Plugin\\AbstractPlugin' => 
    array (
      'tslib_pibase' => 'tslib_pibase',
    ),
    'TYPO3\\CMS\\Frontend\\Utility\\CompressionUtility' => 
    array (
      'tslib_fecompression' => 'tslib_fecompression',
    ),
    'TYPO3\\CMS\\Frontend\\Utility\\EidUtility' => 
    array (
      'tslib_eidtools' => 'tslib_eidtools',
    ),
    'TYPO3\\CMS\\Frontend\\View\\AdminPanelView' => 
    array (
      'tslib_adminpanel' => 'tslib_adminpanel',
    ),
    'TYPO3\\CMS\\Frontend\\View\\AdminPanelViewHookInterface' => 
    array (
      'tslib_adminpanelhook' => 'tslib_adminpanelhook',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\AddFlexFormsToAclUpdate' => 
    array (
      'tx_coreupdates_addflexformstoacl' => 'tx_coreupdates_addflexformstoacl',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\CharsetDefaultsUpdate' => 
    array (
      'tx_coreupdates_charsetdefaults' => 'tx_coreupdates_charsetdefaults',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\CompatVersionUpdate' => 
    array (
      'tx_coreupdates_compatversion' => 'tx_coreupdates_compatversion',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\CompressionLevelUpdate' => 
    array (
      'tx_coreupdates_compressionlevel' => 'tx_coreupdates_compressionlevel',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\CscSplitUpdate' => 
    array (
      'tx_coreupdates_cscsplit' => 'tx_coreupdates_cscsplit',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\FlagsFromSpriteUpdate' => 
    array (
      'tx_coreupdates_flagsfromsprite' => 'tx_coreupdates_flagsfromsprite',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\ImagecolsUpdate' => 
    array (
      'tx_coreupdates_imagecols' => 'tx_coreupdates_imagecols',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\ImagelinkUpdate' => 
    array (
      'tx_coreupdates_imagelink' => 'tx_coreupdates_imagelink',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\InstallSysExtsUpdate' => 
    array (
      'tx_coreupdates_installsysexts' => 'tx_coreupdates_installsysexts',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\MediaFlexformUpdate' => 
    array (
      'tx_coreupdates_mediaflexform' => 'tx_coreupdates_mediaflexform',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\MergeAdvancedUpdate' => 
    array (
      'tx_coreupdates_mergeadvanced' => 'tx_coreupdates_mergeadvanced',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\MigrateWorkspacesUpdate' => 
    array (
      'tx_coreupdates_migrateworkspaces' => 'tx_coreupdates_migrateworkspaces',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\NotInMenuUpdate' => 
    array (
      'tx_coreupdates_notinmenu' => 'tx_coreupdates_notinmenu',
    ),
    'TYPO3\\CMS\\Install\\CoreUpdates\\T3skinUpdate' => 
    array (
      'tx_coreupdates_t3skin' => 'tx_coreupdates_t3skin',
    ),
    'TYPO3\\CMS\\Install\\Service\\EnableFileService' => 
    array (
      'tx_install_service_basicservice' => 'tx_install_service_basicservice',
      'typo3\\cms\\install\\enablefileservice' => 'typo3\\cms\\install\\enablefileservice',
    ),
    'TYPO3\\CMS\\Install\\Report\\InstallStatusReport' => 
    array (
      'tx_install_report_installstatus' => 'tx_install_report_installstatus',
    ),
    'TYPO3\\CMS\\Install\\Service\\SessionService' => 
    array (
      'tx_install_session' => 'tx_install_session',
      'typo3\\cms\\install\\session' => 'typo3\\cms\\install\\session',
    ),
    'TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService' => 
    array (
      't3lib_install_sql' => 't3lib_install_sql',
      'typo3\\cms\\install\\sql\\schemamigrator' => 'typo3\\cms\\install\\sql\\schemamigrator',
    ),
    'TYPO3\\CMS\\Install\\Updates\\AbstractUpdate' => 
    array (
      'tx_install_updates_base' => 'tx_install_updates_base',
    ),
    'TYPO3\\CMS\\Install\\Updates\\FilemountUpdateWizard' => 
    array (
      'tx_install_updates_file_filemountupdatewizard' => 'tx_install_updates_file_filemountupdatewizard',
    ),
    'TYPO3\\CMS\\Install\\Updates\\InitUpdateWizard' => 
    array (
      'tx_install_updates_file_initupdatewizard' => 'tx_install_updates_file_initupdatewizard',
    ),
    'TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard' => 
    array (
      'tx_install_updates_file_tceformsupdatewizard' => 'tx_install_updates_file_tceformsupdatewizard',
    ),
    'TYPO3\\CMS\\Install\\Updates\\TtContentUploadsUpdateWizard' => 
    array (
      'tx_install_updates_file_ttcontentuploadsupdatewizard' => 'tx_install_updates_file_ttcontentuploadsupdatewizard',
    ),
    'TYPO3\\CMS\\Lang\\LanguageService' => 
    array (
      'language' => 'language',
    ),
    'TYPO3\\CMS\\Recordlist\\Browser\\ElementBrowser' => 
    array (
      'browse_links' => 'browse_links',
    ),
    'TYPO3\\CMS\\Recordlist\\Controller\\ElementBrowserController' => 
    array (
      'sc_browse_links' => 'sc_browse_links',
    ),
    'TYPO3\\CMS\\Recordlist\\Controller\\ElementBrowserFramesetController' => 
    array (
      'sc_browser' => 'sc_browser',
    ),
    'TYPO3\\CMS\\Recordlist\\RecordList' => 
    array (
      'sc_db_list' => 'sc_db_list',
    ),
    'TYPO3\\CMS\\Recordlist\\RecordList\\AbstractDatabaseRecordList' => 
    array (
      'recordlist' => 'recordlist',
    ),
    'TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList' => 
    array (
      'localrecordlist' => 'localrecordlist',
    ),
    'TYPO3\\CMS\\Recordlist\\RecordList\\RecordListHookInterface' => 
    array (
      'localrecordlist_actionshook' => 'localrecordlist_actionshook',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Evaluation\\BackendEvaluator' => 
    array (
      'tx_saltedpasswords_eval_be' => 'tx_saltedpasswords_eval_be',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Evaluation\\Evaluator' => 
    array (
      'tx_saltedpasswords_eval' => 'tx_saltedpasswords_eval',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Evaluation\\FrontendEvaluator' => 
    array (
      'tx_saltedpasswords_eval_fe' => 'tx_saltedpasswords_eval_fe',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Salt\\AbstractSalt' => 
    array (
      'tx_saltedpasswords_abstract_salts' => 'tx_saltedpasswords_abstract_salts',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Salt\\BlowfishSalt' => 
    array (
      'tx_saltedpasswords_salts_blowfish' => 'tx_saltedpasswords_salts_blowfish',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt' => 
    array (
      'tx_saltedpasswords_salts_md5' => 'tx_saltedpasswords_salts_md5',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt' => 
    array (
      'tx_saltedpasswords_salts_phpass' => 'tx_saltedpasswords_salts_phpass',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Salt\\SaltFactory' => 
    array (
      'tx_saltedpasswords_salts_factory' => 'tx_saltedpasswords_salts_factory',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Salt\\SaltInterface' => 
    array (
      'tx_saltedpasswords_salts' => 'tx_saltedpasswords_salts',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\SaltedPasswordService' => 
    array (
      'tx_saltedpasswords_sv1' => 'tx_saltedpasswords_sv1',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Task\\BulkUpdateFieldProvider' => 
    array (
      'tx_saltedpasswords_tasks_bulkupdate_additionalfieldprovider' => 'tx_saltedpasswords_tasks_bulkupdate_additionalfieldprovider',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Task\\BulkUpdateTask' => 
    array (
      'tx_saltedpasswords_tasks_bulkupdate' => 'tx_saltedpasswords_tasks_bulkupdate',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility' => 
    array (
      'tx_saltedpasswords_emconfhelper' => 'tx_saltedpasswords_emconfhelper',
    ),
    'TYPO3\\CMS\\Saltedpasswords\\Utility\\SaltedPasswordsUtility' => 
    array (
      'tx_saltedpasswords_div' => 'tx_saltedpasswords_div',
    ),
    'TYPO3\\CMS\\Sv\\AbstractAuthenticationService' => 
    array (
      'tx_sv_authbase' => 'tx_sv_authbase',
    ),
    'TYPO3\\CMS\\Sv\\AuthenticationService' => 
    array (
      'tx_sv_auth' => 'tx_sv_auth',
    ),
    'TYPO3\\CMS\\Sv\\LoginFormHook' => 
    array (
      'tx_sv_loginformhook' => 'tx_sv_loginformhook',
    ),
    'TYPO3\\CMS\\Sv\\Report\\ServicesListReport' => 
    array (
      'tx_sv_reports_serviceslist' => 'tx_sv_reports_serviceslist',
    ),
  ),
);