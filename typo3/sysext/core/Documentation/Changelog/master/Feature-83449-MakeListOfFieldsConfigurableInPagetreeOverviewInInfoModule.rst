.. include:: ../../Includes.txt

======================================================================================
Feature: #83449 - Make list of fields configurable in Pagetree overview in Info module
======================================================================================

See :issue:`83449`

Description
===========

The available fields in the module "Pagetree overview" in the Info module have been hardcoded to the entries "Basic settings", "Cache and age" and "Record overview".

By using pageTsConfig it is now possible to change the available fields and add additional entries to the selectbox.

.. code-block:: typoscript

            mod.web_info.fieldDefinitions {
                0 {
                    label = LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:pages_0
                    fields = title,uid,alias,starttime,endtime,fe_group,target,url,shortcut,shortcut_mode
                }
                1 {
                    label = LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:pages_1
                    fields = title,uid,###ALL_TABLES###
                }
                2 {
                    label = LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:pages_2
                    fields = title,uid,lastUpdated,newUntil,cache_timeout,php_tree_stop,TSconfig,is_siteroot,fe_login_mode
                }
            }

The string `###ALL_TABLES###` is replaced with a list of table names an editor has access to.

.. index:: Backend, NotScanned
