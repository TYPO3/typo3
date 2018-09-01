.. include:: ../../Includes.txt

=============================================
Deprecation: #85707 - LoginFramesetController
=============================================

See :issue:`85707`

Description
===========

The class :php:`TYPO3\CMS\Backend\Controller\LoginFramesetController` builds a simple HTML frameset
and has been replaced by using the full logic within :php:`LoginController` or a request to
`index.php?loginRefresh=1` directly.


Impact
======

Instantiating the LoginFramesetController class will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom logic using the rare functionality of LoginFramesetController.


Migration
=========

Reference `index.php?loginRefresh=1` in the callers code directly, or re-implement the frameset if
necessary.

.. index:: Backend, FullyScanned, ext:backend
