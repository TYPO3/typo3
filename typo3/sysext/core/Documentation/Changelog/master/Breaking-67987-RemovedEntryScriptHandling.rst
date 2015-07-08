================================================
Breaking: #67987 - Removed entry script handling
================================================

Description
===========

Definition and handling of constants ``TYPO3_MOD_PATH`` and ``PATH_typo3_mod`` have been dropped.
These constants were important for modules that were still not called through the ``mod.php``
``_DISPATCH`` system that was introduces in TYPO3 CMS version 4.2.
It is required to route modules through ``typo3/mod.php`` from now on in case the module relies
on the definition of those constants.

The following old entry scripts were removed:

* typo3/sysext/cms/layout/db_layout.php, use ``\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_layout')`` to link to the module
* typo3/sysext/cms/layout/db_new_content_el.php, use ``\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('new_content_element')`` to link to the module


Impact
======

Path resolving may fail for script that rely on entry points different from ``typo3/mod.php``
Constants ``TYPO3_MOD_PATH`` and ``PATH_typo3_mod`` are not defined anymore and scripts may
throw a PHP warning level error if they access these constants.


Affected Installations
======================

Installations may fail if linking to modules that use an entry script with a ``conf.php`` file that do not
use ``$MCONF['script'] = '_DISPATCH';``. Those modules must be adapted to ``mod.php`` entry point and may
need adaption of further references that are defined relative to the entry script.

Searching for extension with backend modules that define ``TYPO3_MOD_PATH`` is a good entry point.