
.. include:: /Includes.rst.txt

=====================================
Deprecation: #67991 - Removed ext:cms
=====================================

See :issue:`67991`

Description
===========

Extension `cms` has been removed. Most functionality has been moved to extension `frontend` with version 6.0 already.
The rest of the files have now been moved to other extensions, extension `cms` does not exist anymore.


Impact
======

Language files from EXT:cms are moved to different places into the core. `LLL:EXT:cms/...` references are
rewritten dynamically to the new file locations, but this fallback layer is subject to be removed with CMS 8.

Third party extensions that define a dependency to extension `cms` will get their dependency rewritten to `core`
on the fly during extension installation as a compatibility layer.


Affected Installations
======================

All 3rd party extensions using language labels from extension `cms` or defining a dependency to extension `cms`
in `ext_emconf.php`.


Migration
=========

Update the dependency constraint in `ext_emconf.php` of the affected extension. A typical substitution is
a dependency to extension `frontend`.

Move the following references to the new location of the language file:

* typo3/sysext/cms/web_info/locallang.xlf -> typo3/sysext/frontend/Resources/Private/Language/locallang_webinfo.xlf
* typo3/sysext/cms/locallang_ttc.xlf -> typo3/sysext/frontend/Resources/Private/Language/locallang_ttc.xlf
* typo3/sysext/cms/locallang_tca.xlf -> typo3/sysext/frontend/Resources/Private/Language/locallang_tca.xlf
* typo3/sysext/cms/layout/locallang_db_new_content_el.xlf -> typo3/sysext/backend/Resources/Private/Language/locallang_db_new_content_el.xlf
* typo3/sysext/cms/layout/locallang.xlf -> typo3/sysext/backend/Resources/Private/Language/locallang_layout.xlf
* typo3/sysext/cms/layout/locallang_mod.xlf -> typo3/sysext/backend/Resources/Private/Language/locallang_mod.xlf
* typo3/sysext/cms/locallang_csh_webinfo.xlf -> typo3/sysext/frontend/Resources/Private/Language/locallang_csh_webinfo.xlf
* typo3/sysext/cms/locallang_csh_weblayout.xlf -> typo3/sysext/frontend/Resources/Private/Language/locallang_csh_weblayout.xlf


.. index:: PHP-API, Frontend
