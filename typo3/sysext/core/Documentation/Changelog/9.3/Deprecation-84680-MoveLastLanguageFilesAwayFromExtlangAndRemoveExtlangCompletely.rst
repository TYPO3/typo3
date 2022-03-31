.. include:: /Includes.rst.txt

================================================================================================
Deprecation: #84680 - Move last language files away from ext:lang and remove ext:lang completely
================================================================================================

See :issue:`84680`

Description
===========

Move last language files away from ext:lang and remove ext:lang completely.


Impact
======

Extensions or configuration that use language paths to EXT:lang/Resources/Private/Language/* will trigger a
PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All extensions or configuration that still uses EXT:lang for translations.


Migration
=========

Migrate the current location to the new location from the list below.

.. code-block:: php

    'lang/Resources/Private/Language/locallang_alt_intro.xlf' => 'about/Resources/Private/Language/Modules/locallang_alt_intro.xlf'
    'lang/Resources/Private/Language/locallang_alt_doc.xlf' => 'backend/Resources/Private/Language/locallang_alt_doc.xlf'
    'lang/Resources/Private/Language/locallang_login.xlf' => 'backend/Resources/Private/Language/locallang_login.xlf'
    'lang/Resources/Private/Language/locallang_common.xlf' => 'core/Resources/Private/Language/locallang_common.xlf'
    'lang/Resources/Private/Language/locallang_core.xlf' => 'core/Resources/Private/Language/locallang_core.xlf'
    'lang/Resources/Private/Language/locallang_general.xlf' => 'core/Resources/Private/Language/locallang_general.xlf'
    'lang/Resources/Private/Language/locallang_misc.xlf' => 'core/Resources/Private/Language/locallang_misc.xlf'
    'lang/Resources/Private/Language/locallang_mod_web_list.xlf' => 'core/Resources/Private/Language/locallang_mod_web_list.xlf'
    'lang/Resources/Private/Language/locallang_tca.xlf' => 'core/Resources/Private/Language/locallang_tca.xlf'
    'lang/Resources/Private/Language/locallang_tsfe.xlf' => 'core/Resources/Private/Language/locallang_tsfe.xlf'
    'lang/Resources/Private/Language/locallang_wizards.xlf' => 'core/Resources/Private/Language/locallang_wizards.xlf'
    'lang/Resources/Private/Language/locallang_browse_links.xlf' => 'recordlist/Resources/Private/Language/locallang_browse_links.xlf'
    'lang/Resources/Private/Language/locallang_tcemain.xlf' => 'workspaces/Resources/Private/Language/locallang_tcemain.xlf'

.. index:: Backend, TCA, NotScanned
