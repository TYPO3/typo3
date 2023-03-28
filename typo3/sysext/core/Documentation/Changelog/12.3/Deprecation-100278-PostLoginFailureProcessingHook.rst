.. include:: /Includes.rst.txt

.. _deprecation-100278-1679605129:

======================================================
Deprecation: #100278 - PostLoginFailureProcessing hook
======================================================

See :issue:`100278`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postLoginFailureProcessing']`
which can be used to handle custom notifications that a login in a frontend or
backend context failed, has been marked as deprecated.


Impact
======

If the hook is registered in a TYPO3 installation, a PHP :php:`E_USER_DEPRECATED`
error is triggered.

The extension scanner also detects any usage of the deprecated interface as
a strong match, and the definition of the hook as a weak match.


Affected installations
======================

TYPO3 installations with custom extensions using this hook.


Migration
=========

Migrate to the newly introduced PSR-14 event
:ref:`\\TYPO3\\CMS\\Core\\Authentication\\Event\\LoginAttemptFailedEvent <feature-100278-1679604666>`.

.. index:: Backend, Frontend, PHP-API, FullyScanned, ext:core
