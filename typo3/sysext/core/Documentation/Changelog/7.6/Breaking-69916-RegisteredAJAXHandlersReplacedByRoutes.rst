
.. include:: /Includes.rst.txt

==============================================================
Breaking: #69916 - Registered AJAX handlers replaced by routes
==============================================================

See :issue:`69916`

Description
===========

AJAX handlers registered in the core by `ExtensionManagementUtility::registerAjaxHandler()` have been replaced
by AJAX routes, which are registered inside any extension under Configuration/Backend/AjaxRoutes.php.

The routes registered in AjaxRoutes.php are available via JavaScript via `TYPO3.settings.ajaxUrls[routeIdentifier]`.

Impact
======

Calling removed AJAX identifiers will result in an error. Please see the table below for migration.


Affected Installations
======================

All 3rd party extensions using one of the removed handlers is affected.


Migration
=========

Please see the table to get the new AJAX identifier.

EXT:backend
^^^^^^^^^^^

==========================================================   =================================   =======================================
Old identifier                                               New identifier                      New AJAX ID
==========================================================   =================================   =======================================
SC_alt_db_navframe::expandCollapse                           sc_alt_db_navframe_expandtoggle     /ajax/sc-alt-db-navframe/expandtoggle
SC_alt_file_navframe::expandCollapse                         sc_alt_file_navframe_expandtoggle   /ajax/sc-alt-file-navframe/expandtoggle
TYPO3_tcefile::process                                       file_process                        /ajax/file/process
TYPO3_tcefile::fileExists                                    file_exists                         /ajax/file/exists
t3lib_TCEforms_inline::createNewRecord                       record_inline_create                /ajax/inline/create
t3lib_TCEforms_inline::getRecordDetails                      record_inline_details               /ajax/inline/record-details
t3lib_TCEforms_inline::synchronizeLocalizeRecords            record_inline_synchronizelocalize   /ajax/inline/synchronizelocalize
t3lib_TCEforms_inline::setExpandedCollapsedState             record_inline_expandcollapse        /ajax/inline/expandcollapse
t3lib_TCEforms_suggest::searchRecord                         record_suggest                      /ajax/wizard/suggest/search
ShortcutMenu::getShortcutEditForm                            shortcut_editform                   /ajax/shortcut/editform
ShortcutMenu::saveShortcut                                   shortcut_saveform                   /ajax/shortcut/saveform
ShortcutMenu::render                                         shortcut_list                       /ajax/shortcut/list
ShortcutMenu::delete                                         shortcut_remove                     /ajax/shortcut/remove
ShortcutMenu::create                                         shortcut_create                     /ajax/shortcut/create
SystemInformationMenu::load                                  systeminformation_render            /ajax/system-information/render
ModuleMenu::reload                                           modulemenu                          /ajax/module-menu
BackendLogin::login                                          login                               /ajax/login
BackendLogin::logout                                         logout                              /ajax/logout
BackendLogin::refreshLogin                                   login_refresh                       /ajax/login/refresh
BackendLogin::isTimedOut                                     login_timedout                      /ajax/login/timedout
ExtDirect::getAPI                                            ext_direct_api                      /ajax/ext-direct/api
ExtDirect::route                                             ext_direct_route                    /ajax/ext-direct/route
DocumentTemplate::getFlashMessages                           flashmessages_render                /ajax/flashmessages/render
ContextMenu::load                                            contextmenu                         /ajax/context-menu
DataHandler::process                                         record_process                      /ajax/record/process
UserSettings::process                                        usersettings_process                /ajax/user-settings/process
ImageManipulationWizard::getHtmlForImageManipulationWizard   wizard_image_manipulation           /ajax/wizard/image-manipulation
LiveSearch                                                   livesearch                          /ajax/livesearch
OnlineMedia::add                                             online_media_create                 /ajax/online-media/create
==========================================================   =================================   =======================================

EXT:beuser
^^^^^^^^^^

==================================   =======================   =========================
Old identifier                       New identifier            New AJAX ID
==================================   =======================   =========================
PermissionAjaxController::dispatch   user_access_permissions   /users/access/permissions
==================================   =======================   =========================

EXT:context_help
^^^^^^^^^^^^^^^^

===================================   =====================   ======================
Old identifier                        New identifier          New AJAX ID
===================================   =====================   ======================
ContextHelpAjaxController::dispatch   context_help            /context-help
===================================   =====================   ======================

EXT:opendocs
^^^^^^^^^^^^

===================================   =====================   ======================
Old identifier                        New identifier          New AJAX ID
===================================   =====================   ======================
TxOpendocs::renderMenu                opendocs_menu           /opendocs/menu
TxOpendocs::closeDocument             opendocs_close          /opendocs/close
===================================   =====================   ======================

EXT:recycler
^^^^^^^^^^^^

===================================   =====================   ======================
Old identifier                        New identifier          New AJAX ID
===================================   =====================   ======================
RecyclerAjaxController::dispatch      recycler                /recycler
===================================   =====================   ======================

EXT:rsaauth
^^^^^^^^^^^

===================================   =====================   ======================
Old identifier                        New identifier          New AJAX ID
===================================   =====================   ======================
BackendLogin::getRsaPublicKey         rsa_publickey           /rsa/publickey
RsaEncryption::getRsaPublicKey        rsa_publickey           /rsa/publickey
===================================   =====================   ======================

EXT:rtehtmlarea
^^^^^^^^^^^^^^^

===================================   ========================   ======================
Old identifier                        New identifier             New AJAX ID
===================================   ========================   ======================
rtehtmlarea::spellchecker             rtehtmlarea_spellchecker   /rte/spellchecker
===================================   ========================   ======================

EXT:t3editor
^^^^^^^^^^^^

====================================   =====================================   =======================================
Old identifier                         New identifier                          New AJAX ID
====================================   =====================================   =======================================
T3Editor::saveCode                     t3editor_save                           /t3editor/save
T3Editor::getPlugins                   t3editor_get_plugins                    /t3editor/get-plugins
T3Editor_TSrefLoader::getTypes         t3editor_tsref                          /t3editor/tsref
T3Editor_TSrefLoader::getDescription   t3editor_tsref                          /t3editor/tsref
CodeCompletion::loadTemplates          t3editor_codecompletion_loadtemplates   /t3editor/codecompletion/load-templates
====================================   =====================================   =======================================

* T3Editor_TSrefLoader::getTypes and T3Editor_TSrefLoader::getDescription have been combined. The separation is done by
  the new parameter `fetch` being either "types" or "description".

EXT:taskcenter
^^^^^^^^^^^^^^

===================================   ========================   ======================
Old identifier                        New identifier             New AJAX ID
===================================   ========================   ======================
Taskcenter::saveCollapseState         taskcenter_collapse        /taskcenter/collapse
Taskcenter::saveSortingState          taskcenter_sort            /taskcenter/sort
===================================   ========================   ======================

EXT:workspaces
^^^^^^^^^^^^^^

===================================   ========================   ======================
Old identifier                        New identifier             New AJAX ID
===================================   ========================   ======================
Workspaces::setWorkspace              workspace_switch           /workspaces/switch
===================================   ========================   ======================


.. index:: PHP-API, Backend
