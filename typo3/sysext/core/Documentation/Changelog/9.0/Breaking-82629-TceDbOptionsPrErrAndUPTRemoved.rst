.. include:: ../../Includes.txt

===========================================================
Breaking: #82629 - Removed tce_db options "prErr" and "uPT"
===========================================================

See :issue:`82629`

Description
===========

The two options `prErr` ("print errors") and `uPT` ("update page tree"), usually set via GET/POST
when calling TYPO3's Backend endpoint `tce_db` (DataHandler actions within the TYPO3 Backend),
have been removed, and are now automatically evaluated when the endpoint is called.

The option `prErr` added possible errors to the Message Queue. The option `uPT` triggered an update
of the pagetree after a page-related action was made.

Both options are dropped as the functionality is enabled by default.

The corresponding methods have been adjusted:

* `TYPO3\CMS\Core\DataHandling\DataHandler->printLogErrorMessages()` does not need a method argument anymore.
* The public property `TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->prErr` is removed
* The public property `TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->uPT` is removed


Impact
======

Calling `tce_db` with any of the two options has no effect anymore.


Affected Installations
======================

Installations with third-party extensions accessing the entrypoint `tce_db` or calling
`DataHandler->printLogErrorMessages()` via PHP.


Migration
=========

Remove any of the parameters in the PHP code and everything will continue to work as before.

.. index:: Backend, PHP-API, FullyScanned