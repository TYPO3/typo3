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

Extensions or configuration that uses one of the following locallang files will not show a translation anymore.

.. code-block:: php

    'lang/Resources/Private/Language/locallang_alt_intro.xlf'
    'lang/Resources/Private/Language/locallang_alt_doc.xlf'
    'lang/Resources/Private/Language/locallang_login.xlf'
    'lang/Resources/Private/Language/locallang_common.xlf'
    'lang/Resources/Private/Language/locallang_core.xlf'
    'lang/Resources/Private/Language/locallang_general.xlf'
    'lang/Resources/Private/Language/locallang_misc.xlf'
    'lang/Resources/Private/Language/locallang_mod_web_list.xlf'
    'lang/Resources/Private/Language/locallang_tca.xlf'
    'lang/Resources/Private/Language/locallang_tsfe.xlf'
    'lang/Resources/Private/Language/locallang_wizards.xlf'
    'lang/Resources/Private/Language/locallang_browse_links.xlf'
    'lang/Resources/Private/Language/locallang_tcemain.xlf'


Affected Installations
======================

All extensions or configuration that still uses one of the mentioned locallang files.


Migration
=========

Use your own language files.

.. index:: Backend, TCA, NotScanned