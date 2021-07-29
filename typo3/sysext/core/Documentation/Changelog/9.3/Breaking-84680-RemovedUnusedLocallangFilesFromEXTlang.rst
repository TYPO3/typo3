.. include:: ../../Includes.txt

===============================================================
Breaking: #84680 - Removed unused locallang files from EXT:lang
===============================================================

See :issue:`84680`

Description
===========

Removed the last unused locallang files from EXT:lang.


Impact
======

Extensions or configuration that use one of the following locallang files will not show a translation anymore.


* :file:`lang/Resources/Private/Language/locallang_alt_intro.xlf`
* :file:`lang/Resources/Private/Language/locallang_alt_doc.xlf`
* :file:`lang/Resources/Private/Language/locallang_login.xlf`
* :file:`lang/Resources/Private/Language/locallang_common.xlf`
* :file:`lang/Resources/Private/Language/locallang_core.xlf`
* :file:`lang/Resources/Private/Language/locallang_general.xlf`
* :file:`lang/Resources/Private/Language/locallang_misc.xlf`
* :file:`lang/Resources/Private/Language/locallang_mod_web_list.xlf`
* :file:`lang/Resources/Private/Language/locallang_tca.xlf`
* :file:`lang/Resources/Private/Language/locallang_tsfe.xlf`
* :file:`lang/Resources/Private/Language/locallang_wizards.xlf`
* :file:`lang/Resources/Private/Language/locallang_browse_links.xlf`
* :file:`lang/Resources/Private/Language/locallang_tcemain.xlf`


Affected Installations
======================

All extensions or configuration that still use any of the mentioned locallang files.


Migration
=========

Use your own language files.

.. index:: Backend, TCA, NotScanned