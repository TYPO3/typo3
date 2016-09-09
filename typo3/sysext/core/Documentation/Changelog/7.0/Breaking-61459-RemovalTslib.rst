
.. include:: ../../Includes.txt

===========================================================
Breaking: #61459 - Removal of tslib directory and constant
===========================================================

See :issue:`61459`

Description
===========

The tslib/ directory and the constant PATH_tslib were removed.

Impact
======

Extensions that still use PATH_tslib constant, and reference typo3/sysext/cms/tslib/index_ts.php directly won't work.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses :file:`index_ts.php` directly, or if the main
:file:`index.php` is not replaced with the TYPO3 Update (used on certain install types). The index.php file must be
replaced with the current version from the TYPO3 CMS Core.

Besides scripts are affected that access the time tracking (:code:`$TT`) or typoscript frontend controller (:code:`$TSFE`)
objects instead of using :code:`$GLOBALS['TT']` respectively :code:`$GLOBALS['TSFE']`.


Migration
=========

Remove the constant PATH_tslib from the 3rd party extension, use a current version of index.php and use :code:`$GLOBALS['TT']`
and :code:`$GLOBALS['TSFE']` where necessary.
