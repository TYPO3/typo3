
.. include:: /Includes.rst.txt

=========================================================================
Breaking: #66286 - Page TSconfig options to hide Web Info modules renamed
=========================================================================

See :issue:`66286`

Description
===========

WEB > Info options in the function menu have new names in Page TSconfig properties to hide these modules


Impact
======

Page TSconfig options in `mod.web_info.menu.function` use new class names.


Affected Installations
======================

Installation which have options in the Info module disabled by using Page TSconfig `mod.web_info.menu.function`.


Migration
=========

The following properties under `mod.web_info.menu.function` have to be renamed:

* tx_cms_webinfo_page -> TYPO3\CMS\Frontend\Controller\PageInformationController
* tx_cms_webinfo_lang -> TYPO3\CMS\Frontend\Controller\TranslationStatusController
* tx_belog_webinfo -> TYPO3\CMS\Belog\Module\BackendLogModuleBootstrap
* tx_infopagetsconfig_webinfo -> TYPO3\CMS\InfoPagetsconfig\Controller\InfoPageTyposcriptConfigController
* tx_linkvalidator_ModFuncReport -> TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport
* tx_indexedsearch_modfunc1 : removed, indexed_search has its own module
* tx_indexedsearch_modfunc2 : removed, indexed_search has its own module


.. index:: TSConfig, Backend
