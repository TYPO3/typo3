.. include:: /Includes.rst.txt

=================================================================================================
Deprecation: #78647 - Move language files from EXT:lang/locallang_* to Resources/Private/Language
=================================================================================================

See :issue:`78647`

Description
===========

Moved language files from `EXT:lang/locallang_*` to `EXT:lang/Resources/Private/Language`


Impact
======

Language files from `EXT:lang` have been moved to different places into the core.


Affected Installations
======================

All 3rd party extensions that are using language labels from `EXT:lang`


Migration
=========

Move the following references to the new location of the language file:

* lang/locallang_alt_doc.xlf -> lang/Resources/Private/Language/locallang_alt_doc.xlf
* lang/locallang_alt_intro.xlf -> lang/Resources/Private/Language/locallang_alt_intro.xlf
* lang/locallang_browse_links.xlf -> lang/Resources/Private/Language/locallang_browse_links.xlf
* lang/locallang_common.xlf -> lang/Resources/Private/Language/locallang_common.xlf
* lang/locallang_core.xlf -> lang/Resources/Private/Language/locallang_core.xlf
* lang/locallang_csh_be_groups.xlf -> lang/Resources/Private/Language/locallang_csh_be_groups.xlf
* lang/locallang_csh_be_users.xlf -> lang/Resources/Private/Language/locallang_csh_be_users.xlf
* lang/locallang_csh_corebe.xlf -> lang/Resources/Private/Language/locallang_csh_corebe.xlf
* lang/locallang_csh_pages.xlf -> lang/Resources/Private/Language/locallang_csh_pages.xlf
* lang/locallang_csh_sysfilem.xlf -> lang/Resources/Private/Language/locallang_csh_sysfilem.xlf
* lang/locallang_csh_syslang.xlf -> lang/Resources/Private/Language/locallang_csh_syslang.xlf
* lang/locallang_csh_sysnews.xlf -> lang/Resources/Private/Language/locallang_csh_sysnews.xlf
* lang/locallang_csh_web_func.xlf -> func/Resources/Private/Language/locallang_csh_web_func.xlf
* lang/locallang_csh_web_info.xlf -> lang/Resources/Private/Language/locallang_csh_web_info.xlf
* lang/locallang_general.xlf -> lang/Resources/Private/Language/locallang_general.xlf
* lang/locallang_login.xlf -> lang/Resources/Private/Language/locallang_login.xlf
* lang/locallang_misc.xlf -> lang/Resources/Private/Language/locallang_misc.xlf
* lang/locallang_mod_admintools.xlf -> lang/Resources/Private/Language/locallang_mod_admintools.xlf
* lang/locallang_mod_file_list.xlf -> lang/Resources/Private/Language/locallang_mod_file_list.xlf
* lang/locallang_mod_file.xlf -> lang/Resources/Private/Language/locallang_mod_file.xlf
* lang/locallang_mod_help_about.xlf -> lang/Resources/Private/Language/locallang_mod_help_about.xlf
* lang/locallang_mod_help_cshmanual.xlf -> lang/Resources/Private/Language/locallang_mod_help_cshmanual.xlf
* lang/locallang_mod_help.xlf -> lang/Resources/Private/Language/locallang_mod_help.xlf
* lang/locallang_mod_system.xlf -> lang/Resources/Private/Language/locallang_mod_system.xlf
* lang/locallang_mod_usertools.xlf -> lang/Resources/Private/Language/locallang_mod_usertools.xlf
* lang/locallang_mod_user_ws.xlf -> lang/Resources/Private/Language/locallang_mod_user_ws.xlf
* lang/locallang_mod_web_func.xlf -> func/Resources/Private/Language/locallang_mod_web_func.xlf
* lang/locallang_mod_web_info.xlf -> lang/Resources/Private/Language/locallang_mod_web_info.xlf
* lang/locallang_mod_web_list.xlf -> lang/Resources/Private/Language/locallang_mod_web_list.xlf
* lang/locallang_mod_web.xlf -> lang/Resources/Private/Language/locallang_mod_web.xlf
* lang/locallang_show_rechis.xlf -> lang/Resources/Private/Language/locallang_show_rechis.xlf
* lang/locallang_t3lib_fullsearch.xlf -> lang/Resources/Private/Language/locallang_t3lib_fullsearch.xlf
* lang/locallang_tca.xlf -> lang/Resources/Private/Language/locallang_tca.xlf
* lang/locallang_tcemain.xlf -> lang/Resources/Private/Language/locallang_tcemain.xlf
* lang/locallang_tsfe.xlf -> lang/Resources/Private/Language/locallang_tsfe.xlf
* lang/locallang_tsparser.xlf -> lang/Resources/Private/Language/locallang_tsparser.xlf
* lang/locallang_view_help.xlf -> lang/Resources/Private/Language/locallang_view_help.xlf
* lang/locallang_wizards.xlf -> lang/Resources/Private/Language/locallang_wizards.xlf

.. index:: ext:lang
