.. include:: /Includes.rst.txt

.. _breaking-96829:

=========================================================
Breaking: #96829 - Removed BackendUtility->getFuncInput()
=========================================================

See :issue:`96829`

Description
===========

Method :php:`BackendUtility::getFuncInput()` is incompatible with
`Content-Security-Policy` HTTP headers due to its onchange JavaScript
handler, and has been removed.

Impact
======

Instances with extensions using the method will raise a fatal
PHP error upon use.

Affected Installations
======================

The method is part of very old-school backend module code and of limited use.
TYPO3 Core code does not use it since at least v9, it is relatively unlikely
backend modules of extensions still use this method. The extension scanner
finds usages with a strong match.

Migration
=========

No direct migration available. The input field HTML should most likely be inlined
to a template and eventual JavaScript events should be handled with a JavaScript
module.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
